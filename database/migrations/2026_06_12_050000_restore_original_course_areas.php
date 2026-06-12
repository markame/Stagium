<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const RENAMES = [
        'Saúde, Estética, Cuidado Humano e Medicamentos' => 'Saúde',
        'Informática, Tecnologia da Informação e Comunicação' => 'Informação e Comunicação',
        'Engenharias, Construção Civil, Arquitetura e Infraestrutura' => 'Infraestrutura',
        'Agronegócio, Agroindústria, Agropecuária, Produção e Recursos Naturais' => 'Produção e Recursos Naturais',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::RENAMES as $oldArea => $newArea) {
            DB::table('courses')
                ->where('area', $oldArea)
                ->update(['area' => $newArea]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::RENAMES as $oldArea => $newArea) {
            DB::table('courses')
                ->where('area', $newArea)
                ->update(['area' => $oldArea]);
        }
    }
};
