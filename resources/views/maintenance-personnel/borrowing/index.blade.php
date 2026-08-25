@extends ("layouts.maintenance-layout")

@php
    use Carbon\Carbon;
@endphp

@section ("content")

    <div
        class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end"
    >
        @if (($overdueBorrowings ?? 0) > 0)
            @if (request('status') === 'Overdue')
                <a
                    href="{{ url('/maintenance/borrowing') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <i data-lucide="list" class="h-4 w-4"></i>
                    Show all records
                </a>
            @else
                <a
                    href="{{ url('/maintenance/borrowing?status=Overdue') }}"
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
    </div>

    <!--@if (($overdueBorrowings ?? 0) > 0 && request('status') !== 'Overdue')
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ $overdueBorrowings }} overdue {{ \Illuminate\Support\Str::plural('record', $overdueBorrowings) }} need attention.
        </div>
    @endif-->

    <div
        class="mb-6 mt-6 overflow-hidden rounded-lg border-t border-b border-slate-300 bg-gray-100 shadow-sm"
    >
        <div
            class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-y-0 xl:grid-cols-[380px_1fr_1fr_1fr]"
        >

            {{-- ===================================================== --}}
            {{-- TOTAL BORROWING RECORDS --}}
            {{-- ===================================================== --}}

            <div class="flex items-center justify-between px-8 py-6">

                <div class="flex flex-col">

                    <p class="text-sm font-medium text-slate-500">
                        Total Borrowing Records
                    </p>

                    <h2 class="mt-2 text-5xl font-medium text-slate-900">
                        {{ number_format($totalBorrowingRecords) }}
                    </h2>


                    {{-- ================================================= --}}
                    {{-- MONTHLY PERCENTAGE CHANGE --}}
                    {{-- ================================================= --}}

                    <p class="mt-3 text-sm">

                        @if ($borrowingMonthlyPercentage === null)

                            <span class="font-semibold text-emerald-500">
                                New activity
                            </span>

                        @else

                            <span
                                class="font-semibold
                                    {{
                                        $borrowingMonthlyPercentage > 0
                                            ? 'text-emerald-500'
                                            : (
                                                $borrowingMonthlyPercentage < 0
                                                    ? 'text-red-500'
                                                    : 'text-slate-500'
                                            )
                                    }}"
                            >
                                {{
                                    $borrowingMonthlyPercentage > 0
                                        ? '+'
                                        : ''
                                }}{{ number_format($borrowingMonthlyPercentage, 2) }}%
                            </span>

                        @endif

                        <span class="text-slate-500">
                            From last month
                        </span>

                    </p>

                </div>


                {{-- ===================================================== --}}
                {{-- REAL 12 MONTH GRAPH --}}
                {{-- ===================================================== --}}

                <div class="ml-6 h-20 w-40 shrink-0">

                    <svg
                        viewBox="0 0 300 100"
                        class="h-full w-full"
                        fill="none"
                    >

                        @php

                            $borrowingCounts =
                                $borrowingMonthlyTrend->pluck('count');

                            $maxBorrowingCount =
                                max(
                                    1,
                                    $borrowingCounts->max()
                                );

                            $borrowingPointCount =
                                max(
                                    1,
                                    $borrowingMonthlyTrend->count() - 1
                                );

                            $borrowingPoints =
                                $borrowingMonthlyTrend
                                    ->values()
                                    ->map(function ($item, $index) use (
                                        $maxBorrowingCount,
                                        $borrowingPointCount
                                    ) {

                                        $x =
                                            ($index / $borrowingPointCount)
                                            * 300;

                                        $y =
                                            90
                                            - (
                                                ($item['count'] / $maxBorrowingCount)
                                                * 75
                                            );

                                        return
                                            round($x, 2)
                                            . ','
                                            . round($y, 2);

                                    })
                                    ->implode(' ');

                        @endphp


                        <polyline
                            points="{{ $borrowingPoints }}"
                            fill="none"
                            stroke="#3b82f6"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                    </svg>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- ON LOAN --}}
            {{-- ===================================================== --}}

            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    On Loan
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($onLoanBorrowings) }}
                </h2>

                <p class="text-base">

                    <span class="font-semibold text-slate-900">
                        {{ number_format($onLoanPercentage, 2) }}%
                    </span>

                    <span class="text-slate-500">
                        of all borrowing records
                    </span>

                </p>

            </div>


            {{-- ===================================================== --}}
            {{-- RETURNED --}}
            {{-- ===================================================== --}}

            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    Returned
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($returnedBorrowings) }}
                </h2>

                <p class="text-base">

                    <span class="font-semibold text-slate-900">
                        {{ number_format($returnedPercentage, 2) }}%
                    </span>

                    <span class="text-slate-500">
                        of all borrowing records
                    </span>

                </p>

            </div>


            {{-- ===================================================== --}}
            {{-- OVERDUE --}}
            {{-- ===================================================== --}}

            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    Overdue
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($overdueBorrowings) }}
                </h2>

                <p class="text-base">

                    <span
                        class="font-semibold
                            {{
                                $overduePercentage > 0
                                    ? 'text-red-500'
                                    : 'text-slate-500'
                            }}"
                    >
                        {{ number_format($overduePercentage, 2) }}%
                    </span>

                    <span class="text-slate-500">
                        of all borrowing records
                    </span>

                </p>

            </div>

        </div>
    </div>

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
                            Borrowing Records
                        </h2>

                        <p class="mt-0.5 text-xs text-slate-400">
                            Track borrowed equipment and return activity
                        </p>

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

            <form
                method="GET"
                action="{{ url()->current() }}"
                class="border-b border-slate-200 px-5 py-4"
            >

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
                    {{-- STATUS FILTER --}}
                    {{-- ================================================= --}}

                    <div class="relative">

                        <select
                            name="status"

                            class="h-9 min-w-[170px]
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
                                @selected(request('status') === 'Overdue')
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
                            data-lucide="sliders-horizontal"
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
                    )

                        <a
                            href="{{ url()->current() }}"

                            class="inline-flex h-10 items-center
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
                                                class="mt-0.5 text-[11px]
                                                    text-slate-400"
                                            >
                                                Equipment record
                                            </p>

                                        </div>

                                    </div>

                                </td>



                                {{-- ===================================== --}}
                                {{-- BORROWER --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

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
                                                        $record->borrowing_borrower_name,
                                                        0,
                                                        1
                                                    )
                                                )
                                            }}
                                        </div>


                                        <span
                                            class="max-w-[180px] truncate
                                                text-xs font-medium
                                                text-slate-700"
                                        >
                                            {{ $record->borrowing_borrower_name }}
                                        </span>

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

                                    @else

                                        <span class="text-xs text-slate-400">
                                            Not returned
                                        </span>

                                    @endif

                                </td>



                                {{-- ===================================== --}}
                                {{-- ACTIONS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div
                                        class="flex items-center justify-center
                                            gap-2"
                                    >

                                        {{-- ================================= --}}
                                        {{-- VIEW BUTTON --}}
                                        {{-- ================================= --}}

                                        <button
                                            type="button"

                                            onclick="viewBorrowing(
                                                '{{ $record->borrowing_record_id }}',
                                                @js($record->equipment_name),
                                                @js($record->borrowing_borrower_name),
                                                @js($record->borrowing_borrower_department),
                                                '{{ $record->borrowing_quantity }}',
                                                '{{ $record->borrowing_date }}',
                                                '{{ $record->borrowing_expected_return_date }}',
                                                '{{ $record->borrowing_actual_return_date }}',
                                                @js($record->borrowing_purpose),
                                                @js($record->borrowing_destination_location),
                                                @js($record->borrowing_authorized_by),
                                                @js($record->borrowing_remarks),
                                                '{{ $record->borrowing_status }}'
                                            )"

                                            class="flex h-10 w-10 items-center
                                                justify-center rounded-xl
                                                bg-slate-100 text-slate-600
                                                transition
                                                hover:bg-slate-200
                                                hover:text-slate-900"

                                            data-tooltip="View borrowing details"

                                            aria-label="View borrowing details"
                                        >
                                            <i
                                                data-lucide="eye"
                                                class="h-[18px] w-[18px]"
                                            ></i>
                                        </button>



                                        {{-- ================================= --}}
                                        {{-- RETURN BUTTON --}}
                                        {{-- ================================= --}}

                                        @if ($record->borrowing_status == "Borrowed")

                                            <button
                                                type="button"

                                                onclick="openReturnModal(
                                                    '{{$record->borrowing_record_id}}',
                                                    '{{$record->equipment_name}}'
                                                )"

                                                class="flex h-10 w-10 items-center
                                                    justify-center rounded-xl
                                                    bg-slate-950 text-white
                                                    shadow-sm transition
                                                    hover:bg-slate-800
                                                    active:scale-95"

                                                data-tooltip="Return equipment"

                                                aria-label="Return equipment"
                                            >
                                                <i
                                                    data-lucide="archive-restore"
                                                    class="h-[18px] w-[18px]"
                                                ></i>
                                            </button>

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

                                            No borrowing records yet

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
                    <p class="mt-1 text-sm text-slate-500">Search and add multiple items for one borrower in a single record set.</p>
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
                action="/maintenance/borrowing/store"
                class="flex min-h-0 flex-1 flex-col"
                @submit="prepareSubmit($event)"
            >
                @csrf

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Equipment cart</p>
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
                                    class="inline-flex h-11 shrink-0 items-center rounded-xl bg-slate-900 px-4 text-sm font-medium text-white transition hover:bg-slate-800"
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
                                                    class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
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
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="borrowerName" class="mb-1.5 block text-sm text-slate-600">
                                    Borrower name <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="borrowerName"
                                    type="text"
                                    name="borrowing_borrower_name"
                                    placeholder="Enter borrower name"
                                    required
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                />
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
                                    Borrow date <span class="text-rose-500">*</span>
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
                                    Expected return <span class="text-rose-500">*</span>
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

    <!-- ===================================================== -->
    <!-- VIEW MODAL — slide-out details panel -->
    <!-- ===================================================== -->

    <div
        id="viewModal"
        class="fixed inset-0 z-50 hidden"
        onclick="if (event.target === this) closeViewModal()"
    >
        <div class="absolute inset-0 bg-[#0b1220]/55 backdrop-blur-[2px]"></div>

        <aside
            id="viewModalPanel"
            class="absolute inset-y-0 right-0 rounded-l-2xl flex h-full w-full max-w-md translate-x-full flex-col bg-white shadow-2xl transition-transform duration-300 ease-out sm:max-w-lg"
        >
            {{-- Header --}}
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">
                        Borrowing Details
                    </h2>
                    <p
                        id="viewSubtitle"
                        class="mt-1 text-sm text-slate-500"
                    >
                        Review borrowing record information.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="closeViewModal()"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            {{-- Scrollable body --}}
            <div class="min-h-0 flex-1 overflow-y-auto">
                {{-- Profile summary --}}
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div
                            id="viewAvatar"
                            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-orange-100 text-lg font-semibold text-orange-700"
                        >
                            —
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3
                                    id="viewBorrowerName"
                                    class="truncate text-base font-semibold text-slate-950"
                                >
                                    —
                                </h3>
                                <span
                                    id="viewStatusBadge"
                                    class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600"
                                >
                                    —
                                </span>
                                <span
                                    id="viewDepartmentBadge"
                                    class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700"
                                >
                                    —
                                </span>
                            </div>

                            <div class="mt-3 space-y-2">
                                <div class="flex items-center gap-2 text-sm text-slate-600">
                                    <i data-lucide="monitor" class="h-3.5 w-3.5 shrink-0 text-sky-600"></i>
                                    <span id="viewEquipmentMeta" class="truncate">—</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-slate-600">
                                    <i data-lucide="map-pin" class="h-3.5 w-3.5 shrink-0 text-emerald-600"></i>
                                    <span id="viewDestinationMeta" class="truncate">—</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-slate-600">
                                    <i data-lucide="shield-check" class="h-3.5 w-3.5 shrink-0 text-violet-600"></i>
                                    <span class="truncate">
                                        Authorized by:
                                        <span id="viewAuthorizedMeta" class="font-medium text-slate-800">—</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="border-b border-slate-200 px-6">
                    <nav class="flex gap-6" aria-label="Borrowing details tabs">
                        <button
                            type="button"
                            data-view-tab="overview"
                            onclick="setViewTab('overview')"
                            class="view-tab relative -mb-px border-b-2 border-slate-950 py-3 text-sm font-semibold text-slate-950"
                        >
                            Overview
                        </button>
                        <button
                            type="button"
                            data-view-tab="schedule"
                            onclick="setViewTab('schedule')"
                            class="view-tab relative -mb-px border-b-2 border-transparent py-3 text-sm font-medium text-slate-500 transition hover:text-slate-800"
                        >
                            Schedule
                        </button>
                        <button
                            type="button"
                            data-view-tab="notes"
                            onclick="setViewTab('notes')"
                            class="view-tab relative -mb-px border-b-2 border-transparent py-3 text-sm font-medium text-slate-500 transition hover:text-slate-800"
                        >
                            Notes
                        </button>
                    </nav>
                </div>

                {{-- Tab panels --}}
                <div class="px-6 py-5">
                    <div id="viewTabOverview" class="view-tab-panel space-y-4">
                        <h4 class="text-sm font-semibold text-slate-800">
                            Borrowing Information
                        </h4>
                        <div
                            id="viewOverviewRows"
                            class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white"
                        ></div>
                    </div>

                    <div id="viewTabSchedule" class="view-tab-panel hidden space-y-4">
                        <h4 class="text-sm font-semibold text-slate-800">
                            Schedule
                        </h4>
                        <div
                            id="viewScheduleRows"
                            class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white"
                        ></div>
                    </div>

                    <div id="viewTabNotes" class="view-tab-panel hidden space-y-4">
                        <h4 class="text-sm font-semibold text-slate-800">
                            Purpose & Remarks
                        </h4>
                        <div
                            id="viewNotesRows"
                            class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white"
                        ></div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button
                    type="button"
                    onclick="closeViewModal()"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Close
                </button>

                <button
                    type="button"
                    id="viewReturnButton"
                    class="hidden inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800"
                >
                    <i data-lucide="archive-restore" class="h-4 w-4"></i>
                    Return equipment
                </button>
            </div>
        </aside>
    </div>

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
            action="/maintenance/borrowing/return"
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
                <div
                    class="mb-6 rounded-xl border border-slate-200 px-4"
                >
                    <div
                        class="flex items-center justify-between gap-6 py-3.5"
                    >
                        <span class="shrink-0 text-sm text-slate-500">
                            Equipment
                        </span>

                        <span
                            id="returnEquipmentName"
                            class="min-w-0 truncate text-right text-sm font-medium text-slate-950"
                        ></span>
                    </div>
                </div>

                <!-- ===================================== -->
                <!-- RETURN FIELDS -->
                <!-- ===================================== -->
                <div class="space-y-5">

                    <!-- CONDITION UPON RETURN -->
                    <div>
                        <label
                            for="returnCondition"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Condition upon return
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
                    class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200 active:bg-black"
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
                },
            };
        }

        window.borrowEquipmentCart = borrowEquipmentCart;

        function openBorrowModal() {
            const modal = document.getElementById("borrowModal");
            if (!modal) return;
            if (modal._x_dataStack?.[0]?.reset) {
                modal._x_dataStack[0].reset();
            }
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
        }

        function escapeHtml(value) {
            return String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#39;");
        }

        function getBorrowerInitials(name) {
            const parts = String(name || "")
                .trim()
                .split(/\s+/)
                .filter(Boolean);

            if (!parts.length) return "?";
            if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();

            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }

        function formatViewDate(value) {
            if (!value || value === "null" || value === "undefined") return "—";

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;

            return date.toLocaleDateString("en-US", {
                month: "short",
                day: "numeric",
                year: "numeric",
            });
        }

        function statusBadgeClass(status) {
            switch (status) {
                case "Borrowed":
                    return "bg-sky-50 text-sky-700";
                case "Returned":
                    return "bg-emerald-50 text-emerald-700";
                case "Overdue":
                    return "bg-rose-50 text-rose-700";
                default:
                    return "bg-slate-100 text-slate-600";
            }
        }

        function detailRow(label, valueHtml) {
            return `
                <div class="flex items-start justify-between gap-6 px-4 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">${label}</span>
                    <div class="min-w-0 text-right text-sm font-medium text-slate-900">
                        ${valueHtml}
                    </div>
                </div>
            `;
        }

        function setViewTab(tab) {
            const panels = {
                overview: document.getElementById("viewTabOverview"),
                schedule: document.getElementById("viewTabSchedule"),
                notes: document.getElementById("viewTabNotes"),
            };

            Object.keys(panels).forEach(function (key) {
                panels[key].classList.toggle("hidden", key !== tab);
            });

            document.querySelectorAll(".view-tab").forEach(function (button) {
                const active = button.getAttribute("data-view-tab") === tab;
                button.classList.toggle("border-slate-950", active);
                button.classList.toggle("text-slate-950", active);
                button.classList.toggle("font-semibold", active);
                button.classList.toggle("border-transparent", !active);
                button.classList.toggle("text-slate-500", !active);
                button.classList.toggle("font-medium", !active);
            });
        }

        function viewBorrowing(
            recordId,
            equipment,
            borrower,
            department,
            quantity,
            borrowDate,
            expectedReturn,
            actualReturn,
            purpose,
            destination,
            authorized,
            remarks,
            status,
        ) {
            const safeBorrower = borrower || "Unknown borrower";
            const safeEquipment = equipment || "—";
            const safeDepartment = department || "No department";
            const safeDestination = destination || "—";
            const safeAuthorized = authorized || "—";
            const safeStatus = status || "Unknown";
            const safePurpose = purpose || "—";
            const safeRemarks = remarks || "—";

            document.getElementById("viewSubtitle").textContent =
                "Review " + safeBorrower + "'s borrowing record.";
            document.getElementById("viewBorrowerName").textContent = safeBorrower;
            document.getElementById("viewAvatar").textContent =
                getBorrowerInitials(safeBorrower);
            document.getElementById("viewEquipmentMeta").textContent = safeEquipment;
            document.getElementById("viewDestinationMeta").textContent =
                safeDestination;
            document.getElementById("viewAuthorizedMeta").textContent =
                safeAuthorized;

            const statusBadge = document.getElementById("viewStatusBadge");
            statusBadge.textContent = safeStatus;
            statusBadge.className =
                "inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold " +
                statusBadgeClass(safeStatus);

            document.getElementById("viewDepartmentBadge").textContent =
                safeDepartment;

            document.getElementById("viewOverviewRows").innerHTML = [
                detailRow("Equipment", escapeHtml(safeEquipment)),
                detailRow("Borrower", escapeHtml(safeBorrower)),
                detailRow("Department", escapeHtml(safeDepartment)),
                detailRow(
                    "Quantity",
                    '<span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2.5 py-1 text-sm font-semibold text-slate-700">' +
                        escapeHtml(quantity || "—") +
                        "</span>"
                ),
                detailRow("Destination", escapeHtml(safeDestination)),
                detailRow("Authorized by", escapeHtml(safeAuthorized)),
                detailRow(
                    "Status",
                    '<span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium ' +
                        statusBadgeClass(safeStatus) +
                        '">' +
                        escapeHtml(safeStatus) +
                        "</span>"
                ),
            ].join("");

            document.getElementById("viewScheduleRows").innerHTML = [
                detailRow(
                    "Borrow date",
                    '<span class="inline-flex items-center gap-1.5"><i data-lucide="calendar" class="h-3.5 w-3.5 text-sky-600"></i>' +
                        escapeHtml(formatViewDate(borrowDate)) +
                        "</span>"
                ),
                detailRow(
                    "Expected return",
                    '<span class="inline-flex items-center gap-1.5"><i data-lucide="clock" class="h-3.5 w-3.5 text-amber-600"></i>' +
                        escapeHtml(formatViewDate(expectedReturn)) +
                        "</span>"
                ),
                detailRow(
                    "Actual return",
                    '<span class="inline-flex items-center gap-1.5"><i data-lucide="check-circle-2" class="h-3.5 w-3.5 text-emerald-600"></i>' +
                        escapeHtml(formatViewDate(actualReturn)) +
                        "</span>"
                ),
            ].join("");

            document.getElementById("viewNotesRows").innerHTML = [
                detailRow(
                    "Purpose",
                    '<span class="whitespace-pre-wrap text-slate-700">' +
                        escapeHtml(safePurpose) +
                        "</span>"
                ),
                detailRow(
                    "Remarks",
                    '<span class="whitespace-pre-wrap text-slate-700">' +
                        escapeHtml(safeRemarks) +
                        "</span>"
                ),
            ].join("");

            const returnButton = document.getElementById("viewReturnButton");
            if (safeStatus === "Borrowed") {
                returnButton.classList.remove("hidden");
                returnButton.onclick = function () {
                    closeViewModal();
                    openReturnModal(recordId, safeEquipment);
                };
            } else {
                returnButton.classList.add("hidden");
                returnButton.onclick = null;
            }

            setViewTab("overview");

            const modal = document.getElementById("viewModal");
            const panel = document.getElementById("viewModalPanel");

            modal.classList.remove("hidden");
            document.body.style.overflow = "hidden";

            requestAnimationFrame(function () {
                panel.classList.remove("translate-x-full");
            });

            if (window.lucide) lucide.createIcons();
        }

        function closeViewModal() {
            const modal = document.getElementById("viewModal");
            const panel = document.getElementById("viewModalPanel");

            panel.classList.add("translate-x-full");
            document.body.style.overflow = "";

            setTimeout(function () {
                modal.classList.add("hidden");
            }, 280);
        }

        function openReturnModal(id, equipment) {
            document.getElementById("returnBorrowingId").value = id;
            document.getElementById("returnEquipmentName").innerText = equipment;
            document.getElementById("returnModal").classList.remove("hidden");
            document.getElementById("returnModal").classList.add("flex");
        }

        function closeReturnModal() {
            document.getElementById("returnModal").classList.add("hidden");
            document.getElementById("returnModal").classList.remove("flex");
        }

    </script>

@endsection
