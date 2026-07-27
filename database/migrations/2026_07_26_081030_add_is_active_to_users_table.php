<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an account status column to the users table.
     *
     * Existing users remain active by default.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table
                ->boolean('is_active')
                ->default(true)
                ->after('password')
                ->index();
        });
    }

    /**
     * Remove the account status column.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
