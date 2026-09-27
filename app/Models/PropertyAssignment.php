<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyAssignment extends Model
{
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_RETURNED = 'Returned';
    public const STATUS_TRANSFERRED = 'Transferred';

    protected $table = 'property_assignments_table';

    protected $primaryKey = 'assignment_id';

    const CREATED_AT = 'assignment_created_at';
    const UPDATED_AT = 'assignment_updated_at';

    protected $fillable = [
        'assignment_equipment_id',
        'assignment_custodian_id',
        'assignment_room_id',
        'assignment_slot_id',
        'assignment_status',
        'assignment_document_no',
        'assignment_notes',
        'assignment_issued_by',
        'assignment_issued_at',
        'assignment_acknowledged_at',
        'assignment_signature_path',
        'assignment_returned_by',
        'assignment_returned_at',
        'assignment_return_condition',
        'assignment_return_notes',
        'assignment_verified_at',
        'assignment_verified_by',
    ];

    protected $casts = [
        'assignment_issued_at' => 'datetime',
        'assignment_verified_at' => 'datetime',
        'assignment_acknowledged_at' => 'datetime',
        'assignment_returned_at' => 'datetime',
        'assignment_created_at' => 'datetime',
        'assignment_updated_at' => 'datetime',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'assignment_equipment_id', 'equipment_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'assignment_room_id', 'room_id');
    }

    public function workstationSlot()
    {
        return $this->belongsTo(WorkstationSlot::class, 'assignment_slot_id', 'workstation_slot_id');
    }

    public function scopeActive($query)
    {
        return $query->where('assignment_status', self::STATUS_ACTIVE);
    }
}
