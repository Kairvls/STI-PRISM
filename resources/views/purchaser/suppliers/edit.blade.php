@extends('layouts.purchaser-layout')

@section('page-title', 'Edit Supplier')
@section('page-subtitle', 'Update supplier information')

@section('content')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('purchaser.suppliers.update', $supplier->supplier_id) }}" class="pur-card space-y-6 p-6">
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

            <div>
                <label class="text-xs font-medium text-gray-500">Contact Number</label>
                <input type="text" name="contact_number" value="{{ $physical->contact_number ?? '' }}" class="pur-input mt-1">
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Company Address</label>
                <textarea name="company_address" rows="4" class="pur-input mt-1">{{ $physical->company_address ?? '' }}</textarea>
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

            <div>
                <label class="text-xs font-medium text-gray-500">Order ID (optional)</label>
                <input type="text" name="order_id" value="{{ $online->order_id ?? '' }}" class="pur-input mt-1">
            </div>
        @endif

        <div class="flex justify-end gap-2">
            <a href="{{ route('purchaser.suppliers.index') }}" class="pur-btn-secondary">Cancel</a>
            <button type="submit" class="pur-btn-primary">Save Changes</button>
        </div>
    </form>
</div>
@endsection
