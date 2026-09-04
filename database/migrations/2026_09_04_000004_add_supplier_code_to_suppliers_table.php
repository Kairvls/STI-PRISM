<?php

use App\Support\SupplierCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('suppliers_table')) {
            return;
        }

        if (!Schema::hasColumn('suppliers_table', 'supplier_code')) {
            DB::statement("ALTER TABLE suppliers_table ADD COLUMN supplier_code VARCHAR(40) DEFAULT NULL AFTER supplier_id");
        }

        $rows = DB::table('suppliers_table')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->select(
                'suppliers_table.supplier_id',
                'suppliers_table.supplier_store_type',
                'suppliers_table.supplier_created_at',
                'suppliers_table.supplier_code',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            )
            ->where(function ($query) {
                $query->whereNull('suppliers_table.supplier_code')
                    ->orWhere('suppliers_table.supplier_code', '');
            })
            ->get();

        $used = DB::table('suppliers_table')
            ->whereNotNull('supplier_code')
            ->where('supplier_code', '!=', '')
            ->pluck('supplier_code')
            ->all();
        $used = array_fill_keys($used, true);

        foreach ($rows as $row) {
            if (!empty($row->supplier_code)) {
                continue;
            }

            $name = $row->company_name ?: $row->shop_name;
            $base = SupplierCode::generate(
                (string) $row->supplier_store_type,
                $name,
                $row->supplier_created_at
            );

            $code = $base;
            $n = 2;
            while (isset($used[$code])) {
                $code = $base . '-' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
                $n++;
            }

            $used[$code] = true;

            DB::table('suppliers_table')
                ->where('supplier_id', $row->supplier_id)
                ->update(['supplier_code' => $code]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('suppliers_table') && Schema::hasColumn('suppliers_table', 'supplier_code')) {
            Schema::table('suppliers_table', function ($table) {
                $table->dropColumn('supplier_code');
            });
        }
    }
};
