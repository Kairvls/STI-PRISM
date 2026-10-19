<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComputerSetMember extends Model
{
    protected $table = 'computer_set_members_table';

    protected $primaryKey = 'computer_set_member_id';

    public $timestamps = false;

    protected $fillable = [
        'computer_set_id',
        'equipment_id',
        'member_role',
        'member_sort',
        'member_added_at',
        'member_added_by',
    ];

    public function computerSet()
    {
        return $this->belongsTo(ComputerSet::class, 'computer_set_id', 'computer_set_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }
}
