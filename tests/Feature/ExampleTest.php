<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ReceitaCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_the_home_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('Painel inicial');
    }

    public function test_a_user_can_register_and_access_courses(): void
    {
        $this->post('/register', [
            'name' => 'Maria Coordenadora',
            'email' => 'maria@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Maria Coordenadora',
            'email' => 'maria@example.com',
        ]);
    }

    public function test_an_authenticated_user_can_register_a_course_as_the_coordinator(): void
    {
        Http::fake([
            'servicodados.ibge.gov.br/*' => Http::response([
                ['nome' => 'São Paulo'],
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/courses', [
                'name' => 'Técnico em Enfermagem',
                'area' => Course::AREAS[0],
                'state' => 'SP',
                'city' => 'São Paulo',
            ])
            ->assertRedirect('/');

        $this->assertDatabaseHas('courses', [
            'name' => 'Técnico em Enfermagem',
            'area' => Course::AREAS[0],
            'state' => 'SP',
            'city' => 'São Paulo',
            'coordinator_id' => $user->id,
        ]);
    }

    public function test_registering_a_course_does_not_use_the_receita_database(): void
    {
        config(['services.google.maps_api_key' => 'test-key']);

        Http::fake([
            'servicodados.ibge.gov.br/*' => Http::response([
                ['nome' => 'São Paulo'],
            ]),
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [
                    [
                        'geometry' => [
                            'location' => [
                                'lat' => -23.55,
                                'lng' => -46.63,
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        ReceitaCompany::create([
            'cnpj' => '12345678000190',
            'corporate_name' => 'Clínica Central LTDA',
            'trade_name' => 'Clínica Central',
            'registration_status' => 'Ativa',
            'cnae_code' => '8630503',
            'cnae_description' => 'Atividade médica ambulatorial',
            'state' => 'SP',
            'city' => 'SÃO PAULO',
            'street_type' => 'Rua',
            'street' => 'Exemplo',
            'number' => '100',
            'district' => 'Centro',
            'zip_code' => '01000000',
            'email' => 'contato@clinica.example',
            'phone' => '11 33334444',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/courses', [
                'name' => 'Técnico em Enfermagem',
                'area' => Course::AREAS[0],
                'state' => 'SP',
                'city' => 'São Paulo',
            ])
            ->assertRedirect('/');

        $this->assertDatabaseCount('course_companies', 0);
    }

    public function test_an_authenticated_user_can_update_their_course(): void
    {
        Http::fake([
            'servicodados.ibge.gov.br/*' => Http::response([
                ['nome' => 'Campinas'],
            ]),
        ]);

        $user = User::factory()->create();
        $course = $user->courses()->create([
            'name' => 'Técnico em Enfermagem',
            'area' => Course::AREAS[0],
            'state' => 'SP',
            'city' => 'São Paulo',
        ]);

        $this->actingAs($user)
            ->put("/courses/{$course->id}", [
                'name' => 'Técnico em Informática',
                'area' => Course::AREAS[1],
                'state' => 'SP',
                'city' => 'Campinas',
            ])
            ->assertRedirect('/');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Técnico em Informática',
            'area' => Course::AREAS[1],
            'state' => 'SP',
            'city' => 'Campinas',
            'coordinator_id' => $user->id,
        ]);
    }

    public function test_an_authenticated_user_can_delete_their_course(): void
    {
        $user = User::factory()->create();
        $course = $user->courses()->create([
            'name' => 'Técnico em Enfermagem',
            'area' => Course::AREAS[0],
            'state' => 'SP',
            'city' => 'São Paulo',
        ]);

        $this->actingAs($user)
            ->delete("/courses/{$course->id}")
            ->assertRedirect('/');

        $this->assertDatabaseMissing('courses', [
            'id' => $course->id,
        ]);
    }

    public function test_a_user_cannot_edit_another_users_course(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $course = $owner->courses()->create([
            'name' => 'Técnico em Enfermagem',
            'area' => Course::AREAS[0],
            'state' => 'SP',
            'city' => 'São Paulo',
        ]);

        $this->actingAs($otherUser)
            ->get("/courses/{$course->id}/edit")
            ->assertNotFound();
    }

    public function test_a_user_can_update_profile_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/profile', [
                'name' => 'Novo Nome',
                'email' => 'novo@example.com',
            ])
            ->assertRedirect('/profile');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Novo Nome',
            'email' => 'novo@example.com',
        ]);
    }

    public function test_google_maps_key_is_required_for_google_places(): void
    {
        config(['services.google.maps_api_key' => null]);

        $user = User::factory()->create();
        $course = $user->courses()->create([
            'name' => 'Técnico em Enfermagem',
            'area' => Course::AREAS[0],
            'state' => 'SP',
            'city' => 'São Paulo',
        ]);

        $this->actingAs($user)
            ->postJson('/companies/scan', ['course_id' => $course->id])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Configure GOOGLE_MAPS_API_KEY no arquivo .env para consultar o Google Places.');
    }

    public function test_company_search_uses_only_google_places(): void
    {
        config(['services.google.maps_api_key' => 'test-key']);

        Http::fake([
            'places.googleapis.com/*' => Http::response([
                'places' => [
                    [
                        'id' => 'google-place-1',
                        'displayName' => ['text' => 'Clínica Central'],
                        'formattedAddress' => 'Rua Exemplo, 100 - São Paulo, SP',
                        'location' => ['latitude' => -23.55, 'longitude' => -46.63],
                        'primaryTypeDisplayName' => ['text' => 'Clínica'],
                        'nationalPhoneNumber' => '(11) 3333-4444',
                        'websiteUri' => 'https://clinica.example',
                        'googleMapsUri' => 'https://maps.google.com/?cid=1',
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $course = $user->courses()->create([
            'name' => 'Técnico em Enfermagem',
            'area' => Course::AREAS[0],
            'state' => 'SP',
            'city' => 'São Paulo',
        ]);

        ReceitaCompany::create([
            'cnpj' => '12345678000190',
            'corporate_name' => 'Clínica Central LTDA',
            'trade_name' => 'Clínica Central',
            'registration_status' => 'Ativa',
            'cnae_code' => '8630503',
            'cnae_description' => 'Atividade médica ambulatorial',
            'state' => 'SP',
            'city' => 'SÃO PAULO',
            'street_type' => 'Rua',
            'street' => 'Exemplo',
            'number' => '100',
            'district' => 'Centro',
            'zip_code' => '01000000',
            'email' => 'contato@clinica.example',
            'phone' => '11 33334444',
        ]);

        $this->actingAs($user)
            ->postJson('/companies/scan', ['course_id' => $course->id])
            ->assertOk()
            ->assertJsonPath('provider', 'google_places')
            ->assertJsonPath('scanned', true)
            ->assertJsonPath('companies.0.name', 'Clínica Central')
            ->assertJsonPath('companies.0.cnpj', null)
            ->assertJsonPath('companies.0.source', 'Google Places')
            ->assertJsonPath('companies.0.phone', '(11) 3333-4444');

        $this->actingAs($user)
            ->getJson("/companies/search?course_id={$course->id}")
            ->assertOk()
            ->assertJsonPath('companies.0.name', 'Clínica Central');
    }

    public function test_google_places_refresh_requires_a_course(): void
    {
        config(['services.google.maps_api_key' => 'test-key']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/companies/scan', [])
            ->assertStatus(422);
    }
}
