<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'organization_invoice_reminder_preferences',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('organization_id')
                    ->unique()
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->boolean(
                        'before_due_reminders_enabled'
                    )
                    ->default(true);

                $table
                    ->boolean(
                        'overdue_reminders_enabled'
                    )
                    ->default(true);

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
            'organization_invoice_reminder_preferences'
        );
    }
};
