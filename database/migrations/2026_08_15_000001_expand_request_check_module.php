<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('request_check_table')) {
            Schema::create('request_check_table', function (Blueprint $table) {
                $table->bigIncrements('request_check_id');
                $table->unsignedBigInteger('request_check_authority_purchase_id')->nullable();
                $table->date('request_check_date')->nullable();
                $table->string('request_check_payee')->nullable();
                $table->text('request_check_amount_words')->nullable();
                $table->decimal('request_check_amount_figures', 12, 2)->nullable();
                $table->text('request_check_particulars_purpose')->nullable();
                $table->string('request_check_requested_by')->nullable();
                $table->text('request_check_approved_by_admin')->nullable();
                $table->string('request_check_status')->default('Draft');
                $table->dateTime('request_check_created_at')->useCurrent();
            });
        }

        Schema::table('request_check_table', function (Blueprint $table) {
            $this->addColumnIfMissing($table, 'request_check_form_number', fn () => $table->string('request_check_form_number', 100)->nullable()->after('request_check_id'));
            $this->addColumnIfMissing($table, 'request_check_requested_by_user_id', fn () => $table->bigInteger('request_check_requested_by_user_id')->nullable()->after('request_check_requested_by'));
            $this->addColumnIfMissing($table, 'request_check_submitted_by', fn () => $table->bigInteger('request_check_submitted_by')->nullable()->after('request_check_requested_by_user_id'));
            $this->addColumnIfMissing($table, 'request_check_submitted_at', fn () => $table->dateTime('request_check_submitted_at')->nullable()->after('request_check_submitted_by'));
            $this->addColumnIfMissing($table, 'request_check_review_stage', fn () => $table->string('request_check_review_stage', 20)->nullable()->after('request_check_status'));
            $this->addColumnIfMissing($table, 'request_check_accounting_verified_by', fn () => $table->bigInteger('request_check_accounting_verified_by')->nullable()->after('request_check_review_stage'));
            $this->addColumnIfMissing($table, 'request_check_accounting_verified_at', fn () => $table->dateTime('request_check_accounting_verified_at')->nullable()->after('request_check_accounting_verified_by'));
            $this->addColumnIfMissing($table, 'request_check_approved_by_user_id', fn () => $table->bigInteger('request_check_approved_by_user_id')->nullable()->after('request_check_approved_by_admin'));
            $this->addColumnIfMissing($table, 'request_check_approved_at', fn () => $table->dateTime('request_check_approved_at')->nullable()->after('request_check_approved_by_user_id'));
            $this->addColumnIfMissing($table, 'request_check_approved_by_signature', fn () => $table->text('request_check_approved_by_signature')->nullable()->after('request_check_approved_at'));
            $this->addColumnIfMissing($table, 'request_check_rejection_reason', fn () => $table->text('request_check_rejection_reason')->nullable()->after('request_check_approved_by_signature'));
            $this->addColumnIfMissing($table, 'request_check_revision_notes', fn () => $table->text('request_check_revision_notes')->nullable()->after('request_check_rejection_reason'));
            $this->addColumnIfMissing($table, 'request_check_is_archived', fn () => $table->boolean('request_check_is_archived')->default(false)->after('request_check_revision_notes'));
            $this->addColumnIfMissing($table, 'request_check_receiving_report_id', fn () => $table->unsignedBigInteger('request_check_receiving_report_id')->nullable()->after('request_check_authority_purchase_id'));
            $this->addColumnIfMissing($table, 'request_check_updated_at', fn () => $table->dateTime('request_check_updated_at')->nullable()->after('request_check_created_at'));
        });

        try {
            DB::statement("
                ALTER TABLE request_check_table
                MODIFY request_check_status ENUM(
                    'Pending',
                    'Draft',
                    'Submitted',
                    'Under Review',
                    'Minor Revision',
                    'Resubmitted',
                    'Pending Admin Approval',
                    'Approved',
                    'Rejected'
                ) NOT NULL DEFAULT 'Draft'
            ");
        } catch (\Throwable $e) {
            // ENUM already expanded or engine does not allow modify.
        }

        DB::table('request_check_table')
            ->where('request_check_status', 'Pending')
            ->whereNull('request_check_submitted_at')
            ->update(['request_check_status' => 'Draft']);

        DB::table('request_check_table')
            ->where('request_check_status', 'Pending')
            ->whereNotNull('request_check_submitted_at')
            ->update(['request_check_status' => 'Submitted']);

        try {
            DB::statement("
                ALTER TABLE request_check_table
                MODIFY request_check_status ENUM(
                    'Draft',
                    'Submitted',
                    'Under Review',
                    'Minor Revision',
                    'Resubmitted',
                    'Pending Admin Approval',
                    'Approved',
                    'Rejected'
                ) NOT NULL DEFAULT 'Draft'
            ");
        } catch (\Throwable $e) {
            // Keep previous enum if Pending rows remain.
        }

        if (!Schema::hasTable('request_check_attachments_table')) {
            Schema::create('request_check_attachments_table', function (Blueprint $table) {
                $table->bigIncrements('request_check_attachment_id');
                $table->unsignedBigInteger('request_check_id');
                $table->string('request_check_attachment_original_name');
                $table->string('request_check_attachment_path');
                $table->string('request_check_attachment_mime_type')->nullable();
                $table->unsignedBigInteger('request_check_attachment_size')->nullable();
                $table->bigInteger('request_check_attachment_uploaded_by')->nullable();
                $table->dateTime('request_check_attachment_created_at')->useCurrent();
                $table->index('request_check_id', 'idx_rfc_attachment_rfc_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('request_check_attachments_table');
    }

    private function addColumnIfMissing(Blueprint $table, string $column, callable $definition): void
    {
        if (!Schema::hasColumn('request_check_table', $column)) {
            $definition();
        }
    }
};
