<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('liquidation_reports_table')) {
            Schema::create('liquidation_reports_table', function (Blueprint $table) {
                $table->bigIncrements('liquidation_report_id');
                $table->unsignedBigInteger('liquidation_report_procurement_request_id')->nullable();
                $table->string('liquidation_report_employee_name')->nullable();
                $table->string('liquidation_report_cheque_number', 100)->nullable();
                $table->text('liquidation_report_purpose')->nullable();
                $table->decimal('liquidation_report_amount_advance', 12, 2)->nullable();
                $table->date('liquidation_report_date_released')->nullable();
                $table->date('liquidation_report_activity_end_date')->nullable();
                $table->date('liquidation_report_submission_deadline')->nullable();
                $table->date('liquidation_report_date_submitted')->nullable();
                $table->integer('liquidation_report_days_lapse')->nullable();
                $table->decimal('liquidation_report_summary_amt_advanced', 12, 2)->nullable();
                $table->decimal('liquidation_report_summary_actual_expense', 12, 2)->nullable();
                $table->decimal('liquidation_report_summary_balance', 12, 2)->nullable();
                $table->string('liquidation_report_cash_returned_or_no', 100)->nullable();
                $table->text('liquidation_report_submitted_by_signature')->nullable();
                $table->date('liquidation_report_submitted_by_date')->nullable();
                $table->text('liquidation_report_checked_by_accountant')->nullable();
                $table->date('liquidation_report_checked_by_date')->nullable();
                $table->text('liquidation_report_indorsed_by_supervisor')->nullable();
                $table->date('liquidation_report_indorsed_by_date')->nullable();
                $table->text('liquidation_report_recommending_approval')->nullable();
                $table->string('liquidation_report_status')->default('Draft');
                $table->dateTime('liquidation_report_created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('liquidation_report_items_table')) {
            Schema::create('liquidation_report_items_table', function (Blueprint $table) {
                $table->bigIncrements('liquidation_item_id');
                $table->unsignedBigInteger('liquidation_report_id')->nullable();
                $table->text('liquidation_item_particulars')->nullable();
                $table->decimal('liquidation_item_particulars_amount', 12, 2)->nullable();
                $table->decimal('liquidation_item_actual_breakdown_amount', 12, 2)->nullable();
                $table->decimal('liquidation_item_actual_total_amount', 12, 2)->nullable();
                $table->decimal('liquidation_item_variance', 12, 2)->default(0);
                $table->string('liquidation_item_ref_no', 100)->nullable();
            });
        }

        Schema::table('liquidation_reports_table', function (Blueprint $table) {
            $this->addIfMissing($table, 'liquidation_report_form_number', fn () => $table->string('liquidation_report_form_number', 100)->nullable()->after('liquidation_report_id'));
            $this->addIfMissing($table, 'liquidation_report_receiving_report_id', fn () => $table->unsignedBigInteger('liquidation_report_receiving_report_id')->nullable()->after('liquidation_report_procurement_request_id'));
            $this->addIfMissing($table, 'liquidation_report_charge_to_account', fn () => $table->string('liquidation_report_charge_to_account')->nullable()->after('liquidation_report_date_released'));
            $this->addIfMissing($table, 'liquidation_report_other_income', fn () => $table->decimal('liquidation_report_other_income', 12, 2)->nullable()->after('liquidation_report_days_lapse'));
            $this->addIfMissing($table, 'liquidation_report_submitted_by', fn () => $table->bigInteger('liquidation_report_submitted_by')->nullable()->after('liquidation_report_submitted_by_date'));
            $this->addIfMissing($table, 'liquidation_report_submitted_at', fn () => $table->dateTime('liquidation_report_submitted_at')->nullable()->after('liquidation_report_submitted_by'));
            $this->addIfMissing($table, 'liquidation_report_review_stage', fn () => $table->string('liquidation_report_review_stage', 20)->nullable()->after('liquidation_report_status'));
            $this->addIfMissing($table, 'liquidation_report_rejection_reason', fn () => $table->text('liquidation_report_rejection_reason')->nullable()->after('liquidation_report_review_stage'));
            $this->addIfMissing($table, 'liquidation_report_revision_notes', fn () => $table->text('liquidation_report_revision_notes')->nullable()->after('liquidation_report_rejection_reason'));
            $this->addIfMissing($table, 'liquidation_report_is_archived', fn () => $table->boolean('liquidation_report_is_archived')->default(false)->after('liquidation_report_revision_notes'));
            $this->addIfMissing($table, 'liquidation_report_updated_at', fn () => $table->dateTime('liquidation_report_updated_at')->nullable()->after('liquidation_report_created_at'));
        });

        try {
            DB::statement("
                ALTER TABLE liquidation_reports_table
                MODIFY liquidation_report_status ENUM(
                    'Pending','Draft','Submitted','Under Review','Minor Revision','Resubmitted','Pending Admin Approval','Approved','Rejected'
                ) NOT NULL DEFAULT 'Draft'
            ");
        } catch (\Throwable $e) {
        }

        DB::table('liquidation_reports_table')->where('liquidation_report_status', 'Pending')->whereNull('liquidation_report_submitted_at')->update(['liquidation_report_status' => 'Draft']);
        DB::table('liquidation_reports_table')->where('liquidation_report_status', 'Pending')->whereNotNull('liquidation_report_submitted_at')->update(['liquidation_report_status' => 'Submitted']);

        try {
            DB::statement("
                ALTER TABLE liquidation_reports_table
                MODIFY liquidation_report_status ENUM(
                    'Draft','Submitted','Under Review','Minor Revision','Resubmitted','Pending Admin Approval','Approved','Rejected'
                ) NOT NULL DEFAULT 'Draft'
            ");
        } catch (\Throwable $e) {
        }

        if (!Schema::hasTable('liquidation_report_attachments_table')) {
            Schema::create('liquidation_report_attachments_table', function (Blueprint $table) {
                $table->bigIncrements('liquidation_attachment_id');
                $table->unsignedBigInteger('liquidation_report_id');
                $table->string('liquidation_attachment_original_name');
                $table->string('liquidation_attachment_path');
                $table->string('liquidation_attachment_mime_type')->nullable();
                $table->unsignedBigInteger('liquidation_attachment_size')->nullable();
                $table->bigInteger('liquidation_attachment_uploaded_by')->nullable();
                $table->dateTime('liquidation_attachment_created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidation_report_attachments_table');
    }

    private function addIfMissing(Blueprint $table, string $column, callable $definition): void
    {
        if (!Schema::hasColumn('liquidation_reports_table', $column)) {
            $definition();
        }
    }
};
