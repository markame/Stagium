<?php

namespace App\Http\Controllers;

use App\Models\StudentTimeLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $companies = $user->companies()->orderBy('corporate_name')->get();
        $selectedCompany = $request->filled('company_id')
            ? $companies->firstWhere('id', (int) $request->input('company_id'))
            : null;
        abort_if($request->filled('company_id') && ! $selectedCompany, 404);

        $studentsQuery = $user->students()->with('internshipCompany')
            ->when($selectedCompany, fn ($query) => $query->where('internship_company_id', $selectedCompany->id));
        $students = $studentsQuery->orderBy('name')->get();
        $studentIds = $students->pluck('id');
        $logs = StudentTimeLog::query()->whereIn('student_id', $studentIds)
            ->when($selectedCompany, fn ($query) => $query->where('company_id', $selectedCompany->id));
        $linkedStudents = $selectedCompany
            ? $students->count()
            : $user->students()->whereNotNull('internship_company_id')->count();

        $lastSevenDays = collect(range(6, 0))->map(function (int $daysAgo): array {
            $date = now()->subDays($daysAgo);
            return ['date'=>$date->toDateString(),'label'=>$date->translatedFormat('D d/m')];
        });
        $entriesSevenDays = (clone $logs)->where('type', 'in')->where('logged_at', '>=', now()->subDays(6)->startOfDay())->get();
        $frequency = $lastSevenDays->map(function (array $day) use ($entriesSevenDays, $linkedStudents): array {
            $present = $entriesSevenDays->filter(fn ($log) => $log->logged_at->toDateString() === $day['date'])->unique('student_id')->count();
            return $day + ['present'=>$present,'percentage'=>$linkedStudents > 0 ? (int) round(($present/$linkedStudents)*100) : 0];
        });

        $entriesThirtyDays = (clone $logs)->where('type', 'in')->where('logged_at', '>=', now()->subDays(29)->startOfDay())->get()->groupBy('student_id');
        $studentFrequency = $students->map(fn ($student) => [
            'name'=>$student->name,
            'company'=>$student->internshipCompany?->corporate_name,
            'entries'=>$entriesThirtyDays->get($student->id, collect())->count(),
        ])->sortByDesc('entries')->values();
        $maximumEntries = max(1, (int) $studentFrequency->max('entries'));

        return view('dashboard', [
            'companies' => $companies,
            'selectedCompany' => $selectedCompany,
            'totalStudents' => $students->count(),
            'totalCompanies' => $companies->count(),
            'linkedStudents' => $linkedStudents,
            'logsToday' => (clone $logs)->whereBetween('logged_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'lastLogs' => $logs->with(['student:id,name', 'company:id,corporate_name'])->latest('logged_at')->limit(10)->get(),
            'frequency' => $frequency,
            'studentFrequency' => $studentFrequency,
            'maximumEntries' => $maximumEntries,
        ]);
    }
}
