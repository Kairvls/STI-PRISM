@extends('layouts.purchaser-layout')

@section('page-title', 'New Online Supplier')
@section('page-subtitle', 'Create online supplier')

@section('content')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('purchaser.suppliers.store') }}" class="space-y-6 bg-white p-6 rounded-lg border border-gray-200">
        @csrf
        <input type="hidden" name="supplier_store_type" value="Online Store">

        <div>
            <label class="text-xs font-medium text-gray-500">App Used</label>
            <input type="text" name="app_used" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" required>
        </div>

        <div>
            <label class="text-xs font-medium text-gray-500">Shop Name</label>
            <input type="text" name="shop_name" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" required>
        </div>

        <div>
            <label class="text-xs font-medium text-gray-500">Order ID (optional)</label>
            <input type="text" name="order_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('purchaser.suppliers.index') }}" class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Cancel</a>
            <button type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white">Create Supplier</button>
        </div>
    </form>
</div>
@endsection
