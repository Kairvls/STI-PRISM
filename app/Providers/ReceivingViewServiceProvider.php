<?php

namespace App\Providers;

use App\Support\ReceivingAttentionSummary;
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
                    $receivingSidebarPendingCount = (int) (ReceivingAttentionSummary::counts()['pendingCount'] ?? 0);
                } catch (\Throwable $e) {
                    // Keep zero if schema/query fails.
                }

                $view->with(compact('receivingSidebarPendingCount'));
            }
        );

        View::composer(
            'layouts.receiving-topbar',
            function ($view) {
                $attentionTotal = 0;

                try {
                    $attentionTotal = (int) (ReceivingAttentionSummary::counts()['attentionTotal'] ?? 0);
                } catch (\Throwable $e) {
                    $attentionTotal = 0;
                }

                $view->with(compact('attentionTotal'));
            }
        );
    }
}
