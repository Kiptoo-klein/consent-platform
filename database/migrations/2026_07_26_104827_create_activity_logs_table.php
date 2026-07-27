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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            /*
             * The organization affected by the activity.
             *
             * This is nullable because some platform-wide events may not
             * belong to a specific organization.
             */
            $table
                ->foreignId('organization_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
             * The authenticated user who performed the action.
             *
             * This remains nullable so system-generated events can also
             * be recorded.
             */
            $table
                ->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
             * A short machine-friendly action name.
             *
             * Examples:
             * user.created
             * user.updated
             * user.archived
             * user.restored
             */
            $table->string('action');

            /*
             * A readable description for administrators.
             */
            $table->text('description');

            /*
             * Polymorphic target of the activity.
             *
             * This allows the same log table to reference users,
             * organizations, consent forms, patients, signatures, and
             * other models added later.
             */
            $table->nullableMorphs('subject');

            /*
             * Optional structured context such as old and new values.
             */
            $table->json('properties')->nullable();

            /*
             * Request information useful for security and auditing.
             */
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            /*
             * Helpful indexes for filtering large audit histories.
             */
            $table->index(['organization_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
