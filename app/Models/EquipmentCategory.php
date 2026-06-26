<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentCategory extends Model
{
    protected $table = 'equipment_categories_table';

    protected $primaryKey = 'equipment_category_id';

    public $timestamps = false;

    protected $guarded = [];

    public function equipment()
    {
        return $this->hasMany(
            Equipment::class,
            'equipment_category_id',
            'equipment_category_id'
        );
    }
}