<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkstationSlot extends Model
{
    protected $table = 'workstation_slots_table';

    protected $primaryKey = 'workstation_slot_id';

    protected $fillable = [
        'room_id',
        'workstation_template_id',
        'workstation_slot_label',
        'workstation_slot_code',
        'workstation_slot_orientation',
        'workstation_slot_position_x',
        'workstation_slot_position_y',
        'workstation_slot_width',
        'workstation_slot_height',
        'workstation_slot_status',
        'workstation_slot_meta',
    ];

    protected $casts = [
        'workstation_slot_position_x' => 'decimal:2',
        'workstation_slot_position_y' => 'decimal:2',
        'workstation_slot_meta' => 'array',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function template()
    {
        return $this->belongsTo(WorkstationTemplate::class, 'workstation_template_id', 'workstation_template_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'workstation_slot_id', 'workstation_slot_id');
    }
}