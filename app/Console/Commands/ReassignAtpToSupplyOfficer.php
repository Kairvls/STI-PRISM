<?php

namespace App\Console\Commands;

use App\Support\ProcurementPortal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-time / ops helper: move legacy Purchaser-owned ATPs to a Supply Officer
 * after ATP creation ownership moved to that role.
 */
class ReassignAtpToSupplyOfficer extends Command
{
    protected $signature = 'atp:reassign-to-supply-officer
                            {--user= : Supply Officer user_id (defaults to first role 8 user)}
                            {--dry-run : Show counts without writing}';

    protected $description = 'Reassign Authority to Purchase created_by from Purchaser/legacy owners to the School Supply / Material Officer';

    public function handle(): int
    {
        if (! Schema::hasTable('authority_to_purchase_table')) {
            $this->error('authority_to_purchase_table is missing.');

            return self::FAILURE;
        }

        $officerId = (int) ($this->option('user') ?: 0);
        if ($officerId <= 0) {
            $officerId = (int) (DB::table('users_table')->where('user_role_id', ProcurementPortal::SUPPLY_OFFICER_ROLE_ID)->value('user_id') ?: 0);
        }
        if ($officerId <= 0 && Schema::hasTable('user_roles_table')) {
            $officerId = (int) (DB::table('user_roles_table')
                ->where('role_id', ProcurementPortal::SUPPLY_OFFICER_ROLE_ID)
                ->value('user_id') ?: 0);
        }

        $officer = $officerId > 0
            ? DB::table('users_table')->where('user_id', $officerId)->first()
            : null;

        if (! $officer) {
            $this->error('No School Supply / Material Officer user found. Pass --user=ID.');

            return self::FAILURE;
        }

        $name = trim((string) ($officer->user_full_name ?: $officer->user_username));
        $this->info("Target Supply Officer: {$name} (#{$officerId})");

        $query = DB::table('authority_to_purchase_table')
            ->where(function ($q) use ($officerId) {
                $q->whereNull('authority_purchase_created_by')
                    ->orWhere('authority_purchase_created_by', '!=', $officerId);
            });

        $count = (clone $query)->count();
        $this->line("ATPs to reassign: {$count}");

        if ($this->option('dry-run')) {
            $this->warn('Dry run — no changes written.');

            return self::SUCCESS;
        }

        if ($count === 0) {
            $this->info('Nothing to update.');

            return self::SUCCESS;
        }

        $updated = $query->update([
            'authority_purchase_created_by' => $officerId,
            'authority_purchase_updated_at' => now(),
        ]);

        $risUpdated = 0;
        if (
            Schema::hasColumn('requisition_issue_slip_table', 'ris_supply_officer_user_id')
            && Schema::hasColumn('requisition_issue_slip_table', 'ris_sent_to_supply_at')
        ) {
            $risUpdated = DB::table('requisition_issue_slip_table')
                ->whereNotNull('ris_sent_to_supply_at')
                ->where(function ($q) use ($officerId) {
                    $q->whereNull('ris_supply_officer_user_id')
                        ->orWhere('ris_supply_officer_user_id', '!=', $officerId);
                })
                ->update([
                    'ris_supply_officer_user_id' => $officerId,
                    'ris_updated_at' => now(),
                ]);
        }

        $this->info("Updated {$updated} ATP record(s).");
        if ($risUpdated > 0) {
            $this->info("Assigned {$risUpdated} sent Incoming RIS row(s) to {$name}.");
        }

        return self::SUCCESS;
    }
}
