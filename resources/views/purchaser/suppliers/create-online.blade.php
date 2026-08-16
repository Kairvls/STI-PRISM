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

        <div>
            <label class="text-xs font-medium text-gray-500">Order ID (optional)</label>
            <input type="text" name="order_id" class="pur-input mt-1">
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('purchaser.suppliers.index') }}" class="pur-btn-secondary">Cancel</a>
            <button type="submit" class="pur-btn-primary">Create Supplier</button>
        </div>
    </form>
</div>
@endsection
