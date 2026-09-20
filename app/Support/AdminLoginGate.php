<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminLoginGate
{
    public static function isAdminIntent(): bool
    {
        return session('login_intent') === 'admin';
    }

    public static function markAdminIntent(): void
    {
        session(['login_intent' => 'admin']);
    }

    public static function clearIntent(): void
    {
        session()->forget('login_intent');
    }

    /**
     * @return list<string>
     */
    public static function allowedEmails(): array
    {
        return config('services.admin.allowed_emails', []);
    }

    public static function emailIsAllowed(string $email): bool
    {
        $allowed = self::allowedEmails();
        if ($allowed === []) {
            return true;
        }

        return in_array(strtolower(trim($email)), $allowed, true);
    }

    public static function recordLogin(?int $userId, string $status, ?string $description = null): void
    {
        $ip = request()->ip();
        $agent = substr((string) request()->userAgent(), 0, 2000);

        if (Schema::hasTable('user_login_logs_table')) {
            DB::table('user_login_logs_table')->insert([
                'user_login_user_id' => $userId,
                'user_login_ip_address' => $ip,
                'user_login_device_information' => $agent,
                'user_login_status' => $status === 'Success' ? 'Success' : 'Failed',
                'user_login_created_at' => now(),
            ]);
        }

        if (Schema::hasTable('audit_logs_table') && $userId) {
            $row = [
                'audit_log_user_id' => $userId,
                'audit_log_action' => $status === 'Success' ? 'Admin login success' : 'Admin login failed',
                'audit_log_table_name' => 'users_table',
                'audit_log_reference_id' => $userId,
                'audit_log_description' => $description
                    ?? ($status === 'Success'
                        ? 'Administrator signed in via Office 365 (/admin/login).'
                        : 'Administrator sign-in attempt failed.'),
                'audit_log_ip_address' => $ip,
                'audit_log_created_at' => now(),
            ];

            if (Schema::hasColumn('audit_logs_table', 'audit_log_module')) {
                $row['audit_log_module'] = 'Admin';
            }

            DB::table('audit_logs_table')->insert($row);
        }
    }
}
