<?php

namespace App\Http\Controllers;

use App\Models\StudentDocument;
use App\Models\StudentTimeLog;
use App\Services\GeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->student()->with('course')->firstOrFail();
        $document = $student->documents()->with('company')->whereIn('type', [StudentDocument::COMMITMENT_TERM, StudentDocument::FORWARDING_TERM])->latest()->first();
        return view('student-portal.dashboard', [
            'student' => $student,
            'company' => $document?->company,
            'logs' => $student->timeLogs()->with('company')->latest('logged_at')->limit(30)->get(),
            'lastLog' => $student->timeLogs()->latest('logged_at')->first(),
        ]);
    }

    public function mark(Request $request, GeoService $geo): JsonResponse
    {
        $data = $request->validate(['latitude' => ['required', 'numeric', 'between:-90,90'], 'longitude' => ['required', 'numeric', 'between:-180,180']]);
        $student = $request->user()->student;
        $document = $student->documents()->with('company')->whereIn('type', [StudentDocument::COMMITMENT_TERM, StudentDocument::FORWARDING_TERM])->latest()->firstOrFail();
        $company = $document->company;
        abort_unless($company && $company->latitude !== null && $company->longitude !== null, 422, 'A localização da empresa ainda não foi configurada.');
        $distance = $geo->distanceMeters((float) $data['latitude'], (float) $data['longitude'], (float) $company->latitude, (float) $company->longitude);
        if ($distance > $company->attendance_radius_meters) {
            return response()->json(['message' => 'Você está fora da área permitida.', 'distance_meters' => $distance, 'radius_meters' => $company->attendance_radius_meters], 422);
        }
        $last = $student->timeLogs()->latest('logged_at')->first();
        $type = (! $last || $last->type === 'out') ? 'in' : 'out';
        $log = StudentTimeLog::create(['student_id' => $student->id, 'company_id' => $company->id, 'type' => $type, 'logged_at' => now(), 'device_latitude' => $data['latitude'], 'device_longitude' => $data['longitude'], 'distance_meters' => $distance, 'ip' => $request->ip(), 'user_agent' => $request->userAgent()]);
        return response()->json(['message' => $type === 'in' ? 'Entrada registrada com sucesso!' : 'Saída registrada com sucesso!', 'type' => $type, 'log' => $log]);
    }
}
