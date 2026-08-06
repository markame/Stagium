<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDocument extends Model
{
    public const TYPES = [
        'solicitacao_convenio' => ['group' => 'Documentos necessários', 'label' => 'Solicitação de Convênio - CI'],
        'minuta_termo' => ['group' => 'Documentos necessários', 'label' => 'Minuta do Termo de Convênio'],
        'formulario_celebracao' => ['group' => 'Documentos necessários', 'label' => 'Formulário para Celebração de Convênio de Estágio'],
        'apolices_seguro' => ['group' => 'Documentos necessários', 'label' => 'Apólices de seguros contra acidentes pessoais dos estagiários'],
        'identificacao_representante' => ['group' => 'Documentos necessários', 'label' => 'Identificação do Representante Legal'],
        'documentacao_empresa' => ['group' => 'Documentos necessários', 'label' => 'Documentação da Empresa conforme a personalidade jurídica'],
        'comprovante_cnpj' => ['group' => 'Certidões de regularidade fiscal', 'label' => 'CNPJ - Comprovante de Inscrição e Situação Cadastral'],
        'cnda_estadual' => ['group' => 'Certidões de regularidade fiscal', 'label' => 'Certidão Negativa da Dívida Ativa da Fazenda Estadual - CNDA'],
        'cnd_estadual' => ['group' => 'Certidões de regularidade fiscal', 'label' => 'Certidão Negativa de Débitos da Fazenda Estadual - CND'],
        'cnd_trabalhista' => ['group' => 'Certidões de regularidade fiscal', 'label' => 'Certidão Negativa de Débitos Trabalhistas'],
        'cnd_federal' => ['group' => 'Certidões de regularidade fiscal', 'label' => 'Certidão Negativa de Débitos Relativos aos Tributos Federais e à Dívida Ativa da União'],
        'regularidade_fgts' => ['group' => 'Certidões de regularidade fiscal', 'label' => 'Certificado de Regularidade do FGTS'],
        'antecedentes_estadual' => ['group' => 'Profissionais liberais', 'label' => 'Certidão de Antecedentes Criminais Estadual'],
        'antecedentes_federal' => ['group' => 'Profissionais liberais', 'label' => 'Certidão de Antecedentes Criminais Federal'],
    ];

    protected $fillable = ['company_id', 'type', 'original_name', 'path', 'mime_type', 'size'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
