@extends('layouts.purchaser-layout')

@section('page-title', 'Suppliers')
@section('page-subtitle', 'Manage Supplier Records')

@section('content')

<div
    x-data="{
        openModal: null,
        addSupplierModal: {{ $errors->any() ? 'true' : 'false' }},
        supplierType: @js(old('supplier_store_type', 'Physical Store'))
    }"
>

    {{-- ========================================================= --}}
    {{-- ALERT MESSAGES --}}
    {{-- ========================================================= --}}

    @if(session('success'))
        <div class="pur-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="pur-alert-error">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="pur-alert-error">
            <p class="font-medium">Please fix the following supplier form errors:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <div class="mb-7 flex flex-wrap justify-end gap-2">
        <button
            type="button"
            x-on:click="addSupplierModal = true; $nextTick(() => window.lucide && window.lucide.createIcons())"
            class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            Add Supplier
        </button>
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
        <a href="{{ route('purchaser.suppliers.index') }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Suppliers</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($supplierSummary['total'] ?? 0) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <i data-lucide="building-2" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>All registered suppliers</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a href="{{ route('purchaser.suppliers.index', ['status' => 'Active']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Active</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($supplierSummary['active'] ?? 0) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <i data-lucide="circle-check-big" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Available for procurement</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a href="{{ route('purchaser.suppliers.index', ['status' => 'Inactive']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Inactive</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($supplierSummary['inactive'] ?? 0) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                    <i data-lucide="circle-pause" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Temporarily unavailable</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a href="{{ route('purchaser.suppliers.index', ['blacklisted' => 'Yes']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Blacklisted</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($supplierSummary['blacklisted'] ?? 0) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <i data-lucide="shield-alert" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Flagged with caution</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>
    </div>

    {{-- SUPPLIER RECORDS --}}
    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Supplier Records</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            {{ isset($suppliers) ? $suppliers->total() : 0 }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">View and manage registered procurement suppliers.</p>
                </div>

                <form
                    method="GET"
                    action="{{ route('purchaser.suppliers.index') }}"
                    class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center"
                >
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search suppliers..."
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>

                    <select name="type" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All Types</option>
                        <option value="Physical Store" {{ request('type') === 'Physical Store' ? 'selected' : '' }}>Physical Store</option>
                        <option value="Online Store" {{ request('type') === 'Online Store' ? 'selected' : '' }}>Online Store</option>
                    </select>

                    <select name="status" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All Statuses</option>
                        @foreach(['Active', 'Inactive'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>

                    <select name="blacklisted" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">Blacklist: All</option>
                        <option value="Yes" {{ request('blacklisted') === 'Yes' ? 'selected' : '' }}>Blacklisted</option>
                        <option value="No" {{ request('blacklisted') === 'No' ? 'selected' : '' }}>Not blacklisted</option>
                    </select>

                    <button
                        type="submit"
                        class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold leading-none text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="filter" class="h-4 w-4 shrink-0"></i>
                        Apply
                    </button>

                    @if(request()->filled('search') || request()->filled('type') || request()->filled('status') || request()->filled('blacklisted'))
                        <a
                            href="{{ route('purchaser.suppliers.index') }}"
                            class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50"
                        >
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Type</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Contact</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Added</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($suppliers ?? [] as $supplier)
                        @php
                            $isActive = (int) ($supplier->supplier_is_active ?? 1) === 1;
                            $isBlacklisted = (int) ($supplier->supplier_is_blacklisted ?? 0) === 1;
                            $supplierStatus = $isActive ? 'Active' : 'Inactive';
                            $supplierName = $supplier->company_name ?? $supplier->shop_name ?? 'Unnamed Supplier';
                            $contactPerson = $supplier->contact_person ?? 'Not specified';
                            $contactNumber = $supplier->contact_number ?? 'No contact number';
                            $statusClass = $isActive ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600';
                        @endphp

                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="building-2" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $supplierName }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <p class="text-xs text-gray-400">Supplier #{{ $supplier->supplier_id }}</p>
                                            @if($isBlacklisted)
                                                <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800" title="{{ $supplier->supplier_blacklist_reason }}">
                                                    Blacklisted
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-600">
                                    {{ $supplier->supplier_store_type }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-700">{{ $contactPerson }}</p>
                                <p class="mt-1 text-xs text-gray-400">{{ $contactNumber }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col items-start gap-1">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                        {{ $supplierStatus }}
                                    </span>
                                    @if($isBlacklisted)
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">
                                            Blacklisted
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @if($supplier->supplier_created_at)
                                    <p class="text-sm text-gray-700">{{ \Carbon\Carbon::parse($supplier->supplier_created_at)->format('M d, Y') }}</p>
                                    <p class="mt-1 text-xs text-gray-400">{{ \Carbon\Carbon::parse($supplier->supplier_created_at)->format('h:i A') }}</p>
                                @else
                                    <span class="text-xs text-gray-400">Not available</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button
                                    type="button"
                                    x-on:click="openModal = 'supplier-{{ $supplier->supplier_id }}'"
                                    class="group inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-950"
                                >
                                    View
                                    <i data-lucide="chevron-right" class="h-4 w-4 transition-transform group-hover:translate-x-0.5"></i>
                                </button>
                            </td>
                        </tr>

                        {{-- VIEW SUPPLIER MODAL --}}
                        <div
                            x-cloak
                            x-show="openModal === 'supplier-{{ $supplier->supplier_id }}'"
                            x-on:keydown.escape.window="openModal = null"
                            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
                        >
                            <div x-on:click.self="openModal = null" class="flex min-h-full w-full justify-center">
                                <div class="my-auto w-full max-w-5xl overflow-hidden rounded-xl bg-white shadow-2xl">
                                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-5">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <h3 class="text-lg font-semibold text-gray-950">{{ $supplierName }}</h3>
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">{{ $supplierStatus }}</span>
                                                @if($isBlacklisted)
                                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800" title="{{ $supplier->supplier_blacklist_reason }}">
                                                        Blacklisted
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-sm text-gray-500">Supplier Record #{{ $supplier->supplier_id }}</p>
                                        </div>
                                        <button
                                            type="button"
                                            x-on:click="openModal = null"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-50 hover:text-gray-900"
                                            aria-label="Close"
                                        >
                                            <i data-lucide="x" class="h-4 w-4"></i>
                                        </button>
                                    </div>

                                    <div class="max-h-[75vh] overflow-y-auto p-6">
                                        <div>
                                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Supplier Information</h4>
                                            @if($isBlacklisted)
                                                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                                    <p class="font-medium">Blacklisted warning</p>
                                                    <p class="mt-1">{{ $supplier->supplier_blacklist_reason ?: 'This supplier is marked as not recommended.' }}</p>
                                                </div>
                                            @endif

                                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4">
                                                    <p class="text-xs text-gray-400">Supplier Type</p>
                                                    <p class="mt-1 font-medium text-gray-900">{{ $supplier->supplier_store_type }}</p>
                                                </div>
                                                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4">
                                                    <p class="text-xs text-gray-400">Company / Shop</p>
                                                    <p class="mt-1 font-medium text-gray-900">{{ $supplierName }}</p>
                                                </div>
                                                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4">
                                                    <p class="text-xs text-gray-400">Status</p>
                                                    <p class="mt-1 font-medium text-gray-900">{{ $supplierStatus }}</p>
                                                </div>
                                                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4">
                                                    <p class="text-xs text-gray-400">Contact Person</p>
                                                    <p class="mt-1 font-medium text-gray-900">{{ $supplier->contact_person ?? 'Not specified' }}</p>
                                                </div>
                                                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4">
                                                    <p class="text-xs text-gray-400">Contact Number</p>
                                                    <p class="mt-1 font-medium text-gray-900">{{ $supplier->contact_number ?? 'Not specified' }}</p>
                                                </div>
                                                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4">
                                                    <p class="text-xs text-gray-400">Email Address</p>
                                                    <p class="mt-1 break-all font-medium text-gray-900">{{ $supplier->email_address ?? 'Not specified' }}</p>
                                                </div>
                                            </div>

                                            @if($supplier->company_address)
                                                <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50/50 p-4">
                                                    <p class="text-xs text-gray-400">Company Address</p>
                                                    <p class="mt-1 text-sm leading-6 text-gray-700">{{ $supplier->company_address }}</p>
                                                </div>
                                            @endif
                                        </div>


                                        {{-- ONLINE INFORMATION --}}
                                        @if($supplier->app_used || $supplier->shop_name)

                                            <div class="mt-8">

                                                <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                                    Online Information
                                                </h4>


                                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">

                                                    <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4">
                                                        <p class="text-xs text-gray-400">Platform</p>
                                                        <p class="mt-1 font-medium text-gray-900">{{ $supplier->app_used ?? 'Not specified' }}</p>
                                                    </div>
                                                    <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4">
                                                        <p class="text-xs text-gray-400">Shop Name</p>
                                                        <p class="mt-1 font-medium text-gray-900">{{ $supplier->shop_name ?? 'Not specified' }}</p>
                                                    </div>

                                                </div>

                                            </div>

                                        @endif

                                    </div>


                                    {{-- MODAL FOOTER --}}
                                    <div class="flex flex-wrap justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">

                                        <button
                                            type="button"
                                            x-on:click="openModal = null"
                                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                                        >
                                            Close
                                        </button>

                                        <a
                                            href="{{ route('purchaser.suppliers.show', $supplier->supplier_id) }}"
                                            class="pur-btn-secondary"
                                        >
                                            Notes &amp; History
                                        </a>

                                        <a
                                            href="{{ route('purchaser.suppliers.edit', $supplier->supplier_id) }}"
                                            class="pur-btn-primary"
                                        >
                                            Edit Supplier
                                        </a>

                                    </div>

                                </div>
                            </div>
                        </div>

                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-400">
                                    <i data-lucide="building-2" class="h-5 w-5"></i>
                                </div>
                                <p class="mt-4 font-medium text-gray-700">No suppliers found</p>
                                <p class="mt-1 text-sm text-gray-400">Add a supplier or adjust the current filters.</p>
                            </td>
                        </tr>
                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- ===================================================== --}}
        {{-- PAGINATION --}}
        {{-- ===================================================== --}}

        @if(isset($suppliers) && $suppliers->hasPages())

            <div class="border-t border-gray-100 px-5 py-4">
                {{ $suppliers->withQueryString()->links() }}
            </div>

        @endif

    </div>


    {{-- ADD SUPPLIER MODAL --}}
    <div
        x-cloak
        x-show="addSupplierModal"
        x-transition.opacity
        x-on:keydown.escape.window="addSupplierModal = false"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
        role="dialog"
        aria-modal="true"
        aria-labelledby="add-supplier-title"
    >
        <div
            x-on:click.self="addSupplierModal = false"
            class="flex min-h-full w-full justify-center"
        >
            <div class="my-auto flex h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                                <i data-lucide="building-2" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 id="add-supplier-title" class="text-lg font-semibold tracking-tight text-gray-950">
                                    Add Supplier
                                </h3>
                                <p class="mt-0.5 text-sm text-gray-500">Register a new procurement supplier.</p>
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        x-on:click="addSupplierModal = false"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('purchaser.suppliers.store') }}" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    <input type="hidden" name="supplier_store_type" x-bind:value="supplierType">

                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5 md:px-6">
                        {{-- TYPE SELECTOR --}}
                        <div>
                            <p class="mb-2 text-sm font-medium text-gray-700">Supplier Type <span class="text-red-500">*</span></p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <button
                                    type="button"
                                    x-on:click="supplierType = 'Physical Store'; $nextTick(() => window.lucide && window.lucide.createIcons())"
                                    class="flex items-start gap-3 rounded-xl border p-4 text-left transition"
                                    :class="supplierType === 'Physical Store'
                                        ? 'border-slate-900 bg-slate-900 text-white shadow-sm'
                                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'"
                                >
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                        :class="supplierType === 'Physical Store' ? 'bg-white/15 text-white' : 'bg-gray-100 text-gray-600'"
                                    >
                                        <i data-lucide="store" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold">Physical Store</p>
                                        <p class="mt-0.5 text-xs" :class="supplierType === 'Physical Store' ? 'text-white/70' : 'text-gray-500'">
                                            Walk-in shop with a street address
                                        </p>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    x-on:click="supplierType = 'Online Store'; $nextTick(() => window.lucide && window.lucide.createIcons())"
                                    class="flex items-start gap-3 rounded-xl border p-4 text-left transition"
                                    :class="supplierType === 'Online Store'
                                        ? 'border-slate-900 bg-slate-900 text-white shadow-sm'
                                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'"
                                >
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                        :class="supplierType === 'Online Store' ? 'bg-white/15 text-white' : 'bg-gray-100 text-gray-600'"
                                    >
                                        <i data-lucide="globe" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold">Online Store</p>
                                        <p class="mt-0.5 text-xs" :class="supplierType === 'Online Store' ? 'text-white/70' : 'text-gray-500'">
                                            Marketplace or website seller
                                        </p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        {{-- CONTACT DETAILS --}}
                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                            <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/80 px-4 py-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-gray-200">
                                    <i data-lucide="id-card" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Supplier details</p>
                                    <p class="text-xs text-gray-500">Name and contact information</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">
                                        Company / Shop Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="supplier_name"
                                        value="{{ old('supplier_name', old('company_name', old('shop_name'))) }}"
                                        required
                                        placeholder="Enter supplier name"
                                        class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                    >
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Contact Person</label>
                                    <input
                                        type="text"
                                        name="contact_person"
                                        value="{{ old('contact_person') }}"
                                        placeholder="Enter contact person"
                                        class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                    >
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Contact Number</label>
                                    <input
                                        type="text"
                                        name="contact_number"
                                        value="{{ old('contact_number') }}"
                                        placeholder="Enter contact number"
                                        class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                    >
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Email Address</label>
                                    <input
                                        type="email"
                                        name="email_address"
                                        value="{{ old('email_address') }}"
                                        placeholder="name@example.com"
                                        class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                    >
                                </div>
                            </div>
                        </div>

                        {{-- PHYSICAL LOCATION --}}
                        <div
                            x-show="supplierType === 'Physical Store'"
                            x-cloak
                            class="overflow-hidden rounded-2xl border border-gray-200 bg-white"
                        >
                            <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/80 px-4 py-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-gray-200">
                                    <i data-lucide="map-pin" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Physical location</p>
                                    <p class="text-xs text-gray-500">Where this store can be found</p>
                                </div>
                            </div>
                            <div class="p-4">
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Company Address</label>
                                <textarea
                                    name="company_address"
                                    rows="3"
                                    placeholder="Street, barangay, city..."
                                    class="box-border w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                    x-bind:disabled="supplierType !== 'Physical Store'"
                                >{{ old('company_address') }}</textarea>
                            </div>
                        </div>

                        {{-- ONLINE INFORMATION --}}
                        <div
                            x-show="supplierType === 'Online Store'"
                            x-cloak
                            class="overflow-hidden rounded-2xl border border-gray-200 bg-white"
                        >
                            <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/80 px-4 py-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-gray-200">
                                    <i data-lucide="shopping-bag" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Online platform</p>
                                    <p class="text-xs text-gray-500">Where this supplier sells online</p>
                                </div>
                            </div>
                            <div class="p-4">
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">
                                    Primary Platform <span class="text-red-500">*</span>
                                </label>
                                <select
                                    name="app_used"
                                    class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition focus:border-gray-300 focus:bg-white"
                                    x-bind:required="supplierType === 'Online Store'"
                                    x-bind:disabled="supplierType !== 'Online Store'"
                                >
                                    <option value="">Select platform</option>
                                    @foreach(['Shopee', 'Lazada', 'Facebook', 'TikTok Shop', 'Website', 'Other'] as $platform)
                                        <option value="{{ $platform }}" @selected(old('app_used') === $platform)>
                                            {{ $platform }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 md:px-6">
                        <button
                            type="button"
                            x-on:click="addSupplierModal = false"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
                        >
                            <i data-lucide="check" class="h-4 w-4"></i>
                            Save Supplier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection
