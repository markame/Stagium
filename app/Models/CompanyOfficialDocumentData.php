<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyOfficialDocumentData extends Model
{
    protected $table = 'company_official_document_data';

    protected $fillable = ['company_id', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
