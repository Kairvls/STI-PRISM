<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'rooms_table';

    protected $primaryKey = 'room_id';

    public $timestamps = false;

    protected $fillable = [
        'room_floor_id', 'room_name', 'room_x', 'room_y', 'room_width',
        'room_height', 'room_color', 'room_type', 'room_metadata', 'room_status',
        'room_is_archived', 'room_archived_at', 'room_archived_reason',
    ];

    protected $casts = [
        'room_metadata' => 'array',
        'room_is_archived' => 'boolean',
        'room_archived_at' => 'datetime',
    ];

    public function floor()
    {
        return $this->belongsTo(
            Floor::class,
            'room_floor_id',
            'floor_id'
        );
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class, 'equipment_room_id', 'room_id');
    }
}
