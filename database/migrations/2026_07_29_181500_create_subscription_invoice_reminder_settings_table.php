<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'subscription_invoice_reminder_settings',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string('singleton_key', 50)
                    ->default('default')
                    ->unique();

                $table
                    ->boolean(
                        'automatic_reminders_enabled'
                    )
                    ->default(true);

                $table->json('before_due_days');
                $table->json('overdue_days');

                $table
                    ->unsignedSmallInteger(
                        'automatic_retry_minutes'
                    )
                    ->default(60);

                $table
                    ->unsignedSmallInteger(
                        'manual_retry_minutes'
                    )
                    ->default(5);

                $table
                    ->foreignId(
                        'updated_by_user_id'
                    )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'subscription_invoice_reminder_settings'
        );
    }
};
