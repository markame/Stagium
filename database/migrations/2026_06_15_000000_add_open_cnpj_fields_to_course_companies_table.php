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
        Schema::table('course_companies', function (Blueprint $table) {
            $table->string('cnpj', 14)->nullable()->after('course_id');
            $table->string('corporate_name')->nullable()->after('name');
            $table->string('trade_name')->nullable()->after('corporate_name');
            $table->string('email')->nullable()->after('address');
            $table->string('cnae_code')->nullable()->after('maps_url');
            $table->string('registration_status')->nullable()->after('cnae_code');
            $table->json('raw_data')->nullable()->after('source');

            $table->index(['course_id', 'cnpj']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_companies', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'cnpj']);
            $table->dropColumn([
                'cnpj',
                'corporate_name',
                'trade_name',
                'email',
                'cnae_code',
                'registration_status',
                'raw_data',
            ]);
        });
    }
};
