<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles_table';

    protected $primaryKey = 'role_id';

    public $timestamps = false;

    protected $fillable = [
        'role_name'
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'user_role_id', 'role_id');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(
            User::class,
            'user_roles_table',
            'role_id',
            'user_id',
            'role_id',
            'user_id'
        )->withPivot('assigned_at');
    }
}