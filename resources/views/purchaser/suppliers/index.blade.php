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


    {{-- ========================================================= --}}
    {{-- PAGE HEADER --}}
    {{-- ========================================================= --}}

    <div class="mb-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

            <div>
                <p class="pur-page-kicker">File Maintenance</p>
                <h1 class="pur-page-title">
                    Suppliers
                </h1>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    x-on:click="addSupplierModal = true"
                    class="pur-btn-primary"
                >
                    + Add Supplier
                </button>
            </div>

        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- SUMMARY CARDS --}}
    {{-- ========================================================= --}}

    <div class="pur-card mb-7">

        <div class="grid grid-cols-1 divide-x divide-y divide-gray-100 sm:grid-cols-2 lg:grid-cols-4 sm:divide-y-0">

            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">
                    {{ $supplierSummary['total'] ?? 0 }}
                </p>

                <div class="mt-1 flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                    <p class="text-xs font-medium text-gray-500">Total Suppliers</p>
                </div>
            </div>

            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">
                    {{ $supplierSummary['active'] ?? 0 }}
                </p>

                <div class="mt-1 flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                    <p class="text-xs font-medium text-gray-500">Active</p>
                </div>
            </div>

            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">
                    {{ $supplierSummary['inactive'] ?? 0 }}
                </p>

                <div class="mt-1 flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                    <p class="text-xs font-medium text-gray-500">Inactive</p>
                </div>
            </div>

            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">
                    {{ $supplierSummary['blacklisted'] ?? 0 }}
                </p>

                <div class="mt-1 flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    <p class="text-xs font-medium text-gray-500">Blacklisted</p>
                </div>
            </div>

        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- SUPPLIER RECORDS --}}
    {{-- ========================================================= --}}

    <div class="pur-card">

        {{-- TABLE HEADER / FILTERS --}}
        <div class="border-b border-gray-100 px-5 py-5">

            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">

                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-semibold text-gray-950">
                            Supplier Records
                        </h2>

                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500">
                            {{ isset($suppliers) ? $suppliers->total() : 0 }}
                        </span>
                    </div>

                    <p class="mt-1 text-sm text-gray-500">
                        View and manage registered procurement suppliers.
                    </p>
                </div>


                {{-- FILTER FORM --}}
                <form
                    method="GET"
                    action="{{ route('purchaser.suppliers.index') }}"
                    class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center"
                >

                    {{-- SEARCH --}}
                    <div class="relative">
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                            />
                        </svg>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search suppliers..."
                            class="pur-input pur-input-search sm:w-64"
                        >
                    </div>


                    {{-- TYPE --}}
                    <select
                        name="type"
                        class="pur-select"
                    >
                        <option value="">All Types</option>

                        <option
                            value="Physical Store"
                            {{ request('type') === 'Physical Store' ? 'selected' : '' }}
                        >
                            Physical Store
                        </option>

                        <option
                            value="Online Store"
                            {{ request('type') === 'Online Store' ? 'selected' : '' }}
                        >
                            Online Store
                        </option>
                    </select>


                    {{-- STATUS --}}
                    <select
                        name="status"
                        class="pur-select"
                    >
                        <option value="">All Statuses</option>

                        @foreach(['Active', 'Inactive'] as $status)

                            <option
                                value="{{ $status }}"
                                {{ request('status') === $status ? 'selected' : '' }}
                            >
                                {{ $status }}
                            </option>

                        @endforeach
                    </select>


                    {{-- BLACKLISTED --}}
                    <select
                        name="blacklisted"
                        class="pur-select"
                    >
                        <option value="">Blacklist: All</option>
                        <option value="Yes" {{ request('blacklisted') === 'Yes' ? 'selected' : '' }}>Blacklisted</option>
                        <option value="No" {{ request('blacklisted') === 'No' ? 'selected' : '' }}>Not blacklisted</option>
                    </select>


                    {{-- APPLY --}}
                    <button
                        type="submit"
                        class="pur-btn-primary"
                    >
                        Apply
                    </button>


                    {{-- CLEAR --}}
                    @if(
                        request()->filled('search') ||
                        request()->filled('type') ||
                        request()->filled('status') ||
                        request()->filled('blacklisted')
                    )
                        <a
                            href="{{ route('purchaser.suppliers.index') }}"
                            class="pur-btn-secondary"
                        >
                            Clear
                        </a>
                    @endif

                </form>
            </div>
        </div>


        {{-- ===================================================== --}}
        {{-- TABLE --}}
        {{-- ===================================================== --}}

        <div class="overflow-x-auto">

            <table class="pur-table">

                <thead class="bg-gray-50/70">

                    <tr class="border-b border-gray-100">

                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Supplier
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Type
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Contact
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Status
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Added
                        </th>

                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100 bg-white">

                    @forelse($suppliers ?? [] as $supplier)

                        @php

                            // =================================================
                            // SUPPLIER INDEX: DETERMINE DISPLAY INFORMATION
                            // =================================================

                            $isActive = (int) ($supplier->supplier_is_active ?? 1) === 1;
                            $isBlacklisted = (int) ($supplier->supplier_is_blacklisted ?? 0) === 1;
                            $supplierStatus = $isActive ? 'Active' : 'Inactive';

                            $supplierName =
                                $supplier->company_name
                                ?? $supplier->shop_name
                                ?? 'Unnamed Supplier';

                            $contactPerson = $supplier->contact_person ?? 'Not specified';
                            $contactNumber = $supplier->contact_number ?? 'No contact number';

                            $statusClass = $isActive
                                ? 'bg-green-50 text-green-700'
                                : 'bg-gray-100 text-gray-600';

                        @endphp


                        <tr class="transition hover:bg-gray-50/70">

                            {{-- SUPPLIER --}}
                            <td class="px-5 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50">

                                        <svg
                                            class="h-4 w-4 text-gray-500"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-6h6v6"
                                            />
                                        </svg>

                                    </div>


                                    <div>

                                        <p class="font-semibold text-gray-900">
                                            {{ $supplierName }}
                                        </p>

                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <p class="text-xs text-gray-400">
                                                Supplier #{{ $supplier->supplier_id }}
                                            </p>
                                            @if($isBlacklisted)
                                                <span
                                                    class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800"
                                                    title="{{ $supplier->supplier_blacklist_reason }}"
                                                >
                                                    Blacklisted
                                                </span>
                                            @endif
                                        </div>

                                    </div>

                                </div>

                            </td>


                            {{-- TYPE --}}
                            <td class="px-5 py-4">

                                <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-600">
                                    {{ $supplier->supplier_store_type }}
                                </span>

                            </td>


                            {{-- CONTACT --}}
                            <td class="px-5 py-4">

                                <p class="text-sm text-gray-700">
                                    {{ $contactPerson }}
                                </p>

                                <p class="mt-1 text-xs text-gray-400">
                                    {{ $contactNumber }}
                                </p>

                            </td>


                            {{-- STATUS --}}
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


                            {{-- CREATED --}}
                            <td class="whitespace-nowrap px-5 py-4">

                                @if($supplier->supplier_created_at)

                                    <p class="text-sm text-gray-700">
                                        {{ \Carbon\Carbon::parse($supplier->supplier_created_at)->format('M d, Y') }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-400">
                                        {{ \Carbon\Carbon::parse($supplier->supplier_created_at)->format('h:i A') }}
                                    </p>

                                @else

                                    <span class="text-xs text-gray-400">
                                        Not available
                                    </span>

                                @endif

                            </td>


                            {{-- ACTION --}}
                            <td class="px-5 py-4 text-right">

                                <button
                                    type="button"
                                    x-on:click="openModal = 'supplier-{{ $supplier->supplier_id }}'"
                                    class="group inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-950"
                                >
                                    View

                                    <svg
                                        class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="m9 18 6-6-6-6"
                                        />
                                    </svg>

                                </button>

                            </td>

                        </tr>


                        {{-- ================================================= --}}
                        {{-- VIEW SUPPLIER MODAL --}}
                        {{-- ================================================= --}}

                        <div
                            x-cloak
                            x-show="openModal === 'supplier-{{ $supplier->supplier_id }}'"
                            x-on:keydown.escape.window="openModal = null"
                            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
                        >

                            <div
                                x-on:click.self="openModal = null"
                                class="flex min-h-full w-full justify-center"
                            >

                                <div class="my-auto w-full max-w-5xl overflow-hidden rounded-xl bg-white shadow-2xl">


                                    {{-- MODAL HEADER --}}
                                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-5">

                                        <div>

                                            <div class="flex flex-wrap items-center gap-3">

                                                <h3 class="text-lg font-semibold text-gray-950">
                                                    {{ $supplierName }}
                                                </h3>

                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                                    {{ $supplierStatus }}
                                                </span>

                                                @if($isBlacklisted)
                                                    <span
                                                        class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800"
                                                        title="{{ $supplier->supplier_blacklist_reason }}"
                                                    >
                                                        Blacklisted
                                                    </span>
                                                @endif
                                            </div>

                                            <p class="mt-1 text-sm text-gray-500">
                                                Supplier Record #{{ $supplier->supplier_id }}
                                            </p>

                                        </div>


                                        <button
                                            type="button"
                                            x-on:click="openModal = null"
                                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                                        >
                                            Close
                                        </button>

                                    </div>


                                    {{-- MODAL CONTENT --}}
                                    <div class="max-h-[75vh] overflow-y-auto p-6">


                                        {{-- SUPPLIER INFORMATION --}}
                                        <div>

                                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                                Supplier Information
                                            </h4>

                                            @if($isBlacklisted)
                                                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                                    <p class="font-medium">Blacklisted warning</p>
                                                    <p class="mt-1">{{ $supplier->supplier_blacklist_reason ?: 'This supplier is marked as not recommended.' }}</p>
                                                </div>
                                            @endif


                                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">

                                                <div class="rounded-lg border border-gray-200 p-4">
                                                    <p class="text-xs text-gray-400">
                                                        Supplier Type
                                                    </p>

                                                    <p class="mt-1 font-medium text-gray-900">
                                                        {{ $supplier->supplier_store_type }}
                                                    </p>
                                                </div>


                                                <div class="rounded-lg border border-gray-200 p-4">
                                                    <p class="text-xs text-gray-400">
                                                        Company / Shop
                                                    </p>

                                                    <p class="mt-1 font-medium text-gray-900">
                                                        {{ $supplierName }}
                                                    </p>
                                                </div>


                                                <div class="rounded-lg border border-gray-200 p-4">
                                                    <p class="text-xs text-gray-400">
                                                        Status
                                                    </p>

                                                    <p class="mt-1 font-medium text-gray-900">
                                                        {{ $supplierStatus }}
                                                    </p>
                                                </div>


                                                <div class="rounded-lg border border-gray-200 p-4">
                                                    <p class="text-xs text-gray-400">
                                                        Contact Person
                                                    </p>

                                                    <p class="mt-1 font-medium text-gray-900">
                                                        {{ $supplier->contact_person ?? 'Not specified' }}
                                                    </p>
                                                </div>


                                                <div class="rounded-lg border border-gray-200 p-4">
                                                    <p class="text-xs text-gray-400">
                                                        Contact Number
                                                    </p>

                                                    <p class="mt-1 font-medium text-gray-900">
                                                        {{ $supplier->contact_number ?? 'Not specified' }}
                                                    </p>
                                                </div>


                                                <div class="rounded-lg border border-gray-200 p-4">
                                                    <p class="text-xs text-gray-400">
                                                        Email Address
                                                    </p>

                                                    <p class="mt-1 break-all font-medium text-gray-900">
                                                        {{ $supplier->email_address ?? 'Not specified' }}
                                                    </p>
                                                </div>

                                            </div>


                                            @if($supplier->company_address)

                                                <div class="mt-3 rounded-lg border border-gray-200 p-4">

                                                    <p class="text-xs text-gray-400">
                                                        Company Address
                                                    </p>

                                                    <p class="mt-1 text-sm leading-6 text-gray-700">
                                                        {{ $supplier->company_address }}
                                                    </p>

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

                                                    <div class="rounded-lg border border-gray-200 p-4">

                                                        <p class="text-xs text-gray-400">
                                                            Platform
                                                        </p>

                                                        <p class="mt-1 font-medium text-gray-900">
                                                            {{ $supplier->app_used ?? 'Not specified' }}
                                                        </p>

                                                    </div>


                                                    <div class="rounded-lg border border-gray-200 p-4">

                                                        <p class="text-xs text-gray-400">
                                                            Shop Name
                                                        </p>

                                                        <p class="mt-1 font-medium text-gray-900">
                                                            {{ $supplier->shop_name ?? 'Not specified' }}
                                                        </p>

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
                            <td
                                colspan="6"
                                class="px-5 py-16 text-center"
                            >

                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50">

                                    <svg
                                        class="h-5 w-5 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-6h6v6"
                                        />
                                    </svg>

                                </div>

                                <p class="mt-4 text-sm font-medium text-gray-700">
                                    No suppliers found
                                </p>

                                <p class="mt-1 text-xs text-gray-400">
                                    Add a supplier or adjust the current filters.
                                </p>

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


    {{-- ========================================================= --}}
    {{-- ADD SUPPLIER MODAL --}}
    {{-- ========================================================= --}}

    <div
        x-cloak
        x-show="addSupplierModal"
        x-on:keydown.escape.window="addSupplierModal = false"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
    >

        <div
            x-on:click.self="addSupplierModal = false"
            class="flex min-h-full w-full justify-center"
        >

            <div class="my-auto w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-2xl">


                {{-- HEADER --}}
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-5">

                    <div>

                        <h3 class="text-lg font-semibold text-gray-950">
                            Add Supplier
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Register a new procurement supplier.
                        </p>

                    </div>


                    <button
                        type="button"
                        x-on:click="addSupplierModal = false"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                    >
                        Close
                    </button>

                </div>


                {{-- ================================================= --}}
                {{-- ADD FORM --}}
                {{-- ================================================= --}}

                <form
                    method="POST"
                    action="{{ route('purchaser.suppliers.store') }}"
                >

                    @csrf


                    <div class="max-h-[75vh] overflow-y-auto p-6">


                        {{-- BASIC INFORMATION --}}
                        <div>

                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Basic Information
                            </h4>


                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">


                                {{-- TYPE --}}
                                <div>

                                    <label class="mb-2 block text-sm font-medium text-gray-700">
                                        Supplier Type
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <select
                                        name="supplier_store_type"
                                        x-model="supplierType"
                                        required
                                        class="pur-input"
                                    >
                                        <option value="Physical Store">Physical Store</option>
                                        <option value="Online Store">Online Store</option>
                                    </select>

                                </div>


                                {{-- NAME --}}
                                <div>

                                    <label class="mb-2 block text-sm font-medium text-gray-700">
                                        Company / Shop Name
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="supplier_name"
                                        value="{{ old('supplier_name', old('company_name', old('shop_name'))) }}"
                                        required
                                        placeholder="Enter supplier name"
                                        class="pur-input"
                                    >

                                </div>


                                {{-- CONTACT PERSON --}}
                                <div>

                                    <label class="mb-2 block text-sm font-medium text-gray-700">
                                        Contact Person
                                    </label>

                                    <input
                                        type="text"
                                        name="contact_person"
                                        value="{{ old('contact_person') }}"
                                        placeholder="Enter contact person"
                                        class="pur-input"
                                    >

                                </div>


                                {{-- CONTACT NUMBER --}}
                                <div>

                                    <label class="mb-2 block text-sm font-medium text-gray-700">
                                        Contact Number
                                    </label>

                                    <input
                                        type="text"
                                        name="contact_number"
                                        value="{{ old('contact_number') }}"
                                        placeholder="Enter contact number"
                                        class="pur-input"
                                    >

                                </div>


                                {{-- EMAIL --}}
                                <div class="sm:col-span-2">

                                    <label class="mb-2 block text-sm font-medium text-gray-700">
                                        Email Address
                                    </label>

                                    <input
                                        type="email"
                                        name="email_address"
                                        value="{{ old('email_address') }}"
                                        placeholder="Enter email address"
                                        class="pur-input"
                                    >

                                </div>

                            </div>

                        </div>


                        <template x-if="supplierType === 'Physical Store'">
                            <div class="mt-8">
                                <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                    Physical Location
                                </h4>
                                <div class="mt-4">
                                    <label class="mb-2 block text-sm font-medium text-gray-700">
                                        Company Address
                                    </label>
                                    <textarea
                                        name="company_address"
                                        rows="3"
                                        placeholder="Enter physical store address"
                                        class="w-full resize-none rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white"
                                    >{{ old('company_address') }}</textarea>
                                </div>
                            </div>
                        </template>

                        <template x-if="supplierType === 'Online Store'">
                            <div class="mt-8">
                                <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                    Online Information
                                </h4>
                                <div class="mt-4">
                                    <label class="mb-2 block text-sm font-medium text-gray-700">
                                        Primary Platform
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <select name="app_used" required class="pur-input">
                                        <option value="">Select platform</option>
                                        @foreach(['Shopee', 'Lazada', 'Facebook', 'TikTok Shop', 'Website', 'Other'] as $platform)
                                            <option value="{{ $platform }}" @selected(old('app_used') === $platform)>
                                                {{ $platform }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </template>

                    </div>


                    {{-- ================================================= --}}
                    {{-- FORM FOOTER --}}
                    {{-- ================================================= --}}

                    <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">

                        <button
                            type="button"
                            x-on:click="addSupplierModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                        >
                            Cancel
                        </button>


                        <button
                            type="submit"
                            class="pur-btn-primary"
                        >
                            Save Supplier
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>

</div>

@endsection