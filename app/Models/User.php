<?php

namespace App\Models;

use App\Models\Role;

use Illuminate\Foundation\Auth\User as Authenticatable;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

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
        'user_profile_picture',
        'user_password',

    ];

    protected $hidden = [

        'user_password',

    ];

    /**
     * Public URL for the stored profile picture, if any.
     */
    public function profilePictureUrl(): ?string
    {
        $value = trim((string) ($this->user_profile_picture ?? ''));
        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value) || str_starts_with($value, '/')) {
            return $value;
        }

        $path = ltrim(preg_replace('#^storage/#', '', $value), '/');

        return asset('storage/' . $path);
    }

    /**
     * One-letter fallback for avatars.
     */
    public function profileInitial(): string
    {
        $name = trim((string) ($this->user_full_name ?? ''));

        return $name !== '' ? strtoupper(substr($name, 0, 1)) : 'U';
    }

    /**
     * CUSTOM PASSWORD COLUMN
     */
    public function getAuthPassword()
    {
        return $this->user_password;
    }

    /**
     * users_table has no remember_token column.
     */
    public function getRememberTokenName()
    {
        return null;
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
     * Public URL for the uploaded profile picture (or null).
     */
    public function getProfilePictureUrlAttribute(): ?string
    {
        $path = $this->user_profile_picture;

        if (! filled($path)) {
            return null;
        }

        if (
            str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '/')
        ) {
            return $path;
        }

        $normalized = ltrim(str_replace('\\', '/', (string) $path), '/');
        $normalized = preg_replace('#^storage/#', '', $normalized) ?: $normalized;

        return asset('storage/'.$normalized);
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
