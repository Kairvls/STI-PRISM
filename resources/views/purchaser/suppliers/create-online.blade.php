@extends('layouts.purchaser-layout')

@section('page-title', 'New Online Supplier')
@section('page-subtitle', 'Create online supplier')

@section('content')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('purchaser.suppliers.store') }}" class="pur-card space-y-6 p-6">
        @csrf
        <input type="hidden" name="supplier_store_type" value="Online Store">

        <div>
            <label class="text-xs font-medium text-gray-500">App Used</label>
            <input type="text" name="app_used" class="pur-input mt-1" required>
        </div>

        <div>
            <label class="text-xs font-medium text-gray-500">Shop Name</label>
            <input type="text" name="shop_name" class="pur-input mt-1" required>
        </div>

        <div class="grid gap-3 lg:grid-cols-2">
            <div>
                <label class="text-xs font-medium text-gray-500">Contact Person</label>
                <input type="text" name="contact_person" value="{{ old('contact_person') }}" class="pur-input mt-1">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-500">Email</label>
                <input type="email" name="email_address" value="{{ old('email_address') }}" class="pur-input mt-1">
            </div>
        </div>

        <div>
            <label class="text-xs font-medium text-gray-500">Contact Number</label>
            @include('partials.phone-input', [
                'name' => 'contact_number',
                'value' => old('contact_number'),
                'id' => 'create-online-supplier-contact-number',
                'placeholder' => '9XX XXX XXXX',
                'inputClass' => 'phone-input--pur pur-input mt-1',
            ])
        </div>

        <div>
            <label class="text-xs font-medium text-gray-500">Store URL</label>
            <input type="text" name="store_url" class="pur-input mt-1" placeholder="https://shopee.ph/shop/...">
        </div>

        <div>
            <label class="text-xs font-medium text-gray-500">Seller / Store ID</label>
            <input type="text" name="seller_id" class="pur-input mt-1" placeholder="Platform seller or store ID">
        </div>

        <div>
            <label class="text-xs font-medium text-gray-500">Operating Hours</label>
            <input type="text" name="operating_hours" value="{{ old('operating_hours') }}" placeholder="e.g. Mon–Sun 8:00 AM – 10:00 PM" maxlength="255" class="pur-input mt-1">
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('purchaser.suppliers.index') }}" class="pur-btn-secondary">Cancel</a>
            <button type="submit" class="pur-btn-primary">Create Supplier</button>
        </div>
    </form>
</div>
@endsection
