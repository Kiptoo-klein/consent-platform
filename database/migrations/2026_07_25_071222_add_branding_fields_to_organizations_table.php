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
        Schema::table('organizations', function (Blueprint $table) {
            if (! Schema::hasColumn('organizations', 'accent_color')) {
                $table->string('accent_color', 20)
                    ->default('#10B981');
            }

            if (! Schema::hasColumn('organizations', 'support_email')) {
                $table->string('support_email')
                    ->nullable();
            }

            if (! Schema::hasColumn('organizations', 'address')) {
                $table->text('address')
                    ->nullable();
            }

            if (! Schema::hasColumn('organizations', 'footer_text')) {
                $table->text('footer_text')
                    ->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_filter([
            Schema::hasColumn('organizations', 'accent_color')
                ? 'accent_color'
                : null,

            Schema::hasColumn('organizations', 'support_email')
                ? 'support_email'
                : null,

            Schema::hasColumn('organizations', 'address')
                ? 'address'
                : null,

            Schema::hasColumn('organizations', 'footer_text')
                ? 'footer_text'
                : null,
        ]);

        if ($columns !== []) {
            Schema::table('organizations', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
