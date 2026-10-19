<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('request_check_table')) {
            Schema::table('request_check_table', function (Blueprint $table) {
                if (! Schema::hasColumn('request_check_table', 'request_check_sent_to_accounting_at')) {
                    $table->timestamp('request_check_sent_to_accounting_at')->nullable();
                }
                if (! Schema::hasColumn('request_check_table', 'request_check_sent_to_accounting_by')) {
                    $table->unsignedBigInteger('request_check_sent_to_accounting_by')->nullable();
                }
            });
        }

        if (! Schema::hasTable('disbursement_vouchers_table')) {
            Schema::create('disbursement_vouchers_table', function (Blueprint $table) {
                $table->id('dv_id');
                $table->unsignedBigInteger('dv_request_check_id');
                $table->unsignedBigInteger('dv_ris_id')->nullable();
                $table->unsignedBigInteger('dv_authority_purchase_id')->nullable();
                $table->string('dv_form_number', 64)->nullable();
                $table->string('dv_status', 32)->default('Draft');
                $table->string('dv_payee', 255)->nullable();
                $table->decimal('dv_amount', 12, 2)->nullable();
                $table->text('dv_remarks')->nullable();
                $table->unsignedBigInteger('dv_created_by')->nullable();
                $table->unsignedBigInteger('dv_submitted_by')->nullable();
                $table->timestamp('dv_submitted_at')->nullable();
                $table->unsignedBigInteger('dv_assigned_reviewer_id')->nullable();
                $table->text('dv_revision_reason')->nullable();
                $table->unsignedBigInteger('dv_approved_by')->nullable();
                $table->timestamp('dv_approved_at')->nullable();
                $table->boolean('dv_is_archived')->default(0);
                $table->timestamps();

                $table->index('dv_request_check_id');
                $table->index('dv_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('disbursement_vouchers_table');

        if (Schema::hasTable('request_check_table')) {
            Schema::table('request_check_table', function (Blueprint $table) {
                foreach (['request_check_sent_to_accounting_at', 'request_check_sent_to_accounting_by'] as $col) {
                    if (Schema::hasColumn('request_check_table', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
