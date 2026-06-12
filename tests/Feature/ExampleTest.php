<?php

namespace Tests\Feature;

use App\Models\Course;
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

    public function test_an_authenticated_user_can_update_their_course(): void
    {
        Http::fake([
            'servicodados.ibge.gov.br/*' => Http::response([
                ['nome' => 'Campinas'],
            ]),
        ]);

        $user = User::factory()->create();
        $course = $user->courses()->create([
            'name' => 'Tecnico em Enfermagem',
            'area' => Course::AREAS[0],
            'state' => 'SP',
            'city' => 'Sao Paulo',
        ]);

        $this->actingAs($user)
            ->put("/courses/{$course->id}", [
                'name' => 'Tecnico em Informatica',
                'area' => Course::AREAS[1],
                'state' => 'SP',
                'city' => 'Campinas',
            ])
            ->assertRedirect('/');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Tecnico em Informatica',
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
            'name' => 'Tecnico em Enfermagem',
            'area' => Course::AREAS[0],
            'state' => 'SP',
            'city' => 'Sao Paulo',
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
            'name' => 'Tecnico em Enfermagem',
            'area' => Course::AREAS[0],
            'state' => 'SP',
            'city' => 'Sao Paulo',
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

    public function test_google_places_key_is_required_for_company_search(): void
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
            ->getJson("/companies/search?course_id={$course->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Configure GOOGLE_MAPS_API_KEY no arquivo .env para usar Google Places API.');
    }

    public function test_an_authenticated_user_can_search_companies_with_google_places(): void
    {
        config(['services.google.maps_api_key' => 'test-key']);

        Http::fake([
            'places.googleapis.com/*' => Http::response([
                'places' => [
                    [
                        'displayName' => ['text' => 'Clínica Central'],
                        'formattedAddress' => 'Rua Exemplo, 100 - São Paulo, SP',
                        'primaryType' => 'clinic',
                        'nationalPhoneNumber' => '(11) 3333-4444',
                        'internationalPhoneNumber' => '+55 11 3333-4444',
                        'websiteUri' => 'https://clinica.example.com',
                        'googleMapsUri' => 'https://maps.google.com/?cid=123',
                        'location' => [
                            'latitude' => -23.55,
                            'longitude' => -46.63,
                        ],
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

        $this->actingAs($user)
            ->getJson("/companies/search?course_id={$course->id}")
            ->assertOk()
            ->assertJsonPath('provider', 'google')
            ->assertJsonPath('companies.0.name', 'Clínica Central')
            ->assertJsonPath('companies.0.phone', '(11) 3333-4444')
            ->assertJsonPath('companies.0.international_phone', '+55 11 3333-4444')
            ->assertJsonPath('companies.0.website_url', 'https://clinica.example.com');
    }
    public function test_company_search_without_course_filter_loads_all_user_courses(): void
    {
        config(['services.google.maps_api_key' => 'test-key']);

        Http::fake([
            'places.googleapis.com/*' => Http::sequence()
                ->push([
                    'places' => [
                        [
                            'displayName' => ['text' => 'Hospital Central'],
                            'formattedAddress' => 'Rua Saude, 100 - Sao Paulo, SP',
                            'primaryType' => 'hospital',
                            'googleMapsUri' => 'https://maps.google.com/?cid=123',
                            'location' => [
                                'latitude' => -23.55,
                                'longitude' => -46.63,
                            ],
                        ],
                    ],
                ])
                ->push([
                    'places' => [
                        [
                            'displayName' => ['text' => 'Empresa de Tecnologia'],
                            'formattedAddress' => 'Rua Dados, 200 - Campinas, SP',
                            'primaryType' => 'software_company',
                            'googleMapsUri' => 'https://maps.google.com/?cid=456',
                            'location' => [
                                'latitude' => -22.9,
                                'longitude' => -47.06,
                            ],
                        ],
                    ],
                ]),
        ]);

        $user = User::factory()->create();
        $user->courses()->create([
            'name' => 'Tecnico em Enfermagem',
            'area' => Course::AREAS[0],
            'state' => 'SP',
            'city' => 'Sao Paulo',
        ]);
        $user->courses()->create([
            'name' => 'Tecnico em Informatica',
            'area' => Course::AREAS[1],
            'state' => 'SP',
            'city' => 'Campinas',
        ]);

        $this->actingAs($user)
            ->getJson('/companies/search')
            ->assertOk()
            ->assertJsonPath('provider', 'google')
            ->assertJsonPath('courses_count', 2)
            ->assertJsonPath('companies.0.name', 'Hospital Central')
            ->assertJsonPath('companies.1.name', 'Empresa de Tecnologia');

        Http::assertSentCount(2);
    }
}
