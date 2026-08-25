<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ReceivingViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer(
            'layouts.receiving-sidebar',
            function ($view) {
                $receivingSidebarPendingCount = 0;

                try {
                    if (Schema::hasTable('receiving_reports_table')) {
                        $query = DB::table('receiving_reports_table')
                            ->whereIn('receiving_report_status', [
                                'Pending',
                                'Submitted',
                                'Resubmitted',
                                'Under Review',
                            ]);

                        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived')) {
                            $query->where(function ($q) {
                                $q->whereNull('receiving_report_is_archived')
                                    ->orWhere('receiving_report_is_archived', 0);
                            });
                        }

                        $receivingSidebarPendingCount = (int) $query->count();
                    }
                } catch (\Throwable $e) {
                    // Keep zero if schema/query fails.
                }

                $view->with(compact('receivingSidebarPendingCount'));
            }
        );
    }
}
