<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use PhpZip\ZipFile;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_import_students_from_the_tab_separated_csv_format(): void
    {
        $user = User::factory()->create();
        $this->courseFor($user, 'Técnico em Informática');
        $csv = $this->header()."\n".
            "Ana da Silva\t123.456.789-01\t1234567 SSP-MA\t12/04/2005\t(98) 99999-8888\t(98) 3333-4444\t\t\t\tRua das Flores, 10\tTécnico em Informática\tMaria da Silva";

        $this->actingAs($user)->post('/students-import', [
            'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
        ])->assertRedirect('/students');

        $this->assertDatabaseHas('students', [
            'coordinator_id' => $user->id,
            'name' => 'Ana da Silva',
            'cpf' => '12345678901',
            'rg' => '1234567 SSP-MA',
            'sms_phone' => '98999998888',
            'phone' => '9833334444',
            'parentage' => 'Maria da Silva',
        ]);
    }

    public function test_import_cannot_use_another_users_course(): void
    {
        $user = User::factory()->create();
        $this->courseFor(User::factory()->create(), 'Técnico em Enfermagem');
        $csv = $this->header()."\n".
            "Ana da Silva\t12345678901\t1234567\t2005-04-12\t98999998888\t9833334444\t\t\t\tRua A\tTécnico em Enfermagem\tMaria da Silva";

        $this->actingAs($user)->from('/students-import')->post('/students-import', [
            'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
        ])->assertRedirect('/students-import')->assertSessionHasErrors('csv_file');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_a_user_can_import_students_from_xlsx(): void
    {
        $user = User::factory()->create();
        $this->courseFor($user, 'Técnico em Administração');
        $headers = explode("\t", $this->header());
        $row = [
            'Bruno Santos', '11122233344', '998877 SSP-MA', '45859', '98988887777',
            '9832221111', '', '', '', 'Avenida Central, 50', 'Técnico em Administração', 'Cláudia Santos',
        ];
        $file = $this->fakeXlsx([$headers, $row]);

        $this->actingAs($user)->post('/students-import', [
            'csv_file' => $file,
        ])->assertRedirect('/students');

        $this->assertDatabaseHas('students', [
            'coordinator_id' => $user->id,
            'name' => 'Bruno Santos',
            'cpf' => '11122233344',
            'birth_date' => '2025-07-21 00:00:00',
            'course_id' => $user->courses()->first()->id,
        ]);
    }

    public function test_xlsx_import_splits_a_delimited_data_row_stored_entirely_in_column_a(): void
    {
        $user = User::factory()->create();
        $this->courseFor($user, 'Técnico em Logística');
        $dataRow = implode("\t", [
            'Carla Mendes', '55566677788', '112233 SSP-MA', '15/03/2004', '98977776666',
            '9831112222', '', '', '', 'Rua do Sol, 30', 'Técnico em Logística', 'Paulo Mendes',
        ]);
        $file = $this->fakeXlsx([[...explode("\t", $this->header())], [$dataRow]]);

        $this->actingAs($user)->post('/students-import', [
            'csv_file' => $file,
        ])->assertRedirect('/students');

        $this->assertDatabaseHas('students', [
            'coordinator_id' => $user->id,
            'name' => 'Carla Mendes',
            'cpf' => '55566677788',
            'rg' => '112233 SSP-MA',
        ]);
    }

    public function test_import_only_requires_name_and_cpf(): void
    {
        $user = User::factory()->create();
        $csv = $this->header()."\n".
            "Aluno Mínimo\t22233344455\t\t\t\t\t\t\t\t\t\t";

        $this->actingAs($user)->post('/students-import', [
            'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
        ])->assertRedirect('/students');

        $this->assertDatabaseHas('students', [
            'coordinator_id' => $user->id,
            'name' => 'Aluno Mínimo',
            'cpf' => '22233344455',
            'course_id' => null,
            'birth_date' => null,
            'phone' => null,
        ]);
    }

    public function test_import_ignores_existing_or_repeated_students_by_name_or_cpf(): void
    {
        $user = User::factory()->create();
        $course = $this->courseFor($user, 'Técnico em Redes');
        $user->students()->create($this->studentData($course->id, 'Aluno Existente', '33344455566'));
        $csv = $this->header()."\n".
            "Aluno Existente\t99988877766\t\t\t\t\t\t\t\t\t\t\n".
            "Outro Nome\t33344455566\t\t\t\t\t\t\t\t\t\t\n".
            "Aluno Novo\t44455566677\t\t\t\t\t\t\t\t\t\t\n".
            "Aluno Novo\t55566677799\t\t\t\t\t\t\t\t\t\t";

        $this->actingAs($user)->post('/students-import', [
            'csv_file' => UploadedFile::fake()->createWithContent('alunos.csv', $csv),
        ])->assertRedirect('/students');

        $this->assertDatabaseCount('students', 2);
        $this->assertDatabaseHas('students', ['name' => 'Aluno Novo', 'cpf' => '44455566677']);
        $this->assertDatabaseMissing('students', ['name' => 'Outro Nome']);
    }

    public function test_a_user_only_sees_their_own_students(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->students()->create($this->studentData($this->courseFor($user, 'Curso do Usuário')->id, 'Aluno Visível', '12345678901'));
        $other->students()->create($this->studentData($this->courseFor($other, 'Curso de Outro Usuário')->id, 'Aluno Oculto', '98765432100'));

        $this->actingAs($user)
            ->get('/students')
            ->assertOk()
            ->assertSee('Aluno Visível')
            ->assertDontSee('Aluno Oculto');
    }

    public function test_students_can_be_searched_by_name_cpf_or_course(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $networks = $this->courseFor($user, 'Redes de Computadores');
        $nursing = $this->courseFor($user, 'Enfermagem');

        $user->students()->create($this->studentData($networks->id, 'Alice da Silva', '12345678901'));
        $user->students()->create($this->studentData($nursing->id, 'Bruno Santos', '98765432100'));
        $other->students()->create($this->studentData($this->courseFor($other, 'Curso Sigiloso')->id, 'Aluno Oculto', '11122233344'));

        $this->actingAs($user)->get('/students?q=Alice')
            ->assertOk()->assertSee('Alice da Silva')->assertDontSee('Bruno Santos')->assertDontSee('Aluno Oculto');

        $this->actingAs($user)->get('/students?q=987.654')
            ->assertOk()->assertSee('Bruno Santos')->assertDontSee('Alice da Silva')->assertDontSee('Aluno Oculto');

        $this->actingAs($user)->get('/students?q=Redes')
            ->assertOk()->assertSee('Alice da Silva')->assertDontSee('Bruno Santos')->assertDontSee('Aluno Oculto');
    }

    public function test_old_student_documents_page_redirects_to_company_documents(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/student-documents')
            ->assertRedirect('/company-forwarding-terms');
    }

    private function header(): string
    {
        return 'Nome'."\t".'CPF'."\t".'RG'."\t".'Data de Nascimento'."\t".'Celular Para SMS'."\t".'Telefone 1'."\t".'Telefone 2'."\t".'Telefone 3'."\t".'Outros Telefones'."\t".'Endereço'."\t".'Curso'."\t".'Responsável';
    }

    private function courseFor(User $user, string $name): Course
    {
        return $user->courses()->create([
            'name' => $name,
            'area' => Course::AREAS[1],
            'state' => 'MA',
            'city' => 'São Luís',
        ]);
    }

    /** @return array<string, mixed> */
    private function studentData(int $courseId, string $name, string $cpf): array
    {
        return [
            'course_id' => $courseId,
            'name' => $name,
            'address' => 'Rua Exemplo, 10',
            'phone' => '98999998888',
            'cpf' => $cpf,
            'parentage' => 'Responsável',
            'birth_date' => '2005-04-12',
        ];
    }

    /** @param array<int, array<int, string>> $rows */
    private function fakeXlsx(array $rows): UploadedFile
    {
        $sheetRows = '';

        foreach ($rows as $rowIndex => $row) {
            $cells = '';
            foreach ($row as $columnIndex => $value) {
                $column = chr(65 + $columnIndex);
                $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $cells .= '<c r="'.$column.($rowIndex + 1).'" t="inlineStr"><is><t>'.$escaped.'</t></is></c>';
            }
            $sheetRows .= '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetRows.'</sheetData></worksheet>';
        $contentTypes = '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
        $path = tempnam(sys_get_temp_dir(), 'students-xlsx-').'.xlsx';
        $zip = new ZipFile();
        $zip->addFromString('[Content_Types].xml', $contentTypes)
            ->addFromString('xl/worksheets/sheet1.xml', $sheet)
            ->saveAsFile($path)
            ->close();

        return new UploadedFile($path, 'alunos.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
