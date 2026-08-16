<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    protected $table = 'conversation_participants';

    protected $primaryKey = 'participant_id';

    public $timestamps = true;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'last_read_at',
        'is_muted',
        'is_hidden',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'is_muted' => 'boolean',
        'is_hidden' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
