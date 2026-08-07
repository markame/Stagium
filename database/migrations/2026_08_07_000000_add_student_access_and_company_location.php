<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('coordinator')->after('password');
            $table->foreignId('student_id')->nullable()->unique()->after('role')->constrained()->cascadeOnDelete();
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('attendance_radius_meters')->default(100);
        });
    }

    public function down(): void
    {
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn(['latitude', 'longitude', 'attendance_radius_meters']));
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_id');
            $table->dropColumn('role');
        });
    }
};
