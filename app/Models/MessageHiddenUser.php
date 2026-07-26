<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageHiddenUser extends Model
{
    protected $table = 'message_hidden_users';

    protected $primaryKey = 'message_hidden_user_id';

    public $timestamps = true;

    protected $fillable = [
        'message_id',
        'user_id',
    ];

    // =====================================================
    // MESSAGE THAT WAS HIDDEN
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
    // USER WHO HID THE MESSAGE
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