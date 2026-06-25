<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $table = 'buildings_table';

    protected $primaryKey = 'building_id';

    public $timestamps = false;

    protected $fillable = [

        'building_name',

        'building_logo',

        'building_address',

    ];

    public function floors()
    {
        return $this->hasMany(
            Floor::class,
            'floor_building_id',
            'building_id'
        );
    }
}
