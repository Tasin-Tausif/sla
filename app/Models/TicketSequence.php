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

    protected $casts = [
        'year' => 'integer',
        'last_number' => 'integer',
    ];
}
