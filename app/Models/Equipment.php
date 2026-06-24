<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    protected $table = 'equipment_table';
    protected $primaryKey = 'equipment_id';
    public $timestamps = false;
    protected $guarded = [];

    public function room()
    {
        return $this->belongsTo(Room::class, 'equipment_room_id', 'room_id');
    }
}
