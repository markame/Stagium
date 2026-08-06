<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\StudentCommitmentTermGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class StudentCommitmentTermController extends Controller
{
    public function __construct(private readonly StudentCommitmentTermGenerator $generator) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));
        $digits = preg_replace('/\D/', '', $search);
        $students = $request->user()->students()->with(['course', 'documents.company'])
            ->when($search !== '', function ($query) use ($search, $digits): void {
                $query->where(function ($query) use ($search, $digits): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('course', fn ($course) => $course->where('name', 'like', "%{$search}%"));
                    if ($digits !== '') {
                        $query->orWhere('cpf', 'like', "%{$digits}%");
                    }
                });
            })->orderBy('name')->get();

        return view('students.commitment-terms', [
            'students' => $students,
            'companies' => $request->user()->companies()->orderBy('corporate_name')->get(),
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->user()->id;
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where(fn ($query) => $query->where('coordinator_id', $userId))],
            'company_id' => ['required', Rule::exists('companies', 'id')->where(fn ($query) => $query->where('coordinator_id', $userId))],
            'start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after:start_date'],
            'daily_start_time' => ['required', 'date_format:H:i'], 'daily_end_time' => ['required', 'date_format:H:i', 'after:daily_start_time'],
            'manager_name' => ['required', 'string', 'max:255'],
            'company_zip' => ['required', 'string', 'max:20'], 'company_neighborhood' => ['required', 'string', 'max:100'],
            'company_city' => ['required', 'string', 'max:100'], 'company_state' => ['required', 'string', 'size:2'],
            'student_neighborhood' => ['required', 'string', 'max:100'], 'student_city' => ['required', 'string', 'max:100'],
            'student_state' => ['required', 'string', 'size:2'],
        ]);
        $student = Student::with('course')->where('coordinator_id', $userId)->findOrFail($data['student_id']);
        $company = Company::where('coordinator_id', $userId)->findOrFail($data['company_id']);

        try {
            $generated = $this->generator->generate($student, $company, $data);
            $path = 'student-documents/'.$student->id.'/termo-compromisso-'.now()->format('YmdHis').'.pdf';
            Storage::disk('local')->put($path, File::get($generated));
            File::deleteDirectory(dirname($generated));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['generation' => $exception->getMessage()]);
        }

        $old = $student->documents()->where('type', StudentDocument::COMMITMENT_TERM)->first();
        $student->documents()->updateOrCreate(['type' => StudentDocument::COMMITMENT_TERM], [
            'company_id' => $company->id, 'original_name' => 'termo-compromisso-'.$student->id.'.pdf',
            'path' => $path, 'generation_data' => $data,
        ]);
        if ($old && $old->path !== $path) {
            Storage::disk('local')->delete($old->path);
        }

        return redirect()->route('students.commitment-terms.index')->with('status', 'Termo de Compromisso gerado e salvo em PDF.');
    }

    public function destroy(Request $request, StudentDocument $document): RedirectResponse
    {
        abort_unless($document->type === StudentDocument::COMMITMENT_TERM && $document->student->coordinator_id === $request->user()->id, 404);
        Storage::disk('local')->delete($document->path);
        $document->delete();
        return redirect()->route('students.commitment-terms.index')->with('status', 'Termo de Compromisso removido.');
    }
}
