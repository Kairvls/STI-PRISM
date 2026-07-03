<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkstationTemplateSlot extends Model
{
    protected $table = 'workstation_template_slots_table';

    protected $primaryKey = 'workstation_template_slot_id';

    public $timestamps = true;

    protected $fillable = [
        'workstation_template_id',
        'workstation_template_slot_key',
        'workstation_template_slot_label',
        'workstation_template_slot_category',
        'workstation_template_slot_required',
        'workstation_template_slot_sort_order',
        'workstation_template_slot_default_status',
    ];

    protected $casts = [
        'workstation_template_slot_required' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(WorkstationTemplate::class, 'workstation_template_id', 'workstation_template_id');
    }
}