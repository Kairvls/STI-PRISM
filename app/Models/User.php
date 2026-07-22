<?php

namespace App\Models;

use App\Models\Role;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users_table';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = [

        'user_role_id',
        'user_employee_id',
        'user_username',
        'user_full_name',
        'user_email_address',
        'user_contact_number',
        'user_password',

    ];

    protected $hidden = [

        'user_password',

    ];

    /**
     * CUSTOM PASSWORD COLUMN
     */
    public function getAuthPassword()
    {
        return $this->user_password;
    }

    /**
     * ROLE RELATIONSHIP
     */
    public function role()
    {
        return $this->belongsTo(
            Role::class,
            'user_role_id',
            'role_id'
        );
    }

    /**
     * MESSAGING RELATIONSHIPS
     */
    public function conversationParticipants()
    {
        return $this->hasMany(ConversationParticipant::class, 'user_id', 'user_id');
    }

    public function conversations()
    {
        return $this->belongsToMany(
            Conversation::class,
            'conversation_participants',
            'user_id',
            'conversation_id'
        )->withPivot('last_read_at')
         ->withTimestamps();
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id', 'user_id');
    }
}
