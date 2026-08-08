<?php

namespace Tests\Feature;

use App\Models\StudentDocument;
use App\Models\StudentTimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinator_can_monitor_students_companies_links_and_time_logs(): void
    {
        $user = User::factory()->create();
        $course = $user->courses()->create(['name'=>'Informática','area'=>'Tecnologia','state'=>'MA','city'=>'São Luís']);
        $student = $user->students()->create(['course_id'=>$course->id,'name'=>'Ana Monitorada','cpf'=>'12345678901']);
        $company = $user->companies()->create(['cnpj'=>'12345678000190','corporate_name'=>'Empresa Monitorada LTDA','trade_name'=>'Monitorada','phone'=>'98999999999','address'=>'Rua A','responsible_name'=>'Maria','responsible_cpf'=>'98765432100','responsible_rg'=>'123','responsible_address'=>'Rua B','responsible_phone'=>'98988888888']);
        $student->documents()->create(['company_id'=>$company->id,'type'=>StudentDocument::FORWARDING_TERM,'original_name'=>'termo.pdf','path'=>'termo.pdf']);
        StudentTimeLog::create(['student_id'=>$student->id,'company_id'=>$company->id,'type'=>'in','logged_at'=>now(),'device_latitude'=>-2.5,'device_longitude'=>-44.3,'distance_meters'=>10]);

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSee('Ana Monitorada')->assertSee('Empresa Monitorada LTDA')
            ->assertSee('Alunos vinculados')->assertSee('Registros de ponto hoje');
    }
}
