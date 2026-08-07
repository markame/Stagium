<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinator_can_create_an_exclusive_student_account(): void
    {
        $coordinator = User::factory()->create();
        $course = $coordinator->courses()->create(['name' => 'Informática', 'area' => Course::AREAS[1], 'state' => 'MA', 'city' => 'São Luís']);
        $this->actingAs($coordinator)->post('/students', [
            'name' => 'Aluno Portal', 'cpf' => '12345678901', 'rg' => '12345', 'birth_date' => '2005-04-12',
            'phone' => '98999998888', 'address' => 'Rua A, 10', 'parentage' => 'Responsável', 'course_id' => $course->id,
        ])->assertRedirect('/students');

        $this->assertDatabaseHas('users', ['username' => '12345678901', 'role' => 'student']);
    }

    public function test_only_student_can_mark_attendance_inside_company_radius(): void
    {
        [$coordinator, $student, $account, $company] = $this->portalData();
        $this->actingAs($coordinator)->get('/aluno')->assertForbidden();
        $this->actingAs($account)->get('/')->assertForbidden();
        $this->actingAs($account)->get('/aluno')->assertOk()->assertSee('Aluno Portal');

        $this->actingAs($account)->postJson('/aluno/ponto', ['latitude' => -2.53001, 'longitude' => -44.30001])
            ->assertOk()->assertJsonPath('type', 'in');
        $this->actingAs($account)->postJson('/aluno/ponto', ['latitude' => -2.53001, 'longitude' => -44.30001])
            ->assertOk()->assertJsonPath('type', 'out');
        $this->assertDatabaseCount('student_time_logs', 2);
    }

    public function test_student_logs_in_using_cpf_as_username_and_password(): void
    {
        [, $student] = $this->portalData();
        $this->post('/login', ['identifier' => $student->cpf, 'password' => $student->cpf])
            ->assertRedirect('/aluno');
        $this->assertAuthenticatedAs($student->userAccount);
    }

    public function test_student_cannot_mark_attendance_outside_company_radius(): void
    {
        [, , $account] = $this->portalData();
        $this->actingAs($account)->postJson('/aluno/ponto', ['latitude' => -2.60000, 'longitude' => -44.40000])
            ->assertStatus(422)->assertJsonPath('message', 'Você está fora da área permitida.');
        $this->assertDatabaseCount('student_time_logs', 0);
    }

    private function portalData(): array
    {
        $coordinator = User::factory()->create();
        $course = $coordinator->courses()->create(['name' => 'Informática', 'area' => Course::AREAS[1], 'state' => 'MA', 'city' => 'São Luís']);
        $student = $coordinator->students()->create(['course_id' => $course->id, 'name' => 'Aluno Portal', 'cpf' => '12345678901']);
        $account = $student->userAccount;
        $company = $coordinator->companies()->create(['cnpj' => '12345678000190', 'corporate_name' => 'Empresa LTDA', 'trade_name' => 'Empresa', 'phone' => '98999998888', 'address' => 'Rua B', 'responsible_name' => 'Responsável', 'responsible_cpf' => '98765432100', 'responsible_rg' => '123', 'responsible_address' => 'Rua C', 'responsible_phone' => '98999998888', 'latitude' => -2.53, 'longitude' => -44.30, 'attendance_radius_meters' => 100]);
        $student->documents()->create(['company_id' => $company->id, 'type' => StudentDocument::COMMITMENT_TERM, 'original_name' => 'termo.pdf', 'path' => 'fake/termo.pdf']);
        return [$coordinator, $student, $account, $company];
    }
}
