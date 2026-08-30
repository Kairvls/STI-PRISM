<?php

namespace App\Providers;

use App\Support\PresidentAttentionSummary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class PresidentViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer(
            'layouts.president-topbar',
            function ($view) {
                $userId = Auth::id();
                $headerNotifications = collect();
                $headerUnreadCount = 0;
                $attentionTotal = 0;

                if (!$userId || !Schema::hasTable('notifications_table')) {
                    try {
                        if ($userId) {
                            $attentionTotal = (int) (PresidentAttentionSummary::counts()['attentionTotal'] ?? 0);
                        }
                    } catch (\Throwable $e) {
                        $attentionTotal = 0;
                    }

                    $view->with(compact('headerNotifications', 'headerUnreadCount', 'attentionTotal'));
                    return;
                }

                $accessibleNotifications = function () use ($userId) {
                    return DB::table('notifications_table')
                        ->where(function ($query) use ($userId) {
                            $query->where('notifications_table.notification_user_id', $userId)
                                ->orWhere(function ($query) {
                                    $query->whereNull('notifications_table.notification_user_id')
                                        ->where('notifications_table.notification_target_role', 'President');
                                });
                        });
                };

                $hasReads = Schema::hasTable('notification_reads_table');

                if ($hasReads) {
                    $headerNotifications = $accessibleNotifications()
                        ->leftJoin('notification_reads_table', function ($join) use ($userId) {
                            $join->on(
                                'notifications_table.notification_id',
                                '=',
                                'notification_reads_table.notification_id'
                            )->where('notification_reads_table.user_id', '=', $userId);
                        })
                        ->select('notifications_table.*')
                        ->selectRaw(
                            'CASE
                                WHEN notification_reads_table.notification_read_id IS NULL
                                THEN 0
                                ELSE 1
                            END AS is_read'
                        )
                        ->orderByDesc('notifications_table.notification_created_at')
                        ->limit(5)
                        ->get();

                    $headerUnreadCount = $accessibleNotifications()
                        ->whereNotExists(function ($query) use ($userId) {
                            $query->select(DB::raw(1))
                                ->from('notification_reads_table')
                                ->whereColumn(
                                    'notification_reads_table.notification_id',
                                    'notifications_table.notification_id'
                                )
                                ->where('notification_reads_table.user_id', $userId);
                        })
                        ->count();
                } else {
                    $headerNotifications = $accessibleNotifications()
                        ->select('notifications_table.*')
                        ->selectRaw('0 AS is_read')
                        ->orderByDesc('notifications_table.notification_created_at')
                        ->limit(5)
                        ->get();

                    $headerUnreadCount = $accessibleNotifications()->count();
                }

                try {
                    $attentionTotal = (int) (PresidentAttentionSummary::counts()['attentionTotal'] ?? 0);
                } catch (\Throwable $e) {
                    $attentionTotal = 0;
                }

                $view->with(compact('headerNotifications', 'headerUnreadCount', 'attentionTotal'));
            }
        );
    }
}
