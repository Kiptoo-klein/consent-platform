<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consent_campaigns')) {
            Schema::create(
                'consent_campaigns',
                function (Blueprint $table): void {
                    $table->id();

                    $table->foreignId('organization_id')
                        ->constrained()
                        ->cascadeOnDelete();

                    $table->foreignId('consent_template_id')
                        ->constrained()
                        ->restrictOnDelete();

                    $table->foreignId(
                        'consent_template_version_id'
                    )
                        ->constrained()
                        ->restrictOnDelete();

                    $table->foreignId('created_by')
                        ->nullable()
                        ->constrained('users')
                        ->nullOnDelete();

                    $table->string('name');

                    $table->unsignedSmallInteger(
                        'recipient_count'
                    );

                    $table->timestamp('expires_at')
                        ->nullable()
                        ->index();

                    $table->timestamps();

                    $table->index(
                        [
                            'organization_id',
                            'created_at',
                        ],
                        'consent_campaign_org_created_index'
                    );
                }
            );
        }

        if (
            Schema::hasTable('consent_sessions')
            && ! Schema::hasColumn(
                'consent_sessions',
                'consent_campaign_id'
            )
        ) {
            Schema::table(
                'consent_sessions',
                function (Blueprint $table): void {
                    $table->foreignId(
                        'consent_campaign_id'
                    )
                        ->nullable()
                        ->after('signing_station_id')
                        ->constrained(
                            'consent_campaigns'
                        )
                        ->nullOnDelete();
                }
            );
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('consent_sessions')
            && Schema::hasColumn(
                'consent_sessions',
                'consent_campaign_id'
            )
        ) {
            Schema::table(
                'consent_sessions',
                function (Blueprint $table): void {
                    $table->dropConstrainedForeignId(
                        'consent_campaign_id'
                    );
                }
            );
        }

        Schema::dropIfExists(
            'consent_campaigns'
        );
    }
};
