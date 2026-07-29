<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'subscription_invoice_notifications',
            function (Blueprint $table): void {
                $table
                    ->foreignId(
                        'retry_of_notification_id'
                    )
                    ->nullable()
                    ->after('recipient_user_id')
                    ->constrained(
                        'subscription_invoice_notifications'
                    )
                    ->nullOnDelete();

                $table
                    ->foreignId(
                        'retry_requested_by_user_id'
                    )
                    ->nullable()
                    ->after(
                        'retry_of_notification_id'
                    )
                    ->constrained('users')
                    ->nullOnDelete();

                $table->index(
                    [
                        'retry_of_notification_id',
                        'created_at',
                    ],
                    'invoice_notification_retry_created_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'subscription_invoice_notifications',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'invoice_notification_retry_created_idx'
                );

                $table->dropConstrainedForeignId(
                    'retry_requested_by_user_id'
                );

                $table->dropConstrainedForeignId(
                    'retry_of_notification_id'
                );
            }
        );
    }
};
