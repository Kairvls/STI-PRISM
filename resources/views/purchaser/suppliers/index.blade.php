@extends('layouts.purchaser-layout')

@section('page-title', 'Suppliers')
@section('page-subtitle', 'Manage physical and online suppliers')

@section('content')
<div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-medium">Please fix the following supplier form errors:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" class="grid flex-1 gap-3 md:grid-cols-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search suppliers" class="h-10 rounded-lg border border-gray-300 px-3 text-sm md:col-span-2">

            {{-- ADDED SUPPLIERS MODULE: supplier type filter. --}}
            <select name="type" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
                <option value="">All Types</option>
                <option value="Physical Store" {{ request('type') === 'Physical Store' ? 'selected' : '' }}>Physical Store</option>
                <option value="Online Store" {{ request('type') === 'Online Store' ? 'selected' : '' }}>Online Store</option>
            </select>

            {{-- ADDED SUPPLIERS MODULE: supplier status filter. --}}
            <select name="status" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
                <option value="">All Statuses</option>
                <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
            </select>

            <div class="flex gap-2 md:col-span-4">
                <button type="submit" class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white">Search</button>
                <a href="{{ route('purchaser.suppliers.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Reset</a>
            </div>
        </form>

        <div class="flex gap-2">
            <a href="{{ route('purchaser.suppliers.create', ['type' => 'Physical']) }}" class="inline-flex h-10 items-center rounded-lg bg-gray-900 px-5 text-sm font-medium text-white">New Physical</a>
            <a href="{{ route('purchaser.suppliers.create', ['type' => 'Online']) }}" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-5 text-sm font-medium text-white">New Online</a>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px]">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Contact</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td class="px-4 py-4 text-sm">#{{ $supplier->supplier_id }}</td>
                            <td class="px-4 py-4 text-sm">{{ $supplier->supplier_store_type }}</td>
                            <td class="px-4 py-4 text-sm">
                                @if($supplier->supplier_store_type === 'Physical Store')
                                    {{ $supplier->company_name ?? '-' }}
                                @else
                                    {{ $supplier->shop_name ?? '-' }}
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm">
                                @if($supplier->supplier_store_type === 'Physical Store')
                                    {{ $supplier->contact_number ?? 'No contact number' }}
                                @else
                                    {{ $supplier->app_used ?? 'No app listed' }}
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm">
                                @if($supplier->supplier_is_active == 0)
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Inactive</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <a href="{{ route('purchaser.suppliers.show', $supplier->supplier_id) }}" class="rounded-lg border border-blue-600 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-600">View</a>
                                <a href="{{ route('purchaser.suppliers.edit', $supplier->supplier_id) }}" class="ml-2 rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700">Edit</a>
                                @if($supplier->supplier_is_active == 0)
                                    <form method="POST" action="{{ route('purchaser.suppliers.activate', $supplier->supplier_id) }}" class="inline-block">
                                        @csrf
                                        <button type="submit" class="ml-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white">Reactivate</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('purchaser.suppliers.deactivate', $supplier->supplier_id) }}" class="inline-block">
                                        @csrf
                                        <button type="submit" class="ml-2 rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white">Deactivate</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">No suppliers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">
        {{ $suppliers->links() }}
    </div>

</div>
@endsection
