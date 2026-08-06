<?php

namespace App\Console\Commands;

use App\Models\ReceitaCompany;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use SplFileObject;

class ImportReceitaCompanies extends Command
{
    protected $signature = 'receita:import-companies {file : Caminho do CSV consolidado da Receita} {--delimiter=; : Delimitador do CSV}';

    protected $description = 'Importa empresas da base pública de CNPJ da Receita Federal para busca local por cidade e área.';

    /**
     * @var array<string, list<string>>
     */
    private array $columnAliases = [
        'cnpj' => ['cnpj', 'cnpj_basico_ordem_dv'],
        'corporate_name' => ['corporate_name', 'razao_social', 'razao_social_nome_empresarial'],
        'trade_name' => ['trade_name', 'nome_fantasia'],
        'registration_status' => ['registration_status', 'situacao_cadastral'],
        'cnae_code' => ['cnae_code', 'cnae_principal', 'cnae_fiscal_principal'],
        'cnae_description' => ['cnae_description', 'cnae_descricao', 'descricao_cnae'],
        'state' => ['state', 'uf'],
        'city' => ['city', 'municipio', 'nome_municipio'],
        'street_type' => ['street_type', 'tipo_logradouro'],
        'street' => ['street', 'logradouro'],
        'number' => ['number', 'numero'],
        'complement' => ['complement', 'complemento'],
        'district' => ['district', 'bairro'],
        'zip_code' => ['zip_code', 'cep'],
        'email' => ['email', 'correio_eletronico'],
        'phone' => ['phone', 'telefone', 'telefone_1'],
    ];

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! is_string($path) || ! is_file($path)) {
            $this->error('Arquivo CSV não encontrado.');

            return self::FAILURE;
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl((string) $this->option('delimiter'));

        $header = $this->normalizedHeader($file->fgetcsv() ?: []);
        $batch = [];
        $imported = 0;

        while (! $file->eof()) {
            $row = $file->fgetcsv();

            if (! is_array($row) || $row === [null]) {
                continue;
            }

            $record = $this->recordFromRow($header, $row);

            if ($record === null) {
                continue;
            }

            $batch[] = $record;

            if (count($batch) >= 1000) {
                $imported += $this->upsert($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $imported += $this->upsert($batch);
        }

        $this->info("Importação concluída. Registros processados: {$imported}");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string|null>  $header
     * @return array<int, string>
     */
    private function normalizedHeader(array $header): array
    {
        return collect($header)
            ->map(fn ($value): string => strtolower(trim((string) $value)))
            ->all();
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, string|null>  $row
     * @return array<string, mixed>|null
     */
    private function recordFromRow(array $header, array $row): ?array
    {
        $source = array_combine($header, array_map(fn ($value): string => trim((string) $value), $row));

        if (! is_array($source)) {
            return null;
        }

        $record = [];

        foreach ($this->columnAliases as $field => $aliases) {
            $record[$field] = $this->firstValue($source, $aliases);
        }

        $record['cnpj'] = preg_replace('/\D/', '', (string) $record['cnpj']);
        $record['state'] = strtoupper((string) $record['state']);
        $record['city'] = mb_strtoupper((string) $record['city']);

        if (strlen((string) $record['cnpj']) !== 14 || blank($record['state']) || blank($record['city'])) {
            return null;
        }

        $record['created_at'] = now();
        $record['updated_at'] = now();

        return $record;
    }

    /**
     * @param  array<string, string>  $source
     * @param  list<string>  $aliases
     */
    private function firstValue(array $source, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            $value = Arr::get($source, $alias);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $batch
     */
    private function upsert(array $batch): int
    {
        DB::table((new ReceitaCompany())->getTable())->upsert(
            $batch,
            ['cnpj'],
            [
                'corporate_name',
                'trade_name',
                'registration_status',
                'cnae_code',
                'cnae_description',
                'state',
                'city',
                'street_type',
                'street',
                'number',
                'complement',
                'district',
                'zip_code',
                'email',
                'phone',
                'updated_at',
            ],
        );

        return count($batch);
    }
}
