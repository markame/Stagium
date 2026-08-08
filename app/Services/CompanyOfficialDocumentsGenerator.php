<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class CompanyOfficialDocumentsGenerator
{
    public function generate(Company $company, array $data): array
    {
        $directory = storage_path('app/private/tmp/company-official-documents/'.bin2hex(random_bytes(12)));
        File::ensureDirectoryExists($directory);

        try {
            return [
                'minuta_termo' => $this->minuta($company, $data, $directory),
                'formulario_celebracao' => $this->formulario($company, $data, $directory),
                'solicitacao_convenio' => $this->ci($company, $data, $directory),
            ];
        } catch (\Throwable $exception) {
            File::deleteDirectory($directory);

            throw $exception instanceof RuntimeException
                ? $exception
                : new RuntimeException('Não foi possível gerar os documentos da empresa.', previous: $exception);
        }
    }

    private function minuta(Company $company, array $data, string $directory): string
    {
        $pdf = $this->base('minuta-convenio-2026-base.pdf');

        for ($page = 1; $page <= 6; $page++) {
            $this->addTemplatePage($pdf, $page);

            if ($page === 1) {
                $this->replaceLine($pdf, 19, 38, 73, 7, 'CONVÊNIO Nº '.$data['agreement_number'].'/2026 – IEMA', 12, 'B');

                $heading = 'CONVÊNIO QUE ENTRE SI CELEBRAM O INSTITUTO ESTADUAL DE EDUCAÇÃO, CIÊNCIA E TECNOLOGIA DO MARANHÃO – IEMA E A EMPRESA '.mb_strtoupper($company->corporate_name).' PARA A CONCESSÃO DE ESTÁGIO OBRIGATÓRIO E NÃO OBRIGATÓRIO NOS TERMOS DA LEI Nº 11.788/2008, AOS ESTUDANTES DOS CURSOS TÉCNICOS DESTE INSTITUTO.';
                $this->replaceBlock($pdf, 93.5, 56.5, 97.5, 48.5, $heading, 10, 4.6, 'B', 'L');

                $opening = 'O INSTITUTO ESTADUAL DE EDUCAÇÃO, CIÊNCIA E TECNOLOGIA DO MARANHÃO – IEMA, pessoa jurídica de direito público, doravante denominada CONVENENTE, inscrita no CNPJ (MF) sob o nº 05.849.024/0001-33, com sede na Rua Primeiro de Maio, nº 80, Bairro Anil, CEP 65046-280, São Luís – MA, neste ato representada por sua Diretora Geral, MIRLA MARIA SANTANA OLIVEIRA, brasileira, Servidora Pública com a Matrícula nº 810665-11, residente e domiciliada em São Luís/MA, e a '.$company->corporate_name.', pessoa jurídica de direito privado, doravante denominada CONCEDENTE, inscrita no CNPJ nº '.$this->cnpj($company->cnpj).', com sede em '.$company->formattedAddress().', neste ato representada por '.$company->responsible_name.', CPF nº '.$this->cpf($company->responsible_cpf).', residente e domiciliado(a) em '.$company->responsible_address.', resolvem firmar o presente CONVÊNIO, fundamentado no art. 8º da Lei nº 11.788, de 25 de setembro de 2008, mediante as cláusulas e condições seguintes.';
                $this->replaceBlock($pdf, 19, 106, 172, 72, $opening, 10.5, 5.1, '', 'J');
            }

            if ($page === 5) {
                $date = Carbon::parse($data['document_date'])->locale('pt_BR');
                $dateText = 'São Luís (MA), '.$date->translatedFormat('d').' de '.$date->translatedFormat('F').' de '.$date->format('Y').'.';
                $this->replaceLine($pdf, 111, 192, 80, 7, $dateText, 12, '', 'R');

                $this->replaceBlock($pdf, 124, 247, 66, 26, $company->responsible_name."\n".$company->corporate_name, 10, 5, 'B', 'C');
            }
        }

        return $this->output($pdf, $directory.'/minuta-convenio.pdf');
    }

    private function formulario(Company $company, array $data, string $directory): string
    {
        $pdf = $this->base('formulario-celebracao-2026-base.pdf');
        $this->addTemplatePage($pdf, 1);

        $fields = [
            [22, 52, 166, 6, $data['iema_unit']],
            [22, 62, 166, 6, $company->corporate_name],
            [22, 72, 72, 6, $this->cnpj($company->cnpj)],
            [96, 72, 92, 6, $this->cpf($company->responsible_cpf)],
            [22, 82, 72, 6, $company->responsible_rg],
            [96, 82, 92, 6, $data['issuing_authority']],
            [22, 91, 166, 7, $data['business_area']],
            [22, 100, 166, 7, $company->formattedAddress()],
            [22, 109, 72, 6, $data['company_city']],
            [96, 109, 20, 6, $data['company_state']],
            [120, 109, 68, 6, $data['company_zip']],
            [22, 118, 72, 6, $company->phone],
            [96, 118, 92, 6, $data['company_email']],
            [22, 145, 166, 7, $data['shipping_address']],
            [22, 154, 88, 6, $data['shipping_city']],
            [116, 154, 28, 6, $data['shipping_state']],
            [148, 154, 40, 6, $data['shipping_zip']],
            [22, 172, 166, 7, $data['delivery_responsible']],
            [22, 181, 82, 6, $data['delivery_phone']],
            [108, 181, 80, 6, $data['delivery_email']],
        ];

        foreach ($fields as [$x, $y, $width, $height, $value]) {
            $this->field($pdf, $x, $y, $width, $height, (string) $value);
        }

        $date = Carbon::parse($data['document_date'])->locale('pt_BR');
        $dateText = $data['company_city'].', '.$date->translatedFormat('d').' de '.$date->translatedFormat('F').' de '.$date->format('Y').'.';
        $this->replaceLine($pdf, 54, 218, 126, 7, $dateText, 10, '', 'C');

        return $this->output($pdf, $directory.'/formulario-celebracao.pdf');
    }

    private function ci(Company $company, array $data, string $directory): string
    {
        $pdf = $this->base('ci-solicitacao-convenio-2026-base.pdf');
        $this->addTemplatePage($pdf, 1);
        $date = Carbon::parse($data['document_date'])->locale('pt_BR');

        $this->replaceLine($pdf, 19, 47, 82, 7, 'C.I. Nº '.$data['ci_number'].'/2026 '.$data['iema_code'], 12, 'B');
        $this->replaceLine($pdf, 19, 65, 82, 7, 'São Luís, '.$date->translatedFormat('d').' de '.$date->translatedFormat('F').' de '.$date->format('Y'), 12);

        $body = 'Segue termo de convênio para estágio de nº '.$data['agreement_number'].'/2026 com a EMPRESA '.mb_strtoupper($company->corporate_name).', para coletar a assinatura de Vossa Senhoria. Informamos que serão concedidas '.$data['vacancies'].' vaga(s) de estágio obrigatório. Segue também documentação conforme Checklist. Antecipadamente, agradecemos a atenção e a sensibilidade que nos foram dispensadas e subscrevemo-nos com estima e apreço.';
        $this->replaceBlock($pdf, 19, 145, 172, 62, $body, 12, 5.6, '', 'J');
        $this->replaceLine($pdf, 95, 216, 48, 7, $data['manager_name'], 12, 'B', 'C');

        return $this->output($pdf, $directory.'/ci-solicitacao-convenio.pdf');
    }

    private function base(string $name): Fpdi
    {
        $path = resource_path('templates/companies/'.$name);
        throw_unless(File::exists($path), RuntimeException::class, 'Uma das bases oficiais não foi encontrada.');

        $pdf = new Fpdi('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->setSourceFile($path);

        return $pdf;
    }

    private function addTemplatePage(Fpdi $pdf, int $page): void
    {
        $template = $pdf->importPage($page);
        $size = $pdf->getTemplateSize($template);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($template);
    }

    private function field(Fpdi $pdf, float $x, float $y, float $width, float $height, string $text): void
    {
        $encoded = $this->encode($text);
        $size = 10.0;
        while ($size > 8.0) {
            $pdf->SetFont('Arial', '', $size);
            if ($pdf->GetStringWidth($encoded) <= $width - 1) {
                break;
            }
            $size -= .25;
        }

        throw_if($pdf->GetStringWidth($encoded) > $width - 1, RuntimeException::class, 'Um dado é muito longo para a célula correspondente no formulário.');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x + .5, $y - .5);
        $pdf->Cell($width - 1, $height, $encoded, 0, 0, 'L');
    }

    private function replaceLine(Fpdi $pdf, float $x, float $y, float $width, float $height, string $text, float $size, string $style = '', string $align = 'L'): void
    {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x, $y, $width, $height, 'F');
        $pdf->SetFont('Arial', $style, $size);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x + .5, $y + .2);
        $pdf->Cell($width - 1, $height - .4, $this->encode($text), 0, 0, $align);
    }

    private function replaceBlock(Fpdi $pdf, float $x, float $y, float $width, float $height, string $text, float $size, float $lineHeight, string $style = '', string $align = 'L'): void
    {
        $lines = $this->wrappedLines($pdf, $text, $width - 2, $size, $style);
        throw_if(count($lines) * $lineHeight > $height, RuntimeException::class, 'Um dado é muito longo para o espaço disponível no documento oficial ('.count($lines).' linhas em '.$width.' x '.$height.' mm).');

        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x, $y, $width, $height, 'F');
        $pdf->SetFont('Arial', $style, $size);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x + 1, $y + 1);
        $pdf->MultiCell($width - 2, $lineHeight, $this->encode(implode("\n", $lines)), 0, $align);
    }

    private function wrappedLines(Fpdi $pdf, string $text, float $width, float $size, string $style = '', int $maximumLines = PHP_INT_MAX): array
    {
        $pdf->SetFont('Arial', $style, $size);
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;
            if ($pdf->GetStringWidth($this->encode($candidate)) <= $width) {
                $line = $candidate;
                continue;
            }

            if ($line !== '') {
                $lines[] = $line;
            }
            $line = $word;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        if (count($lines) > $maximumLines) {
            $kept = array_slice($lines, 0, $maximumLines - 1);
            $kept[] = implode(' ', array_slice($lines, $maximumLines - 1));
            $lines = $kept;
        }

        return $lines ?: [''];
    }

    private function output(Fpdi $pdf, string $path): string
    {
        $pdf->Output('F', $path);
        throw_unless(File::exists($path) && File::size($path) > 0, RuntimeException::class, 'Um PDF não foi criado.');

        return $path;
    }

    private function encode(string $value): string
    {
        return iconv('UTF-8', 'Windows-1252//TRANSLIT', trim($value)) ?: trim($value);
    }

    private function cpf(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);

        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits) ?: $value;
    }

    private function cnpj(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);

        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digits) ?: $value;
    }
}
