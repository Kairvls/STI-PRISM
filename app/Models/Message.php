<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $table = 'messages';

    protected $primaryKey = 'message_id';

    public $timestamps = true;

    protected $fillable = [
        'conversation_id',
        'sender_id',

        // =============================================
        // MESSAGE THIS MESSAGE IS REPLYING TO
        // =============================================

        'reply_to_message_id',

        'message_content',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }

    // =====================================================
    // ORIGINAL MESSAGE THIS MESSAGE IS REPLYING TO
    // =====================================================

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(
            Message::class,
            'reply_to_message_id',
            'message_id'
        );
    }


    // =====================================================
    // OPTIONAL:
    // MESSAGES THAT REPLIED TO THIS MESSAGE
    // =====================================================

    public function replies()
    {
        return $this->hasMany(
            Message::class,
            'reply_to_message_id',
            'message_id'
        );
    }

    // =====================================================
    // MESSAGE REACTIONS
    // =====================================================

    public function reactions()
    {
        return $this->hasMany(
            MessageReaction::class,
            'message_id',
            'message_id'
        );
    }
}
