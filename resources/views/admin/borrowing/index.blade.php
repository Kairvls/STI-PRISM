@extends ("layouts.maintenance-layout")

@php
    use Carbon\Carbon;
@endphp

@section ("content")

    <div
        class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end"
    >
        @if (!empty($showArchived))
            <a
                href="{{ route('admin.borrowing.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i data-lucide="list" class="h-4 w-4"></i>
                Show active records
            </a>
        @else
            <a
                href="{{ route('admin.borrowing.index', ['archived' => 1]) }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i data-lucide="archive" class="h-4 w-4"></i>
                Show archived
            </a>

            @if (($overdueBorrowings ?? 0) > 0)
                @if (request('status') === 'Overdue' || !empty($attentionFocus))
                    <a
                        href="{{ route('admin.borrowing.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        <i data-lucide="list" class="h-4 w-4"></i>
                        Show all records
                    </a>
                @else
                    <a
                        href="{{ route('admin.borrowing.index', ['status' => 'Overdue']) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-[13px] font-semibold text-amber-900 transition hover:bg-amber-100"
                    >
                        <i data-lucide="alert-triangle" class="h-4 w-4"></i>
                        Show overdue only
                        <span class="inline-flex min-w-[20px] items-center justify-center rounded-full bg-amber-500 px-1.5 text-[11px] font-bold text-white">
                            {{ $overdueBorrowings }}
                        </span>
                    </a>
                @endif
            @endif

            <button
                type="button"
                onclick="openBorrowModal()"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-blue-800"
            >
                <i data-lucide="plus" class="h-4 w-4"></i>
                Borrow Equipment
            </button>
        @endif
    </div>

    <!--@if (($overdueBorrowings ?? 0) > 0 && request('status') !== 'Overdue')
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ $overdueBorrowings }} overdue {{ \Illuminate\Support\Str::plural('record', $overdueBorrowings) }} need attention.
        </div>
    @endif-->

    @php
        $borrowingMonthlyHint = $borrowingMonthlyPercentage === null
            ? 'New activity vs last month'
            : (($borrowingMonthlyPercentage > 0 ? '+' : '') . number_format($borrowingMonthlyPercentage, 2) . '% vs last month');
    @endphp
    <div class="mb-6 mt-6">
    </div>

    @include('partials.attention-focus-chip', ['focus' => $attentionFocus ?? null, 'total' => $borrowings->total()])

    <div class="rounded-3xl bg-white">
        <!--<div class="mb-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-black">Borrowing Records</h1>

            <button
                onclick="openBorrowModal()"
                class="rounded-xl bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
            >
                Borrow Equipment
            </button>
        </div>-->

        {{-- ========================================================= --}}
        {{-- BORROWING RECORDS TABLE --}}
        {{-- ========================================================= --}}

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">

            {{-- ===================================================== --}}
            {{-- TABLE HEADER INFORMATION --}}
            {{-- ===================================================== --}}

            <div
                class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4
                    sm:flex-row sm:items-center sm:justify-between"
            >

                <div class="flex items-center gap-3">

                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center
                            rounded-lg bg-slate-100 text-slate-600"
                    >
                        <i
                            data-lucide="clipboard-list"
                            class="h-4 w-4"
                        ></i>
                    </div>


                    <div>

                        <h2 class="text-sm font-semibold text-slate-900">
                            {{ !empty($showArchived) ? 'Archived Borrowing Records' : 'Borrowing Records' }}
                        </h2>

                    </div>

                </div>


                {{-- TOTAL RECORDS --}}
                <div
                    class="inline-flex w-fit items-center gap-2
                        rounded-lg border border-slate-200
                        bg-slate-50 px-3 py-2
                        text-xs font-medium text-slate-500"
                >
                    <i
                        data-lucide="package"
                        class="h-3.5 w-3.5"
                    ></i>

                    {{ $borrowings->total() }} total

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- SEARCH AND FILTER BAR --}}
            {{-- ADD BETWEEN TABLE HEADER INFORMATION AND TABLE --}}
            {{-- ===================================================== --}}

            <form method="GET" action="{{ url()->current() }}" data-auto-filter
                class="border-b border-slate-200 px-5 py-4"
            >
                @if (!empty($showArchived))
                    <input type="hidden" name="archived" value="1">
                @endif

                <div
                    class="flex flex-col gap-3
                        sm:flex-row sm:items-center"
                >

                    {{-- ================================================= --}}
                    {{-- SEARCH --}}
                    {{-- ================================================= --}}

                    <div class="relative min-w-0 flex-1">

                        <i
                            data-lucide="search"
                            class="pointer-events-none absolute
                                left-3 top-1/2 h-4 w-4
                                -translate-y-1/2 text-slate-400"
                        ></i>

                        <input
                            type="search"

                            name="search"

                            value="{{ request('search') }}"

                            placeholder="Search equipment, borrower, department, or authorized person..."

                            class="h-9 w-full rounded-lg
                                border border-slate-200
                                bg-white pl-10 pr-3
                                text-sm text-slate-700
                                outline-none transition
                                placeholder:text-slate-400
                                focus:border-slate-400
                                focus:ring-2 focus:ring-slate-100"
                        >

                    </div>


                    {{-- ================================================= --}}
                    {{-- BORROWER FILTER --}}
                    {{-- ================================================= --}}

                    <div class="relative">
                        <select
                            name="borrower"
                            data-searchable="1"
                            data-search-placeholder="Search borrower…"
                            class="h-9 min-w-[180px]
                                appearance-none rounded-lg
                                border border-slate-200
                                bg-white pl-3 pr-9
                                text-sm text-slate-600
                                outline-none transition
                                focus:border-slate-400
                                focus:ring-2 focus:ring-slate-100"
                        >
                            <option value="">All borrowers</option>
                            @foreach (($borrowers ?? collect()) as $borrowerName)
                                <option value="{{ $borrowerName }}" @selected(($borrowerFilter ?? request('borrower')) === $borrowerName)>
                                    {{ $borrowerName }}
                                </option>
                            @endforeach
                        </select>
                        <i
                            data-lucide="chevron-down"
                            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        ></i>
                    </div>

                    {{-- ================================================= --}}
                    {{-- STATUS FILTER --}}
                    {{-- ================================================= --}}

                    <div class="relative">

                        <select
                            name="status"

                            class="h-9 min-w-[150px]
                                appearance-none rounded-lg
                                border border-slate-200
                                bg-white pl-3 pr-9
                                text-sm text-slate-600
                                outline-none transition
                                focus:border-slate-400
                                focus:ring-2 focus:ring-slate-100"
                        >

                            <option value="">
                                All Status
                            </option>

                            <option
                                value="Borrowed"
                                @selected(request('status') === 'Borrowed')
                            >
                                Borrowed
                            </option>

                            <option
                                value="Returned"
                                @selected(request('status') === 'Returned')
                            >
                                Returned
                            </option>

                            <option
                                value="Overdue"
                                @selected(request('status') === 'Overdue' || !empty($attentionFocus))
                            >
                                Overdue
                            </option>

                        </select>


                        <i
                            data-lucide="chevron-down"
                            class="pointer-events-none absolute
                                right-3 top-1/2 h-4 w-4
                                -translate-y-1/2 text-slate-400"
                        ></i>

                    </div>

                    {{-- ================================================= --}}
                    {{-- CALENDAR DATE FILTER --}}
                    {{-- ================================================= --}}

                    @php
                        $hasDateFilter = filled($dateFrom ?? request('date_from')) || filled($dateTo ?? request('date_to'));
                    @endphp
                    <div
                        class="relative"
                        x-data="{
                            open: {{ $hasDateFilter ? 'true' : 'false' }},
                            from: @js($dateFrom ?? request('date_from', '')),
                            to: @js($dateTo ?? request('date_to', '')),
                        }"
                        @keydown.escape.window="open = false"
                    >
                        <button
                            type="button"
                            @click="open = !open"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border transition
                                {{ $hasDateFilter
                                    ? 'border-[#0025cc]/30 bg-blue-50 text-[#0025cc]'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}"
                            aria-label="Filter by borrow date"
                            title="Filter by borrow date"
                        >
                            <i data-lucide="calendar" class="h-4 w-4"></i>
                        </button>

                        <div
                            x-show="open"
                            x-cloak
                            @click.outside="open = false"
                            x-transition
                            class="absolute right-0 z-30 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-4 shadow-xl"
                        >
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Borrow date</p>
                            <div class="mt-3 space-y-3">
                                <div>
                                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">From</label>
                                    <input
                                        type="date"
                                        name="date_from"
                                        x-model="from"
                                        class="h-9 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                                    >
                                </div>
                                <div>
                                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">To</label>
                                    <input
                                        type="date"
                                        name="date_to"
                                        x-model="to"
                                        class="h-9 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                                    >
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between gap-2">
                                <button
                                    type="button"
                                    class="text-xs font-semibold text-slate-500 hover:text-slate-800"
                                    @click="from = ''; to = ''; $el.closest('form').requestSubmit()"
                                >
                                    Clear dates
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex h-8 items-center rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white hover:bg-blue-800"
                                >
                                    Apply dates
                                </button>
                            </div>
                            @if ($hasDateFilter)
                                <p class="mt-3 text-[11px] text-slate-500">
                                    {{ filled($dateFrom ?? request('date_from')) ? \Carbon\Carbon::parse($dateFrom ?? request('date_from'))->format('M j, Y') : '…' }}
                                    –
                                    {{ filled($dateTo ?? request('date_to')) ? \Carbon\Carbon::parse($dateTo ?? request('date_to'))->format('M j, Y') : '…' }}
                                </p>
                            @endif
                        </div>
                    </div>


                    {{-- ================================================= --}}
                    {{-- APPLY BUTTON --}}
                    {{-- ================================================= --}}

                    <button
                        type="submit"

                        class="inline-flex h-9 items-center
                            justify-center gap-2 rounded-lg
                            bg-[#0025cc] px-4
                            text-sm font-semibold text-white
                            transition hover:bg-blue-800"
                    >

                        <i
                            data-lucide="filter"
                            class="h-4 w-4"
                        ></i>

                        Apply

                    </button>


                    {{-- ================================================= --}}
                    {{-- CLEAR BUTTON --}}
                    {{-- ================================================= --}}

                    @if (
                        request()->filled('search')
                        || request()->filled('status')
                        || request()->filled('borrower')
                        || request()->filled('date_from')
                        || request()->filled('date_to')
                    )

                        <a
                            href="{{ url()->current() }}"

                            class="inline-flex h-9 items-center
                                justify-center gap-2 rounded-lg
                                border border-slate-200
                                bg-white px-4
                                text-sm font-medium text-slate-600
                                transition
                                hover:bg-slate-50
                                hover:text-slate-900"
                        >

                            <i
                                data-lucide="x"
                                class="h-4 w-4"
                            ></i>

                            Clear

                        </a>

                    @endif

                </div>

            </form>



            {{-- ===================================================== --}}
            {{-- TABLE --}}
            {{-- ===================================================== --}}

            <div class="overflow-x-auto">

                <table class="w-full  text-left">

                    {{-- ================================================= --}}
                    {{-- TABLE HEADER --}}
                    {{-- ================================================= --}}

                    <thead class="border-b border-slate-200 bg-slate-50/70">

                        <tr
                            class="text-[10px] font-semibold uppercase
                                tracking-[0.08em] text-slate-400"
                        >

                            <th class="px-5 py-3">
                                Equipment
                            </th>

                            <th class="px-5 py-3">
                                Borrower
                            </th>

                            <th class="px-5 py-3">
                                Department
                            </th>

                            <th class="px-5 py-3">
                                Status
                            </th>

                            <th class="px-5 py-3">
                                Borrow Date
                            </th>

                            <th class="px-5 py-3">
                                Expected Return
                            </th>

                            <th class="px-5 py-3">
                                Actual Return
                            </th>

                            <th class="w-[130px] px-5 py-3 text-center">
                                Actions
                            </th>

                        </tr>

                    </thead>



                    {{-- ================================================= --}}
                    {{-- TABLE BODY --}}
                    {{-- ================================================= --}}

                    <tbody class="divide-y divide-slate-100">

                        @forelse ($borrowings as $record)

                            @php
                                /*
                                |--------------------------------------------------------------------------
                                | BORROWING STATUS DESIGN
                                |--------------------------------------------------------------------------
                                */

                                $borrowingStatus =
                                    $record->borrowing_status ?? "Unknown";


                                $statusClass = match ($borrowingStatus) {
                                    "Borrowed" => "bg-sky-50 text-sky-700",
                                    "Returned" => "bg-emerald-50 text-emerald-700",
                                    "Overdue" => "bg-rose-50 text-rose-700",
                                    default => "bg-slate-100 text-slate-600",
                                };


                                $statusDotClass = match ($borrowingStatus) {
                                    "Borrowed" =>
                                        "bg-blue-500",

                                    "Returned" =>
                                        "bg-emerald-500",

                                    "Overdue" =>
                                        "bg-red-500",

                                    default =>
                                        "bg-slate-400",
                                };
                            @endphp



                            <tr
                                class="group transition-colors
                                    hover:bg-slate-50/70"
                            >

                                {{-- ===================================== --}}
                                {{-- EQUIPMENT --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex items-center gap-3">

                                        <div
                                            class="flex h-9 w-9 shrink-0
                                                items-center justify-center
                                                rounded-lg border border-slate-200
                                                bg-white text-slate-400"
                                        >
                                            <i
                                                data-lucide="package"
                                                class="h-4 w-4"
                                            ></i>
                                        </div>


                                        <div class="min-w-0">

                                            <p
                                                class="max-w-[200px] truncate
                                                    text-sm font-semibold
                                                    text-slate-800"

                                                data-tooltip="{{ $record->equipment_name }}"
                                            >
                                                {{ $record->equipment_name }}
                                            </p>


                                            <p
                                                class="mt-0.5 max-w-[200px] truncate text-[11px]
                                                    text-slate-400"
                                            >
                                                {{ $record->equipment_asset_tag ?: 'No asset tag' }}
                                                @if ($record->borrowed_from_room_name)
                                                    · from {{ $record->borrowed_from_room_name }}
                                                @endif
                                            </p>

                                        </div>

                                    </div>

                                </td>



                                {{-- ===================================== --}}
                                {{-- BORROWER --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">
                                    @php
                                        $borrowerName = trim((string) ($record->borrowing_borrower_name ?? ''));
                                        $borrowerCaution = ($borrowerCautions ?? [])[$borrowerName] ?? null;
                                        $borrowerOverdue = (int) ($borrowerCaution['overdue'] ?? 0);
                                        $borrowerOpen = (int) ($borrowerCaution['open'] ?? 0);
                                    @endphp

                                    <div class="flex items-center gap-2">

                                        <div
                                            class="flex h-7 w-7 shrink-0
                                                items-center justify-center
                                                rounded-full bg-slate-100
                                                text-[10px] font-semibold
                                                text-slate-500"
                                        >
                                            {{
                                                strtoupper(
                                                    substr(
                                                        $borrowerName !== '' ? $borrowerName : '?',
                                                        0,
                                                        1
                                                    )
                                                )
                                            }}
                                        </div>

                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span
                                                    class="max-w-[160px] truncate
                                                        text-xs font-medium
                                                        text-slate-700"
                                                >
                                                    {{ $borrowerName !== '' ? $borrowerName : '—' }}
                                                </span>
                                                @if ($borrowerOverdue > 0)
                                                    <span
                                                        class="inline-flex shrink-0 items-center gap-0.5 rounded-md bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-700 ring-1 ring-rose-100"
                                                        title="{{ $borrowerOverdue }} overdue · {{ $borrowerOpen }} still out. Do not lend more until returns are cleared."
                                                        data-pur-tip-title="Borrower caution"
                                                        data-pur-tip="{{ $borrowerOverdue }} overdue {{ \Illuminate\Support\Str::plural('item', $borrowerOverdue) }} · {{ $borrowerOpen }} still out. Clear returns before lending more."
                                                        data-pur-tip-accent="#be123c"
                                                    >
                                                        <i data-lucide="alert-triangle" class="h-3 w-3"></i>
                                                        Caution
                                                    </span>
                                                @elseif ($borrowerOpen > 1)
                                                    <span
                                                        class="inline-flex shrink-0 items-center gap-0.5 rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 ring-1 ring-amber-100"
                                                        title="{{ $borrowerOpen }} items still borrowed."
                                                        data-pur-tip-title="Open borrows"
                                                        data-pur-tip="{{ $borrowerOpen }} items still out for this borrower."
                                                        data-pur-tip-accent="#b45309"
                                                    >
                                                        <i data-lucide="clock-3" class="h-3 w-3"></i>
                                                        {{ $borrowerOpen }} out
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                    </div>

                                </td>



                                {{-- ===================================== --}}
                                {{-- DEPARTMENT --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <span
                                        class="inline-flex rounded-md
                                            bg-slate-100 px-2 py-1
                                            text-[11px] font-medium
                                            text-slate-600"
                                    >
                                        {{
                                            $record->borrowing_borrower_department
                                                ?? "No department"
                                        }}
                                    </span>

                                </td>



                                {{-- ===================================== --}}
                                {{-- STATUS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $statusClass }}">
                                        {{ $borrowingStatus }}
                                    </span>
                                </td>



                                {{-- ===================================== --}}
                                {{-- BORROW DATE --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <span
                                        class="whitespace-nowrap
                                            text-xs text-slate-600"
                                    >
                                        {{
                                            $record->borrowing_date
                                                ? Carbon::parse(
                                                    $record->borrowing_date
                                                )->format("M d, Y")
                                                : "—"
                                        }}
                                    </span>

                                </td>



                                {{-- ===================================== --}}
                                {{-- EXPECTED RETURN --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <span
                                        class="whitespace-nowrap
                                            text-xs font-medium
                                            text-slate-700"
                                    >
                                        {{
                                            $record->borrowing_expected_return_date
                                                ? Carbon::parse(
                                                    $record->borrowing_expected_return_date
                                                )->format("M d, Y")
                                                : "—"
                                        }}
                                    </span>

                                </td>



                                {{-- ===================================== --}}
                                {{-- ACTUAL RETURN --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    @if ($record->borrowing_actual_return_date)

                                        <div class="flex items-center gap-1.5">

                                            <i
                                                data-lucide="check"
                                                class="h-3.5 w-3.5
                                                    text-emerald-500"
                                            ></i>

                                            <span
                                                class="whitespace-nowrap
                                                    text-xs text-slate-600"
                                            >
                                                {{
                                                    Carbon::parse(
                                                        $record->borrowing_actual_return_date
                                                    )->format("M d, Y")
                                                }}
                                            </span>

                                        </div>

                                    @elseif ($borrowingStatus === 'Overdue' && $record->borrowing_expected_return_date)

                                        @php
                                            $daysLate = (int) Carbon::parse($record->borrowing_expected_return_date)->startOfDay()->diffInDays(today());
                                        @endphp
                                        <span class="whitespace-nowrap text-xs font-semibold text-rose-600">
                                            Not returned · {{ $daysLate }} day{{ $daysLate === 1 ? '' : 's' }} late
                                        </span>

                                    @else

                                        <span class="text-xs text-slate-400">
                                            Not returned yet
                                        </span>

                                    @endif

                                </td>



                                {{-- ===================================== --}}
                                {{-- ACTIONS --}}
                                {{-- ===================================== --}}

                                @php
                                    $isArchivedRecord = !empty($record->borrowing_is_archived);
                                    $canReturn = in_array($record->borrowing_status, ['Borrowed', 'Overdue'], true) && ! $isArchivedRecord;
                                    $canArchive = ($record->borrowing_status === 'Returned') && ! $isArchivedRecord;
                                    $canUnarchive = $isArchivedRecord;
                                    $viewPayload = [
                                        'id' => $record->borrowing_record_id,
                                        'equipmentId' => $record->borrowing_equipment_id,
                                        'equipment' => $record->equipment_name,
                                        'assetTag' => $record->equipment_asset_tag,
                                        'tracking' => $record->equipment_tracking_mode,
                                        'borrower' => $record->borrowing_borrower_name,
                                        'department' => $record->borrowing_borrower_department,
                                        'quantity' => $record->borrowing_quantity,
                                        'borrowDate' => $record->borrowing_date,
                                        'expectedReturn' => $record->borrowing_expected_return_date,
                                        'actualReturn' => $record->borrowing_actual_return_date,
                                        'purpose' => $record->borrowing_purpose,
                                        'destination' => $record->borrowing_destination_location,
                                        'authorized' => $record->borrowing_authorized_by,
                                        'remarks' => $record->borrowing_remarks,
                                        'status' => $record->borrowing_status,
                                        'conditionOut' => $record->borrowing_equipment_condition,
                                        'fromRoom' => $record->borrowed_from_room_name,
                                        'returnedTo' => $record->returned_to_room_name,
                                        'returnCondition' => $record->borrowing_return_condition,
                                        'returnRemarks' => $record->borrowing_return_remarks,
                                        'returnedBy' => $record->returned_by_name,
                                    ];
                                @endphp

                                <td class="px-5 py-4">

                                    <div class="flex items-center justify-center gap-2">

                                        <button
                                            type="button"
                                            onclick="viewBorrowing(@js($viewPayload))"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                            data-tooltip="View"
                                            aria-label="View"
                                            title="View"
                                        >
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </button>

                                        @if ($canReturn)
                                            <div
                                                class="relative inline-block"
                                                x-data="borrowingActionMenu()"
                                            >
                                                <button
                                                    type="button"
                                                    x-ref="trigger"
                                                    @click="toggle()"
                                                    @click.outside="open = false"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg
                                                        border border-slate-200 bg-white text-slate-500 transition
                                                        hover:bg-slate-50 hover:text-slate-800"
                                                    data-tooltip="Actions"
                                                    aria-label="Borrowing actions"
                                                    aria-haspopup="menu"
                                                    :aria-expanded="open.toString()"
                                                >
                                                    <i data-lucide="ellipsis" class="h-4 w-4"></i>
                                                </button>

                                                <div
                                                    x-ref="menu"
                                                    x-cloak
                                                    x-show="open"
                                                    x-transition
                                                    class="fixed z-[200] w-48 overflow-hidden rounded-xl
                                                        border border-slate-200 bg-white p-1.5 text-left
                                                        shadow-lg shadow-slate-900/10"
                                                    role="menu"
                                                >
                                                    <button
                                                        type="button"
                                                        role="menuitem"
                                                        @click="open = false; openReturnModal({
                                                            id: {{ (int) $record->borrowing_record_id }},
                                                            equipment: @js($record->equipment_name),
                                                            assetTag: @js($record->equipment_asset_tag),
                                                            tracking: @js($record->equipment_tracking_mode),
                                                            quantity: {{ (int) ($record->borrowing_quantity ?? 1) }},
                                                            fromRoom: @js($record->borrowed_from_room_name),
                                                        })"
                                                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2
                                                            text-sm text-slate-700 transition hover:bg-slate-50"
                                                    >
                                                        <i data-lucide="archive-restore" class="h-4 w-4 text-slate-500"></i>
                                                        Return equipment
                                                    </button>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($canArchive)
                                            <form
                                                method="POST"
                                                action="{{ route('admin.borrowing.archive', $record->borrowing_record_id) }}"
                                                data-pur-confirm="This returned record will be hidden from the active list. You can restore it anytime from Show archived."
                                                data-pur-confirm-title="Archive borrowing record?"
                                                data-pur-confirm-ok="Archive"
                                                data-pur-confirm-kind="archive"
                                                class="inline"
                                            >
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#007a3f] transition hover:bg-slate-50"
                                                    data-tooltip="Archive"
                                                    aria-label="Archive"
                                                    title="Archive"
                                                >
                                                    <i data-lucide="archive" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if ($canUnarchive)
                                            <form
                                                method="POST"
                                                action="{{ route('admin.borrowing.unarchive', $record->borrowing_record_id) }}"
                                                data-pur-confirm="This record will return to the active borrowing list."
                                                data-pur-confirm-title="Restore from archive?"
                                                data-pur-confirm-ok="Unarchive"
                                                data-pur-confirm-kind="archive"
                                                class="inline"
                                            >
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50"
                                                    data-tooltip="Unarchive"
                                                    aria-label="Unarchive"
                                                    title="Unarchive"
                                                >
                                                    <i data-lucide="archive-restore" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @endif

                                    </div>

                                </td>

                            </tr>



                        @empty

                            <tr>

                                <td
                                    colspan="8"
                                    class="px-6 py-16 text-center"
                                >

                                    {{-- ===================================================== --}}
                                    {{-- EMPTY STATE --}}
                                    {{-- ===================================================== --}}

                                    <div class="mx-auto flex max-w-sm flex-col items-center">

                                        {{-- ================================================= --}}
                                        {{-- ICON --}}
                                        {{-- ================================================= --}}

                                        <div
                                            class="flex h-12 w-12 items-center justify-center
                                                rounded-2xl border border-slate-200
                                                bg-slate-50 text-slate-400"
                                        >
                                            <i
                                                data-lucide="clipboard-list"
                                                class="h-5 w-5"
                                            ></i>
                                        </div>


                                        {{-- ================================================= --}}
                                        {{-- TITLE --}}
                                        {{-- ================================================= --}}

                                        <h3 class="mt-4 text-sm font-semibold text-slate-800">

                                            {{ !empty($showArchived) ? 'No archived borrowing records' : 'No borrowing records yet' }}

                                        </h3>


                                        {{-- ================================================= --}}
                                        {{-- DESCRIPTION --}}
                                        {{-- ================================================= --}}

                                        <p
                                            class="mt-1.5 max-w-xs text-xs leading-5
                                                text-slate-400"
                                        >

                                            Borrowing activity will appear here after
                                            equipment has been issued to a borrower.

                                        </p>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            {{-- ===================================================== --}}
            {{-- PAGINATION --}}
            {{-- ADD AFTER THE TABLE CONTAINER --}}
            {{-- KEEP INSIDE THE BORROWING RECORDS SECTION --}}
            {{-- ===================================================== --}}

            @if ($borrowings->hasPages())

                <div
                    class="flex flex-col gap-3 border-t border-slate-200
                        px-5 py-4
                        sm:flex-row sm:items-center sm:justify-between"
                >

                    {{-- ================================================= --}}
                    {{-- PAGINATION INFORMATION --}}
                    {{-- ================================================= --}}

                    <p class="text-xs text-slate-500">

                        Showing

                        <span class="font-semibold text-slate-700">
                            {{ $borrowings->firstItem() }}
                        </span>

                        to

                        <span class="font-semibold text-slate-700">
                            {{ $borrowings->lastItem() }}
                        </span>

                        of

                        <span class="font-semibold text-slate-700">
                            {{ $borrowings->total() }}
                        </span>

                        borrowing records

                    </p>


                    {{-- ================================================= --}}
                    {{-- PAGINATION LINKS --}}
                    {{-- ================================================= --}}

                    <div>
                        {{ $borrowings->links() }}
                    </div>

                </div>

            @endif

        </section>
    </div>

    <!-- ===================================================== -->
    <!-- BORROW MODAL -->
    <!-- ===================================================== -->

    <div
        id="borrowModal"
        x-data="borrowEquipmentCart(@js($borrowableEquipmentJson ?? []))"
        x-cloak
        class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4"
        @keydown.escape.window="if (!$el.classList.contains('hidden')) closeBorrowModal()"
    >
        <div class="my-auto flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex items-start justify-between gap-4 px-6 pt-6">
                <div class="min-w-0">
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900">Borrow equipment</h2>
                </div>
                <button
                    type="button"
                    onclick="closeBorrowModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form
                method="POST"
                action="{{ route('admin.borrowing.store') }}"
                class="flex min-h-0 flex-1 flex-col"
                @submit="prepareSubmit($event)"
            >
                @csrf

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Equipment cart <span class="text-red-500">*</span></p>
                                <p class="mt-1 text-sm text-slate-500">Type to search, then add quantity for each equipment line.</p>
                            </div>
                            <p class="rounded-lg bg-white px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200/80" x-text="cart.length ? (cart.length + ' line' + (cart.length === 1 ? '' : 's') + ' · ' + totalQty + ' pcs') : 'No items yet'"></p>
                        </div>

                        <div class="relative" @click.outside="open = false">
                            <label class="mb-1.5 block text-sm text-slate-600">Find equipment</label>
                            <div class="flex gap-2">
                                <div class="relative min-w-0 flex-1">
                                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                                    <input
                                        type="text"
                                        x-model="query"
                                        @focus="open = true"
                                        @input="open = true"
                                        @keydown.arrow-down.prevent="move(1)"
                                        @keydown.arrow-up.prevent="move(-1)"
                                        @keydown.enter.prevent="selectHighlighted()"
                                        placeholder="Search by name, room, or asset tag"
                                        class="h-11 w-full rounded-xl border-0 bg-white pl-10 pr-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                        autocomplete="off"
                                    >
                                </div>
                                <input
                                    type="number"
                                    min="1"
                                    :max="selected?.available || 1"
                                    x-model.number="addQty"
                                    class="h-11 w-24 rounded-xl border-0 bg-white px-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:ring-2 focus:ring-slate-900/10"
                                    title="Quantity to add"
                                >
                                <button
                                    type="button"
                                    @click="addSelected()"
                                    class="inline-flex h-11 shrink-0 items-center rounded-xl bg-[#0025cc] px-4 text-sm font-medium text-white transition hover:bg-[#001fad]"
                                >
                                    Add
                                </button>
                            </div>

                            <div
                                x-show="open"
                                x-cloak
                                class="absolute left-0 right-0 top-[calc(100%+0.35rem)] z-40 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                            >
                                <div class="max-h-64 overflow-y-auto py-1">
                                    <template x-if="filtered.length === 0">
                                        <p class="px-3 py-4 text-sm text-slate-400">No matching borrowable equipment.</p>
                                    </template>
                                    <template x-for="(item, index) in filtered" :key="item.id">
                                        <button
                                            type="button"
                                            @click="choose(item)"
                                            class="flex w-full items-start gap-3 px-3 py-2.5 text-left transition"
                                            :class="index === highlight ? 'bg-[#0025cc]/5' : 'hover:bg-slate-50'"
                                        >
                                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                                <i data-lucide="package" class="h-4 w-4"></i>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-medium text-slate-900" x-text="item.name"></span>
                                                <span class="mt-0.5 block truncate text-xs text-slate-500" x-text="meta(item)"></span>
                                            </span>
                                            <span class="shrink-0 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700" x-text="item.available + ' avail'"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <p x-show="selected" x-cloak class="mt-2 text-xs text-slate-500">
                                Selected: <span class="font-medium text-slate-800" x-text="selected?.name"></span>
                                <span x-text="' · up to ' + (selected?.available || 0) + ' available'"></span>
                            </p>
                            <p x-show="pickerError" x-cloak class="mt-2 text-xs font-medium text-rose-600" x-text="pickerError"></p>
                        </div>

                        <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                            <template x-if="cart.length === 0">
                                <div class="px-4 py-8 text-center">
                                    <p class="text-sm font-medium text-slate-700">Cart is empty</p>
                                    <p class="mt-1 text-xs text-slate-400">Add chairs, tables, and other items here before creating the borrow.</p>
                                </div>
                            </template>
                            <template x-if="cart.length > 0">
                                <div class="divide-y divide-slate-100">
                                    <template x-for="(line, index) in cart" :key="line.id">
                                        <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                                            <input type="hidden" :name="'items[' + index + '][equipment_id]'" :value="line.id">
                                            <input type="hidden" :name="'items[' + index + '][condition]'" :value="line.condition || ''">
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-slate-900" x-text="line.name"></p>
                                                <p class="mt-0.5 truncate text-xs text-slate-500" x-text="meta(line)"></p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <label class="sr-only" :for="'cart-qty-' + line.id">Quantity</label>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    :max="line.available"
                                                    :id="'cart-qty-' + line.id"
                                                    :name="'items[' + index + '][quantity]'"
                                                    x-model.number="line.quantity"
                                                    @change="clampLine(line)"
                                                    class="h-9 w-20 rounded-lg border border-slate-200 px-2 text-sm"
                                                >
                                                <span class="text-xs text-slate-400" x-text="'/' + line.available"></span>
                                                <button
                                                    type="button"
                                                    @click="removeLine(index)"
                                                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-red-700 transition hover:bg-slate-50"
                                                    aria-label="Remove item"
                                                >
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <p x-show="cartError" x-cloak class="text-xs font-medium text-rose-600" x-text="cartError"></p>
                    </div>

                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Borrower</p>
                            <p class="mt-1 text-sm text-slate-500">Shared across every item in this borrow.</p>
                        </div>
                        <div
                            id="borrowerCautionBanner"
                            class="hidden rounded-xl border px-3.5 py-3 text-sm"
                            role="alert"
                        ></div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="borrowerName" class="mb-1.5 block text-sm text-slate-600">
                                    Borrower name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="borrowerName"
                                    type="text"
                                    name="borrowing_borrower_name"
                                    placeholder="Enter borrower name"
                                    required
                                    list="knownBorrowersList"
                                    autocomplete="off"
                                    oninput="updateBorrowerCaution(this.value)"
                                    onchange="updateBorrowerCaution(this.value)"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                />
                                <datalist id="knownBorrowersList">
                                    @foreach (($borrowers ?? collect()) as $borrowerName)
                                        <option value="{{ $borrowerName }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div>
                                <label for="borrowDepartment" class="mb-1.5 block text-sm text-slate-600">
                                    Department <span class="text-slate-400">(optional)</span>
                                </label>
                                <input
                                    id="borrowDepartment"
                                    type="text"
                                    name="borrowing_borrower_department"
                                    placeholder="Enter department"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="borrowAuthorizedBy" class="mb-1.5 block text-sm text-slate-600">
                                    Authorized by <span class="text-slate-400">(optional)</span>
                                </label>
                                <input
                                    id="borrowAuthorizedBy"
                                    type="text"
                                    name="borrowing_authorized_by"
                                    placeholder="Enter authorizing personnel"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Schedule</p>
                            <p class="mt-1 text-sm text-slate-500">One borrow / return window for the whole cart.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="borrowDate" class="mb-1.5 block text-sm text-slate-600">
                                    Borrow date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="borrowDate"
                                    type="date"
                                    name="borrowing_date"
                                    required
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                            <div>
                                <label for="borrowExpectedReturn" class="mb-1.5 block text-sm text-slate-600">
                                    Expected return <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="borrowExpectedReturn"
                                    type="date"
                                    name="borrowing_expected_return_date"
                                    required
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Notes</p>
                            <p class="mt-1 text-sm text-slate-500">Optional purpose, destination, and remarks.</p>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label for="borrowPurpose" class="mb-1.5 block text-sm text-slate-600">
                                    Purpose <span class="text-slate-400">(optional)</span>
                                </label>
                                <textarea
                                    id="borrowPurpose"
                                    name="borrowing_purpose"
                                    rows="2"
                                    placeholder="Describe why the equipment is being borrowed"
                                    class="w-full resize-none rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                ></textarea>
                            </div>
                            <div>
                                <label for="borrowDestination" class="mb-1.5 block text-sm text-slate-600">
                                    Destination <span class="text-slate-400">(optional)</span>
                                </label>
                                <input
                                    id="borrowDestination"
                                    type="text"
                                    name="borrowing_destination_location"
                                    placeholder="Enter destination location"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                            <div>
                                <label for="borrowRemarks" class="mb-1.5 block text-sm text-slate-600">
                                    Remarks <span class="text-slate-400">(optional)</span>
                                </label>
                                <textarea
                                    id="borrowRemarks"
                                    name="borrowing_remarks"
                                    rows="2"
                                    placeholder="Add any additional notes"
                                    class="w-full resize-none rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 items-center justify-end gap-2 px-6 pb-6">
                    <button
                        type="button"
                        onclick="closeBorrowModal()"
                        class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fa8]"
                        x-text="cart.length ? ('Create borrow · ' + cart.length + ' line' + (cart.length === 1 ? '' : 's')) : 'Create borrowing record'"
                    ></button>
                </div>
            </form>
        </div>
    </div>

    @include('admin.borrowing.partials.details-drawer')

    <!-- ===================================================== -->
    <!-- RETURN MODAL -->
    <!-- ===================================================== -->

    <div
    id="returnModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
>
    <!-- ===================================== -->
    <!-- RETURN EQUIPMENT MODAL -->
    <!-- ===================================== -->
    <div
        class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6 border-b border-dashed border-slate-500">
            <div>
                <p
                    class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                >
                    Borrowing Record
                </p>

                <h2
                    class="mt-1.5 text-lg font-semibold tracking-tight text-slate-950"
                >
                    Return equipment
                </h2>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeReturnModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- RETURN FORM -->
        <!-- ===================================== -->
        <form
            method="POST"
            action="{{ route('admin.borrowing.return') }}"
        >
            @csrf

            <input
                type="hidden"
                id="returnBorrowingId"
                name="borrowing_record_id"
            />

            <!-- ===================================== -->
            <!-- MODAL CONTENT -->
            <!-- ===================================== -->
            <div class="border-y border-slate-100 px-6 py-5">

                <!-- ===================================== -->
                <!-- EQUIPMENT INFORMATION -->
                <!-- ===================================== -->
                <div class="mb-6 rounded-xl border border-slate-200 px-4">
                    <div class="flex items-center justify-between gap-6 border-b border-slate-100 py-3.5">
                        <span class="shrink-0 text-sm text-slate-500">Equipment</span>
                        <span
                            id="returnEquipmentName"
                            class="min-w-0 truncate text-right text-sm font-medium text-slate-950"
                        ></span>
                    </div>

                    <div class="flex items-center justify-between gap-6 border-b border-slate-100 py-3.5">
                        <span class="shrink-0 text-sm text-slate-500">Asset tag</span>
                        <span
                            id="returnEquipmentAssetTag"
                            class="min-w-0 truncate text-right text-sm font-medium text-slate-950"
                        ></span>
                    </div>

                    <div class="flex items-center justify-between gap-6 border-b border-slate-100 py-3.5">
                        <span class="shrink-0 text-sm text-slate-500">Tracking</span>
                        <span
                            id="returnEquipmentTracking"
                            class="min-w-0 truncate text-right text-sm font-medium text-slate-950"
                        ></span>
                    </div>

                    <div class="flex items-center justify-between gap-6 border-b border-slate-100 py-3.5">
                        <span class="shrink-0 text-sm text-slate-500">Quantity</span>
                        <span
                            id="returnEquipmentQty"
                            class="min-w-0 truncate text-right text-sm font-medium text-slate-950"
                        ></span>
                    </div>

                    <div class="flex items-center justify-between gap-6 py-3.5">
                        <span class="shrink-0 text-sm text-slate-500">Borrowed from</span>
                        <span
                            id="returnEquipmentFrom"
                            class="min-w-0 truncate text-right text-sm font-medium text-slate-950"
                        ></span>
                    </div>
                </div>

                <!-- ===================================== -->
                <!-- RETURN FIELDS -->
                <!-- ===================================== -->
                <div class="space-y-5">

                    <!-- RETURN TO STORAGE -->
                    <div>
                        <label
                            for="returnRoom"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Return to storage room
                        </label>

                        @if (($returnStorageRooms ?? collect())->isNotEmpty())
                            <select
                                id="returnRoom"
                                name="return_room_id"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            >
                                @foreach ($returnStorageRooms as $storageRoom)
                                    <option value="{{ $storageRoom->room_id }}">{{ $storageRoom->room_name }}</option>
                                @endforeach
                            </select>
                            
                        @else
                            <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                No storage room is set up, so the equipment stays in the room it was taken from.
                            </p>
                        @endif
                    </div>

                    <!-- CONDITION UPON RETURN -->
                    <div>
                        <label
                            for="returnCondition"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Condition upon return <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="returnCondition"
                            name="return_condition"
                            required
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                            <option value="Good">
                                Good
                            </option>

                            <option value="Damaged">
                                Damaged
                            </option>

                            <option value="For Repair">
                                For Repair
                            </option>
                        </select>
                    </div>

                    <!-- RETURN REMARKS -->
                    <div>
                        <div
                            class="mb-2 flex items-center justify-between gap-4"
                        >
                            <label
                                for="returnRemarks"
                                class="text-sm font-medium text-slate-700"
                            >
                                Return remarks
                            </label>

                            <span class="text-xs text-slate-400">
                                Optional
                            </span>
                        </div>

                        <textarea
                            id="returnRemarks"
                            name="remarks"
                            rows="3"
                            placeholder="Add notes about the equipment condition or return"
                            class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        ></textarea>
                    </div>
                </div>
            </div>

            <div class="border-t border-dashed border-slate-500"></div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div
                class="flex items-center justify-end gap-2 px-6 py-4"
            >
                <button
                    type="button"
                    onclick="closeReturnModal()"
                    class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fad] focus:outline-none focus:ring-4 focus:ring-slate-200 active:bg-[#001a99]"
                >
                    Confirm return
                </button>
            </div>
        </form>
    </div>
</div>

    <script>
        function borrowEquipmentCart(catalog) {
            return {
                catalog: Array.isArray(catalog) ? catalog : [],
                query: '',
                open: false,
                highlight: 0,
                selected: null,
                addQty: 1,
                cart: [],
                pickerError: '',
                cartError: '',
                get filtered() {
                    const q = String(this.query || '').trim().toLowerCase();
                    const selectedIds = new Set(this.cart.map((line) => line.id));
                    return this.catalog
                        .filter((item) => item.available > 0)
                        .filter((item) => {
                            if (!q) return true;
                            return [item.name, item.room, item.assetTag]
                                .join(' ')
                                .toLowerCase()
                                .includes(q);
                        })
                        .slice(0, 40);
                },
                get totalQty() {
                    return this.cart.reduce((sum, line) => sum + (Number(line.quantity) || 0), 0);
                },
                meta(item) {
                    const bits = [];
                    if (item.room) bits.push(item.room);
                    if (item.assetTag) bits.push(item.assetTag);
                    bits.push((item.tracking || 'Individual') + ' · ' + item.available + ' available');
                    return bits.join(' · ');
                },
                reset() {
                    this.query = '';
                    this.open = false;
                    this.highlight = 0;
                    this.selected = null;
                    this.addQty = 1;
                    this.cart = [];
                    this.pickerError = '';
                    this.cartError = '';
                },
                choose(item) {
                    this.selected = item;
                    this.query = item.name;
                    this.open = false;
                    this.addQty = 1;
                    this.pickerError = '';
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                move(delta) {
                    if (!this.filtered.length) return;
                    this.highlight = (this.highlight + delta + this.filtered.length) % this.filtered.length;
                },
                selectHighlighted() {
                    if (!this.filtered.length) return;
                    this.choose(this.filtered[this.highlight] || this.filtered[0]);
                },
                addSelected() {
                    this.pickerError = '';
                    if (!this.selected) {
                        this.pickerError = 'Search and select an equipment item first.';
                        return;
                    }
                    const qty = Math.max(1, Number(this.addQty) || 1);
                    if (qty > this.selected.available) {
                        this.pickerError = 'Only ' + this.selected.available + ' available for this item.';
                        return;
                    }
                    const existing = this.cart.find((line) => line.id === this.selected.id);
                    if (existing) {
                        const next = existing.quantity + qty;
                        if (next > existing.available) {
                            this.pickerError = 'Cart would exceed available quantity (' + existing.available + ').';
                            return;
                        }
                        existing.quantity = next;
                    } else {
                        this.cart.push({
                            id: this.selected.id,
                            name: this.selected.name,
                            room: this.selected.room,
                            assetTag: this.selected.assetTag,
                            tracking: this.selected.tracking,
                            available: this.selected.available,
                            quantity: qty,
                            condition: '',
                        });
                    }
                    this.selected = null;
                    this.query = '';
                    this.addQty = 1;
                    this.cartError = '';
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                clampLine(line) {
                    let qty = Number(line.quantity) || 1;
                    if (qty < 1) qty = 1;
                    if (qty > line.available) qty = line.available;
                    line.quantity = qty;
                },
                removeLine(index) {
                    this.cart.splice(index, 1);
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                prepareSubmit(event) {
                    this.cartError = '';
                    if (!this.cart.length) {
                        event.preventDefault();
                        this.cartError = 'Add at least one equipment item to the cart.';
                        return;
                    }
                    for (const line of this.cart) {
                        this.clampLine(line);
                        if (line.quantity > line.available) {
                            event.preventDefault();
                            this.cartError = line.name + ' exceeds available quantity.';
                            return;
                        }
                    }

                    const borrowerInput = document.getElementById('borrowerName');
                    const caution = typeof findBorrowerCaution === 'function'
                        ? findBorrowerCaution(borrowerInput?.value || '')
                        : null;
                    const overdue = Number(caution?.overdue || 0);
                    if (overdue > 0) {
                        const ok = window.confirm(
                            'Preventive caution: this borrower has ' + overdue +
                            ' overdue item(s) still out. Continue lending anyway?'
                        );
                        if (!ok) {
                            event.preventDefault();
                        }
                    }
                },
            };
        }

        window.borrowEquipmentCart = borrowEquipmentCart;

        const borrowerCautions = @json($borrowerCautions ?? []);

        function findBorrowerCaution(name) {
            const needle = String(name || '').trim().toLowerCase();
            if (!needle) return null;
            if (borrowerCautions[name]) return borrowerCautions[name];
            const key = Object.keys(borrowerCautions).find((k) => k.trim().toLowerCase() === needle);
            return key ? borrowerCautions[key] : null;
        }

        function updateBorrowerCaution(name) {
            const banner = document.getElementById('borrowerCautionBanner');
            if (!banner) return;
            const caution = findBorrowerCaution(name);
            const overdue = Number(caution?.overdue || 0);
            const open = Number(caution?.open || 0);

            if (overdue > 0) {
                banner.className = 'rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-sm text-rose-800';
                banner.innerHTML =
                    '<div class="flex items-start gap-2">' +
                    '<i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0"></i>' +
                    '<div><p class="font-semibold">Preventive caution</p>' +
                    '<p class="mt-0.5 text-xs leading-5">This borrower has <strong>' + overdue + '</strong> overdue ' +
                    (overdue === 1 ? 'item' : 'items') +
                    ' and <strong>' + open + '</strong> still out. Clear returns before lending more equipment.</p></div></div>';
                banner.classList.remove('hidden');
            } else if (open > 0) {
                banner.className = 'rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-sm text-amber-900';
                banner.innerHTML =
                    '<div class="flex items-start gap-2">' +
                    '<i data-lucide="clock-3" class="mt-0.5 h-4 w-4 shrink-0"></i>' +
                    '<div><p class="font-semibold">Open borrows</p>' +
                    '<p class="mt-0.5 text-xs leading-5">This borrower already has <strong>' + open + '</strong> ' +
                    (open === 1 ? 'item' : 'items') +
                    ' still borrowed. Confirm they can take more before continuing.</p></div></div>';
                banner.classList.remove('hidden');
            } else {
                banner.className = 'hidden rounded-xl border px-3.5 py-3 text-sm';
                banner.innerHTML = '';
            }
            if (window.lucide) window.lucide.createIcons();
        }

        function openBorrowModal() {
            const modal = document.getElementById("borrowModal");
            if (!modal) return;
            if (modal._x_dataStack?.[0]?.reset) {
                modal._x_dataStack[0].reset();
            }
            const nameInput = document.getElementById('borrowerName');
            if (nameInput) updateBorrowerCaution(nameInput.value || '');
            modal.classList.remove("hidden");
            modal.classList.add("flex");
            document.body.style.overflow = 'hidden';
            if (window.lucide) window.lucide.createIcons();
        }

        function closeBorrowModal() {
            const modal = document.getElementById("borrowModal");
            if (!modal) return;
            modal.classList.add("hidden");
            modal.classList.remove("flex");
            document.body.style.overflow = '';
            if (modal._x_dataStack?.[0]?.reset) {
                modal._x_dataStack[0].reset();
            }
            updateBorrowerCaution('');
        }


        function openReturnModal(payload) {
            const data = (payload && typeof payload === 'object')
                ? payload
                : { id: payload, equipment: arguments[1] || '' };

            const tracking = String(data.tracking || '').trim() || 'Individual';
            const qty = Math.max(1, Number(data.quantity || 1));
            const assetTag = String(data.assetTag || '').trim();
            const fromRoom = String(data.fromRoom || '').trim();

            document.getElementById("returnBorrowingId").value = data.id || '';
            document.getElementById("returnEquipmentName").innerText = data.equipment || '—';
            document.getElementById("returnEquipmentAssetTag").innerText = assetTag || 'No asset tag';
            document.getElementById("returnEquipmentTracking").innerText = tracking;
            document.getElementById("returnEquipmentQty").innerText = String(qty);
            document.getElementById("returnEquipmentFrom").innerText = fromRoom || '—';

            document.getElementById("returnModal").classList.remove("hidden");
            document.getElementById("returnModal").classList.add("flex");
        }

        function closeReturnModal() {
            document.getElementById("returnModal").classList.add("hidden");
            document.getElementById("returnModal").classList.remove("flex");
        }

        function borrowingActionMenu() {
            return {
                open: false,
                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        this.$nextTick(() => {
                            this.place();
                            if (window.lucide) window.lucide.createIcons();
                        });
                    }
                },
                place() {
                    const trigger = this.$refs.trigger;
                    const menu = this.$refs.menu;
                    if (!trigger || !menu) return;

                    const rect = trigger.getBoundingClientRect();
                    const menuHeight = menu.offsetHeight || 160;
                    const menuWidth = menu.offsetWidth || 192;
                    const spaceBelow = window.innerHeight - rect.bottom - 8;
                    const spaceAbove = rect.top - 8;
                    const openUp = spaceBelow < menuHeight && spaceAbove > spaceBelow;

                    menu.style.left = Math.max(8, rect.right - menuWidth) + 'px';
                    menu.style.top = openUp
                        ? Math.max(8, rect.top - menuHeight - 4) + 'px'
                        : (rect.bottom + 4) + 'px';
                },
            };
        }

    </script>

@endsection
