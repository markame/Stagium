<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        return view('companies.index', [
            'companies' => $request->user()->companies()
                ->with(['studentDocuments.student.course'])
                ->orderBy('corporate_name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->companies()->create($this->validatedData($request));

        return redirect()->route('companies.index')->with('status', 'Empresa cadastrada com sucesso.');
    }

    public function edit(Request $request, Company $company): View
    {
        $this->ensureCompanyBelongsToUser($request, $company);

        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->ensureCompanyBelongsToUser($request, $company);
        $company->update($this->validatedData($request, $company));

        return redirect()->route('companies.index')->with('status', 'Empresa atualizada com sucesso.');
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        $this->ensureCompanyBelongsToUser($request, $company);
        $company->load('documents');
        foreach ($company->documents as $document) {
            Storage::disk('local')->delete($document->path);
        }
        foreach ($company->studentDocuments as $document) {
            Storage::disk('local')->delete($document->path);
            $document->delete();
        }
        $company->delete();

        return redirect()->route('companies.index')->with('status', 'Empresa excluída com sucesso.');
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request, ?Company $company = null): array
    {
        $request->merge([
            'cnpj' => preg_replace('/\D/', '', (string) $request->input('cnpj')),
            'phone' => preg_replace('/[^\d+]/', '', (string) $request->input('phone')),
            'responsible_cpf' => preg_replace('/\D/', '', (string) $request->input('responsible_cpf')),
            'responsible_phone' => preg_replace('/[^\d+]/', '', (string) $request->input('responsible_phone')),
            'attendance_radius_meters' => $request->input('attendance_radius_meters', 100),
            'address_zip' => preg_replace('/\D/', '', (string) $request->input('address_zip')),
        ]);

        $data = $request->validate([
            'cnpj' => ['required', 'digits:14', Rule::unique('companies')->ignore($company)],
            'corporate_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'address' => ['nullable', 'string', 'max:255', 'required_without:address_street'],
            'address_street' => ['nullable', 'required_without:address', 'string', 'max:255'],
            'address_number' => ['nullable', 'required_with:address_street', 'string', 'max:30'],
            'address_neighborhood' => ['nullable', 'required_with:address_street', 'string', 'max:255'],
            'address_zip' => ['nullable', 'required_with:address_street', 'digits:8'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'responsible_name' => ['required', 'string', 'max:255'],
            'responsible_cpf' => ['required', 'digits:11'],
            'responsible_rg' => ['required', 'string', 'max:30'],
            'responsible_address' => ['required', 'string', 'max:255'],
            'responsible_phone' => ['required', 'string', 'min:10', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'attendance_radius_meters' => ['required', 'integer', 'min:20', 'max:5000'],
        ], [
            'cnpj.digits' => 'O CNPJ deve conter 14 dígitos.',
            'cnpj.unique' => 'Este CNPJ já está cadastrado.',
            'responsible_cpf.digits' => 'O CPF do responsável deve conter 11 dígitos.',
            'address_zip.digits' => 'O CEP deve conter 8 dígitos.',
        ]);

        if (filled($data['address_street'] ?? null)) {
            $data['address'] = $this->formatAddress($data);
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function formatAddress(array $data): string
    {
        return implode(', ', array_filter([
            trim($data['address_street'].', '.$data['address_number']),
            $data['address_neighborhood'],
            $data['address_complement'] ?? null,
            'CEP '.preg_replace('/(\d{5})(\d{3})/', '$1-$2', $data['address_zip']),
        ], fn ($part) => filled($part)));
    }

    private function ensureCompanyBelongsToUser(Request $request, Company $company): void
    {
        abort_unless($company->coordinator_id === $request->user()->id, 404);
    }
}
