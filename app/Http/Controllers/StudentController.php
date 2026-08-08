<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Services\StudentCsvImporter;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function __construct(private readonly StudentCsvImporter $csvImporter) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));
        $searchDigits = preg_replace('/\D/', '', $search);

        return view('students.index', [
            'courses' => $request->user()->courses()->orderBy('name')->get(),
            'companies' => $request->user()->companies()->orderBy('corporate_name')->get(),
            'students' => $request->user()->students()
                ->with(['course', 'internshipCompany'])
                ->when($search !== '', function ($query) use ($search, $searchDigits): void {
                    $query->where(function ($query) use ($search, $searchDigits): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhereHas('course', fn ($course) => $course->where('name', 'like', "%{$search}%"));

                        if ($searchDigits !== '') {
                            $query->orWhere('cpf', 'like', "%{$searchDigits}%");
                        }
                    });
                })
                ->orderBy('name')
                ->get(),
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $student = $request->user()->students()->create($this->validatedData($request));
        });

        return redirect()->route('students.index')->with('status', 'Aluno cadastrado com sucesso.');
    }

    public function importForm(): View
    {
        return view('students.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => [
                'required',
                'file',
                'extensions:csv,txt,xlsx',
                'mimetypes:text/plain,text/csv,application/csv,application/zip,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'max:5120',
            ],
        ], [
            'csv_file.extensions' => 'Envie um arquivo CSV, TXT ou XLSX.',
            'csv_file.mimetypes' => 'O conteúdo do arquivo deve ser CSV, TXT ou XLSX.',
            'csv_file.max' => 'O arquivo não pode ultrapassar 5 MB.',
        ]);

        try {
            $count = $this->csvImporter->import($request->user(), $request->file('csv_file'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['csv_file' => $exception->getMessage()]);
        }

        return redirect()->route('students.index')->with('status', "{$count} aluno(s) importado(s) com sucesso.");
    }

    public function importTemplate(): StreamedResponse
    {
        $header = "Nome\tCPF\tRG\tData de Nascimento\tCelular Para SMS\tTelefone 1\tTelefone 2\tTelefone 3\tOutros Telefones\tEndereço\tCurso\tResponsável\r\n";

        return response()->streamDownload(function () use ($header): void {
            echo "\xEF\xBB\xBF".$header;
        }, 'modelo-importacao-alunos.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function edit(Request $request, Student $student): View
    {
        $this->ensureStudentBelongsToUser($request, $student);

        return view('students.edit', [
            'student' => $student->load('userAccount'),
            'courses' => $request->user()->courses()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->ensureStudentBelongsToUser($request, $student);
        DB::transaction(function () use ($request, $student): void {
            $student->update($this->validatedData($request, $student));
        });

        return redirect()->route('students.index')->with('status', 'Aluno atualizado com sucesso.');
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->ensureStudentBelongsToUser($request, $student);

        foreach ($student->documents as $document) {
            Storage::disk('local')->delete($document->path);
        }
        $student->delete();

        return redirect()->route('students.index')->with('status', 'Aluno excluído com sucesso.');
    }

    public function updateInternshipCompany(Request $request, Student $student): RedirectResponse
    {
        $this->ensureStudentBelongsToUser($request, $student);
        $data = $request->validate([
            'company_id' => ['nullable', Rule::exists('companies', 'id')->where(fn ($query) => $query->where('coordinator_id', $request->user()->id))],
        ]);
        $student->update(['internship_company_id' => $data['company_id'] ?? null]);

        return back()->with('status', 'Vínculo de estágio do aluno atualizado com sucesso.');
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request, ?Student $student = null): array
    {
        $request->merge([
            'cpf' => preg_replace('/\D/', '', (string) $request->input('cpf')),
            'phone' => preg_replace('/[^\d+]/', '', (string) $request->input('phone')),
            'sms_phone' => preg_replace('/[^\d+]/', '', (string) $request->input('sms_phone')) ?: null,
            'phone_2' => preg_replace('/[^\d+]/', '', (string) $request->input('phone_2')) ?: null,
            'phone_3' => preg_replace('/[^\d+]/', '', (string) $request->input('phone_3')) ?: null,
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'cpf' => ['required', 'digits:11', Rule::unique('students')->ignore($student)],
            'rg' => ['nullable', 'string', 'max:30'],
            'sms_phone' => ['nullable', 'string', 'max:20'],
            'phone_2' => ['nullable', 'string', 'max:20'],
            'phone_3' => ['nullable', 'string', 'max:20'],
            'other_phones' => ['nullable', 'string', 'max:255'],
            'parentage' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'course_id' => [
                'required',
                Rule::exists('courses', 'id')->where(
                    fn ($query) => $query->where('coordinator_id', $request->user()->id)
                ),
            ],
        ], [
            'cpf.digits' => 'O CPF deve conter 11 dígitos.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'birth_date.before' => 'A data de nascimento deve ser anterior a hoje.',
            'course_id.exists' => 'Selecione um curso válido.',
        ]);
    }

    private function ensureStudentBelongsToUser(Request $request, Student $student): void
    {
        abort_unless($student->coordinator_id === $request->user()->id, 404);
    }

}
