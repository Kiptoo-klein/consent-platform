<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('organizations', function (Blueprint $table) {
        $table->id();

        $table->string('name');
        $table->string('slug')->unique();

        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->string('website')->nullable();

        $table->string('logo')->nullable();

        $table->string('primary_color')->default('#2563eb');
        $table->string('secondary_color')->default('#1e293b');

        $table->timestamps();
    });
}
};
