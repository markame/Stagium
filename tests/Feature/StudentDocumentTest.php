<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentForwardingTermGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use setasign\Fpdi\Fpdi;

class StudentDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_documents_page_shows_the_official_term_form(): void
    {
        $user = User::factory()->create();
        $this->studentFor($user);
        $company = $this->companyFor($user);

        $this->actingAs($user)
            ->get('/company-forwarding-terms?company_id='.$company->id)
            ->assertOk()
            ->assertSee('Termo de Encaminhamento')
            ->assertSee('Gerar termo em PDF');
    }

    public function test_a_user_can_generate_and_save_a_student_forwarding_term(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $student = $this->studentFor($user);
        $company = $this->companyFor($user);
        $testDirectory = storage_path('app/private/tmp/student-terms/test-'.bin2hex(random_bytes(4)));
        mkdir($testDirectory, 0777, true);
        $generated = $testDirectory.'/term.pdf';
        file_put_contents($generated, '%PDF-1.4 generated test PDF');

        $generator = Mockery::mock(StudentForwardingTermGenerator::class);
        $generator->shouldReceive('generate')->once()->andReturn($generated);
        $this->app->instance(StudentForwardingTermGenerator::class, $generator);

        $this->actingAs($user)->post('/companies/forwarding-terms', [
            'student_ids' => [$student->id],
            'company_id' => $company->id,
            'start_date' => '2026-08-10',
            'manager_name' => 'Joana Gestora',
            'iema_unit' => 'São Luís Centro',
            'responsible_role' => 'Diretora Administrativa',
        ])->assertRedirect('/company-forwarding-terms?company_id='.$company->id);

        $document = $student->documents()->firstOrFail();
        $this->assertSame($company->id, $document->company_id);
        $this->assertSame('Joana Gestora', $document->generation_data['manager_name']);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_forwarding_term_generator_creates_a_pdf_without_word_automation(): void
    {
        $user = User::factory()->create();
        $student = $this->studentFor($user)->load('course');
        $secondStudent = $student->replicate();
        $secondStudent->name = 'Bruno Santos';
        $secondStudent->cpf = '98765432100';
        $secondStudent->save();
        $secondStudent->setRelation('course', $student->course);
        $thirdStudent = $student->replicate();
        $thirdStudent->name = 'Carla Mendes';
        $thirdStudent->cpf = '11122233344';
        $thirdStudent->save();
        $thirdStudent->setRelation('course', $student->course);
        $fourthStudent = $student->replicate();
        $fourthStudent->name = 'Diego Costa';
        $fourthStudent->cpf = '55566677788';
        $fourthStudent->save();
        $fourthStudent->setRelation('course', $student->course);
        $company = $this->companyFor($user);

        $path = app(StudentForwardingTermGenerator::class)->generate(collect([$student, $secondStudent, $thirdStudent, $fourthStudent]), $company, [
            'start_date' => '2026-08-10',
            'manager_name' => 'Joana Gestora',
            'iema_unit' => 'São Luís Centro',
            'responsible_role' => 'Diretora Administrativa',
        ]);

        $this->assertFileExists($path);
        $this->assertSame(2, (new Fpdi())->setSourceFile($path));
        $this->assertStringStartsWith('%PDF-', file_get_contents($path));
        unlink($path);
        rmdir(dirname($path));
    }

    public function test_a_user_cannot_generate_a_term_for_another_users_student_or_company(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->post('/companies/forwarding-terms', [
            'student_ids' => [$this->studentFor($other)->id],
            'company_id' => $this->companyFor($other)->id,
            'start_date' => '2026-08-10',
            'manager_name' => 'Joana Gestora',
            'iema_unit' => 'São Luís Centro',
            'responsible_role' => 'Diretora',
        ])->assertSessionHasErrors(['student_ids.0', 'company_id']);

        $this->assertDatabaseCount('student_documents', 0);
    }

    private function studentFor(User $user): Student
    {
        $course = $user->courses()->create([
            'name' => 'Técnico em Informática',
            'area' => Course::AREAS[1],
            'state' => 'MA',
            'city' => 'São Luís',
        ]);

        return $user->students()->create([
            'course_id' => $course->id,
            'name' => 'Ana da Silva',
            'address' => 'Rua das Flores, 10',
            'phone' => '98999998888',
            'cpf' => '12345678901',
            'parentage' => 'Maria da Silva',
            'birth_date' => '2005-04-12',
        ]);
    }

    private function companyFor(User $user): Company
    {
        return $user->companies()->create([
            'cnpj' => '12345678000190',
            'corporate_name' => 'Empresa Exemplo LTDA',
            'trade_name' => 'Empresa Exemplo',
            'phone' => '9833334444',
            'address' => 'Rua Comercial, 100',
            'responsible_name' => 'Maria Responsável',
            'responsible_cpf' => '12345678901',
            'responsible_rg' => '1234567 SSP-MA',
            'responsible_address' => 'Rua das Flores, 20',
            'responsible_phone' => '98999998888',
        ]);
    }
}
