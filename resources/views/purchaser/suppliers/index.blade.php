@extends('layouts.purchaser-layout')

@section('page-title', 'Suppliers')
@section('page-subtitle', 'Manage Supplier Records')

@section('content')

<div
    x-data="{
        openModal: null,
        editModal: @js(old('_editing_supplier_id') ? 'edit-'.old('_editing_supplier_id') : null),
        addSupplierModal: {{ $errors->any() && !old('_editing_supplier_id') ? 'true' : 'false' }},
        supplierType: @js(old('supplier_store_type', 'Physical Store'))
    }"
    x-effect="
        if (editModal) {
            $nextTick(() => {
                window.lucide && window.lucide.createIcons();
                const id = String(editModal).replace('edit-', '');
                const phone = document.getElementById('edit-modal-contact-' + id);
                if (phone && window.refreshPrismPhoneInput) window.refreshPrismPhoneInput(phone);
            });
        }
    "
>

    <div class="mb-7 flex flex-wrap justify-end gap-2">
        <button
            type="button"
            x-on:click="addSupplierModal = true; $nextTick(() => { window.lucide && window.lucide.createIcons(); const phone = document.getElementById('supplier-contact-number'); if (phone && window.refreshPrismPhoneInput) window.refreshPrismPhoneInput(phone); })"
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
                            $contactNumber = \App\Support\PhoneNumber::formatForDisplay($supplier->contact_number);
                            $landlineNumber = $supplier->supplier_store_type === 'Physical Store'
                                ? \App\Support\PhoneNumber::formatLandlineForDisplay($supplier->landline_number ?? null)
                                : null;
                            $statusClass = $isActive ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600';
                            $supplierCode = $supplier->supplier_code
                                ?: \App\Support\SupplierCode::generate(
                                    (string) $supplier->supplier_store_type,
                                    $supplierName,
                                    $supplier->supplier_created_at
                                );
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
                                            <p class="font-mono text-xs text-gray-400">{{ $supplierCode }}</p>
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
                                @if($contactNumber)
                                    <button
                                        type="button"
                                        class="mt-1 inline-flex max-w-full items-center gap-1 rounded text-left text-xs font-medium text-gray-600 transition hover:text-[#0025cc] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0025cc]/30"
                                        title="Click to copy mobile number"
                                        aria-label="Copy mobile number {{ $contactNumber }}"
                                        x-data="{ copied: false }"
                                        x-on:click="navigator.clipboard.writeText(@js($contactNumber)).then(() => { copied = true; setTimeout(() => copied = false, 1200); }).catch(() => {})"
                                    >
                                        <span class="select-all" x-text="copied ? 'Copied!' : @js($contactNumber)"></span>
                                    </button>
                                @else
                                    <p class="mt-1 text-xs text-gray-400">No mobile number</p>
                                @endif
                                @if($landlineNumber)
                                    <button
                                        type="button"
                                        class="mt-0.5 inline-flex max-w-full items-center gap-1 rounded text-left text-xs text-gray-500 transition hover:text-[#0025cc] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0025cc]/30"
                                        title="Click to copy landline"
                                        aria-label="Copy landline {{ $landlineNumber }}"
                                        x-data="{ copied: false }"
                                        x-on:click="navigator.clipboard.writeText(@js($landlineNumber)).then(() => { copied = true; setTimeout(() => copied = false, 1200); }).catch(() => {})"
                                    >
                                        <span class="select-all" x-text="copied ? 'Copied!' : @js('Landline: '.$landlineNumber)"></span>
                                    </button>
                                @elseif($supplier->supplier_store_type === 'Physical Store')
                                    <p class="mt-0.5 text-xs text-gray-400">No landline</p>
                                @endif
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
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        x-on:click="editModal = null; openModal = 'supplier-{{ $supplier->supplier_id }}'"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                                        title="View supplier"
                                        aria-label="View supplier"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                    <button
                                        type="button"
                                        x-on:click="openModal = null; editModal = 'edit-{{ $supplier->supplier_id }}'; $nextTick(() => { window.lucide && window.lucide.createIcons(); const phone = document.getElementById('edit-modal-contact-{{ $supplier->supplier_id }}'); if (phone && window.refreshPrismPhoneInput) window.refreshPrismPhoneInput(phone); })"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001fa8]"
                                        title="Edit supplier"
                                        aria-label="Edit supplier"
                                    >
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        {{-- VIEW SUPPLIER MODAL (teleported out of <tbody> so forms/inputs stay valid) --}}
                        <template x-teleport="body">
                        <div
                            x-cloak
                            x-show="openModal === 'supplier-{{ $supplier->supplier_id }}'"
                            x-on:keydown.escape.window="if (openModal === 'supplier-{{ $supplier->supplier_id }}') openModal = null"
                            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/40 p-4 backdrop-blur-[2px] md:p-8"
                            role="dialog"
                            aria-modal="true"
                        >
                            <div x-on:click.self="openModal = null" class="flex min-h-full w-full justify-center">
                                <div class="my-auto w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-[0_24px_64px_rgba(15,23,42,0.14)]">
                                    <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-5 md:px-8">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2.5">
                                                <h3 class="truncate text-xl font-semibold tracking-tight text-slate-950">{{ $supplierName }}</h3>
                                                <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $statusClass }}">{{ $supplierStatus }}</span>
                                                @if($isBlacklisted)
                                                    <span class="inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800" title="{{ $supplier->supplier_blacklist_reason }}">
                                                        Blacklisted
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="mt-1 font-mono text-sm text-slate-500">{{ $supplierCode }}</p>
                                        </div>
                                        <button
                                            type="button"
                                            x-on:click="openModal = null"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-50 hover:text-slate-900"
                                            aria-label="Close"
                                        >
                                            <i data-lucide="x" class="h-4 w-4"></i>
                                        </button>
                                    </div>

                                    <div class="max-h-[70vh] overflow-y-auto px-6 pb-2 md:px-8">
                                        @if($isBlacklisted)
                                            <div class="mb-6 rounded-xl border border-amber-200/80 bg-amber-50/70 px-4 py-3 text-sm text-amber-900">
                                                <p class="font-medium">Blacklisted warning</p>
                                                <p class="mt-1 text-amber-800/90">{{ $supplier->supplier_blacklist_reason ?: 'This supplier is marked as not recommended.' }}</p>
                                            </div>
                                        @endif

                                        <section>
                                            <h4 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Supplier information</h4>
                                            <dl class="mt-4 divide-y divide-slate-100">
                                                <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6">
                                                    <dt class="text-sm text-slate-500">Supplier type</dt>
                                                    <dd class="text-sm font-medium text-slate-900">{{ $supplier->supplier_store_type }}</dd>
                                                </div>
                                                <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6">
                                                    <dt class="text-sm text-slate-500">Company / Shop</dt>
                                                    <dd class="text-sm font-medium text-slate-900">{{ $supplierName }}</dd>
                                                </div>
                                                <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6">
                                                    <dt class="text-sm text-slate-500">Status</dt>
                                                    <dd class="text-sm font-medium text-slate-900">{{ $supplierStatus }}</dd>
                                                </div>
                                                <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6">
                                                    <dt class="text-sm text-slate-500">Contact person</dt>
                                                    <dd class="text-sm font-medium text-slate-900">{{ $supplier->contact_person ?? '—' }}</dd>
                                                </div>
                                                <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6">
                                                    <dt class="text-sm text-slate-500">Operating hours</dt>
                                                    <dd class="text-sm font-medium text-slate-900">{{ $supplier->operating_hours ?? '—' }}</dd>
                                                </div>
                                                <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6">
                                                    <dt class="text-sm text-slate-500">Contact number</dt>
                                                    <dd class="text-sm font-medium text-slate-900">{{ \App\Support\PhoneNumber::formatForDisplay($supplier->contact_number) ?? '—' }}</dd>
                                                </div>
                                                @if($supplier->supplier_store_type === 'Physical Store')
                                                    <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6">
                                                        <dt class="text-sm text-slate-500">Landline</dt>
                                                        <dd class="text-sm font-medium text-slate-900">{{ \App\Support\PhoneNumber::formatLandlineForDisplay($supplier->landline_number) ?? '—' }}</dd>
                                                    </div>
                                                @endif
                                                <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6">
                                                    <dt class="text-sm text-slate-500">Email</dt>
                                                    <dd class="break-all text-sm font-medium text-slate-900">{{ $supplier->email_address ?? '—' }}</dd>
                                                </div>
                                                @if($supplier->company_address)
                                                    <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6">
                                                        <dt class="text-sm text-slate-500">Address</dt>
                                                        <dd class="text-sm leading-6 text-slate-700">{{ $supplier->company_address }}</dd>
                                                    </div>
                                                @endif
                                            </dl>
                                        </section>

                                        @if($supplier->app_used || $supplier->shop_name)
                                            <section class="mt-8 border-t border-slate-100 pt-6">
                                                <h4 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Online information</h4>
                                                <dl class="mt-4 divide-y divide-slate-100">
                                                    <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6">
                                                        <dt class="text-sm text-slate-500">Platform</dt>
                                                        <dd class="text-sm font-medium text-slate-900">{{ $supplier->app_used ?? '—' }}</dd>
                                                    </div>
                                                    <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6">
                                                        <dt class="text-sm text-slate-500">Shop name</dt>
                                                        <dd class="text-sm font-medium text-slate-900">{{ $supplier->shop_name ?? '—' }}</dd>
                                                    </div>
                                                    <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6">
                                                        <dt class="text-sm text-slate-500">Store URL</dt>
                                                        <dd class="text-sm font-medium text-slate-900">
                                                            @if(!empty($supplier->store_url))
                                                                <a href="{{ $supplier->store_url }}" target="_blank" rel="noopener noreferrer" class="break-all text-[#0025cc] hover:underline">{{ $supplier->store_url }}</a>
                                                            @else
                                                                —
                                                            @endif
                                                        </dd>
                                                    </div>
                                                    <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-6">
                                                        <dt class="text-sm text-slate-500">Seller / Store ID</dt>
                                                        <dd class="text-sm font-medium text-slate-900">{{ $supplier->seller_id ?? '—' }}</dd>
                                                    </div>
                                                </dl>
                                            </section>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap items-center justify-end gap-2 px-6 py-5 md:px-8">
                                        <button
                                            type="button"
                                            x-on:click="openModal = null"
                                            class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                        >
                                            Close
                                        </button>

                                        <a
                                            href="{{ route('purchaser.suppliers.show', $supplier->supplier_id) }}"
                                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                                        >
                                            Notes &amp; History
                                        </a>

                                        <a
                                            href="{{ route('purchaser.suppliers.edit', $supplier->supplier_id) }}"
                                            class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#001fa8]"
                                        >
                                            Edit Supplier
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </template>

                        {{-- EDIT SUPPLIER MODAL (teleported out of <tbody> so phone fields submit correctly) --}}
                        @php
                            $isEditingThis = (string) old('_editing_supplier_id') === (string) $supplier->supplier_id;
                            $editName = $supplier->supplier_store_type === 'Physical Store'
                                ? ($isEditingThis ? old('company_name', $supplier->company_name) : ($supplier->company_name ?? ''))
                                : ($isEditingThis ? old('shop_name', $supplier->shop_name) : ($supplier->shop_name ?? ''));
                            $editContactPerson = $isEditingThis ? old('contact_person', $supplier->contact_person) : ($supplier->contact_person ?? '');
                            $editEmail = $isEditingThis ? old('email_address', $supplier->email_address) : ($supplier->email_address ?? '');
                            $editContactNumber = $isEditingThis ? old('contact_number', $supplier->contact_number) : ($supplier->contact_number ?? '');
                            $editLandline = $isEditingThis ? old('landline_number', $supplier->landline_number) : ($supplier->landline_number ?? '');
                            $editAddress = $isEditingThis ? old('company_address', $supplier->company_address) : ($supplier->company_address ?? '');
                            $editOperatingHours = $isEditingThis ? old('operating_hours', $supplier->operating_hours) : ($supplier->operating_hours ?? '');
                            $editAppUsed = $isEditingThis ? old('app_used', $supplier->app_used) : ($supplier->app_used ?? '');
                            $editStoreUrl = $isEditingThis ? old('store_url', $supplier->store_url) : ($supplier->store_url ?? '');
                            $editSellerId = $isEditingThis ? old('seller_id', $supplier->seller_id) : ($supplier->seller_id ?? '');
                        @endphp
                        <template x-teleport="body">
                        <div
                            x-cloak
                            x-show="editModal === 'edit-{{ $supplier->supplier_id }}'"
                            x-on:keydown.escape.window="if (editModal === 'edit-{{ $supplier->supplier_id }}') editModal = null"
                            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/40 p-4 backdrop-blur-[2px] md:p-8"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="edit-supplier-title-{{ $supplier->supplier_id }}"
                        >
                            <div x-on:click.self="editModal = null" class="flex min-h-full w-full justify-center">
                                <div class="my-auto flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-[0_24px_64px_rgba(15,23,42,0.14)]">
                                    <div class="flex shrink-0 items-start justify-between gap-4 px-6 pt-6 pb-4 md:px-8">
                                        <div class="min-w-0">
                                            <h3 id="edit-supplier-title-{{ $supplier->supplier_id }}" class="text-xl font-semibold tracking-tight text-slate-950">
                                                Edit Supplier
                                            </h3>
                                            <p class="mt-1 font-mono text-sm text-slate-500">{{ $supplierCode }} · {{ $supplier->supplier_store_type }}</p>
                                        </div>
                                        <button
                                            type="button"
                                            x-on:click="editModal = null"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-50 hover:text-slate-900"
                                            aria-label="Close"
                                        >
                                            <i data-lucide="x" class="h-4 w-4"></i>
                                        </button>
                                    </div>

                                    <form
                                        method="POST"
                                        action="{{ route('purchaser.suppliers.update', $supplier->supplier_id) }}"
                                        class="flex min-h-0 flex-1 flex-col"
                                    >
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="supplier_store_type" value="{{ $supplier->supplier_store_type }}">
                                        <input type="hidden" name="_editing_supplier_id" value="{{ $supplier->supplier_id }}">

                                        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 pb-2 md:px-8">
                                            @if($supplier->supplier_store_type === 'Physical Store')
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-medium text-slate-500">Company Name <span class="text-red-500">*</span></label>
                                                    <input type="text" name="company_name" value="{{ $editName }}" required class="box-border h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-slate-300 focus:bg-white">
                                                </div>
                                                <div class="grid gap-4 sm:grid-cols-2">
                                                    <div>
                                                        <label class="mb-1.5 block text-xs font-medium text-slate-500">Contact Person</label>
                                                        <input type="text" name="contact_person" value="{{ $editContactPerson }}" class="box-border h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-slate-300 focus:bg-white">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1.5 block text-xs font-medium text-slate-500">Email</label>
                                                        <input type="email" name="email_address" value="{{ $editEmail }}" class="box-border h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-slate-300 focus:bg-white">
                                                    </div>
                                                </div>
                                                <div class="grid gap-4 sm:grid-cols-2">
                                                    <div>
                                                        <label class="mb-1.5 block text-xs font-medium text-slate-500">Contact Number</label>
                                                        @include('partials.phone-input', [
                                                            'name' => 'contact_number',
                                                            'value' => $editContactNumber,
                                                            'id' => 'edit-modal-contact-'.$supplier->supplier_id,
                                                            'placeholder' => '9XX XXX XXXX',
                                                        ])
                                                    </div>
                                                    <div>
                                                        <label class="mb-1.5 block text-xs font-medium text-slate-500">Landline Number</label>
                                                        @include('partials.landline-input', [
                                                            'name' => 'landline_number',
                                                            'value' => $editLandline,
                                                            'id' => 'edit-modal-landline-'.$supplier->supplier_id,
                                                            'placeholder' => '(0XX) XXX-XXXX',
                                                        ])
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-medium text-slate-500">Company Address</label>
                                                    <textarea name="company_address" rows="3" class="box-border w-full resize-none rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-slate-300 focus:bg-white">{{ $editAddress }}</textarea>
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-medium text-slate-500">Operating Hours</label>
                                                    <input type="text" name="operating_hours" value="{{ $editOperatingHours }}" placeholder="e.g. Mon–Fri 9:00 AM – 6:00 PM" maxlength="255" class="box-border h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-slate-300 focus:bg-white">
                                                </div>
                                            @else
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-medium text-slate-500">Shop Name <span class="text-red-500">*</span></label>
                                                    <input type="text" name="shop_name" value="{{ $editName }}" required class="box-border h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-slate-300 focus:bg-white">
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-medium text-slate-500">Primary Platform <span class="text-red-500">*</span></label>
                                                    <select name="app_used" required class="box-border h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-slate-300 focus:bg-white">
                                                        <option value="">Select platform</option>
                                                        @foreach(['Shopee', 'Lazada', 'Facebook', 'TikTok Shop', 'Website', 'Other'] as $platform)
                                                            <option value="{{ $platform }}" @selected($editAppUsed === $platform)>{{ $platform }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="grid gap-4 sm:grid-cols-2">
                                                    <div>
                                                        <label class="mb-1.5 block text-xs font-medium text-slate-500">Contact Person</label>
                                                        <input type="text" name="contact_person" value="{{ $editContactPerson }}" class="box-border h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-slate-300 focus:bg-white">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1.5 block text-xs font-medium text-slate-500">Contact Number</label>
                                                        @include('partials.phone-input', [
                                                            'name' => 'contact_number',
                                                            'value' => $editContactNumber,
                                                            'id' => 'edit-modal-contact-'.$supplier->supplier_id,
                                                            'placeholder' => '9XX XXX XXXX',
                                                        ])
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-medium text-slate-500">Email</label>
                                                    <input type="email" name="email_address" value="{{ $editEmail }}" class="box-border h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-slate-300 focus:bg-white">
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-medium text-slate-500">Store URL</label>
                                                    <input type="text" name="store_url" value="{{ $editStoreUrl }}" placeholder="https://shopee.ph/shop/..." class="box-border h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-slate-300 focus:bg-white">
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-medium text-slate-500">Seller / Store ID</label>
                                                    <input type="text" name="seller_id" value="{{ $editSellerId }}" placeholder="Platform seller or store ID" class="box-border h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-slate-300 focus:bg-white">
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-medium text-slate-500">Operating Hours</label>
                                                    <input type="text" name="operating_hours" value="{{ $editOperatingHours }}" placeholder="e.g. Mon–Sun 8:00 AM – 10:00 PM" maxlength="255" class="box-border h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-slate-300 focus:bg-white">
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 px-6 py-5 md:px-8">
                                            <button
                                                type="button"
                                                x-on:click="editModal = null"
                                                class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                            >
                                                Cancel
                                            </button>
                                            <button
                                                type="submit"
                                                class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#001fa8]"
                                            >
                                                Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        </template>

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
        x-effect="if (addSupplierModal) { $nextTick(() => { const phone = document.getElementById('supplier-contact-number'); if (phone && window.refreshPrismPhoneInput) window.refreshPrismPhoneInput(phone); }); }"
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
                                <div class="sm:col-span-2" style="order: 1">
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

                                <div style="order: 2">
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Contact Person</label>
                                    <input
                                        type="text"
                                        name="contact_person"
                                        value="{{ old('contact_person') }}"
                                        placeholder="Enter contact person"
                                        class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                    >
                                </div>

                                <div
                                    x-bind:style="supplierType === 'Physical Store' ? 'order: 3' : 'order: 5'"
                                    x-bind:class="supplierType === 'Online Store' ? 'sm:col-span-2' : ''"
                                >
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Email Address</label>
                                    <input
                                        type="email"
                                        name="email_address"
                                        value="{{ old('email_address') }}"
                                        placeholder="name@example.com"
                                        class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                    >
                                </div>

                                <div
                                    x-bind:style="supplierType === 'Physical Store' ? 'order: 4' : 'order: 3'"
                                >
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Contact Number</label>
                                    @include('partials.phone-input', [
                                        'name' => 'contact_number',
                                        'value' => old('contact_number'),
                                        'id' => 'supplier-contact-number',
                                        'placeholder' => '9XX XXX XXXX',
                                    ])
                                </div>

                                <div
                                    x-show="supplierType === 'Physical Store'"
                                    x-cloak
                                    style="order: 5"
                                >
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Landline Number</label>
                                    @include('partials.landline-input', [
                                        'name' => 'landline_number',
                                        'value' => old('landline_number'),
                                        'id' => 'supplier-landline-number',
                                        'placeholder' => '(0XX) XXX-XXXX',
                                        'alpineDisabled' => "supplierType !== 'Physical Store'",
                                    ])
                                </div>

                                <div class="sm:col-span-2" style="order: 6">
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Operating Hours</label>
                                    <input
                                        type="text"
                                        name="operating_hours"
                                        value="{{ old('operating_hours') }}"
                                        placeholder="e.g. Mon–Fri 9:00 AM – 6:00 PM"
                                        maxlength="255"
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
                            <div class="space-y-4 p-4">
                                <div>
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
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Store URL</label>
                                    <input
                                        type="text"
                                        name="store_url"
                                        value="{{ old('store_url') }}"
                                        placeholder="https://shopee.ph/shop/..."
                                        class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                        x-bind:disabled="supplierType !== 'Online Store'"
                                    >
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Seller / Store ID</label>
                                    <input
                                        type="text"
                                        name="seller_id"
                                        value="{{ old('seller_id') }}"
                                        placeholder="Platform seller or store ID"
                                        class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                        x-bind:disabled="supplierType !== 'Online Store'"
                                    >
                                </div>
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
