<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Hash;

class Student extends Model
{
    protected static function booted(): void
    {
        static::created(function (Student $student): void {
            if (filled($student->cpf) && ! $student->userAccount()->exists()) {
                $student->userAccount()->create([
                    'name' => $student->name,
                    'username' => preg_replace('/\D/', '', $student->cpf),
                    'email' => null,
                    'password' => Hash::make(preg_replace('/\D/', '', $student->cpf)),
                    'role' => 'student',
                ]);
            }
        });
        static::updated(function (Student $student): void {
            if (! $student->userAccount || ! filled($student->cpf)) return;
            $values = ['name' => $student->name];
            if ($student->wasChanged('cpf')) {
                $cpf = preg_replace('/\D/', '', $student->cpf);
                $values += ['username' => $cpf, 'password' => Hash::make($cpf)];
            }
            $student->userAccount->update($values);
        });
    }
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

    public function userAccount(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(StudentTimeLog::class);
    }
}
