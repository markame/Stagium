<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CompanySearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $apiKey = config('services.google.maps_api_key');

        if (blank($apiKey)) {
            return response()->json([
                'message' => 'Configure GOOGLE_MAPS_API_KEY no arquivo .env para usar Google Places API.',
                'companies' => [],
            ], 422);
        }

        $validated = $request->validate([
            'course_id' => ['nullable', 'integer'],
        ]);

        $courses = Course::where('coordinator_id', $request->user()->id)
            ->when($validated['course_id'] ?? null, fn ($query, int $courseId) => $query->whereKey($courseId))
            ->orderBy('name')
            ->get();

        if (($validated['course_id'] ?? null) && $courses->isEmpty()) {
            abort(404);
        }

        $companies = $courses
            ->flatMap(fn (Course $course): array => $this->googleCompaniesFor($course, $apiKey))
            ->unique(fn (array $company): string => strtolower($company['name'].'|'.($company['address'] ?? '').'|'.$company['lat'].'|'.$company['lng']))
            ->values()
            ->all();

        return response()->json([
            'provider' => 'google',
            'courses_count' => $courses->count(),
            'center' => $this->centerFromCompanies($companies),
            'companies' => $companies,
        ]);
    }

    /**
     * @return array<int, array{name: string, type: string|null, lat: float, lng: float, address: string|null, phone: string|null, international_phone: string|null, website_url: string|null, maps_url: string|null, source: string, course: array{id: int, name: string, area: string, city: string|null, state: string|null}}>
     */
    private function googleCompaniesFor(Course $course, string $apiKey): array
    {
        $cacheKey = "google.companies.{$course->id}.{$course->updated_at?->timestamp}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($course, $apiKey) {
            $response = Http::acceptJson()
                ->timeout(30)
                ->withHeaders([
                    'X-Goog-Api-Key' => $apiKey,
                    'X-Goog-FieldMask' => 'places.displayName,places.formattedAddress,places.location,places.primaryType,places.nationalPhoneNumber,places.internationalPhoneNumber,places.websiteUri,places.googleMapsUri',
                ])
                ->post('https://places.googleapis.com/v1/places:searchText', [
                    'textQuery' => $this->queryForCourse($course),
                    'languageCode' => 'pt-BR',
                    'regionCode' => 'BR',
                    'maxResultCount' => 20,
                ]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json('places', []))
                ->map(function (array $place) use ($course): ?array {
                    $location = $place['location'] ?? [];
                    $lat = $location['latitude'] ?? null;
                    $lng = $location['longitude'] ?? null;
                    $name = $place['displayName']['text'] ?? null;

                    if ($lat === null || $lng === null || blank($name)) {
                        return null;
                    }

                    return [
                        'name' => $name,
                        'type' => $place['primaryType'] ?? null,
                        'lat' => (float) $lat,
                        'lng' => (float) $lng,
                        'address' => $place['formattedAddress'] ?? null,
                        'phone' => $place['nationalPhoneNumber'] ?? null,
                        'international_phone' => $place['internationalPhoneNumber'] ?? null,
                        'website_url' => $place['websiteUri'] ?? null,
                        'maps_url' => $place['googleMapsUri'] ?? null,
                        'source' => 'Google Places',
                        'course' => [
                            'id' => $course->id,
                            'name' => $course->name,
                            'area' => $course->area,
                            'city' => $course->city,
                            'state' => $course->state,
                        ],
                    ];
                })
                ->filter()
                ->values()
                ->all();
        });
    }

    private function queryForCourse(Course $course): string
    {
        $term = match ($course->area) {
            'Saúde, Estética, Cuidado Humano e Medicamentos' => 'saúde estética cuidado humano medicamentos hospital clínica consultório laboratório farmácia odontologia fisioterapia psicologia enfermagem',
            'Informática, Tecnologia da Informação e Comunicação' => 'informática tecnologia da informação comunicação software hardware redes telecomunicações internet suporte técnico dados',
            'Gestão e Negócios' => 'gestão negócios administração contabilidade consultoria recursos humanos financeiro banco comércio escritório',
            'Controle e Processos Industriais' => 'indústria fábrica manutenção automação controle de qualidade produção mecânica elétrica processos industriais',
            'Engenharias, Construção Civil, Arquitetura e Infraestrutura' => 'engenharias construção civil arquitetura infraestrutura obras saneamento energia logística transporte manutenção predial',
            'Agronegócio, Agroindústria, Agropecuária, Produção e Recursos Naturais' => 'agronegócio agroindústria agropecuária fazendas produção recursos naturais agricultura pecuária meio ambiente alimentos mineração pesca silvicultura',
            'Jurídica' => 'jurídico advocacia cartório fórum defensoria promotoria consultoria jurídica',
            default => 'empresas serviços organizações',
        };

        return "{$course->name} {$course->area} {$term} empresas públicas privadas em {$course->city}, {$course->state}, Brasil";
    }

    /**
     * @param  array<int, array{lat: float, lng: float}>  $companies
     * @return array{lat: float, lng: float}|null
     */
    private function centerFromCompanies(array $companies): ?array
    {
        if ($companies === []) {
            return null;
        }

        return [
            'lat' => (float) collect($companies)->avg('lat'),
            'lng' => (float) collect($companies)->avg('lng'),
        ];
    }
}
