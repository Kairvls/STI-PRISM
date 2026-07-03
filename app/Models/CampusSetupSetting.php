<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampusSetupSetting extends Model
{
    protected $table = 'campus_setup_settings_table';

    protected $primaryKey = 'campus_setup_setting_id';

    public $timestamps = false;

    protected $fillable = [
        'campus_setup_pin_hash',
        'campus_setup_pin_updated_by',
        'campus_setup_pin_updated_at',
    ];

    protected $casts = [
        'campus_setup_pin_updated_at' => 'datetime',
    ];
}