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
                @php
                    $borrowFeeRates = \App\Support\BorrowingFees::rates();
                    $canEditBorrowFees = \App\Support\RoleAccess::isAdmin();
                @endphp
                <div class="flex flex-wrap items-center gap-2">
                    @if ($canEditBorrowFees)
                        <button
                            type="button"
                            onclick="openBorrowFeesModal()"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            <i data-lucide="banknote" class="h-3.5 w-3.5 text-slate-500"></i>
                            Fees · ₱{{ number_format($borrowFeeRates['late_fee_per_day'], 2) }}/day
                        </button>
                    @endif
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

                            placeholder="Search slip no., event, equipment, or borrower..."

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
                                value="Pending"
                                @selected(request('status') === 'Pending')
                            >
                                Pending
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

                            <option
                                value="Denied"
                                @selected(request('status') === 'Denied')
                            >
                                Denied
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
                    {{-- SOURCE FILTER --}}
                    {{-- ================================================= --}}

                    @if (\Illuminate\Support\Facades\Schema::hasColumn('borrowing_records_table', 'borrowing_source'))
                    <div class="relative">
                        <select
                            name="source"
                            class="h-9 min-w-[150px]
                                appearance-none rounded-lg
                                border border-slate-200
                                bg-white pl-3 pr-9
                                text-sm text-slate-600
                                outline-none transition
                                focus:border-slate-400
                                focus:ring-2 focus:ring-slate-100"
                        >
                            <option value="">All sources</option>
                            <option value="online" @selected(request('source') === 'online')>Online requests</option>
                            <option value="admin" @selected(request('source') === 'admin')>Desk / admin</option>
                        </select>
                        <i
                            data-lucide="chevron-down"
                            class="pointer-events-none absolute
                                right-3 top-1/2 h-4 w-4
                                -translate-y-1/2 text-slate-400"
                        ></i>
                    </div>
                    @endif

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
                        || request()->filled('source')
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
                                if ($statuses->contains('Pending')) {
                                    $borrowingStatus = 'Pending';
                                } elseif ($statuses->contains('Overdue')) {
                                    $borrowingStatus = 'Overdue';
                                } elseif ($statuses->contains('Borrowed')) {
                                    $borrowingStatus = 'Borrowed';
                                } elseif ($statuses->contains('Denied')) {
                                    $borrowingStatus = 'Denied';
                                } elseif ($statuses->contains('Returned')) {
                                    $borrowingStatus = 'Returned';
                                } else {
                                    $borrowingStatus = $record->borrowing_status ?? 'Unknown';
                                }

                                $statusClass = match ($borrowingStatus) {
                                    "Pending" => "bg-amber-50 text-amber-800",
                                    "Borrowed" => "bg-sky-50 text-sky-700",
                                    "Returned" => "bg-emerald-50 text-emerald-700",
                                    "Overdue" => "bg-rose-50 text-rose-700",
                                    "Denied" => "bg-slate-200 text-slate-700",
                                    default => "bg-slate-100 text-slate-600",
                                };

                                $openSlipItem = $slipItems->first(fn ($item) => in_array($item->borrowing_status, ['Borrowed', 'Overdue'], true));
                                $allReturned = $slipItems->every(fn ($item) => ($item->borrowing_status ?? '') === 'Returned');
                                $allPending = $slipItems->isNotEmpty()
                                    && $slipItems->every(fn ($item) => ($item->borrowing_status ?? '') === 'Pending');
                                $canDecidePending = $allPending
                                    && $slipNo !== ''
                                    && ($record->borrowing_source ?? $slipItems->first()->borrowing_source ?? '') === 'online';
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
                                        $isOnlineBorrow = ($record->borrowing_source ?? 'admin') === 'online'
                                            || $slipItems->contains(fn ($item) => ($item->borrowing_source ?? '') === 'online');
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
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span
                                                    class="max-w-[160px] truncate
                                                        text-[13px] font-medium
                                                        text-slate-700"
                                                >
                                                    {{ $borrowerName !== '' ? $borrowerName : '—' }}
                                                </span>
                                                @if ($isOnlineBorrow)
                                                    <span
                                                        class="inline-flex shrink-0 items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-[11px] font-semibold text-indigo-700 ring-1 ring-indigo-100"
                                                        title="Submitted online from the landing page"
                                                        data-pur-tip-title="Online borrow"
                                                        data-pur-tip="Teacher submitted this borrow online. Confirm physical release with Property."
                                                        data-pur-tip-accent="#4338ca"
                                                    >
                                                        Online
                                                    </span>
                                                @endif
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
                                    $slipQuantity = (int) $slipItems->sum(fn ($item) => max(1, (int) ($item->borrowing_quantity ?? 1)));
                                    $slipNames = $slipItems
                                        ->map(fn ($item) => trim((string) ($item->equipment_name ?? '')))
                                        ->filter()
                                        ->unique()
                                        ->values();
                                    $slipEquipment = $slipNames->count() === 1
                                        ? $slipNames->first()
                                        : ($slipNames->count() > 1
                                            ? $slipNames->take(3)->implode(', ').($slipNames->count() > 3 ? ' +'.($slipNames->count() - 3) : '')
                                            : $actionRecord->equipment_name);
                                    $slipAssetTag = $slipItems->count() === 1
                                        ? ($actionRecord->equipment_asset_tag ?? null)
                                        : null;
                                    $openReturnItems = $slipItems
                                        ->filter(fn ($item) => in_array($item->borrowing_status ?? '', ['Borrowed', 'Overdue'], true))
                                        ->values();
                                    $slipUniqueness = ($openReturnItems->isNotEmpty() ? $openReturnItems : $slipItems)
                                        ->flatMap(function ($item) {
                                            $qty = max(1, (int) ($item->borrowing_quantity ?? 1));

                                            return \App\Support\EquipmentIdentity::borrowedUnitLabels($item, $qty);
                                        })
                                        ->values()
                                        ->all();
                                    $openReturnQuantity = (int) $openReturnItems->sum(fn ($item) => max(1, (int) ($item->borrowing_quantity ?? 1)));
                                    $viewPayload = [
                                        'id' => $actionRecord->borrowing_record_id,
                                        'slipNo' => $actionRecord->borrowing_transaction_no ?? null,
                                        'source' => $actionRecord->borrowing_source ?? 'admin',
                                        'eventName' => $actionRecord->borrowing_event_name ?? null,
                                        'eventType' => $actionRecord->borrowing_event_type ?? null,
                                        'eventLocation' => $actionRecord->borrowing_event_location ?? null,
                                        'equipmentId' => $actionRecord->borrowing_equipment_id,
                                        'equipment' => $slipEquipment,
                                        'assetTag' => $slipAssetTag,
                                        'uniqueness' => $slipUniqueness,
                                        'tracking' => $slipItems->count() > 1 ? 'Bulk' : $actionRecord->equipment_tracking_mode,
                                        'borrower' => $actionRecord->borrowing_borrower_name,
                                        'department' => $actionRecord->borrowing_borrower_department,
                                        'quantity' => $openReturnQuantity > 0
                                            ? $openReturnQuantity
                                            : ($slipQuantity > 0 ? $slipQuantity : $actionRecord->borrowing_quantity),
                                        'returnQuantity' => $openReturnQuantity > 0 ? $openReturnQuantity : ($slipQuantity > 0 ? $slipQuantity : $actionRecord->borrowing_quantity),
                                        'borrowDate' => $actionRecord->borrowing_date,
                                        'expectedReturn' => $actionRecord->borrowing_expected_return_date,
                                        'actualReturn' => $actionRecord->borrowing_actual_return_date,
                                        'purpose' => $actionRecord->borrowing_purpose,
                                        'destination' => $actionRecord->borrowing_destination_location,
                                        'remarks' => $actionRecord->borrowing_remarks,
                                        'status' => $borrowingStatus,
                                        'rejectionReason' => $actionRecord->borrowing_rejection_reason ?? null,
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

                                        @if ($canDecidePending)
                                            <form
                                                method="POST"
                                                action="{{ route('admin.borrowing.accept') }}"
                                                class="inline"
                                                data-pur-confirm="Accept this online borrow request? Stock will be marked borrowed and ready for release."
                                                data-pur-confirm-title="Accept online borrow?"
                                                data-pur-confirm-ok="Accept"
                                            >
                                                @csrf
                                                <input type="hidden" name="slip" value="{{ $slipNo }}">
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100"
                                                    data-tooltip="Accept"
                                                    aria-label="Accept online borrow"
                                                    title="Accept"
                                                >
                                                    <i data-lucide="check" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                            <form
                                                method="POST"
                                                action="{{ route('admin.borrowing.deny') }}"
                                                class="inline"
                                                onsubmit="return submitDenyOnlineBorrow(event, @js($slipNo));"
                                            >
                                                @csrf
                                                <input type="hidden" name="slip" value="{{ $slipNo }}">
                                                <input type="hidden" name="reason" value="">
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100"
                                                    data-tooltip="Deny"
                                                    aria-label="Deny online borrow"
                                                    title="Deny"
                                                >
                                                    <i data-lucide="x" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if ($canReturn)
                                            <button
                                                type="button"
                                                onclick="openReturnModal({
                                                    id: {{ (int) $actionRecord->borrowing_record_id }},
                                                    equipment: @js($slipEquipment),
                                                    assetTag: @js($slipAssetTag),
                                                    uniqueness: @js($slipUniqueness),
                                                    tracking: @js($slipItems->count() > 1 ? 'Bulk' : $actionRecord->equipment_tracking_mode),
                                                    quantity: {{ $openReturnQuantity > 0 ? $openReturnQuantity : ($slipQuantity > 0 ? $slipQuantity : (int) ($actionRecord->borrowing_quantity ?? 1)) }},
                                                    fromRoom: @js($actionRecord->borrowed_from_room_name),
                                                    expectedReturn: @js($actionRecord->borrowing_expected_return_date),
                                                    status: @js($actionRecord->borrowing_status),
                                                })"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[#0025cc]/25 bg-[#0025cc]/5 text-[#0025cc] transition hover:bg-[#0025cc]/10 hover:text-[#001fa8]"
                                                data-tooltip="Return"
                                                aria-label="Return equipment"
                                                title="Return"
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
        role="dialog"
        aria-modal="true"
        aria-labelledby="borrowModalTitle"
        class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4"
        @keydown.escape.window="if (!$el.classList.contains('hidden')) { if (open) { open = false; } else { closeBorrowModal(); } }"
    >
        <div class="my-auto flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex items-start justify-between gap-4 px-6 pt-6">
                <div class="min-w-0">
                    <h2 id="borrowModalTitle" class="text-xl font-semibold tracking-tight text-slate-900">Borrow equipment</h2>
                </div>
                <button
                    type="button"
                    onclick="closeBorrowModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" aria-hidden="true" class="h-4 w-4"></i>
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
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Items to borrow <span class="text-red-500" aria-hidden="true">*</span></p>
                            </div>
                            <p aria-live="polite" class="rounded-lg bg-white px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200/80" x-text="badgeText"></p>
                        </div>

                        <div class="relative" @click.outside="open = false">
                            <label for="adminBorrowSearch" class="mb-1 block text-sm font-medium text-slate-700">Add equipment</label>
                            <p id="adminBorrowSearchHint" class="mb-2 text-xs text-slate-500">Only available storage stock is shown. Identical units are grouped — select an item to add it.</p>
                            <div class="relative">
                                <i data-lucide="search" aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                                <input
                                    id="adminBorrowSearch"
                                    type="text"
                                    role="combobox"
                                    aria-autocomplete="list"
                                    :aria-expanded="open ? 'true' : 'false'"
                                    aria-controls="adminBorrowSearchList"
                                    aria-describedby="adminBorrowSearchHint"
                                    :aria-activedescendant="(open && filtered.length) ? ('adminBorrowOpt-' + highlight) : null"
                                    x-model="query"
                                    @focus="openList()"
                                    @input="openList()"
                                    @keydown.arrow-down.prevent="move(1)"
                                    @keydown.arrow-up.prevent="move(-1)"
                                    @keydown.enter.prevent="selectHighlighted()"
                                    @keydown.escape.stop="open = false"
                                    placeholder="Search by name, room, or asset tag"
                                    class="h-11 w-full rounded-xl border-0 bg-white pl-10 pr-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/90 placeholder:text-slate-400 transition focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                                    autocomplete="off"
                                >
                            </div>

                            <div
                                x-show="open"
                                x-cloak
                                class="absolute left-0 right-0 top-[calc(100%+0.35rem)] z-40 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                            >
                                <div id="adminBorrowSearchList" role="listbox" aria-label="Available equipment" class="max-h-64 overflow-y-auto py-1">
                                    <template x-if="filtered.length === 0">
                                        <p class="px-3.5 py-4 text-sm text-slate-500">No matching equipment available.</p>
                                    </template>
                                    <template x-for="(item, index) in filtered" :key="item.id">
                                        <button
                                            type="button"
                                            :id="'adminBorrowOpt-' + index"
                                            role="option"
                                            :aria-selected="index === highlight ? 'true' : 'false'"
                                            :aria-disabled="isMaxed(item) ? 'true' : 'false'"
                                            @click="addItem(item)"
                                            class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition"
                                            :class="[index === highlight ? 'bg-[#0025cc]/5' : 'hover:bg-slate-50', isMaxed(item) ? 'opacity-60' : '']"
                                        >
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                                <i data-lucide="package" aria-hidden="true" class="h-4 w-4"></i>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-medium text-slate-900" x-text="item.name"></span>
                                                <span class="mt-0.5 block truncate text-xs text-slate-500" x-text="meta(item)"></span>
                                            </span>
                                            <template x-if="isMaxed(item)">
                                                <span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Max added</span>
                                            </template>
                                            <template x-if="!isMaxed(item) && inCartQty(item.id) > 0">
                                                <span class="shrink-0 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700" x-text="'In list (' + inCartQty(item.id) + '/' + item.available + ') · +1'"></span>
                                            </template>
                                            <template x-if="inCartQty(item.id) === 0">
                                                <span class="shrink-0 rounded-md bg-[#0025cc]/10 px-2 py-0.5 text-[11px] font-semibold text-[#0025cc]">+ Add</span>
                                            </template>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <p class="sr-only" aria-live="polite" aria-atomic="true" x-text="liveStatus"></p>
                            <p x-show="pickerError" x-cloak role="alert" class="mt-2 text-xs font-medium text-rose-600" x-text="pickerError"></p>
                        </div>

                        <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                            <template x-if="cart.length === 0">
                                <div class="px-4 py-7 text-center">
                                    <p class="text-sm font-medium text-slate-700">No items selected yet</p>
                                    <p class="mt-1 text-xs text-slate-500">Search above and click an item to add it to this borrow.</p>
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
                                                <template x-if="line.available <= 1">
                                                    <span class="inline-flex h-9 items-center rounded-lg bg-slate-100 px-3 text-xs font-medium text-slate-600">
                                                        <input type="hidden" :name="'items[' + index + '][quantity]'" :value="line.quantity">
                                                        Qty 1
                                                    </span>
                                                </template>
                                                <template x-if="line.available > 1">
                                                    <div class="flex items-center gap-2">
                                                        <div class="inline-flex items-center rounded-lg border border-slate-200 bg-white" role="group" :aria-label="'Quantity for ' + line.name">
                                                            <button
                                                                type="button"
                                                                @click="stepLine(line, -1)"
                                                                :disabled="Number(line.quantity) <= 1"
                                                                :aria-label="'Decrease quantity for ' + line.name"
                                                                class="flex h-9 w-8 items-center justify-center rounded-l-lg text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0025cc]/40 disabled:cursor-not-allowed disabled:opacity-40"
                                                            >
                                                                <i data-lucide="minus" aria-hidden="true" class="h-3.5 w-3.5"></i>
                                                            </button>
                                                            <input
                                                                type="text"
                                                                inputmode="numeric"
                                                                autocomplete="off"
                                                                :id="'cart-qty-' + line.id"
                                                                :name="'items[' + index + '][quantity]'"
                                                                :aria-label="'Quantity for ' + line.name + ' (max ' + line.available + ')'"
                                                                x-model="line.quantity"
                                                                @input="clampLine(line, false)"
                                                                @blur="clampLine(line, true)"
                                                                class="h-9 w-11 border-x border-slate-200 bg-white text-center text-sm font-medium text-slate-900 outline-none focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                                                            >
                                                            <button
                                                                type="button"
                                                                @click="stepLine(line, 1)"
                                                                :disabled="Number(line.quantity) >= line.available"
                                                                :aria-label="'Increase quantity for ' + line.name"
                                                                class="flex h-9 w-8 items-center justify-center rounded-r-lg text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0025cc]/40 disabled:cursor-not-allowed disabled:opacity-40"
                                                            >
                                                                <i data-lucide="plus" aria-hidden="true" class="h-3.5 w-3.5"></i>
                                                            </button>
                                                        </div>
                                                        <span class="text-xs font-medium text-slate-500" x-text="'of ' + line.available"></span>
                                                    </div>
                                                </template>
                                                <button
                                                    type="button"
                                                    @click="removeLine(index)"
                                                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-rose-700 transition hover:border-rose-200 hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                                                    :aria-label="'Remove ' + line.name"
                                                >
                                                    <i data-lucide="trash-2" aria-hidden="true" class="h-4 w-4"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <p x-show="cartError" x-cloak role="alert" class="text-xs font-medium text-rose-600" x-text="cartError"></p>
                    </div>

                    <div
                        class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80"
                        x-data="borrowerPicker(@js(($registeredBorrowers ?? collect())->values()))"
                    >
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Borrower</p>
                            <p class="mt-1 text-sm text-slate-600">Faculty and staff with campus accounts only.</p>
                        </div>
                        <div
                            id="borrowerCautionBanner"
                            class="hidden rounded-xl border px-3.5 py-3 text-sm"
                            role="alert"
                        ></div>
                        <input type="hidden" name="borrowing_borrower_custodian_id" :value="custodianId || ''">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="borrowerRegistered" class="mb-1.5 block text-sm font-medium text-slate-700">
                                    Faculty / staff <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <select
                                    id="borrowerRegistered"
                                    x-model="custodianId"
                                    @change="onRegisteredChange()"
                                    required
                                    data-searchable="1"
                                    data-search-placeholder="Search faculty or staff…"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/90 transition focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                                >
                                    <option value="">Select faculty or staff…</option>
                                    <template x-for="person in people" :key="person.id">
                                        <option :value="String(person.id)" x-text="person.label"></option>
                                    </template>
                                </select>
                                <p x-show="people.length === 0" class="mt-1.5 text-xs text-amber-700">No active faculty/staff accounts found. Add them under property custodians / faculty &amp; staff first.</p>
                            </div>
                            <div>
                                <label for="borrowerNameReadonly" class="mb-1.5 block text-sm font-medium text-slate-700">Borrower name</label>
                                <input
                                    id="borrowerNameReadonly"
                                    type="text"
                                    name="borrowing_borrower_name"
                                    x-model="registeredName"
                                    readonly
                                    required
                                    class="h-11 w-full rounded-xl border-0 bg-slate-100 px-3.5 text-sm text-slate-700 outline-none ring-1 ring-slate-200/80"
                                />
                            </div>
                            <div>
                                <label for="borrowDepartment" class="mb-1.5 block text-sm font-medium text-slate-700">
                                    Department <span class="text-slate-500 font-normal">(optional)</span>
                                </label>
                                <input
                                    id="borrowDepartment"
                                    type="text"
                                    name="borrowing_borrower_department"
                                    x-model="department"
                                    readonly
                                    placeholder="Filled from the selected account"
                                    class="h-11 w-full rounded-xl border-0 bg-slate-100 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Schedule</p>
                            <p class="mt-1 text-sm text-slate-600">One borrow and return window for all selected items.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="borrowDate" class="mb-1.5 block text-sm font-medium text-slate-700">
                                    Borrow from <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <input
                                    id="borrowDate"
                                    type="date"
                                    name="borrowing_date"
                                    required
                                    onchange="syncBorrowScheduleDates()"
                                    oninput="syncBorrowScheduleDates()"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/90 transition focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                                />
                            </div>
                            <div>
                                <label for="borrowExpectedReturn" class="mb-1.5 block text-sm font-medium text-slate-700">
                                    Expected return by <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <input
                                    id="borrowExpectedReturn"
                                    type="date"
                                    name="borrowing_expected_return_date"
                                    required
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/90 transition focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Notes</p>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label for="borrowPurpose" class="mb-1.5 block text-sm font-medium text-slate-700">
                                    Purpose <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <textarea
                                    id="borrowPurpose"
                                    name="borrowing_purpose"
                                    rows="2"
                                    required
                                    maxlength="2000"
                                    placeholder="Describe why the equipment is being borrowed"
                                    class="w-full resize-none rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none ring-1 ring-slate-200/90 placeholder:text-slate-400 transition focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 flex-col gap-2 border-t border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p x-show="cartError" x-cloak role="alert" class="text-xs font-medium text-rose-600 sm:max-w-sm" x-text="cartError"></p>
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
                            x-text="'Confirm borrow'"
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
        class="flex max-h-[calc(100vh-2rem)] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div class="flex shrink-0 items-center justify-between gap-4 border-b border-dashed border-slate-300 px-5 py-3.5">
            <div>
                <h2 class="text-base font-semibold tracking-tight text-slate-950">
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
            class="flex min-h-0 flex-1 flex-col"
            onsubmit="return prepareReturnSubmit()"
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
            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">

                <div class="mb-3 grid grid-cols-2 gap-x-4 gap-y-2 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                    <div class="min-w-0">
                        <p class="text-[11px] text-slate-400">Equipment</p>
                        <p id="returnEquipmentName" class="truncate font-medium text-slate-950"></p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] text-slate-400">Still out</p>
                        <p id="returnEquipmentQty" class="truncate font-medium text-slate-950"></p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] text-slate-400">Tracking</p>
                        <p id="returnEquipmentTracking" class="truncate font-medium text-slate-950"></p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] text-slate-400">Borrowed from</p>
                        <p id="returnEquipmentFrom" class="truncate font-medium text-slate-950"></p>
                    </div>
                    <div class="col-span-2 min-w-0">
                        <p class="text-[11px] text-slate-400">Uniqueness</p>
                        <details id="returnUniquenessDetails" class="group mt-1">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-900 [&::-webkit-details-marker]:hidden">
                                <span id="returnUniquenessSummary" class="truncate">—</span>
                                <i data-lucide="chevron-down" class="h-3.5 w-3.5 shrink-0 text-slate-400 transition group-open:rotate-180"></i>
                            </summary>
                            <ul id="returnEquipmentUniqueness" class="mt-1 max-h-28 space-y-1 overflow-y-auto overscroll-contain rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs leading-4 text-slate-700"></ul>
                        </details>
                    </div>
                </div>

                <div id="returnOverdueNotice" class="mb-3 hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
                    This item is overdue.
                </div>

                <div class="space-y-3">

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="returnQuantity" class="mb-1.5 block text-sm font-medium text-slate-700">
                                Quantity returned
                            </label>
                            <input
                                type="number"
                                id="returnQuantity"
                                name="return_quantity"
                                min="1"
                                step="1"
                                inputmode="numeric"
                                required
                                oninput="syncReturnQuantities()"
                                onblur="syncReturnQuantities(true)"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            >
                            <p id="returnQuantityHint" class="mt-1 text-xs text-slate-400"></p>
                        </div>
                        <div>
                            <label for="returnDamagedQuantity" class="mb-1.5 block text-sm font-medium text-slate-700">
                                Damaged
                            </label>
                            <input
                                type="number"
                                id="returnDamagedQuantity"
                                name="damaged_quantity"
                                min="0"
                                step="1"
                                inputmode="numeric"
                                value="0"
                                oninput="syncReturnQuantities()"
                                onblur="syncReturnQuantities(true)"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            >
                            <p id="returnDamageFeeLabel" class="mt-1 text-xs text-slate-400"></p>
                        </div>
                    </div>

                    <div>
                        <label
                            for="returnRoom"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
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

                    <div class="rounded-xl border border-slate-200 px-3.5 py-2.5">
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-[11px] text-slate-400">Days overdue</p>
                                <p id="returnLateDays" class="font-medium text-slate-950">0</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-slate-400">Late fee</p>
                                <p id="returnLateFee" class="font-medium text-slate-950">₱0.00</p>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-4 border-t border-slate-100 pt-2 text-sm">
                            <span class="text-slate-500">Damage fee</span>
                            <span id="returnDamageFee" class="font-medium text-slate-950">₱0.00</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-4 border-t border-slate-100 pt-2 text-sm">
                            <span class="font-medium text-slate-700">Amount due</span>
                            <span id="returnFeeTotal" class="text-base font-semibold text-slate-950">₱0.00</span>
                        </div>
                    </div>

                    <!-- RETURN REMARKS -->
                    <div>
                        <div
                            class="mb-1.5 flex items-center justify-between gap-4"
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
                            rows="2"
                            placeholder="Add notes about the equipment condition or return"
                            class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm leading-5 text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        ></textarea>
                    </div>
                </div>
            </div>

            <div class="shrink-0 border-t border-dashed border-slate-500"></div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div
                class="flex shrink-0 items-center justify-end gap-2 px-5 py-3"
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

@if ($canEditBorrowFees ?? false)
    <div id="borrowFeesModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-6 py-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Borrowing</p>
                    <h2 class="mt-0.5 text-lg font-semibold text-slate-950">Borrow fees</h2>
                </div>
                <button type="button" onclick="closeBorrowFeesModal()" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.settings.borrow-fees.update') }}">
                @csrf
                <div class="space-y-4 px-6 py-5">
                    <label class="block text-sm font-medium text-slate-700">
                        Late fee per day
                        <span class="mt-2 flex overflow-hidden rounded-lg border border-slate-200 focus-within:border-slate-400 focus-within:ring-4 focus-within:ring-slate-100">
                            <span class="flex items-center bg-slate-50 px-3 text-sm font-semibold text-slate-600">₱</span>
                            <input
                                type="text"
                                inputmode="decimal"
                                name="late_fee_per_day"
                                required
                                data-peso-fee
                                autocomplete="off"
                                value="{{ ($borrowFeeRates['late_fee_per_day'] ?? 0) > 0 ? number_format($borrowFeeRates['late_fee_per_day'], 2) : '' }}"
                                class="w-full border-0 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none"
                            >
                        </span>
                        <span class="mt-1 block text-xs font-normal text-slate-400">Charged for each day past the expected return date.</span>
                    </label>
                    <label class="block text-sm font-medium text-slate-700">
                        Damage fee
                        <span class="mt-2 flex overflow-hidden rounded-lg border border-slate-200 focus-within:border-slate-400 focus-within:ring-4 focus-within:ring-slate-100">
                            <span class="flex items-center bg-slate-50 px-3 text-sm font-semibold text-slate-600">₱</span>
                            <input
                                type="text"
                                inputmode="decimal"
                                name="damage_fee"
                                required
                                data-peso-fee
                                autocomplete="off"
                                value="{{ ($borrowFeeRates['damage_fee'] ?? 0) > 0 ? number_format($borrowFeeRates['damage_fee'], 2) : '' }}"
                                class="w-full border-0 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none"
                            >
                        </span>
                        <span class="mt-1 block text-xs font-normal text-slate-400">Added when damage is found during inspection.</span>
                    </label>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button type="button" onclick="closeBorrowFeesModal()" class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fad]">Save fees</button>
                </div>
            </form>
        </div>
    </div>
@endif

    <script>
        function borrowerPicker(people) {
            const list = Array.isArray(people) ? people : [];
            return {
                people: list,
                mode: 'registered',
                custodianId: '',
                registeredName: '',
                department: '',
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
            };
        }
        window.borrowerPicker = borrowerPicker;

        function borrowEquipmentCart(catalog) {
            return {
                catalog: Array.isArray(catalog) ? catalog : [],
                query: '',
                open: false,
                highlight: 0,
                cart: [],
                pickerError: '',
                cartError: '',
                liveStatus: '',
                get filtered() {
                    const q = String(this.query || '').trim().toLowerCase();
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
                get badgeText() {
                    if (!this.cart.length) return 'No items selected';
                    const itemLabel = this.cart.length + ' item' + (this.cart.length === 1 ? '' : 's');
                    return this.totalQty > this.cart.length
                        ? (itemLabel + ' · ' + this.totalQty + ' total')
                        : itemLabel;
                },
                inCartQty(id) {
                    const line = this.cart.find((row) => row.id === id);
                    return line ? (Number(line.quantity) || 0) : 0;
                },
                isMaxed(item) {
                    return this.inCartQty(item.id) >= Number(item.available || 0);
                },
                meta(item) {
                    const bits = [];
                    if (item.room) bits.push(item.room);
                    if (item.assetTag) bits.push(item.assetTag);
                    bits.push((item.tracking || 'Individual') + ' · ' + item.available + ' available');
                    const pending = Number(item.pending || 0);
                    if (pending > 0) bits.push(pending + ' pending');
                    return bits.join(' · ');
                },
                announce(message) {
                    this.liveStatus = '';
                    this.$nextTick(() => {
                        this.liveStatus = message || '';
                    });
                },
                openList() {
                    this.pickerError = '';
                    this.highlight = 0;
                    this.open = true;
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                reset() {
                    this.query = '';
                    this.open = false;
                    this.highlight = 0;
                    this.cart = [];
                    this.pickerError = '';
                    this.cartError = '';
                    this.liveStatus = '';
                },
                move(delta) {
                    if (!this.open) {
                        this.openList();
                        return;
                    }
                    if (!this.filtered.length) return;
                    this.highlight = (this.highlight + delta + this.filtered.length) % this.filtered.length;
                    this.$nextTick(() => {
                        const el = document.getElementById('adminBorrowOpt-' + this.highlight);
                        if (el) el.scrollIntoView({ block: 'nearest' });
                    });
                },
                selectHighlighted() {
                    if (!this.open || !this.filtered.length) return;
                    this.addItem(this.filtered[this.highlight] || this.filtered[0]);
                },
                addItem(item) {
                    this.pickerError = '';
                    if (!item || item.available < 1) {
                        this.pickerError = 'This item is not available right now.';
                        return;
                    }
                    const existing = this.cart.find((line) => line.id === item.id);
                    if (existing) {
                        const current = Number(existing.quantity) || 1;
                        if (current >= existing.available) {
                            this.pickerError = 'All available units of ' + existing.name + ' (' + existing.available + ') are already added.';
                            this.open = false;
                            return;
                        }
                        existing.quantity = current + 1;
                        this.announce('Increased ' + existing.name + ' to ' + existing.quantity + ' of ' + existing.available + '.');
                    } else {
                        this.cart.push({
                            id: item.id,
                            name: item.name,
                            room: item.room,
                            assetTag: item.assetTag,
                            tracking: item.tracking,
                            available: item.available,
                            pending: Number(item.pending || 0),
                            quantity: 1,
                            condition: '',
                        });
                        this.announce('Added ' + item.name + '.');
                    }
                    this.query = '';
                    this.highlight = 0;
                    this.open = false;
                    this.cartError = '';
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                stepLine(line, delta) {
                    const max = Math.max(1, Number(line.available) || 1);
                    const current = Math.max(1, parseInt(String(line.quantity ?? 1), 10) || 1);
                    const next = Math.min(max, Math.max(1, current + delta));
                    if (next === current) return;
                    line.quantity = next;
                    this.announce(line.name + ' quantity set to ' + next + '.');
                },
                clampLine(line, finalize = true) {
                    const digits = String(line.quantity ?? '').replace(/\D/g, '');
                    const max = Math.max(1, Number(line.available) || 1);
                    if (digits === '') {
                        line.quantity = finalize ? 1 : '';
                        return;
                    }
                    let qty = parseInt(digits, 10);
                    if (qty > max) qty = max;
                    if (finalize && qty < 1) qty = 1;
                    line.quantity = qty < 1 ? '' : qty;
                },
                removeLine(index) {
                    const removed = this.cart[index];
                    this.cart.splice(index, 1);
                    if (removed) {
                        this.announce('Removed ' + removed.name + '.');
                    }
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
                        this.focusInvalidField(
                            document.getElementById('adminBorrowSearch'),
                            'Add at least one equipment item.'
                        );
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

                    const borrowerSelect = document.getElementById('borrowerRegistered');
                    const borrowerInput = document.querySelector('#borrowModal [name="borrowing_borrower_name"]');
                    const borrowerName = String(borrowerInput?.value || '').trim();
                    const custodianId = String(borrowerSelect?.value || '').trim();
                    if (!custodianId || !borrowerName) {
                        event.preventDefault();
                        this.focusInvalidField(
                            borrowerSelect,
                            'Select a faculty or staff account.'
                        );
                        return;
                    }

                    const requiredFields = [
                        { id: 'borrowDate', message: 'Enter the borrow date.' },
                        { id: 'borrowExpectedReturn', message: 'Enter the expected return date.' },
                        { id: 'borrowPurpose', message: 'Enter the purpose of this borrow.' },
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
                    const borrowAt = borrowDate ? new Date(borrowDate + 'T00:00:00') : null;
                    const returnAt = expectedReturn ? new Date(expectedReturn + 'T00:00:00') : null;
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    if (!borrowAt || Number.isNaN(borrowAt.getTime()) || !returnAt || Number.isNaN(returnAt.getTime())) {
                        event.preventDefault();
                        this.focusInvalidField(
                            document.getElementById('borrowDate'),
                            'Enter a valid borrow / return date.'
                        );
                        return;
                    }
                    if (borrowAt < today) {
                        event.preventDefault();
                        this.focusInvalidField(
                            document.getElementById('borrowDate'),
                            'Borrow date cannot be in the past.'
                        );
                        return;
                    }
                    if (returnAt < borrowAt) {
                        event.preventDefault();
                        this.focusInvalidField(
                            document.getElementById('borrowExpectedReturn'),
                            'Expected return must be on or after the borrow date.'
                        );
                        return;
                    }
                    const maxReturn = new Date(borrowAt.getTime() + (30 * 24 * 60 * 60 * 1000));
                    if (returnAt > maxReturn) {
                        event.preventDefault();
                        this.focusInvalidField(
                            document.getElementById('borrowExpectedReturn'),
                            'Expected return cannot be more than 30 days after the borrow date.'
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

        function toLocalDateValue(date) {
            const pad = (n) => String(n).padStart(2, '0');
            return date.getFullYear()
                + '-' + pad(date.getMonth() + 1)
                + '-' + pad(date.getDate());
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

            expected.min = borrowValue;
            if (expected.value && expected.value < borrowValue) {
                expected.value = borrowValue;
            }
        }

        function seedBorrowScheduleDefaults() {
            const borrow = document.getElementById('borrowDate');
            const expected = document.getElementById('borrowExpectedReturn');
            if (!borrow || !expected) return;

            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const later = new Date(today.getTime() + (1 * 24 * 60 * 60 * 1000));

            if (!borrow.value) borrow.value = toLocalDateValue(today);
            if (!expected.value) expected.value = toLocalDateValue(later);
            borrow.min = toLocalDateValue(today);
            syncBorrowScheduleDates();
        }

        function openBorrowModal() {
            const modal = document.getElementById("borrowModal");
            if (!modal) return;
            if (modal._x_dataStack?.[0]?.reset) {
                modal._x_dataStack[0].reset();
            }
            const nameInput = document.querySelector('#borrowModal [name="borrowing_borrower_name"]');
            if (typeof updateBorrowerCaution === 'function') {
                updateBorrowerCaution(nameInput?.value || '');
            }
            modal.classList.remove("hidden");
            modal.classList.add("flex");
            document.body.style.overflow = 'hidden';
            seedBorrowScheduleDefaults();
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

        window.submitDenyOnlineBorrow = async function (event, slipNo) {
            event.preventDefault();
            const form = event.target;
            let reason = '';
            if (window.Swal) {
                const result = await Swal.fire({
                    title: 'Deny online borrow?',
                    input: 'textarea',
                    inputLabel: 'Reason (optional)',
                    inputPlaceholder: 'Why is this request denied?',
                    showCancelButton: true,
                    confirmButtonText: 'Deny request',
                    confirmButtonColor: '#be123c',
                    cancelButtonText: 'Cancel',
                    inputAttributes: { maxlength: 500 },
                });
                if (!result.isConfirmed) return false;
                reason = String(result.value || '').trim();
            } else {
                if (!window.confirm('Deny online borrow slip ' + slipNo + '?')) return false;
                reason = String(window.prompt('Denial reason (optional)', '') || '').trim();
            }
            const reasonInput = form.querySelector('input[name="reason"]');
            if (reasonInput) reasonInput.value = reason;
            form.submit();
            return false;
        };


        const borrowFeeRates = @json(\App\Support\BorrowingFees::rates());

        function peso(amount) {
            const value = Number(amount) || 0;
            return '₱' + value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function returnDaysLate(expectedReturn) {
            if (!expectedReturn) return 0;
            const raw = String(expectedReturn).trim().replace(' ', 'T');
            const due = new Date(raw);
            if (Number.isNaN(due.getTime())) return 0;
            due.setHours(0, 0, 0, 0);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (today <= due) return 0;
            return Math.round((today - due) / 86400000);
        }

        let returnUniquenessLines = [];

        function returnOutstanding() {
            return Math.max(1, Number(document.getElementById('returnModal')?.dataset.outstanding || 1));
        }

        function readReturnCount(input, fallback, max) {
            if (!input || String(input.value).trim() === '') return fallback;
            const value = Math.floor(Number(input.value));
            if (!Number.isFinite(value)) return fallback;
            return Math.min(max, Math.max(0, value));
        }

        function syncReturnQuantities(finalize) {
            const max = returnOutstanding();
            const qtyInput = document.getElementById('returnQuantity');
            const damagedInput = document.getElementById('returnDamagedQuantity');
            let qty = readReturnCount(qtyInput, finalize ? max : 0, max);
            if (qty < 1) qty = finalize ? max : 0;
            if (qtyInput && (finalize || String(qtyInput.value).trim() !== '')) {
                if (finalize || Number(qtyInput.value) > max || Number(qtyInput.value) < 1) {
                    qtyInput.value = String(Math.max(1, qty));
                }
                qty = Math.max(1, Math.min(max, Math.floor(Number(qtyInput.value) || 1)));
            }
            const damagedMax = Math.max(0, qty);
            let damaged = readReturnCount(damagedInput, 0, damagedMax);
            if (damagedInput && (finalize || String(damagedInput.value).trim() !== '')) {
                if (finalize || Number(damagedInput.value) > damagedMax || Number(damagedInput.value) < 0) {
                    damagedInput.value = String(damaged);
                }
                damaged = Math.min(damagedMax, Math.max(0, Math.floor(Number(damagedInput.value) || 0)));
            }
            if (qtyInput) qtyInput.max = String(max);
            if (damagedInput) damagedInput.max = String(damagedMax);
            renderReturnUniqueness();
            refreshReturnFees();
        }

        function renderReturnUniqueness() {
            const lines = returnUniquenessLines.slice();
            const uniquenessList = document.getElementById('returnEquipmentUniqueness');
            const uniquenessSummary = document.getElementById('returnUniquenessSummary');
            const uniquenessDetails = document.getElementById('returnUniquenessDetails');
            if (!uniquenessList || !uniquenessSummary) return;
            const damaged = Math.min(lines.length, readReturnCount(document.getElementById('returnDamagedQuantity'), 0, lines.length));
            uniquenessList.replaceChildren();
            lines.forEach((line, index) => {
                const item = document.createElement('li');
                item.className = 'break-words';
                item.textContent = index < damaged ? ('Damaged · ' + line) : line;
                uniquenessList.appendChild(item);
            });
            uniquenessSummary.textContent = lines.length === 1 ? '1 item' : (lines.length + ' items');
            uniquenessList.classList.remove('hidden');
            if (uniquenessDetails) {
                uniquenessDetails.onclick = null;
                const chevron = uniquenessDetails.querySelector('[data-lucide="chevron-down"], svg');
                if (chevron) chevron.classList.remove('hidden');
            }
        }

        function refreshReturnFees() {
            const days = Number(document.getElementById('returnModal')?.dataset.lateDays || 0);
            const perDay = Number(borrowFeeRates.late_fee_per_day || 0);
            const damageRate = Number(borrowFeeRates.damage_fee || 0);
            const max = returnOutstanding();
            const qty = Math.max(1, Math.min(max, readReturnCount(document.getElementById('returnQuantity'), max, max) || max));
            const damaged = Math.min(qty, readReturnCount(document.getElementById('returnDamagedQuantity'), 0, qty));
            const lateFee = days * perDay * qty;
            const damageFee = damaged * damageRate;
            const daysEl = document.getElementById('returnLateDays');
            const lateEl = document.getElementById('returnLateFee');
            const damageLabel = document.getElementById('returnDamageFeeLabel');
            const damageEl = document.getElementById('returnDamageFee');
            const totalEl = document.getElementById('returnFeeTotal');
            const hint = document.getElementById('returnQuantityHint');
            if (daysEl) daysEl.textContent = String(days);
            if (lateEl) {
                lateEl.textContent = peso(lateFee) + (days > 0 && perDay > 0
                    ? ' (' + qty + ' × ' + days + ' × ' + peso(perDay) + ')'
                    : '');
            }
            if (damageLabel) damageLabel.textContent = peso(damageRate) + ' each';
            if (damageEl) {
                damageEl.textContent = peso(damageFee) + (damaged > 0 && damageRate > 0
                    ? ' (' + damaged + ' × ' + peso(damageRate) + ')'
                    : '');
            }
            if (hint) hint.textContent = 'Up to ' + max + ' still out';
            if (totalEl) totalEl.textContent = peso(lateFee + damageFee);
        }

        function prepareReturnSubmit() {
            syncReturnQuantities(true);
            const max = returnOutstanding();
            const qty = Math.floor(Number(document.getElementById('returnQuantity')?.value || 0));
            const damaged = Math.floor(Number(document.getElementById('returnDamagedQuantity')?.value || 0));
            if (qty < 1 || qty > max) {
                window.alert('Enter a return quantity from 1 to ' + max + '.');
                return false;
            }
            if (damaged < 0 || damaged > qty) {
                window.alert('Damaged quantity cannot be more than the quantity returned.');
                return false;
            }
            return true;
        }

        function formatPesoFee(input, finalize) {
            const raw = String(input.value || '').replace(/,/g, '');
            if (raw.trim() === '') {
                input.value = '';
                return;
            }
            const cleaned = raw.replace(/[^\d.]/g, '');
            const dot = cleaned.indexOf('.');
            let whole = dot === -1 ? cleaned : cleaned.slice(0, dot);
            let cents = dot === -1 ? null : cleaned.slice(dot + 1).replace(/\./g, '');
            whole = whole.replace(/^0+(?=\d)/, '').slice(0, 5);
            if (whole === '') whole = '0';
            const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            if (finalize) {
                input.value = grouped + '.' + (cents || '').slice(0, 2).padEnd(2, '0');
                return;
            }
            if (cents === null) {
                input.value = grouped;
                return;
            }
            input.value = grouped + '.' + cents.slice(0, 2);
        }

        function bindPesoFeeInputs(root) {
            const scope = root || document;
            scope.querySelectorAll('[data-peso-fee]').forEach(function (input) {
                if (input.dataset.pesoBound === '1') return;
                input.dataset.pesoBound = '1';
                input.addEventListener('input', function () {
                    const end = input.selectionEnd;
                    const before = input.value.length;
                    formatPesoFee(input, false);
                    const delta = input.value.length - before;
                    const next = Math.max(0, (end || 0) + delta);
                    input.setSelectionRange(next, next);
                });
                input.addEventListener('blur', function () {
                    formatPesoFee(input, true);
                });
                formatPesoFee(input, true);
            });
            const form = scope.querySelector('#borrowFeesModal form');
            if (form && form.dataset.pesoBound !== '1') {
                form.dataset.pesoBound = '1';
                form.addEventListener('submit', function () {
                    form.querySelectorAll('[data-peso-fee]').forEach(function (input) {
                        formatPesoFee(input, true);
                        input.value = input.value.replace(/,/g, '');
                        if (input.value === '') input.value = '0';
                    });
                });
            }
        }

        function openBorrowFeesModal() {
            const modal = document.getElementById('borrowFeesModal');
            if (!modal) return;
            bindPesoFeeInputs(modal);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (window.lucide) window.lucide.createIcons();
        }

        function closeBorrowFeesModal() {
            const modal = document.getElementById('borrowFeesModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openReturnModal(payload) {
            const data = (payload && typeof payload === 'object')
                ? payload
                : { id: payload, equipment: arguments[1] || '' };

            const tracking = String(data.tracking || '').trim() || 'Individual';
            const qty = Math.max(1, Number(data.quantity || 1));
            const assetTag = String(data.assetTag || '').trim();
            const uniqueness = Array.isArray(data.uniqueness)
                ? data.uniqueness.map((line) => String(line || '').trim()).filter(Boolean)
                : [];
            const fromRoom = String(data.fromRoom || '').trim();
            const days = returnDaysLate(data.expectedReturn);
            const overdue = days > 0 || String(data.status || '') === 'Overdue';

            document.getElementById("returnBorrowingId").value = data.id || '';
            document.getElementById("returnEquipmentName").innerText = data.equipment || '—';
            returnUniquenessLines = uniqueness.length
                ? uniqueness
                : [assetTag ? ('Tag ' + assetTag) : 'No asset tag / serial'];
            document.getElementById("returnEquipmentTracking").innerText = tracking;
            document.getElementById("returnEquipmentQty").innerText = String(qty);
            document.getElementById("returnEquipmentFrom").innerText = fromRoom || '—';

            const notice = document.getElementById('returnOverdueNotice');
            const modal = document.getElementById('returnModal');
            if (modal) {
                modal.dataset.lateDays = String(days);
                modal.dataset.outstanding = String(qty);
            }
            if (notice) {
                notice.classList.toggle('hidden', !overdue);
                notice.textContent = overdue
                    ? (days > 0
                        ? 'This borrow is overdue by ' + days + ' ' + (days === 1 ? 'day' : 'days') + '.'
                        : 'This borrow is overdue.')
                    : '';
            }
            const qtyInput = document.getElementById('returnQuantity');
            const damagedInput = document.getElementById('returnDamagedQuantity');
            if (qtyInput) {
                qtyInput.max = String(qty);
                qtyInput.value = String(qty);
            }
            if (damagedInput) {
                damagedInput.max = String(qty);
                damagedInput.value = '0';
            }
            const remarks = document.getElementById('returnRemarks');
            if (remarks) remarks.value = '';
            const uniquenessDetails = document.getElementById('returnUniquenessDetails');
            if (uniquenessDetails) uniquenessDetails.open = false;
            syncReturnQuantities(true);

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
