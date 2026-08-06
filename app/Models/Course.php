<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    public const AREAS = [
        'Saúde',
        'Informação e Comunicação',
        'Gestão e Negócios',
        'Controle e Processos Industriais',
        'Infraestrutura',
        'Produção e Recursos Naturais',
        'Jurídica',
    ];

    public const STATES = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO',
        'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI',
        'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    protected $fillable = [
        'name',
        'area',
        'state',
        'city',
        'coordinator_id',
    ];

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(CourseCompany::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
