<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signing_stations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('consent_template_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('name');

            $table->string('station_token', 64)
                ->unique();

            $table->boolean('active')
                ->default(true);

            $table->boolean('require_email')
                ->default(false);

            $table->boolean('require_reference')
                ->default(false);

            $table->unsignedInteger('auto_reset_seconds')
                ->default(8);

            $table->timestamps();

            $table->index([
                'organization_id',
                'active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signing_stations');
    }
};
