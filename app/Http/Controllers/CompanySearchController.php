<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\GooglePlacesCompanySearch;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CompanySearchController extends Controller
{
    public function __construct(private readonly GooglePlacesCompanySearch $search) {}

    public function __invoke(Request $request): JsonResponse
    {
        $courses = $this->coursesForRequest($request);
        $companies = $this->searchGooglePlaces($courses);

        return $this->companiesResponse($courses, $companies);
    }

    public function scan(Request $request): JsonResponse
    {
        if (blank(config('services.google.maps_api_key'))) {
            return response()->json([
                'message' => 'Configure GOOGLE_MAPS_API_KEY no arquivo .env para consultar o Google Places.',
                'companies' => [],
            ], 422);
        }

        if (! $request->filled('course_id')) {
            return response()->json([
                'message' => 'Selecione um curso para consultar empresas no Google Places.',
                'companies' => [],
                'errors' => [
                    'course_id' => ['Selecione um curso para consultar empresas no Google Places.'],
                ],
            ], 422);
        }

        $course = Course::where('coordinator_id', $request->user()->id)
            ->findOrFail((int) $request->input('course_id'));

        $courses = new EloquentCollection([$course]);
        return $this->companiesResponse($courses, $this->searchGooglePlaces($courses), ['scanned' => true]);
    }

    /**
     * @return EloquentCollection<int, Course>
     */
    private function coursesForRequest(Request $request): EloquentCollection
    {
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

        return $courses;
    }

    /**
     * @param  EloquentCollection<int, Course>  $courses
     * @return Collection<int, array<string, mixed>>
     */
    private function searchGooglePlaces(EloquentCollection $courses): Collection
    {
        $apiKey = (string) config('services.google.maps_api_key');
        if ($courses->isEmpty() || $apiKey === '') {
            return collect();
        }
        return $courses->flatMap(fn (Course $course) => $this->search->search($course, $apiKey))
            ->unique(fn (array $company) => $company['id'])->values();
    }

    /**
     * @param  EloquentCollection<int, Course>  $courses
     * @param  Collection<int, array<string, mixed>>  $companies
     * @param  array<string, mixed>  $extra
     */
    private function companiesResponse(EloquentCollection $courses, Collection $companies, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'provider' => 'google_places',
            'courses_count' => $courses->count(),
            'center' => $this->centerFromCompanies($companies->all()),
            'companies' => $companies->values()->all(),
        ], $extra));
    }

    /**
     * @return array<string, mixed>
     */
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
