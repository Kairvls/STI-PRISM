<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SupplierRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $supplierName = $this->filled('supplier_name') ? trim((string) $this->supplier_name) : null;
        $companyName = $this->filled('company_name') ? trim((string) $this->company_name) : $supplierName;
        $shopName = $this->filled('shop_name') ? trim((string) $this->shop_name) : $supplierName;

        // Modal binds type via Alpine; keep a safe default so a blank hidden field
        // does not fall through into Online-only rules (app_used required).
        $storeType = $this->filled('supplier_store_type')
            ? trim((string) $this->input('supplier_store_type'))
            : 'Physical Store';

        $rawStoreUrl = $this->filled('store_url') ? trim((string) $this->store_url) : null;
        if ($rawStoreUrl !== null && ! preg_match('#^https?://#i', $rawStoreUrl)) {
            $rawStoreUrl = 'https://'.$rawStoreUrl;
        }

        $this->merge([
            'supplier_store_type' => $storeType,
            'company_name' => $companyName,
            'contact_person' => $this->filled('contact_person') ? trim($this->contact_person) : null,
            'email_address' => $this->filled('email_address') ? trim($this->email_address) : null,
            'contact_number' => PhoneNumber::normalizeForStorage(
                $this->has('contact_number') ? trim((string) $this->input('contact_number')) : null
            ),
            'landline_number' => PhoneNumber::normalizeLandlineForStorage(
                $this->has('landline_number') ? trim((string) $this->input('landline_number')) : null
            ),
            'company_address' => $this->filled('company_address') ? trim($this->company_address) : null,
            'operating_hours' => $storeType === 'Physical Store' && $this->filled('operating_hours')
                ? trim($this->operating_hours)
                : null,
            'supplier_check_name' => $this->filled('supplier_check_name') ? trim($this->supplier_check_name) : null,
            'app_used' => $this->filled('app_used') ? trim($this->app_used) : null,
            'shop_name' => $shopName,
            'store_url' => $rawStoreUrl,
            'seller_id' => $this->filled('seller_id') ? trim($this->seller_id) : null,
            'order_id' => $this->filled('order_id') ? trim($this->order_id) : null,
        ]);
    }

    public function rules()
    {
        $rules = [
            'supplier_store_type' => ['required', Rule::in(['Physical Store', 'Online Store'])],
            'operating_hours' => ['nullable', 'string', 'max:255'],
            'supplier_check_name' => ['nullable', 'string', 'max:255'],
            'supplier_logo' => [
                'nullable',
                'image',
                'mimes:'.\App\Support\SupplierLogo::MIMES,
                'max:'.\App\Support\SupplierLogo::MAX_KB,
            ],
            'remove_supplier_logo' => ['nullable', 'boolean'],
        ];

        if ($this->input('supplier_store_type') === 'Physical Store') {
            return array_merge($rules, [
                'company_name' => ['required', 'string', 'max:255'],
                'contact_person' => ['nullable', 'string', 'max:255'],
                'email_address' => ['nullable', 'email', 'max:255'],
                'contact_number' => PhoneNumber::rules(),
                'landline_number' => ['nullable', 'string', 'max:50'],
                'company_address' => ['nullable', 'string', 'max:2000'],
            ]);
        }

        return array_merge($rules, [
            'app_used' => ['required', 'string', 'max:100'],
            'shop_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email_address' => ['nullable', 'email', 'max:255'],
            'contact_number' => PhoneNumber::rules(),
            'store_url' => ['nullable', 'url', 'max:500'],
            'seller_id' => ['nullable', 'string', 'max:100'],
            'order_id' => ['nullable', 'string', 'max:255'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $isPhysical = $this->input('supplier_store_type') === 'Physical Store';
            $field = $isPhysical ? 'company_name' : 'shop_name';
            $name = trim((string) $this->input($field, ''));
            if ($name === '') {
                return;
            }

            if ($this->displayNameTaken($name, $this->currentSupplierId())) {
                $validator->errors()->add(
                    $field,
                    'A supplier named “'.$name.'” already exists. Use a different name, or edit the existing supplier.'
                );
            }
        });
    }

    public function attributes(): array
    {
        return [
            'supplier_store_type' => 'supplier type',
            'company_name' => 'company name',
            'app_used' => 'platform',
            'shop_name' => 'shop name',
            'contact_person' => 'contact person',
            'contact_number' => 'contact number',
            'email_address' => 'email address',
            'operating_hours' => 'operating hours',
            'supplier_check_name' => 'check name',
            'supplier_logo' => 'brand logo',
        ];
    }

    private function currentSupplierId(): ?int
    {
        $routeId = $this->route('supplier');
        if ($routeId === null || $routeId === '') {
            return null;
        }

        return (int) $routeId;
    }

    /**
     * True when another supplier already uses this display name
     * (Physical company_name or Online shop_name), case-insensitive.
     */
    private function displayNameTaken(string $name, ?int $ignoreSupplierId = null): bool
    {
        $key = mb_strtolower($name);

        if (Schema::hasTable('physical_suppliers_table')) {
            $physical = DB::table('physical_suppliers_table')
                ->whereRaw('LOWER(company_name) = ?', [$key]);
            if ($ignoreSupplierId) {
                $physical->where('supplier_id', '!=', $ignoreSupplierId);
            }
            if ($physical->exists()) {
                return true;
            }
        }

        if (Schema::hasTable('online_suppliers_table')) {
            $online = DB::table('online_suppliers_table')
                ->whereRaw('LOWER(shop_name) = ?', [$key]);
            if ($ignoreSupplierId) {
                $online->where('supplier_id', '!=', $ignoreSupplierId);
            }
            if ($online->exists()) {
                return true;
            }
        }

        return false;
    }
}
