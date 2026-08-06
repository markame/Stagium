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
        Schema::create('course_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('google_place_id')->nullable();
            $table->string('source_hash', 40);
            $table->string('name');
            $table->string('type')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('international_phone')->nullable();
            $table->string('website_url')->nullable();
            $table->string('maps_url')->nullable();
            $table->string('source')->default('Google Places');
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'source_hash']);
            $table->index(['course_id', 'google_place_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_companies');
    }
};
