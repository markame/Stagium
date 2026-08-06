<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->change();
            $table->string('address')->nullable()->change();
            $table->string('phone', 20)->nullable()->change();
            $table->string('parentage')->nullable()->change();
            $table->date('birth_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable(false)->change();
            $table->string('address')->nullable(false)->change();
            $table->string('phone', 20)->nullable(false)->change();
            $table->string('parentage')->nullable(false)->change();
            $table->date('birth_date')->nullable(false)->change();
        });
    }
};
