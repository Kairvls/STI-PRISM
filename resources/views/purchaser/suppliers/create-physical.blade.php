@extends('layouts.purchaser-layout')

@section('page-title', 'New Physical Supplier')
@section('page-subtitle', 'Create physical supplier')

@section('content')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('purchaser.suppliers.store') }}" class="space-y-6 bg-white p-6 rounded-lg border border-gray-200">
        @csrf
        <input type="hidden" name="supplier_store_type" value="Physical Store">

        <div>
            <label class="text-xs font-medium text-gray-500">Company Name</label>
            <input type="text" name="company_name" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" required>
        </div>

        <div class="grid gap-3 lg:grid-cols-2">
            <div>
                <label class="text-xs font-medium text-gray-500">Contact Person</label>
                <input type="text" name="contact_person" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-500">Email</label>
                <input type="email" name="email_address" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
            </div>
        </div>

        <div>
            <label class="text-xs font-medium text-gray-500">Contact Number</label>
            <input type="text" name="contact_number" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
        </div>

        <div>
            <label class="text-xs font-medium text-gray-500">Company Address</label>
            <textarea name="company_address" rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('purchaser.suppliers.index') }}" class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Cancel</a>
            <button type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white">Create Supplier</button>
        </div>
    </form>
</div>
@endsection
