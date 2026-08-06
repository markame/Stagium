<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coordinator_id')->constrained('users')->cascadeOnDelete();
            $table->string('cnpj', 14)->unique();
            $table->string('corporate_name');
            $table->string('trade_name');
            $table->string('phone', 20);
            $table->string('address');
            $table->string('responsible_name');
            $table->string('responsible_cpf', 11);
            $table->string('responsible_address');
            $table->string('responsible_phone', 20);
            $table->timestamps();

            $table->index(['coordinator_id', 'corporate_name']);
            $table->index(['coordinator_id', 'responsible_cpf']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
