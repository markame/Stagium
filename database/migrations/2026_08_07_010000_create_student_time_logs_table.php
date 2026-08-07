<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['in', 'out']);
            $table->dateTime('logged_at');
            $table->decimal('device_latitude', 10, 7);
            $table->decimal('device_longitude', 10, 7);
            $table->unsignedInteger('distance_meters');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'logged_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('student_time_logs'); }
};
