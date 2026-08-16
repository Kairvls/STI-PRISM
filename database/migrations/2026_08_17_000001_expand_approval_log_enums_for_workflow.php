<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement("
                ALTER TABLE approval_logs_table
                MODIFY COLUMN approval_log_level ENUM(
                    'Admin',
                    'President',
                    'Accounting',
                    'Receiving',
                    'Admin Approval',
                    'Admin Co-sign',
                    'Admin Return'
                ) NOT NULL
            ");
        } catch (\Throwable $e) {
        }

        try {
            DB::statement("
                ALTER TABLE approval_logs_table
                MODIFY COLUMN approval_log_approval_status ENUM(
                    'Approved',
                    'Rejected',
                    'Directly Approved',
                    'Co-signed',
                    'Submitted',
                    'Under Review',
                    'Resubmitted',
                    'Pending',
                    'Forwarded to President',
                    'Minor Revision',
                    'Admin Approved'
                ) NOT NULL
            ");
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
    }
};
