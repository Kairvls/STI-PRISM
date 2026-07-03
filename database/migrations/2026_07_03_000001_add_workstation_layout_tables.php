<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms_table', function (Blueprint $table): void {
            if (!Schema::hasColumn('rooms_table', 'room_layout_mode')) {
                $table->enum('room_layout_mode', ['loose_equipment', 'workstation_grid'])
                    ->default('loose_equipment')
                    ->after('room_status');
            }

            if (!Schema::hasColumn('rooms_table', 'room_layout_version')) {
                $table->unsignedInteger('room_layout_version')
                    ->default(1)
                    ->after('room_layout_mode');
            }
        });

        if (!Schema::hasTable('workstation_templates_table')) {
            Schema::create('workstation_templates_table', function (Blueprint $table): void {
                $table->bigIncrements('workstation_template_id');
                $table->string('workstation_template_name', 150);
                $table->string('workstation_template_code', 50)->unique();
                $table->text('workstation_template_description')->nullable();
                $table->unsignedInteger('workstation_template_default_width')->default(140);
                $table->unsignedInteger('workstation_template_default_height')->default(100);
                $table->enum('workstation_template_default_orientation', ['north', 'east', 'south', 'west'])->default('north');
                $table->boolean('workstation_template_is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('workstation_template_slots_table')) {
            Schema::create('workstation_template_slots_table', function (Blueprint $table): void {
                $table->bigIncrements('workstation_template_slot_id');
                $table->unsignedBigInteger('workstation_template_id');
                $table->string('workstation_template_slot_key', 80);
                $table->string('workstation_template_slot_label', 150);
                $table->string('workstation_template_slot_category', 80);
                $table->boolean('workstation_template_slot_required')->default(true);
                $table->unsignedInteger('workstation_template_slot_sort_order')->default(0);
                $table->enum('workstation_template_slot_default_status', ['Good', 'Damaged', 'Under Maintenance', 'Disposed'])->nullable();
                $table->timestamps();

                $table->foreign('workstation_template_id')
                    ->references('workstation_template_id')
                    ->on('workstation_templates_table')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if (!Schema::hasTable('workstation_slots_table')) {
            Schema::create('workstation_slots_table', function (Blueprint $table): void {
                $table->bigIncrements('workstation_slot_id');
                $table->bigInteger('room_id');
                $table->unsignedBigInteger('workstation_template_id');
                $table->string('workstation_slot_label', 150);
                $table->string('workstation_slot_code', 80)->nullable();
                $table->enum('workstation_slot_orientation', ['north', 'east', 'south', 'west'])->default('north');
                $table->decimal('workstation_slot_position_x', 6, 2)->default(0);
                $table->decimal('workstation_slot_position_y', 6, 2)->default(0);
                $table->unsignedInteger('workstation_slot_width')->default(140);
                $table->unsignedInteger('workstation_slot_height')->default(100);
                $table->enum('workstation_slot_status', ['Active', 'Inactive', 'Needs Attention'])->default('Active');
                $table->json('workstation_slot_meta')->nullable();
                $table->timestamps();

                $table->foreign('room_id')
                    ->references('room_id')
                    ->on('rooms_table')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();

                $table->foreign('workstation_template_id')
                    ->references('workstation_template_id')
                    ->on('workstation_templates_table')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if (!Schema::hasColumn('equipment_table', 'workstation_slot_id')) {
            Schema::table('equipment_table', function (Blueprint $table): void {
                $table->unsignedBigInteger('workstation_slot_id')->nullable()->after('equipment_room_id');
            });
        }

        $hasWorkstationSlotFk = collect(DB::select(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'equipment_table'
               AND COLUMN_NAME = 'workstation_slot_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL"
        ))->isNotEmpty();

        if (Schema::hasColumn('equipment_table', 'workstation_slot_id') && !$hasWorkstationSlotFk) {
            Schema::table('equipment_table', function (Blueprint $table): void {
                $table->foreign('workstation_slot_id')
                    ->references('workstation_slot_id')
                    ->on('workstation_slots_table')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        Schema::table('equipment_table', function (Blueprint $table): void {
            if (Schema::hasColumn('equipment_table', 'workstation_slot_id')) {
                try {
                    $table->dropForeign(['workstation_slot_id']);
                } catch (\Throwable $throwable) {
                    // FK may already be absent on partially applied environments.
                }
                $table->dropColumn('workstation_slot_id');
            }
        });

        if (Schema::hasTable('workstation_slots_table')) {
            Schema::dropIfExists('workstation_slots_table');
        }

        if (Schema::hasTable('workstation_template_slots_table')) {
            Schema::dropIfExists('workstation_template_slots_table');
        }

        if (Schema::hasTable('workstation_templates_table')) {
            Schema::dropIfExists('workstation_templates_table');
        }

        Schema::table('rooms_table', function (Blueprint $table): void {
            if (Schema::hasColumn('rooms_table', 'room_layout_version')) {
                $table->dropColumn('room_layout_version');
            }

            if (Schema::hasColumn('rooms_table', 'room_layout_mode')) {
                $table->dropColumn('room_layout_mode');
            }
        });
    }
};