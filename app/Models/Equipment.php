<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\EquipmentCategory;

class Equipment extends Model
{
    protected $table = 'equipment_table';
    protected $primaryKey = 'equipment_id';
    public $timestamps = false;
    protected $fillable = [

        'equipment_category_id',

        'equipment_room_id',

        'equipment_name',

        'equipment_asset_tag',

        'equipment_brand_name',

        'equipment_model',

        'equipment_serial_number',

        'equipment_quantity',

        'equipment_tracking_mode',

        'equipment_condition_status',

        'equipment_inventory_status',

        'equipment_purchase_date',

        'equipment_warranty_expiration',

        'equipment_current_location',

        'equipment_placement_zone',

        'equipment_position_x',

        'equipment_position_y',

        'equipment_is_borrowable',

        'equipment_qr_code',

    ];

    protected $casts = [

        'equipment_position_x' => 'integer',

        'equipment_position_y' => 'integer',

        'equipment_quantity' => 'integer',

        'equipment_is_borrowable' => 'boolean',

    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'equipment_room_id', 'room_id');
    }
    
    public function category()
    {
        return $this->belongsTo(
            EquipmentCategory::class,
            'equipment_category_id',
            'equipment_category_id'
        );
    }

    protected static function booted()
    {
        static::created(function (self $equipment) {
            \App\Support\EquipmentQrCodes::assignIfEligible((int) $equipment->equipment_id);
            $equipment->refresh();
        });
    }

    // ==============================
    // Supplier Relationship
    // ==============================

    public function supplier()
    {
        return $this->belongsTo(

            Supplier::class,

            'equipment_supplier_id',

            'supplier_id'

        );
    }
}
