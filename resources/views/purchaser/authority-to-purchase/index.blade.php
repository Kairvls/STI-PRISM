@extends('layouts.purchaser-layout')

@section('page-title', 'Authority to Purchase')
@section('page-subtitle', 'Manage ATP drafts, submissions, approvals, and archives')

@section('content')

<div
    x-data="{
        createOpen: {{ $errors->any() && old('authority_purchase_ris_id') ? 'true' : 'false' }},
        viewOpen: false,
        editOpen: false,
        rejectOpen: false,
        selectedAtp: null,

        openView(id) {
            this.selectedAtp = id;
            this.viewOpen = true;
            this.editOpen = false;
            this.rejectOpen = false;
        },

        openEdit(id) {
            this.selectedAtp = id;
            this.editOpen = true;
            this.viewOpen = false;
            this.rejectOpen = false;
        },

        openReject(id) {
            this.selectedAtp = id;
            this.rejectOpen = true;
        },

        closeAll() {
            this.createOpen = false;
            this.viewOpen = false;
            this.editOpen = false;
            this.rejectOpen = false;
            this.selectedAtp = null;
        }
    }"
    @keydown.escape.window="closeAll()"
    class="space-y-6"
>

    {{-- ========================================================= --}}
    {{-- FLASH MESSAGES --}}
    {{-- ========================================================= --}}

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-medium">Please check the following:</p>

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

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">
                Authority to Purchase
            </h2>

            <p class="text-sm text-slate-600">
                Create ATP only from approved RIS and track its approval lifecycle.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">

            {{-- Archive / Active View --}}
            @if($archiveView)
                <a
                    href="{{ route('purchaser.atp.index') }}"
                    class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-5 text-sm font-medium text-gray-700"
                >
                    Active ATP
                </a>
            @else
                <a
                    href="{{ route('purchaser.atp.index', ['view' => 'archive']) }}"
                    class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-5 text-sm font-medium text-gray-700"
                >
                    Archive
                </a>
            @endif

            {{-- Open Create Modal --}}
            @if(!$archiveView)
                <button
                    type="button"
                    @click="createOpen = true"
                    class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white"
                >
                    New ATP
                </button>
            @endif

        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- SUMMARY CARDS --}}
    {{-- ========================================================= --}}

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">
                Draft
            </p>

            <p class="mt-3 text-3xl font-semibold text-slate-900">
                {{ $atpSummary['draft'] }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">
                Submitted
            </p>

            <p class="mt-3 text-3xl font-semibold text-slate-900">
                {{ $atpSummary['submitted'] }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">
                Approved
            </p>

            <p class="mt-3 text-3xl font-semibold text-green-600">
                {{ $atpSummary['approved'] }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">
                Rejected
            </p>

            <p class="mt-3 text-3xl font-semibold text-red-600">
                {{ $atpSummary['rejected'] }}
            </p>
        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- SEARCH / FILTERS --}}
    {{-- ========================================================= --}}

    <form
        method="GET"
        class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 lg:grid-cols-5"
    >

        @if($archiveView)
            <input type="hidden" name="view" value="archive">
        @endif

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search ATP, RIS, supplier, or equipment"
            class="h-10 rounded-lg border border-gray-300 px-3 text-sm lg:col-span-2"
        >

        <select
            name="status"
            class="h-10 rounded-lg border border-gray-300 px-3 text-sm"
        >
            <option value="">
                All statuses
            </option>

            <option
                value="Pending"
                {{ request('status') === 'Pending' ? 'selected' : '' }}
            >
                Pending
            </option>

            <option
                value="Approved"
                {{ request('status') === 'Approved' ? 'selected' : '' }}
            >
                Approved
            </option>

            <option
                value="Rejected"
                {{ request('status') === 'Rejected' ? 'selected' : '' }}
            >
                Rejected
            </option>
        </select>

        <select
            name="request_type"
            class="h-10 rounded-lg border border-gray-300 px-3 text-sm"
        >
            <option value="">
                All RIS types
            </option>

            <option
                value="New Procurement"
                {{ request('request_type') === 'New Procurement' ? 'selected' : '' }}
            >
                New Procurement
            </option>

            <option
                value="Replacement"
                {{ request('request_type') === 'Replacement' ? 'selected' : '' }}
            >
                Replacement
            </option>
        </select>

        <div class="flex gap-2">

            <button
                type="submit"
                class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white"
            >
                Search
            </button>

            <a
                href="{{ $archiveView ? route('purchaser.atp.index', ['view' => 'archive']) : route('purchaser.atp.index') }}"
                class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700"
            >
                Reset
            </a>

        </div>

    </form>


    {{-- ========================================================= --}}
    {{-- ATP TABLE --}}
    {{-- ========================================================= --}}

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">

        <div class="overflow-x-auto">

            <table class="w-full min-w-[1000px] text-sm">

                <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">

                    <tr>
                        <th class="px-4 py-3">ATP No.</th>
                        <th class="px-4 py-3">RIS</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-gray-100 bg-white">

                    @forelse($atps as $atp)

                        <tr class="hover:bg-gray-50">

                            {{-- ATP Number --}}
                            <td class="px-4 py-4 font-medium text-slate-900">

                                {{ $atp->authority_purchase_form_number
                                    ?? 'ATP-' . $atp->authority_purchase_id }}

                            </td>


                            {{-- RIS --}}
                            <td class="px-4 py-4 text-gray-600">

                                {{ $atp->ris_form_number
                                    ?? 'RIS-' . $atp->authority_purchase_ris_id }}

                                <br>

                                <span class="text-xs text-gray-400">

                                    {{ $atp->equipment_name
                                        ?? $atp->report_unlisted_equipment_name
                                        ?? 'No equipment' }}

                                </span>

                            </td>


                            {{-- Supplier --}}
                            <td class="px-4 py-4 text-gray-600">

                                @if($atp->supplier_store_type === 'Physical Store')

                                    {{ $atp->company_name
                                        ?? 'Physical supplier' }}

                                @else

                                    {{ $atp->shop_name
                                        ?? 'Online supplier' }}

                                @endif

                            </td>


                            {{-- Date --}}
                            <td class="px-4 py-4 text-gray-600">

                                @if($atp->authority_purchase_date)

                                    {{ \Carbon\Carbon::parse(
                                        $atp->authority_purchase_date
                                    )->format('M d, Y') }}

                                @else
                                    —
                                @endif

                            </td>


                            {{-- Status --}}
                            <td class="px-4 py-4">

                                @if($atp->authority_purchase_status === 'Approved')

                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                        Approved
                                    </span>

                                @elseif($atp->authority_purchase_status === 'Rejected')

                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                        Rejected
                                    </span>

                                @elseif($atp->authority_purchase_submitted_at)

                                    <span class="rounded-full bg-gray-900 px-3 py-1 text-xs font-semibold text-white">
                                        Submitted
                                    </span>

                                @else

                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                        Draft
                                    </span>

                                @endif

                            </td>


                            {{-- Actions --}}
                            <td class="px-4 py-4">

                                <div class="flex flex-wrap gap-2">

                                    {{-- VIEW --}}
                                    <button
                                        type="button"
                                        @click="openView({{ $atp->authority_purchase_id }})"
                                        class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700"
                                    >
                                        View
                                    </button>


                                    {{-- DRAFT ACTIONS --}}
                                    @if(
                                        !$atp->authority_purchase_submitted_at
                                        && $atp->authority_purchase_status === 'Pending'
                                        && !$archiveView
                                    )

                                        <button
                                            type="button"
                                            @click="openEdit({{ $atp->authority_purchase_id }})"
                                            class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700"
                                        >
                                            Edit
                                        </button>


                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'purchaser.atp.submit',
                                                $atp->authority_purchase_id
                                            ) }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white"
                                            >
                                                Submit
                                            </button>
                                        </form>

                                    @endif


                                    {{-- SUBMITTED ACTIONS --}}
                                    @if(
                                        $atp->authority_purchase_status === 'Pending'
                                        && $atp->authority_purchase_submitted_at
                                        && !$archiveView
                                    )

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'purchaser.atp.approve',
                                                $atp->authority_purchase_id
                                            ) }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="rounded-lg bg-green-100 px-3 py-2 text-xs font-medium text-green-700"
                                            >
                                                Approve
                                            </button>

                                        </form>


                                        <button
                                            type="button"
                                            @click="openReject({{ $atp->authority_purchase_id }})"
                                            class="rounded-lg border border-red-300 px-3 py-2 text-xs font-medium text-red-700"
                                        >
                                            Reject
                                        </button>

                                    @endif


                                    {{-- ARCHIVE --}}
                                    @if($archiveView)

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'purchaser.atp.restore',
                                                $atp->authority_purchase_id
                                            ) }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700"
                                            >
                                                Restore
                                            </button>

                                        </form>

                                    @elseif(!$atp->authority_purchase_is_archived)

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'purchaser.atp.archive',
                                                $atp->authority_purchase_id
                                            ) }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="rounded-lg bg-gray-100 px-3 py-2 text-xs font-medium text-gray-700"
                                            >
                                                Archive
                                            </button>

                                        </form>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="6"
                                class="px-4 py-12 text-center text-sm text-gray-500"
                            >
                                No ATP records found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- PAGINATION --}}
    {{-- ========================================================= --}}

    <div>
        {{ $atps->links() }}
    </div>



    {{-- ========================================================= --}}
    {{-- CREATE ATP MODAL --}}
    {{-- ========================================================= --}}

    <div
        x-show="createOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
    >

        {{-- Overlay --}}
        <div
            class="fixed inset-0 bg-black/40"
            @click="createOpen = false"
        ></div>


        <div class="relative flex min-h-full items-center justify-center p-4">

            <div
                @click.stop
                class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl"
            >

                {{-- Modal Header --}}
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">

                    <div>
                        <h3 class="text-xl font-semibold text-slate-900">
                            New Authority to Purchase
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Select an approved RIS and create an ATP draft.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="createOpen = false"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                    >
                        ✕
                    </button>

                </div>


                {{-- No RIS Available --}}
                @if($eligibleRis->isEmpty())

                    <div class="p-6">

                        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-600">

                            No approved RIS is currently available for Authority to Purchase creation.

                        </div>

                    </div>

                @else

                    {{-- Create Form --}}
                    <form
                        method="POST"
                        action="{{ route('purchaser.atp.store') }}"
                    >

                        @csrf

                        <div class="max-h-[70vh] space-y-6 overflow-y-auto p-6">


                            {{-- RIS / Supplier --}}
                            <div class="grid gap-4 lg:grid-cols-2">

                                <div>

                                    <label class="text-xs font-medium text-gray-500">
                                        Approved RIS
                                    </label>

                                    <select
                                        name="authority_purchase_ris_id"
                                        required
                                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                    >

                                        <option value="">
                                            Select approved RIS
                                        </option>

                                        @foreach($eligibleRis as $ris)

                                            <option
                                                value="{{ $ris->ris_id }}"
                                                {{ old(
                                                    'authority_purchase_ris_id',
                                                    $selectedRisId ?? ''
                                                ) == $ris->ris_id ? 'selected' : '' }}
                                            >

                                                {{ $ris->ris_form_number
                                                    ?? 'RIS-' . $ris->ris_id }}

                                                @if(
                                                    $ris->equipment_name
                                                    || $ris->report_unlisted_equipment_name
                                                )
                                                    ·
                                                    {{ $ris->equipment_name
                                                        ?? $ris->report_unlisted_equipment_name }}
                                                @elseif($ris->ris_manual_title)
                                                    · {{ $ris->ris_manual_title }}
                                                @endif

                                            </option>

                                        @endforeach

                                    </select>

                                </div>


                                <div>

                                    <label class="text-xs font-medium text-gray-500">
                                        Supplier
                                    </label>

                                    <select
                                        name="authority_purchase_supplier_id"
                                        required
                                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                    >

                                        <option value="">
                                            Select supplier
                                        </option>

                                        @foreach($suppliers as $supplier)

                                            <option
                                                value="{{ $supplier->supplier_id }}"
                                                {{ old('authority_purchase_supplier_id') == $supplier->supplier_id ? 'selected' : '' }}
                                            >

                                                @if($supplier->supplier_store_type === 'Physical Store')

                                                    {{ $supplier->company_name
                                                        ?? 'Physical supplier #' . $supplier->supplier_id }}

                                                @else

                                                    {{ $supplier->shop_name
                                                        ?? 'Online supplier #' . $supplier->supplier_id }}

                                                @endif

                                            </option>

                                        @endforeach

                                    </select>

                                </div>

                            </div>


                            {{-- Basic ATP Information --}}
                            <div class="grid gap-4 lg:grid-cols-3">

                                <div>

                                    <label class="text-xs font-medium text-gray-500">
                                        Purchase date
                                    </label>

                                    <input
                                        type="date"
                                        name="authority_purchase_date"
                                        value="{{ old(
                                            'authority_purchase_date',
                                            now()->format('Y-m-d')
                                        ) }}"
                                        required
                                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                    >

                                </div>


                                <div>

                                    <label class="text-xs font-medium text-gray-500">
                                        Received by
                                    </label>

                                    <input
                                        type="text"
                                        name="authority_purchase_received_by_name"
                                        value="{{ old(
                                            'authority_purchase_received_by_name'
                                        ) }}"
                                        required
                                        placeholder="Receiver name"
                                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                    >

                                </div>


                                <div>

                                    <label class="text-xs font-medium text-gray-500">
                                        Reference PO / PR
                                    </label>

                                    <input
                                        type="text"
                                        name="authority_purchase_reference_po_no"
                                        value="{{ old(
                                            'authority_purchase_reference_po_no'
                                        ) }}"
                                        placeholder="PO or PR number"
                                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                    >

                                </div>

                            </div>


                            {{-- ATP Items --}}
                            <div>

                                <div class="mb-2">

                                    <h4 class="text-sm font-semibold text-slate-900">
                                        ATP Items
                                    </h4>

                                    <p class="text-xs text-gray-500">
                                        Enter the items included in this Authority to Purchase.
                                    </p>

                                </div>


                                <div class="overflow-x-auto rounded-xl border border-gray-200">

                                    <table class="w-full min-w-[750px] text-sm">

                                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">

                                            <tr>
                                                <th class="px-3 py-3 text-left">
                                                    Description
                                                </th>

                                                <th class="w-28 px-3 py-3 text-left">
                                                    Qty
                                                </th>

                                                <th class="w-36 px-3 py-3 text-left">
                                                    Unit
                                                </th>

                                                <th class="w-44 px-3 py-3 text-left">
                                                    Unit Price
                                                </th>
                                            </tr>

                                        </thead>

                                        <tbody>

                                            @for($itemIndex = 0; $itemIndex < 8; $itemIndex++)

                                                <tr>

                                                    <td class="border-t border-gray-200 p-2">

                                                        <input
                                                            type="text"
                                                            name="items[{{ $itemIndex }}][description]"
                                                            value="{{ old(
                                                                'items.' . $itemIndex . '.description'
                                                            ) }}"
                                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                                        >

                                                    </td>

                                                    <td class="border-t border-gray-200 p-2">

                                                        <input
                                                            type="number"
                                                            name="items[{{ $itemIndex }}][quantity]"
                                                            value="{{ old(
                                                                'items.' . $itemIndex . '.quantity'
                                                            ) }}"
                                                            min="1"
                                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                                        >

                                                    </td>

                                                    <td class="border-t border-gray-200 p-2">

                                                        <input
                                                            type="text"
                                                            name="items[{{ $itemIndex }}][unit]"
                                                            value="{{ old(
                                                                'items.' . $itemIndex . '.unit'
                                                            ) }}"
                                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                                        >

                                                    </td>

                                                    <td class="border-t border-gray-200 p-2">

                                                        <input
                                                            type="number"
                                                            name="items[{{ $itemIndex }}][unit_price]"
                                                            value="{{ old(
                                                                'items.' . $itemIndex . '.unit_price'
                                                            ) }}"
                                                            min="0"
                                                            step="0.01"
                                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                                        >

                                                    </td>

                                                </tr>

                                            @endfor

                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </div>


                        {{-- Footer --}}
                        <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">

                            <button
                                type="button"
                                @click="createOpen = false"
                                class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white"
                            >
                                Save Draft
                            </button>

                        </div>

                    </form>

                @endif

            </div>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- VIEW ATP MODALS --}}
    {{-- One modal is generated for each ATP on the current page --}}
    {{-- ========================================================= --}}

    @foreach($atps as $atp)

        @php
            $items = $atpItems->get(
                $atp->authority_purchase_id,
                collect()
            );
        @endphp


        <div
            x-show="viewOpen && selectedAtp === {{ $atp->authority_purchase_id }}"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
        >

            <div
                class="fixed inset-0 bg-black/40"
                @click="viewOpen = false"
            ></div>


            <div class="relative flex min-h-full items-center justify-center p-4">

                <div
                    @click.stop
                    class="relative w-full max-w-5xl rounded-2xl bg-white shadow-xl"
                >

                    {{-- Header --}}
                    <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">

                        <div>

                            <div class="flex flex-wrap items-center gap-3">

                                <h3 class="text-xl font-semibold text-slate-900">

                                    {{ $atp->authority_purchase_form_number
                                        ?? 'ATP #' . $atp->authority_purchase_id }}

                                </h3>


                                @if($atp->authority_purchase_status === 'Approved')

                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                        Approved
                                    </span>

                                @elseif($atp->authority_purchase_status === 'Rejected')

                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                        Rejected
                                    </span>

                                @elseif($atp->authority_purchase_submitted_at)

                                    <span class="rounded-full bg-gray-900 px-3 py-1 text-xs font-semibold text-white">
                                        Submitted
                                    </span>

                                @else

                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                        Draft
                                    </span>

                                @endif

                            </div>


                            <p class="mt-1 text-sm text-gray-500">

                                RIS:
                                {{ $atp->ris_form_number
                                    ?? 'RIS-' . $atp->authority_purchase_ris_id }}

                            </p>

                        </div>


                        <button
                            type="button"
                            @click="viewOpen = false"
                            class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                        >
                            ✕
                        </button>

                    </div>


                    {{-- Body --}}
                    <div class="max-h-[70vh] space-y-6 overflow-y-auto p-6">


                        {{-- ATP / RIS Information --}}
                        <div class="grid gap-6 lg:grid-cols-2">


                            {{-- ATP Information --}}
                            <section class="rounded-xl border border-gray-200 p-5">

                                <h4 class="font-semibold text-slate-900">
                                    ATP Information
                                </h4>

                                <dl class="mt-5 grid gap-4 sm:grid-cols-2">

                                    <div>

                                        <dt class="text-xs uppercase tracking-wide text-gray-500">
                                            Purchase Date
                                        </dt>

                                        <dd class="mt-1 text-sm text-gray-700">

                                            @if($atp->authority_purchase_date)

                                                {{ \Carbon\Carbon::parse(
                                                    $atp->authority_purchase_date
                                                )->format('M d, Y') }}

                                            @else
                                                —
                                            @endif

                                        </dd>

                                    </div>


                                    <div>

                                        <dt class="text-xs uppercase tracking-wide text-gray-500">
                                            Supplier
                                        </dt>

                                        <dd class="mt-1 text-sm text-gray-700">

                                            @if($atp->supplier_store_type === 'Physical Store')

                                                {{ $atp->company_name
                                                    ?? 'Physical supplier' }}

                                            @else

                                                {{ $atp->shop_name
                                                    ?? 'Online supplier' }}

                                            @endif

                                        </dd>

                                    </div>


                                    <div>

                                        <dt class="text-xs uppercase tracking-wide text-gray-500">
                                            Received By
                                        </dt>

                                        <dd class="mt-1 text-sm text-gray-700">

                                            {{ $atp->authority_purchase_received_by_name
                                                ?? '—' }}

                                        </dd>

                                    </div>


                                    <div>

                                        <dt class="text-xs uppercase tracking-wide text-gray-500">
                                            Reference PO / PR
                                        </dt>

                                        <dd class="mt-1 text-sm text-gray-700">

                                            {{ $atp->authority_purchase_reference_po_no
                                                ?? '—' }}

                                        </dd>

                                    </div>


                                    @if($atp->authority_purchase_submitted_at)

                                        <div class="sm:col-span-2">

                                            <dt class="text-xs uppercase tracking-wide text-gray-500">
                                                Submitted
                                            </dt>

                                            <dd class="mt-1 text-sm text-gray-700">

                                                {{ \Carbon\Carbon::parse(
                                                    $atp->authority_purchase_submitted_at
                                                )->format('M d, Y h:i A') }}

                                            </dd>

                                        </div>

                                    @endif


                                    @if($atp->authority_purchase_rejection_reason)

                                        <div class="sm:col-span-2 rounded-lg bg-red-50 p-3">

                                            <dt class="text-xs uppercase tracking-wide text-red-500">
                                                Rejection Reason
                                            </dt>

                                            <dd class="mt-1 text-sm text-red-700">

                                                {{ $atp->authority_purchase_rejection_reason }}

                                            </dd>

                                        </div>

                                    @endif

                                </dl>

                            </section>


                            {{-- RIS Summary --}}
                            <section class="rounded-xl border border-gray-200 p-5">

                                <h4 class="font-semibold text-slate-900">
                                    RIS Summary
                                </h4>

                                <dl class="mt-5 grid gap-4">

                                    <div>

                                        <dt class="text-xs uppercase tracking-wide text-gray-500">
                                            RIS
                                        </dt>

                                        <dd class="mt-1 text-sm text-gray-700">

                                            {{ $atp->ris_form_number
                                                ?? 'RIS-' . $atp->authority_purchase_ris_id }}

                                        </dd>

                                    </div>


                                    <div>

                                        <dt class="text-xs uppercase tracking-wide text-gray-500">
                                            RIS Type
                                        </dt>

                                        <dd class="mt-1 text-sm text-gray-700">

                                            {{ $atp->ris_request_type ?? '—' }}

                                        </dd>

                                    </div>


                                    @if($atp->ris_manual_title)

                                        <div>

                                            <dt class="text-xs uppercase tracking-wide text-gray-500">
                                                Title
                                            </dt>

                                            <dd class="mt-1 text-sm text-gray-700">

                                                {{ $atp->ris_manual_title }}

                                            </dd>

                                        </div>

                                    @endif


                                    <div>

                                        <dt class="text-xs uppercase tracking-wide text-gray-500">
                                            Related Equipment
                                        </dt>

                                        <dd class="mt-1 text-sm text-gray-700">

                                            {{ $atp->equipment_name
                                                ?? $atp->report_unlisted_equipment_name
                                                ?? '—' }}

                                        </dd>

                                    </div>


                                    <div>

                                        <dt class="text-xs uppercase tracking-wide text-gray-500">
                                            Report #
                                        </dt>

                                        <dd class="mt-1 text-sm text-gray-700">

                                            {{ $atp->report_id ?? '—' }}

                                        </dd>

                                    </div>

                                </dl>

                            </section>

                        </div>


                        {{-- ATP Items --}}
                        <section>

                            <h4 class="mb-3 font-semibold text-slate-900">
                                ATP Items
                            </h4>


                            <div class="overflow-x-auto rounded-xl border border-gray-200">

                                <table class="w-full min-w-[700px] text-sm">

                                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">

                                        <tr>

                                            <th class="px-3 py-3 text-left">
                                                Description
                                            </th>

                                            <th class="px-3 py-3 text-right">
                                                Qty
                                            </th>

                                            <th class="px-3 py-3 text-right">
                                                Unit
                                            </th>

                                            <th class="px-3 py-3 text-right">
                                                Unit Price
                                            </th>

                                            <th class="px-3 py-3 text-right">
                                                Total
                                            </th>

                                        </tr>

                                    </thead>

                                    <tbody class="divide-y divide-gray-200">

                                        @forelse($items as $item)

                                            <tr>

                                                <td class="px-3 py-3">
                                                    {{ $item->atp_description ?? '—' }}
                                                </td>

                                                <td class="px-3 py-3 text-right">
                                                    {{ $item->atp_quantity ?? '—' }}
                                                </td>

                                                <td class="px-3 py-3 text-right">
                                                    {{ $item->atp_unit ?? '—' }}
                                                </td>

                                                <td class="px-3 py-3 text-right">

                                                    {{ $item->atp_unit_price !== null
                                                        ? number_format($item->atp_unit_price, 2)
                                                        : '—' }}

                                                </td>

                                                <td class="px-3 py-3 text-right font-medium">

                                                    {{ $item->atp_amount !== null
                                                        ? number_format($item->atp_amount, 2)
                                                        : '—' }}

                                                </td>

                                            </tr>

                                        @empty

                                            <tr>

                                                <td
                                                    colspan="5"
                                                    class="px-3 py-6 text-center text-gray-500"
                                                >
                                                    No ATP line items added.
                                                </td>

                                            </tr>

                                        @endforelse

                                    </tbody>

                                </table>

                            </div>

                        </section>

                    </div>


                    {{-- Footer Actions --}}
                    <div class="flex flex-wrap justify-between gap-3 border-t border-gray-200 px-6 py-4">

                        <div class="flex flex-wrap gap-2">

                            @if(
                                !$archiveView
                                && !$atp->authority_purchase_submitted_at
                                && $atp->authority_purchase_status === 'Pending'
                            )

                                <button
                                    type="button"
                                    @click="
                                        viewOpen = false;
                                        openEdit({{ $atp->authority_purchase_id }});
                                    "
                                    class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700"
                                >
                                    Edit Draft
                                </button>


                                <form
                                    method="POST"
                                    action="{{ route(
                                        'purchaser.atp.submit',
                                        $atp->authority_purchase_id
                                    ) }}"
                                >

                                    @csrf

                                    <button
                                        type="submit"
                                        class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white"
                                    >
                                        Submit ATP
                                    </button>

                                </form>

                            @endif


                            @if(
                                !$archiveView
                                && $atp->authority_purchase_status === 'Pending'
                                && $atp->authority_purchase_submitted_at
                            )

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'purchaser.atp.approve',
                                        $atp->authority_purchase_id
                                    ) }}"
                                >

                                    @csrf

                                    <button
                                        type="submit"
                                        class="h-10 rounded-lg bg-green-100 px-5 text-sm font-medium text-green-700"
                                    >
                                        Approve
                                    </button>

                                </form>


                                <button
                                    type="button"
                                    @click="
                                        viewOpen = false;
                                        openReject({{ $atp->authority_purchase_id }});
                                    "
                                    class="h-10 rounded-lg border border-red-300 px-5 text-sm font-medium text-red-700"
                                >
                                    Reject
                                </button>

                            @endif

                        </div>


                        <button
                            type="button"
                            @click="viewOpen = false"
                            class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700"
                        >
                            Close
                        </button>

                    </div>

                </div>

            </div>

        </div>

    @endforeach



    {{-- ========================================================= --}}
    {{-- EDIT ATP MODALS --}}
    {{-- ========================================================= --}}

    @foreach($atps as $atp)

        @php
            $editItems = $atpItems->get(
                $atp->authority_purchase_id,
                collect()
            );
        @endphp


        @if(
            !$atp->authority_purchase_submitted_at
            && $atp->authority_purchase_status === 'Pending'
        )

            <div
                x-show="editOpen && selectedAtp === {{ $atp->authority_purchase_id }}"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto"
            >

                <div
                    class="fixed inset-0 bg-black/40"
                    @click="editOpen = false"
                ></div>


                <div class="relative flex min-h-full items-center justify-center p-4">

                    <div
                        @click.stop
                        class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl"
                    >

                        {{-- Header --}}
                        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">

                            <div>

                                <h3 class="text-xl font-semibold text-slate-900">
                                    Edit ATP Draft
                                </h3>

                                <p class="mt-1 text-sm text-gray-500">

                                    {{ $atp->authority_purchase_form_number
                                        ?? 'ATP-' . $atp->authority_purchase_id }}

                                </p>

                            </div>


                            <button
                                type="button"
                                @click="editOpen = false"
                                class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                            >
                                ✕
                            </button>

                        </div>


                        <form
                            method="POST"
                            action="{{ route(
                                'purchaser.atp.update',
                                $atp->authority_purchase_id
                            ) }}"
                        >

                            @csrf
                            @method('PUT')


                            <div class="max-h-[70vh] space-y-6 overflow-y-auto p-6">


                                {{-- RIS / Supplier --}}
                                <div class="grid gap-4 lg:grid-cols-2">

                                    <div>

                                        <label class="text-xs font-medium text-gray-500">
                                            Approved RIS
                                        </label>

                                        <input
                                            type="text"
                                            disabled
                                            value="{{ $atp->ris_form_number
                                                ?? 'RIS-' . $atp->authority_purchase_ris_id }}"
                                            class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-gray-100 px-3 text-sm text-gray-700"
                                        >

                                    </div>


                                    <div>

                                        <label class="text-xs font-medium text-gray-500">
                                            Supplier
                                        </label>

                                        <select
                                            name="authority_purchase_supplier_id"
                                            required
                                            class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                        >

                                            @foreach($suppliers as $supplier)

                                                <option
                                                    value="{{ $supplier->supplier_id }}"
                                                    {{ $supplier->supplier_id == $atp->authority_purchase_supplier_id
                                                        ? 'selected'
                                                        : '' }}
                                                >

                                                    @if($supplier->supplier_store_type === 'Physical Store')

                                                        {{ $supplier->company_name
                                                            ?? 'Physical supplier #' . $supplier->supplier_id }}

                                                    @else

                                                        {{ $supplier->shop_name
                                                            ?? 'Online supplier #' . $supplier->supplier_id }}

                                                    @endif

                                                </option>

                                            @endforeach

                                        </select>

                                    </div>

                                </div>


                                {{-- Basic Information --}}
                                <div class="grid gap-4 lg:grid-cols-3">

                                    <div>

                                        <label class="text-xs font-medium text-gray-500">
                                            Purchase Date
                                        </label>

                                        <input
                                            type="date"
                                            name="authority_purchase_date"
                                            value="{{ $atp->authority_purchase_date }}"
                                            required
                                            class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                        >

                                    </div>


                                    <div>

                                        <label class="text-xs font-medium text-gray-500">
                                            Received By
                                        </label>

                                        <input
                                            type="text"
                                            name="authority_purchase_received_by_name"
                                            value="{{ $atp->authority_purchase_received_by_name }}"
                                            required
                                            class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                        >

                                    </div>


                                    <div>

                                        <label class="text-xs font-medium text-gray-500">
                                            Reference PO / PR
                                        </label>

                                        <input
                                            type="text"
                                            name="authority_purchase_reference_po_no"
                                            value="{{ $atp->authority_purchase_reference_po_no }}"
                                            class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                        >

                                    </div>

                                </div>


                                {{-- Edit Items --}}
                                <div>

                                    <h4 class="mb-3 text-sm font-semibold text-slate-900">
                                        ATP Items
                                    </h4>


                                    <div class="overflow-x-auto rounded-xl border border-gray-200">

                                        <table class="w-full min-w-[750px] text-sm">

                                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">

                                                <tr>

                                                    <th class="px-3 py-3 text-left">
                                                        Description
                                                    </th>

                                                    <th class="w-28 px-3 py-3 text-left">
                                                        Qty
                                                    </th>

                                                    <th class="w-36 px-3 py-3 text-left">
                                                        Unit
                                                    </th>

                                                    <th class="w-44 px-3 py-3 text-left">
                                                        Unit Price
                                                    </th>

                                                </tr>

                                            </thead>

                                            <tbody>

                                                @foreach($editItems as $itemIndex => $item)

                                                    <tr>

                                                        <td class="border-t border-gray-200 p-2">

                                                            <input
                                                                type="text"
                                                                name="items[{{ $itemIndex }}][description]"
                                                                value="{{ $item->atp_description }}"
                                                                class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                                            >

                                                        </td>

                                                        <td class="border-t border-gray-200 p-2">

                                                            <input
                                                                type="number"
                                                                name="items[{{ $itemIndex }}][quantity]"
                                                                value="{{ $item->atp_quantity }}"
                                                                min="1"
                                                                class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                                            >

                                                        </td>

                                                        <td class="border-t border-gray-200 p-2">

                                                            <input
                                                                type="text"
                                                                name="items[{{ $itemIndex }}][unit]"
                                                                value="{{ $item->atp_unit }}"
                                                                class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                                            >

                                                        </td>

                                                        <td class="border-t border-gray-200 p-2">

                                                            <input
                                                                type="number"
                                                                name="items[{{ $itemIndex }}][unit_price]"
                                                                value="{{ $item->atp_unit_price }}"
                                                                min="0"
                                                                step="0.01"
                                                                class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                                            >

                                                        </td>

                                                    </tr>

                                                @endforeach


                                                {{-- Extra Empty Rows --}}
                                                @for(
                                                    $itemIndex = count($editItems);
                                                    $itemIndex < max(8, count($editItems) + 1);
                                                    $itemIndex++
                                                )

                                                    <tr>

                                                        <td class="border-t border-gray-200 p-2">

                                                            <input
                                                                type="text"
                                                                name="items[{{ $itemIndex }}][description]"
                                                                class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                                            >

                                                        </td>

                                                        <td class="border-t border-gray-200 p-2">

                                                            <input
                                                                type="number"
                                                                name="items[{{ $itemIndex }}][quantity]"
                                                                min="1"
                                                                class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                                            >

                                                        </td>

                                                        <td class="border-t border-gray-200 p-2">

                                                            <input
                                                                type="text"
                                                                name="items[{{ $itemIndex }}][unit]"
                                                                class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                                            >

                                                        </td>

                                                        <td class="border-t border-gray-200 p-2">

                                                            <input
                                                                type="number"
                                                                name="items[{{ $itemIndex }}][unit_price]"
                                                                min="0"
                                                                step="0.01"
                                                                class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                                            >

                                                        </td>

                                                    </tr>

                                                @endfor

                                            </tbody>

                                        </table>

                                    </div>

                                </div>

                            </div>


                            {{-- Footer --}}
                            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">

                                <button
                                    type="button"
                                    @click="editOpen = false"
                                    class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white"
                                >
                                    Update Draft
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        @endif

    @endforeach



    {{-- ========================================================= --}}
    {{-- REJECT ATP MODALS --}}
    {{-- ========================================================= --}}

    @foreach($atps as $atp)

        @if(
            $atp->authority_purchase_status === 'Pending'
            && $atp->authority_purchase_submitted_at
        )

            <div
                x-show="rejectOpen && selectedAtp === {{ $atp->authority_purchase_id }}"
                x-cloak
                class="fixed inset-0 z-[60] overflow-y-auto"
            >

                <div
                    class="fixed inset-0 bg-black/40"
                    @click="rejectOpen = false"
                ></div>


                <div class="relative flex min-h-full items-center justify-center p-4">

                    <div
                        @click.stop
                        class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl"
                    >

                        <form
                            method="POST"
                            action="{{ route(
                                'purchaser.atp.reject',
                                $atp->authority_purchase_id
                            ) }}"
                        >

                            @csrf


                            <div class="border-b border-gray-200 px-6 py-5">

                                <h3 class="text-lg font-semibold text-slate-900">
                                    Reject ATP
                                </h3>

                                <p class="mt-1 text-sm text-gray-500">

                                    {{ $atp->authority_purchase_form_number
                                        ?? 'ATP-' . $atp->authority_purchase_id }}

                                </p>

                            </div>


                            <div class="p-6">

                                <label class="text-xs font-medium text-gray-600">
                                    Rejection Reason
                                </label>

                                <textarea
                                    name="authority_purchase_rejection_reason"
                                    rows="4"
                                    required
                                    placeholder="Explain why this ATP is being rejected"
                                    class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                ></textarea>

                            </div>


                            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">

                                <button
                                    type="button"
                                    @click="rejectOpen = false"
                                    class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    class="h-10 rounded-lg bg-red-600 px-5 text-sm font-medium text-white"
                                >
                                    Confirm Reject
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        @endif

    @endforeach

</div>


{{-- ============================================================= --}}
{{-- XCLOAK --}}
{{-- Prevent Alpine modals flashing while page loads --}}
{{-- ============================================================= --}}

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

@endsection