<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCompany;
use App\Services\ReceitaCompanyScanner;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CompanySearchController extends Controller
{
    public function __construct(private readonly ReceitaCompanyScanner $scanner) {}

    public function __invoke(Request $request): JsonResponse
    {
        $courses = $this->coursesForRequest($request);
        $companies = $this->storedCompaniesFor($courses);

        return $this->companiesResponse($courses, $companies, [
            'scanned' => false,
            'new_companies_count' => 0,
            'skipped_count' => 0,
        ]);
    }

    public function scan(Request $request): JsonResponse
    {
        if (blank(config('services.google.maps_api_key'))) {
            return response()->json([
                'message' => 'Configure GOOGLE_MAPS_API_KEY no arquivo .env para geocodificar os endereços no Google Maps.',
                'companies' => [],
            ], 422);
        }

        if (! $request->filled('course_id')) {
            return response()->json([
                'message' => 'Selecione um curso para executar o scanner da base da Receita.',
                'companies' => [],
                'errors' => [
                    'course_id' => ['Selecione um curso para executar o scanner da base da Receita.'],
                ],
            ], 422);
        }

        $course = Course::where('coordinator_id', $request->user()->id)
            ->findOrFail((int) $request->input('course_id'));

        $result = $this->scanner->scan($course, config('services.google.maps_api_key'));

        $courses = new EloquentCollection([$course]);
        $companies = $this->storedCompaniesFor($courses);

        return $this->companiesResponse($courses, $companies, [
            'scanned' => true,
            'new_companies_count' => $result['new_companies_count'],
            'skipped_count' => $result['skipped_count'],
        ]);
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
    private function storedCompaniesFor(EloquentCollection $courses): Collection
    {
        if ($courses->isEmpty()) {
            return collect();
        }

        return CourseCompany::with('course')
            ->whereIn('course_id', $courses->pluck('id'))
            ->orderBy('course_id')
            ->orderBy('name')
            ->get()
            ->map(fn (CourseCompany $company): array => $this->formatCompany($company));
    }

    /**
     * @param  EloquentCollection<int, Course>  $courses
     * @param  Collection<int, array<string, mixed>>  $companies
     * @param  array<string, mixed>  $extra
     */
    private function companiesResponse(EloquentCollection $courses, Collection $companies, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'provider' => 'receita_federal',
            'courses_count' => $courses->count(),
            'center' => $this->centerFromCompanies($companies->all()),
            'companies' => $companies->values()->all(),
        ], $extra));
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCompany(CourseCompany $company): array
    {
        return [
            'id' => $company->id,
            'cnpj' => $company->cnpj,
            'name' => $company->name,
            'corporate_name' => $company->corporate_name,
            'trade_name' => $company->trade_name,
            'type' => $company->type,
            'cnae_code' => $company->cnae_code,
            'registration_status' => $company->registration_status,
            'lat' => $company->lat,
            'lng' => $company->lng,
            'address' => $company->address,
            'email' => $company->email,
            'phone' => $company->phone,
            'international_phone' => $company->international_phone,
            'website_url' => $company->website_url,
            'maps_url' => $company->maps_url,
            'source' => $company->source,
            'last_scanned_at' => $company->last_scanned_at?->toISOString(),
            'course' => [
                'id' => $company->course->id,
                'name' => $company->course->name,
                'area' => $company->course->area,
                'city' => $company->course->city,
                'state' => $company->course->state,
            ],
        ];
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
