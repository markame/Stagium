<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GooglePlacesCompanySearch
{
    /** @return Collection<int, array<string, mixed>> */
    public function search(Course $course, string $apiKey): Collection
    {
        $query = trim($course->area.' '.$course->name.' empresas em '.$course->city.' '.$course->state);
        $response = Http::timeout(20)->withHeaders([
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location,places.primaryTypeDisplayName,places.nationalPhoneNumber,places.internationalPhoneNumber,places.websiteUri,places.googleMapsUri,places.businessStatus',
        ])->post('https://places.googleapis.com/v1/places:searchText', [
            'textQuery' => $query,
            'languageCode' => 'pt-BR',
            'regionCode' => 'BR',
            'pageSize' => 20,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('O Google Places não conseguiu concluir a busca de empresas.');
        }

        return collect($response->json('places', []))->map(fn (array $place): array => [
            'id' => $place['id'] ?? null,
            'cnpj' => null,
            'name' => $place['displayName']['text'] ?? 'Empresa sem nome',
            'corporate_name' => null,
            'trade_name' => $place['displayName']['text'] ?? null,
            'type' => $place['primaryTypeDisplayName']['text'] ?? 'empresa',
            'registration_status' => $place['businessStatus'] ?? null,
            'lat' => (float) ($place['location']['latitude'] ?? 0),
            'lng' => (float) ($place['location']['longitude'] ?? 0),
            'address' => $place['formattedAddress'] ?? null,
            'email' => null,
            'phone' => $place['nationalPhoneNumber'] ?? null,
            'international_phone' => $place['internationalPhoneNumber'] ?? null,
            'website_url' => $place['websiteUri'] ?? null,
            'maps_url' => $place['googleMapsUri'] ?? null,
            'source' => 'Google Places',
            'course' => ['id' => $course->id, 'name' => $course->name, 'area' => $course->area, 'city' => $course->city, 'state' => $course->state],
        ])->filter(fn (array $company): bool => $company['lat'] !== 0.0 && $company['lng'] !== 0.0)->values();
    }
}
