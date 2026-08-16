@extends('layouts.purchaser-layout')

@section('page-title', 'New Physical Supplier')
@section('page-subtitle', 'Create physical supplier')

@section('content')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('purchaser.suppliers.store') }}" class="pur-card space-y-6 p-6">
        @csrf
        <input type="hidden" name="supplier_store_type" value="Physical Store">

        <div>
            <label class="text-xs font-medium text-gray-500">Company Name</label>
            <input type="text" name="company_name" class="pur-input mt-1" required>
        </div>

        <div class="grid gap-3 lg:grid-cols-2">
            <div>
                <label class="text-xs font-medium text-gray-500">Contact Person</label>
                <input type="text" name="contact_person" class="pur-input mt-1">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-500">Email</label>
                <input type="email" name="email_address" class="pur-input mt-1">
            </div>
        </div>

        <div>
            <label class="text-xs font-medium text-gray-500">Contact Number</label>
            <input type="text" name="contact_number" class="pur-input mt-1">
        </div>

        <div>
            <label class="text-xs font-medium text-gray-500">Company Address</label>
            <textarea name="company_address" rows="4" class="pur-input mt-1"></textarea>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('purchaser.suppliers.index') }}" class="pur-btn-secondary">Cancel</a>
            <button type="submit" class="pur-btn-primary">Create Supplier</button>
        </div>
    </form>
</div>
@endsection
