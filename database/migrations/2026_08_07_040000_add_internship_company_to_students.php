<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('internship_company_id')->nullable()->after('course_id')->constrained('companies')->nullOnDelete();
        });
        DB::table('students')->orderBy('id')->eachById(function ($student): void {
            $companyId = DB::table('student_documents')->where('student_id', $student->id)->whereNotNull('company_id')->latest('updated_at')->value('company_id');
            if ($companyId) DB::table('students')->where('id', $student->id)->update(['internship_company_id' => $companyId]);
        });
    }
    public function down(): void
    {
        Schema::table('students', fn (Blueprint $table) => $table->dropConstrainedForeignId('internship_company_id'));
    }
};
