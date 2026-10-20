<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineSupplier extends Model
{
    protected $table = 'online_suppliers_table';

    protected $primaryKey = 'online_id';

    public $timestamps = false;

    protected $fillable = [
        'supplier_id',
        'app_used',
        'shop_name',
        'contact_person',
        'email_address',
        'contact_number',
        'store_url',
        'seller_id',
        'order_id',
    ];
}
