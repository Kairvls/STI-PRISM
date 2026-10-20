<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalSupplier extends Model
{
    protected $table = 'physical_suppliers_table';

    protected $primaryKey = 'physical_id';

    public $timestamps = false;

    protected $fillable = [
        'supplier_id',
        'company_name',
        'contact_person',
        'email_address',
        'contact_number',
        'landline_number',
        'company_address',
    ];
}
