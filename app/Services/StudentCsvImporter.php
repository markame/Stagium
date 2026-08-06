<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class StudentCsvImporter
{
    public function __construct(private readonly XlsxRowReader $xlsxReader) {}

    private const REQUIRED_HEADERS = [
        'nome', 'cpf', 'rg', 'data_de_nascimento', 'celular_para_sms',
        'telefone_1', 'telefone_2', 'telefone_3', 'outros_telefones',
        'endereco', 'curso', 'responsavel',
    ];

    public function import(User $user, UploadedFile $file): int
    {
        if (strtolower($file->getClientOriginalExtension()) === 'xlsx') {
            $rawRows = $this->xlsxReader->read($file->getRealPath());
            throw_if($rawRows === [], RuntimeException::class, 'A planilha está vazia.');
            $headers = array_map(fn ($header) => $this->normalizeHeader($header), array_shift($rawRows));
        } else {
            $content = file_get_contents($file->getRealPath());
            throw_if($content === false || trim($content) === '', RuntimeException::class, 'O arquivo está vazio.');

            if (! mb_check_encoding($content, 'UTF-8')) {
                $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
            }

            $lines = preg_split('/\R/u', trim($content));
            $delimiter = $this->detectDelimiter($lines[0]);
            $headers = array_map(fn ($header) => $this->normalizeHeader($header), str_getcsv($lines[0], $delimiter));
            $rawRows = array_map(fn ($line) => str_getcsv($line, $delimiter), array_slice($lines, 1));
        }
        $missing = array_diff(self::REQUIRED_HEADERS, $headers);

        if ($missing !== []) {
            throw new RuntimeException('Cabeçalho inválido. Coluna(s) ausente(s): '.implode(', ', $missing).'.');
        }

        $courses = $user->courses()->get()->keyBy(fn ($course) => $this->normalizeText($course->name));
        $rows = [];
        $errors = [];

        foreach ($rawRows as $offset => $values) {
            if (collect($values)->every(fn ($value) => trim((string) $value) === '')) {
                continue;
            }

            // Some Excel imports keep a delimited row entirely in column A.
            if (count($values) === 1 && count($headers) > 1) {
                $inlineDelimiter = $this->delimiterInValue((string) $values[0]);
                if ($inlineDelimiter !== null) {
                    $values = str_getcsv((string) $values[0], $inlineDelimiter);
                }
            }

            $values = array_pad($values, count($headers), null);
            $row = array_combine($headers, array_slice($values, 0, count($headers)));
            $lineNumber = $offset + 2;
            $course = $courses->get($this->normalizeText((string) $row['curso']));
            $birthDate = $this->parseDate((string) $row['data_de_nascimento']);
            $data = [
                'name' => trim((string) $row['nome']),
                'cpf' => $this->digits($row['cpf']),
                'rg' => trim((string) $row['rg']),
                'birth_date' => $birthDate,
                'sms_phone' => $this->phone($row['celular_para_sms']),
                'phone' => $this->phone($row['telefone_1']),
                'phone_2' => $this->phone($row['telefone_2']),
                'phone_3' => $this->phone($row['telefone_3']),
                'other_phones' => trim((string) $row['outros_telefones']) ?: null,
                'address' => trim((string) $row['endereco']),
                'course_id' => $course?->id,
                'parentage' => trim((string) $row['responsavel']),
            ];

            if ($data['name'] !== '' && $data['cpf'] !== '' && (
                $user->students()->where('name', $data['name'])->exists()
                || $user->students()->where('cpf', $data['cpf'])->exists()
                || \App\Models\Student::where('cpf', $data['cpf'])->exists()
                || collect($rows)->contains(fn ($imported) => $imported['cpf'] === $data['cpf'] || $this->normalizeText($imported['name']) === $this->normalizeText($data['name']))
            )) {
                continue;
            }

            if (trim((string) $row['curso']) !== '' && ! $course) {
                $errors[] = "Linha {$lineNumber}: o curso informado não pertence à sua conta ou não foi encontrado.";
                continue;
            }

            if (! $data['phone']) {
                $data['phone'] = $data['sms_phone'] ?: $data['phone_2'] ?: $data['phone_3'];
            }

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'cpf' => ['required', 'digits:11', Rule::unique('students', 'cpf')],
                'rg' => ['nullable', 'string', 'max:30'],
                'birth_date' => ['nullable', 'date', 'before:today'],
                'phone' => ['nullable', 'string', 'min:10', 'max:20'],
                'sms_phone' => ['nullable', 'string', 'max:20'],
                'phone_2' => ['nullable', 'string', 'max:20'],
                'phone_3' => ['nullable', 'string', 'max:20'],
                'other_phones' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:255'],
                'course_id' => ['nullable', 'integer'],
                'parentage' => ['nullable', 'string', 'max:255'],
            ], [
                'required' => 'O campo :attribute é obrigatório.',
                'cpf.digits' => 'O CPF deve conter 11 dígitos.',
                'cpf.unique' => 'O CPF já está cadastrado.',
                'birth_date.date' => 'A data de nascimento é inválida.',
                'birth_date.before' => 'A data de nascimento deve ser anterior a hoje.',
                'phone.min' => 'O telefone deve conter ao menos 10 dígitos.',
                'max' => 'O campo :attribute excedeu o tamanho permitido.',
            ], [
                'name' => 'nome',
                'cpf' => 'CPF',
                'rg' => 'RG',
                'birth_date' => 'data de nascimento',
                'phone' => 'telefone 1 ou celular para SMS',
                'address' => 'endereço',
                'course_id' => 'curso',
                'parentage' => 'responsável',
            ]);

            if ($validator->fails()) {
                $errors[] = "Linha {$lineNumber}: ".implode(' ', $validator->errors()->all());
                continue;
            }

            $rows[] = $data;
        }

        if ($errors !== []) {
            throw new RuntimeException(implode(' ', array_slice($errors, 0, 10)));
        }

        throw_if($rows === [], RuntimeException::class, 'O arquivo não possui alunos para importar.');

        DB::transaction(function () use ($user, $rows): void {
            foreach ($rows as $row) {
                $user->students()->create($row);
            }
        });

        return count($rows);
    }

    private function detectDelimiter(string $header): string
    {
        $counts = ["\t" => substr_count($header, "\t"), ';' => substr_count($header, ';'), ',' => substr_count($header, ',')];
        arsort($counts);
        $delimiter = array_key_first($counts);
        throw_if($counts[$delimiter] === 0, RuntimeException::class, 'Não foi possível identificar o separador do arquivo.');

        return $delimiter;
    }

    private function delimiterInValue(string $value): ?string
    {
        $counts = ["\t" => substr_count($value, "\t"), ';' => substr_count($value, ';'), ',' => substr_count($value, ',')];
        arsort($counts);
        $delimiter = array_key_first($counts);

        return $counts[$delimiter] > 0 ? $delimiter : null;
    }

    private function normalizeHeader(?string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(Str::ascii(ltrim((string) $value, "\xEF\xBB\xBF")))), '_');
    }

    private function normalizeText(string $value): string
    {
        return strtolower(trim(Str::ascii($value)));
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }

    private function phone(mixed $value): ?string
    {
        $phone = preg_replace('/[^\d+]/', '', (string) $value);

        return $phone !== '' ? $phone : null;
    }

    private function parseDate(string $value): ?string
    {
        if (is_numeric(trim($value)) && (float) $value > 1) {
            return Carbon::create(1899, 12, 30)->addDays((int) floor((float) $value))->format('Y-m-d');
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim($value));
                if ($date && $date->format($format) === trim($value)) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                // Try the next supported format.
            }
        }

        return null;
    }
}
