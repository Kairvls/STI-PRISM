<?php

namespace App\Providers;

use App\Support\AdminAttentionSummary;
use App\Support\AdminPortal;
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
            ['layouts.admin-sidebar', 'layouts.admin-topbar', 'layouts.school-admin-sidebar'],
            function ($view) {
                $adminSidebarPendingRis = 0;
                $adminSidebarAwaitingCosign = 0;
                $adminSidebarAmendRis = 0;
                $attentionTotal = 0;

                // RIS work is the School Administrator's; the Administrator portal only monitors it.
                if (! AdminPortal::canActOnRis()) {
                    $view->with(compact(
                        'adminSidebarPendingRis',
                        'adminSidebarAwaitingCosign',
                        'adminSidebarAmendRis',
                        'attentionTotal'
                    ));

                    return;
                }

                try {
                    $attention = AdminAttentionSummary::counts();
                    $adminSidebarPendingRis = $attention['pendingRis'];
                    $adminSidebarAwaitingCosign = $attention['awaitingCosign'];
                    $adminSidebarAmendRis = $attention['amendRis'];
                    $attentionTotal = $attention['attentionTotal'];
                } catch (\Throwable $e) {
                    // Keep zeros if schema/query fails.
                }

                $view->with(compact(
                    'adminSidebarPendingRis',
                    'adminSidebarAwaitingCosign',
                    'adminSidebarAmendRis',
                    'attentionTotal'
                ));
            }
        );
    }
}
