<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemSubCategory extends Model
{
    protected $table = 'item_subcategories_table';

    protected $primaryKey = 'item_subcategory_id';

    public $timestamps = false;

    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(
            ItemCategory::class,
            'item_category_id',
            'item_category_id'
        );
    }
}
