<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SUPPLY_OFFICER_ROLE_ID = 8;

    public function up(): void
    {
        if (Schema::hasTable('roles_table')) {
            DB::table('roles_table')->updateOrInsert(
                ['role_id' => self::SUPPLY_OFFICER_ROLE_ID],
                ['role_name' => 'School Supply / Material Officer']
            );
        }

        if (Schema::hasTable('requisition_issue_slip_table')) {
            Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
                if (! Schema::hasColumn('requisition_issue_slip_table', 'ris_sent_to_supply_at')) {
                    $table->timestamp('ris_sent_to_supply_at')->nullable()->after('ris_updated_at');
                }
                if (! Schema::hasColumn('requisition_issue_slip_table', 'ris_sent_to_supply_by')) {
                    $table->unsignedBigInteger('ris_sent_to_supply_by')->nullable()->after('ris_sent_to_supply_at');
                }
                if (! Schema::hasColumn('requisition_issue_slip_table', 'ris_supply_officer_user_id')) {
                    $table->unsignedBigInteger('ris_supply_officer_user_id')->nullable()->after('ris_sent_to_supply_by');
                }
            });
        }

        if (Schema::hasTable('authority_to_purchase_table')) {
            Schema::table('authority_to_purchase_table', function (Blueprint $table) {
                if (! Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_released_to_purchaser_at')) {
                    $table->timestamp('authority_purchase_released_to_purchaser_at')->nullable();
                }
                if (! Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_released_to_purchaser_by')) {
                    $table->unsignedBigInteger('authority_purchase_released_to_purchaser_by')->nullable();
                }
                if (! Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_released_to_user_id')) {
                    $table->unsignedBigInteger('authority_purchase_released_to_user_id')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('authority_to_purchase_table')) {
            Schema::table('authority_to_purchase_table', function (Blueprint $table) {
                foreach ([
                    'authority_purchase_released_to_purchaser_at',
                    'authority_purchase_released_to_purchaser_by',
                    'authority_purchase_released_to_user_id',
                ] as $column) {
                    if (Schema::hasColumn('authority_to_purchase_table', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('requisition_issue_slip_table')) {
            Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
                foreach ([
                    'ris_sent_to_supply_at',
                    'ris_sent_to_supply_by',
                    'ris_supply_officer_user_id',
                ] as $column) {
                    if (Schema::hasColumn('requisition_issue_slip_table', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('user_roles_table')) {
            DB::table('user_roles_table')->where('role_id', self::SUPPLY_OFFICER_ROLE_ID)->delete();
        }

        if (Schema::hasTable('roles_table')) {
            $inUse = Schema::hasTable('users_table')
                && DB::table('users_table')->where('user_role_id', self::SUPPLY_OFFICER_ROLE_ID)->exists();
            if (! $inUse) {
                DB::table('roles_table')->where('role_id', self::SUPPLY_OFFICER_ROLE_ID)->delete();
            }
        }
    }
};
