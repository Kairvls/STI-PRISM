<?php

namespace App\Http\Controllers;

use App\Support\NotificationLinks;
use App\Support\RoleAccess;
use App\Support\WorkflowNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    /**
     * Marks the notification as read and sends the user to the record it is about.
     */
    public function open(int $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && Schema::hasTable('notifications_table'), 404);

        $roles = array_values(array_filter(array_map(
            fn ($roleId) => WorkflowNotifier::roleNameForId((int) $roleId),
            RoleAccess::roleIds($user)
        )));

        $notification = DB::table('notifications_table')
            ->where('notification_id', $id)
            ->where(function ($q) use ($user, $roles) {
                $q->where('notification_user_id', $user->user_id)
                    ->orWhere(function ($q) use ($roles) {
                        $q->whereNull('notification_user_id')
                            ->whereIn('notification_target_role', $roles);
                    });
            })
            ->first();

        abort_unless($notification, 404);

        if (Schema::hasTable('notification_reads_table')) {
            DB::table('notification_reads_table')->insertOrIgnore([
                'notification_id' => $notification->notification_id,
                'user_id' => $user->user_id,
                'notification_read_at' => now(),
            ]);
        }

        $destination = NotificationLinks::resolve($notification);

        return redirect($destination !== '' ? $destination : RoleAccess::dashboardPath(RoleAccess::primaryRoleId($user)));
    }
}
