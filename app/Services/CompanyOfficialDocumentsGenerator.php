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
        $pdf = $this->base('papel-timbrado-base.pdf');

        for ($page = 1; $page <= 6; $page++) {
            if ($page === 1 || $page === 5) {
                $pdf->setSourceFile(resource_path('templates/companies/papel-timbrado-base.pdf'));
                $this->addTemplatePage($pdf, 1);
            } else {
                $pdf->setSourceFile(resource_path('templates/companies/minuta-convenio-2026-base.pdf'));
                $this->addTemplatePage($pdf, $page);
            }

            if ($page === 1) {
                $this->line($pdf, 19, 38, 73, 7, 'CONVÊNIO Nº '.$data['agreement_number'].'/2026 - IEMA', 12, 'B');

                $heading = 'CONVÊNIO QUE ENTRE SI CELEBRAM O INSTITUTO ESTADUAL DE EDUCAÇÃO, CIÊNCIA E TECNOLOGIA DO MARANHÃO – IEMA E A EMPRESA '.mb_strtoupper($company->corporate_name).' PARA A CONCESSÃO DE ESTÁGIO OBRIGATÓRIO E NÃO OBRIGATÓRIO NOS TERMOS DA LEI Nº 11.788/2008, AOS ESTUDANTES DOS CURSOS TÉCNICOS DESTE INSTITUTO.';
                $this->block($pdf, 94, 57, 96, 47, $heading, 10, 4.6, 'B', 'L');

                $opening = 'O INSTITUTO ESTADUAL DE EDUCAÇÃO, CIÊNCIA E TECNOLOGIA DO MARANHÃO – IEMA, pessoa jurídica de direito público, doravante denominada CONVENENTE, inscrita no CNPJ (MF) sob o nº 05.849.024/0001-33, com sede na Rua Primeiro de Maio, nº 80, Bairro Anil, CEP 65046-280, São Luís – MA, neste ato representada por sua Diretora Geral, MIRLA MARIA SANTANA OLIVEIRA, brasileira, Servidora Pública com a Matrícula nº 810665-11, residente e domiciliada em São Luís/MA, e a '.$company->corporate_name.', pessoa jurídica de direito privado, doravante denominada CONCEDENTE, inscrita no CNPJ nº '.$this->cnpj($company->cnpj).', com sede em '.$company->formattedAddress().', neste ato representada por '.$company->responsible_name.', CPF nº '.$this->cpf($company->responsible_cpf).', residente e domiciliado(a) em '.$company->responsible_address.', resolvem firmar o presente CONVÊNIO, fundamentado no art. 8º da Lei nº 11.788, de 25 de setembro de 2008, mediante as cláusulas e condições seguintes.';
                $this->block($pdf, 19, 106, 172, 67, $opening, 10.5, 5.1, '', 'J');
                $clause = "CLÁUSULA PRIMEIRA - DO OBJETO\nO presente CONVÊNIO tem por finalidade a concessão de estágio supervisionado obrigatório e não obrigatório aos alunos regularmente matriculados e com frequência efetiva em curso(s) técnico(s) ofertado(s) pelo CONVENENTE.\nParágrafo Primeiro: O estágio visa ao aprendizado de competências próprias da atividade profissional e à contextualização dos conteúdos curriculares, na perspectiva da preparação do ESTAGIÁRIO para a vida cidadã e para o mundo do trabalho.\nParágrafo Segundo: O estágio deve ser planejado, executado, acompanhado e avaliado conforme a legislação pertinente e o Projeto Pedagógico do Curso.";
                $this->block($pdf, 19, 179, 172, 95, $clause, 11, 5.6, '', 'J');
            }

            if ($page === 5) {
                $legal = "CLÁUSULA SÉTIMA - DA LEI GERAL DE PROTEÇÃO DE DADOS\nAs partes se comprometem a proteger os direitos fundamentais de liberdade e de privacidade e o livre desenvolvimento da personalidade da pessoa natural, relativos ao tratamento de dados pessoais, inclusive nos meios digitais, nos termos da Lei Geral de Proteção de Dados - LGPD (Lei nº 13.709/2018).\nParágrafo Único: O tratamento de dados pessoais dar-se-á de acordo com as bases legais previstas nos artigos 7º, 11 e 14 da Lei nº 13.709/2018, para propósitos legítimos, específicos, explícitos e informados ao titular.\nCLÁUSULA OITAVA - DOS CASOS OMISSOS\nOs casos omissos serão resolvidos de comum acordo pelos partícipes.\nCLÁUSULA NONA - DO FORO\nO foro competente para resolver eventuais questões decorrentes do presente CONVÊNIO que não possam ser solucionadas administrativamente é o da Comarca de São Luís/MA, com eliminação de qualquer outro, por mais privilegiado que seja.\nE, por estarem de pleno acordo com as condições ora estipuladas, firmam o presente instrumento em 02 (duas) vias de igual teor, na presença de testemunhas que também o subscrevem, para que produza seus efeitos legais e jurídicos.";
                $this->block($pdf, 19, 35, 172, 148, $legal, 11, 5.7, '', 'J');
                $date = Carbon::parse($data['document_date'])->locale('pt_BR');
                $dateText = 'São Luís (MA), '.$date->translatedFormat('d').' de '.$date->translatedFormat('F').' de '.$date->format('Y').'.';
                $this->line($pdf, 105, 190, 86, 7, $dateText, 11, '', 'R');

                $this->line($pdf, 30, 211, 65, 7, 'Pela CONVENENTE:', 11, 'B', 'C');
                $this->line($pdf, 116, 211, 75, 7, 'Pela CONCEDENTE:', 11, 'B', 'C');
                $pdf->Line(27, 244, 96, 244);
                $pdf->Line(114, 244, 191, 244);
                $this->block($pdf, 27, 246, 69, 23, "Mirla Maria Santana Oliveira\nDiretora Geral do Instituto de Educação, Ciência e Tecnologia do Maranhão - IEMA", 10, 4.6, '', 'C');

                $this->block($pdf, 114, 246, 77, 23, $company->responsible_name."\n".$company->corporate_name, 10, 4.6, '', 'C');
            }
        }

        return $this->output($pdf, $directory.'/minuta-convenio.pdf');
    }

    private function formulario(Company $company, array $data, string $directory): string
    {
        $pdf = $this->base('papel-timbrado-base.pdf');
        $this->addTemplatePage($pdf, 1);

        $this->drawForm($pdf);

        $fields = [
            [22, 51, 166, 6, $data['iema_unit']],
            [22, 65, 166, 5, $company->corporate_name],
            [22, 75, 81, 5, $this->cnpj($company->cnpj)],
            [107, 75, 81, 5, $this->cpf($company->responsible_cpf)],
            [22, 85, 81, 5, $company->responsible_rg],
            [107, 85, 81, 5, $data['issuing_authority']],
            [22, 94, 166, 5, $data['business_area']],
            [22, 103, 166, 5, $company->formattedAddress()],
            [22, 112, 70, 5, $data['company_city']],
            [96, 112, 20, 5, $data['company_state']],
            [120, 112, 68, 5, $data['company_zip']],
            [22, 121, 70, 5, $company->phone],
            [96, 121, 92, 5, $data['company_email']],
            [22, 150, 166, 5, $data['shipping_address']],
            [22, 159, 90, 5, $data['shipping_city']],
            [116, 159, 30, 5, $data['shipping_state']],
            [150, 159, 38, 5, $data['shipping_zip']],
            [22, 170, 166, 5, $data['delivery_responsible']],
            [22, 179, 82, 5, $data['delivery_phone']],
            [108, 179, 80, 5, $data['delivery_email']],
        ];

        foreach ($fields as [$x, $y, $width, $height, $value]) {
            $this->field($pdf, $x, $y, $width, $height, (string) $value);
        }

        $date = Carbon::parse($data['document_date'])->locale('pt_BR');
        $dateText = $data['company_city'].', '.$date->translatedFormat('d').' de '.$date->translatedFormat('F').' de '.$date->format('Y').'.';
        $this->line($pdf, 54, 218, 126, 7, $dateText, 10, '', 'C');

        return $this->output($pdf, $directory.'/formulario-celebracao.pdf');
    }

    private function ci(Company $company, array $data, string $directory): string
    {
        $pdf = $this->base('papel-timbrado-base.pdf');
        $this->addTemplatePage($pdf, 1);
        $date = Carbon::parse($data['document_date'])->locale('pt_BR');

        $this->line($pdf, 19, 47, 82, 7, 'C.I. Nº '.$data['ci_number'].'/2026 '.$data['iema_code'], 12, 'B');
        $this->line($pdf, 19, 65, 82, 7, 'São Luís, '.$date->translatedFormat('d').' de '.$date->translatedFormat('F').' de '.$date->format('Y'), 12);
        $this->line($pdf, 20, 107, 170, 7, 'Prezada Sra. MIRLA MARIA SANTANA OLIVEIRA', 12, 'B');
        $this->line($pdf, 20, 131, 170, 7, 'ASSUNTO: SOLICITAÇÃO DE ABERTURA DE CONVÊNIO PARA ESTÁGIO', 12, 'B');

        $body = 'Segue termo de convênio para estágio de nº '.$data['agreement_number'].'/2026 com a EMPRESA '.mb_strtoupper($company->corporate_name).', para coletar a assinatura de Vossa Senhoria. Informamos que serão concedidas '.$data['vacancies'].' vaga(s) de estágio obrigatório. Segue também documentação conforme Checklist. Antecipadamente, agradecemos a atenção e a sensibilidade que nos foram dispensadas e subscrevemo-nos com estima e apreço.';
        $this->block($pdf, 20, 145, 170, 62, $body, 12, 5.6, '', 'J');
        $this->line($pdf, 75, 216, 60, 7, $data['manager_name'], 12, 'B', 'C');
        $this->line($pdf, 75, 236, 60, 7, 'Gestor(a) Geral - IP', 11, 'B', 'C');

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

    private function drawForm(Fpdi $pdf): void
    {
        $this->line($pdf, 20, 36, 170, 8, 'FORMULÁRIO PARA CELEBRAÇÃO DE CONVÊNIO DE ESTÁGIO', 12, 'B', 'C');
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(.2);

        foreach ([[20,46,170,14],[20,60,170,10],[20,70,85,10],[105,70,85,10],[20,80,85,10],[105,80,85,10],[20,90,170,9],[20,99,170,9],[20,108,74,9],[94,108,24,9],[118,108,72,9],[20,117,74,9],[94,117,96,9]] as $cell) {
            $pdf->Rect(...$cell);
        }
        foreach ([[20,137,170,9],[20,146,170,9],[20,155,94,9],[114,155,34,9],[148,155,42,9],[20,166,170,9],[20,175,86,9],[106,175,84,9]] as $cell) {
            $pdf->Rect(...$cell);
        }
        $pdf->Rect(20, 193, 170, 61);

        $labels = [
            [22,46,'IEMA PLENO:'],[22,60,'NOME DO PARCEIRO:'],[22,70,'CNPJ:'],[107,70,'CPF:'],
            [22,80,'RG:'],[107,80,'ÓRGÃO EXPEDIDOR:'],[22,90,'ÁREA DE ATUAÇÃO:'],[22,99,'ENDEREÇO:'],
            [22,108,'CIDADE:'],[96,108,'UF:'],[120,108,'CEP:'],[22,117,'TELEFONE:'],[96,117,'E-MAIL:'],
            [22,137,'ENDEREÇO PARA ENVIO DO CONVÊNIO (caso seja diferente do acima)'],[22,146,'LOGRADOURO'],
            [22,155,'CIDADE:'],[116,155,'UF:'],[150,155,'CEP:'],[22,166,'RESPONSÁVEL PELA ENTREGA DOS DOCUMENTOS:'],
            [22,175,'TELEFONE (obrigatório):'],[108,175,'E-MAIL (obrigatório):'],
        ];
        foreach ($labels as [$x, $y, $label]) {
            $this->line($pdf, $x, $y, 165, 4.5, $label, 8.5, 'B');
        }

        $declaration = 'Declaro possuir interesse na celebração do convênio de estágio com o Instituto de Educação, Ciência e Tecnologia do Maranhão - IEMA e que as informações prestadas acima, assim como os documentos anexos, são verdadeiras e podem ser utilizadas para tanto.';
        $this->block($pdf, 22, 199, 166, 28, $declaration, 9, 4.6, 'I', 'J');
        $this->line($pdf, 28, 231, 45, 6, 'LOCAL E DATA', 9, '', 'C');
        $pdf->Line(67, 244, 143, 244);
        $this->line($pdf, 65, 246, 80, 6, 'ASSINATURA DO CONCEDENTE', 9, '', 'C');
    }

    private function line(Fpdi $pdf, float $x, float $y, float $width, float $height, string $text, float $size, string $style = '', string $align = 'L'): void
    {
        $pdf->SetFont('Arial', $style, $size);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, $y);
        $pdf->Cell($width, $height, $this->encode($text), 0, 0, $align);
    }

    private function block(Fpdi $pdf, float $x, float $y, float $width, float $height, string $text, float $size, float $lineHeight, string $style = '', string $align = 'L'): void
    {
        $lines = $this->wrappedLines($pdf, $text, $width, $size, $style);
        throw_if(count($lines) * $lineHeight > $height, RuntimeException::class, 'Um dado é muito longo para o espaço disponível no documento oficial.');
        $pdf->SetFont('Arial', $style, $size);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, $y);
        $pdf->MultiCell($width, $lineHeight, $this->encode(implode("\n", $lines)), 0, $align);
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
        $lines = [];
        foreach (preg_split('/\R/u', trim($text)) ?: [] as $paragraph) {
            $words = preg_split('/\s+/u', trim($paragraph)) ?: [];
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
