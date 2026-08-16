<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Uom extends Model
{
    protected $table = 'uom_table';

    protected $primaryKey = 'uom_id';

    public $timestamps = false;

    protected $guarded = [];
}
