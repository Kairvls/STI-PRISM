@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'Edit Supplier')
@section('page-subtitle', 'Update supplier information')

@section('content')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route(($pp ?? 'purchaser').'.suppliers.update', $supplier->supplier_id) }}" class="pur-card space-y-6 p-6">
        @csrf
        @method('PUT')

        <input type="hidden" name="supplier_store_type" value="{{ $supplier->supplier_store_type }}">

        @if($supplier->supplier_store_type === 'Physical Store')
            <div>
                <label class="text-xs font-medium text-gray-500">Company Name</label>
                <input type="text" name="company_name" value="{{ $physical->company_name ?? '' }}" class="pur-input mt-1" required>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                <div>
                    <label class="text-xs font-medium text-gray-500">Contact Person</label>
                    <input type="text" name="contact_person" value="{{ $physical->contact_person ?? '' }}" class="pur-input mt-1">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500">Email</label>
                    <input type="email" name="email_address" value="{{ $physical->email_address ?? '' }}" class="pur-input mt-1">
                </div>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                <div>
                    <label class="text-xs font-medium text-gray-500">Contact Number</label>
                    @include('partials.phone-input', [
                        'name' => 'contact_number',
                        'value' => old('contact_number', $physical->contact_number ?? ''),
                        'id' => 'edit-supplier-contact-number',
                        'placeholder' => '9XX XXX XXXX',
                        'inputClass' => 'phone-input--pur pur-input mt-1',
                    ])
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500">Landline Number</label>
                    @include('partials.landline-input', [
                        'name' => 'landline_number',
                        'value' => old('landline_number', $physical->landline_number ?? ''),
                        'id' => 'edit-supplier-landline-number',
                        'placeholder' => '(0XX) XXX-XXXX',
                        'inputClass' => 'phone-input--pur pur-input mt-1',
                    ])
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Company Address</label>
                <textarea name="company_address" rows="4" class="pur-input mt-1">{{ $physical->company_address ?? '' }}</textarea>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Operating Hours</label>
                <input type="text" name="operating_hours" value="{{ old('operating_hours', $supplier->operating_hours ?? '') }}" placeholder="e.g. Mon–Fri 9:00 AM – 6:00 PM" maxlength="255" class="pur-input mt-1">
            </div>
        @else
            <div>
                <label class="text-xs font-medium text-gray-500">App Used</label>
                <input type="text" name="app_used" value="{{ $online->app_used ?? '' }}" class="pur-input mt-1" required>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Shop Name</label>
                <input type="text" name="shop_name" value="{{ $online->shop_name ?? '' }}" class="pur-input mt-1" required>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                <div>
                    <label class="text-xs font-medium text-gray-500">Contact Person</label>
                    <input type="text" name="contact_person" value="{{ $online->contact_person ?? '' }}" class="pur-input mt-1">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500">Contact Number</label>
                    @include('partials.phone-input', [
                        'name' => 'contact_number',
                        'value' => old('contact_number', $online->contact_number ?? ''),
                        'id' => 'edit-online-supplier-contact-number',
                        'placeholder' => '9XX XXX XXXX',
                        'inputClass' => 'phone-input--pur pur-input mt-1',
                    ])
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Email</label>
                <input type="email" name="email_address" value="{{ $online->email_address ?? '' }}" class="pur-input mt-1">
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Store URL</label>
                <input type="text" name="store_url" value="{{ $online->store_url ?? '' }}" placeholder="https://shopee.ph/shop/..." class="pur-input mt-1">
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Seller / Store ID</label>
                <input type="text" name="seller_id" value="{{ $online->seller_id ?? '' }}" placeholder="Platform seller or store ID" class="pur-input mt-1">
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Operating Hours</label>
                <input type="text" name="operating_hours" value="{{ old('operating_hours', $supplier->operating_hours ?? '') }}" placeholder="e.g. Mon–Sun 8:00 AM – 10:00 PM" maxlength="255" class="pur-input mt-1">
            </div>
        @endif

        <div class="flex justify-end gap-2">
            <a href="{{ route(($pp ?? 'purchaser').'.suppliers.index') }}" class="pur-btn-secondary">Cancel</a>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#001fa8]">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
