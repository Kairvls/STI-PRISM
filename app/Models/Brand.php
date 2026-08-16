<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'brands_table';

    protected $primaryKey = 'brand_id';

    public $timestamps = false;

    protected $guarded = [];
}
