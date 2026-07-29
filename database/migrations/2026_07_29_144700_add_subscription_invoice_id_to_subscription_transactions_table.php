<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'subscription_transactions',
            function (Blueprint $table): void {
                $table
                    ->foreignId('subscription_invoice_id')
                    ->nullable()
                    ->after('organization_subscription_id')
                    ->constrained('subscription_invoices')
                    ->nullOnDelete();

                $table->index([
                    'subscription_invoice_id',
                    'status',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'subscription_transactions',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'subscription_invoice_id',
                    'status',
                ]);

                $table->dropConstrainedForeignId(
                    'subscription_invoice_id'
                );
            }
        );
    }
};
