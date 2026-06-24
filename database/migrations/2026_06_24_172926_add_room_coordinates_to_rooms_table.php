<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rooms_table', function (Blueprint $table) {

            $table->integer('room_x')->default(0);

            $table->integer('room_y')->default(0);

            $table->integer('room_width')->default(120);

            $table->integer('room_height')->default(80);

            $table->string('room_color')->nullable();

            $table->string('room_type')->nullable();

            $table->json('room_metadata')->nullable();

            $table->enum(
                'room_status',
                [
                    'Normal',
                    'Maintenance Needed',
                    'Critical'
                ]
            )->default('Normal');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms_table', function (Blueprint $table) {

            $table->dropColumn([
                'room_x',
                'room_y',
                'room_width',
                'room_height',
                'room_color',
                'room_type',
                'room_metadata',
                'room_status'
            ]);

        });
    }
};
