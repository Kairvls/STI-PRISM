<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('authority_to_purchase_table')) {
            Schema::table('authority_to_purchase_table', function (Blueprint $table) {
                if (!Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_payment_path')) {
                    $table->string('authority_purchase_payment_path', 30)->nullable()->after('authority_purchase_status');
                }
                if (!Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_payment_path_chosen_at')) {
                    $table->dateTime('authority_purchase_payment_path_chosen_at')->nullable()->after('authority_purchase_payment_path');
                }
            });
        }

        if (Schema::hasTable('authority_to_purchase_items_table')) {
            Schema::table('authority_to_purchase_items_table', function (Blueprint $table) {
                if (!Schema::hasColumn('authority_to_purchase_items_table', 'atp_supplier_stock')) {
                    $table->unsignedInteger('atp_supplier_stock')->nullable()->after('atp_quantity');
                }
                if (!Schema::hasColumn('authority_to_purchase_items_table', 'atp_back_order_qty')) {
                    $table->unsignedInteger('atp_back_order_qty')->nullable()->after('atp_supplier_stock');
                }
            });
        }

        if (Schema::hasTable('request_check_table')) {
            Schema::table('request_check_table', function (Blueprint $table) {
                if (!Schema::hasColumn('request_check_table', 'request_check_funding_type')) {
                    $table->string('request_check_funding_type', 30)->default('request_for_check')->after('request_check_authority_purchase_id');
                }
            });

            DB::table('request_check_table')
                ->whereNull('request_check_funding_type')
                ->orWhere('request_check_funding_type', '')
                ->update(['request_check_funding_type' => 'request_for_check']);
        }

        if (Schema::hasTable('receiving_report_items_table')) {
            Schema::table('receiving_report_items_table', function (Blueprint $table) {
                if (!Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_supplier_id')) {
                    $table->unsignedBigInteger('receiving_report_item_supplier_id')->nullable()->after('receiving_report_item_article');
                }
                if (!Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_supplier_name')) {
                    $table->string('receiving_report_item_supplier_name', 255)->nullable()->after('receiving_report_item_supplier_id');
                }
            });
        }

        if (!Schema::hasTable('procurement_record_packages_table')) {
            Schema::create('procurement_record_packages_table', function (Blueprint $table) {
                $table->bigIncrements('package_id');
                $table->unsignedBigInteger('package_authority_purchase_id')->nullable();
                $table->string('package_payment_path', 30)->nullable();
                $table->string('package_status', 40)->default('ready');
                $table->json('package_checklist')->nullable();
                $table->unsignedBigInteger('package_submitted_by')->nullable();
                $table->dateTime('package_submitted_to_accounting_at')->nullable();
                $table->unsignedBigInteger('package_forwarded_by')->nullable();
                $table->dateTime('package_forwarded_to_president_at')->nullable();
                $table->text('package_notes')->nullable();
                $table->boolean('package_is_archived')->default(false);
                $table->dateTime('package_created_at')->useCurrent();
                $table->dateTime('package_updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_record_packages_table');

        if (Schema::hasTable('receiving_report_items_table')) {
            Schema::table('receiving_report_items_table', function (Blueprint $table) {
                foreach (['receiving_report_item_supplier_name', 'receiving_report_item_supplier_id'] as $col) {
                    if (Schema::hasColumn('receiving_report_items_table', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('request_check_table') && Schema::hasColumn('request_check_table', 'request_check_funding_type')) {
            Schema::table('request_check_table', function (Blueprint $table) {
                $table->dropColumn('request_check_funding_type');
            });
        }

        if (Schema::hasTable('authority_to_purchase_items_table')) {
            Schema::table('authority_to_purchase_items_table', function (Blueprint $table) {
                foreach (['atp_back_order_qty', 'atp_supplier_stock'] as $col) {
                    if (Schema::hasColumn('authority_to_purchase_items_table', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('authority_to_purchase_table')) {
            Schema::table('authority_to_purchase_table', function (Blueprint $table) {
                foreach (['authority_purchase_payment_path_chosen_at', 'authority_purchase_payment_path'] as $col) {
                    if (Schema::hasColumn('authority_to_purchase_table', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
