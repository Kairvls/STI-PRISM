<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrowing_records_table', function (Blueprint $table) {
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_from_room_id')) {
                $table->unsignedBigInteger('borrowing_from_room_id')->nullable();
            }
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_returned_to_room_id')) {
                $table->unsignedBigInteger('borrowing_returned_to_room_id')->nullable();
            }
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_return_condition')) {
                $table->string('borrowing_return_condition', 60)->nullable();
            }
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_return_remarks')) {
                $table->text('borrowing_return_remarks')->nullable();
            }
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_returned_by')) {
                $table->unsignedBigInteger('borrowing_returned_by')->nullable();
            }
        });

        // Loans still out were taken from the room the equipment is in now.
        DB::table('borrowing_records_table')
            ->whereNull('borrowing_from_room_id')
            ->whereIn('borrowing_status', ['Borrowed', 'Overdue'])
            ->update([
                'borrowing_from_room_id' => DB::raw('(SELECT equipment_room_id FROM equipment_table WHERE equipment_table.equipment_id = borrowing_records_table.borrowing_equipment_id)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('borrowing_records_table', function (Blueprint $table) {
            foreach (['borrowing_from_room_id', 'borrowing_returned_to_room_id', 'borrowing_return_condition', 'borrowing_return_remarks', 'borrowing_returned_by'] as $column) {
                if (Schema::hasColumn('borrowing_records_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
