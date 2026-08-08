<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CompanyOfficialDocumentsGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class CompanyOfficialDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_three_official_company_documents_keep_their_page_counts(): void
    {
        $user = User::factory()->create();
        $company = $user->companies()->create(['cnpj'=>'12345678000190','corporate_name'=>'Empresa Exemplo LTDA','trade_name'=>'Empresa Exemplo','phone'=>'98999998888','address'=>'Rua Comercial, 100, Centro, São Luís - MA','responsible_name'=>'Maria Responsável','responsible_cpf'=>'98765432100','responsible_rg'=>'1234567','responsible_address'=>'Rua das Flores, 20, São Luís - MA','responsible_phone'=>'98999998888']);
        $data = ['agreement_number'=>'015','ci_number'=>'025','iema_unit'=>'IEMA Pleno São Luís Centro','iema_code'=>'IP-SÃO LUÍS','manager_name'=>'Joana Gestora','vacancies'=>3,'document_date'=>'2026-08-07','issuing_authority'=>'SSP/MA','business_area'=>'Tecnologia da Informação','company_city'=>'São Luís','company_state'=>'MA','company_zip'=>'65000-000','company_email'=>'empresa@example.com','shipping_address'=>'Rua Comercial, 100, Centro','shipping_city'=>'São Luís','shipping_state'=>'MA','shipping_zip'=>'65000-000','delivery_responsible'=>'Maria Responsável','delivery_phone'=>'98999998888','delivery_email'=>'maria@example.com'];
        $files = app(CompanyOfficialDocumentsGenerator::class)->generate($company,$data);
        $this->assertSame(6,(new Fpdi())->setSourceFile($files['minuta_termo']));
        $this->assertSame(1,(new Fpdi())->setSourceFile($files['formulario_celebracao']));
        $this->assertSame(1,(new Fpdi())->setSourceFile($files['solicitacao_convenio']));
        $directory=dirname(reset($files)); foreach($files as $path) unlink($path); rmdir($directory);
    }

    public function test_official_documents_accept_long_company_and_address_data(): void
    {
        $user = User::factory()->create();
        $company = $user->companies()->create(['cnpj'=>'12345678000190','corporate_name'=>'Empresa Maranhense de Desenvolvimento Tecnológico e Serviços Especializados LTDA','trade_name'=>'Empresa','phone'=>'98999998888','address'=>'Avenida Principal de Acesso ao Distrito Industrial, 1250, Parque Tecnológico, Bloco Administrativo, CEP 65000-000','responsible_name'=>'Maria Aparecida dos Santos Nascimento de Oliveira','responsible_cpf'=>'98765432100','responsible_rg'=>'1234567','responsible_address'=>'Rua das Flores, 20','responsible_phone'=>'98999998888']);
        $data = ['agreement_number'=>'015','ci_number'=>'025','iema_unit'=>'IEMA Pleno São Luís Centro','iema_code'=>'IP-SÃO LUÍS','manager_name'=>'Joana Gestora','vacancies'=>3,'document_date'=>'2026-08-07','issuing_authority'=>'SSP/MA','business_area'=>'Tecnologia da Informação e Serviços Especializados','company_city'=>'São Luís','company_state'=>'MA','company_zip'=>'65000-000','company_email'=>'departamento.convenios@example.com','shipping_address'=>$company->address,'shipping_city'=>'São Luís','shipping_state'=>'MA','shipping_zip'=>'65000-000','delivery_responsible'=>$company->responsible_name,'delivery_phone'=>'98999998888','delivery_email'=>'responsavel.documentos@example.com'];

        $files = app(CompanyOfficialDocumentsGenerator::class)->generate($company,$data);
        foreach ($files as $path) $this->assertFileExists($path);
        $directory=dirname(reset($files)); foreach($files as $path) unlink($path); rmdir($directory);
    }

    public function test_official_form_saves_data_without_storing_generated_pdfs(): void
    {
        $user = User::factory()->create();
        $company = $user->companies()->create(['cnpj'=>'12345678000190','corporate_name'=>'Empresa Exemplo LTDA','trade_name'=>'Empresa Exemplo','phone'=>'98999998888','address'=>'Rua Comercial, 100','responsible_name'=>'Maria Responsável','responsible_cpf'=>'98765432100','responsible_rg'=>'1234567','responsible_address'=>'Rua das Flores, 20','responsible_phone'=>'98999998888']);

        $this->actingAs($user)->post(route('companies.documents.generate-official', $company), $this->officialData())
            ->assertRedirect(route('companies.documents.index', ['company_id' => $company->id]));

        $this->assertDatabaseHas('company_official_document_data', ['company_id' => $company->id]);
        $this->assertDatabaseCount('company_documents', 0);
    }

    public function test_official_pdf_is_generated_only_when_downloaded(): void
    {
        $user = User::factory()->create();
        $company = $user->companies()->create(['cnpj'=>'12345678000190','corporate_name'=>'Empresa Exemplo LTDA','trade_name'=>'Empresa Exemplo','phone'=>'98999998888','address'=>'Rua Comercial, 100','responsible_name'=>'Maria Responsável','responsible_cpf'=>'98765432100','responsible_rg'=>'1234567','responsible_address'=>'Rua das Flores, 20','responsible_phone'=>'98999998888']);
        $company->officialDocumentData()->create(['data' => $this->officialData()]);

        $this->actingAs($user)->get(route('companies.documents.download-official', [$company, 'solicitacao_convenio']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseCount('company_documents', 0);
    }

    private function officialData(): array
    {
        return ['agreement_number'=>'015','ci_number'=>'025','iema_unit'=>'IEMA Pleno São Luís Centro','iema_code'=>'IP-SÃO LUÍS','manager_name'=>'Joana Gestora','vacancies'=>3,'document_date'=>'2026-08-07','issuing_authority'=>'SSP/MA','business_area'=>'Tecnologia da Informação','company_city'=>'São Luís','company_state'=>'MA','company_zip'=>'65000-000','company_email'=>'empresa@example.com','shipping_address'=>'Rua Comercial, 100, Centro','shipping_city'=>'São Luís','shipping_state'=>'MA','shipping_zip'=>'65000-000','delivery_responsible'=>'Maria Responsável','delivery_phone'=>'98999998888','delivery_email'=>'maria@example.com'];
    }
}
