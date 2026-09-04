<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        $this->merge([
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
            'operating_hours' => $this->filled('operating_hours') ? trim($this->operating_hours) : null,
            'app_used' => $this->filled('app_used') ? trim($this->app_used) : null,
            'shop_name' => $shopName,
            'store_url' => $this->filled('store_url') ? trim($this->store_url) : null,
            'seller_id' => $this->filled('seller_id') ? trim($this->seller_id) : null,
            'order_id' => $this->filled('order_id') ? trim($this->order_id) : null,
        ]);
    }

    public function rules()
    {
        $rules = [
            'supplier_store_type' => ['required', Rule::in(['Physical Store', 'Online Store'])],
            'operating_hours' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->input('supplier_store_type') === 'Physical Store') {
            return array_merge($rules, [
                'company_name' => ['required', 'string', 'max:255'],
                'contact_person' => ['nullable', 'string', 'max:255'],
                'email_address' => ['nullable', 'email', 'max:255'],
                'contact_number' => ['nullable', 'string', 'max:50'],
                'landline_number' => ['nullable', 'string', 'max:50'],
                'company_address' => ['nullable', 'string', 'max:2000'],
            ]);
        }

        return array_merge($rules, [
            'app_used' => ['required', 'string', 'max:100'],
            'shop_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email_address' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'store_url' => ['nullable', 'string', 'max:500'],
            'seller_id' => ['nullable', 'string', 'max:100'],
            'order_id' => ['nullable', 'string', 'max:255'],
        ]);
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
        ];
    }
}
