<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'email_quota_states',
            function (Blueprint $table): void {
                $table
                    ->unsignedTinyInteger('id')
                    ->primary();

                $table->timestamps();
            }
        );

        DB::table('email_quota_states')->insert([
            'id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create(
            'email_quota_attempts',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string('category', 80)
                    ->index();

                $table
                    ->string('job_key', 190)
                    ->nullable()
                    ->index();

                $table
                    ->string('priority', 20)
                    ->default('normal');

                $table
                    ->string('status', 20)
                    ->index();

                $table
                    ->timestamp('reserved_at')
                    ->index();

                $table
                    ->timestamp('sent_at')
                    ->nullable();

                $table
                    ->timestamp('failed_at')
                    ->nullable();

                $table
                    ->text('error_message')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'status',
                        'reserved_at',
                    ],
                    'email_quota_status_reserved_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'email_quota_attempts'
        );

        Schema::dropIfExists(
            'email_quota_states'
        );
    }
};
