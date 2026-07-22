<?php

namespace App\Http\Requests;

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
        // ADDED SUPPLIERS MODULE: trim supplier fields before validation and saving.
        $this->merge([
            'company_name' => $this->filled('company_name') ? trim($this->company_name) : null,
            'contact_person' => $this->filled('contact_person') ? trim($this->contact_person) : null,
            'email_address' => $this->filled('email_address') ? trim($this->email_address) : null,
            'contact_number' => $this->filled('contact_number') ? trim($this->contact_number) : null,
            'company_address' => $this->filled('company_address') ? trim($this->company_address) : null,
            'app_used' => $this->filled('app_used') ? trim($this->app_used) : null,
            'shop_name' => $this->filled('shop_name') ? trim($this->shop_name) : null,
            'order_id' => $this->filled('order_id') ? trim($this->order_id) : null,
        ]);
    }

    public function rules()
    {
        $rules = [
            'supplier_store_type' => ['required', Rule::in(['Physical Store', 'Online Store'])],
        ];

        if ($this->input('supplier_store_type') === 'Physical Store') {
            $rules = array_merge($rules, [
                'company_name' => ['required', 'string', 'max:255'],
                'contact_person' => ['nullable', 'string', 'max:255'],
                'email_address' => ['nullable', 'email', 'max:255'],
                'contact_number' => ['nullable', 'string', 'max:50'],
                'company_address' => ['nullable', 'string', 'max:2000'],
            ]);
        } else {
            $rules = array_merge($rules, [
                'app_used' => ['required', 'string', 'max:100'],
                'shop_name' => ['required', 'string', 'max:255'],
                'order_id' => ['nullable', 'string', 'max:255'],
            ]);
        }

        return $rules;
    }
}
