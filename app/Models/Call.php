<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Call extends Model
{
    protected $primaryKey = 'call_id';

    protected $fillable = [
        
        'call_uuid',

        'conversation_id',

        'caller_id',

        'receiver_id',

        'call_type',

        'status',

        'started_at',

        'answered_at',

        'ended_at',

        'duration',
    ];

    protected $casts = [

        'started_at' => 'datetime',

        'answered_at' => 'datetime',

        'ended_at' => 'datetime',
    ];

    public function message(): HasOne
    {
        return $this->hasOne(
            Message::class,
            'call_id',
            'call_id'
        );
    }
}