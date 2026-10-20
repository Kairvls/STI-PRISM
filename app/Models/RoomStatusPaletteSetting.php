<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomStatusPaletteSetting extends Model
{
    protected $table = 'room_status_palette_settings_table';

    protected $primaryKey = 'room_status_palette_setting_id';

    public $timestamps = false;

    protected $fillable = [
        'palette_normal',
        'palette_under_maintenance',
        'palette_critical',
        'palette_updated_by',
        'palette_updated_at',
    ];

    protected $casts = [
        'palette_updated_at' => 'datetime',
    ];
}
