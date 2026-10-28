<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketSequence extends Model
{
    protected $fillable = ['year', 'last_number'];

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'year';

    protected $keyType = 'int';

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
