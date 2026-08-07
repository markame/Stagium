<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class StudentCommitmentTermGenerator
{
    public function generate(Student $student, Company $company, array $data): string
    {
        $directory = storage_path('app/private/tmp/student-commitment-terms/'.bin2hex(random_bytes(12)));
        File::ensureDirectoryExists($directory);
        $output = $directory.'/termo-compromisso.pdf';
        $template = resource_path('templates/students/termo-compromisso-2026-base.pdf');

        try {
            throw_unless(File::exists($template), RuntimeException::class, 'A base do Termo de Compromisso não foi encontrada.');
            $pdf = new Fpdi('P', 'mm', 'A4');
            $pdf->SetAutoPageBreak(false);
            $pages = $pdf->setSourceFile($template);
            throw_unless($pages === 4, RuntimeException::class, 'A base do Termo de Compromisso está incompleta.');

            for ($page = 1; $page <= $pages; $page++) {
                $imported = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($imported);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($imported);

                if ($page === 1) {
                    $this->fillIdentificationPage($pdf, $student, $company, $data);
                } elseif ($page === 3) {
                    $this->fillDurationClause($pdf, $data);
                } elseif ($page === 4) {
                    $this->replace($pdf, 105, 184.5, 83, 8, $data['manager_name'], 'C', 11, 'B');
                }
            }

            $pdf->Output('F', $output);
            throw_unless(File::exists($output) && File::size($output) > 0, RuntimeException::class, 'O PDF do Termo de Compromisso não foi criado.');

            return $output;
        } catch (\Throwable $exception) {
            File::deleteDirectory($directory);
            throw $exception instanceof RuntimeException ? $exception : new RuntimeException('Não foi possível gerar o Termo de Compromisso.', previous: $exception);
        }
    }

    private function fillIdentificationPage(Fpdi $pdf, Student $student, Company $company, array $data): void
    {
        $this->replace($pdf, 21.5, 136.2, 111, 6.5, $company->corporate_name);
        $this->replace($pdf, 135, 136.2, 52, 6.5, $this->formatCnpj($company->cnpj));
        $this->replace($pdf, 21.5, 149.7, 111, 6.5, $company->responsible_name);
        $this->replace($pdf, 135, 149.7, 52, 6.5, $this->formatCpf($company->responsible_cpf));
        $this->replace($pdf, 21.5, 163.2, 111, 6.5, $company->formattedAddress());
        $this->replace($pdf, 135, 163.2, 52, 6.5, $data['company_zip']);
        $this->replace($pdf, 21.5, 176.7, 111, 6.5, $data['company_neighborhood']);
        $this->replace($pdf, 135, 176.7, 22, 6.5, $data['company_city']);
        $this->replace($pdf, 159, 176.7, 28, 6.5, $data['company_state']);

        $this->replace($pdf, 21.5, 197.2, 165.5, 5.7, $student->name);
        $this->replace($pdf, 21.5, 208.1, 111, 6.3, $this->formatCpf($student->cpf));
        $this->replace($pdf, 135, 208.1, 52, 6.3, Carbon::parse($student->birth_date)->format('d/m/Y'));
        $this->replace($pdf, 21.5, 220.6, 111, 7.5, $student->address);
        $this->replace($pdf, 135, 220.6, 52, 7.5, $data['student_city']);
        $this->replace($pdf, 21.5, 233.1, 111, 7.8, $data['student_neighborhood']);
        $this->replace($pdf, 135, 233.1, 52, 7.8, $data['student_state']);
        $period = Carbon::parse($data['start_date'])->format('d/m/Y').' a '.Carbon::parse($data['end_date'])->format('d/m/Y');
        $this->replace($pdf, 21.5, 247, 165.5, 7.5, $period);
    }

    private function fillDurationClause(Fpdi $pdf, array $data): void
    {
        $this->replace($pdf, 19.5, 248.2, 25, 7, Carbon::parse($data['start_date'])->format('d/m/Y'), 'L', 10);
        $this->replace($pdf, 98, 248.2, 28, 7, Carbon::parse($data['end_date'])->format('d/m/Y'), 'L', 10);
        $this->replace($pdf, 59, 255, 18, 6, $data['daily_start_time'], 'C', 10);
        $this->replace($pdf, 82, 255, 18, 6, $data['daily_end_time'], 'C', 10);
    }

    private function replace(Fpdi $pdf, float $x, float $y, float $width, float $height, string $text, string $align = 'L', float $fontSize = 9, string $style = ''): void
    {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x + .15, $y + .1, $width - .3, $height - .2, 'F');
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', trim($text)) ?: trim($text);
        for ($size = $fontSize; $size >= 6.5; $size -= .5) {
            $pdf->SetFont('Arial', $style, $size);
            if ($pdf->GetStringWidth($encoded) <= $width - 1) {
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetXY($x + .5, $y);
                $pdf->Cell($width - 1, $height, $encoded, 0, 0, $align);
                return;
            }
        }
        throw new RuntimeException('Um dos dados é muito longo para o espaço disponível no Termo de Compromisso.');
    }

    private function formatCpf(string $cpf): string
    {
        $digits = preg_replace('/\D/', '', $cpf);
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits) ?: $cpf;
    }

    private function formatCnpj(string $cnpj): string
    {
        $digits = preg_replace('/\D/', '', $cnpj);
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digits) ?: $cnpj;
    }
}
