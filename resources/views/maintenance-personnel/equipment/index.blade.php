@extends ("layouts.maintenance-layout")

@section ("title", "Equipment Inventory")

@section ("content")
    <div class="space-y-6">
        <!-- PAGE HEADER -->
        <div class="flex justify-end">
            <button
                type="button"
                onclick="openAddEquipmentModal()"
                class="inline-flex items-center gap-2 rounded-xl bg-[rgba(0,55,199,0.85)] px-4 py-3 font-semibold font-sans-serif text-sm text-white transition hover:bg-[rgba(0,44,155,0.85)]"
            >
                <i data-lucide="plus" class="w-4 h-4"></i>

                Add Equipment
            </button>
        </div>

        <!-- DASHBOARD CARDS -->
        <!-- ========================================================= -->
        <!-- DASHBOARD STATS -->
        <!-- ========================================================= -->
        {{-- ===================================================== --}}
        {{-- EQUIPMENT INVENTORY DASHBOARD --}}
        {{-- ===================================================== --}}

        <div
            class="overflow-hidden rounded-lg border-y border-slate-300 bg-gray-100 shadow-sm"
        >
            <div
                class="grid grid-cols-1 divide-y divide-slate-200
                    md:grid-cols-2 md:divide-y-0
                    xl:grid-cols-[380px_1fr_1fr_1fr]"
            >

                {{-- ===================================================== --}}
                {{-- TOTAL EQUIPMENT --}}
                {{-- ===================================================== --}}

                <div class="flex items-center justify-between px-8 py-6">

                    {{-- ================================================= --}}
                    {{-- LEFT CONTENT --}}
                    {{-- ================================================= --}}

                    <div class="flex flex-col">

                        <p class="text-sm font-medium text-slate-500">
                            Total Equipment
                        </p>


                        <h2 class="mt-2 text-5xl font-medium text-slate-900">

                            {{ number_format($totalEquipment) }}

                        </h2>


                        {{-- ================================================= --}}
                        {{-- MONTHLY REGISTRATION PERCENTAGE CHANGE --}}
                        {{-- ================================================= --}}

                        <p class="mt-3 text-sm">

                            @if ($equipmentMonthlyPercentage === null)

                                {{-- ============================================= --}}
                                {{-- PREVIOUS MONTH = 0 --}}
                                {{-- CURRENT MONTH HAS NEW EQUIPMENT --}}
                                {{-- ============================================= --}}

                                <span class="font-semibold text-emerald-600">
                                    New activity
                                </span>

                            @else

                                <span
                                    class="font-semibold
                                        {{
                                            $equipmentMonthlyPercentage > 0
                                                ? 'text-emerald-600'
                                                : (
                                                    $equipmentMonthlyPercentage < 0
                                                        ? 'text-red-600'
                                                        : 'text-slate-500'
                                                )
                                        }}"
                                >

                                    {{
                                        $equipmentMonthlyPercentage > 0
                                            ? '+'
                                            : ''
                                    }}{{ number_format($equipmentMonthlyPercentage, 2) }}%

                                </span>

                            @endif


                            <span class="text-slate-500">
                                From last month
                            </span>

                        </p>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- REAL 12 MONTH EQUIPMENT REGISTRATION TREND --}}
                    {{-- ===================================================== --}}

                    @php

                        // =====================================================
                        // GET REAL MONTHLY REGISTRATION COUNTS
                        // =====================================================

                        $equipmentTrendCounts =
                            $equipmentMonthlyTrend->pluck('count');


                        // =====================================================
                        // GRAPH DIMENSIONS
                        // =====================================================

                        $equipmentGraphWidth = 300;

                        $equipmentGraphHeight = 100;

                        $equipmentGraphTopPadding = 10;

                        $equipmentGraphBottomPadding = 10;


                        // =====================================================
                        // HIGHEST MONTHLY REGISTRATION COUNT
                        // =====================================================

                        $maxEquipmentTrendCount =
                            max(
                                1,
                                $equipmentTrendCounts->max()
                            );


                        // =====================================================
                        // NUMBER OF GRAPH POINTS
                        // =====================================================

                        $equipmentTrendPointCount =
                            max(
                                1,
                                $equipmentMonthlyTrend->count() - 1
                            );


                        // =====================================================
                        // BUILD LINE GRAPH POINTS
                        // =====================================================

                        $equipmentTrendPoints =
                            $equipmentMonthlyTrend

                                ->values()

                                ->map(function (
                                    $item,
                                    $index
                                ) use (
                                    $equipmentGraphWidth,
                                    $equipmentGraphHeight,
                                    $equipmentGraphTopPadding,
                                    $equipmentGraphBottomPadding,
                                    $maxEquipmentTrendCount,
                                    $equipmentTrendPointCount
                                ) {

                                    // =========================================
                                    // X POSITION
                                    // =========================================

                                    $x =
                                        (
                                            $index
                                            / $equipmentTrendPointCount
                                        )
                                        * $equipmentGraphWidth;


                                    // =========================================
                                    // AVAILABLE GRAPH HEIGHT
                                    // =========================================

                                    $usableHeight =
                                        $equipmentGraphHeight
                                        - $equipmentGraphTopPadding
                                        - $equipmentGraphBottomPadding;


                                    // =========================================
                                    // Y POSITION
                                    // =========================================

                                    $y =
                                        $equipmentGraphHeight
                                        - $equipmentGraphBottomPadding
                                        - (
                                            (
                                                $item['count']
                                                / $maxEquipmentTrendCount
                                            )
                                            * $usableHeight
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

                        $equipmentTrendAreaPoints =
                            '0,100 '
                            . $equipmentTrendPoints
                            . ' 300,100';

                    @endphp


                    <div class="ml-6 h-20 w-40 shrink-0">

                        <svg
                            viewBox="0 0 300 100"
                            class="h-full w-full"
                            fill="none"
                            aria-label="Equipment registrations over the last 12 months"
                        >

                            {{-- ================================================= --}}
                            {{-- GRAPH AREA --}}
                            {{-- ================================================= --}}

                            <polygon
                                points="{{ $equipmentTrendAreaPoints }}"
                                fill="currentColor"
                                fill-opacity=".08"
                                class="text-slate-900"
                            />


                            {{-- ================================================= --}}
                            {{-- GRAPH LINE --}}
                            {{-- ================================================= --}}

                            <polyline
                                points="{{ $equipmentTrendPoints }}"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="text-slate-900"
                            />

                        </svg>

                    </div>

                </div>


                {{-- ===================================================== --}}
                {{-- ACTIVE EQUIPMENT --}}
                {{-- ===================================================== --}}

                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%]
                            border-l border-slate-200 xl:block"
                    ></span>


                    <p class="text-md font-medium text-slate-600">
                        Active
                    </p>


                    <h2 class="text-5xl font-medium text-slate-900">

                        {{ number_format($activeEquipment) }}

                    </h2>


                    <p class="text-base">

                        <span class="font-semibold text-emerald-600">

                            {{
                                number_format(
                                    $activeEquipmentPercentage,
                                    2
                                )
                            }}%

                        </span>

                        <span class="text-slate-500">
                            of all equipment
                        </span>

                    </p>

                </div>


                {{-- ===================================================== --}}
                {{-- UNDER MAINTENANCE EQUIPMENT --}}
                {{-- ===================================================== --}}

                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%]
                            border-l border-slate-200 xl:block"
                    ></span>


                    <p class="text-md font-medium text-slate-600">
                        Under Maintenance
                    </p>


                    <h2 class="text-5xl font-medium text-slate-900">

                        {{ number_format($underMaintenanceEquipment) }}

                    </h2>


                    <p class="text-base">

                        <span class="font-semibold text-amber-600">

                            {{
                                number_format(
                                    $underMaintenanceEquipmentPercentage,
                                    2
                                )
                            }}%

                        </span>

                        <span class="text-slate-500">
                            of all equipment
                        </span>

                    </p>

                </div>


                {{-- ===================================================== --}}
                {{-- DISPOSED EQUIPMENT --}}
                {{-- ===================================================== --}}

                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%]
                            border-l border-slate-200 xl:block"
                    ></span>


                    <p class="text-md font-medium text-slate-600">
                        Disposed
                    </p>


                    <h2 class="text-5xl font-medium text-slate-900">

                        {{ number_format($disposedEquipment) }}

                    </h2>


                    <p class="text-base">

                        <span class="font-semibold text-slate-600">

                            {{
                                number_format(
                                    $disposedEquipmentPercentage,
                                    2
                                )
                            }}%

                        </span>

                        <span class="text-slate-500">
                            of all equipment
                        </span>

                    </p>

                </div>

            </div>
        </div>

        <!-- FILTER SECTION 
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <form method="GET">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
                    <div class="lg:col-span-2">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search equipment..."
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <select
                            name="category"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black"
                        >
                            <option value="">All Categories</option>

                            @foreach ($categories as $category)
                                <option
                                    value="{{ $category->equipment_category_id }}"
                                    {{
                                        request("category") ==
                                        $category->equipment_category_id
                                            ? "selected"
                                            : ""
                                    }}
                                >
                                    {{ $category->equipment_category_name }}
                                </option>

                            @endforeach
                        </select>
                    </div>

                    <div>
                        <select
                            name="room"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black"
                        >
                            <option value="">All Rooms</option>

                            @foreach ($rooms as $room)
                                <option
                                    value="{{ $room->room_id }}"
                                    {{
                                        request("room") == $room->room_id
                                            ? "selected"
                                            : ""
                                    }}
                                >
                                    {{ $room->room_name }}
                                </option>

                            @endforeach
                        </select>
                    </div>

                    <div>
                        <select
                            name="status"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black"
                        >
                            <option value="">All Status</option>

                            <option
                                value="Active"
                                {{
                                    request("status") == "Active"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                Active
                            </option>

                            <option
                                value="Under Maintenance"
                                {{
                                    request("status") == "Under Maintenance"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                Under Maintenance
                            </option>

                            <option
                                value="Borrowed"
                                {{
                                    request("status") == "Borrowed"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                Borrowed
                            </option>

                            <option
                                value="For Replacement"
                                {{
                                    request("status") == "For Replacement"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                For Replacement
                            </option>

                            <option
                                value="Disposed"
                                {{
                                    request("status") == "Disposed"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                Disposed
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button
                        type="submit"
                        class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700"
                    >
                        Search
                    </button>
                </div>
            </form>
        </div>-->

        <!-- EQUIPMENT TABLE -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
        >
            <div class="overflow-x-auto">

                {{-- ===================================================== --}}
                {{-- TABLE TOOLBAR --}}
                {{-- STATUS TABS SHRINK AND SCROLL --}}
                {{-- FILTERS KEEP THEIR SPACE --}}
                {{-- ===================================================== --}}

                <div
                    class="flex flex-col gap-3 border-b border-slate-200
                        bg-white px-5 py-4
                        xl:flex-row xl:items-center"
                >

                    {{-- ================================================= --}}
                    {{-- LEFT SIDE --}}
                    {{-- STATUS TABS --}}
                    {{-- TAKES REMAINING AVAILABLE SPACE --}}
                    {{-- ================================================= --}}

                    <div class="min-w-0 flex-1">

                        <div
                            class="flex items-center gap-1
                                overflow-x-auto whitespace-nowrap
                                [scrollbar-width:none]
                                [&::-webkit-scrollbar]:hidden"
                        >

                            {{-- ALL --}}

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'status' => null,
                                    'page' => null,
                                ]) }}"

                                class="shrink-0 rounded-lg px-3 py-2
                                    text-sm transition
                                    {{
                                        !request()->filled('status')
                                            ? 'bg-slate-900 font-medium text-white'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                All
                            </a>


                            {{-- ACTIVE --}}

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'status' => 'Active',
                                    'page' => null,
                                ]) }}"

                                class="shrink-0 rounded-lg px-3 py-2
                                    text-sm transition
                                    {{
                                        request('status') === 'Active'
                                            ? 'bg-slate-900 font-medium text-white'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                Active
                            </a>


                            {{-- MAINTENANCE --}}

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'status' => 'Under Maintenance',
                                    'page' => null,
                                ]) }}"

                                class="shrink-0 rounded-lg px-3 py-2
                                    text-sm transition
                                    {{
                                        request('status') === 'Under Maintenance'
                                            ? 'bg-slate-900 font-medium text-white'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                Maintenance
                            </a>


                            {{-- BORROWED --}}

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'status' => 'Borrowed',
                                    'page' => null,
                                ]) }}"

                                class="shrink-0 rounded-lg px-3 py-2
                                    text-sm transition
                                    {{
                                        request('status') === 'Borrowed'
                                            ? 'bg-slate-900 font-medium text-white'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                Borrowed
                            </a>


                            {{-- DISPOSED --}}

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'status' => 'Disposed',
                                    'page' => null,
                                ]) }}"

                                class="shrink-0 rounded-lg px-3 py-2
                                    text-sm transition
                                    {{
                                        request('status') === 'Disposed'
                                            ? 'bg-slate-900 font-medium text-white'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                Disposed
                            </a>

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- RIGHT SIDE --}}
                    {{-- SEARCH AND FILTERS --}}
                    {{-- DOES NOT WRAP ON XL SCREENS --}}
                    {{-- ================================================= --}}

                    <form
                        method="GET"
                        action="{{ url()->current() }}"

                        class="flex shrink-0 flex-wrap items-center gap-2
                            xl:flex-nowrap"
                    >

                        {{-- ================================================= --}}
                        {{-- PRESERVE STATUS --}}
                        {{-- ================================================= --}}

                        @if (request()->filled('status'))

                            <input
                                type="hidden"
                                name="status"
                                value="{{ request('status') }}"
                            >

                        @endif


                        {{-- ================================================= --}}
                        {{-- SEARCH --}}
                        {{-- ================================================= --}}

                        <div class="relative">

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

                                placeholder="Search equipment..."

                                class="h-10 w-48 rounded-lg
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
                        {{-- CATEGORY --}}
                        {{-- ================================================= --}}

                        <div class="relative">

                            <select
                                name="category"

                                class="h-10 w-40 appearance-none
                                    rounded-lg border border-slate-200
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
                        {{-- ROOM --}}
                        {{-- ================================================= --}}

                        <div class="relative">

                            <select
                                name="room"

                                class="h-10 w-36 appearance-none
                                    rounded-lg border border-slate-200
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
                                            request('room') == $room->room_id
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
                        {{-- APPLY --}}
                        {{-- ================================================= --}}

                        <button
                            type="submit"

                            class="inline-flex h-10 shrink-0
                                items-center justify-center gap-2
                                rounded-lg bg-slate-950 px-4
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
                        )

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'search' => null,
                                    'category' => null,
                                    'room' => null,
                                    'page' => null,
                                ]) }}"

                                class="inline-flex h-10 w-10 shrink-0
                                    items-center justify-center
                                    rounded-lg border border-slate-200
                                    bg-white text-slate-500
                                    transition
                                    hover:bg-slate-50
                                    hover:text-slate-900"

                                data-tooltip="Clear filters"
                            >

                                <i
                                    data-lucide="x"
                                    class="h-4 w-4"
                                ></i>

                            </a>

                        @endif

                    </form>

                </div>



                

                <table class="w-full">
                    <thead class="border-b border-slate-200 bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black">
                                Equipment
                            </th>

                            <th class="px-4 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black">
                                Brand
                            </th>

                            <th class="px-4 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black">
                                Category
                            </th>

                            <th class="px-4 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black">
                                Room
                            </th>

                            <th class="px-4 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black">
                                Qty
                            </th>

                            <th class="px-4 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black">
                                Condition
                            </th>

                            <th class="px-4 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black">
                                Status
                            </th>

                            <th class="w-32 px-4 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($equipment as $item)
                            @php
                                $conditionClass = match ($item->equipment_condition_status ?? "") {
                                    "Good" => "bg-emerald-50 text-emerald-700",
                                    "Fair" => "bg-sky-50 text-sky-700",
                                    "Damaged" => "bg-amber-50 text-amber-700",
                                    "Critical" => "bg-rose-50 text-rose-700",
                                    default => "bg-slate-100 text-slate-600",
                                };

                                $inventoryClass = match ($item->equipment_inventory_status ?? "") {
                                    "Active" => "bg-emerald-50 text-emerald-700",
                                    "Borrowed" => "bg-sky-50 text-sky-700",
                                    "Under Maintenance" => "bg-amber-50 text-amber-700",
                                    "For Replacement" => "bg-orange-50 text-orange-700",
                                    "Disposed" => "bg-rose-50 text-rose-700",
                                    default => "bg-slate-100 text-slate-600",
                                };
                            @endphp
                            <tr class="border-b border-slate-100 transition duration-200 hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-900">
                                            {{ $item->equipment_name }}
                                        </span>

                                        <span class="mt-1 text-xs text-slate-400">
                                            {{ $item->equipment_asset_tag ?? "No Asset Tag" }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"
                                    >
                                        {{ $item->equipment_brand_name }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs text-slate-700"
                                    >
                                        {{ $item->equipment_category_name }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-sm text-slate-700">
                                    {{ $item->room_name }}
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="inline-flex h-8 min-w-[32px] items-center justify-center rounded-lg bg-slate-100 px-2 text-sm font-semibold text-slate-800"
                                    >
                                        {{ $item->equipment_quantity }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $conditionClass }}">
                                        {{ $item->equipment_condition_status }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $inventoryClass }}">
                                        {{ $item->equipment_inventory_status }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex justify-center gap-2">

                                        <button
                                            type="button"
                                            onclick="openEquipmentModal(

                                                '{{ $item->equipment_asset_tag ?? 'N/A' }}',

                                                '{{ $item->equipment_name }}',

                                                '{{ $item->equipment_brand_name ?? 'N/A' }}',

                                                '{{ $item->equipment_model ?? 'N/A' }}',

                                                '{{ $item->equipment_serial_number ?? 'N/A' }}',

                                                '{{ $item->equipment_category_name }}',

                                                '{{ $item->room_name }}',

                                                '{{ $item->equipment_quantity }}',

                                                '{{ $item->equipment_condition_status }}',

                                                '{{ $item->equipment_inventory_status }}',

                                                '{{ $item->equipment_is_borrowable }}',

                                                '{{ $item->equipment_purchase_date ?? 'N/A' }}',

                                                '{{ $item->equipment_warranty_expiration ?? 'N/A' }}',

                                                '{{ $item->equipment_is_borrowable ? 'Yes' : 'No' }}'

                                            )"
                                            class="flex h-9 items-center justify-center gap-x-1.5 rounded-lg  bg-slate-100 px-3 text-xs  text-slate-800 transition shadow-sm hover:bg-slate-200 hover:text-gray-600"
                                            data-tooltip="View equipment"
                                            aria-label="View equipment"
                                        >
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </button>

                                        <button
                                            type="button"
                                            onclick="openEditEquipmentModal(

                                                '{{ $item->equipment_id }}',

                                                '{{ $item->equipment_category_id }}',

                                                '{{ $item->equipment_room_id }}',

                                                '{{ $item->equipment_asset_tag }}',

                                                '{{ $item->equipment_name }}',

                                                '{{ $item->equipment_brand_name }}',

                                                '{{ $item->equipment_model }}',

                                                '{{ $item->equipment_serial_number }}',

                                                '{{ $item->equipment_quantity }}',

                                                '{{ $item->equipment_condition_status }}',

                                                '{{ $item->equipment_inventory_status }}',

                                                '{{ $item->equipment_is_borrowable }}'

                                            )"
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#FFF200] text-black transition hover:bg-[#E6E600]"
                                            data-tooltip="Edit equipment"
                                            aria-label="Edit equipment"
                                        >
                                            <i data-lucide="edit-3" class="h-4 w-4"></i>
                                        </button>

                                    </div>
                                </td>
                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="9"
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
                                                    || request()->filled('room')
                                                    || request()->filled('status')
                                                        ? 'search-x'
                                                        : 'package-open'
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
                                                || request()->filled('room')
                                                || request()->filled('status')

                                                    ? 'No matching equipment'

                                                    : 'No equipment yet'
                                            }}

                                        </h3>


                                        {{-- ================================================= --}}
                                        {{-- DESCRIPTION --}}
                                        {{-- ================================================= --}}

                                        <p
                                            class="mt-1.5 max-w-xs text-xs leading-5
                                                text-slate-400"
                                        >

                                            {{
                                                request()->filled('search')
                                                || request()->filled('category')
                                                || request()->filled('room')
                                                || request()->filled('status')

                                                    ? 'No equipment matches your current search or filters. Try adjusting them.'

                                                    : 'Equipment added to the inventory will appear here.'
                                            }}

                                        </p>


                                        {{-- ================================================= --}}
                                        {{-- CLEAR FILTERS --}}
                                        {{-- ONLY SHOW WHEN FILTERING --}}
                                        {{-- ================================================= --}}

                                        @if (
                                            request()->filled('search')
                                            || request()->filled('category')
                                            || request()->filled('room')
                                            || request()->filled('status')
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
            {{-- PLACE INSIDE EQUIPMENT TABLE CARD --}}
            {{-- DIRECTLY BELOW TABLE CONTAINER --}}
            {{-- ===================================================== --}}

            @if ($equipment->hasPages())

                <div
                    class="flex flex-col gap-3
                        border-t border-slate-200
                        px-5 py-4
                        sm:flex-row
                        sm:items-center
                        sm:justify-between"
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
                    {{-- PAGINATION LINKS --}}
                    {{-- ================================================= --}}

                    <div>
                        {{ $equipment->links() }}
                    </div>

                </div>

            @endif
        </div>

        
    </div>

    @php
        $eqField = 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
        $eqLabel = 'mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500';
    @endphp

        <div
        id="viewEquipmentModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div class="flex w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
            <div class="flex items-start justify-between gap-4 px-6 pt-6">
                <div class="min-w-0">
                    <p id="modal_name" class="truncate text-xl font-semibold tracking-tight text-slate-900"></p>
                    <p class="mt-1 truncate text-sm text-slate-500">
                        <span id="modal_category"></span>
                        <span class="mx-1.5 text-slate-300">·</span>
                        <span id="modal_room"></span>
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span id="modal_condition" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"></span>
                        <span id="modal_status" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"></span>
                    </div>
                </div>
                <button type="button" onclick="closeEquipmentModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3 px-6">
                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Quantity</p>
                    <p id="modal_quantity" class="mt-1 text-lg font-semibold text-slate-900"></p>
                </div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Borrowable</p>
                    <p id="modal_borrowable" class="mt-1 text-lg font-semibold text-slate-900"></p>
                </div>
            </div>

            <div class="mt-4 space-y-2.5 px-6 pb-2 text-sm">
                <div class="flex justify-between gap-4"><span class="text-slate-400">Asset tag</span><span id="modal_asset_tag" class="text-right font-medium text-slate-800"></span></div>
                <div class="flex justify-between gap-4"><span class="text-slate-400">Brand</span><span id="modal_brand" class="text-right font-medium text-slate-800"></span></div>
                <div class="flex justify-between gap-4"><span class="text-slate-400">Model</span><span id="modal_model" class="text-right font-medium text-slate-800"></span></div>
                <div class="flex justify-between gap-4"><span class="text-slate-400">Serial</span><span id="modal_serial" class="text-right font-medium text-slate-800"></span></div>
                <div class="flex justify-between gap-4"><span class="text-slate-400">Purchased</span><span id="modal_purchase_date" class="text-right font-medium text-slate-800"></span></div>
                <div class="flex justify-between gap-4"><span class="text-slate-400">Warranty</span><span id="modal_warranty" class="text-right font-medium text-slate-800"></span></div>
            </div>

            <div class="flex justify-end px-6 py-4">
                <button type="button" onclick="closeEquipmentModal()" class="h-10 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white">Close</button>
            </div>
        </div>
    </div>
<div
        id="addEquipmentModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <form action="/maintenance/equipment/store" method="POST" class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
            @csrf
            <div class="flex items-start justify-between px-6 pt-6">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">Add equipment</h2>
                    <p class="mt-1 text-sm text-slate-500">Identity on the left, status on the right.</p>
                </div>
                <button type="button" onclick="closeAddEquipmentModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">What & where</p>
                        <div>
                            <label for="add_equipment_name" class="{{ $eqLabel }}">Equipment name <span class="text-rose-500">*</span></label>
                            <input id="add_equipment_name" type="text" name="equipment_name" required placeholder="e.g. Mouse" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="add_equipment_category" class="{{ $eqLabel }}">Category <span class="text-rose-500">*</span></label>
                            <select id="add_equipment_category" name="equipment_category_id" required class="{{ $eqField }}">
                                <option value="">Select category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->equipment_category_id }}">{{ $category->equipment_category_name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-xs text-slate-400">Filled from the equipment name. You can still choose another category.</p>
                        </div>
                        <div>
                            <label for="add_equipment_room" class="{{ $eqLabel }}">Room <span class="text-rose-500">*</span></label>
                            <select id="add_equipment_room" name="equipment_room_id" required class="{{ $eqField }}">
                                <option value="">Select room</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->room_id }}">{{ $room->room_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="add_equipment_quantity" class="{{ $eqLabel }}">Qty</label>
                                <input id="add_equipment_quantity" type="number" min="1" value="1" name="equipment_quantity" class="{{ $eqField }}" />
                            </div>
                            <div>
                                <label for="add_equipment_condition" class="{{ $eqLabel }}">Condition</label>
                                <select id="add_equipment_condition" name="equipment_condition_status" class="{{ $eqField }}">
                                    <option value="Good">Good</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="Under Maintenance">Under maintenance</option>
                                    <option value="Disposed">Disposed</option>
                                </select>
                            </div>
                        </div>
                        <label class="flex items-center justify-between rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-200/80">
                            <span class="text-sm font-medium text-slate-900">Can be borrowed</span>
                            <input id="add_equipment_borrowable" type="checkbox" name="equipment_is_borrowable" value="1" class="peer sr-only">
                            <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-slate-900 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5"></span>
                        </label>
                    </div>
                </div>
                <details class="mt-5 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80">
                    <summary class="cursor-pointer text-sm font-medium text-slate-700">More details</summary>
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="add_equipment_asset_tag" class="{{ $eqLabel }}">Asset tag</label>
                            <input id="add_equipment_asset_tag" type="text" name="equipment_asset_tag" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="add_equipment_brand" class="{{ $eqLabel }}">Brand name</label>
                            <input id="add_equipment_brand" type="text" name="equipment_brand_name" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="add_equipment_model" class="{{ $eqLabel }}">Model</label>
                            <input id="add_equipment_model" type="text" name="equipment_model" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="add_equipment_serial" class="{{ $eqLabel }}">Serial number</label>
                            <input id="add_equipment_serial" type="text" name="equipment_serial_number" class="{{ $eqField }}" />
                        </div>
                    </div>
                </details>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button type="button" onclick="closeAddEquipmentModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                <button type="submit" class="h-10 rounded-xl bg-slate-900 px-5 text-sm font-medium text-white transition hover:bg-slate-800">Add equipment</button>
            </div>
        </form>
    </div>

    <div
        id="editEquipmentModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <form id="editEquipmentForm" method="POST" class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
            @csrf
            <div class="flex items-start justify-between px-6 pt-6">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">Edit equipment</h2>
                    <p class="mt-1 text-sm text-slate-500">Identity on the left, status on the right.</p>
                </div>
                <button type="button" onclick="closeEditEquipmentModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">What & where</p>
                        <div>
                            <label for="edit_equipment_name" class="{{ $eqLabel }}">Equipment name <span class="text-rose-500">*</span></label>
                            <input id="edit_equipment_name" type="text" name="equipment_name" required class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_category" class="{{ $eqLabel }}">Category <span class="text-rose-500">*</span></label>
                            <select id="edit_category" name="equipment_category_id" required class="{{ $eqField }}">
                                <option value="">Select category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->equipment_category_id }}">{{ $category->equipment_category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="edit_room" class="{{ $eqLabel }}">Room <span class="text-rose-500">*</span></label>
                            <select id="edit_room" name="equipment_room_id" required class="{{ $eqField }}">
                                <option value="">Select room</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->room_id }}">{{ $room->room_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="edit_quantity" class="{{ $eqLabel }}">Qty</label>
                                <input id="edit_quantity" type="number" min="1" name="equipment_quantity" class="{{ $eqField }}" />
                            </div>
                            <div>
                                <label for="edit_condition" class="{{ $eqLabel }}">Condition</label>
                                <select id="edit_condition" name="equipment_condition_status" class="{{ $eqField }}">
                                    <option value="Good">Good</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="Under Maintenance">Under maintenance</option>
                                    <option value="Disposed">Disposed</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="edit_status" class="{{ $eqLabel }}">Inventory status</label>
                            <select id="edit_status" name="equipment_inventory_status" class="{{ $eqField }}">
                                <option value="Active">Active</option>
                                <option value="Under Maintenance">Under maintenance</option>
                                <option value="Borrowed">Borrowed</option>
                                <option value="For Replacement">For replacement</option>
                                <option value="Disposed">Disposed</option>
                            </select>
                        </div>
                        <label class="flex items-center justify-between rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-200/80">
                            <span class="text-sm font-medium text-slate-900">Can be borrowed</span>
                            <input id="edit_equipment_borrowable" type="checkbox" name="equipment_is_borrowable" value="1" class="peer sr-only">
                            <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-slate-900 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5"></span>
                        </label>
                    </div>
                </div>
                <details class="mt-5 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80">
                    <summary class="cursor-pointer text-sm font-medium text-slate-700">More details</summary>
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="edit_asset_tag" class="{{ $eqLabel }}">Asset tag</label>
                            <input id="edit_asset_tag" type="text" name="equipment_asset_tag" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_brand" class="{{ $eqLabel }}">Brand name</label>
                            <input id="edit_brand" type="text" name="equipment_brand_name" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_model" class="{{ $eqLabel }}">Model</label>
                            <input id="edit_model" type="text" name="equipment_model" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_serial" class="{{ $eqLabel }}">Serial number</label>
                            <input id="edit_serial" type="text" name="equipment_serial_number" class="{{ $eqField }}" />
                        </div>
                    </div>
                </details>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button type="button" onclick="closeEditEquipmentModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                <button type="submit" class="h-10 rounded-xl bg-slate-900 px-5 text-sm font-medium text-white transition hover:bg-slate-800">Save changes</button>
            </div>
        </form>
    </div>

    <script>
        function openEquipmentModal(
            assetTag,
            name,
            brand,
            model,
            serial,
            category,
            room,
            quantity,
            condition,
            status,
            purchaseDate,
            warranty,
            borrowable,
        ) {
            document.getElementById("modal_asset_tag").textContent = assetTag;
            document.getElementById("modal_name").textContent = name;
            document.getElementById("modal_brand").textContent = brand;
            document.getElementById("modal_model").textContent = model;
            document.getElementById("modal_serial").textContent = serial;
            document.getElementById("modal_category").textContent = category;
            document.getElementById("modal_room").textContent = room;
            document.getElementById("modal_quantity").textContent = quantity;
            document.getElementById("modal_condition").textContent = condition;
            document.getElementById("modal_status").textContent = status;
            document.getElementById("modal_purchase_date").textContent =
                purchaseDate;
            document.getElementById("modal_warranty").textContent = warranty;
            document.getElementById("modal_borrowable").textContent =
                borrowable;

            const modal = document.getElementById("viewEquipmentModal");

            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }

        function closeEquipmentModal() {
            const modal = document.getElementById("viewEquipmentModal");

            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }
    </script>

    <script>
        function openAddEquipmentModal() {
            const modal = document.getElementById("addEquipmentModal");

            modal.classList.remove("hidden");
            modal.classList.add("flex");
            document.getElementById("add_equipment_name")?.dispatchEvent(new Event("equipment-category-reset"));
        }

        function closeAddEquipmentModal() {
            const modal = document.getElementById("addEquipmentModal");

            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }
    </script>

    <script>
        function openEditEquipmentModal(
            id,
            category,
            room,
            assetTag,
            name,
            brand,
            model,
            serial,
            quantity,
            condition,
            status,
            borrowable
        ) {
            document.getElementById("editEquipmentForm").action =
                "/maintenance/equipment/update/" + id;

            document.getElementById("edit_equipment_name").value = name;

            document.getElementById("edit_asset_tag").value = assetTag;

            document.getElementById("edit_brand").value = brand;

            document.getElementById("edit_model").value = model;

            document.getElementById("edit_serial").value = serial;

            document.getElementById("edit_quantity").value = quantity;

            setEqSelectValue("edit_condition", condition);

            setEqSelectValue("edit_status", status);

            setEqSelectValue("edit_category", category);

            setEqSelectValue("edit_room", room);

            document.getElementById("edit_equipment_borrowable").checked =
                borrowable == 1;

            document
                .getElementById("editEquipmentModal")
                .classList.remove("hidden");

            document.getElementById("editEquipmentModal").classList.add("flex");
        }

        function closeEditEquipmentModal() {
            if (window.closeEqSelectPanel) {
                closeEqSelectPanel();
            }
            document
                .getElementById("editEquipmentModal")
                .classList.add("hidden");

            document
                .getElementById("editEquipmentModal")
                .classList.remove("flex");
        }
    </script>

    @include('layouts.partials.equipment-category-detect')

@endsection
