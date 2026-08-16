<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    protected $table = 'item_categories_table';

    protected $primaryKey = 'item_category_id';

    public $timestamps = false;

    protected $guarded = [];

    public function subcategories()
    {
        return $this->hasMany(
            ItemSubCategory::class,
            'item_category_id',
            'item_category_id'
        );
    }
}
