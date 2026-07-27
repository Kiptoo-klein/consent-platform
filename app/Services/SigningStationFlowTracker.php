<?php

namespace App\Services;

use App\Models\ConsentSession;
use App\Models\SigningStation;
use App\Models\SigningStationFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SigningStationFlowTracker
{
    public function beginReview(
        Request $request,
        SigningStation $station
    ): ?SigningStationFlow {
        if (! $this->ready()) {
            return null;
        }

        $flow = $this->currentFlow($request, $station);

        if (! $flow) {
            $flow = SigningStationFlow::query()->create([
                'flow_token' => (string) Str::uuid(),
                'organization_id' => $station->organization_id,
                'signing_station_id' => $station->id,
                'status' => SigningStationFlow::STATUS_REVIEWING,
                'current_stage' => SigningStationFlow::STAGE_REVIEW,
                'started_at' => now(),
                'last_activity_at' => now(),
            ]);

            $request->session()->put(
                $this->sessionKey($station),
                $flow->flow_token
            );

            return $flow;
        }

        $flow->forceFill([
            'status' => SigningStationFlow::STATUS_REVIEWING,
            'current_stage' => SigningStationFlow::STAGE_REVIEW,
            'last_activity_at' => now(),
        ])->save();

        return $flow;
    }

    public function confirmReview(
        Request $request,
        SigningStation $station
    ): void {
        $flow = $this->currentFlow($request, $station)
            ?? $this->beginReview($request, $station);

        if (! $flow) {
            return;
        }

        $flow->forceFill([
            'status' => SigningStationFlow::STATUS_DETAILS,
            'current_stage' => SigningStationFlow::STAGE_DETAILS,
            'review_confirmed_at' => $flow->review_confirmed_at ?? now(),
            'details_started_at' => $flow->details_started_at ?? now(),
            'last_activity_at' => now(),
        ])->save();
    }

    public function touchDetails(
        Request $request,
        SigningStation $station
    ): void {
        $flow = $this->currentFlow($request, $station)
            ?? $this->beginReview($request, $station);

        if (! $flow) {
            return;
        }

        $flow->forceFill([
            'status' => SigningStationFlow::STATUS_DETAILS,
            'current_stage' => SigningStationFlow::STAGE_DETAILS,
            'details_started_at' => $flow->details_started_at ?? now(),
            'last_activity_at' => now(),
        ])->save();
    }

    public function attachConsent(
        Request $request,
        SigningStation $station,
        ConsentSession $consentSession
    ): void {
        $flow = $this->currentFlow($request, $station)
            ?? $this->beginReview($request, $station);

        if (! $flow) {
            return;
        }

        $flow->forceFill([
            'consent_session_id' => $consentSession->id,
            'status' => SigningStationFlow::STATUS_SIGNING,
            'current_stage' => SigningStationFlow::STAGE_SIGNING,
            'consent_started_at' => $flow->consent_started_at ?? now(),
            'last_activity_at' => now(),
        ])->save();
    }

    public function cancelStationFlow(
        Request $request,
        SigningStation $station,
        bool $timedOut
    ): void {
        if (! $this->ready()) {
            return;
        }

        $flow = $this->currentFlow($request, $station);

        if ($flow) {
            $now = now();

            $flow->forceFill([
                'status' => $timedOut
                    ? SigningStationFlow::STATUS_ABANDONED
                    : SigningStationFlow::STATUS_CANCELLED,
                'abandonment_reason' => $timedOut
                    ? 'inactivity_timeout'
                    : 'user_cancelled',
                'abandoned_at' => $timedOut ? $now : null,
                'cancelled_at' => $timedOut ? null : $now,
                'last_activity_at' => $now,
            ])->save();
        }

        $this->forget($request, $station);
    }

    public function completeConsent(
        Request $request,
        ConsentSession $consentSession
    ): void {
        if (! $this->ready() || ! $consentSession->signing_station_id) {
            return;
        }

        $flow = $this->flowForConsent($consentSession);

        if (! $flow) {
            return;
        }

        $now = now();

        $flow->forceFill([
            'status' => SigningStationFlow::STATUS_COMPLETED,
            'current_stage' => SigningStationFlow::STAGE_COMPLETED,
            'completed_at' => $consentSession->completed_at ?? $now,
            'cancelled_at' => null,
            'abandoned_at' => null,
            'abandonment_reason' => null,
            'last_activity_at' => $now,
        ])->save();

        $consentSession->loadMissing('signingStation');

        if ($consentSession->signingStation) {
            $this->forget($request, $consentSession->signingStation);
        }
    }

    public function cancelConsent(
        Request $request,
        ConsentSession $consentSession,
        bool $timedOut
    ): void {
        if (! $this->ready() || ! $consentSession->signing_station_id) {
            return;
        }

        $flow = $this->flowForConsent($consentSession);

        if (! $flow || $flow->status === SigningStationFlow::STATUS_COMPLETED) {
            return;
        }

        $now = now();

        $flow->forceFill([
            'status' => $timedOut
                ? SigningStationFlow::STATUS_ABANDONED
                : SigningStationFlow::STATUS_CANCELLED,
            'current_stage' => SigningStationFlow::STAGE_SIGNING,
            'abandonment_reason' => $timedOut
                ? 'inactivity_timeout'
                : 'user_cancelled',
            'abandoned_at' => $timedOut ? $now : null,
            'cancelled_at' => $timedOut ? null : $now,
            'last_activity_at' => $now,
        ])->save();

        $consentSession->loadMissing('signingStation');

        if ($consentSession->signingStation) {
            $this->forget($request, $consentSession->signingStation);
        }
    }

    private function currentFlow(
        Request $request,
        SigningStation $station
    ): ?SigningStationFlow {
        if (! $this->ready()) {
            return null;
        }

        $token = $request->session()->get(
            $this->sessionKey($station)
        );

        if (! is_string($token) || $token === '') {
            return null;
        }

        return SigningStationFlow::query()
            ->where('flow_token', $token)
            ->where('organization_id', $station->organization_id)
            ->where('signing_station_id', $station->id)
            ->whereIn('status', [
                SigningStationFlow::STATUS_REVIEWING,
                SigningStationFlow::STATUS_DETAILS,
                SigningStationFlow::STATUS_SIGNING,
            ])
            ->first();
    }

    private function flowForConsent(
        ConsentSession $consentSession
    ): ?SigningStationFlow {
        $existing = SigningStationFlow::query()
            ->where('consent_session_id', $consentSession->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $fresh = $consentSession->fresh(['signingStation'])
            ?? $consentSession;

        if (! $fresh->signing_station_id) {
            return null;
        }

        return SigningStationFlow::query()->firstOrCreate(
            [
                'consent_session_id' => $fresh->id,
            ],
            [
                'flow_token' => (string) Str::uuid(),
                'organization_id' => $fresh->organization_id,
                'signing_station_id' => $fresh->signing_station_id,
                'status' => SigningStationFlow::STATUS_SIGNING,
                'current_stage' => SigningStationFlow::STAGE_SIGNING,
                'started_at' => $fresh->started_at
                    ?? $fresh->created_at
                    ?? now(),
                'consent_started_at' => $fresh->started_at
                    ?? $fresh->created_at
                    ?? now(),
                'last_activity_at' => now(),
            ]
        );
    }

    private function forget(
        Request $request,
        SigningStation $station
    ): void {
        $request->session()->forget($this->sessionKey($station));
    }

    private function sessionKey(SigningStation $station): string
    {
        return 'signing_station_flow_'.$station->id;
    }

    private function ready(): bool
    {
        return Schema::hasTable('signing_station_flows');
    }
}
