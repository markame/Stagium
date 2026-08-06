<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseCompany extends Model
{
    protected $fillable = [
        'course_id',
        'cnpj',
        'google_place_id',
        'source_hash',
        'name',
        'corporate_name',
        'trade_name',
        'type',
        'lat',
        'lng',
        'address',
        'email',
        'phone',
        'international_phone',
        'website_url',
        'maps_url',
        'cnae_code',
        'registration_status',
        'source',
        'raw_data',
        'last_scanned_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'raw_data' => 'array',
        'last_scanned_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
