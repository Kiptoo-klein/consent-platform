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
        Schema::create('consent_template_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('consent_template_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('version_number');

            $table->string('title');

            $table->text('description')
                ->nullable();

            $table->json('template_schema');

            $table->timestamp('published_at');

            $table->foreignId('published_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique([
                'consent_template_id',
                'version_number',
            ]);

            $table->index([
                'consent_template_id',
                'published_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consent_template_versions');
    }
};
