<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    public const FORWARDING_TERM = 'forwarding_term';
    public const COMMITMENT_TERM = 'commitment_term';

    protected $fillable = ['student_id', 'company_id', 'type', 'original_name', 'path', 'generation_data'];

    protected function casts(): array
    {
        return ['generation_data' => 'array'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
