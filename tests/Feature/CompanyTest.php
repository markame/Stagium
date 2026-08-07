<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_register_a_company(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/companies', $this->validData())
            ->assertRedirect('/companies');

        $this->assertDatabaseHas('companies', [
            'coordinator_id' => $user->id,
            'cnpj' => '12345678000190',
            'corporate_name' => 'Empresa Exemplo LTDA',
            'trade_name' => 'Empresa Exemplo',
            'phone' => '1133334444',
            'responsible_name' => 'Maria Responsável',
            'responsible_cpf' => '12345678901',
            'responsible_rg' => '1234567 SSP-MA',
            'responsible_phone' => '11999998888',
        ]);
    }

    public function test_cnpj_must_be_unique(): void
    {
        $owner = User::factory()->create();
        $owner->companies()->create($this->normalizedData());

        $this->actingAs(User::factory()->create())
            ->post('/companies', $this->validData())
            ->assertSessionHasErrors('cnpj');

        $this->assertDatabaseCount('companies', 1);
    }

    public function test_company_address_can_be_registered_in_separate_fields(): void
    {
        $user = User::factory()->create();
        $data = $this->validData();
        unset($data['address']);
        $data += [
            'address_street' => 'Avenida Principal',
            'address_number' => '250',
            'address_neighborhood' => 'Centro',
            'address_zip' => '65000-000',
            'address_complement' => 'Sala 4',
        ];

        $this->actingAs($user)->post('/companies', $data)->assertRedirect('/companies');

        $this->assertDatabaseHas('companies', [
            'coordinator_id' => $user->id,
            'address_street' => 'Avenida Principal',
            'address_number' => '250',
            'address_neighborhood' => 'Centro',
            'address_zip' => '65000000',
            'address_complement' => 'Sala 4',
            'address' => 'Avenida Principal, 250, Centro, Sala 4, CEP 65000-000',
        ]);
    }

    public function test_a_user_cannot_edit_another_users_company(): void
    {
        $owner = User::factory()->create();
        $company = $owner->companies()->create($this->normalizedData());

        $this->actingAs(User::factory()->create())
            ->get("/companies/{$company->id}/edit")
            ->assertNotFound();
    }

    /** @return array<string, string> */
    private function validData(): array
    {
        return [
            'cnpj' => '12.345.678/0001-90',
            'corporate_name' => 'Empresa Exemplo LTDA',
            'trade_name' => 'Empresa Exemplo',
            'phone' => '(11) 3333-4444',
            'address' => 'Rua Comercial, 100, São Paulo - SP',
            'responsible_name' => 'Maria Responsável',
            'responsible_cpf' => '123.456.789-01',
            'responsible_rg' => '1234567 SSP-MA',
            'responsible_address' => 'Rua das Flores, 20, São Paulo - SP',
            'responsible_phone' => '(11) 99999-8888',
        ];
    }

    /** @return array<string, string> */
    private function normalizedData(): array
    {
        return array_merge($this->validData(), [
            'cnpj' => '12345678000190',
            'phone' => '1133334444',
            'responsible_cpf' => '12345678901',
            'responsible_phone' => '11999998888',
        ]);
    }
}
