<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('address_street')->nullable()->after('address');
            $table->string('address_number', 30)->nullable()->after('address_street');
            $table->string('address_neighborhood')->nullable()->after('address_number');
            $table->string('address_zip', 9)->nullable()->after('address_neighborhood');
            $table->string('address_complement')->nullable()->after('address_zip');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['address_street', 'address_number', 'address_neighborhood', 'address_zip', 'address_complement']);
        });
    }
};
