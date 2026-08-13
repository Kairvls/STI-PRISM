<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $table = 'messages';

    protected $primaryKey = 'message_id';

    public $timestamps = true;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'reply_to_message_id',
        'forwarded_from_message_id',
        'message_content',
        'message_type',
        'call_id',

        // =============================================
        // MESSAGE ACTION STATES
        // =============================================

        'is_unsent',
        'unsent_at',
        'is_edited',
        'edited_at',

        // =============================================
        // DELIVERY / READ STATES
        // =============================================

        'is_read',
        'delivered_at',
        'read_at',
    ];


    // =====================================================
    // FIELD CASTS
    // =====================================================

    protected $casts = [
        'is_unsent' => 'boolean',
        'unsent_at' => 'datetime',

        'is_edited' => 'boolean',
        'edited_at' => 'datetime',

        'is_read' => 'boolean',
        'delivered_at' => 'datetime',
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

    public function attachments(): HasMany
    {
        return $this->hasMany(
            MessageAttachment::class,
            'message_id',
            'message_id'
        );
    }

    public function hiddenUsers()
    {
        return $this->hasMany(
            MessageHiddenUser::class,
            'message_id',
            'message_id'
        );
    }

    public function call()
    {
        return $this->belongsTo(
            Call::class,
            'call_id',
            'call_id'
        );
    }
}
