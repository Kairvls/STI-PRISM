@extends('layouts.admin-layout')

{{-- ===================================================== --}}
{{-- PROCUREMENT REQUEST / RIS APPROVAL PAGE --}}
{{-- ===================================================== --}}

@section('content')

<div class="space-y-6">


    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <div>

        <h1 class="text-2xl font-bold text-gray-900">
            RIS Approval
        </h1>

        <p class="mt-1 text-sm text-gray-600">
            Review and manage Requisition Issue Slips submitted by the Purchaser.
        </p>

    </div>


    {{-- ===================================================== --}}
    {{-- SUCCESS MESSAGE --}}
    {{-- ===================================================== --}}

    @if(session('success'))

        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- ERROR MESSAGE --}}
    {{-- ===================================================== --}}

    @if(session('error'))

        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- CURRENT FILTER --}}
    {{-- ===================================================== --}}

    @php

        $filter = $filter ?? 'all';

        $search = $search ?? '';

    @endphp


    {{-- ===================================================== --}}
    {{-- RIS STATISTIC CARDS --}}
    {{-- ===================================================== --}}

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">


        {{-- ================================================= --}}
        {{-- TOTAL RIS --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="Total number of submitted RIS forms"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Total RIS
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-gray-900">
                    {{ $totalRis }}
                </span>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- PENDING --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="RIS forms currently waiting for review"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Pending
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-amber-600">
                    {{ $pendingRis }}
                </span>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- AMEND --}}
        {{-- Database value is still Rejected --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="RIS forms returned for amendment"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Amend
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-rose-600">
                    {{ $amendRis }}
                </span>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- APPROVED --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="RIS forms that have been approved"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Approved
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-emerald-600">
                    {{ $approvedRis }}
                </span>

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- PROCUREMENT REQUEST TABLE CARD --}}
    {{-- ===================================================== --}}

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">


        {{-- ================================================= --}}
        {{-- TABLE CARD HEADER --}}
        {{-- ================================================= --}}

        <div class="border-b border-gray-100 px-5 py-4">

            <div class="flex flex-col gap-4">


                {{-- ================================================= --}}
                {{-- TABLE TITLE --}}
                {{-- ================================================= --}}

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <h2 class="text-sm font-semibold text-gray-900">
                            RIS Records
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            Requisition Issue Slips forwarded for review
                        </p>

                    </div>


                    {{-- ================================================= --}}
                    {{-- CURRENT RESULT TOTAL --}}
                    {{-- ================================================= --}}

                    <div
                        class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700"
                        title="Number of RIS records matching the current filter"
                    >

                        {{ $risRecords->total() }} total

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- FILTERS + LIVE SEARCH --}}
                {{-- ================================================= --}}

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">


                    {{-- ================================================= --}}
                    {{-- STATUS FILTER TOGGLES --}}
                    {{-- ================================================= --}}

                    <div class="flex flex-wrap items-center gap-2">


                        {{-- ALL --}}

                        <a
                            href="{{ route('admin.procurement-review.ris', ['filter' => 'all']) }}"
                            title="Show all RIS records"
                            class="
                                rounded-lg px-4 py-2 text-sm font-semibold transition
                                {{ $filter === 'all'
                                    ? 'bg-slate-900 text-white shadow-sm'
                                    : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                }}
                            "
                        >
                            All
                        </a>


                        {{-- PENDING --}}

                        <a
                            href="{{ route('admin.procurement-review.ris', ['filter' => 'pending']) }}"
                            title="Show only Pending RIS records"
                            class="
                                rounded-lg px-4 py-2 text-sm font-semibold transition
                                {{ $filter === 'pending'
                                    ? 'border border-amber-300 bg-amber-50 text-amber-700'
                                    : 'border border-gray-200 bg-white text-gray-600 hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700'
                                }}
                            "
                        >
                            Pending
                        </a>


                        {{-- AMEND --}}
                        {{-- Database filter remains rejected --}}

                        <a
                            href="{{ route('admin.procurement-review.ris', ['filter' => 'rejected']) }}"
                            title="Show RIS records returned for amendment"
                            class="
                                rounded-lg px-4 py-2 text-sm font-semibold transition
                                {{ $filter === 'rejected'
                                    ? 'border border-rose-300 bg-rose-50 text-rose-700'
                                    : 'border border-gray-200 bg-white text-gray-600 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700'
                                }}
                            "
                        >
                            Amend
                        </a>


                        {{-- APPROVED --}}

                        <a
                            href="{{ route('admin.procurement-review.ris', ['filter' => 'approved']) }}"
                            title="Show only Approved RIS records"
                            class="
                                rounded-lg px-4 py-2 text-sm font-semibold transition
                                {{ $filter === 'approved'
                                    ? 'border border-emerald-300 bg-emerald-50 text-emerald-700'
                                    : 'border border-gray-200 bg-white text-gray-600 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700'
                                }}
                            "
                        >
                            Approved
                        </a>

                    </div>


                    {{-- ================================================= --}}
                    {{-- LIVE SEARCH --}}
                    {{-- ================================================= --}}

                    <form
                        id="risSearchForm"
                        method="GET"
                        action="{{ route('admin.procurement-review.ris') }}"
                        class="w-full lg:max-w-md"
                    >

                        {{-- Keep current status filter when searching --}}

                        <input
                            type="hidden"
                            name="filter"
                            value="{{ $filter }}"
                        >


                        <div class="relative">

                            {{-- Search icon --}}

                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">

                                <svg
                                    class="h-4 w-4 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                                    ></path>

                                </svg>

                            </div>


                            <input
                                id="risLiveSearch"
                                type="search"
                                name="search"
                                value="{{ $search }}"
                                placeholder="Search RIS, requester, equipment, status..."
                                autocomplete="off"
                                title="Search RIS records"
                                class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                            >

                        </div>

                    </form>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- RIS TABLE --}}
        {{-- ===================================================== --}}

        <div class="overflow-x-auto">

            <table class="w-full min-w-[1250px]">


                {{-- ================================================= --}}
                {{-- TABLE HEADER --}}
                {{-- ================================================= --}}

                <thead class="border-b border-gray-200 bg-gray-50">

                    <tr>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            RIS No.
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Request
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Equipment
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Requested By
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Status
                        </th>

                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Amount
                        </th>

                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Actions
                        </th>

                    </tr>

                </thead>


                {{-- ================================================= --}}
                {{-- TABLE BODY --}}
                {{-- ================================================= --}}

                <tbody class="divide-y divide-gray-100">

                    @forelse($risRecords as $ris)


                        {{-- ================================================= --}}
                        {{-- RIS ROW --}}
                        {{-- Keep deprecated rows visually grayed --}}
                        {{-- Buttons themselves will NOT be grayed --}}
                        {{-- ================================================= --}}

                        <tr
                            class="
                                transition hover:bg-gray-50/70
                                {{ $ris->ris_status !== 'Pending'
                                    ? 'bg-gray-50 text-gray-500'
                                    : ''
                                }}
                            "
                        >


                            {{-- ================================================= --}}
                            {{-- RIS NUMBER --}}
                            {{-- ================================================= --}}

                            <td class="px-5 py-4">

                                <div
                                    class="text-sm font-semibold {{ $ris->ris_status !== 'Pending' ? 'text-gray-500' : 'text-gray-900' }}"
                                    title="RIS Number"
                                >

                                    {{ $ris->ris_form_number ?? 'RIS-' . $ris->ris_id }}

                                </div>

                            </td>


                            {{-- ================================================= --}}
                            {{-- REQUEST --}}
                            {{-- Person associated with the submitted request --}}
                            {{-- ================================================= --}}

                            <td class="px-5 py-4">

                                <div
                                    class="text-sm font-medium {{ $ris->ris_status !== 'Pending' ? 'text-gray-500' : 'text-gray-700' }}"
                                    title="Person who requested this RIS"
                                >

                                    {{ $ris->ris_requested_by_signature ?? 'Purchaser' }}

                                </div>

                            </td>


                            {{-- ================================================= --}}
                            {{-- EQUIPMENT --}}
                            {{-- ================================================= --}}

                            <td class="px-5 py-4">

                                <div
                                    class="max-w-[220px] text-sm {{ $ris->ris_status !== 'Pending' ? 'text-gray-500' : 'text-gray-700' }}"
                                    title="Equipment included in this RIS"
                                >

                                    {{
                                        $ris->equipment_name
                                        ?? $ris->report_unlisted_equipment_name
                                        ?? 'Unknown Equipment'
                                    }}

                                </div>

                            </td>


                            {{-- ================================================= --}}
                            {{-- REQUESTED BY --}}
                            {{-- Person who submitted/sent the RIS --}}
                            {{-- ================================================= --}}

                            <td class="px-5 py-4">

                                <div
                                    class="text-sm font-medium {{ $ris->ris_status !== 'Pending' ? 'text-gray-500' : 'text-gray-700' }}"
                                    title="Person who sent this RIS request"
                                >

                                    {{ $ris->ris_requested_by_signature ?? 'Purchaser' }}

                                </div>


                                {{-- Submitted date --}}

                                <div
                                    class="mt-1 text-xs text-gray-400"
                                    title="Date the RIS was submitted"
                                >

                                    {{ $ris->ris_requested_by_date ?? 'N/A' }}

                                </div>

                            </td>


                            {{-- ================================================= --}}
                            {{-- STATUS --}}
                            {{-- ================================================= --}}

                            <td class="px-5 py-4">


                                {{-- PENDING --}}

                                @if($ris->ris_status === 'Pending')

                                    <span
                                        class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"
                                        title="This RIS is waiting for review"
                                    >
                                        Pending
                                    </span>


                                {{-- APPROVED --}}

                                @elseif($ris->ris_status === 'Approved')

                                    <span
                                        class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                        title="This RIS has been approved"
                                    >
                                        Approved
                                    </span>


                                {{-- AMEND --}}
                                {{-- Database value remains Rejected --}}

                                @elseif($ris->ris_status === 'Rejected')

                                    <span
                                        class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700"
                                        title="This RIS was returned for amendment"
                                    >
                                        Amend
                                    </span>


                                {{-- OTHER STATUS --}}

                                @else

                                    <span
                                        class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-600"
                                        title="Current RIS status"
                                    >
                                        {{ $ris->ris_status }}
                                    </span>

                                @endif

                            </td>


                            {{-- ================================================= --}}
                            {{-- AMOUNT --}}
                            {{-- ================================================= --}}

                            <td
                                class="px-5 py-4 text-right text-sm font-semibold {{ $ris->ris_status !== 'Pending' ? 'text-gray-500' : 'text-gray-900' }}"
                                title="Total amount of this RIS"
                            >

                                ₱{{ number_format((float) ($ris->ris_total_amount ?? 0), 2) }}

                            </td>


                            {{-- ================================================= --}}
                            {{-- ACTIONS --}}
                            {{-- EVERYTHING INTERACTABLE IS INSIDE THIS COLUMN --}}
                            {{-- ACTIONS ARE CENTERED --}}
                            {{-- ================================================= --}}

                            <td class="px-5 py-4">

                                <div class="flex flex-wrap items-center justify-center gap-2">


                                    {{-- ================================================= --}}
                                    {{-- VIEW / PREVIEW --}}
                                    {{-- Button stays active even on old/deprecated forms --}}
                                    {{-- ================================================= --}}

                                    <button
                                        type="button"
                                        onclick="openRisPreviewModal('{{ $ris->ris_id }}')"
                                        title="Preview this RIS form"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                                    >

                                        <svg
                                            class="h-3.5 w-3.5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            ></path>

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            ></path>

                                        </svg>

                                        View

                                    </button>


                                    {{-- ================================================= --}}
                                    {{-- PENDING ACTIONS --}}
                                    {{-- ================================================= --}}

                                    @if($ris->ris_status === 'Pending')


                                        {{-- ================================================= --}}
                                        {{-- NORMAL APPROVAL --}}
                                        {{-- ================================================= --}}

                                        <form
                                            method="POST"
                                            action="{{ route('admin.procurement-review.ris.approve', $ris->ris_id) }}"
                                        >

                                            @csrf

                                            <button
                                                type="submit"
                                                title="Approve this RIS and forward it to the President for final approval"
                                                onclick="return confirm('Approve this RIS and forward it to the President for final approval?')"
                                                class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                            >
                                                Approved for President
                                            </button>

                                        </form>


                                        {{-- ================================================= --}}
                                        {{-- DIRECT APPROVAL --}}
                                        {{-- ================================================= --}}

                                        <form
                                            method="POST"
                                            action="{{ route('admin.procurement-review.ris.direct-approve', $ris->ris_id) }}"
                                        >

                                            @csrf

                                            <button
                                                type="submit"
                                                title="Immediately approve this RIS and return it to the Purchaser"
                                                onclick="return confirm('Directly approve this RIS and return it to the Purchaser? This will bypass the normal signing stage.')"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800"
                                            >

                                                <svg
                                                    class="h-3.5 w-3.5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >

                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M5 13l4 4L19 7"
                                                    ></path>

                                                </svg>

                                                Direct Approval

                                            </button>

                                        </form>


                                        {{-- ================================================= --}}
                                        {{-- AMEND --}}
                                        {{-- Backend route remains reject --}}
                                        {{-- Only visible wording changed --}}
                                        {{-- ================================================= --}}

                                        <form
                                            method="POST"
                                            action="{{ route('admin.procurement-review.ris.reject', $ris->ris_id) }}"
                                        >

                                            @csrf

                                            <button
                                                type="submit"
                                                title="Return this RIS to the Purchaser for amendment"
                                                onclick="return confirm('Return this RIS to the Purchaser for amendment?')"
                                                class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"
                                            >
                                                Amend
                                            </button>

                                        </form>

                                    @endif


                                </div>

                            </td>

                        </tr>


                    @empty


                        {{-- ================================================= --}}
                        {{-- EMPTY TABLE --}}
                        {{-- ================================================= --}}

                        <tr>

                            <td
                                colspan="7"
                                class="px-5 py-16 text-center"
                            >

                                <div class="mx-auto flex max-w-sm flex-col items-center">

                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400">

                                        <svg
                                            class="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            ></path>

                                        </svg>

                                    </div>

                                    <h3 class="mt-3 text-sm font-semibold text-gray-700">
                                        No RIS records found
                                    </h3>

                                    <p class="mt-1 text-xs text-gray-400">

                                        No RIS records match the selected filter or search.

                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- ===================================================== --}}
        {{-- CUSTOM PAGINATION --}}
        {{-- Format: < current page > --}}
        {{-- Bottom right --}}
        {{-- ===================================================== --}}

        @if($risRecords->hasPages())

            <div class="flex items-center justify-end border-t border-gray-100 px-5 py-4">

                <div class="flex items-center gap-1">


                    {{-- ================================================= --}}
                    {{-- PREVIOUS PAGE --}}
                    {{-- ================================================= --}}

                    @if($risRecords->onFirstPage())

                        <span
                            class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-300"
                            title="No previous page"
                        >
                            &lt;
                        </span>

                    @else

                        <a
                            href="{{ $risRecords->previousPageUrl() }}"
                            title="Previous page"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                        >
                            &lt;
                        </a>

                    @endif


                    {{-- ================================================= --}}
                    {{-- CURRENT PAGE --}}
                    {{-- ================================================= --}}

                    <span
                        class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-slate-900 px-3 text-sm font-semibold text-white"
                        title="Current page {{ $risRecords->currentPage() }}"
                    >

                        {{ $risRecords->currentPage() }}

                    </span>


                    {{-- ================================================= --}}
                    {{-- NEXT PAGE --}}
                    {{-- ================================================= --}}

                    @if($risRecords->hasMorePages())

                        <a
                            href="{{ $risRecords->nextPageUrl() }}"
                            title="Next page"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                        >
                            &gt;
                        </a>

                    @else

                        <span
                            class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-300"
                            title="No next page"
                        >
                            &gt;
                        </span>

                    @endif

                </div>

            </div>

        @endif

    </div>

</div>


{{-- ===================================================== --}}
{{-- RIS PREVIEW MODAL --}}
{{-- FULL PREVIEW KEPT --}}
{{-- ===================================================== --}}

<div
    id="risPreviewModal"
    class="fixed inset-0 z-50 hidden"
>

    <div
        class="flex h-screen items-center justify-center bg-black/30 p-2 backdrop-blur-[2px]"
        onclick="closeRisPreviewModal()"
    >

        <div
            class="h-[calc(100vh-1rem)] w-full max-w-6xl overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
            onclick="event.stopPropagation()"
        >


            {{-- ===================================================== --}}
            {{-- MODAL HEADER --}}
            {{-- ===================================================== --}}

            <div class="border-b border-gray-100 px-6 py-5">

                <div class="flex items-start justify-between gap-4">

                    <div>

                        <h3 class="text-lg font-bold text-slate-950">
                            RIS Form Preview
                        </h3>

                        <p
                            id="risPreviewModalSubtitle"
                            class="mt-1 text-sm text-slate-600"
                        >
                            Requisition and Issue Slip
                        </p>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- CLOSE BUTTON --}}
                    {{-- ===================================================== --}}

                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                        onclick="closeRisPreviewModal()"
                        title="Close RIS preview"
                        aria-label="Close"
                    >

                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            ></path>

                        </svg>

                    </button>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- RIS PREVIEW IFRAME --}}
            {{-- ===================================================== --}}

            <div class="h-full overflow-auto bg-gray-50">

                <iframe
                    id="risPreviewIframe"
                    class="h-full w-full"
                    style="min-height: calc(100vh - 140px);"
                    src="about:blank"
                    title="RIS Form Preview"
                ></iframe>

            </div>

        </div>

    </div>

</div>


{{-- ===================================================== --}}
{{-- RIS PAGE JAVASCRIPT --}}
{{-- ===================================================== --}}

<script>


    // =====================================================
    // LIVE SEARCH
    // Automatically searches after user stops typing.
    // =====================================================

    let risSearchTimer = null;

    const risSearchInput =
        document.getElementById('risLiveSearch');

    const risSearchForm =
        document.getElementById('risSearchForm');


    if (risSearchInput && risSearchForm) {

        risSearchInput.addEventListener('input', function () {

            // Cancel the previous timer.
            clearTimeout(risSearchTimer);


            // Wait 400ms before submitting.
            // This prevents a request on every single keystroke.
            risSearchTimer = setTimeout(function () {

                risSearchForm.submit();

            }, 400);

        });

    }


    // =====================================================
    // OPEN RIS PREVIEW
    // =====================================================

    function openRisPreviewModal(risId) {

        const modal =
            document.getElementById('risPreviewModal');

        const iframe =
            document.getElementById('risPreviewIframe');

        const subtitle =
            document.getElementById('risPreviewModalSubtitle');


        if (!modal || !iframe) {

            return;

        }


        // =====================================================
        // UPDATE MODAL TITLE
        // =====================================================

        if (subtitle) {

            subtitle.textContent =
                `RIS #${risId}`;

        }


        // =====================================================
        // LOAD RIS FORM
        //
        // The timestamp is a cache buster.
        // eg. it prevents the browser from showing an older
        // cached version of the RIS form.
        // =====================================================

        iframe.src =
            `/admin/procurement-review/ris/${risId}/print?ts=${Date.now()}`;


        // =====================================================
        // SHOW MODAL
        // =====================================================

        modal.classList.remove('hidden');

    }


    // =====================================================
    // CLOSE RIS PREVIEW
    // =====================================================

    function closeRisPreviewModal() {

        const modal =
            document.getElementById('risPreviewModal');

        const iframe =
            document.getElementById('risPreviewIframe');


        // =====================================================
        // CLEAR PREVIEW
        // =====================================================

        if (iframe) {

            iframe.src = 'about:blank';

        }


        // =====================================================
        // HIDE MODAL
        // =====================================================

        if (modal) {

            modal.classList.add('hidden');

        }

    }


    // =====================================================
    // CLOSE PREVIEW WITH ESCAPE KEY
    // =====================================================

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                closeRisPreviewModal();

            }

        }
    );

</script>

@endsection