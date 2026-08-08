<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\StudentForwardingTermGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentDocumentController extends Controller
{
    public function __construct(private readonly StudentForwardingTermGenerator $generator) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));
        $searchDigits = preg_replace('/\D/', '', $search);
        $companies = $request->user()->companies()
            ->when($search !== '', function ($query) use ($search, $searchDigits): void {
                $query->where(function ($query) use ($search, $searchDigits): void {
                    $query->where('corporate_name', 'like', "%{$search}%")
                        ->orWhere('trade_name', 'like', "%{$search}%");
                    if ($searchDigits !== '') {
                        $query->orWhere('cnpj', 'like', "%{$searchDigits}%");
                    }
                });
            })->orderBy('corporate_name')->get();
        $selectedCompany = $companies->firstWhere('id', (int) $request->input('company_id')) ?? $companies->first();
        $selectedCompany?->load('studentDocuments.student.course');

        return view('companies.terms', [
            'students' => $request->user()->students()
                ->with('course')->orderBy('name')->get(),
            'companies' => $companies,
            'selectedCompany' => $selectedCompany,
            'search' => $search,
            'hasCompanies' => $request->user()->companies()->exists(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->user()->id;
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1', 'max:30'],
            'student_ids.*' => ['required', 'distinct', Rule::exists('students', 'id')->where(fn ($query) => $query->where('coordinator_id', $userId))],
            'company_id' => ['required', Rule::exists('companies', 'id')->where(fn ($query) => $query->where('coordinator_id', $userId))],
            'start_date' => ['required', 'date'],
            'manager_name' => ['required', 'string', 'max:255'],
            'iema_unit' => ['required', 'string', 'max:255'],
            'responsible_role' => ['required', 'string', 'max:255'],
        ]);

        $students = Student::with('course')->where('coordinator_id', $userId)->whereIn('id', $data['student_ids'])->get();
        $company = Company::where('coordinator_id', $userId)->findOrFail($data['company_id']);

        if ($students->contains(fn (Student $student) => ! $student->course)) {
            return back()->withInput()->withErrors([
                'student_ids' => 'Vincule um curso a todos os alunos antes de gerar o Termo de Encaminhamento.',
            ]);
        }

        try {
            $generatedPath = $this->generator->generate($students, $company, $data);
            $storagePath = 'company-documents/'.$company->id.'/termo-encaminhamento-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(3)).'.pdf';
            Storage::disk('local')->put($storagePath, File::get($generatedPath));
            File::delete($generatedPath);
            $workDirectory = dirname($generatedPath);
            $allowedRoot = storage_path('app/private/tmp/student-terms').DIRECTORY_SEPARATOR;
            if (str_starts_with($workDirectory.DIRECTORY_SEPARATOR, $allowedRoot)) {
                File::deleteDirectory($workDirectory);
            }
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['generation' => $exception->getMessage()]);
        }

        $oldPaths = StudentDocument::whereIn('student_id', $students->pluck('id'))
            ->where('type', StudentDocument::FORWARDING_TERM)->pluck('path');
        StudentDocument::whereIn('student_id', $students->pluck('id'))
            ->where('type', StudentDocument::FORWARDING_TERM)->delete();
        foreach ($students as $student) {
            $student->update(['internship_company_id' => $company->id]);
            $student->documents()->create([
                'company_id' => $company->id,
                'type' => StudentDocument::FORWARDING_TERM,
                'original_name' => 'termo-encaminhamento-'.$company->id.'.pdf',
                'path' => $storagePath,
                'generation_data' => $data,
            ]);
        }
        foreach ($oldPaths->unique() as $oldPath) {
            if (! StudentDocument::where('path', $oldPath)->exists()) {
                Storage::disk('local')->delete($oldPath);
            }
        }

        return redirect()->route('companies.terms.index', ['company_id' => $company->id])->with('status', 'Termo de encaminhamento gerado com os alunos selecionados.');
    }

    public function download(Request $request, StudentDocument $document): StreamedResponse
    {
        abort_unless($document->student->coordinator_id === $request->user()->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name, ['Content-Type' => 'application/pdf']);
    }

    public function destroy(Request $request, StudentDocument $document): RedirectResponse
    {
        abort_unless($document->student->coordinator_id === $request->user()->id, 404);
        $companyId = $document->company_id;
        $path = $document->path;
        StudentDocument::where('path', $path)->delete();
        Storage::disk('local')->delete($path);

        return redirect()->route('companies.terms.index', ['company_id' => $companyId])->with('status', 'Termo de encaminhamento removido.');
    }
}
