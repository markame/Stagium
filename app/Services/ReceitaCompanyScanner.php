<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseCompany;
use App\Models\ReceitaCompany;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Http;

class ReceitaCompanyScanner
{
    /**
     * @return array{new_companies_count: int, skipped_count: int}
     */
    public function scan(Course $course, ?string $googleMapsApiKey = null): array
    {
        $newCompaniesCount = 0;
        $skippedCount = 0;

        foreach ($this->receitaCompaniesFor($course) as $receitaCompany) {
            $company = $this->companyFromReceita($receitaCompany, $googleMapsApiKey);

            if ($company === null) {
                $skippedCount++;
                continue;
            }

            $storedCompany = CourseCompany::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'source_hash' => $company['source_hash'],
                ],
                [
                    'cnpj' => $company['cnpj'],
                    'google_place_id' => null,
                    'name' => $company['name'],
                    'corporate_name' => $company['corporate_name'],
                    'trade_name' => $company['trade_name'],
                    'type' => $company['type'],
                    'lat' => $company['lat'],
                    'lng' => $company['lng'],
                    'address' => $company['address'],
                    'email' => $company['email'],
                    'phone' => $company['phone'],
                    'international_phone' => null,
                    'website_url' => null,
                    'maps_url' => $company['maps_url'],
                    'cnae_code' => $company['cnae_code'],
                    'registration_status' => $company['registration_status'],
                    'source' => 'Receita Federal',
                    'raw_data' => $company['raw_data'],
                    'last_scanned_at' => now(),
                ],
            );

            if ($storedCompany->wasRecentlyCreated) {
                $newCompaniesCount++;
            }
        }

        return [
            'new_companies_count' => $newCompaniesCount,
            'skipped_count' => $skippedCount,
        ];
    }

    /**
     * @return EloquentCollection<int, ReceitaCompany>
     */
    private function receitaCompaniesFor(Course $course): EloquentCollection
    {
        $terms = $this->termsForArea($course->area);

        return ReceitaCompany::query()
            ->where('state', $course->state)
            ->whereRaw('UPPER(city) = ?', [mb_strtoupper((string) $course->city)])
            ->when($terms !== [], function ($query) use ($terms): void {
                $query->where(function ($query) use ($terms): void {
                    foreach ($terms as $term) {
                        $query->orWhere('cnae_description', 'like', "%{$term}%")
                            ->orWhere('corporate_name', 'like', "%{$term}%")
                            ->orWhere('trade_name', 'like', "%{$term}%");
                    }
                });
            })
            ->orderByRaw("registration_status = 'Ativa' desc")
            ->orderBy('trade_name')
            ->orderBy('corporate_name')
            ->limit(25)
            ->get();
    }

    /**
     * @return list<string>
     */
    private function termsForArea(string $area): array
    {
        return match ($area) {
            'Saúde' => ['saúde', 'hospital', 'clínica', 'farmácia', 'medicamento', 'odontologia', 'fisioterapia', 'enfermagem', 'estética', 'médica'],
            'Informação e Comunicação' => ['informática', 'software', 'tecnologia', 'internet', 'dados', 'telecomunicação', 'comunicação', 'mídia'],
            'Gestão e Negócios' => ['administração', 'consultoria', 'contabilidade', 'financeiro', 'banco', 'comércio', 'recursos humanos'],
            'Controle e Processos Industriais' => ['indústria', 'fabricação', 'manutenção', 'automação', 'mecânica', 'elétrica', 'produção'],
            'Infraestrutura' => ['construção', 'engenharia', 'arquitetura', 'infraestrutura', 'obras', 'saneamento', 'logística', 'transporte'],
            'Produção e Recursos Naturais' => ['agricultura', 'pecuária', 'fazenda', 'agropecuária', 'agroindústria', 'alimentos', 'mineração', 'pesca', 'silvicultura'],
            'Jurídica' => ['advocacia', 'jurídico', 'cartório', 'direito'],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function companyFromReceita(ReceitaCompany $receitaCompany, ?string $googleMapsApiKey): ?array
    {
        $address = $receitaCompany->fullAddress();
        $location = $this->geocodeAddress($address, $googleMapsApiKey);

        if ($location === null) {
            return null;
        }

        return [
            'cnpj' => $receitaCompany->cnpj,
            'source_hash' => sha1((string) $receitaCompany->cnpj),
            'name' => $receitaCompany->displayName(),
            'corporate_name' => $receitaCompany->corporate_name,
            'trade_name' => $receitaCompany->trade_name,
            'type' => $receitaCompany->cnae_description,
            'cnae_code' => $receitaCompany->cnae_code,
            'registration_status' => $receitaCompany->registration_status,
            'lat' => $location['lat'],
            'lng' => $location['lng'],
            'address' => $address,
            'email' => $receitaCompany->email,
            'phone' => $receitaCompany->phone,
            'maps_url' => 'https://www.google.com/maps/search/?api=1&query='.urlencode($address),
            'raw_data' => $receitaCompany->toArray(),
        ];
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    private function geocodeAddress(string $address, ?string $apiKey): ?array
    {
        if (blank($address)) {
            return null;
        }

        if (blank($apiKey)) {
            return [
                'lat' => 0.0,
                'lng' => 0.0,
            ];
        }

        $response = Http::acceptJson()
            ->timeout(20)
            ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $apiKey,
                'region' => 'br',
            ]);

        if (! $response->successful() || $response->json('status') !== 'OK') {
            return null;
        }

        $location = $response->json('results.0.geometry.location');

        if (! is_array($location) || ! isset($location['lat'], $location['lng'])) {
            return null;
        }

        return [
            'lat' => (float) $location['lat'],
            'lng' => (float) $location['lng'],
        ];
    }
}
