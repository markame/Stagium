<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_register_a_student_in_their_course(): void
    {
        $user = User::factory()->create();
        $course = $this->courseFor($user);

        $this->actingAs($user)->post('/students', [
            'name' => 'Ana da Silva',
            'address' => 'Rua das Flores, 123',
            'phone' => '(11) 99999-8888',
            'cpf' => '123.456.789-01',
            'parentage' => 'Maria da Silva e João da Silva',
            'birth_date' => '2005-04-12',
            'course_id' => $course->id,
        ])->assertRedirect('/students');

        $this->assertDatabaseHas('students', [
            'coordinator_id' => $user->id,
            'course_id' => $course->id,
            'name' => 'Ana da Silva',
            'cpf' => '12345678901',
            'phone' => '11999998888',
        ]);
    }

    public function test_a_user_cannot_assign_a_student_to_another_users_course(): void
    {
        $user = User::factory()->create();
        $otherCourse = $this->courseFor(User::factory()->create());

        $this->actingAs($user)->post('/students', [
            'name' => 'Ana da Silva',
            'address' => 'Rua das Flores, 123',
            'phone' => '11999998888',
            'cpf' => '12345678901',
            'parentage' => 'Maria da Silva',
            'birth_date' => '2005-04-12',
            'course_id' => $otherCourse->id,
        ])->assertSessionHasErrors('course_id');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_a_user_cannot_edit_another_users_student(): void
    {
        $owner = User::factory()->create();
        $student = Student::create([
            'coordinator_id' => $owner->id,
            'course_id' => $this->courseFor($owner)->id,
            'name' => 'Ana da Silva',
            'address' => 'Rua das Flores, 123',
            'phone' => '11999998888',
            'cpf' => '12345678901',
            'parentage' => 'Maria da Silva',
            'birth_date' => '2005-04-12',
        ]);

        $this->actingAs(User::factory()->create())
            ->get("/students/{$student->id}/edit")
            ->assertNotFound();
    }

    public function test_coordinator_can_change_the_students_internship_company(): void
    {
        $user = User::factory()->create();
        $student = $user->students()->create(['course_id'=>$this->courseFor($user)->id,'name'=>'Ana','cpf'=>'12345678901']);
        $company = $user->companies()->create(['cnpj'=>'12345678000190','corporate_name'=>'Nova Empresa LTDA','trade_name'=>'Nova Empresa','phone'=>'98999999999','address'=>'Rua A','responsible_name'=>'Maria','responsible_cpf'=>'98765432100','responsible_rg'=>'123','responsible_address'=>'Rua B','responsible_phone'=>'98988888888']);

        $this->actingAs($user)->put("/students/{$student->id}/internship-company", ['company_id'=>$company->id])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('students', ['id'=>$student->id,'internship_company_id'=>$company->id]);
    }

    private function courseFor(User $user): Course
    {
        return $user->courses()->create([
            'name' => 'Técnico em Informática',
            'area' => Course::AREAS[1],
            'state' => 'SP',
            'city' => 'Campinas',
        ]);
    }
}
