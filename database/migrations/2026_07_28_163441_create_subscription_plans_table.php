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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            /*
             * Total seats include the single Organization Admin.
             * Organization Admin is fixed at exactly one per organization.
             */
            $table->unsignedSmallInteger('max_users');

            $table->unsignedSmallInteger(
                'max_consent_managers'
            );

            $table->unsignedSmallInteger('max_staff');
            $table->unsignedSmallInteger('max_auditors');

            $table->boolean('is_active')->default(true);

            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
