<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\StudentDocument;
use App\Models\User;
use App\Services\StudentCommitmentTermGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class StudentCommitmentTermTest extends TestCase
{
    use RefreshDatabase;

    public function test_commitment_term_is_individual_and_uses_the_four_page_template(): void
    {
        $user = User::factory()->create();
        $course = $user->courses()->create(['name' => 'Técnico em Informática', 'area' => Course::AREAS[1], 'state' => 'MA', 'city' => 'São Luís']);
        $student = $user->students()->create(['course_id' => $course->id, 'name' => 'Ana da Silva', 'address' => 'Rua das Flores, 10', 'phone' => '98999998888', 'cpf' => '12345678901', 'parentage' => 'Maria da Silva', 'birth_date' => '2005-04-12']);
        $company = $user->companies()->create(['cnpj' => '12345678000190', 'corporate_name' => 'Empresa Exemplo LTDA', 'trade_name' => 'Empresa Exemplo', 'phone' => '9833334444', 'address' => 'Rua Comercial, 100', 'responsible_name' => 'Maria Responsável', 'responsible_cpf' => '98765432100', 'responsible_rg' => '1234567 SSP-MA', 'responsible_address' => 'Rua das Flores, 20', 'responsible_phone' => '98999998888']);
        $data = ['start_date' => '2026-08-10', 'end_date' => '2026-11-10', 'daily_start_time' => '08:00', 'daily_end_time' => '14:00', 'manager_name' => 'Joana Gestora', 'company_zip' => '65000-000', 'company_neighborhood' => 'Centro', 'company_city' => 'São Luís', 'company_state' => 'MA', 'student_neighborhood' => 'Anil', 'student_city' => 'São Luís', 'student_state' => 'MA'];

        $path = app(StudentCommitmentTermGenerator::class)->generate($student, $company, $data);
        $this->assertSame(4, (new Fpdi())->setSourceFile($path));
        $this->assertStringStartsWith('%PDF-', file_get_contents($path));
        unlink($path);
        rmdir(dirname($path));
    }

    public function test_commitment_terms_page_is_restricted_to_the_authenticated_users_students(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $course = $other->courses()->create(['name' => 'Curso Oculto', 'area' => Course::AREAS[1], 'state' => 'MA', 'city' => 'São Luís']);
        $other->students()->create(['course_id' => $course->id, 'name' => 'Aluno Oculto', 'cpf' => '99988877766']);

        $this->actingAs($user)->get('/student-commitment-terms')->assertOk()->assertDontSee('Aluno Oculto');
        $this->assertDatabaseCount((new StudentDocument())->getTable(), 0);
    }
}
