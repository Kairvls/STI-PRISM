<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('equipment_table', function (Blueprint $table) {

            $table->enum(

                'equipment_tracking_mode',

                [

                    'Bulk',

                    'Individual'

                ]

            )

            ->default('Bulk')

            ->after('equipment_quantity');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            //
        });
    }
};
