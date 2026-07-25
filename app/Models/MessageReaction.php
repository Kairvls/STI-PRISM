<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReaction extends Model
{
    protected $table = 'message_reactions';

    protected $primaryKey = 'message_reaction_id';


    // =====================================================
    // FILLABLE FIELDS
    // =====================================================

    protected $fillable = [
        'message_id',
        'user_id',
        'reaction',
    ];


    // =====================================================
    // MESSAGE
    // =====================================================

    public function message(): BelongsTo
    {
        return $this->belongsTo(
            Message::class,
            'message_id',
            'message_id'
        );
    }


    // =====================================================
    // USER WHO REACTED
    // =====================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'user_id'
        );
    }
}