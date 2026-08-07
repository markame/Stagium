<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'coordinator_id',
        'cnpj',
        'corporate_name',
        'trade_name',
        'phone',
        'address',
        'address_street',
        'address_number',
        'address_neighborhood',
        'address_zip',
        'address_complement',
        'responsible_name',
        'responsible_cpf',
        'responsible_rg',
        'responsible_address',
        'responsible_phone',
        'latitude',
        'longitude',
        'attendance_radius_meters',
    ];

    public function formattedAddress(): string
    {
        if (blank($this->address_street)) {
            return (string) $this->address;
        }

        return implode(', ', array_filter([
            trim($this->address_street.', '.$this->address_number),
            $this->address_neighborhood,
            $this->address_complement,
            $this->address_zip ? 'CEP '.preg_replace('/(\d{5})(\d{3})/', '$1-$2', $this->address_zip) : null,
        ], fn ($part) => filled($part)));
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CompanyDocument::class);
    }

    public function studentDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }
}
