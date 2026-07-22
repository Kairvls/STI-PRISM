@extends('layouts.purchaser-layout')

@section('page-title', 'Suppliers')
<<<<<<< HEAD
@section('page-subtitle', 'Manage Supplier Records')

@section('content')

<div
    x-data="{
        openModal: null,
        addSupplierModal: false,
        supplierType: 'Physical Store',
        linkRows: [
            { platform: 'Shopee', label: '', url: '' }
        ],

        addLink() {
            this.linkRows.push({
                platform: 'Shopee',
                label: '',
                url: ''
            });
        },

        removeLink(index) {
            if (this.linkRows.length > 1) {
                this.linkRows.splice(index, 1);
            }
        }
    }"
    x-cloak
>

    {{-- ========================================================= --}}
    {{-- ALERT MESSAGES --}}
    {{-- ========================================================= --}}

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
=======
@section('page-subtitle', 'Manage physical and online suppliers')

@section('content')
<div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
>>>>>>> c4a35edc5d072bfc8cb72a8a88f1cc1b610c0f67
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-medium">Please fix the following supplier form errors:</p>
<<<<<<< HEAD

=======
>>>>>>> c4a35edc5d072bfc8cb72a8a88f1cc1b610c0f67
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

<<<<<<< HEAD

    {{-- ========================================================= --}}
    {{-- PAGE HEADER --}}
    {{-- ========================================================= --}}

    <div class="mb-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

            <div>
                <div class="mb-2 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-gray-900"></span>

                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">
                        Procurement
                    </span>
                </div>

                <h1 class="text-3xl font-semibold tracking-tight text-gray-950">
                    Suppliers
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                    Manage physical and online suppliers, contact information,
                    purchasing sources, supplier links, and supplier history.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    x-on:click="addSupplierModal = true"
                    class="rounded-xl bg-gray-950 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800"
                >
                    + Add Supplier
                </button>
            </div>

        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- SUMMARY CARDS --}}
    {{-- ========================================================= --}}

    <div class="mb-7 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        <div class="grid grid-cols-2 divide-x divide-y divide-gray-100 sm:grid-cols-3 lg:grid-cols-5 lg:divide-y-0">

            {{-- TOTAL --}}
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">
                    {{ $supplierSummary['total'] ?? 0 }}
                </p>

                <div class="mt-1 flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>

                    <p class="text-xs font-medium text-gray-500">
                        Total Suppliers
                    </p>
                </div>
            </div>

            {{-- ACTIVE --}}
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">
                    {{ $supplierSummary['active'] ?? 0 }}
                </p>

                <div class="mt-1 flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>

                    <p class="text-xs font-medium text-gray-500">
                        Active
                    </p>
                </div>
            </div>

            {{-- UNDER REVIEW --}}
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">
                    {{ $supplierSummary['review'] ?? 0 }}
                </p>

                <div class="mt-1 flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>

                    <p class="text-xs font-medium text-gray-500">
                        Under Review
                    </p>
                </div>
            </div>

            {{-- BLACKLISTED --}}
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">
                    {{ $supplierSummary['blacklisted'] ?? 0 }}
                </p>

                <div class="mt-1 flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>

                    <p class="text-xs font-medium text-gray-500">
                        Blacklisted
                    </p>
                </div>
            </div>

            {{-- INACTIVE --}}
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">
                    {{ $supplierSummary['inactive'] ?? 0 }}
                </p>

                <div class="mt-1 flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>

                    <p class="text-xs font-medium text-gray-500">
                        Inactive
                    </p>
                </div>
            </div>

        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- SUPPLIER RECORDS --}}
    {{-- ========================================================= --}}

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

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
                    class="flex flex-col gap-2 sm:flex-row sm:flex-wrap"
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
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>


                    {{-- TYPE --}}
                    <select
                        name="type"
                        class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white"
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

                        <option
                            value="Both"
                            {{ request('type') === 'Both' ? 'selected' : '' }}
                        >
                            Both
                        </option>
                    </select>


                    {{-- STATUS --}}
                    <select
                        name="status"
                        class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white"
                    >
                        <option value="">All Statuses</option>

                        @foreach([
                            'Active',
                            'Under Review',
                            'Inactive',
                            'Blacklisted'
                        ] as $status)

                            <option
                                value="{{ $status }}"
                                {{ request('status') === $status ? 'selected' : '' }}
                            >
                                {{ $status }}
                            </option>

                        @endforeach
                    </select>


                    {{-- APPLY --}}
                    <button
                        type="submit"
                        class="rounded-xl bg-gray-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800"
                    >
                        Apply
                    </button>


                    {{-- CLEAR --}}
                    @if(
                        request()->filled('search') ||
                        request()->filled('type') ||
                        request()->filled('status')
                    )
                        <a
                            href="{{ route('purchaser.suppliers.index') }}"
                            class="rounded-xl border border-gray-200 px-4 py-2.5 text-center text-sm font-medium text-gray-600 transition hover:bg-gray-50"
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

            <table class="w-full text-sm">

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

                            $physical = $supplier->physicalSupplier ?? null;
                            $online = $supplier->onlineSupplier ?? null;

                            $supplierName =
                                $physical?->company_name
                                ?? $online?->shop_name
                                ?? 'Unnamed Supplier';

                            $contactPerson =
                                $physical?->contact_person
                                ?? 'Not specified';

                            $contactNumber =
                                $physical?->contact_number
                                ?? 'No contact number';

                            $statusClass = match($supplier->supplier_status ?? 'Active') {
                                'Active' =>
                                    'bg-green-50 text-green-700',

                                'Under Review' =>
                                    'bg-amber-50 text-amber-700',

                                'Blacklisted' =>
                                    'bg-red-50 text-red-700',

                                'Inactive' =>
                                    'bg-gray-100 text-gray-600',

                                default =>
                                    'bg-gray-100 text-gray-600',
                            };

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

                                        <p class="mt-0.5 text-xs text-gray-400">
                                            Supplier #{{ $supplier->supplier_id }}
                                        </p>

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

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                    {{ $supplier->supplier_status ?? 'Active' }}
                                </span>

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
                                                    {{ $supplier->supplier_status ?? 'Active' }}
                                                </span>

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
                                                        {{ $supplier->supplier_status ?? 'Active' }}
                                                    </p>
                                                </div>


                                                <div class="rounded-lg border border-gray-200 p-4">
                                                    <p class="text-xs text-gray-400">
                                                        Contact Person
                                                    </p>

                                                    <p class="mt-1 font-medium text-gray-900">
                                                        {{ $physical?->contact_person ?? 'Not specified' }}
                                                    </p>
                                                </div>


                                                <div class="rounded-lg border border-gray-200 p-4">
                                                    <p class="text-xs text-gray-400">
                                                        Contact Number
                                                    </p>

                                                    <p class="mt-1 font-medium text-gray-900">
                                                        {{ $physical?->contact_number ?? 'Not specified' }}
                                                    </p>
                                                </div>


                                                <div class="rounded-lg border border-gray-200 p-4">
                                                    <p class="text-xs text-gray-400">
                                                        Email Address
                                                    </p>

                                                    <p class="mt-1 break-all font-medium text-gray-900">
                                                        {{ $physical?->email_address ?? 'Not specified' }}
                                                    </p>
                                                </div>

                                            </div>


                                            @if($physical?->company_address)

                                                <div class="mt-3 rounded-lg border border-gray-200 p-4">

                                                    <p class="text-xs text-gray-400">
                                                        Company Address
                                                    </p>

                                                    <p class="mt-1 text-sm leading-6 text-gray-700">
                                                        {{ $physical->company_address }}
                                                    </p>

                                                </div>

                                            @endif

                                        </div>


                                        {{-- ONLINE INFORMATION --}}
                                        @if($online)

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
                                                            {{ $online->app_used ?? 'Not specified' }}
                                                        </p>

                                                    </div>


                                                    <div class="rounded-lg border border-gray-200 p-4">

                                                        <p class="text-xs text-gray-400">
                                                            Shop Name
                                                        </p>

                                                        <p class="mt-1 font-medium text-gray-900">
                                                            {{ $online->shop_name ?? 'Not specified' }}
                                                        </p>

                                                    </div>

                                                </div>

                                            </div>

                                        @endif


                                        {{-- SUPPLIER LINKS --}}
                                        <div class="mt-8">

                                            <div class="flex items-center justify-between">

                                                <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                                    Supplier Links
                                                </h4>

                                            </div>


                                            <div class="mt-3 space-y-2">

                                                @forelse($supplier->supplierLinks ?? [] as $link)

                                                    <div class="flex flex-col gap-3 rounded-lg border border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between">

                                                        <div>

                                                            <p class="font-medium text-gray-900">
                                                                {{ $link->supplier_link_platform }}
                                                            </p>

                                                            @if($link->supplier_link_label)
                                                                <p class="mt-1 text-xs text-gray-400">
                                                                    {{ $link->supplier_link_label }}
                                                                </p>
                                                            @endif

                                                        </div>


                                                        <a
                                                            href="{{ $link->supplier_link_url }}"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="text-sm font-medium text-gray-600 hover:text-gray-950"
                                                        >
                                                            Open Link
                                                        </a>

                                                    </div>

                                                @empty

                                                    <div class="rounded-lg border border-dashed border-gray-200 px-4 py-8 text-center">

                                                        <p class="text-sm text-gray-500">
                                                            No supplier links added.
                                                        </p>

                                                    </div>

                                                @endforelse

                                            </div>

                                        </div>


                                        {{-- SUPPLIER HISTORY --}}
                                        <div class="mt-8">

                                            <div class="flex items-center justify-between gap-3">

                                                <div>
                                                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                                        Supplier History
                                                    </h4>

                                                    <p class="mt-1 text-sm text-gray-400">
                                                        Notes, issues, warnings, and purchasing experiences.
                                                    </p>
                                                </div>

                                            </div>


                                            <div class="mt-4 space-y-3">

                                                @forelse($supplier->supplierHistory ?? [] as $history)

                                                    <div class="rounded-lg border border-gray-200 p-4">

                                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">

                                                            <div>

                                                                <p class="text-sm font-semibold text-gray-900">
                                                                    {{ $history->supplier_history_type }}
                                                                </p>

                                                                <p class="mt-2 text-sm leading-6 text-gray-600">
                                                                    {{ $history->supplier_history_note }}
                                                                </p>

                                                            </div>


                                                            <p class="whitespace-nowrap text-xs text-gray-400">
                                                                {{ \Carbon\Carbon::parse($history->supplier_history_created_at)->format('M d, Y') }}
                                                            </p>

                                                        </div>

                                                    </div>

                                                @empty

                                                    <div class="rounded-lg border border-dashed border-gray-200 px-4 py-8 text-center">

                                                        <p class="text-sm text-gray-500">
                                                            No supplier history recorded.
                                                        </p>

                                                    </div>

                                                @endforelse

                                            </div>

                                        </div>

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

                                        <button
                                            type="button"
                                            class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                                        >
                                            Edit Supplier
                                        </button>

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
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white"
                                    >
                                        <option value="Physical Store">
                                            Physical Store
                                        </option>

                                        <option value="Online Store">
                                            Online Store
                                        </option>

                                        <option value="Both">
                                            Both
                                        </option>
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
                                        required
                                        placeholder="Enter supplier name"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white"
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
                                        placeholder="Enter contact person"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white"
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
                                        placeholder="Enter contact number"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white"
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
                                        placeholder="Enter email address"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white"
                                    >

                                </div>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- PHYSICAL INFORMATION --}}
                        {{-- ================================================= --}}

                        <div
                            x-show="
                                supplierType === 'Physical Store' ||
                                supplierType === 'Both'
                            "
                            class="mt-8"
                        >

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
                                ></textarea>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- ONLINE INFORMATION --}}
                        {{-- ================================================= --}}

                        <div
                            x-show="
                                supplierType === 'Online Store' ||
                                supplierType === 'Both'
                            "
                            class="mt-8"
                        >

                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Online Information
                            </h4>


                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">


                                <div>

                                    <label class="mb-2 block text-sm font-medium text-gray-700">
                                        Primary Platform
                                    </label>

                                    <select
                                        name="app_used"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white"
                                    >
                                        <option value="">
                                            Select platform
                                        </option>

                                        <option value="Shopee">Shopee</option>
                                        <option value="Lazada">Lazada</option>
                                        <option value="Facebook">Facebook</option>
                                        <option value="TikTok Shop">TikTok Shop</option>
                                        <option value="Website">Website</option>
                                        <option value="Other">Other</option>
                                    </select>

                                </div>


                                <div>

                                    <label class="mb-2 block text-sm font-medium text-gray-700">
                                        Online Shop Name
                                    </label>

                                    <input
                                        type="text"
                                        name="shop_name"
                                        placeholder="Enter online shop name"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white"
                                    >

                                </div>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- SUPPLIER LINKS --}}
                        {{-- ================================================= --}}

                        <div class="mt-8">

                            <div class="flex items-center justify-between gap-3">

                                <div>
                                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                        Supplier Links
                                    </h4>

                                    <p class="mt-1 text-xs text-gray-400">
                                        Add Shopee, Facebook, website, or other supplier pages.
                                    </p>
                                </div>


                                <button
                                    type="button"
                                    x-on:click="addLink()"
                                    class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50"
                                >
                                    + Add Link
                                </button>

                            </div>


                            <div class="mt-4 space-y-3">

                                <template
                                    x-for="(link, index) in linkRows"
                                    :key="index"
                                >

                                    <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-gray-50/50 p-4 lg:grid-cols-[160px_1fr_2fr_auto]">


                                        {{-- PLATFORM --}}
                                        <select
                                            x-model="link.platform"
                                            x-bind:name="'links[' + index + '][platform]'"
                                            class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none"
                                        >
                                            <option value="Shopee">Shopee</option>
                                            <option value="Lazada">Lazada</option>
                                            <option value="Facebook">Facebook</option>
                                            <option value="Website">Website</option>
                                            <option value="TikTok Shop">TikTok Shop</option>
                                            <option value="Other">Other</option>
                                        </select>


                                        {{-- LABEL --}}
                                        <input
                                            type="text"
                                            x-model="link.label"
                                            x-bind:name="'links[' + index + '][label]'"
                                            placeholder="Label"
                                            class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none"
                                        >


                                        {{-- URL --}}
                                        <input
                                            type="url"
                                            x-model="link.url"
                                            x-bind:name="'links[' + index + '][url]'"
                                            placeholder="https://..."
                                            class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none"
                                        >


                                        {{-- REMOVE --}}
                                        <button
                                            type="button"
                                            x-on:click="removeLink(index)"
                                            class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50"
                                        >
                                            Remove
                                        </button>

                                    </div>

                                </template>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- INITIAL HISTORY --}}
                        {{-- ================================================= --}}

                        <div class="mt-8">

                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Initial History
                            </h4>

                            <p class="mt-1 text-xs text-gray-400">
                                Optional initial note about this supplier.
                            </p>


                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">


                                <div>

                                    <label class="mb-2 block text-sm font-medium text-gray-700">
                                        Type
                                    </label>

                                    <select
                                        name="history_type"
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none"
                                    >
                                        <option value="General Note">
                                            General Note
                                        </option>

                                        <option value="Purchase Experience">
                                            Purchase Experience
                                        </option>

                                        <option value="Product Issue">
                                            Product Issue
                                        </option>

                                        <option value="Delivery Issue">
                                            Delivery Issue
                                        </option>

                                        <option value="Warning">
                                            Warning
                                        </option>

                                        <option value="Positive Feedback">
                                            Positive Feedback
                                        </option>
                                    </select>

                                </div>


                                <div class="sm:col-span-2">

                                    <label class="mb-2 block text-sm font-medium text-gray-700">
                                        Note
                                    </label>

                                    <textarea
                                        name="history_note"
                                        rows="3"
                                        placeholder="Optional supplier note..."
                                        class="w-full resize-none rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none"
                                    ></textarea>

                                </div>

                            </div>

                        </div>

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
                            class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
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
=======
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
>>>>>>> c4a35edc5d072bfc8cb72a8a88f1cc1b610c0f67
