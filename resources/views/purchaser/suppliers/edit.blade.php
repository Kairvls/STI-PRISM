@extends('layouts.purchaser-layout')

@section('page-title', 'Edit Supplier')
@section('page-subtitle', 'Update supplier information')

@section('content')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('purchaser.suppliers.update', $supplier->supplier_id) }}" class="space-y-6 bg-white p-6 rounded-lg border border-gray-200">
        @csrf
        @method('PUT')

        <input type="hidden" name="supplier_store_type" value="{{ $supplier->supplier_store_type }}">

        @if($supplier->supplier_store_type === 'Physical Store')
            <div>
                <label class="text-xs font-medium text-gray-500">Company Name</label>
                <input type="text" name="company_name" value="{{ $physical->company_name ?? '' }}" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" required>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                <div>
                    <label class="text-xs font-medium text-gray-500">Contact Person</label>
                    <input type="text" name="contact_person" value="{{ $physical->contact_person ?? '' }}" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500">Email</label>
                    <input type="email" name="email_address" value="{{ $physical->email_address ?? '' }}" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Contact Number</label>
                <input type="text" name="contact_number" value="{{ $physical->contact_number ?? '' }}" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Company Address</label>
                <textarea name="company_address" rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ $physical->company_address ?? '' }}</textarea>
            </div>
        @else
            <div>
                <label class="text-xs font-medium text-gray-500">App Used</label>
                <input type="text" name="app_used" value="{{ $online->app_used ?? '' }}" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" required>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Shop Name</label>
                <input type="text" name="shop_name" value="{{ $online->shop_name ?? '' }}" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" required>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Order ID (optional)</label>
                <input type="text" name="order_id" value="{{ $online->order_id ?? '' }}" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
            </div>
        @endif

        <div class="flex justify-end gap-2">
            <a href="{{ route('purchaser.suppliers.index') }}" class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Cancel</a>
            <button type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white">Save Changes</button>
        </div>
    </form>
</div>
@endsection
