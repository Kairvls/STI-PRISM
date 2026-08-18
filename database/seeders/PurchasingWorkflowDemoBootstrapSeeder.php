<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class PurchasingWorkflowDemoBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureDemoActors();
        $this->ensureDemoSuppliers();
    }

    public function ensureDemoActors(): array
    {
        $actors = [
            'admin' => $this->upsertUser([
                'role_id' => 1,
                'employee_id' => 'DEMO-ADMIN-001',
                'username' => 'demo_admin',
                'full_name' => 'Demo Admin',
                'email' => 'demo.admin@prism.local',
                'contact' => '09170000001',
            ]),
            'purchaser' => $this->upsertUser([
                'role_id' => 3,
                'employee_id' => 'DEMO-PUR-001',
                'username' => 'demo_purchaser',
                'full_name' => 'Demo Purchaser',
                'email' => 'demo.purchaser@prism.local',
                'contact' => '09170000003',
            ]),
            'president' => $this->upsertUser([
                'role_id' => 4,
                'employee_id' => 'DEMO-PRES-001',
                'username' => 'demo_president',
                'full_name' => 'Demo President',
                'email' => 'demo.president@prism.local',
                'contact' => '09170000004',
            ]),
            'accounting' => $this->upsertUser([
                'role_id' => 5,
                'employee_id' => 'DEMO-ACC-001',
                'username' => 'demo_accounting',
                'full_name' => 'Demo Accounting',
                'email' => 'demo.accounting@prism.local',
                'contact' => '09170000005',
            ]),
            'receiving' => $this->upsertUser([
                'role_id' => 6,
                'employee_id' => 'DEMO-REC-001',
                'username' => 'demo_receiving',
                'full_name' => 'Demo Receiving',
                'email' => 'demo.receiving@prism.local',
                'contact' => '09170000006',
            ]),
        ];

        return $actors;
    }

    public function ensureDemoSuppliers(): array
    {
        $suppliers = [];

        $suppliers[] = $this->upsertPhysicalSupplier([
            'key' => 'demo-physical-1',
            'company_name' => 'Demo Office Depot',
            'contact_person' => 'Maria Santos',
            'email_address' => 'orders@demoofficedepot.local',
            'contact_number' => '09171000001',
            'company_address' => 'Ormoc City Demo Address 1',
        ]);

        $suppliers[] = $this->upsertPhysicalSupplier([
            'key' => 'demo-physical-2',
            'company_name' => 'Demo Campus Supply',
            'contact_person' => 'Jose Cruz',
            'email_address' => 'sales@democampussupply.local',
            'contact_number' => '09171000002',
            'company_address' => 'Ormoc City Demo Address 2',
        ]);

        $suppliers[] = $this->upsertPhysicalSupplier([
            'key' => 'demo-physical-3',
            'company_name' => 'Demo Tech Hardware',
            'contact_person' => 'Anne Flores',
            'email_address' => 'support@demotechhardware.local',
            'contact_number' => '09171000003',
            'company_address' => 'Ormoc City Demo Address 3',
        ]);

        $suppliers[] = $this->upsertOnlineSupplier([
            'key' => 'demo-online-1',
            'app_used' => 'Shopee',
            'shop_name' => 'Demo Gadgets Hub',
            'order_id' => 'DEMO-ORDER-001',
        ]);

        $suppliers[] = $this->upsertOnlineSupplier([
            'key' => 'demo-online-2',
            'app_used' => 'Lazada',
            'shop_name' => 'Demo IT Mart',
            'order_id' => 'DEMO-ORDER-002',
        ]);

        return $suppliers;
    }

    private function upsertUser(array $data): int
    {
        $existing = DB::table('users_table')
            ->where('user_username', $data['username'])
            ->first();

        $payload = [
            'user_role_id' => $data['role_id'],
            'user_employee_id' => $data['employee_id'],
            'user_username' => $data['username'],
            'user_full_name' => $data['full_name'],
            'user_email_address' => $data['email'],
            'user_contact_number' => $data['contact'],
            'user_password' => Hash::make('password'),
        ];

        if ($existing) {
            DB::table('users_table')
                ->where('user_id', $existing->user_id)
                ->update($payload);

            return (int) $existing->user_id;
        }

        return (int) DB::table('users_table')->insertGetId($payload);
    }

    private function upsertPhysicalSupplier(array $data): array
    {
        $existing = DB::table('physical_suppliers_table')
            ->where('company_name', $data['company_name'])
            ->first();

        $supplierId = $this->upsertSupplierBase($existing?->supplier_id, 'Physical Store');

        if (Schema::hasTable('online_suppliers_table')) {
            DB::table('online_suppliers_table')->where('supplier_id', $supplierId)->delete();
        }

        DB::table('physical_suppliers_table')->updateOrInsert(
            ['supplier_id' => $supplierId],
            [
                'company_name' => $data['company_name'],
                'contact_person' => $data['contact_person'],
                'email_address' => $data['email_address'],
                'contact_number' => $data['contact_number'],
                'company_address' => $data['company_address'],
            ]
        );

        return [
            'supplier_id' => $supplierId,
            'supplier_store_type' => 'Physical Store',
            'display_name' => $data['company_name'],
            'address' => $data['company_address'],
        ];
    }

    private function upsertOnlineSupplier(array $data): array
    {
        $existing = DB::table('online_suppliers_table')
            ->where('shop_name', $data['shop_name'])
            ->first();

        $supplierId = $this->upsertSupplierBase($existing?->supplier_id, 'Online Store');

        if (Schema::hasTable('physical_suppliers_table')) {
            DB::table('physical_suppliers_table')->where('supplier_id', $supplierId)->delete();
        }

        DB::table('online_suppliers_table')->updateOrInsert(
            ['supplier_id' => $supplierId],
            [
                'app_used' => $data['app_used'],
                'shop_name' => $data['shop_name'],
                'order_id' => $data['order_id'],
            ]
        );

        return [
            'supplier_id' => $supplierId,
            'supplier_store_type' => 'Online Store',
            'display_name' => $data['shop_name'],
            'address' => $data['app_used'],
        ];
    }

    private function upsertSupplierBase($existingSupplierId, string $storeType): int
    {
        $payload = $this->onlyExisting('suppliers_table', [
            'supplier_store_type' => $storeType,
            'supplier_is_active' => 1,
            'supplier_created_at' => now(),
            'supplier_updated_at' => now(),
        ]);

        if ($existingSupplierId) {
            unset($payload['supplier_created_at']);

            DB::table('suppliers_table')
                ->where('supplier_id', $existingSupplierId)
                ->update($payload);

            return (int) $existingSupplierId;
        }

        return (int) DB::table('suppliers_table')->insertGetId($payload);
    }

    private function onlyExisting(string $table, array $payload): array
    {
        $filtered = [];

        foreach ($payload as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
    }
}
