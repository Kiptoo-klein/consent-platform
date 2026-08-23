<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Email ownership was not previously enforced.
         *
         * Existing accounts are grandfathered so enabling verification
         * does not unexpectedly lock legitimate users out.
         *
         * Users created after this migration remain unverified until
         * they prove ownership of their email address.
         */
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update([
                'email_verified_at' => now(),
            ]);
    }

    public function down(): void
    {
        /*
         * Intentionally irreversible.
         *
         * Rolling back application code must never erase a user's
         * previously established verification state.
         */
    }
};
