<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('email')->nullable()->change();
        });
        DB::table('students')->whereNotNull('cpf')->orderBy('id')->get()->each(function ($student): void {
            if (DB::table('users')->where('student_id', $student->id)->exists()) return;
            $cpf = preg_replace('/\D/', '', $student->cpf);
            if (strlen($cpf) !== 11 || DB::table('users')->where('username', $cpf)->exists()) return;
            DB::table('users')->insert(['name' => $student->name, 'username' => $cpf, 'email' => null, 'password' => Hash::make($cpf), 'role' => 'student', 'student_id' => $student->id, 'created_at' => now(), 'updated_at' => now()]);
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
            $table->string('email')->nullable(false)->change();
        });
    }
};
