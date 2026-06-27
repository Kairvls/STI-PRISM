<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomActivityLog extends Model
{
    protected $table = 'room_activity_logs_table';

    protected $primaryKey = 'activity_id';

    public $timestamps = false;

    protected $fillable = [

        'room_id',

        'equipment_id',

        'user_id',

        'activity_type',

        'activity_title',

        'activity_description',

        'created_at'

    ];
}