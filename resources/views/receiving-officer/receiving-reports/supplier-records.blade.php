@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Supplier Records</h1>
        <p class="admin-page-subtitle">Suppliers linked to purchases and accepted deliveries.</p>
    </div>

    @include('layouts.partials.receiving-query-error')

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4">
            @include('layouts.partials.receiving-filters', ['placeholder' => 'Search supplier, contact, type...'])
        </div>
        <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
        @forelse($suppliers as $supplier)
            <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-5">
                <h2 class="text-sm font-semibold text-gray-900">{{ $supplier->supplier_name }}</h2>
                <p class="mt-1 text-xs text-gray-500">{{ $supplier->supplier_store_type }}{{ $supplier->company_address ? ' · '.$supplier->company_address : '' }}</p>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div><p class="text-xs text-gray-400">Contact</p><p class="text-gray-800">{{ $supplier->contact_person ?: '—' }}</p></div>
                    <div><p class="text-xs text-gray-400">Phone</p><p class="text-gray-800">{{ $supplier->contact_number ?: '—' }}</p></div>
                    <div><p class="text-xs text-gray-400">Accepted deliveries</p><p class="text-gray-800">{{ $supplier->delivery_count }}</p></div>
                    <div><p class="text-xs text-gray-400">Last delivery</p><p class="text-gray-800">{{ $supplier->last_delivery ? \Carbon\Carbon::parse($supplier->last_delivery)->format('M d, Y') : '—' }}</p></div>
                </dl>
            </div>
        @empty
            <p class="col-span-2 py-10 text-center text-sm text-gray-400">No suppliers match this filter, or none have been recorded yet.</p>
        @endforelse
        </div>
    </div>
</div>

@endsection
