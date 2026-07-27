<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('signing_station_flows')) {
            Schema::create('signing_station_flows', function (Blueprint $table): void {
                $table->id();
                $table->uuid('flow_token')->unique();
                $table->unsignedBigInteger('organization_id')->index();
                $table->unsignedBigInteger('signing_station_id')->index();
                $table->unsignedBigInteger('consent_session_id')
                    ->nullable()
                    ->unique();
                $table->string('status', 32)->index();
                $table->string('current_stage', 32)->nullable()->index();
                $table->string('abandonment_reason', 64)->nullable();
                $table->timestamp('started_at')->nullable()->index();
                $table->timestamp('review_confirmed_at')->nullable();
                $table->timestamp('details_started_at')->nullable();
                $table->timestamp('consent_started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('abandoned_at')->nullable()->index();
                $table->timestamp('last_activity_at')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(
                    ['organization_id', 'started_at'],
                    'station_flows_org_started_index'
                );
                $table->index(
                    ['signing_station_id', 'status'],
                    'station_flows_station_status_index'
                );
            });
        }

        $this->backfillExistingConsentSessions();
    }

    public function down(): void
    {
        Schema::dropIfExists('signing_station_flows');
    }

    private function backfillExistingConsentSessions(): void
    {
        if (! Schema::hasTable('consent_sessions')) {
            return;
        }

        DB::table('consent_sessions')
            ->whereNotNull('signing_station_id')
            ->whereNotIn('id', function ($query): void {
                $query->select('consent_session_id')
                    ->from('signing_station_flows')
                    ->whereNotNull('consent_session_id');
            })
            ->orderBy('id')
            ->chunkById(200, function ($sessions): void {
                $rows = [];

                foreach ($sessions as $session) {
                    $status = match ((string) $session->status) {
                        'completed' => 'completed',
                        'cancelled' => 'cancelled',
                        default => 'signing',
                    };

                    $stage = $status === 'completed'
                        ? 'completed'
                        : 'signing';

                    $startedAt = $session->started_at
                        ?? $session->created_at
                        ?? now();

                    $rows[] = [
                        'flow_token' => (string) Str::uuid(),
                        'organization_id' => $session->organization_id,
                        'signing_station_id' => $session->signing_station_id,
                        'consent_session_id' => $session->id,
                        'status' => $status,
                        'current_stage' => $stage,
                        'abandonment_reason' => $status === 'cancelled'
                            ? 'historical_cancellation'
                            : null,
                        'started_at' => $startedAt,
                        'review_confirmed_at' => null,
                        'details_started_at' => null,
                        'consent_started_at' => $startedAt,
                        'completed_at' => $session->completed_at ?? null,
                        'cancelled_at' => $session->cancelled_at ?? null,
                        'abandoned_at' => null,
                        'last_activity_at' => $session->completed_at
                            ?? $session->cancelled_at
                            ?? $session->updated_at
                            ?? $startedAt,
                        'metadata' => json_encode([
                            'backfilled' => true,
                        ]),
                        'created_at' => $session->created_at ?? now(),
                        'updated_at' => $session->updated_at ?? now(),
                    ];
                }

                if ($rows !== []) {
                    DB::table('signing_station_flows')->insert($rows);
                }
            });
    }
};
