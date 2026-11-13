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

            <a
                href="{{ route('admin.borrowing.borrowers') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i data-lucide="users" class="h-4 w-4"></i>
                Borrowers list
            </a>

            <a
                href="{{ route('admin.borrowing.event-types') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i data-lucide="tags" class="h-4 w-4"></i>
                Event Name - Types
            </a>

            <button
                type="button"
                onclick="openBorrowModal()"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-blue-800"
            >
                <i data-lucide="plus" class="h-4 w-4"></i>
                Borrow for event/use
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

                            placeholder="Search slip no., event, equipment, borrower, or authorized person..."

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
                            class="text-[11px] font-semibold uppercase
                                tracking-[0.08em] text-slate-400"
                        >

                            <th class="px-5 py-3">
                                Equipment
                            </th>

                            <th class="px-5 py-3">
                                Borrower
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

                        @php
                            $renderedBorrowSlips = [];
                        @endphp

                        @forelse ($borrowings as $record)

                            @php
                                $slipItems = collect($record->slip_items ?? [$record])->values();
                                $slipNo = trim((string) ($record->borrowing_transaction_no ?? ''));
                                $collapseSlip = $slipNo !== ''
                                    && $slipItems->count() > 1
                                    && empty($slipFilter)
                                    && ! request()->filled('record');
                                $isMultiSlip = $slipNo !== '' && $slipItems->count() > 1;

                                // One visual row per multi-item slip (siblings on this page are skipped).
                                if ($collapseSlip && isset($renderedBorrowSlips[$slipNo])) {
                                    continue;
                                }
                                if ($collapseSlip) {
                                    $renderedBorrowSlips[$slipNo] = true;
                                    // Prefer the earliest line on the slip as the primary display row.
                                    $record = $slipItems->first() ?? $record;
                                } elseif (! $collapseSlip && $isMultiSlip) {
                                    // Expanded slip/record view: tooltip still lists siblings, primary is this row.
                                    $slipItems = collect([$record])->concat(
                                        $slipItems->reject(fn ($item) => (int) $item->borrowing_record_id === (int) $record->borrowing_record_id)
                                    )->values();
                                }

                                $statuses = $slipItems->pluck('borrowing_status')->filter()->values();
                                if ($statuses->contains('Overdue')) {
                                    $borrowingStatus = 'Overdue';
                                } elseif ($statuses->contains('Borrowed')) {
                                    $borrowingStatus = 'Borrowed';
                                } elseif ($statuses->contains('Returned')) {
                                    $borrowingStatus = 'Returned';
                                } else {
                                    $borrowingStatus = $record->borrowing_status ?? 'Unknown';
                                }

                                $statusClass = match ($borrowingStatus) {
                                    "Borrowed" => "bg-sky-50 text-sky-700",
                                    "Returned" => "bg-emerald-50 text-emerald-700",
                                    "Overdue" => "bg-rose-50 text-rose-700",
                                    default => "bg-slate-100 text-slate-600",
                                };

                                $openSlipItem = $slipItems->first(fn ($item) => in_array($item->borrowing_status, ['Borrowed', 'Overdue'], true));
                                $allReturned = $slipItems->every(fn ($item) => ($item->borrowing_status ?? '') === 'Returned');
                                $anyReturnedDate = $slipItems
                                    ->map(fn ($item) => $item->borrowing_actual_return_date ?? null)
                                    ->filter()
                                    ->sort()
                                    ->last();
                            @endphp



                            <tr
                                class="group transition-colors
                                    hover:bg-slate-50/70"
                            >

                                {{-- ===================================== --}}
                                {{-- EQUIPMENT (slip under name) --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">
                                    @include('admin.borrowing.partials.slip-equipment-popover', [
                                        'items' => $slipItems,
                                    ])
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
                                        $borrowerDept = trim((string) ($record->borrowing_borrower_department ?? ''));
                                    @endphp

                                    <div class="flex items-center gap-2">

                                        <div
                                            class="flex h-8 w-8 shrink-0
                                                items-center justify-center
                                                rounded-full bg-slate-100
                                                text-xs font-semibold
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
                                                        text-[13px] font-medium
                                                        text-slate-700"
                                                >
                                                    {{ $borrowerName !== '' ? $borrowerName : '—' }}
                                                </span>
                                                @if ($borrowerOverdue > 0)
                                                    <span
                                                        class="inline-flex shrink-0 items-center gap-0.5 rounded-md bg-rose-50 px-1.5 py-0.5 text-[11px] font-semibold text-rose-700 ring-1 ring-rose-100"
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
                                                        class="inline-flex shrink-0 items-center gap-0.5 rounded-md bg-amber-50 px-1.5 py-0.5 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-100"
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
                                            @if ($borrowerDept !== '')
                                                <p class="mt-0.5 max-w-[180px] truncate text-xs text-slate-400">{{ $borrowerDept }}</p>
                                            @endif
                                        </div>

                                    </div>

                                </td>



                                {{-- ===================================== --}}
                                {{-- STATUS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[12px] font-semibold {{ $statusClass }}">
                                        {{ $borrowingStatus }}
                                    </span>
                                </td>



                                {{-- ===================================== --}}
                                {{-- BORROW DATE --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <span
                                        class="whitespace-nowrap
                                            text-[13px] text-slate-600"
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
                                            text-[13px] font-medium
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

                                    @if ($allReturned && $anyReturnedDate)

                                        <div class="flex items-center gap-1.5">

                                            <i
                                                data-lucide="check"
                                                class="h-3.5 w-3.5
                                                    text-emerald-500"
                                            ></i>

                                            <span
                                                class="whitespace-nowrap
                                                    text-[13px] text-slate-600"
                                            >
                                                {{
                                                    Carbon::parse(
                                                        $anyReturnedDate
                                                    )->format("M d, Y")
                                                }}
                                            </span>

                                        </div>

                                    @elseif ($isMultiSlip && ! $allReturned && $anyReturnedDate)

                                        <span class="whitespace-nowrap text-[13px] font-medium text-amber-700">
                                            Partially returned
                                        </span>

                                    @elseif ($borrowingStatus === 'Overdue' && $record->borrowing_expected_return_date)

                                        @php
                                            $daysLate = (int) Carbon::parse($record->borrowing_expected_return_date)->startOfDay()->diffInDays(today());
                                        @endphp
                                        <span class="whitespace-nowrap text-[13px] font-semibold text-rose-600">
                                            Not returned · {{ $daysLate }} day{{ $daysLate === 1 ? '' : 's' }} late
                                        </span>

                                    @else

                                        <span class="text-[13px] text-slate-400">
                                            Not returned yet
                                        </span>

                                    @endif

                                </td>



                                {{-- ===================================== --}}
                                {{-- ACTIONS --}}
                                {{-- ===================================== --}}

                                @php
                                    $actionRecord = $openSlipItem ?: $record;
                                    $isArchivedRecord = !empty($actionRecord->borrowing_is_archived);
                                    $canReturn = $openSlipItem && ! $isArchivedRecord;
                                    $canArchive = $allReturned && ! $isArchivedRecord;
                                    $canUnarchive = $isArchivedRecord;
                                    $viewPayload = [
                                        'id' => $actionRecord->borrowing_record_id,
                                        'slipNo' => $actionRecord->borrowing_transaction_no ?? null,
                                        'eventName' => $actionRecord->borrowing_event_name ?? null,
                                        'eventType' => $actionRecord->borrowing_event_type ?? null,
                                        'eventLocation' => $actionRecord->borrowing_event_location ?? null,
                                        'equipmentId' => $actionRecord->borrowing_equipment_id,
                                        'equipment' => $actionRecord->equipment_name,
                                        'assetTag' => $actionRecord->equipment_asset_tag,
                                        'tracking' => $actionRecord->equipment_tracking_mode,
                                        'borrower' => $actionRecord->borrowing_borrower_name,
                                        'department' => $actionRecord->borrowing_borrower_department,
                                        'quantity' => $actionRecord->borrowing_quantity,
                                        'borrowDate' => $actionRecord->borrowing_date,
                                        'expectedReturn' => $actionRecord->borrowing_expected_return_date,
                                        'actualReturn' => $actionRecord->borrowing_actual_return_date,
                                        'purpose' => $actionRecord->borrowing_purpose,
                                        'destination' => $actionRecord->borrowing_destination_location,
                                        'authorized' => $actionRecord->borrowing_authorized_by,
                                        'remarks' => $actionRecord->borrowing_remarks,
                                        'status' => $actionRecord->borrowing_status,
                                        'conditionOut' => $actionRecord->borrowing_equipment_condition,
                                        'fromRoom' => $actionRecord->borrowed_from_room_name,
                                        'returnedTo' => $actionRecord->returned_to_room_name,
                                        'returnCondition' => $actionRecord->borrowing_return_condition,
                                        'returnRemarks' => $actionRecord->borrowing_return_remarks,
                                        'returnedBy' => $actionRecord->returned_by_name,
                                        'slipItemCount' => $slipItems->count(),
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
                                            <button
                                                type="button"
                                                onclick="openReturnModal({
                                                    id: {{ (int) $actionRecord->borrowing_record_id }},
                                                    equipment: @js($actionRecord->equipment_name),
                                                    assetTag: @js($actionRecord->equipment_asset_tag),
                                                    tracking: @js($actionRecord->equipment_tracking_mode),
                                                    quantity: {{ (int) ($actionRecord->borrowing_quantity ?? 1) }},
                                                    fromRoom: @js($actionRecord->borrowed_from_room_name),
                                                })"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[#0025cc]/25 bg-[#0025cc]/5 text-[#0025cc] transition hover:bg-[#0025cc]/10 hover:text-[#001fa8]"
                                                data-tooltip="{{ $isMultiSlip ? 'Return next open item' : 'Return' }}"
                                                aria-label="Return equipment"
                                                title="{{ $isMultiSlip ? 'Return next open item' : 'Return' }}"
                                            >
                                                <i data-lucide="archive-restore" class="h-4 w-4"></i>
                                            </button>
                                        @endif

                                        @if ($canArchive)
                                            <form
                                                method="POST"
                                                action="{{ route('admin.borrowing.archive', $actionRecord->borrowing_record_id) }}"
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
                                                action="{{ route('admin.borrowing.unarchive', $actionRecord->borrowing_record_id) }}"
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
                                    colspan="7"
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
                novalidate
                @submit="prepareSubmit($event)"
            >
                @csrf

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Equipment cart <span class="text-red-500">*</span></p>
                                
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

                    <div
                        class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80"
                        x-data="borrowerPicker(@js(($registeredBorrowers ?? collect())->values()))"
                    >
                        <div>
                            <p class="text-[13px] font-semibold uppercase tracking-wide text-slate-400">Borrower</p>
                            
                        </div>
                        <div
                            id="borrowerCautionBanner"
                            class="hidden rounded-xl border px-3.5 py-3 text-sm"
                            role="alert"
                        ></div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="setMode('registered')"
                                :class="mode === 'registered' ? 'bg-[#0025cc] text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'"
                                class="inline-flex h-9 items-center rounded-lg px-3 text-xs font-semibold transition"
                            >Registered</button>
                            <button
                                type="button"
                                @click="setMode('guest')"
                                :class="mode === 'guest' ? 'bg-[#0025cc] text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'"
                                class="inline-flex h-9 items-center rounded-lg px-3 text-xs font-semibold transition"
                            >Guest / walk-in</button>
                        </div>
                        <input type="hidden" name="borrowing_borrower_custodian_id" :value="custodianId || ''">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2" x-show="mode === 'registered'" x-cloak>
                                <div class="mb-1.5 flex flex-wrap items-center justify-between gap-2">
                                    <label for="borrowerRegistered" class="block text-sm text-slate-600">
                                        Registered borrower <span class="text-red-500">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="toggleRegister()"
                                        class="text-xs font-semibold text-[#0025cc] hover:underline"
                                        x-text="showRegister ? 'Hide registration' : '+ Register external borrower'"
                                    ></button>
                                </div>
                                <select
                                    id="borrowerRegistered"
                                    x-model="custodianId"
                                    @change="onRegisteredChange()"
                                    :required="mode === 'registered' && !showRegister"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:ring-2 focus:ring-slate-900/10"
                                >
                                    <option value="">Select a registered person…</option>
                                    <template x-for="person in people" :key="person.id">
                                        <option :value="String(person.id)" x-text="person.label"></option>
                                    </template>
                                </select>
                                
                                <p x-show="people.length === 0" class="mt-1.5 text-xs text-amber-700">No registered external borrowers yet. Register one below, open Borrowers list, or use Guest / walk-in.</p>

                                <div
                                    x-show="showRegister"
                                    x-cloak
                                    class="mt-3 space-y-3 rounded-xl border border-slate-200 bg-white p-3.5"
                                >
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Register external borrower</p>
                                    <p class="text-xs text-slate-500">For outside-school people who borrow often — not one-time guests.</p>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="sm:col-span-2">
                                            <label for="externalBorrowerName" class="mb-1 block text-xs text-slate-600">Full name <span class="text-red-500">*</span></label>
                                            <input
                                                id="externalBorrowerName"
                                                type="text"
                                                x-model="reg.name"
                                                maxlength="50"
                                                required
                                                class="h-10 w-full rounded-lg border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-slate-900/10"
                                                placeholder="Borrower full name"
                                            >
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="externalBorrowerOrg" class="mb-1 block text-xs text-slate-600">Organization / company <span class="text-red-500">*</span></label>
                                            <input
                                                id="externalBorrowerOrg"
                                                type="text"
                                                x-model="reg.organization"
                                                maxlength="50"
                                                required
                                                class="h-10 w-full rounded-lg border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-slate-900/10"
                                                placeholder="Outside school / company name"
                                            >
                                        </div>
                                        <div>
                                            <label for="externalBorrowerPosition" class="mb-1 block text-xs text-slate-600">Role / title</label>
                                            <input
                                                id="externalBorrowerPosition"
                                                type="text"
                                                x-model="reg.position"
                                                maxlength="120"
                                                class="h-10 w-full rounded-lg border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-slate-900/10"
                                                placeholder="Optional"
                                            >
                                        </div>
                                        <div>
                                            <label for="external-borrower-contact" class="mb-1 block text-xs text-slate-600">Contact number</label>
                                            @include('partials.phone-input', [
                                                'name' => 'external_borrower_contact_number',
                                                'value' => '',
                                                'id' => 'external-borrower-contact',
                                                'inputClass' => 'h-10 w-full rounded-lg border-0 bg-slate-50 px-3 text-sm text-slate-800 outline-none ring-1 ring-slate-200 focus:bg-white focus:ring-2 focus:ring-slate-900/10',
                                            ])
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="externalBorrowerEmail" class="mb-1 block text-xs text-slate-600">Email</label>
                                            <input
                                                id="externalBorrowerEmail"
                                                type="email"
                                                x-model="reg.email"
                                                maxlength="150"
                                                inputmode="email"
                                                autocomplete="email"
                                                pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$"
                                                class="h-10 w-full rounded-lg border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200 focus:ring-2 focus:ring-slate-900/10"
                                                placeholder="name@example.com"
                                            >
                                        </div>
                                    </div>
                                    <p x-show="regError" x-cloak class="text-xs font-medium text-rose-600" x-text="regError"></p>
                                    <p x-show="regSuccess" x-cloak class="text-xs font-medium text-emerald-700" x-text="regSuccess"></p>
                                    <div class="flex justify-end">
                                        <button
                                            type="button"
                                            @click="registerExternal()"
                                            :disabled="regSaving"
                                            class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-3.5 text-xs font-semibold text-white transition hover:bg-[#001fa8] disabled:opacity-60"
                                        >
                                            <span x-show="!regSaving">Save & select</span>
                                            <span x-show="regSaving" x-cloak>Saving…</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div x-show="mode === 'guest'" x-cloak>
                                <label for="borrowerName" class="mb-1.5 block text-sm text-slate-600">
                                    Borrower name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="borrowerName"
                                    type="text"
                                    name="borrowing_borrower_name"
                                    x-model="guestName"
                                    @input="updateBorrowerCaution(guestName)"
                                    :required="mode === 'guest'"
                                    :disabled="mode !== 'guest'"
                                    placeholder="Enter guest borrower name"
                                    maxlength="150"
                                    autocomplete="off"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10 disabled:bg-slate-100"
                                />
                            </div>
                            <div x-show="mode === 'registered'" x-cloak>
                                <label class="mb-1.5 block text-sm text-slate-600">Borrower name</label>
                                <input
                                    type="text"
                                    name="borrowing_borrower_name"
                                    x-model="registeredName"
                                    readonly
                                    :disabled="mode !== 'registered'"
                                    class="h-11 w-full rounded-xl border-0 bg-slate-100 px-3.5 text-sm text-slate-700 outline-none ring-1 ring-slate-200/80"
                                />
                            </div>
                            <div>
                                <label for="borrowDepartment" class="mb-1.5 block text-sm text-slate-600">
                                    Department / organization <span class="text-slate-400">(optional)</span>
                                </label>
                                <input
                                    id="borrowDepartment"
                                    type="text"
                                    name="borrowing_borrower_department"
                                    x-model="department"
                                    :readonly="mode === 'registered' && !!custodianId"
                                    placeholder="Department or outside organization"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10 read-only:bg-slate-100"
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
                                    onchange="syncBorrowScheduleDates()"
                                    oninput="syncBorrowScheduleDates()"
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

                    <div class="space-y-4 rounded-2xl bg-indigo-50/50 p-4 ring-1 ring-indigo-100">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-500">Event / activity</p>
                            <p class="mt-1 text-sm text-slate-600">For big school or city events where STI College Ormoc equipment is borrowed outside campus.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="borrowEventName" class="mb-1.5 block text-sm text-slate-600">
                                    Event name <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="borrowEventName"
                                    name="borrowing_event_name"
                                    required
                                    data-searchable="1"
                                    data-search-placeholder="Search event names…"
                                    data-add-url="{{ route('admin.borrowing.event-names.quick-add') }}"
                                    data-add-label="Add new event name"
                                    data-add-placeholder="New event name"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:ring-2 focus:ring-slate-900/10"
                                >
                                    <option value="" disabled selected>Select event name</option>
                                    @foreach (($eventNames ?? collect()) as $eventName)
                                        <option value="{{ $eventName->event_name_label }}">{{ $eventName->event_name_label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="borrowEventType" class="mb-1.5 block text-sm text-slate-600">
                                    Event type <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="borrowEventType"
                                    name="borrowing_event_type"
                                    required
                                    data-searchable="1"
                                    data-search-placeholder="Search event types…"
                                    data-add-url="{{ route('admin.borrowing.event-types.quick-add') }}"
                                    data-add-label="Add new event type"
                                    data-add-placeholder="New event type name"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:ring-2 focus:ring-slate-900/10"
                                >
                                    <option value="" disabled selected>Select event type</option>
                                    @foreach (($eventTypes ?? collect()) as $eventType)
                                        <option value="{{ $eventType->event_type_name }}">{{ $eventType->event_type_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="borrowEventLocation" class="mb-1.5 block text-sm text-slate-600">
                                    Event venue / location <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="borrowEventLocation"
                                    type="text"
                                    name="borrowing_event_location"
                                    required
                                    maxlength="50"
                                    placeholder="e.g. Ormoc City Plaza, Partner campus"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
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

                <div class="flex shrink-0 flex-col gap-2 border-t border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p x-show="cartError" x-cloak class="text-xs font-medium text-rose-600 sm:max-w-sm" x-text="cartError"></p>
                    <div class="flex items-center justify-end gap-2 sm:ml-auto">
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
        function borrowerPicker(people) {
            const list = Array.isArray(people) ? people : [];
            return {
                people: list,
                mode: list.length ? 'registered' : 'guest',
                custodianId: '',
                registeredName: '',
                guestName: '',
                department: '',
                showRegister: false,
                regSaving: false,
                regError: '',
                regSuccess: '',
                reg: { name: '', organization: '', position: '', email: '' },
                setMode(next) {
                    this.mode = next;
                    this.showRegister = false;
                    this.regError = '';
                    this.regSuccess = '';
                    if (next === 'registered') {
                        this.guestName = '';
                        this.onRegisteredChange();
                    } else {
                        this.custodianId = '';
                        this.registeredName = '';
                        this.department = '';
                        if (typeof updateBorrowerCaution === 'function') {
                            updateBorrowerCaution('');
                        }
                    }
                },
                toggleRegister() {
                    this.showRegister = !this.showRegister;
                    this.regError = '';
                    this.regSuccess = '';
                    if (this.showRegister) {
                        this.$nextTick(() => this.initExternalPhone());
                    }
                },
                initExternalPhone() {
                    const input = document.getElementById('external-borrower-contact');
                    if (!input) return;
                    if (typeof window.initPrismPhoneInput === 'function') {
                        window.initPrismPhoneInput(input);
                    }
                },
                readExternalPhone() {
                    const input = document.getElementById('external-borrower-contact');
                    const storage = document.getElementById('external-borrower-contact-storage');
                    if (input && typeof window.writePrismPhoneStorage === 'function') {
                        window.writePrismPhoneStorage(input, input.value || '');
                    }
                    const raw = String(storage?.value || input?.value || '').trim();
                    return raw;
                },
                clearExternalPhone() {
                    const input = document.getElementById('external-borrower-contact');
                    const storage = document.getElementById('external-borrower-contact-storage');
                    if (storage) storage.value = '';
                    if (!input) return;
                    if (typeof window.writePrismPhoneStorage === 'function') {
                        window.writePrismPhoneStorage(input, '');
                    }
                    const iti = window.PRISM_PHONE_INPUTS && window.PRISM_PHONE_INPUTS.get(input);
                    if (iti && typeof iti.setNumber === 'function') {
                        try { iti.setNumber(''); } catch (e) { /* ignore */ }
                    }
                    input.value = '';
                },
                isValidEmail(value) {
                    if (!value) return true;
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                },
                onRegisteredChange() {
                    const id = Number(this.custodianId || 0);
                    const person = this.people.find((row) => Number(row.id) === id);
                    this.registeredName = person ? String(person.name || '') : '';
                    this.department = person
                        ? String(person.department || person.organization || '')
                        : '';
                    if (typeof updateBorrowerCaution === 'function') {
                        updateBorrowerCaution(this.registeredName);
                    }
                },
                async registerExternal() {
                    this.regError = '';
                    this.regSuccess = '';
                    const name = String(this.reg.name || '').trim();
                    const organization = String(this.reg.organization || '').trim();
                    const position = String(this.reg.position || '').trim();
                    const email = String(this.reg.email || '').trim();
                    const contact = this.readExternalPhone();

                    if (!name) {
                        this.regError = 'Enter the borrower full name.';
                        return;
                    }
                    if (name.length > 50) {
                        this.regError = 'Full name may not be longer than 50 characters.';
                        return;
                    }
                    if (!organization) {
                        this.regError = 'Enter the organization or company.';
                        return;
                    }
                    if (organization.length > 50) {
                        this.regError = 'Organization may not be longer than 50 characters.';
                        return;
                    }
                    if (position.length > 120) {
                        this.regError = 'Role / title may not be longer than 120 characters.';
                        return;
                    }
                    if (email && !this.isValidEmail(email)) {
                        this.regError = 'Enter a valid email address.';
                        return;
                    }
                    if (email.length > 150) {
                        this.regError = 'Email may not be longer than 150 characters.';
                        return;
                    }

                    this.regSaving = true;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            || document.querySelector('#borrowModal input[name="_token"]')?.value
                            || '';
                        const response = await fetch(@js(route('admin.borrowing.external-borrowers.store')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                full_name: name,
                                organization,
                                position: position || null,
                                contact_number: contact || null,
                                email: email || null,
                            }),
                        });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const firstError = data?.errors
                                ? Object.values(data.errors).flat()[0]
                                : null;
                            throw new Error(firstError || data.message || 'Could not register borrower.');
                        }
                        const borrower = data.borrower;
                        if (!borrower?.id) {
                            throw new Error('Registration succeeded but no borrower was returned.');
                        }
                        this.people = [borrower, ...this.people.filter((row) => Number(row.id) !== Number(borrower.id))];
                        this.custodianId = String(borrower.id);
                        this.onRegisteredChange();
                        this.showRegister = false;
                        this.reg = { name: '', organization: '', position: '', email: '' };
                        this.clearExternalPhone();
                        this.regSuccess = 'External borrower registered and selected.';
                    } catch (error) {
                        this.regError = error?.message || 'Could not register borrower.';
                    } finally {
                        this.regSaving = false;
                    }
                },
            };
        }
        window.borrowerPicker = borrowerPicker;

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
                focusInvalidField(el, message) {
                    this.cartError = message || 'Please complete the required fields.';
                    if (!el) return;
                    try {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } catch (e) { /* ignore */ }
                    this.$nextTick(() => {
                        try { el.focus({ preventScroll: false }); } catch (err) { el.focus?.(); }
                        if (typeof el.reportValidity === 'function') {
                            el.reportValidity();
                        }
                    });
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

                    const borrowerInput = document.querySelector('#borrowModal [name="borrowing_borrower_name"]:not([disabled])')
                        || document.getElementById('borrowerName');
                    const borrowerName = String(borrowerInput?.value || '').trim();
                    if (!borrowerName) {
                        event.preventDefault();
                        const target = this.mode === 'registered'
                            ? document.getElementById('borrowerRegistered')
                            : document.getElementById('borrowerName');
                        this.focusInvalidField(target, 'Select or enter a borrower name.');
                        return;
                    }

                    if (this.mode === 'registered' && !this.custodianId) {
                        event.preventDefault();
                        this.focusInvalidField(
                            document.getElementById('borrowerRegistered'),
                            'Select a registered borrower, or switch to Guest / walk-in.'
                        );
                        return;
                    }

                    const requiredFields = [
                        { id: 'borrowDate', message: 'Enter the borrow date.' },
                        { id: 'borrowExpectedReturn', message: 'Enter the expected return date.' },
                        { id: 'borrowEventName', message: 'Select an event name.' },
                        { id: 'borrowEventType', message: 'Select an event type.' },
                        { id: 'borrowEventLocation', message: 'Enter the event venue / location.' },
                    ];
                    for (const field of requiredFields) {
                        const el = document.getElementById(field.id);
                        const value = String(el?.value || '').trim();
                        if (!el || !value) {
                            event.preventDefault();
                            this.focusInvalidField(el, field.message);
                            return;
                        }
                    }

                    const borrowDate = document.getElementById('borrowDate')?.value || '';
                    const expectedReturn = document.getElementById('borrowExpectedReturn')?.value || '';
                    if (borrowDate && expectedReturn && expectedReturn < borrowDate) {
                        event.preventDefault();
                        this.focusInvalidField(
                            document.getElementById('borrowExpectedReturn'),
                            'Expected return cannot be earlier than the borrow date.'
                        );
                        return;
                    }

                    const form = event.target;
                    if (form?.dataset?.borrowCautionAccepted === '1') {
                        delete form.dataset.borrowCautionAccepted;
                        return;
                    }

                    const caution = typeof findBorrowerCaution === 'function'
                        ? findBorrowerCaution(borrowerName)
                        : null;
                    const overdue = Number(caution?.overdue || 0);
                    const open = Number(caution?.open || 0);
                    if (overdue > 0) {
                        event.preventDefault();
                        const ask = typeof window.purConfirm === 'function'
                            ? window.purConfirm({
                                title: 'Preventive caution',
                                text: 'This borrower has ' + overdue + ' overdue '
                                    + (overdue === 1 ? 'item' : 'items')
                                    + (open > 0 ? (' and ' + open + ' still out') : '')
                                    + '. Clear returns first, or continue lending anyway?',
                                confirmText: 'Continue lending',
                                cancelText: 'Go back',
                                kind: 'caution',
                            })
                            : Promise.resolve(window.confirm(
                                'Preventive caution: this borrower has ' + overdue +
                                ' overdue item(s) still out. Continue lending anyway?'
                            ));

                        ask.then((ok) => {
                            if (!ok) {
                                this.cartError = 'Borrow cancelled — clear overdue returns first, or continue lending.';
                                return;
                            }
                            form.dataset.borrowCautionAccepted = '1';
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                            } else {
                                form.submit();
                            }
                        });
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

        function syncBorrowScheduleDates() {
            const borrow = document.getElementById('borrowDate');
            const expected = document.getElementById('borrowExpectedReturn');
            if (!borrow || !expected) return;

            const borrowValue = String(borrow.value || '').trim();
            if (!borrowValue) {
                expected.removeAttribute('min');
                return;
            }

            // Lock expected-return calendar days before the borrow date (same day still allowed).
            expected.min = borrowValue;
            if (expected.value && expected.value < borrowValue) {
                expected.value = '';
            }
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
            syncBorrowScheduleDates();
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

        @if (request()->boolean('open_borrow'))
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof openBorrowModal === 'function') openBorrowModal();
            });
        @endif

    </script>

@endsection
