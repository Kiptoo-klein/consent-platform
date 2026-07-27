<?php

namespace App\Services;

use App\Mail\ConsentSigningRequestMail;
use App\Models\ConsentNotification;
use App\Models\ConsentSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ConsentNotificationService
{
    public function eligibilityError(
        ConsentSession $consentSession
    ): ?string {
        if ($consentSession->signing_station_id !== null) {
            return 'Email delivery is only available for individual consent records.';
        }

        if (! filled($consentSession->signer_email)) {
            return 'This consent record does not have a signer email address.';
        }

        if ($consentSession->isCompleted()) {
            return 'A completed consent record no longer needs signing reminders.';
        }

        if ($consentSession->isCancelled()) {
            return 'A cancelled consent record cannot receive signing emails.';
        }

        if ($consentSession->isExpired()) {
            return 'An expired consent record cannot receive signing emails.';
        }

        if (
            $consentSession->expires_at !== null
            && ! CarbonImmutable::parse(
                $consentSession->expires_at
            )->isFuture()
        ) {
            return 'The signing deadline has already passed.';
        }

        return null;
    }

    public function sendInitial(
        ConsentSession $consentSession,
        ?int $actorUserId,
        string $trigger
    ): ConsentNotification {
        $hasSuccessfulInitial = ConsentNotification::query()
            ->where(
                'consent_session_id',
                $consentSession->id
            )
            ->whereIn('type', [
                ConsentNotification::TYPE_INITIAL,
                ConsentNotification::TYPE_RESEND,
            ])
            ->where(
                'status',
                ConsentNotification::STATUS_SENT
            )
            ->exists();

        return $this->send(
            consentSession: $consentSession,
            type: $hasSuccessfulInitial
                ? ConsentNotification::TYPE_RESEND
                : ConsentNotification::TYPE_INITIAL,
            trigger: $trigger,
            actorUserId: $actorUserId,
            daysBeforeDeadline: null
        );
    }

    public function sendManualReminder(
        ConsentSession $consentSession,
        ?int $actorUserId
    ): ConsentNotification {
        return $this->send(
            consentSession: $consentSession,
            type: ConsentNotification::TYPE_MANUAL_REMINDER,
            trigger: ConsentNotification::TRIGGER_MANUAL,
            actorUserId: $actorUserId,
            daysBeforeDeadline: null
        );
    }

    public function sendAutomaticReminder(
        ConsentSession $consentSession,
        int $daysBeforeDeadline
    ): ?ConsentNotification {
        if (
            $this->automaticReminderAlreadySent(
                $consentSession,
                $daysBeforeDeadline
            )
        ) {
            return null;
        }

        $retryMinutes = max(
            1,
            (int) config(
                'consent-notifications.automatic_retry_minutes',
                60
            )
        );

        $recentAttemptExists = ConsentNotification::query()
            ->where(
                'consent_session_id',
                $consentSession->id
            )
            ->where(
                'type',
                ConsentNotification::TYPE_AUTOMATIC_REMINDER
            )
            ->where(
                'days_before_deadline',
                $daysBeforeDeadline
            )
            ->where(
                'created_at',
                '>=',
                now()->subMinutes($retryMinutes)
            )
            ->exists();

        if ($recentAttemptExists) {
            return null;
        }

        return $this->send(
            consentSession: $consentSession,
            type: ConsentNotification::TYPE_AUTOMATIC_REMINDER,
            trigger: ConsentNotification::TRIGGER_SCHEDULER,
            actorUserId: null,
            daysBeforeDeadline: $daysBeforeDeadline
        );
    }

    public function automaticReminderAlreadySent(
        ConsentSession $consentSession,
        int $daysBeforeDeadline
    ): bool {
        return ConsentNotification::query()
            ->where(
                'consent_session_id',
                $consentSession->id
            )
            ->where(
                'type',
                ConsentNotification::TYPE_AUTOMATIC_REMINDER
            )
            ->where(
                'days_before_deadline',
                $daysBeforeDeadline
            )
            ->where(
                'status',
                ConsentNotification::STATUS_SENT
            )
            ->exists();
    }

    public function wasRecentlySent(
        ConsentSession $consentSession
    ): bool {
        $cooldownMinutes = max(
            1,
            (int) config(
                'consent-notifications.manual_cooldown_minutes',
                5
            )
        );

        return ConsentNotification::query()
            ->where(
                'consent_session_id',
                $consentSession->id
            )
            ->where(
                'status',
                ConsentNotification::STATUS_SENT
            )
            ->where(
                'sent_at',
                '>=',
                now()->subMinutes($cooldownMinutes)
            )
            ->exists();
    }

    private function send(
        ConsentSession $consentSession,
        string $type,
        string $trigger,
        ?int $actorUserId,
        ?int $daysBeforeDeadline
    ): ConsentNotification {
        $consentSession->loadMissing([
            'organization',
            'consentTemplate',
        ]);

        $recipient = trim(
            (string) $consentSession->signer_email
        );

        $templateTitle =
            $consentSession->consentTemplate?->title
            ?? 'Consent document';

        $organizationName =
            $consentSession->organization?->name
            ?? config('app.name', 'Consent Platform');

        $isReminder = in_array(
            $type,
            [
                ConsentNotification::TYPE_MANUAL_REMINDER,
                ConsentNotification::TYPE_AUTOMATIC_REMINDER,
            ],
            true
        );

        $subject = $isReminder
            ? "Reminder: {$templateTitle} requires your signature"
            : "Consent request: {$templateTitle}";

        $introMessage = $isReminder
            ? "This is a reminder from {$organizationName} to review and sign your consent document."
            : "{$organizationName} has sent you a consent document to review and sign securely.";

        $notification = DB::transaction(
            function () use (
                $consentSession,
                $actorUserId,
                $type,
                $trigger,
                $recipient,
                $subject,
                $introMessage,
                $daysBeforeDeadline
            ): ConsentNotification {
                ConsentSession::query()
                    ->whereKey($consentSession->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                return ConsentNotification::query()->create([
                    'organization_id' =>
                        $consentSession->organization_id,
                    'consent_session_id' =>
                        $consentSession->id,
                    'actor_user_id' => $actorUserId,
                    'type' => $type,
                    'trigger' => $trigger,
                    'status' =>
                        ConsentNotification::STATUS_PROCESSING,
                    'recipient_email' => $recipient,
                    'subject' => $subject,
                    'message' => $introMessage,
                    'days_before_deadline' =>
                        $daysBeforeDeadline,
                    'scheduled_for' =>
                        $trigger === ConsentNotification::TRIGGER_SCHEDULER
                            ? now()
                            : null,
                    'metadata' => [
                        'template_title' =>
                            $consentSession->consentTemplate?->title,
                        'expires_at' =>
                            $consentSession->expires_at?->toIso8601String(),
                    ],
                ]);
            },
            3
        );

        try {
            Mail::to($recipient)->send(
                new ConsentSigningRequestMail(
                    consentSession: $consentSession,
                    mailSubject: $subject,
                    introMessage: $introMessage,
                    notificationType: $type
                )
            );

            $notification->forceFill([
                'status' =>
                    ConsentNotification::STATUS_SENT,
                'sent_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ])->save();
        } catch (Throwable $exception) {
            report($exception);

            $notification->forceFill([
                'status' =>
                    ConsentNotification::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => mb_substr(
                    $exception->getMessage(),
                    0,
                    2000
                ),
            ])->save();
        }

        return $notification->refresh();
    }
}
