<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        return view('welcome', [
            'areas' => Course::AREAS,
            'states' => Course::STATES,
            'courses' => Course::with('coordinator')
                ->where('coordinator_id', $request->user()->id)
                ->latest()
                ->get(),
            'googleMapsApiKey' => config('services.google.maps_api_key'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->courses()->create($this->validateCourse($request));

        return back()->with('status', 'Curso cadastrado com sucesso. Use a busca para consultar empresas no Google Places.');
    }

    public function edit(Request $request, Course $course): View
    {
        $this->ensureCourseBelongsToUser($request, $course);

        return view('courses.edit', [
            'areas' => Course::AREAS,
            'states' => Course::STATES,
            'course' => $course,
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseBelongsToUser($request, $course);

        $course->update($this->validateCourse($request));

        return redirect()->route('courses.index')->with('status', 'Curso atualizado com sucesso.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseBelongsToUser($request, $course);

        $course->delete();

        return back()->with('status', 'Curso excluído com sucesso.');
    }

    /**
     * @return array{name: string, area: string, state: string, city: string}
     */
    private function validateCourse(Request $request): array
    {
        $cities = $this->citiesForState($request->input('state'));

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', Rule::in(Course::AREAS)],
            'state' => ['required', 'string', Rule::in(Course::STATES)],
            'city' => ['required', 'string', 'max:255', Rule::in($cities)],
        ], [
            'city.in' => 'Selecione uma cidade válida para o estado informado.',
        ]);
    }

    private function ensureCourseBelongsToUser(Request $request, Course $course): void
    {
        abort_unless($course->coordinator_id === $request->user()->id, 404);
    }

    /**
     * @return array<int, string>
     */
    private function citiesForState(?string $state): array
    {
        if (! in_array($state, Course::STATES, true)) {
            return [];
        }

        return Cache::remember("ibge.cities.{$state}", now()->addDay(), function () use ($state) {
            $response = Http::acceptJson()
                ->timeout(10)
                ->get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$state}/municipios", [
                    'orderBy' => 'nome',
                ]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json())
                ->pluck('nome')
                ->filter()
                ->values()
                ->all();
        });
    }
}
