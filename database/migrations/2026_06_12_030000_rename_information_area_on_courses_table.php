<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_AREA = 'Informação e Comunicação';

    private const NEW_AREA = 'Informática, Tecnologia da Informação e Comunicação';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('courses')
            ->where('area', self::OLD_AREA)
            ->update(['area' => self::NEW_AREA]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('courses')
            ->where('area', self::NEW_AREA)
            ->update(['area' => self::OLD_AREA]);
    }
};
