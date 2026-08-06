<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use setasign\Fpdi\Fpdi;

class CompanyDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_page_lists_all_required_documents(): void
    {
        $user = User::factory()->create();
        $company = $this->companyFor($user);

        $this->actingAs($user)
            ->get("/company-documents?company_id={$company->id}")
            ->assertOk()
            ->assertSee('Solicitação de Convênio - CI')
            ->assertSee('Certificado de Regularidade do FGTS')
            ->assertSee('Certidão de Antecedentes Criminais Federal');
    }

    public function test_companies_can_be_searched_by_name_or_cnpj_on_the_documents_page(): void
    {
        $user = User::factory()->create();
        $company = $this->companyFor($user);
        $other = $company->replicate();
        $other->fill([
            'cnpj' => '98765432000110',
            'corporate_name' => 'Clínica Saúde LTDA',
            'trade_name' => 'Clínica Saúde',
        ]);
        $user->companies()->save($other);

        $this->actingAs($user)
            ->get('/company-documents?q=98765432')
            ->assertOk()
            ->assertSee('Clínica Saúde')
            ->assertDontSee('Empresa Exemplo LTDA');

        $this->actingAs($user)
            ->get('/company-documents?q=Empresa Exemplo')
            ->assertOk()
            ->assertSee('Empresa Exemplo LTDA')
            ->assertDontSee('Clínica Saúde LTDA');
    }

    public function test_a_user_can_attach_a_required_pdf_to_their_company(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $company = $this->companyFor($user);

        $this->actingAs($user)->post("/companies/{$company->id}/documents/solicitacao_convenio", [
            'document' => $this->fakePdf('solicitacao.pdf', 'Solicitacao'),
        ])->assertRedirect("/company-documents?company_id={$company->id}");

        $document = CompanyDocument::firstOrFail();
        $this->assertSame('solicitacao_convenio', $document->type);
        $this->assertSame('solicitacao.pdf', $document->original_name);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_company_documents_only_accept_pdf_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $company = $this->companyFor($user);

        $this->actingAs($user)->post("/companies/{$company->id}/documents/minuta_termo", [
            'document' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertSessionHasErrors('document');

        $this->assertDatabaseCount('company_documents', 0);
    }

    public function test_a_user_cannot_download_another_users_company_document(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $company = $this->companyFor($owner);
        $path = UploadedFile::fake()->create('certidao.pdf', 50, 'application/pdf')
            ->store("company-documents/{$company->id}", 'local');
        $document = $company->documents()->create([
            'type' => 'cnd_federal',
            'original_name' => 'certidao.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 51200,
        ]);

        $this->actingAs(User::factory()->create())
            ->get("/company-documents/{$document->id}/download")
            ->assertNotFound();
    }

    public function test_all_documents_can_be_downloaded_as_one_pdf_without_the_minute(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $company = $this->companyFor($user);

        foreach (['solicitacao_convenio', 'minuta_termo', 'comprovante_cnpj'] as $type) {
            $file = $this->fakePdf("{$type}.pdf", $type);
            $path = $file->store("company-documents/{$company->id}", 'local');
            $company->documents()->create([
                'type' => $type,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => 'application/pdf',
                'size' => $file->getSize(),
            ]);
        }

        $response = $this->actingAs($user)
            ->get("/company-documents/{$company->id}/download-all")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $mergedPath = tempnam(sys_get_temp_dir(), 'merged-pdf-');
        file_put_contents($mergedPath, $response->streamedContent());
        $this->assertSame(2, (new Fpdi())->setSourceFile($mergedPath));
        unlink($mergedPath);
    }

    private function companyFor(User $user): Company
    {
        return $user->companies()->create([
            'cnpj' => '12345678000190',
            'corporate_name' => 'Empresa Exemplo LTDA',
            'trade_name' => 'Empresa Exemplo',
            'phone' => '1133334444',
            'address' => 'Rua Comercial, 100',
            'responsible_name' => 'Maria Responsável',
            'responsible_cpf' => '12345678901',
            'responsible_rg' => '1234567 SSP-MA',
            'responsible_address' => 'Rua das Flores, 20',
            'responsible_phone' => '11999998888',
        ]);
    }

    private function fakePdf(string $name, string $text): UploadedFile
    {
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, $text);

        return UploadedFile::fake()->createWithContent($name, $pdf->Output('S'));
    }
}
