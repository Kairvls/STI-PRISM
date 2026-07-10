@extends ("layouts.maintenance-layout")

@php
    use Carbon\Carbon;
@endphp

@section ("content")

    <div
        class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
    >
        <div>
            <h1 class="text-4xl font-black text-slate-900">
                Borrowing Records
            </h1>

            <p class="mt-1 text-slate-500">Monitor equipment inventory, condition, and operational status.</p>
        </div>

        <button
            type="button"
            onclick="openBorrowModal()"
            class="inline-flex items-center gap-2 rounded-xl bg-[rgba(0,55,199,0.85)] px-4 py-3 font-semibold text-sm text-white transition hover:bg-[rgba(0,44,155,0.85)]"
        >
            <i data-lucide="plus" class="w-4 h-4"></i>

            Borrow Equipment
        </button>
    </div>

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

                            class="h-10 w-full rounded-lg
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

                            class="h-10 min-w-[170px]
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

                        class="inline-flex h-10 items-center
                            justify-center gap-2 rounded-lg
                            bg-slate-950 px-4
                            text-sm font-semibold text-white
                            transition hover:bg-slate-800"
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
                                    "Borrowed" =>
                                        "bg-blue-50 text-blue-700 ring-blue-200",

                                    "Returned" =>
                                        "bg-emerald-50 text-emerald-700 ring-emerald-200",

                                    "Overdue" =>
                                        "bg-red-50 text-red-700 ring-red-200",

                                    default =>
                                        "bg-slate-100 text-slate-600 ring-slate-200",
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

                                                title="{{ $record->equipment_name }}"
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

                                    <span
                                        class="inline-flex items-center gap-1.5
                                            rounded-full px-2.5 py-1
                                            text-[11px] font-medium
                                            ring-1 ring-inset
                                            {{ $statusClass }}"
                                    >

                                        <span
                                            class="h-1.5 w-1.5 rounded-full
                                                {{ $statusDotClass }}"
                                        ></span>

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
                                                '{{ $record->equipment_name }}',
                                                '{{ $record->borrowing_borrower_name }}',
                                                '{{ $record->borrowing_borrower_department }}',
                                                '{{ $record->borrowing_quantity }}',
                                                '{{ $record->borrowing_date }}',
                                                '{{ $record->borrowing_expected_return_date }}',
                                                '{{ $record->borrowing_actual_return_date }}',
                                                '{{ $record->borrowing_purpose }}',
                                                '{{ $record->borrowing_destination_location }}',
                                                '{{ $record->borrowing_authorized_by }}',
                                                '{{ $record->borrowing_remarks }}',
                                                '{{ $record->borrowing_status }}'
                                            )"

                                            class="flex h-10 w-10 items-center
                                                justify-center rounded-xl
                                                bg-slate-100 text-slate-600
                                                transition
                                                hover:bg-slate-200
                                                hover:text-slate-900"

                                            title="View borrowing details"

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

                                                title="Return equipment"

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
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- BORROW EQUIPMENT MODAL -->
    <!-- ===================================== -->
    <div
        class="flex max-h-[70vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div
            class="flex shrink-0 items-start justify-between gap-6 px-6 pb-5 pt-6 border-b border-dashed border-slate-500"
        >
            <div>
                <p
                    class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                >
                    Equipment Borrowing
                </p>

                <h2
                    class="mt-1.5 text-lg font-semibold tracking-tight text-slate-950"
                >
                    Borrow equipment
                </h2>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeBorrowModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- BORROW FORM -->
        <!-- ===================================== -->
        <form
            method="POST"
            action="/maintenance/borrowing/store"
            class="flex min-h-0 flex-1 flex-col"
        >
            @csrf

            <!-- ===================================== -->
            <!-- SCROLLABLE CONTENT -->
            <!-- ===================================== -->
            <div
                class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100 px-6 py-6"
            >
                <div class="space-y-8">

                    <!-- ===================================== -->
                    <!-- EQUIPMENT DETAILS -->
                    <!-- ===================================== -->
                    <section>
                        <div class="mb-4">
                            <h3
                                class="text-sm font-semibold text-slate-900"
                            >
                                Equipment details
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Select the equipment and quantity to be borrowed.
                            </p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <!-- EQUIPMENT -->
                            <div>
                                <label
                                    for="borrowEquipment"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Equipment
                                </label>

                                <select
                                    id="borrowEquipment"
                                    name="borrowing_equipment_id"
                                    required
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                >
                                    <option value="">
                                        Select equipment
                                    </option>

                                    @foreach ($equipment as $item)
                                        <option
                                            value="{{ $item->equipment_id }}"
                                        >
                                            {{ $item->equipment_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- QUANTITY -->
                            <div>
                                <label
                                    for="borrowQuantity"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Quantity
                                </label>

                                <input
                                    id="borrowQuantity"
                                    type="number"
                                    name="borrowing_quantity"
                                    value="1"
                                    min="1"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <!-- ===================================== -->
                    <!-- BORROWER INFORMATION -->
                    <!-- ===================================== -->
                    <section>
                        <div class="mb-4">
                            <h3
                                class="text-sm font-semibold text-slate-900"
                            >
                                Borrower information
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Provide the details of the person borrowing the equipment.
                            </p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <!-- BORROWER NAME -->
                            <div>
                                <label
                                    for="borrowerName"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Borrower name
                                </label>

                                <input
                                    id="borrowerName"
                                    type="text"
                                    name="borrowing_borrower_name"
                                    placeholder="Enter borrower name"
                                    required
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- DEPARTMENT -->
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label
                                        for="borrowDepartment"
                                        class="text-sm font-medium text-slate-700"
                                    >
                                        Department
                                    </label>

                                    <span class="text-xs text-slate-400">
                                        Optional
                                    </span>
                                </div>

                                <input
                                    id="borrowDepartment"
                                    type="text"
                                    name="borrowing_borrower_department"
                                    placeholder="Enter department"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- AUTHORIZED BY -->
                            <div class="md:col-span-2">
                                <div class="mb-2 flex items-center justify-between">
                                    <label
                                        for="borrowAuthorizedBy"
                                        class="text-sm font-medium text-slate-700"
                                    >
                                        Authorized by
                                    </label>

                                    <span class="text-xs text-slate-400">
                                        Optional
                                    </span>
                                </div>

                                <input
                                    id="borrowAuthorizedBy"
                                    type="text"
                                    name="borrowing_authorized_by"
                                    placeholder="Enter authorizing personnel"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <!-- ===================================== -->
                    <!-- BORROWING SCHEDULE -->
                    <!-- ===================================== -->
                    <section>
                        <div class="mb-4">
                            <h3
                                class="text-sm font-semibold text-slate-900"
                            >
                                Borrowing schedule
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Set the borrowing period and equipment condition.
                            </p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <!-- BORROW DATE -->
                            <div>
                                <label
                                    for="borrowDate"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Borrow date
                                </label>

                                <input
                                    id="borrowDate"
                                    type="date"
                                    name="borrowing_date"
                                    required
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- EXPECTED RETURN DATE -->
                            <div>
                                <label
                                    for="borrowExpectedReturn"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Expected return date
                                </label>

                                <input
                                    id="borrowExpectedReturn"
                                    type="date"
                                    name="borrowing_expected_return_date"
                                    required
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- CONDITION -->
                            <div class="md:col-span-2">
                                <div class="mb-2 flex items-center justify-between">
                                    <label
                                        for="borrowCondition"
                                        class="text-sm font-medium text-slate-700"
                                    >
                                        Equipment condition
                                    </label>

                                    <span class="text-xs text-slate-400">
                                        Optional
                                    </span>
                                </div>

                                <input
                                    id="borrowCondition"
                                    type="text"
                                    name="borrowing_equipment_condition"
                                    placeholder="e.g. Good condition"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <!-- ===================================== -->
                    <!-- ADDITIONAL DETAILS -->
                    <!-- ===================================== -->
                    <section>
                        <div class="mb-4">
                            <h3
                                class="text-sm font-semibold text-slate-900"
                            >
                                Additional details
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Add the purpose, destination, or other relevant notes.
                            </p>
                        </div>

                        <div class="space-y-5">
                            <!-- PURPOSE -->
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label
                                        for="borrowPurpose"
                                        class="text-sm font-medium text-slate-700"
                                    >
                                        Purpose
                                    </label>

                                    <span class="text-xs text-slate-400">
                                        Optional
                                    </span>
                                </div>

                                <textarea
                                    id="borrowPurpose"
                                    name="borrowing_purpose"
                                    rows="3"
                                    placeholder="Describe why the equipment is being borrowed"
                                    class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                ></textarea>
                            </div>

                            <!-- DESTINATION -->
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label
                                        for="borrowDestination"
                                        class="text-sm font-medium text-slate-700"
                                    >
                                        Destination
                                    </label>

                                    <span class="text-xs text-slate-400">
                                        Optional
                                    </span>
                                </div>

                                <input
                                    id="borrowDestination"
                                    type="text"
                                    name="borrowing_destination_location"
                                    placeholder="Enter destination location"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- REMARKS -->
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label
                                        for="borrowRemarks"
                                        class="text-sm font-medium text-slate-700"
                                    >
                                        Remarks
                                    </label>

                                    <span class="text-xs text-slate-400">
                                        Optional
                                    </span>
                                </div>

                                <textarea
                                    id="borrowRemarks"
                                    name="borrowing_remarks"
                                    rows="3"
                                    placeholder="Add any additional notes"
                                    class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                ></textarea>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="border-t border-dashed border-slate-500"></div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div
                class="flex shrink-0 items-center justify-end gap-2 px-6 py-4"
            >
                <button
                    type="button"
                    onclick="closeBorrowModal()"
                    class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-[rgba(0,55,199,0.85)] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[rgba(0,44,155,0.85)] focus:outline-none focus:ring-4 focus:ring-slate-200 active:bg-black"
                >
                    Create borrowing record
                </button>
            </div>
        </form>
    </div>
</div>

    <!-- ===================================================== -->
    <!-- VIEW MODAL -->
    <!-- ===================================================== -->

    <div
    id="viewModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- BORROWING DETAILS MODAL -->
    <!-- ===================================== -->
    <div
        class="flex max-h-[80vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div
            class="flex shrink-0 items-start justify-between gap-6 px-6 pb-5 pt-6 border-b border-dashed border-slate-500"
        >
            <div>
                <p
                    class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                >
                    Borrowing Record
                </p>

                <h2
                    class="mt-1.5 text-lg font-semibold tracking-tight text-slate-950"
                >
                    Borrowing details
                </h2>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeViewModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- BORROWING DETAILS CONTENT -->
        <!-- ===================================== -->
        <div
            class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100 px-6 py-2"
        >
            <div
                id="viewBorrowDetails"
                class="divide-y divide-slate-100"
            ></div>
        </div>

        <div class="border-t border-dashed border-slate-500"></div>

        <!-- ===================================== -->
        <!-- MODAL FOOTER -->
        <!-- ===================================== -->
        <div
            class="flex shrink-0 items-center justify-end px-6 py-4"
        >
            <button
                type="button"
                onclick="closeViewModal()"
                class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
            >
                Close
            </button>
        </div>
    </div>
</div>

    <!-- ===================================================== -->
    <!-- RETURN MODAL -->
    <!-- ===================================================== -->

    <div
    id="returnModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
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
        function openBorrowModal() {
            document.getElementById("borrowModal").classList.remove("hidden");

            document.getElementById("borrowModal").classList.add("flex");
        }

        function closeBorrowModal() {
            document.getElementById("borrowModal").classList.add("hidden");

            document.getElementById("borrowModal").classList.remove("flex");
        }

        function viewBorrowing(
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
            // =====================================
            // BORROWING DETAILS CONTAINER
            // =====================================
            document.getElementById("viewBorrowDetails").innerHTML = `

                <!-- ===================================== -->
                <!-- EQUIPMENT -->
                <!-- ===================================== -->
                <div class="flex items-start justify-between gap-8 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">
                        Equipment
                    </span>

                    <span class="max-w-[65%] text-right text-sm font-medium text-slate-950">
                        ${equipment || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- BORROWER -->
                <!-- ===================================== -->
                <div class="flex items-start justify-between gap-8 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">
                        Borrower
                    </span>

                    <span class="max-w-[65%] text-right text-sm font-medium text-slate-900">
                        ${borrower || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- DEPARTMENT -->
                <!-- ===================================== -->
                <div class="flex items-start justify-between gap-8 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">
                        Department
                    </span>

                    <span class="max-w-[65%] text-right text-sm font-medium text-slate-900">
                        ${department || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- QUANTITY -->
                <!-- ===================================== -->
                <div class="flex items-center justify-between gap-8 py-3.5">
                    <span class="text-sm text-slate-500">
                        Quantity
                    </span>

                    <span class="text-sm font-medium text-slate-900">
                        ${quantity || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- BORROW DATE -->
                <!-- ===================================== -->
                <div class="flex items-center justify-between gap-8 py-3.5">
                    <span class="text-sm text-slate-500">
                        Borrow date
                    </span>

                    <span class="text-sm font-medium text-slate-900">
                        ${borrowDate || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- EXPECTED RETURN -->
                <!-- ===================================== -->
                <div class="flex items-center justify-between gap-8 py-3.5">
                    <span class="text-sm text-slate-500">
                        Expected return
                    </span>

                    <span class="text-sm font-medium text-slate-900">
                        ${expectedReturn || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- ACTUAL RETURN -->
                <!-- ===================================== -->
                <div class="flex items-center justify-between gap-8 py-3.5">
                    <span class="text-sm text-slate-500">
                        Actual return
                    </span>

                    <span class="text-sm font-medium text-slate-900">
                        ${actualReturn || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- DESTINATION -->
                <!-- ===================================== -->
                <div class="flex items-start justify-between gap-8 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">
                        Destination
                    </span>

                    <span class="max-w-[65%] text-right text-sm font-medium text-slate-900">
                        ${destination || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- AUTHORIZED BY -->
                <!-- ===================================== -->
                <div class="flex items-start justify-between gap-8 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">
                        Authorized by
                    </span>

                    <span class="max-w-[65%] text-right text-sm font-medium text-slate-900">
                        ${authorized || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- PURPOSE -->
                <!-- ===================================== -->
                <div class="flex items-start justify-between gap-8 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">
                        Purpose
                    </span>

                    <span class="max-w-[65%] whitespace-pre-wrap text-right text-sm leading-6 text-slate-700">
                        ${purpose || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- REMARKS -->
                <!-- ===================================== -->
                <div class="flex items-start justify-between gap-8 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">
                        Remarks
                    </span>

                    <span class="max-w-[65%] whitespace-pre-wrap text-right text-sm leading-6 text-slate-700">
                        ${remarks || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- STATUS -->
                <!-- ===================================== -->
                <div class="flex items-center justify-between gap-8 py-3.5">
                    <span class="text-sm text-slate-500">
                        Status
                    </span>

                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                        ${status || "—"}
                    </span>
                </div>
            `;

            // =====================================
            // OPEN BORROWING DETAILS MODAL
            // =====================================
            document.getElementById("viewModal").classList.remove("hidden");
            document.getElementById("viewModal").classList.add("flex");
        }

        function closeViewModal() {
            document.getElementById("viewModal").classList.add("hidden");

            document.getElementById("viewModal").classList.remove("flex");
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
