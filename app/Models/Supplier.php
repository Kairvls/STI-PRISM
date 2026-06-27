<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PhysicalSupplier;
use App\Models\OnlineSupplier;

class Supplier extends Model
{
    protected $table='suppliers_table';

    protected $primaryKey='supplier_id';

    public $timestamps=false;

    public function physical()
    {
        return $this->hasOne(

            PhysicalSupplier::class,

            'supplier_id'

        );
    }

    public function online()
    {
        return $this->hasOne(

            OnlineSupplier::class,

            'supplier_id'

        );
    }

}