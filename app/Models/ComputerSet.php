<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComputerSet extends Model
{
    protected $table = 'computer_sets_table';

    protected $primaryKey = 'computer_set_id';

    public $timestamps = false;

    protected $fillable = [
        'computer_set_tag',
        'computer_set_name',
        'computer_set_room_id',
        'computer_set_placement_zone',
        'computer_set_status',
        'computer_set_created_by',
        'computer_set_created_at',
        'computer_set_updated_at',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'computer_set_room_id', 'room_id');
    }

    public function members()
    {
        return $this->hasMany(ComputerSetMember::class, 'computer_set_id', 'computer_set_id');
    }
}
