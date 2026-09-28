<?php

use App\Support\BackOrderNumber;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('back_orders_table')) {
            return;
        }

        if (! Schema::hasColumn('back_orders_table', 'back_order_number')) {
            Schema::table('back_orders_table', function (Blueprint $table) {
                $table->string('back_order_number', 30)->nullable()->after('back_order_id');
                $table->unique('back_order_number', 'bo_number_unique');
            });
        }

        // Number existing back orders in the order they were created, by the month they were created.
        $sequences = [];
        DB::table('back_orders_table')
            ->whereNull('back_order_number')
            ->orderBy('back_order_created_at')
            ->orderBy('back_order_id')
            ->get(['back_order_id', 'back_order_payment_path', 'back_order_created_at'])
            ->each(function ($bo) use (&$sequences) {
                $created = $bo->back_order_created_at ? Carbon::parse($bo->back_order_created_at) : now();
                $prefix = BackOrderNumber::prefix($bo->back_order_payment_path, $created);
                if (! isset($sequences[$prefix])) {
                    $sequences[$prefix] = (int) substr((string) BackOrderNumber::next($bo->back_order_payment_path, $created), -5) - 1;
                }
                $sequences[$prefix]++;

                DB::table('back_orders_table')
                    ->where('back_order_id', $bo->back_order_id)
                    ->update(['back_order_number' => $prefix.str_pad((string) $sequences[$prefix], 5, '0', STR_PAD_LEFT)]);
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('back_orders_table') && Schema::hasColumn('back_orders_table', 'back_order_number')) {
            Schema::table('back_orders_table', function (Blueprint $table) {
                $table->dropUnique('bo_number_unique');
                $table->dropColumn('back_order_number');
            });
        }
    }
};
