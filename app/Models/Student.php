<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'coordinator_id',
        'course_id',
        'name',
        'address',
        'phone',
        'sms_phone',
        'phone_2',
        'phone_3',
        'other_phones',
        'cpf',
        'rg',
        'parentage',
        'birth_date',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }
}
