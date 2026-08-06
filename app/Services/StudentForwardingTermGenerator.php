<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class StudentForwardingTermGenerator
{
    public function generate(Student|iterable $students, Company $company, array $data): string
    {
        $students = $students instanceof Student ? collect([$students]) : collect($students);
        throw_if($students->isEmpty(), RuntimeException::class, 'Selecione pelo menos um aluno para gerar o termo.');
        $workDirectory = storage_path('app/private/tmp/student-terms/'.bin2hex(random_bytes(12)));
        File::ensureDirectoryExists($workDirectory);
        $outputPath = $workDirectory.'/termo-encaminhamento.pdf';
        $templatePath = resource_path('templates/students/termo-encaminhamento-2026-base.pdf');

        try {
            throw_unless(File::exists($templatePath), RuntimeException::class, 'A base do Termo de Encaminhamento não foi encontrada.');
            $date = Carbon::parse($data['start_date'])->locale('pt_BR');
            $pdf = new Fpdi('P', 'mm', 'A4');
            $pdf->SetAutoPageBreak(false);
            $pdf->setSourceFile($templatePath);
            $template = $pdf->importPage(1);
            $size = $pdf->getTemplateSize($template);
            foreach ($students->chunk(3) as $pageStudents) {
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($template);
            $pdf->SetTextColor(0, 0, 0);

            $body = 'Em decorrência do convênio celebrado entre o Instituto de Educação, Ciência e Tecnologia do Maranhão – IEMA e a '.$company->corporate_name.', informamos que o estágio iniciará nesta empresa no dia '.$date->translatedFormat('d').' de '.$date->translatedFormat('F').' do corrente ano. Estamos encaminhando para realização de estágio o(s) estudante(s):';
            $this->writeFittedParagraph($pdf, 20, 109, 170, 29, $body);

            $pdf->SetFont('Times', 'B', 12);
            foreach ($pageStudents->values() as $index => $student) {
                throw_unless($student->course, RuntimeException::class, 'Todos os alunos selecionados devem possuir um curso.');
                $y = 150.5 + ($index * 7.2);
                $this->writeFittedCell($pdf, 22, $y, 91, 7, $student->name, 'L', 'B');
                $this->writeFittedCell($pdf, 117, $y, 71, 7, $student->course->name, 'L', 'B');
            }

            $pdf->SetFont('Times', '', 12);
            $this->writeFittedCell($pdf, 55, 205.3, 100, 7, $data['manager_name'], 'C');
            $this->writeFittedCell($pdf, 38, 213.7, 134, 7, 'Gestor(a) Geral do IEMA Pleno '.$data['iema_unit'], 'C');
            $this->writeFittedCell($pdf, 31, 230.5, 155, 7, $company->responsible_name, 'L');
            $this->writeFittedCell($pdf, 20, 239.2, 168, 7, $company->responsible_name, 'L');
            $this->writeFittedCell($pdf, 20, 247.8, 168, 7, $data['responsible_role'], 'L');
            }

            $pdf->Output('F', $outputPath);
            throw_unless(File::exists($outputPath) && File::size($outputPath) > 0, RuntimeException::class, 'O arquivo PDF não foi criado.');

            return $outputPath;
        } catch (\Throwable $exception) {
            File::deleteDirectory($workDirectory);

            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException('Não foi possível gerar o PDF do aluno.', previous: $exception);
        }
    }

    private function writeFittedParagraph(Fpdi $pdf, float $x, float $y, float $width, float $height, string $text): void
    {
        $encoded = $this->encode($text);

        for ($fontSize = 12.0; $fontSize >= 9.0; $fontSize -= 0.5) {
            $pdf->SetFont('Times', '', $fontSize);
            $lineHeight = 7.2 * ($fontSize / 12);

            if ($this->lineCount($pdf, $width, $encoded) * $lineHeight <= $height) {
                $pdf->SetXY($x, $y);
                $pdf->MultiCell($width, $lineHeight, $encoded, 0, 'J');

                return;
            }
        }

        throw new RuntimeException('O nome da empresa é muito longo para o espaço disponível no template.');
    }

    private function writeFittedCell(Fpdi $pdf, float $x, float $y, float $width, float $height, string $text, string $align, string $style = ''): void
    {
        $encoded = $this->encode($text);
        for ($fontSize = 12.0; $fontSize >= 8.0; $fontSize -= 0.5) {
            $pdf->SetFont('Times', $style, $fontSize);
            if ($pdf->GetStringWidth($encoded) <= $width) {
                $pdf->SetXY($x, $y);
                $pdf->Cell($width, $height, $encoded, 0, 0, $align);

                return;
            }
        }

        throw new RuntimeException('Um dos nomes informados é muito longo para o espaço disponível no template.');
    }

    private function lineCount(Fpdi $pdf, float $width, string $text): int
    {
        $lines = 1;
        $lineWidth = 0.0;
        $spaceWidth = $pdf->GetStringWidth(' ');

        foreach (preg_split('/\s+/', trim($text)) as $word) {
            $wordWidth = $pdf->GetStringWidth($word);
            if ($lineWidth > 0 && $lineWidth + $spaceWidth + $wordWidth > $width) {
                $lines++;
                $lineWidth = $wordWidth;
            } else {
                $lineWidth += ($lineWidth > 0 ? $spaceWidth : 0) + $wordWidth;
            }
        }

        return $lines;
    }

    private function encode(string $text): string
    {
        return iconv('UTF-8', 'Windows-1252//TRANSLIT', $text) ?: $text;
    }
}
