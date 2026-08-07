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
            throw $exception instanceof RuntimeException ? $exception : new RuntimeException('Não foi possível gerar os documentos da empresa.', previous: $exception);
        }
    }

    private function minuta(Company $company, array $data, string $directory): string
    {
        $pdf = $this->base('minuta-convenio-2026-base.pdf');
        for ($page = 1; $page <= 6; $page++) {
            $this->addTemplatePage($pdf, $page);
            if ($page === 1) {
                $this->replace($pdf, 19, 38, 72, 8, 'CONVÊNIO Nº '.$data['agreement_number'].'/2026 - IEMA', 'L', 10, 'B');
                $heading = 'CONVÊNIO QUE ENTRE SI CELEBRAM O INSTITUTO ESTADUAL DE EDUCAÇÃO, CIÊNCIA E TECNOLOGIA DO MARANHÃO - IEMA E A EMPRESA '.mb_strtoupper($company->corporate_name).' PARA A CONCESSÃO DE ESTÁGIO OBRIGATÓRIO E NÃO OBRIGATÓRIO NOS TERMOS DA LEI Nº. 11.788/2008, AOS ESTUDANTES DOS CURSOS TÉCNICOS DESTE INSTITUTO.';
                $pdf->SetFillColor(255,255,255); $pdf->Rect(93,67,98,50,'F'); $pdf->SetFont('Arial','B',9.5); $pdf->SetXY(94,68); $pdf->MultiCell(96,5,$this->encode($heading),0,'L');
                // The source paragraph contains fixed company placeholders starting near
                // the bottom of the heading. Replace the whole paragraph block so none
                // of the original text remains visible underneath the generated copy.
                $pdf->SetFillColor(255, 255, 255); $pdf->Rect(18.5, 106, 173, 77, 'F');
                $text = 'O INSTITUTO ESTADUAL DE EDUCAÇÃO, CIÊNCIA E TECNOLOGIA DO MARANHÃO - IEMA, pessoa jurídica de direito público, doravante denominada CONVENENTE, inscrita no CNPJ (MF) sob o nº. 05.849.024/0001-33, com sede na Rua Primeiro de Maio, n° 80, Bairro Anil, CEP: 65046-280, São Luís - MA, neste ato representada por sua Diretora Geral, MIRLA MARIA SANTANA OLIVEIRA, brasileira, Servidora Pública com a Matrícula nº. 810665-11, residente e domiciliada em São Luís/MA e a '.$company->corporate_name.', pessoa jurídica de direito privado, doravante denominada CONCEDENTE, inscrita no CNPJ nº '.$this->cnpj($company->cnpj).', com sede no endereço '.$company->formattedAddress().', neste ato representada por '.$company->responsible_name.', brasileiro(a), CPF nº. '.$this->cpf($company->responsible_cpf).', residente e domiciliado(a) em '.$company->responsible_address.', resolvem firmar o presente CONVÊNIO, fundamentado no art. 8º da Lei nº 11.788, de 25 de setembro de 2008, mediante as cláusulas e condições que se seguem.';
                $this->paragraph($pdf, 20, 108, 170, 73, $text, 8);
            } elseif ($page === 5) {
                $date = Carbon::parse($data['document_date'])->locale('pt_BR');
                $this->replace($pdf, 105, 191, 86, 10, 'São Luís (MA), '.$date->translatedFormat('d').' de '.$date->translatedFormat('F').' de '.$date->format('Y').'.', 'C', 10);
                $this->replace($pdf, 350/2.834, 704/2.834, 190/2.834, 40/2.834, $company->responsible_name."\n".$company->corporate_name, 'C', 9);
            }
        }
        return $this->output($pdf, $directory.'/minuta-convenio.pdf');
    }

    private function formulario(Company $company, array $data, string $directory): string
    {
        $pdf = $this->base('formulario-celebracao-2026-base.pdf'); $this->addTemplatePage($pdf, 1);
        $fields = [
            [22,52,166,$data['iema_unit']], [22,62,166,$company->corporate_name], [22,72,72,$this->cnpj($company->cnpj)], [96,72,92,$this->cpf($company->responsible_cpf)],
            [22,82,72,$company->responsible_rg], [96,82,92,$data['issuing_authority']], [22,92,166,$data['business_area']], [22,100,166,$company->formattedAddress()],
            [22,109,72,$data['company_city']], [96,109,20,$data['company_state']], [120,109,68,$data['company_zip']], [22,118,72,$company->phone], [96,118,92,$data['company_email']],
            [22,145,166,$data['shipping_address']], [22,154,88,$data['shipping_city']], [116,154,28,$data['shipping_state']], [148,154,40,$data['shipping_zip']],
            [22,172,166,$data['delivery_responsible']], [22,181,82,$data['delivery_phone']], [108,181,80,$data['delivery_email']],
        ];
        foreach ($fields as [$x,$y,$w,$value]) $this->write($pdf,$x,$y,$w,7,$value,8.5);
        $date = Carbon::parse($data['document_date'])->locale('pt_BR');
        $this->replace($pdf, 54, 218, 126, 8, $data['company_city'].', '.$date->translatedFormat('d').' de '.$date->translatedFormat('F').' de '.$date->format('Y').'.', 'C', 9);
        return $this->output($pdf, $directory.'/formulario-celebracao.pdf');
    }

    private function ci(Company $company, array $data, string $directory): string
    {
        $pdf = $this->base('ci-solicitacao-convenio-2026-base.pdf'); $this->addTemplatePage($pdf, 1);
        $date = Carbon::parse($data['document_date'])->locale('pt_BR');
        $this->replace($pdf, 24, 46, 72, 11, 'C.I. Nº '.$data['ci_number'].'/2026 '.$data['iema_code'], 'C', 10, 'B');
        $this->replace($pdf, 19, 64, 78, 9, 'São Luís, '.$date->translatedFormat('d').' de '.$date->translatedFormat('F').' de '.$date->format('Y'), 'L', 10);
        $body = 'Segue termo de convênio para estágio de n° '.$data['agreement_number'].'/2026 com a EMPRESA '.mb_strtoupper($company->corporate_name).', para coletar a assinatura de Vossa Senhoria. Informamos que serão concedidas '.$data['vacancies'].' vaga(s) de estágio obrigatório. Segue também documentação conforme Checklist. Antecipadamente, grato pela atenção e sensibilidade que nos foi dispensada, subscrevemo-nos com estima e apreço.';
        $pdf->SetFillColor(255,255,255); $pdf->Rect(19,146,172,45,'F'); $this->paragraph($pdf,20,148,170,41,$body,10);
        $this->replace($pdf, 95, 216, 48, 9, $data['manager_name'], 'C', 10, 'B');
        return $this->output($pdf, $directory.'/ci-solicitacao-convenio.pdf');
    }

    private function base(string $name): Fpdi
    {
        $path = resource_path('templates/companies/'.$name);
        throw_unless(File::exists($path), RuntimeException::class, 'Uma das bases oficiais não foi encontrada.');
        $pdf = new Fpdi('P','mm','A4'); $pdf->SetAutoPageBreak(false); $pdf->setSourceFile($path); return $pdf;
    }
    private function addTemplatePage(Fpdi $pdf, int $page): void { $t=$pdf->importPage($page); $s=$pdf->getTemplateSize($t); $pdf->AddPage($s['orientation'],[$s['width'],$s['height']]); $pdf->useTemplate($t); }
    private function replace(Fpdi $pdf,float $x,float $y,float $w,float $h,string $text,string $align='L',float $size=9,string $style=''): void { $pdf->SetFillColor(255,255,255);$pdf->Rect($x,$y,$w,$h,'F');$this->write($pdf,$x,$y,$w,$h,$text,$size,$align,$style); }
    private function write(Fpdi $pdf,float $x,float $y,float $w,float $h,string $text,float $size=9,string $align='L',string $style=''): void { $text=$this->encode($text);for($s=$size;$s>=6;$s-=.5){$pdf->SetFont('Arial',$style,$s);if($pdf->GetStringWidth(str_replace("\n",' ',$text))<=$w-1){$pdf->SetTextColor(0,0,0);$pdf->SetXY($x+.5,$y);$pdf->MultiCell($w-1,$h/ max(1,substr_count($text,"\n")+1),$text,0,$align);return;}}throw new RuntimeException('Um dado é muito longo para o espaço disponível.'); }
    private function paragraph(Fpdi $pdf,float $x,float $y,float $w,float $h,string $text,float $size): void { $pdf->SetFont('Arial','',$size);$pdf->SetTextColor(0,0,0);$pdf->SetXY($x,$y);$pdf->MultiCell($w,5,$this->encode($text),0,'J'); }
    private function output(Fpdi $pdf,string $path): string { $pdf->Output('F',$path);throw_unless(File::exists($path)&&File::size($path)>0,RuntimeException::class,'Um PDF não foi criado.');return $path; }
    private function encode(string $v): string { return iconv('UTF-8','Windows-1252//TRANSLIT',trim($v))?:trim($v); }
    private function cpf(string $v): string { $d=preg_replace('/\D/','',$v);return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/','$1.$2.$3-$4',$d)?:$v; }
    private function cnpj(string $v): string { $d=preg_replace('/\D/','',$v);return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/','$1.$2.$3/$4-$5',$d)?:$v; }
}
