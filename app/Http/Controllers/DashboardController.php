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
        $studentIds = $user->students()->pluck('id');
        $logs = StudentTimeLog::query()->whereIn('student_id', $studentIds);

        return view('dashboard', [
            'totalStudents' => $studentIds->count(),
            'totalCompanies' => $user->companies()->count(),
            'linkedStudents' => $user->students()->whereNotNull('internship_company_id')->count(),
            'logsToday' => (clone $logs)->whereBetween('logged_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'lastLogs' => $logs->with(['student:id,name', 'company:id,corporate_name'])->latest('logged_at')->limit(10)->get(),
        ]);
    }
}
