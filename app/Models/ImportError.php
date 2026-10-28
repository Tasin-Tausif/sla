<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportError extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['import_batch_id', 'row_number', 'raw_data', 'error_message'];

    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
            'raw_data' => 'array',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
