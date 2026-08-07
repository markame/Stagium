<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use setasign\Fpdi\Fpdi;
use App\Services\CompanyOfficialDocumentsGenerator;
use Illuminate\Support\Facades\File;
use RuntimeException;

class CompanyDocumentController extends Controller
{
    public function __construct(private readonly CompanyOfficialDocumentsGenerator $officialGenerator) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));
        $searchDigits = preg_replace('/\D/', '', $search);
        $companyQuery = $request->user()->companies();
        $hasCompanies = (clone $companyQuery)->exists();
        $companies = $companyQuery
            ->with('documents')
            ->when($search !== '', function ($query) use ($search, $searchDigits): void {
                $query->where(function ($query) use ($search, $searchDigits): void {
                    $query->where('trade_name', 'like', "%{$search}%")
                        ->orWhere('corporate_name', 'like', "%{$search}%");

                    if ($searchDigits !== '') {
                        $query->orWhere('cnpj', 'like', "%{$searchDigits}%");
                    }
                });
            })
            ->orderBy('corporate_name')
            ->get();
        $selectedCompany = $companies->firstWhere('id', (int) $request->input('company_id')) ?? $companies->first();

        return view('companies.documents', [
            'companies' => $companies,
            'selectedCompany' => $selectedCompany,
            'documentTypes' => CompanyDocument::TYPES,
            'search' => $search,
            'hasCompanies' => $hasCompanies,
        ]);
    }

    public function store(Request $request, Company $company, string $type): RedirectResponse
    {
        $this->authorizeCompany($request, $company);
        abort_unless(isset(CompanyDocument::TYPES[$type]), 404);

        $validated = $request->validate([
            'document' => [
                'required',
                'file',
                'mimes:pdf',
                'max:10240',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        (new Fpdi())->setSourceFile($value->getRealPath());
                    } catch (\Throwable) {
                        $fail('O arquivo PDF está inválido ou corrompido.');
                    }
                },
            ],
        ], [
            'document.mimes' => 'O documento deve ser um arquivo PDF.',
            'document.max' => 'O documento não pode ultrapassar 10 MB.',
        ]);

        $file = $validated['document'];
        $oldDocument = $company->documents()->where('type', $type)->first();
        $path = $file->store("company-documents/{$company->id}", 'local');

        $company->documents()->updateOrCreate(['type' => $type], [
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/pdf',
            'size' => $file->getSize(),
        ]);

        if ($oldDocument && $oldDocument->path !== $path) {
            Storage::disk('local')->delete($oldDocument->path);
        }

        return redirect()->route('companies.documents.index', ['company_id' => $company->id])
            ->with('status', 'Documento anexado com sucesso.');
    }

    public function generateOfficial(Request $request, Company $company): RedirectResponse
    {
        $this->authorizeCompany($request, $company);
        $data = $request->validate([
            'agreement_number' => ['required','string','max:20'], 'ci_number' => ['required','string','max:20'],
            'iema_unit' => ['required','string','max:150'], 'iema_code' => ['required','string','max:40'],
            'manager_name' => ['required','string','max:255'], 'vacancies' => ['required','integer','min:1','max:999'],
            'document_date' => ['required','date'], 'issuing_authority' => ['required','string','max:50'],
            'business_area' => ['required','string','max:150'], 'company_city' => ['required','string','max:100'],
            'company_state' => ['required','string','size:2'], 'company_zip' => ['required','string','max:20'],
            'company_email' => ['required','email','max:255'], 'shipping_address' => ['required','string','max:255'],
            'shipping_city' => ['required','string','max:100'], 'shipping_state' => ['required','string','size:2'],
            'shipping_zip' => ['required','string','max:20'], 'delivery_responsible' => ['required','string','max:255'],
            'delivery_phone' => ['required','string','max:30'], 'delivery_email' => ['required','email','max:255'],
        ]);
        try {
            $generated = $this->officialGenerator->generate($company, $data);
            foreach ($generated as $type => $temporaryPath) {
                $old = $company->documents()->where('type', $type)->first();
                $path = 'company-documents/'.$company->id.'/generated-'.$type.'-'.now()->format('YmdHis').'.pdf';
                Storage::disk('local')->put($path, File::get($temporaryPath));
                $company->documents()->updateOrCreate(['type' => $type], ['original_name' => $type.'-'.$company->id.'.pdf', 'path' => $path, 'mime_type' => 'application/pdf', 'size' => File::size($temporaryPath)]);
                if ($old && $old->path !== $path) Storage::disk('local')->delete($old->path);
            }
            File::deleteDirectory(dirname(reset($generated)));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['generation' => $exception->getMessage()]);
        }
        return redirect()->route('companies.documents.index',['company_id'=>$company->id])->with('status','Minuta, Formulário de Celebração e C.I. gerados em PDF.');
    }

    public function download(Request $request, CompanyDocument $document): StreamedResponse
    {
        $this->authorizeCompany($request, $document->company);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name, [
            'Content-Type' => $document->mime_type,
        ]);
    }

    public function downloadAll(Request $request, Company $company): StreamedResponse
    {
        $this->authorizeCompany($request, $company);

        $types = array_keys(CompanyDocument::TYPES);
        $types = array_values(array_filter($types, fn (string $type) => $type !== 'minuta_termo'));
        $documents = $company->documents()->whereIn('type', $types)->get()->keyBy('type');
        abort_if($documents->isEmpty(), 404, 'Nenhum documento disponível para unificação.');

        $pdf = new Fpdi();

        foreach ($types as $type) {
            $document = $documents->get($type);

            if (! $document) {
                continue;
            }

            $path = Storage::disk('local')->path($document->path);
            abort_unless(is_file($path), 404);
            $pageCount = $pdf->setSourceFile($path);

            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template);
            }
        }

        $content = $pdf->Output('S');
        $filename = 'documentos-empresa-'.$company->id.'.pdf';

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    public function destroy(Request $request, CompanyDocument $document): RedirectResponse
    {
        $this->authorizeCompany($request, $document->company);
        $companyId = $document->company_id;
        Storage::disk('local')->delete($document->path);
        $document->delete();

        return redirect()->route('companies.documents.index', ['company_id' => $companyId])
            ->with('status', 'Documento removido com sucesso.');
    }

    private function authorizeCompany(Request $request, Company $company): void
    {
        abort_unless($company->coordinator_id === $request->user()->id, 404);
    }
}
