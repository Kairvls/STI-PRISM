<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkstationTemplate extends Model
{
    protected $table = 'workstation_templates_table';

    protected $primaryKey = 'workstation_template_id';

    public $timestamps = true;

    protected $fillable = [
        'workstation_template_name',
        'workstation_template_code',
        'workstation_template_description',
        'workstation_template_default_width',
        'workstation_template_default_height',
        'workstation_template_default_orientation',
        'workstation_template_is_active',
    ];

    protected $casts = [
        'workstation_template_is_active' => 'boolean',
    ];

    public function slots()
    {
        return $this->hasMany(WorkstationTemplateSlot::class, 'workstation_template_id', 'workstation_template_id');
    }
}