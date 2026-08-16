@extends ("layouts.maintenance-layout")

@section ("content")

    <div
        class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
    >
        <div>
            <h1 class="text-4xl font-black text-slate-900">
                Disposal Records
            </h1>

            <p class="mt-1 text-slate-500">
                Equipment here is waiting for replacement until you mark it disposed.
            </p>
        </div>

        <button
            type="button"
            onclick="openDisposeModal()"
            class="inline-flex items-center gap-2 rounded-xl bg-[rgba(0,55,199,0.85)] px-4 py-3 font-semibold text-sm text-white transition hover:bg-[rgba(0,44,155,0.85)]"
        >
            <i data-lucide="plus" class="w-4 h-4"></i>

            Dispose Equipment
        </button>
    </div>

    <div
        class="mb-6 mt-6 overflow-hidden rounded-lg border-t border-b border-slate-300 bg-gray-100 shadow-sm"
    >
        <div
            class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-y-0 xl:grid-cols-[380px_1fr_1fr_1fr]"
        >

            {{-- ===================================================== --}}
            {{-- TOTAL DISPOSAL RECORDS --}}
            {{-- ===================================================== --}}

            <div class="flex items-center justify-between px-8 py-6">

                <div class="flex flex-col">

                    <p class="text-sm font-medium text-slate-500">
                        Total Disposal Records
                    </p>

                    <h2 class="mt-2 text-5xl font-medium text-slate-900">
                        {{ number_format($totalDisposalRecords) }}
                    </h2>


                    {{-- ================================================= --}}
                    {{-- MONTHLY PERCENTAGE CHANGE --}}
                    {{-- ================================================= --}}

                    <p class="mt-3 text-sm">

                        @if ($disposalMonthlyPercentage === null)

                            <span class="font-semibold text-emerald-500">
                                New activity
                            </span>

                        @else

                            <span
                                class="font-semibold
                                    {{
                                        $disposalMonthlyPercentage > 0
                                            ? 'text-emerald-500'
                                            : (
                                                $disposalMonthlyPercentage < 0
                                                    ? 'text-red-500'
                                                    : 'text-slate-500'
                                            )
                                    }}"
                            >
                                {{
                                    $disposalMonthlyPercentage > 0
                                        ? '+'
                                        : ''
                                }}{{ number_format($disposalMonthlyPercentage, 2) }}%
                            </span>

                        @endif

                        <span class="text-slate-500">
                            From last month
                        </span>

                    </p>

                </div>


                {{-- ===================================================== --}}
                {{-- GRAPH --}}
                {{-- ===================================================== --}}

                <div class="ml-6 h-20 w-40 shrink-0">

                    <svg
                        viewBox="0 0 300 100"
                        class="h-full w-full"
                        fill="none"
                    >

                        @php

                            $disposalCounts =
                                $disposalMonthlyTrend->pluck('count');

                            $maxDisposalCount =
                                max(
                                    1,
                                    $disposalCounts->max()
                                );

                            $disposalPointCount =
                                max(
                                    1,
                                    $disposalMonthlyTrend->count() - 1
                                );

                            $disposalPoints =
                                $disposalMonthlyTrend
                                    ->values()
                                    ->map(function ($item, $index) use (
                                        $maxDisposalCount,
                                        $disposalPointCount
                                    ) {

                                        $x =
                                            ($index / $disposalPointCount)
                                            * 300;

                                        $y =
                                            90
                                            - (
                                                ($item['count'] / $maxDisposalCount)
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
                            points="{{ $disposalPoints }}"
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
            {{-- DAMAGED DISPOSALS --}}
            {{-- ===================================================== --}}

            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    Damaged Disposals
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($damagedDisposals) }}
                </h2>

                <p class="text-base">

                    <span class="font-semibold text-slate-900">
                        {{ number_format($damagedDisposalsPercentage, 2) }}%
                    </span>

                    <span class="text-slate-500">
                        of disposal records
                    </span>

                </p>

            </div>


            {{-- ===================================================== --}}
            {{-- DISPOSED EQUIPMENT --}}
            {{-- ===================================================== --}}

            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    Disposed Equipment
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($disposedEquipment) }}
                </h2>

                <p class="text-base">

                    <span class="font-semibold text-slate-900">
                        {{ number_format($disposedEquipmentPercentage, 2) }}%
                    </span>

                    <span class="text-slate-500">
                        of all equipment
                    </span>

                </p>

            </div>


            {{-- ===================================================== --}}
            {{-- DISPOSED THIS MONTH --}}
            {{-- ===================================================== --}}

            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    Disposed This Month
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($currentMonthDisposals) }}
                </h2>

                <p class="text-base">

                    @if ($disposalMonthlyPercentage === null)

                        <span class="font-semibold text-emerald-500">
                            New activity
                        </span>

                    @else

                        <span
                            class="font-semibold
                                {{
                                    $disposalMonthlyPercentage > 0
                                        ? 'text-emerald-500'
                                        : (
                                            $disposalMonthlyPercentage < 0
                                                ? 'text-red-500'
                                                : 'text-slate-500'
                                        )
                                }}"
                        >
                            {{
                                $disposalMonthlyPercentage > 0
                                    ? '+'
                                    : ''
                            }}{{ number_format($disposalMonthlyPercentage, 2) }}%
                        </span>

                    @endif

                    <span class="text-slate-500">
                        From last month
                    </span>

                </p>

            </div>

        </div>
    </div>

    <div class="rounded-3xl bg-white">
        <!--<div class="mb-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-black">Disposal Records</h1>

            <button
                onclick="openDisposeModal()"
                class="rounded-xl bg-red-600 px-5 py-3 text-white hover:bg-red-700"
            >
                Dispose Equipment
            </button>
        </div>-->

        {{-- ========================================================= --}}
        {{-- DISPOSAL RECORDS TABLE --}}
        {{-- ========================================================= --}}

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">

            {{-- ===================================================== --}}
            {{-- HEADER --}}
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
                        <i data-lucide="archive-x" class="h-4 w-4"></i>
                    </div>


                    <div>

                        <h2 class="text-sm font-semibold text-slate-900">
                            Disposal Records
                        </h2>

                        <p class="mt-0.5 text-xs text-slate-400">
                            Track disposed equipment and disposal information
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
                    <i data-lucide="package-x" class="h-3.5 w-3.5"></i>

                    {{ $disposals->total() }} total
                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- SEARCH AND FILTER BAR --}}
            {{-- ADD BETWEEN HEADER AND TABLE --}}
            {{-- ===================================================== --}}

            <form
                method="GET"
                action="{{ url()->current() }}"
                class="border-b border-slate-200 px-5 py-4"
            >

                <div class="flex flex-col gap-3 xl:flex-row xl:items-center">

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
                            placeholder="Search equipment, category, reason, or disposal area..."

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
                    {{-- CATEGORY FILTER --}}
                    {{-- ================================================= --}}

                    <div class="relative">

                        <select
                            name="category"

                            class="h-10 min-w-[175px]
                                appearance-none rounded-lg
                                border border-slate-200
                                bg-white pl-3 pr-9
                                text-sm text-slate-600
                                outline-none transition
                                focus:border-slate-400
                                focus:ring-2 focus:ring-slate-100"
                        >

                            <option value="">
                                All Categories
                            </option>

                            @foreach ($categories as $category)

                                <option
                                    value="{{ $category->equipment_category_id }}"

                                    @selected(
                                        request('category')
                                        == $category->equipment_category_id
                                    )
                                >
                                    {{ $category->equipment_category_name }}
                                </option>

                            @endforeach

                        </select>

                        <i
                            data-lucide="chevron-down"
                            class="pointer-events-none absolute
                                right-3 top-1/2 h-4 w-4
                                -translate-y-1/2 text-slate-400"
                        ></i>

                    </div>


                    {{-- ================================================= --}}
                    {{-- CONDITION FILTER --}}
                    {{-- ================================================= --}}

                    <div class="relative">

                        <select
                            name="condition"

                            class="h-10 min-w-[165px]
                                appearance-none rounded-lg
                                border border-slate-200
                                bg-white pl-3 pr-9
                                text-sm text-slate-600
                                outline-none transition
                                focus:border-slate-400
                                focus:ring-2 focus:ring-slate-100"
                        >

                            <option value="">
                                All Conditions
                            </option>

                            <option
                                value="Good"
                                @selected(request('condition') === 'Good')
                            >
                                Good
                            </option>

                            <option
                                value="Fair"
                                @selected(request('condition') === 'Fair')
                            >
                                Fair
                            </option>

                            <option
                                value="Damaged"
                                @selected(request('condition') === 'Damaged')
                            >
                                Damaged
                            </option>

                            <option
                                value="Critical"
                                @selected(request('condition') === 'Critical')
                            >
                                Critical
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
                    {{-- REASON FILTER --}}
                    {{-- VALUES MATCH YOUR DISPOSE MODAL --}}
                    {{-- ================================================= --}}

                    <div class="relative">

                        <select
                            name="reason"

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
                                All Reasons
                            </option>

                            <option
                                value="Beyond Repair"
                                @selected(request('reason') === 'Beyond Repair')
                            >
                                Beyond repair
                            </option>

                            <option
                                value="Obsolete"
                                @selected(request('reason') === 'Obsolete')
                            >
                                Obsolete
                            </option>

                            <option
                                value="Damaged"
                                @selected(request('reason') === 'Damaged')
                            >
                                Damaged
                            </option>

                            <option
                                value="Lost"
                                @selected(request('reason') === 'Lost')
                            >
                                Lost
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
                    {{-- APPLY --}}
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
                    {{-- CLEAR --}}
                    {{-- ================================================= --}}

                    @if (
                        request()->filled('search')
                        || request()->filled('category')
                        || request()->filled('condition')
                        || request()->filled('reason')
                    )

                        <a
                            href="{{ url()->current() }}"

                            class="inline-flex h-10 items-center
                                justify-center gap-2 rounded-lg
                                border border-slate-200
                                bg-white px-4
                                text-sm font-medium text-slate-600
                                transition
                                hover:border-slate-300
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

                <table class="w-full min-w-[1150px] text-left">

                    {{-- ================================================= --}}
                    {{-- TABLE HEADER --}}
                    {{-- ================================================= --}}

                    <thead class="border-b border-slate-200 bg-slate-50/70">

                        <tr
                            class="text-[12px] font-semibold uppercase
                                tracking-[0.08em] text-black"
                        >

                            <th class="px-5 py-3">
                                Equipment
                            </th>

                            <th class="px-5 py-3">
                                Category
                            </th>

                            <th class="px-5 py-3">
                                Condition
                            </th>

                            <th class="px-5 py-3">
                                Reason
                            </th>

                            <th class="px-5 py-3">
                                Disposal Area
                            </th>

                            <th class="px-5 py-3">
                                Disposed Date
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

                        @forelse ($disposals as $record)

                            @php
                                /*
                                |--------------------------------------------------------------------------
                                | CONDITION STATUS DESIGN
                                |--------------------------------------------------------------------------
                                |
                                | Add or change these values if your database
                                | uses other equipment condition statuses.
                                |
                                */

                                $conditionStatus =
                                    $record->equipment_condition_status ?? "Unknown";


                                $conditionClass = match ($conditionStatus) {
                                    "Good" =>
                                        "bg-emerald-50 text-emerald-700 ring-emerald-200",

                                    "Fair" =>
                                        "bg-blue-50 text-blue-700 ring-blue-200",

                                    "Damaged" =>
                                        "bg-amber-50 text-amber-700 ring-amber-200",

                                    "Critical" =>
                                        "bg-red-50 text-red-700 ring-red-200",

                                    default =>
                                        "bg-slate-100 text-slate-600 ring-slate-200",
                                };


                                $conditionDotClass = match ($conditionStatus) {
                                    "Good" =>
                                        "bg-emerald-500",

                                    "Fair" =>
                                        "bg-blue-500",

                                    "Damaged" =>
                                        "bg-amber-500",

                                    "Critical" =>
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
                                                data-lucide="package-x"
                                                class="h-4 w-4"
                                            ></i>
                                        </div>


                                        <div class="min-w-0">

                                            <p
                                                class="max-w-[210px] truncate
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
                                                {{ ($record->equipment_inventory_status ?? '') === 'For Replacement'
                                                    ? 'Awaiting replacement / disposal'
                                                    : 'Disposed equipment' }}
                                            </p>

                                        </div>

                                    </div>

                                </td>



                                {{-- ===================================== --}}
                                {{-- CATEGORY --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <span
                                        class="inline-flex rounded-md
                                            bg-slate-100 px-2 py-1
                                            text-[11px] font-medium
                                            text-slate-600"
                                    >
                                        {{
                                            $record->equipment_category_name
                                                ?? "Uncategorized"
                                        }}
                                    </span>

                                </td>



                                {{-- ===================================== --}}
                                {{-- CONDITION --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <span
                                        class="inline-flex items-center gap-1.5
                                            rounded-full px-2.5 py-1
                                            text-[11px] font-medium
                                            ring-1 ring-inset
                                            {{ $conditionClass }}"
                                    >

                                        <span
                                            class="h-1.5 w-1.5 rounded-full
                                                {{ $conditionDotClass }}"
                                        ></span>

                                        {{ $conditionStatus }}

                                    </span>

                                </td>



                                {{-- ===================================== --}}
                                {{-- DISPOSAL REASON --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <p
                                        class="max-w-[230px] truncate
                                            text-xs leading-5 text-slate-600"

                                        title="{{ $record->disposal_reason }}"
                                    >
                                        {{
                                            $record->disposal_reason
                                                ?? "No reason provided"
                                        }}
                                    </p>

                                </td>



                                {{-- ===================================== --}}
                                {{-- DISPOSAL AREA --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex items-center gap-2">

                                        <i
                                            data-lucide="map-pin"
                                            class="h-3.5 w-3.5 shrink-0
                                                text-slate-400"
                                        ></i>


                                        <span
                                            class="max-w-[180px] truncate
                                                text-xs font-medium
                                                text-slate-600"

                                            title="{{ $record->disposal_area_location }}"
                                        >
                                            {{
                                                $record->disposal_area_location
                                                    ?? "No area assigned"
                                            }}
                                        </span>

                                    </div>

                                </td>



                                {{-- ===================================== --}}
                                {{-- DISPOSED DATE --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex items-center gap-2">

                                        <i
                                            data-lucide="calendar"
                                            class="h-3.5 w-3.5 shrink-0
                                                text-slate-400"
                                        ></i>


                                        <div>

                                            <p
                                                class="whitespace-nowrap
                                                    text-xs font-medium
                                                    text-slate-700"
                                            >
                                                {{
                                                    \Carbon\Carbon::parse(
                                                        $record->disposal_disposed_at
                                                    )->format("M d, Y")
                                                }}
                                            </p>


                                            <p
                                                class="mt-0.5 text-[10px]
                                                    text-slate-400"
                                            >
                                                {{
                                                    \Carbon\Carbon::parse(
                                                        $record->disposal_disposed_at
                                                    )->format("h:i A")
                                                }}
                                            </p>

                                        </div>

                                    </div>

                                </td>

                                



                                {{-- ===================================== --}}
                                {{-- ACTIONS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div
                                        class="flex items-center justify-center gap-2"
                                    >
                                        <form method="POST" action="/maintenance/disposal/restore">
                                            @csrf
                                            <input type="hidden" name="disposal_id" value="{{ $record->disposal_record_id }}" />
                                            <button
                                                type="submit"
                                                class="flex h-9 items-center rounded-xl bg-white px-2.5 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50"
                                                title="Return to Inventory"
                                            >
                                                Restore
                                            </button>
                                        </form>

                                        @if (($record->equipment_inventory_status ?? '') !== 'Disposed')
                                            <form method="POST" action="/maintenance/disposal/confirm">
                                                @csrf
                                                <input type="hidden" name="disposal_id" value="{{ $record->disposal_record_id }}" />
                                                <button
                                                    type="submit"
                                                    class="flex h-9 items-center rounded-xl bg-slate-900 px-2.5 text-[11px] font-medium text-white transition hover:bg-slate-800"
                                                    title="Mark actually disposed"
                                                >
                                                    Dispose
                                                </button>
                                            </form>
                                        @endif

                                        {{-- ================================= --}}
                                        {{-- VIEW BUTTON --}}
                                        {{-- ================================= --}}

                                        <button
                                            type="button"

                                            onclick="viewDisposal(
                                                '{{ $record->equipment_name }}',
                                                '{{ $record->equipment_category_name }}',
                                                '{{ $record->equipment_condition_status }}',
                                                '{{ $record->disposal_reason }}',
                                                '{{ $record->disposal_area_location }}',
                                                '{{ $record->disposal_disposed_at }}'
                                            )"

                                            class="flex h-9 w-9 items-center
                                                justify-center rounded-xl
                                                bg-slate-100 text-slate-600
                                                transition
                                                hover:bg-slate-200
                                                hover:text-slate-900"

                                            title="View disposal details"

                                            aria-label="View disposal details"
                                        >
                                            <i
                                                data-lucide="eye"
                                                class="h-3.5 w-3.5"
                                            ></i>
                                        </button>



                                        {{-- ================================= --}}
                                        {{-- DELETE BUTTON --}}
                                        {{-- ================================= --}}

                                        <button
                                            type="button"

                                            onclick='openDeleteModal(
                                                @js($record->disposal_record_id)
                                            )'

                                            class="flex h-9 w-9 items-center
                                                justify-center rounded-xl
                                                bg-red-600 text-white
                                                shadow-sm transition
                                                hover:bg-red-700
                                                active:scale-95"

                                            title="Delete disposal record"

                                            aria-label="Delete disposal record"
                                        >
                                            <i
                                                data-lucide="trash-2"
                                                class="h-3.5"
                                            ></i>
                                        </button>

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
                                                data-lucide="{{
                                                    request()->filled('search')
                                                    || request()->filled('category')
                                                    || request()->filled('condition')
                                                    || request()->filled('reason')
                                                        ? 'search-x'
                                                        : 'archive-x'
                                                }}"
                                                class="h-5 w-5"
                                            ></i>
                                        </div>


                                        {{-- ================================================= --}}
                                        {{-- TITLE --}}
                                        {{-- ================================================= --}}

                                        <h3 class="mt-4 text-sm font-semibold text-slate-800">

                                            {{
                                                request()->filled('search')
                                                || request()->filled('category')
                                                || request()->filled('condition')
                                                || request()->filled('reason')

                                                    ? 'No matching disposal records'

                                                    : 'No disposal records yet'
                                            }}

                                        </h3>


                                        {{-- ================================================= --}}
                                        {{-- DESCRIPTION --}}
                                        {{-- ================================================= --}}

                                        <p class="mt-1.5 max-w-xs text-xs leading-5 text-slate-400">

                                            {{
                                                request()->filled('search')
                                                || request()->filled('category')
                                                || request()->filled('condition')
                                                || request()->filled('reason')

                                                    ? 'No disposal records match your current search or filters.'

                                                    : 'Equipment moved to disposal will appear here with its reason, location, and disposal date.'
                                            }}

                                        </p>


                                        {{-- ================================================= --}}
                                        {{-- EMPTY STATE ACTION --}}
                                        {{-- ================================================= --}}

                                        @if (
                                            request()->filled('search')
                                            || request()->filled('category')
                                            || request()->filled('condition')
                                            || request()->filled('reason')
                                        )

                                            <a
                                                href="{{ url()->current() }}"

                                                class="mt-5 inline-flex h-9 items-center gap-2
                                                    rounded-lg border border-slate-200
                                                    bg-white px-3.5
                                                    text-xs font-semibold text-slate-600
                                                    shadow-sm transition
                                                    hover:border-slate-300
                                                    hover:bg-slate-50
                                                    hover:text-slate-900"
                                            >

                                                <i
                                                    data-lucide="rotate-ccw"
                                                    class="h-3.5 w-3.5"
                                                ></i>

                                                Clear filters

                                            </a>

                                        @else

                                            <button
                                                type="button"
                                                onclick="openDisposeModal()"

                                                class="mt-5 inline-flex h-9 items-center gap-2
                                                    rounded-lg border border-slate-200
                                                    bg-white px-3.5
                                                    text-xs font-semibold text-slate-600
                                                    shadow-sm transition
                                                    hover:border-slate-300
                                                    hover:bg-slate-50
                                                    hover:text-slate-900"
                                            >

                                                <i
                                                    data-lucide="plus"
                                                    class="h-3.5 w-3.5"
                                                ></i>

                                                Dispose equipment

                                            </button>

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            {{-- ===================================================== --}}
            {{-- PAGINATION --}}
            {{-- ADD THIS DIRECTLY BELOW THE TABLE CONTAINER --}}
            {{-- ===================================================== --}}

            @if ($disposals->hasPages())

                <div
                    class="flex flex-col gap-3
                        border-t border-slate-200
                        px-5 py-4
                        sm:flex-row
                        sm:items-center
                        sm:justify-between"
                >

                    {{-- ============================================= --}}
                    {{-- PAGINATION INFORMATION --}}
                    {{-- ============================================= --}}

                    <p class="text-xs text-slate-500">

                        Showing

                        <span class="font-semibold text-slate-700">
                            {{ $disposals->firstItem() }}
                        </span>

                        to

                        <span class="font-semibold text-slate-700">
                            {{ $disposals->lastItem() }}
                        </span>

                        of

                        <span class="font-semibold text-slate-700">
                            {{ $disposals->total() }}
                        </span>

                        disposal records

                    </p>


                    {{-- ============================================= --}}
                    {{-- PAGINATION LINKS --}}
                    {{-- ============================================= --}}

                    <div>
                        {{ $disposals->links() }}
                    </div>

                </div>

            @endif

        </section>
    </div>

    <!-- DISPOSE MODAL -->

    <div
    id="disposeModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- DISPOSE EQUIPMENT MODAL -->
    <!-- ===================================== -->
    <div
        class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
            <div class="min-w-0">
                <!-- WARNING ICON -->
                <div
                    class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600"
                >
                    <i data-lucide="archive-x" class="h-4 w-4"></i>
                </div>

                <h2
                    class="text-lg font-semibold tracking-tight text-slate-950"
                >
                    Dispose equipment
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Record equipment that is no longer available for normal use.
                </p>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeDisposeModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- DISPOSAL FORM -->
        <!-- ===================================== -->
        <form action="/maintenance/disposal/store" method="POST">
            @csrf

            <!-- ===================================== -->
            <!-- FORM CONTENT -->
            <!-- ===================================== -->
            <div class="border-y border-slate-100 px-6 py-5">
                <div class="space-y-5">

                    <!-- ===================================== -->
                    <!-- EQUIPMENT -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="disposeEquipment"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Equipment
                        </label>

                        <select
                            id="disposeEquipment"
                            name="equipment_id"
                            required
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                            <option value="">
                                Select equipment
                            </option>

                            @foreach ($equipment as $item)
                                <option value="{{ $item->equipment_id }}">
                                    {{ $item->equipment_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- ===================================== -->
                    <!-- DISPOSAL REASON -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="disposeReason"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Disposal reason
                        </label>

                        <select
                            id="disposeReason"
                            name="reason"
                            required
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                            <option value="">
                                Select a reason
                            </option>

                            <option value="Beyond Repair">
                                Beyond repair
                            </option>

                            <option value="Obsolete">
                                Obsolete
                            </option>

                            <option value="Damaged">
                                Damaged
                            </option>

                            <option value="Lost">
                                Lost
                            </option>
                        </select>
                    </div>

                    <!-- ===================================== -->
                    <!-- DISPOSAL AREA -->
                    <!-- ===================================== -->
                    <div>
                        <div
                            class="mb-2 flex items-center justify-between gap-4"
                        >
                            <label
                                for="disposeLocation"
                                class="text-sm font-medium text-slate-700"
                            >
                                Disposal area
                            </label>

                            <span class="text-xs text-slate-400">
                                Optional
                            </span>
                        </div>

                        <input
                            id="disposeLocation"
                            type="text"
                            name="location"
                            placeholder="Enter storage or disposal location"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        />
                    </div>
                </div>
            </div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button
                    type="button"
                    onclick="closeDisposeModal()"
                    class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-100 active:bg-rose-800"
                >
                    Dispose equipment
                </button>
            </div>
        </form>
    </div>
</div>

    <!-- ===================================================== -->
    <!-- VIEW DISPOSAL MODAL -->
    <!-- ===================================================== -->

    <div
    id="viewModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- DISPOSAL DETAILS MODAL -->
    <!-- ===================================== -->
    <div
        class="flex max-h-[85vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
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
                    Disposal Record
                </p>

                <h2
                    class="mt-1.5 text-lg font-semibold tracking-tight text-slate-950"
                >
                    Disposal details
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
        <!-- DISPOSAL DETAILS CONTENT -->
        <!-- ===================================== -->
        <div
            class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100 px-6 py-2"
        >
            <div
                id="viewDisposalDetails"
                class="divide-y divide-slate-100"
            ></div>
        </div>

        <div class="border-t border-dashed border-slate-500 "></div>

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
    <!-- DELETE DISPOSAL MODAL -->
    <!-- ===================================================== -->

    <div
    id="deleteModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- DELETE DISPOSAL RECORD MODAL -->
    <!-- ===================================== -->
    <div
        class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
            <div class="min-w-0">
                <!-- DELETE ICON -->
                <div
                    class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600"
                >
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                </div>

                <h2
                    class="text-lg font-semibold tracking-tight text-slate-950"
                >
                    Delete disposal record?
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    This disposal record will be permanently deleted. This
                    action cannot be undone.
                </p>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeDeleteModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- DELETE FORM -->
        <!-- ===================================== -->
        <form
            action="/maintenance/disposal/delete"
            method="POST"
        >
            @csrf
            @method('DELETE')

            <input
                type="hidden"
                id="deleteDisposalId"
                name="disposal_id"
            />

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div
                class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4"
            >
                <button
                    type="button"
                    onclick="closeDeleteModal()"
                    class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-100 active:bg-rose-800"
                >
                    Delete record
                </button>
            </div>
        </form>
    </div>
</div>

    <script>
        function openDisposeModal() {
            document.getElementById("disposeModal").classList.remove("hidden");

            document.getElementById("disposeModal").classList.add("flex");
        }

        function closeDisposeModal() {
            document.getElementById("disposeModal").classList.add("hidden");

            document.getElementById("disposeModal").classList.remove("flex");
        }

        function viewDisposal(
            equipment,
            category,
            condition,
            reason,
            location,
            date,
        ) {
            // =====================================
            // DISPOSAL DETAILS CONTAINER
            // =====================================
            document.getElementById("viewDisposalDetails").innerHTML = `

                <!-- ===================================== -->
                <!-- EQUIPMENT -->
                <!-- ===================================== -->
                <div class="flex items-start justify-between gap-8 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">
                        Equipment
                    </span>

                    <span class="max-w-[65%] break-words text-right text-sm font-medium text-slate-950">
                        ${equipment || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- CATEGORY -->
                <!-- ===================================== -->
                <div class="flex items-start justify-between gap-8 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">
                        Category
                    </span>

                    <span class="max-w-[65%] break-words text-right text-sm font-medium text-slate-900">
                        ${category || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- CONDITION -->
                <!-- ===================================== -->
                <div class="flex items-start justify-between gap-8 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">
                        Condition
                    </span>

                    <span class="max-w-[65%] break-words text-right text-sm font-medium text-slate-900">
                        ${condition || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- DISPOSAL REASON -->
                <!-- ===================================== -->
                <div class="flex items-start justify-between gap-8 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">
                        Disposal reason
                    </span>

                    <span class="max-w-[65%] break-words text-right text-sm font-medium text-slate-900">
                        ${reason || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- DISPOSAL AREA -->
                <!-- ===================================== -->
                <div class="flex items-start justify-between gap-8 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">
                        Disposal area
                    </span>

                    <span class="max-w-[65%] break-words text-right text-sm font-medium text-slate-900">
                        ${location || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- DISPOSED DATE -->
                <!-- ===================================== -->
                <div class="flex items-center justify-between gap-8 py-3.5">
                    <span class="text-sm text-slate-500">
                        Disposed date
                    </span>

                    <span class="text-sm font-medium text-slate-900">
                        ${date || "—"}
                    </span>
                </div>
            `;

            // =====================================
            // OPEN DISPOSAL DETAILS MODAL
            // =====================================
            document.getElementById("viewModal").classList.remove("hidden");
            document.getElementById("viewModal").classList.add("flex");
        }

        function closeViewModal() {
            document.getElementById("viewModal").classList.add("hidden");

            document.getElementById("viewModal").classList.remove("flex");
        }

        function openDeleteModal(id) {
            document.getElementById("deleteDisposalId").value = id;

            document.getElementById("deleteModal").classList.remove("hidden");

            document.getElementById("deleteModal").classList.add("flex");
        }

        function closeDeleteModal() {
            document.getElementById("deleteModal").classList.add("hidden");

            document.getElementById("deleteModal").classList.remove("flex");
        }
    </script>

@endsection
