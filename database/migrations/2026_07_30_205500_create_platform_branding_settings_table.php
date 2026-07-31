<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'platform_branding_settings',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string('singleton_key', 50)
                    ->default('default')
                    ->unique();

                $table
                    ->string('platform_name', 120)
                    ->default('eConsent');

                $table
                    ->string('short_name', 10)
                    ->default('eC');

                $table
                    ->string('tagline', 255)
                    ->nullable();

                $table
                    ->text('description')
                    ->nullable();

                $table
                    ->string('logo_path')
                    ->nullable();

                $table
                    ->string('favicon_path')
                    ->nullable();

                $table
                    ->string('primary_color', 7)
                    ->default('#312E81');

                $table
                    ->string('accent_color', 7)
                    ->default('#4F46E5');

                $table
                    ->string('pdf_primary_color', 7)
                    ->default('#312E81');

                $table
                    ->string('support_email')
                    ->nullable();

                $table
                    ->string('website_url')
                    ->nullable();

                $table
                    ->text('footer_text')
                    ->nullable();

                $table
                    ->foreignId('updated_by_user_id')
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
            'platform_branding_settings'
        );
    }
};
