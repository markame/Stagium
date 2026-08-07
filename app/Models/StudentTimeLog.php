<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTimeLog extends Model
{
    protected $fillable = ['student_id', 'company_id', 'type', 'logged_at', 'device_latitude', 'device_longitude', 'distance_meters', 'ip', 'user_agent'];
    protected function casts(): array { return ['logged_at' => 'datetime']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}
