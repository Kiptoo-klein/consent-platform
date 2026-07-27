<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addSenderName = ! Schema::hasColumn(
            'signing_stations',
            'sender_name'
        );

        $addReplyToEmail = ! Schema::hasColumn(
            'signing_stations',
            'reply_to_email'
        );

        $addEmailDescription = ! Schema::hasColumn(
            'signing_stations',
            'email_description'
        );

        if (! $addSenderName && ! $addReplyToEmail && ! $addEmailDescription) {
            return;
        }

        Schema::table('signing_stations', function (Blueprint $table) use (
            $addSenderName,
            $addReplyToEmail,
            $addEmailDescription
        ): void {
            if ($addSenderName) {
                $table->string('sender_name', 120)->nullable();
            }

            if ($addReplyToEmail) {
                $table->string('reply_to_email')->nullable();
            }

            if ($addEmailDescription) {
                $table->text('email_description')->nullable();
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('signing_stations', 'sender_name')
                ? 'sender_name'
                : null,
            Schema::hasColumn('signing_stations', 'reply_to_email')
                ? 'reply_to_email'
                : null,
            Schema::hasColumn('signing_stations', 'email_description')
                ? 'email_description'
                : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table(
            'signing_stations',
            function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            }
        );
    }
};
