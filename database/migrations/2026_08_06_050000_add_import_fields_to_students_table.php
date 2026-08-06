<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('rg', 30)->nullable()->after('cpf');
            $table->string('sms_phone', 20)->nullable()->after('phone');
            $table->string('phone_2', 20)->nullable()->after('sms_phone');
            $table->string('phone_3', 20)->nullable()->after('phone_2');
            $table->string('other_phones')->nullable()->after('phone_3');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['rg', 'sms_phone', 'phone_2', 'phone_3', 'other_phones']);
        });
    }
};
