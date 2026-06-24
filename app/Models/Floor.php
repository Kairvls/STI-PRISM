<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Floor extends Model
{
    protected $table = 'floors_table';

    protected $primaryKey = 'floor_id';

    public $timestamps = false;

    protected $fillable = ['floor_building_id', 'floor_level'];

    public function building()
    {
        return $this->belongsTo(
            Building::class,
            'floor_building_id',
            'building_id'
        );
    }

    public function rooms()
    {
        return $this->hasMany(
            Room::class,
            'room_floor_id',
            'floor_id'
        );
    }
}
