@extends ("layouts.maintenance-layout")

@section ("title", "Transfer & History")

@section ("content")
    <div class="space-y-6">
        <!-- PAGE HEADER -->
        <div>
            <h1 class="text-4xl font-black text-slate-900">
                Transfer & History
            </h1>

            <p class="mt-1 text-slate-500">Track equipment movements and maintenance history.</p>
        </div>

        {{-- ===================================================== --}}
        {{-- TRANSFER DASHBOARD --}}
        {{-- ===================================================== --}}

        <div
            class="mb-6 mt-6 overflow-hidden rounded-lg border-y border-slate-300 bg-gray-100 shadow-sm"
        >
            <div
                class="grid grid-cols-1 divide-y divide-slate-200
                    md:grid-cols-2 md:divide-y-0
                    xl:grid-cols-[380px_1fr_1fr_1fr]"
            >

                {{-- ===================================================== --}}
                {{-- TOTAL TRANSFER RECORDS --}}
                {{-- ===================================================== --}}

                <div class="flex items-center justify-between px-8 py-6">

                    <div class="flex flex-col">

                        <p class="text-sm font-medium text-slate-500">
                            Total Transfer Records
                        </p>

                        <h2 class="mt-2 text-5xl font-medium text-slate-900">
                            {{ number_format($totalTransferRecords) }}
                        </h2>


                        {{-- ================================================= --}}
                        {{-- MONTHLY PERCENTAGE CHANGE --}}
                        {{-- ================================================= --}}

                        <p class="mt-3 text-sm">

                            @if ($transferMonthlyPercentage === null)

                                <span class="font-semibold text-emerald-500">
                                    New activity
                                </span>

                            @else

                                <span
                                    class="font-semibold
                                    {{
                                        $transferMonthlyPercentage > 0
                                            ? 'text-emerald-500'
                                            : (
                                                $transferMonthlyPercentage < 0
                                                    ? 'text-red-500'
                                                    : 'text-slate-500'
                                            )
                                    }}"
                                >

                                    {{
                                        $transferMonthlyPercentage > 0
                                            ? '+'
                                            : ''
                                    }}

                                    {{ number_format($transferMonthlyPercentage, 2) }}%

                                </span>

                            @endif


                            <span class="text-slate-500">
                                From last month
                            </span>

                        </p>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- REAL 12 MONTH TRANSFER TREND GRAPH --}}
                    {{-- ===================================================== --}}

                    @php

                        // =====================================================
                        // GET MONTHLY TRANSFER COUNTS
                        // =====================================================

                        $transferCounts =
                            $transferMonthlyTrend->pluck('count');


                        // =====================================================
                        // GET MAXIMUM COUNT
                        // =====================================================

                        $maxTransferCount =
                            max(
                                1,
                                $transferCounts->max()
                            );


                        // =====================================================
                        // NUMBER OF GRAPH INTERVALS
                        // =====================================================

                        $transferPointCount =
                            max(
                                1,
                                $transferMonthlyTrend->count() - 1
                            );


                        // =====================================================
                        // BUILD LINE GRAPH POINTS
                        // =====================================================

                        $transferPoints =
                            $transferMonthlyTrend

                                ->values()

                                ->map(function ($item, $index) use (
                                    $maxTransferCount,
                                    $transferPointCount
                                ) {

                                    $x =
                                        (
                                            $index
                                            / $transferPointCount
                                        )
                                        * 300;


                                    $y =
                                        90
                                        - (
                                            (
                                                $item['count']
                                                / $maxTransferCount
                                            )
                                            * 75
                                        );


                                    return
                                        round($x, 2)
                                        . ','
                                        . round($y, 2);

                                })

                                ->implode(' ');


                        // =====================================================
                        // BUILD AREA GRAPH POINTS
                        // =====================================================

                        $transferAreaPoints =
                            '0,100 '
                            . $transferPoints
                            . ' 300,100';

                    @endphp


                    <div class="ml-6 h-20 w-40 shrink-0">

                        <svg
                            viewBox="0 0 300 100"
                            class="h-full w-full"
                            fill="none"
                            aria-label="Transfer activity over the last 12 months"
                        >

                            {{-- ================================================= --}}
                            {{-- GRAPH AREA --}}
                            {{-- ================================================= --}}

                            <polygon
                                points="{{ $transferAreaPoints }}"
                                fill="#3b82f6"
                                fill-opacity=".08"
                            />


                            {{-- ================================================= --}}
                            {{-- GRAPH LINE --}}
                            {{-- ================================================= --}}

                            <polyline
                                points="{{ $transferPoints }}"
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
                {{-- TRANSFERRED THIS MONTH --}}
                {{-- ===================================================== --}}

                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%]
                            border-l border-slate-200 xl:block"
                    ></span>


                    <p class="text-md font-medium text-slate-600">
                        Transferred This Month
                    </p>


                    <h2 class="text-5xl font-medium text-slate-900">
                        {{ number_format($currentMonthTransfers) }}
                    </h2>


                    {{-- ================================================= --}}
                    {{-- MONTHLY PERCENTAGE CHANGE --}}
                    {{-- ================================================= --}}

                    <p class="text-base">

                        @if ($transferMonthlyPercentage === null)

                            <span class="font-semibold text-emerald-500">
                                New activity
                            </span>

                        @else

                            <span
                                class="font-semibold
                                {{
                                    $transferMonthlyPercentage > 0
                                        ? 'text-emerald-500'
                                        : (
                                            $transferMonthlyPercentage < 0
                                                ? 'text-red-500'
                                                : 'text-slate-500'
                                        )
                                }}"
                            >

                                {{
                                    $transferMonthlyPercentage > 0
                                        ? '+'
                                        : ''
                                }}

                                {{ number_format($transferMonthlyPercentage, 2) }}%

                            </span>

                        @endif


                        <span class="text-slate-500">
                            From last month
                        </span>

                    </p>

                </div>


                {{-- ===================================================== --}}
                {{-- EQUIPMENT TRANSFERRED --}}
                {{-- ===================================================== --}}

                


                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%]
                            border-l border-slate-200 xl:block"
                    ></span>


                    <p class="text-md font-medium text-slate-600">
                        Equipment Transferred
                    </p>


                    <h2 class="text-5xl font-medium text-slate-900">
                        {{ number_format($equipmentTransferred) }}
                    </h2>


                    {{-- ===================================================== --}}
                    {{-- EQUIPMENT TRANSFERRED PERCENTAGE --}}
                    {{-- ===================================================== --}}

                    <p class="text-base">

                        <span class="font-semibold text-emerald-500">

                            {{ number_format($equipmentTransferredPercentage, 2) }}%

                        </span>

                        <span class="text-slate-500">
                            of all equipment
                        </span>

                    </p>

                </div>


                {{-- ===================================================== --}}
                {{-- ROOMS INVOLVED --}}
                {{-- ===================================================== --}}

                


                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%]
                            border-l border-slate-200 xl:block"
                    ></span>


                    <p class="text-md font-medium text-slate-600">
                        Rooms Involved
                    </p>


                    <h2 class="text-5xl font-medium text-slate-900">
                        {{ number_format($roomsInvolved) }}
                    </h2>


                    {{-- ===================================================== --}}
                    {{-- ROOMS INVOLVED PERCENTAGE --}}
                    {{-- ===================================================== --}}

                    <p class="text-base">

                        <span class="font-semibold text-emerald-500">

                            {{ number_format($roomsInvolvedPercentage, 2) }}%

                        </span>

                        <span class="text-slate-500">
                            of all rooms
                        </span>

                    </p>

                </div>

            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- EQUIPMENT MANAGEMENT TABLE --}}
        {{-- ========================================================= --}}

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">

            {{-- ===================================================== --}}
            {{-- HEADER --}}
            {{-- ===================================================== --}}

            <div
                class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4
                    sm:flex-row sm:items-center sm:justify-between"
            >

                {{-- HEADER INFORMATION --}}
                <div class="flex items-center gap-3">

                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center
                            rounded-lg bg-slate-100 text-slate-600"
                    >
                        <i data-lucide="package-search" class="h-4 w-4"></i>
                    </div>


                    <div>

                        <h2 class="text-sm font-semibold text-slate-900">
                            Equipment Management
                        </h2>

                        <p class="mt-0.5 text-xs text-slate-400">
                            Manage equipment locations, maintenance, and movement history
                        </p>

                    </div>

                </div>


                {{-- TOTAL COUNT --}}
                <div
                    class="inline-flex w-fit items-center gap-2
                        rounded-lg border border-slate-200
                        bg-slate-50 px-3 py-2
                        text-xs font-medium text-slate-500"
                >
                    <i data-lucide="package" class="h-3.5 w-3.5"></i>

                    {{ $equipment->total() }} total
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
                            placeholder="Search equipment, asset tag, category, or room..."

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
                    {{-- ROOM FILTER --}}
                    {{-- ================================================= --}}

                    <div class="relative">

                        <select
                            name="room"

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
                                All Rooms
                            </option>

                            @foreach ($rooms as $room)

                                <option
                                    value="{{ $room->room_id }}"

                                    @selected(
                                        request('room')
                                        == $room->room_id
                                    )
                                >
                                    {{ $room->room_name }}
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
                    {{-- STATUS FILTER --}}
                    {{-- ================================================= --}}

                    <div class="relative">

                        <select
                            name="status"

                            class="h-10 min-w-[180px]
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
                                value="Active"
                                @selected(request('status') === 'Active')
                            >
                                Active
                            </option>

                            <option
                                value="Borrowed"
                                @selected(request('status') === 'Borrowed')
                            >
                                Borrowed
                            </option>

                            <option
                                value="Under Maintenance"
                                @selected(request('status') === 'Under Maintenance')
                            >
                                Under Maintenance
                            </option>

                            <option
                                value="Disposed"
                                @selected(request('status') === 'Disposed')
                            >
                                Disposed
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
                        || request()->filled('room')
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

                            <i data-lucide="x" class="h-4 w-4"></i>

                            Clear

                        </a>

                    @endif

                </div>

            </form>



            {{-- ===================================================== --}}
            {{-- TABLE --}}
            {{-- ===================================================== --}}

            <div class="overflow-x-auto">

                <table class="w-full min-w-[950px] text-left">

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
                                Current Room
                            </th>

                            <th class="px-5 py-3">
                                Status
                            </th>

                            <th class="w-16 px-5 py-3 text-center">
                                Actions
                            </th>

                        </tr>

                    </thead>



                    {{-- ================================================= --}}
                    {{-- TABLE BODY --}}
                    {{-- ================================================= --}}

                    <tbody class="divide-y divide-slate-100">

                        @forelse ($equipment as $item)

                            @php
                                /*
                                |--------------------------------------------------------------------------
                                | EQUIPMENT STATUS DESIGN
                                |--------------------------------------------------------------------------
                                |
                                | Change the values here if your database uses different
                                | inventory status names.
                                |
                                */

                                $inventoryStatus =
                                    $item->equipment_inventory_status ?? "Unknown";


                                $statusClass = match ($inventoryStatus) {
                                    "Active" =>
                                        "bg-emerald-50 text-emerald-700 ring-emerald-200",

                                    "Borrowed" =>
                                        "bg-blue-50 text-blue-700 ring-blue-200",

                                    "Under Maintenance" =>
                                        "bg-amber-50 text-amber-700 ring-amber-200",

                                    "Disposed" =>
                                        "bg-red-50 text-red-700 ring-red-200",

                                    default =>
                                        "bg-slate-100 text-slate-600 ring-slate-200",
                                };


                                $statusDotClass = match ($inventoryStatus) {
                                    "Active" =>
                                        "bg-emerald-500",

                                    "Borrowed" =>
                                        "bg-blue-500",

                                    "Under Maintenance" =>
                                        "bg-amber-500",

                                    "Disposed" =>
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

                                        {{-- EQUIPMENT ICON --}}
                                        <div
                                            class="flex h-9 w-9 shrink-0
                                                items-center justify-center
                                                rounded-lg border border-slate-200
                                                bg-white text-slate-400"
                                        >
                                            <i
                                                data-lucide="monitor-cog"
                                                class="h-4 w-4"
                                            ></i>
                                        </div>


                                        {{-- EQUIPMENT INFORMATION --}}
                                        <div class="min-w-0">

                                            <p
                                                class="max-w-[240px] truncate
                                                    text-sm font-semibold
                                                    text-slate-800"

                                                title="{{ $item->equipment_name }}"
                                            >
                                                {{ $item->equipment_name }}
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
                                            $item->equipment_category_name
                                                ?? "Uncategorized"
                                        }}
                                    </span>

                                </td>



                                {{-- ===================================== --}}
                                {{-- CURRENT ROOM --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    @if (!empty($item->room_name))

                                        <div class="flex items-center gap-2">

                                            <i
                                                data-lucide="map-pin"
                                                class="h-3.5 w-3.5 shrink-0
                                                    text-slate-400"
                                            ></i>


                                            <span
                                                class="max-w-[220px] truncate
                                                    text-xs font-medium
                                                    text-slate-600"

                                                title="{{ $item->room_name }}"
                                            >
                                                {{ $item->room_name }}
                                            </span>

                                        </div>

                                    @else

                                        <span class="text-xs text-slate-400">
                                            No room assigned
                                        </span>

                                    @endif

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


                                        {{ $inventoryStatus }}

                                    </span>

                                </td>



                                {{-- ===================================== --}}
                                {{-- ACTIONS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4 text-center">

                                    <div
                                        class="relative inline-block"
                                        x-data="{ open: false }"
                                    >

                                        {{-- ACTION MENU BUTTON --}}
                                        <button
                                            type="button"

                                            @click="open = !open"

                                            @click.outside="open = false"

                                            class="flex h-8 w-8 items-center
                                                justify-center rounded-lg
                                                text-slate-400 transition
                                                hover:bg-slate-200/70
                                                hover:text-slate-700"

                                            aria-label="Equipment actions"
                                        >
                                            <i
                                                data-lucide="ellipsis"
                                                class="h-4 w-4"
                                            ></i>
                                        </button>



                                        {{-- ================================= --}}
                                        {{-- ACTION DROPDOWN --}}
                                        {{-- ================================= --}}

                                        <div
                                            x-cloak

                                            x-show="open"

                                            x-transition.origin.top.right

                                            class="absolute right-0 top-10 z-50
                                                w-48 overflow-hidden rounded-xl
                                                border border-slate-200
                                                bg-white p-1.5 text-left
                                                shadow-lg shadow-slate-900/10"
                                        >

                                            {{-- ================================= --}}
                                            {{-- TRANSFER --}}
                                            {{-- ================================= --}}

                                            <button
                                                type="button"

                                                @click="
                                                    open = false;

                                                    openTransferModal(
                                                        @js($item->equipment_id),
                                                        @js($item->equipment_name),
                                                        @js($item->room_name)
                                                    );
                                                "

                                                class="flex w-full items-center gap-2.5
                                                    rounded-lg px-3 py-2
                                                    text-xs font-medium
                                                    text-slate-600 transition
                                                    hover:bg-slate-50
                                                    hover:text-slate-900"
                                            >
                                                <i
                                                    data-lucide="arrow-right-left"
                                                    class="h-3.5 w-3.5"
                                                ></i>

                                                Transfer equipment
                                            </button>



                                            {{-- ================================= --}}
                                            {{-- ADD MAINTENANCE --}}
                                            {{-- ================================= --}}

                                            <button
                                                type="button"

                                                @click="
                                                    open = false;

                                                    openMaintenanceModal(
                                                        @js($item->equipment_id),
                                                        @js($item->equipment_name)
                                                    );
                                                "

                                                class="flex w-full items-center gap-2.5
                                                    rounded-lg px-3 py-2
                                                    text-xs font-medium
                                                    text-slate-600 transition
                                                    hover:bg-slate-50
                                                    hover:text-slate-900"
                                            >
                                                <i
                                                    data-lucide="wrench"
                                                    class="h-3.5 w-3.5"
                                                ></i>

                                                Add maintenance
                                            </button>



                                            <div
                                                class="my-1 border-t border-slate-100"
                                            ></div>



                                            {{-- ================================= --}}
                                            {{-- MAINTENANCE HISTORY --}}
                                            {{-- ================================= --}}

                                            <button
                                                type="button"

                                                @click="
                                                    open = false;

                                                    openHistoryModal(
                                                        @js($item->equipment_id),
                                                        @js($item->equipment_name)
                                                    );
                                                "

                                                class="flex w-full items-center gap-2.5
                                                    rounded-lg px-3 py-2
                                                    text-xs font-medium
                                                    text-slate-600 transition
                                                    hover:bg-slate-50
                                                    hover:text-slate-900"
                                            >
                                                <i
                                                    data-lucide="history"
                                                    class="h-3.5 w-3.5"
                                                ></i>

                                                Maintenance history
                                            </button>



                                            {{-- ================================= --}}
                                            {{-- TRANSFER HISTORY --}}
                                            {{-- ================================= --}}

                                            <button
                                                type="button"

                                                @click="
                                                    open = false;

                                                    openTransferHistory(
                                                        @js($item->equipment_id),
                                                        @js($item->equipment_name)
                                                    );
                                                "

                                                class="flex w-full items-center gap-2.5
                                                    rounded-lg px-3 py-2
                                                    text-xs font-medium
                                                    text-slate-600 transition
                                                    hover:bg-slate-50
                                                    hover:text-slate-900"
                                            >
                                                <i
                                                    data-lucide="route"
                                                    class="h-3.5 w-3.5"
                                                ></i>

                                                Transfer logs
                                            </button>

                                        </div>

                                    </div>

                                </td>

                            </tr>



                        @empty

                            <tr>

                                <td
                                    colspan="5"
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
                                                data-lucide="package-search"
                                                class="h-5 w-5"
                                            ></i>
                                        </div>


                                        {{-- ================================================= --}}
                                        {{-- TITLE --}}
                                        {{-- ================================================= --}}

                                        <h3 class="mt-4 text-sm font-semibold text-slate-800">

                                            No equipment available

                                        </h3>


                                        {{-- ================================================= --}}
                                        {{-- DESCRIPTION --}}
                                        {{-- ================================================= --}}

                                        <p
                                            class="mt-1.5 max-w-xs text-xs leading-5
                                                text-slate-400"
                                        >

                                            Equipment added to the inventory will appear here
                                            for transfer and maintenance management.

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
            {{-- PLACE BELOW THE TABLE --}}
            {{-- KEEP INSIDE THE EQUIPMENT MANAGEMENT SECTION --}}
            {{-- ===================================================== --}}

            @if ($equipment->hasPages())

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
                            {{ $equipment->firstItem() }}
                        </span>

                        to

                        <span class="font-semibold text-slate-700">
                            {{ $equipment->lastItem() }}
                        </span>

                        of

                        <span class="font-semibold text-slate-700">
                            {{ $equipment->total() }}
                        </span>

                        equipment

                    </p>


                    {{-- ================================================= --}}
                    {{-- LARAVEL PAGINATION LINKS --}}
                    {{-- ================================================= --}}

                    <div>
                        {{ $equipment->links() }}
                    </div>

                </div>

            @endif

        </section>
    </div>

    <!-- ===================================================== -->
    <!-- TRANSFER MODAL -->
    <!-- ===================================================== -->

    <div
    id="transferModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- TRANSFER EQUIPMENT MODAL -->
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
                    Equipment Management
                </p>

                <h2
                    class="mt-1.5 text-lg font-semibold tracking-tight text-slate-950"
                >
                    Transfer equipment
                </h2>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeTransferModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- TRANSFER FORM -->
        <!-- ===================================== -->
        <form action="/maintenance/equipment/transfer" method="POST">
            @csrf

            <input
                type="hidden"
                id="transfer_equipment_id"
                name="equipment_id"
            />

            <!-- ===================================== -->
            <!-- MODAL CONTENT -->
            <!-- ===================================== -->
            <div class="border-y border-slate-100 px-6 py-5">

                <!-- ===================================== -->
                <!-- CURRENT EQUIPMENT INFORMATION -->
                <!-- ===================================== -->
                <div
                    class="mb-6 divide-y divide-slate-100 rounded-xl border border-slate-200 px-4"
                >
                    <!-- EQUIPMENT -->
                    <div
                        class="flex items-center justify-between gap-6 py-3.5"
                    >
                        <span class="shrink-0 text-sm text-slate-500">
                            Equipment
                        </span>

                        <span
                            id="transferEquipmentName"
                            class="min-w-0 truncate text-right text-sm font-medium text-slate-950"
                        ></span>
                    </div>

                    <!-- CURRENT ROOM -->
                    <div
                        class="flex items-center justify-between gap-6 py-3.5"
                    >
                        <span class="shrink-0 text-sm text-slate-500">
                            Current room
                        </span>

                        <span
                            id="transferCurrentRoom"
                            class="min-w-0 truncate text-right text-sm font-medium text-slate-800"
                        ></span>
                    </div>
                </div>

                <!-- ===================================== -->
                <!-- TRANSFER FIELDS -->
                <!-- ===================================== -->
                <div class="space-y-5">

                    <!-- TRANSFER TO -->
                    <div>
                        <label
                            for="transferRoom"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Transfer to
                        </label>

                        <select
                            id="transferRoom"
                            name="room_id"
                            required
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                            <option value="">
                                Select destination room
                            </option>

                            @foreach ($rooms as $room)
                                <option value="{{ $room->room_id }}">
                                    {{ $room->room_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- REMARKS -->
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <label
                                for="transferRemarks"
                                class="text-sm font-medium text-slate-700"
                            >
                                Remarks
                            </label>

                            <span class="text-xs text-slate-400">
                                Optional
                            </span>
                        </div>

                        <textarea
                            id="transferRemarks"
                            name="remarks"
                            rows="3"
                            placeholder="Add a note about this transfer"
                            class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        ></textarea>
                    </div>
                </div>
            </div>

            <div class="border-t border-dashed border-slate-500"></div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button
                    type="button"
                    onclick="closeTransferModal()"
                    class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-[rgba(0,55,199,0.85)] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[rgba(0,44,155,0.85)] focus:outline-none focus:ring-4 focus:ring-slate-200 active:bg-black"
                >
                    Transfer equipment
                </button>
            </div>
        </form>
    </div>
</div>

    <!-- ===================================================== -->
    <!-- HISTORY MODAL -->
    <!-- ===================================================== -->

    <div
    id="historyModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- MAINTENANCE HISTORY MODAL -->
    <!-- ===================================== -->
    <div
        class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div
            class="flex shrink-0 items-start justify-between border-b border-dashed border-slate-500 gap-6 px-6 pb-5 pt-6"
        >
            <div class="min-w-0">
                <p
                    class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                >
                    Equipment Records
                </p>

                <h2
                    id="historyEquipmentName"
                    class="mt-1.5 truncate text-lg font-semibold tracking-tight text-slate-950"
                >
                    Maintenance history
                </h2>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeHistoryModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- HISTORY CONTENT -->
        <!-- ===================================== -->
        <div class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100">
            <div class="p-6">
                <div id="historyContent">
                    <!-- ===================================== -->
                    <!-- EMPTY STATE -->
                    <!-- ===================================== -->
                    <div
                        class="flex min-h-[280px] flex-col items-center justify-center text-center"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                        >
                            <i
                                data-lucide="history"
                                class="h-4 w-4"
                            ></i>
                        </div>

                        <h3
                            class="mt-4 text-sm font-medium text-slate-900"
                        >
                            No maintenance history
                        </h3>

                        <p
                            class="mt-1.5 max-w-xs text-sm leading-6 text-slate-500"
                        >
                            Maintenance records for this equipment will appear
                            here.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-dashed border-slate-500"></div>

        <!-- ===================================== -->
        <!-- MODAL FOOTER -->
        <!-- ===================================== -->
        <div class="flex shrink-0 items-center justify-end px-6 py-4">
            <button
                type="button"
                onclick="closeHistoryModal()"
                class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
            >
                Close
            </button>
        </div>
    </div>
</div>

    <!-- ===================================================== -->
    <!-- ADD MAINTENANCE MODAL -->
    <!-- ===================================================== -->

    <div
    id="maintenanceModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- ADD MAINTENANCE RECORD MODAL -->
    <!-- ===================================== -->
    <div
        class="flex max-h-[70vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div
            class="flex shrink-0 items-start justify-between gap-6 px-6 pb-5 pt-6 border-b border-dashed border-slate-500"
        >
            <div class="min-w-0">
                <p
                    class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                >
                    Maintenance Record
                </p>

                <h2
                    class="mt-1.5 text-lg font-semibold tracking-tight text-slate-950"
                >
                    Add maintenance record
                </h2>

                <p
                    id="maintenanceEquipmentName"
                    class="mt-1 truncate text-sm text-slate-500"
                ></p>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeMaintenanceModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- MAINTENANCE FORM -->
        <!-- ===================================== -->
        <form
            action="/maintenance/equipment/history/store"
            method="POST"
            enctype="multipart/form-data"
            class="flex min-h-0 flex-1 flex-col"
        >
            @csrf

            <input
                type="hidden"
                id="maintenance_equipment_id"
                name="equipment_id"
            />

            <!-- ===================================== -->
            <!-- SCROLLABLE FORM CONTENT -->
            <!-- ===================================== -->
            <div
                class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100 px-6 py-5"
            >
                <div class="space-y-5">

                    <!-- ===================================== -->
                    <!-- STATUS -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="maintenanceStatus"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Status
                        </label>

                        <select
                            id="maintenanceStatus"
                            name="status"
                            required
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                            <option value="Pending">
                                Pending
                            </option>

                            <option value="Processing">
                                Processing
                            </option>

                            <option value="Resolved">
                                Resolved
                            </option>

                            <option value="For Replacement">
                                For Replacement
                            </option>
                        </select>
                    </div>

                    <!-- ===================================== -->
                    <!-- FINDINGS -->
                    <!-- ===================================== -->
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <label
                                for="maintenanceFindings"
                                class="text-sm font-medium text-slate-700"
                            >
                                Findings
                            </label>

                            <span class="text-xs text-slate-400">
                                Optional
                            </span>
                        </div>

                        <textarea
                            id="maintenanceFindings"
                            name="findings"
                            rows="3"
                            placeholder="Describe the issue or inspection findings"
                            class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        ></textarea>
                    </div>

                    <!-- ===================================== -->
                    <!-- REPAIR ACTION -->
                    <!-- ===================================== -->
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <label
                                for="maintenanceRepairAction"
                                class="text-sm font-medium text-slate-700"
                            >
                                Repair action
                            </label>

                            <span class="text-xs text-slate-400">
                                Optional
                            </span>
                        </div>

                        <textarea
                            id="maintenanceRepairAction"
                            name="repair_action"
                            rows="3"
                            placeholder="Describe the repair or action performed"
                            class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        ></textarea>
                    </div>

                    <!-- ===================================== -->
                    <!-- PROOF IMAGE -->
                    <!-- ===================================== -->
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <label
                                for="maintenanceProofImage"
                                class="text-sm font-medium text-slate-700"
                            >
                                Proof image
                            </label>

                            <span class="text-xs text-slate-400">
                                Optional
                            </span>
                        </div>

                        <label
                            for="maintenanceProofImage"
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-300 px-4 py-4 transition hover:border-slate-400 hover:bg-slate-50"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500"
                            >
                                <i
                                    data-lucide="image-plus"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-700">
                                    Upload an image
                                </p>

                                <p class="mt-0.5 text-xs text-slate-400">
                                    Select a photo from your device
                                </p>
                            </div>

                            <input
                                id="maintenanceProofImage"
                                type="file"
                                name="proof_image"
                                accept="image/*"
                                class="hidden"
                            />
                        </label>
                    </div>
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
                    onclick="closeMaintenanceModal()"
                    class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-[rgba(0,55,199,0.85)] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[rgba(0,44,155,0.85)] focus:outline-none focus:ring-4 focus:ring-slate-200 active:bg-black"
                >
                    Save record
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===================================================== --}}
{{-- TRANSFER HISTORY MODAL --}}
{{-- ===================================================== --}}

<div
    id="transferHistoryModal"
    class="fixed inset-0 z-50 hidden items-center justify-center
        bg-black/30 p-4 backdrop-blur-[2px]"
>
    <div
        class="flex max-h-[85vh] w-full max-w-4xl flex-col
            overflow-hidden rounded-2xl border border-black/5
            bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >

        {{-- ===================================== --}}
        {{-- MODAL HEADER --}}
        {{-- ===================================== --}}

        <div
            class="flex shrink-0 items-start justify-between gap-6
                border-b border-dashed border-slate-500
                px-6 pb-5 pt-6"
        >
            <div class="min-w-0">

                <p
                    class="text-[11px] font-medium uppercase
                        tracking-[0.16em] text-slate-400"
                >
                    Equipment Movement Records
                </p>

                <h2
                    id="transferHistoryEquipmentName"
                    class="mt-1.5 truncate text-lg font-semibold
                        tracking-tight text-slate-950"
                >
                    Transfer logs
                </h2>

            </div>


            <button
                type="button"
                onclick="closeTransferHistory()"

                class="flex h-8 w-8 shrink-0 items-center
                    justify-center rounded-full text-slate-400
                    transition hover:bg-slate-100
                    hover:text-slate-900"

                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>

        </div>


        {{-- ===================================== --}}
        {{-- MODAL CONTENT --}}
        {{-- ===================================== --}}

        <div class="min-h-0 flex-1 overflow-y-auto">

            <div
                id="transferHistoryContent"
                class="p-6"
            >

                {{-- CONTENT LOADED USING JAVASCRIPT --}}

            </div>

        </div>


        {{-- ===================================== --}}
        {{-- MODAL FOOTER --}}
        {{-- ===================================== --}}

        <div
            class="flex shrink-0 items-center justify-end
                border-t border-dashed border-slate-500
                px-6 py-4"
        >

            <button
                type="button"
                onclick="closeTransferHistory()"

                class="rounded-lg px-3.5 py-2.5
                    text-sm font-medium text-slate-600
                    transition hover:bg-slate-100
                    hover:text-slate-950"
            >
                Close
            </button>

        </div>

    </div>
</div>

    <script>
        async function openHistoryModal(id, name) {
            document.getElementById("historyEquipmentName").innerText =
                name + " History";

            document.getElementById("historyContent").innerHTML = `
        <div class="text-center py-10">
            Loading...
        </div>
    `;

            document.getElementById("historyModal").classList.remove("hidden");

            document.getElementById("historyModal").classList.add("flex");

            try {
                const response = await fetch(
                    "/maintenance/equipment/history/" + id,
                );

                const data = await response.json();

                if (data.length === 0) {
                    document.getElementById("historyContent").innerHTML = `
                <div class="text-center text-slate-500 py-10">
                    No maintenance history found.
                </div>
            `;

                    return;
                }

                let html = "";

                data.forEach((item) => {
                    html += `

                <div class="border-l-4 border-indigo-500 pl-4 mb-6">

                    <div class="font-semibold text-indigo-600">

                        <span
                        class="inline-block px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-semibold">

                        ${item.equipment_maintenance_status}

                        </span>

                    </div>

                    <div class="text-sm text-slate-500 mb-2">

                        ${item.equipment_maintenance_created_at ?? ""}

                    </div>

                    <div class="mb-2">

                        <strong>Findings:</strong><br>

                        ${item.equipment_maintenance_findings ?? "N/A"}

                    </div>

                    <div>

                            <strong>Repair Action:</strong><br>

                            ${item.equipment_maintenance_repair_action ?? "N/A"}

                        </div>

                        ${
                            item.equipment_maintenance_proof_image
                                ? `
                        <div class="mt-3">

                            <a
                                href="/storage/${item.equipment_maintenance_proof_image}"
                                target="_blank"
                                class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 font-medium">

                                📷 View Proof Image

                            </a>

                        </div>
                        `
                                : ""
                        }

                </div>

            `;
                });

                document.getElementById("historyContent").innerHTML = html;
            } catch (error) {
                document.getElementById("historyContent").innerHTML = `
            <div class="text-center text-red-500 py-10">
                Failed to load history.
            </div>
        `;
            }
        }

        function closeHistoryModal() {
            document.getElementById("historyModal").classList.add("hidden");

            document.getElementById("historyModal").classList.remove("flex");
        }

        function openTransferModal(id, name, room) {
            document.getElementById("transfer_equipment_id").value = id;

            document.getElementById("transferEquipmentName").innerText = name;

            document.getElementById("transferCurrentRoom").innerText = room;

            document.getElementById("transferModal").classList.remove("hidden");

            document.getElementById("transferModal").classList.add("flex");
        }

        function closeTransferModal() {
            document.getElementById("transferModal").classList.add("hidden");

            document.getElementById("transferModal").classList.remove("flex");
        }

        function openMaintenanceModal(id, name) {
            document.getElementById("maintenance_equipment_id").value = id;

            document.getElementById("maintenanceEquipmentName").innerText =
                name + " Maintenance";

            document
                .getElementById("maintenanceModal")
                .classList.remove("hidden");

            document.getElementById("maintenanceModal").classList.add("flex");
        }

        function closeMaintenanceModal() {
            document.getElementById("maintenanceModal").classList.add("hidden");

            document
                .getElementById("maintenanceModal")
                .classList.remove("flex");
        }

        // =====================================================
        // TRANSFER HISTORY
        // =====================================================

        async function openTransferHistory(id, name) {

            // =====================================
            // GET MODAL ELEMENTS
            // =====================================

            const modal =
                document.getElementById("transferHistoryModal");

            const title =
                document.getElementById("transferHistoryEquipmentName");

            const content =
                document.getElementById("transferHistoryContent");


            // =====================================
            // SET EQUIPMENT NAME
            // =====================================

            title.innerText = name + " Transfer Logs";


            // =====================================
            // SHOW LOADING STATE
            // =====================================

            content.innerHTML = `
                <div
                    class="flex min-h-[280px]
                        items-center justify-center
                        text-sm text-slate-500"
                >
                    Loading transfer logs...
                </div>
            `;


            // =====================================
            // OPEN MODAL
            // =====================================

            modal.classList.remove("hidden");

            modal.classList.add("flex");


            // =====================================
            // FETCH TRANSFER LOGS
            // =====================================

            try {

                const response = await fetch(
                    "/maintenance/equipment/transfers/" + id
                );

                // =====================================
                // CHECK HTTP RESPONSE
                // =====================================

                if (!response.ok) {

                    throw new Error(
                        "HTTP error: " + response.status
                    );

                }


                const data = await response.json();


                // =====================================
                // EMPTY STATE
                // =====================================

                if (data.length === 0) {

                    content.innerHTML = `

                        <div
                            class="flex min-h-[280px]
                                flex-col items-center
                                justify-center text-center"
                        >

                            <div
                                class="flex h-10 w-10
                                    items-center justify-center
                                    rounded-full bg-slate-100
                                    text-slate-400"
                            >
                                <i
                                    data-lucide="route"
                                    class="h-4 w-4"
                                ></i>
                            </div>


                            <h3
                                class="mt-4 text-sm font-medium
                                    text-slate-900"
                            >
                                No transfer logs
                            </h3>


                            <p
                                class="mt-1.5 max-w-xs
                                    text-sm leading-6
                                    text-slate-500"
                            >
                                Transfer records for this equipment
                                will appear here.
                            </p>

                        </div>
                    `;


                    // =====================================
                    // REFRESH LUCIDE ICONS
                    // =====================================

                    if (window.lucide) {
                        lucide.createIcons();
                    }


                    return;

                }


                // =====================================
                // BUILD TRANSFER LOGS
                // =====================================

                let html = `
                    <div class="space-y-3">
                `;


                data.forEach((item) => {

                    html += `

                        <div
                            class="rounded-xl border
                                border-slate-200 bg-white p-4"
                        >

                            <div
                                class="flex items-start
                                    justify-between gap-6"
                            >

                                <div class="min-w-0">

                                    <p
                                        class="text-sm font-semibold
                                            text-slate-900"
                                    >
                                        ${item.from_room_name ?? "No previous room"}

                                        <span
                                            class="mx-2 text-slate-400"
                                        >
                                            →
                                        </span>

                                        ${item.to_room_name ?? "Unknown room"}
                                    </p>


                                    <p
                                        class="mt-1 text-xs
                                            text-slate-400"
                                    >
                                        ${item.created_at ?? ""}
                                    </p>

                                </div>

                            </div>


                            ${
                                item.remarks
                                    ? `
                                        <div
                                            class="mt-4 border-t
                                                border-slate-100 pt-4"
                                        >

                                            <p
                                                class="text-xs font-medium
                                                    text-slate-500"
                                            >
                                                Remarks
                                            </p>

                                            <p
                                                class="mt-1 text-sm
                                                    leading-6 text-slate-700"
                                            >
                                                ${item.remarks}
                                            </p>

                                        </div>
                                    `
                                    : ""
                            }

                        </div>

                    `;

                });


                html += `
                    </div>
                `;


                content.innerHTML = html;


                // =====================================
                // REFRESH LUCIDE ICONS
                // =====================================

                if (window.lucide) {
                    lucide.createIcons();
                }


            } catch (error) {

                console.error(
                    "Transfer history error:",
                    error
                );


                content.innerHTML = `

                    <div
                        class="flex min-h-[280px]
                            flex-col items-center
                            justify-center text-center"
                    >

                        <h3
                            class="text-sm font-medium
                                text-red-600"
                        >
                            Failed to load transfer logs
                        </h3>


                        <p
                            class="mt-1 text-sm
                                text-slate-500"
                        >
                            Check your Laravel route,
                            controller, and browser console.
                        </p>

                    </div>

                `;

            }

        }


        // =====================================================
        // CLOSE TRANSFER HISTORY
        // =====================================================

        function closeTransferHistory() {

            const modal =
                document.getElementById("transferHistoryModal");

            modal.classList.add("hidden");

            modal.classList.remove("flex");

        }
    </script>

@endsection
