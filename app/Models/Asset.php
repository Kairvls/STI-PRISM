<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $table = 'equipment_table';

    protected $primaryKey = 'equipment_id';

    public $timestamps = false;

    protected $fillable = [
        'equipment_category_id',
        'equipment_room_id',
        'workstation_slot_id',
        'equipment_supplier_id',
        'equipment_qr_code',
        'equipment_image',
        'equipment_asset_tag',
        'equipment_name',
        'equipment_brand_name',
        'equipment_model',
        'equipment_serial_number',
        'equipment_quantity',
        'equipment_tracking_mode',
        'equipment_condition_status',
        'equipment_inventory_status',
        'equipment_purchase_date',
        'equipment_purchase_cost',
        'equipment_acquired_date',
        'equipment_warranty_expiration',
        'equipment_current_location',
        'equipment_width',
        'equipment_height',
        'equipment_rotation',
        'equipment_is_borrowable',
    ];

    protected $casts = [
        'equipment_quantity' => 'integer',
        'equipment_purchase_cost' => 'decimal:2',
        'equipment_is_borrowable' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'equipment_room_id', 'room_id');
    }

    public function workstationSlot()
    {
        return $this->belongsTo(WorkstationSlot::class, 'workstation_slot_id', 'workstation_slot_id');
    }

    public function category()
    {
        return $this->belongsTo(EquipmentCategory::class, 'equipment_category_id', 'equipment_category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'equipment_supplier_id', 'supplier_id');
    }
}