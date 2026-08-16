<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('receiving_reports_table')) {
            Schema::create('receiving_reports_table', function (Blueprint $table) {
                $table->bigIncrements('receiving_report_id');
                $table->unsignedBigInteger('receiving_report_procurement_request_id')->nullable();
                $table->string('receiving_report_form_number', 100)->nullable();
                $table->unsignedBigInteger('receiving_report_supplier_id')->nullable();
                $table->text('receiving_report_supplier_address_override')->nullable();
                $table->date('receiving_report_date')->nullable();
                $table->string('receiving_report_invoice_no', 100)->nullable();
                $table->string('receiving_report_dr_no', 100)->nullable();
                $table->date('receiving_report_delivery_date')->nullable();
                $table->string('receiving_report_second_count_by')->nullable();
                $table->text('receiving_report_received_by_signature')->nullable();
                $table->string('receiving_report_status')->default('Draft');
                $table->dateTime('receiving_report_created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('receiving_report_items_table')) {
            Schema::create('receiving_report_items_table', function (Blueprint $table) {
                $table->bigIncrements('receiving_report_item_id');
                $table->unsignedBigInteger('receiving_report_id')->nullable();
                $table->integer('receiving_report_item_quantity')->nullable();
                $table->string('receiving_report_item_unit', 50)->nullable();
                $table->text('receiving_report_item_article')->nullable();
            });
        }

        Schema::table('receiving_reports_table', function (Blueprint $table) {
            $this->addIfMissing($table, 'receiving_report_request_check_id', fn () => $table->unsignedBigInteger('receiving_report_request_check_id')->nullable()->after('receiving_report_procurement_request_id'));
            $this->addIfMissing($table, 'receiving_report_received_from', fn () => $table->string('receiving_report_received_from')->nullable()->after('receiving_report_supplier_id'));
            $this->addIfMissing($table, 'receiving_report_submitted_by', fn () => $table->bigInteger('receiving_report_submitted_by')->nullable()->after('receiving_report_received_by_signature'));
            $this->addIfMissing($table, 'receiving_report_submitted_at', fn () => $table->dateTime('receiving_report_submitted_at')->nullable()->after('receiving_report_submitted_by'));
            $this->addIfMissing($table, 'receiving_report_second_count_by_user_id', fn () => $table->bigInteger('receiving_report_second_count_by_user_id')->nullable()->after('receiving_report_second_count_by'));
            $this->addIfMissing($table, 'receiving_report_second_count_at', fn () => $table->dateTime('receiving_report_second_count_at')->nullable()->after('receiving_report_second_count_by_user_id'));
            $this->addIfMissing($table, 'receiving_report_second_count_signature', fn () => $table->text('receiving_report_second_count_signature')->nullable()->after('receiving_report_second_count_at'));
            $this->addIfMissing($table, 'receiving_report_revision_notes', fn () => $table->text('receiving_report_revision_notes')->nullable()->after('receiving_report_status'));
            $this->addIfMissing($table, 'receiving_report_return_reason', fn () => $table->text('receiving_report_return_reason')->nullable()->after('receiving_report_revision_notes'));
            $this->addIfMissing($table, 'receiving_report_is_archived', fn () => $table->boolean('receiving_report_is_archived')->default(false)->after('receiving_report_return_reason'));
            $this->addIfMissing($table, 'receiving_report_updated_at', fn () => $table->dateTime('receiving_report_updated_at')->nullable()->after('receiving_report_created_at'));
        });

        try {
            DB::statement("
                ALTER TABLE receiving_reports_table
                MODIFY receiving_report_status ENUM(
                    'Pending',
                    'Draft',
                    'Submitted',
                    'Under Review',
                    'Minor Revision',
                    'Resubmitted',
                    'Completed',
                    'Returned'
                ) NOT NULL DEFAULT 'Draft'
            ");
        } catch (\Throwable $e) {
        }

        DB::table('receiving_reports_table')
            ->where('receiving_report_status', 'Pending')
            ->whereNull('receiving_report_submitted_at')
            ->update(['receiving_report_status' => 'Draft']);

        DB::table('receiving_reports_table')
            ->where('receiving_report_status', 'Pending')
            ->whereNotNull('receiving_report_submitted_at')
            ->update(['receiving_report_status' => 'Submitted']);

        try {
            DB::statement("
                ALTER TABLE receiving_reports_table
                MODIFY receiving_report_status ENUM(
                    'Draft',
                    'Submitted',
                    'Under Review',
                    'Minor Revision',
                    'Resubmitted',
                    'Completed',
                    'Returned'
                ) NOT NULL DEFAULT 'Draft'
            ");
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
    }

    private function addIfMissing(Blueprint $table, string $column, callable $definition): void
    {
        if (!Schema::hasColumn('receiving_reports_table', $column)) {
            $definition();
        }
    }
};
