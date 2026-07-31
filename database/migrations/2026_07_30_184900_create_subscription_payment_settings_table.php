<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'subscription_payment_settings',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string('singleton_key', 50)
                    ->default('default')
                    ->unique();

                $table
                    ->boolean('mpesa_enabled')
                    ->default(false);

                $table
                    ->string('mpesa_type', 20)
                    ->nullable();

                $table
                    ->string(
                        'mpesa_business_number',
                        50
                    )
                    ->nullable();

                $table
                    ->text(
                        'mpesa_account_reference_instructions'
                    )
                    ->nullable();

                $table
                    ->text('mpesa_instructions')
                    ->nullable();

                $table
                    ->boolean('bank_enabled')
                    ->default(false);

                $table
                    ->string('bank_name', 160)
                    ->nullable();

                $table
                    ->string(
                        'bank_account_name',
                        200
                    )
                    ->nullable();

                $table
                    ->string(
                        'bank_account_number',
                        120
                    )
                    ->nullable();

                $table
                    ->string('bank_branch', 160)
                    ->nullable();

                $table
                    ->string(
                        'bank_swift_code',
                        50
                    )
                    ->nullable();

                $table
                    ->text(
                        'bank_reference_instructions'
                    )
                    ->nullable();

                $table
                    ->text('bank_instructions')
                    ->nullable();

                $table
                    ->string(
                        'billing_contact_email'
                    )
                    ->nullable();

                $table
                    ->string(
                        'billing_contact_phone',
                        50
                    )
                    ->nullable();

                $table
                    ->text(
                        'additional_instructions'
                    )
                    ->nullable();

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
            'subscription_payment_settings'
        );
    }
};
