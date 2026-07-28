<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organization_subscriptions', function (Blueprint $table) {
            $table->id();

            /*
             * Each organization has one current subscription.
             */
            $table->foreignId('organization_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Plans cannot be deleted while organizations still use them.
             */
            $table->foreignId('subscription_plan_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Supported values will include:
             * trialing, active, past_due, cancelled,
             * expired, and suspended.
             */
            $table->string('status', 30)
                ->default('trialing')
                ->index();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamp('current_period_starts_at')
                ->nullable();

            $table->timestamp('current_period_ends_at')
                ->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_subscriptions');
    }
};
