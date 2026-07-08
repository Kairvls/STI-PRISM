<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class MaintenanceViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }


    public function boot(): void
    {
        // =====================================================
        // SHARE NOTIFICATIONS WITH MAINTENANCE TOPBAR
        // =====================================================

        View::composer(
            'layouts.maintenance-topbar',
            function ($view) {

                // =====================================================
                // CURRENT USER
                // =====================================================

                $userId = Auth::id();


                // =====================================================
                // DEFAULT VALUES
                // =====================================================

                $headerNotifications =
                    collect();

                $headerUnreadCount =
                    0;


                // =====================================================
                // STOP IF USER IS NOT AUTHENTICATED
                // =====================================================

                if (!$userId) {

                    $view->with(
                        compact(
                            'headerNotifications',
                            'headerUnreadCount'
                        )
                    );

                    return;

                }


                // =====================================================
                // BASE ACCESSIBLE NOTIFICATIONS QUERY
                // =====================================================

                $accessibleNotifications = function () use (
                    $userId
                ) {

                    return DB::table(
                        'notifications_table'
                    )

                    ->where(function ($query) use ($userId) {

                        // =============================================
                        // PERSONAL NOTIFICATION
                        // =============================================

                        $query->where(
                            'notifications_table.notification_user_id',
                            $userId
                        )


                        // =============================================
                        // OR MAINTENANCE BROADCAST
                        // =============================================

                        ->orWhere(function ($query) {

                            $query
                                ->whereNull(
                                    'notifications_table.notification_user_id'
                                )

                                ->where(
                                    'notifications_table.notification_target_role',
                                    'Maintenance Personnel'
                                );

                        });

                    });

                };


                // =====================================================
                // GET LATEST NOTIFICATIONS
                //
                // SHOW READ AND UNREAD
                // is_read IS FOR CURRENT USER ONLY
                // =====================================================

                $headerNotifications =
                    $accessibleNotifications()

                    ->leftJoin(
                        'notification_reads_table',
                        function ($join) use ($userId) {

                            $join->on(
                                'notifications_table.notification_id',
                                '=',
                                'notification_reads_table.notification_id'
                            );

                            $join->where(
                                'notification_reads_table.user_id',
                                '=',
                                $userId
                            );

                        }
                    )

                    ->select(
                        'notifications_table.*'
                    )

                    ->selectRaw(
                        'CASE
                            WHEN notification_reads_table.notification_read_id IS NULL
                            THEN 0
                            ELSE 1
                        END AS is_read'
                    )

                    ->orderByDesc(
                        'notifications_table.notification_created_at'
                    )

                    ->limit(5)

                    ->get();


                // =====================================================
                // GET TOTAL UNREAD COUNT
                // =====================================================

                $headerUnreadCount =
                    $accessibleNotifications()

                    ->whereNotExists(
                        function ($query) use ($userId) {

                            $query
                                ->select(DB::raw(1))

                                ->from(
                                    'notification_reads_table'
                                )

                                ->whereColumn(
                                    'notification_reads_table.notification_id',
                                    'notifications_table.notification_id'
                                )

                                ->where(
                                    'notification_reads_table.user_id',
                                    $userId
                                );

                        }
                    )

                    ->count();


                // =====================================================
                // SEND DATA TO TOPBAR
                // =====================================================

                $view->with(
                    compact(
                        'headerNotifications',
                        'headerUnreadCount'
                    )
                );

            }
        );
    }
}