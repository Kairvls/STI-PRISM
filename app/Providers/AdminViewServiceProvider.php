<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AdminViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer(
            'layouts.admin-sidebar',
            function ($view) {
                $adminSidebarPendingRis = 0;
                $adminSidebarAwaitingCosign = 0;
                $adminSidebarAmendRis = 0;

                try {
                    if (Schema::hasTable('requisition_issue_slip_table')) {
                        $base = DB::table('requisition_issue_slip_table')
                            ->whereNotNull('ris_requested_by_date');

                        $adminSidebarPendingRis = (clone $base)
                            ->whereIn('ris_status', ['Submitted', 'Under Review', 'Resubmitted', 'Pending'])
                            ->count();

                        $adminSidebarAmendRis = (clone $base)
                            ->whereIn('ris_status', ['Minor Revision', 'Rejected'])
                            ->count();

                        // Match AdminController::applyRisAwaitingAdminActionScope
                        $adminSidebarAwaitingCosign = DB::table('requisition_issue_slip_table')
                            ->where(function ($q) {
                                $q->where('ris_status', 'Approved by the President')
                                    ->orWhere(function ($legacy) {
                                        $legacy->where('ris_status', 'Approved')
                                            ->whereNotNull('ris_approved_by_signature')
                                            ->where('ris_approved_by_signature', '!=', '')
                                            ->where('ris_approved_by_signature', 'like', 'data:image%');
                                    });
                            })
                            ->where(function ($unsigned) {
                                $unsigned->whereNull('ris_issued_by_signature')
                                    ->orWhere('ris_issued_by_signature', '');
                            })
                            ->count();
                    }
                } catch (\Throwable $e) {
                    // Keep zeros if schema/query fails.
                }

                $view->with(compact(
                    'adminSidebarPendingRis',
                    'adminSidebarAwaitingCosign',
                    'adminSidebarAmendRis'
                ));
            }
        );
    }
}
