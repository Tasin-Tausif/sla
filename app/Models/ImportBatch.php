<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'file_path',
        'job_batch_id',
        'status',
        'total_rows',
        'processed_rows',
        'imported_rows',
        'duplicate_rows',
        'failed_rows',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'status' => ImportStatus::class,
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'imported_rows' => 'integer',
        'duplicate_rows' => 'integer',
        'failed_rows' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
