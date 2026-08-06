<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceitaCompany extends Model
{
    protected $fillable = [
        'cnpj',
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
    ];

    public function displayName(): string
    {
        return filled($this->trade_name) ? $this->trade_name : ($this->corporate_name ?? $this->cnpj);
    }

    public function fullAddress(): string
    {
        return collect([
            trim(collect([
                $this->street_type,
                $this->street,
                $this->number,
            ])->filter()->implode(' ')),
            $this->complement,
            $this->district,
            collect([$this->city, $this->state])->filter()->implode(' - '),
            filled($this->zip_code) ? 'CEP '.$this->zip_code : null,
            'Brasil',
        ])->filter()->implode(', ');
    }
}
