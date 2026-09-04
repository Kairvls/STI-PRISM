@extends ("layouts.maintenance-layout")

@section ("title", ($isStockPage ?? false) ? "Inventory" : "All Equipment")

@section ("content")
    @php
        $isStockPage = $isStockPage ?? (($scope ?? 'stock') === 'stock');
        $eqImageUrl = function ($path) {
            if (!filled($path)) {
                return '';
            }

            if (
                str_starts_with($path, 'http://')
                || str_starts_with($path, 'https://')
                || str_starts_with($path, '/storage/')
            ) {
                return $path;
            }

            return asset('storage/'.$path);
        };
    @endphp

    <div class="space-y-6">
        <!-- PAGE HEADER -->
        @if ($isStockPage)
            <div class="flex flex-wrap items-center justify-end gap-2">
                <button
                    type="button"
                    id="inventoryTransferSelectedBtn"
                    onclick="openInventoryTransferSelectedModal()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    disabled
                >
                    <i data-lucide="check-square" class="h-4 w-4"></i>
                    Transfer selected
                    <span id="inventoryTransferSelectedCount" class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-bold text-slate-600">0</span>
                </button>
                <button
                    type="button"
                    onclick="openInventoryTransferAllModal()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    @if (empty($stockTransferIds ?? [])) disabled @endif
                >
                    <i data-lucide="move" class="h-4 w-4"></i>
                    Transfer all
                </button>
                <button
                    type="button"
                    onclick="openAddEquipmentModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 font-semibold font-sans-serif text-[13px] text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                    @if (!($defaultStorageRoomId ?? null)) disabled @endif
                >
                    <i data-lucide="plus" class="w-4 h-4"></i>

                    Add to stock
                </button>
            </div>
            @if (!($defaultStorageRoomId ?? null))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    Create a room with type <span class="font-semibold">Storage / Stockroom</span> before adding inventory stock.
                </div>
            @endif
        @endif

        <!-- DASHBOARD CARDS -->
        @if ($isStockPage)
            <div class="overflow-hidden rounded-lg border-y border-slate-300 bg-gray-100 shadow-sm">
                <div class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-y-0 xl:grid-cols-4">
                    <div class="px-8 py-6">
                        <p class="text-sm font-medium text-slate-500">Items in storage</p>
                        <h2 class="mt-2 text-4xl font-medium text-slate-900">{{ number_format($totalEquipment) }}</h2>
                        <p class="mt-3 text-sm text-slate-500">
                            @if ($equipmentMonthlyPercentage === null)
                                <span class="font-semibold text-emerald-600">New activity</span>
                            @else
                                <span class="font-semibold {{ $equipmentMonthlyPercentage > 0 ? 'text-emerald-600' : ($equipmentMonthlyPercentage < 0 ? 'text-red-600' : 'text-slate-500') }}">
                                    {{ $equipmentMonthlyPercentage > 0 ? '+' : '' }}{{ number_format($equipmentMonthlyPercentage, 2) }}%
                                </span>
                            @endif
                            <span>added vs last month</span>
                        </p>
                    </div>
                    <div class="px-8 py-6">
                        <p class="text-sm font-medium text-slate-500">Total quantity</p>
                        <h2 class="mt-2 text-4xl font-medium text-slate-900">{{ number_format($stockTotalQuantity ?? 0) }}</h2>
                        <p class="mt-3 text-sm text-slate-500">Combined qty in stockrooms</p>
                    </div>
                    <div class="px-8 py-6">
                        <p class="text-sm font-medium text-slate-500">Stock types</p>
                        <h2 class="mt-2 text-4xl font-medium text-slate-900">{{ number_format($stockTypeCount ?? 0) }}</h2>
                        <p class="mt-3 text-sm text-slate-500">Distinct equipment names</p>
                    </div>
                    <div class="px-8 py-6">
                        <p class="text-sm font-medium text-slate-500">Storage rooms</p>
                        <h2 class="mt-2 text-4xl font-medium text-slate-900">{{ number_format($storageRoomsWithStock ?? 0) }}</h2>
                        <p class="mt-3 text-sm text-slate-500">Rooms holding stock</p>
                    </div>
                </div>
            </div>
        @else
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
        @endif

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
                    {{-- STATUS TABS (All Equipment only) --}}
                    {{-- ================================================= --}}

                    <div class="min-w-0 flex-1">

                        @if ($isStockPage)
                            <p class="text-sm text-slate-500">
                                Storage stock only. Check items to <span class="font-medium text-slate-800">Transfer selected</span>, use row <span class="font-medium text-slate-800">Transfer to</span>, or <span class="font-medium text-slate-800">Transfer all</span>.
                            </p>
                        @else
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
                                            ? 'bg-gray-100/80 font-medium text-black'
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
                                            ? 'bg-gray-100/80 font-medium text-black'
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
                                            ? 'bg-gray-100/80 font-medium text-black'
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
                                            ? 'bg-gray-100/80 font-medium text-black'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                Borrowed
                            </a>


                            {{-- FOR REPLACEMENT --}}

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'status' => 'For Replacement',
                                    'page' => null,
                                ]) }}"

                                class="shrink-0 rounded-lg px-3 py-2
                                    text-sm transition
                                    {{
                                        request('status') === 'For Replacement'
                                            ? 'bg-gray-100/80 font-medium text-black'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                For Replacement
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
                                            ? 'bg-gray-100/80 font-medium text-black'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                Disposed
                            </a>

                        </div>
                        @endif

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
                                    {{ $isStockPage ? 'All storage rooms' : 'All Rooms' }}
                                </option>

                                @foreach ($rooms as $room)

                                    <option
                                        value="{{ $room->room_id }}"

                                        @selected(
                                            request('room') == $room->room_id
                                        )
                                    >
                                        {{ \App\Support\RoomCategories::isStorageType($room->room_type ?? null) ? 'Storage · '.$room->room_name : $room->room_name }}
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
                                rounded-lg bg-[#0025cc] px-4
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



                

                <table class="w-full" @if ($isStockPage) id="inventoryStockTable" @endif>
                    <thead class="border-b border-slate-200 bg-slate-50/80">
                        <tr>
                            @if ($isStockPage)
                                <th class="w-12 px-4 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        id="inventorySelectAllPage"
                                        class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                        onclick="toggleInventorySelectAllPage(this)"
                                        aria-label="Select all on this page"
                                    />
                                </th>
                            @endif
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

                                $placementZone = trim((string) (
                                    $item->equipment_placement_zone
                                    ?: $item->equipment_current_location
                                    ?: ''
                                ));
                                $isStorageStock = \App\Support\RoomCategories::isStorageType($item->room_type ?? null);
                                $placementLabel = $isStorageStock ? 'Stock' : 'Deployed';
                                $placementClass = $isStorageStock
                                    ? 'bg-amber-50 text-amber-700 ring-amber-200'
                                    : 'bg-sky-50 text-sky-700 ring-sky-200';
                            @endphp
                            <tr class="border-b border-slate-100 transition duration-200 hover:bg-slate-50">
                                @if ($isStockPage)
                                    <td class="px-4 py-4 text-center">
                                        <input
                                            type="checkbox"
                                            class="inventory-stock-checkbox h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                            value="{{ (int) $item->equipment_id }}"
                                            data-name="{{ $item->equipment_name }}"
                                            onchange="syncInventoryTransferSelection()"
                                            aria-label="Select {{ $item->equipment_name }}"
                                        />
                                    </td>
                                @endif
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                                            @if (filled($item->equipment_image))
                                                <button
                                                    type="button"
                                                    onclick="event.stopPropagation(); openEquipmentPhotoViewer({{ json_encode($eqImageUrl($item->equipment_image)) }}, {{ json_encode($item->equipment_name) }})"
                                                    class="group relative h-full w-full"
                                                    aria-label="View {{ $item->equipment_name }} photo fullscreen"
                                                >
                                                    <img
                                                        src="{{ $eqImageUrl($item->equipment_image) }}"
                                                        alt="{{ $item->equipment_name }}"
                                                        class="h-full w-full object-cover"
                                                    >
                                                    <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 transition group-hover:bg-slate-950/40">
                                                        <i data-lucide="expand" class="h-3.5 w-3.5 text-white opacity-0 transition group-hover:opacity-100"></i>
                                                    </span>
                                                </button>
                                            @else
                                                <span
                                                    class="inline-flex h-6 w-6 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                                                    data-equipment-layout-icon="{{ $item->equipment_name }}"
                                                ></span>
                                            @endif
                                        </div>
                                        <div class="flex min-w-0 flex-col">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-semibold text-slate-900">
                                                    {{ $item->equipment_name }}
                                                </span>
                                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset {{ $placementClass }}">
                                                    {{ $placementLabel }}
                                                </span>
                                            </div>

                                            <span class="mt-1 text-xs text-slate-400">
                                                {{ $item->equipment_asset_tag ?? "No Asset Tag" }}
                                                @if ($placementZone !== '')
                                                    · {{ $placementZone }}
                                                @endif
                                            </span>
                                        </div>
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
                                            onclick='openEquipmentModal(@json(\App\Support\LayoutEquipmentPayload::fromRow($item, $eqImageUrl($item->equipment_image))))'
                                            class="flex h-9 items-center justify-center gap-x-1.5 rounded-lg  bg-slate-100 px-3 text-xs  text-slate-800 transition shadow-sm hover:bg-slate-200 hover:text-gray-600"
                                            data-tooltip="View equipment"
                                            aria-label="View equipment"
                                        >
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </button>

                                        @if ($isStockPage)
                                            <button
                                                type="button"
                                                onclick="openInventoryTransferToModal(
                                                    {{ (int) $item->equipment_id }},
                                                    {{ json_encode($item->equipment_name) }},
                                                    {{ json_encode($item->room_name ?? '') }},
                                                    {{ json_encode($item->equipment_asset_tag ?? '') }}
                                                )"
                                                class="flex h-9 items-center justify-center gap-x-1.5 rounded-lg bg-sky-50 px-3 text-xs font-medium text-sky-800 transition hover:bg-sky-100"
                                                data-tooltip="Transfer to room"
                                                aria-label="Transfer to room"
                                            >
                                                <i data-lucide="move" class="h-4 w-4"></i>
                                            </button>
                                        @endif

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

                                                '{{ $item->equipment_purchase_date ? \Carbon\Carbon::parse($item->equipment_purchase_date)->format('Y-m-d') : '' }}',

                                                '{{ $item->equipment_acquired_date ? \Carbon\Carbon::parse($item->equipment_acquired_date)->format('Y-m-d') : '' }}',

                                                '{{ $item->equipment_purchase_cost ?? '' }}',

                                                '{{ $item->equipment_warranty_expiration ? \Carbon\Carbon::parse($item->equipment_warranty_expiration)->format('Y-m-d') : '' }}',

                                                '{{ $item->equipment_is_borrowable }}',

                                                {{ json_encode($eqImageUrl($item->equipment_image)) }}

                                            )"
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#FFF200] text-black transition hover:bg-[#E6E600]"
                                            data-tooltip="Edit equipment"
                                            aria-label="Edit equipment"
                                        >
                                            <i data-lucide="edit-3" class="h-4 w-4"></i>
                                        </button>

                                        @if (($item->equipment_inventory_status ?? '') === 'For Replacement')
                                            <button
                                                type="button"
                                                onclick="openInventoryDisposeModal(
                                                    {{ (int) $item->equipment_id }},
                                                    {{ json_encode($item->equipment_name) }},
                                                    {{ json_encode($item->room_name ?? '') }}
                                                )"
                                                class="flex h-9 items-center justify-center gap-x-1.5 rounded-lg bg-rose-600 px-3 text-xs font-medium text-white transition hover:bg-rose-700"
                                                data-tooltip="Dispose equipment"
                                                aria-label="Dispose equipment"
                                            >
                                                <i data-lucide="archive-x" class="h-4 w-4"></i>
                                                
                                            </button>
                                        @endif

                                    </div>
                                </td>
                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="{{ $isStockPage ? 9 : 8 }}"
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
            class="fixed inset-0 z-50 hidden"
            aria-hidden="true"
        >
            <div
                class="absolute inset-0 bg-[#0b1220]/50 backdrop-blur-[1px] transition-opacity"
                onclick="closeEquipmentModal()"
            ></div>

            <aside
                id="viewEquipmentModalPanel"
                class="absolute right-0 top-0 flex h-full w-full max-w-[420px] translate-x-full flex-col overflow-hidden rounded-l-2xl border-l border-slate-200 bg-white shadow-2xl shadow-slate-950/10 transition-transform duration-300 ease-out"
                role="dialog"
                aria-modal="true"
                aria-labelledby="eqAssetModal_drawer_title"
            >
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="min-w-0">
                        <h2 id="eqAssetModal_drawer_title" class="text-xl font-semibold tracking-tight text-slate-900">Asset details</h2>
                        <p id="eqAssetModal_drawer_subtitle" class="mt-1 text-sm text-slate-500">Review equipment information and lifecycle.</p>
                    </div>
                    <button type="button" onclick="closeEquipmentModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                @include('maintenance-personnel.equipment.partials.equipment-asset-details-body')
            </aside>
        </div>

        <style>
            .eq-drawer-scroll {
                scrollbar-width: thin;
                scrollbar-color: #cbd5e1 transparent;
            }

            .eq-drawer-scroll::-webkit-scrollbar {
                width: 6px;
            }

            .eq-drawer-scroll::-webkit-scrollbar-thumb {
                border-radius: 999px;
                background: #cbd5e1;
            }
        </style>
    <div
        id="addEquipmentModal"
        x-data="inventoryAddEquipment()"
        x-show="open"
        x-cloak
        x-effect="document.body.style.overflow = open ? 'hidden' : ''"
        @keydown.escape.window="if (document.getElementById('equipmentPhotoViewer')?.classList.contains('flex')) { return; } if (open) { if (fullscreen && step === 2) { fullscreen = false; } else { close(); } }"
        @if ($errors->any())
        x-init="
            open = true;
            formError = {{ json_encode($errors->first()) }};
            errors = {
                @if ($errors->has('equipment_name')) name: {{ json_encode($errors->first('equipment_name')) }}, @endif
                @if ($errors->has('equipment_category_id')) category: {{ json_encode($errors->first('equipment_category_id')) }}, @endif
                @if ($errors->has('equipment_room_id')) room: {{ json_encode($errors->first('equipment_room_id')) }}, @endif
                @if ($errors->has('equipment_quantity')) quantity: {{ json_encode($errors->first('equipment_quantity')) }}, @endif
                @if ($errors->has('equipment_image')) image: {{ json_encode($errors->first('equipment_image')) }}, @endif
            };
            $nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        "
        @endif
        class="fixed inset-0 z-50 hidden items-center justify-center overflow-hidden bg-[#0b1220]/70"
        :class="[
            open ? '!flex' : 'hidden',
            fullscreen && step === 2 ? 'p-0' : 'p-4'
        ]"
    >
        <form
            action="/maintenance/equipment/store"
            method="POST"
            enctype="multipart/form-data"
            @submit="prepareSubmit($event)"
            class="flex w-full flex-col overflow-hidden border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
            :class="fullscreen && step === 2
                ? 'h-[100dvh] max-h-[100dvh] max-w-none rounded-none border-0 shadow-none'
                : (step === 2
                    ? 'max-h-[90vh] w-[calc(93vw-1.5rem)] max-w-[calc(93vw-1.5rem)] rounded-2xl'
                    : 'max-h-[90vh] max-w-4xl rounded-2xl')"
        >
            @csrf
            <input type="hidden" name="equipment_tracking_mode" :value="tracking">
            <input type="hidden" name="equipment_quantity" :value="quantity">

            <div class="flex items-start justify-between px-6 pt-6">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900" x-text="step === 2 ? 'Item details' : {{ $isStockPage ? "'Add to stock'" : "'Add equipment'" }}"></h2>
                    <p class="mt-1 text-sm text-slate-500" x-text="step === 2
                        ? 'Edit unique identity per unit. Shared name, category, and room apply to all.'
                        : {{ $isStockPage ? "'Receive equipment into a storage room. Deploy later with Transfers.'" : "'Identity on the left, status on the right.'" }}"></p>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <button
                        type="button"
                        x-show="step === 2"
                        x-cloak
                        @click="fullscreen = !fullscreen; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                        class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                        :title="fullscreen ? 'Exit full screen' : 'Full screen'"
                        :aria-label="fullscreen ? 'Exit full screen' : 'Full screen'"
                    >
                        <i :data-lucide="fullscreen ? 'minimize-2' : 'maximize-2'" class="h-4 w-4"></i>
                    </button>
                    <button type="button" @click="close()" class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <div
                x-show="formError"
                x-cloak
                class="mx-6 mt-4 flex items-start gap-3 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100"
            >
                <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                <p class="min-w-0 flex-1 leading-relaxed" x-text="formError"></p>
                <button type="button" @click="formError = ''" class="shrink-0 rounded-lg p-1 text-rose-400 transition hover:bg-rose-100 hover:text-rose-700" aria-label="Dismiss">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                </button>
            </div>

            <div class="eq-modal-scroll min-h-0 flex-1 overflow-y-auto px-6 py-5" x-show="step === 1">
                <div
                    class="mb-5 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80"
                    x-show="!needsItemStep()"
                    x-cloak
                >
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Photo (optional)</p>
                    <div class="mt-3 flex items-center gap-4">
                        <button
                            type="button"
                            x-show="imagePreview"
                            x-cloak
                            @click="openEquipmentPhotoViewer(imagePreview, name || 'Equipment photo')"
                            class="group relative flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80"
                            aria-label="View equipment photo fullscreen"
                        >
                            <img :src="imagePreview" alt="Equipment photo preview" class="h-full w-full object-cover">
                            <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 transition group-hover:bg-slate-950/40">
                                <i data-lucide="expand" class="h-4 w-4 text-white opacity-0 transition group-hover:opacity-100"></i>
                            </span>
                        </button>
                        <div
                            x-show="!imagePreview"
                            class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                                x-html="window.PrismEquipmentIcons ? window.PrismEquipmentIcons.svg(name || '') : ''"
                            ></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900">Add equipment photo</p>
                            <p class="mt-0.5 text-xs text-slate-400">JPG, PNG, WebP, or GIF. Max 5 MB.</p>
                            <p x-show="imagePreview" x-cloak class="mt-0.5 text-xs text-slate-500">Click the photo to view it full screen.</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <label class="inline-flex h-9 cursor-pointer items-center rounded-lg bg-white px-3 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/80 transition hover:bg-slate-50">
                                    Choose image
                                    <input
                                        type="file"
                                        :name="needsItemStep() ? null : 'equipment_image'"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        class="sr-only"
                                        x-ref="imageInput"
                                        :disabled="needsItemStep()"
                                        @change="onImageChange($event)"
                                    >
                                </label>
                                <button
                                    type="button"
                                    x-show="imagePreview"
                                    x-cloak
                                    @click="clearImage()"
                                    class="inline-flex h-9 items-center rounded-lg px-3 text-xs font-semibold text-rose-600 hover:bg-rose-50"
                                >
                                    Remove
                                </button>
                            </div>
                            <p x-show="errors.image" x-cloak class="mt-2 text-xs font-medium text-rose-600" x-text="errors.image"></p>
                        </div>
                    </div>
                </div>
                <div
                    class="mb-5 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200/80"
                    x-show="needsItemStep()"
                    x-cloak
                >
                    Photos are optional per unit on the next step — one shared photo isn’t used when creating multiple individually tracked assets.
                </div>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">What & where</p>
                        <div>
                            <label for="add_equipment_name" class="{{ $eqLabel }}">Equipment name <span class="text-rose-500">*</span></label>
                            <input
                                id="add_equipment_name"
                                type="text"
                                name="equipment_name"
                                x-model="name"
                                @input="clearError('name'); onNameInput(); syncAssetTag()"
                                placeholder="e.g. Mouse"
                                class="{{ $eqField }}"
                                :class="errors.name ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                            />
                            <p x-show="errors.name" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.name"></p>
                        </div>
                        <div>
                            <label for="add_equipment_category" class="{{ $eqLabel }}">Category <span class="text-rose-500">*</span></label>
                            <select
                                id="add_equipment_category"
                                name="equipment_category_id"
                                x-model="category"
                                @change="clearError('category'); onCategoryChange()"
                                class="{{ $eqField }}"
                                :class="errors.category ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                            >
                                <option value="">Select category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->equipment_category_id }}">{{ $category->equipment_category_name }}</option>
                                @endforeach
                            </select>
                            <p x-show="errors.category" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.category"></p>
                            <p x-show="!errors.category" class="mt-1.5 text-xs text-slate-400">Filled from the equipment name. You can still choose another category.</p>
                        </div>
                        <div>
                            <label for="add_equipment_room" class="{{ $eqLabel }}">{{ $isStockPage ? 'Storage room' : 'Room' }} <span class="text-rose-500">*</span></label>
                            <select
                                id="add_equipment_room"
                                name="equipment_room_id"
                                x-model="room"
                                @change="clearError('room'); syncAssetTag()"
                                class="{{ $eqField }}"
                                :class="errors.room ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                            >
                                <option value="">{{ $isStockPage ? 'Select storage room' : 'Select room' }}</option>
                                @foreach (($isStockPage ? ($storageRooms ?? $rooms) : $rooms) as $room)
                                    @php
                                        $isStorageRoom = \App\Support\RoomCategories::isStorageType($room->room_type ?? null);
                                        $roomLabel = $isStockPage
                                            ? $room->room_name
                                            : ($isStorageRoom ? 'Storage · '.$room->room_name : $room->room_name);
                                    @endphp
                                    <option value="{{ $room->room_id }}">{{ $roomLabel }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-xs text-slate-400">
                                @if ($isStockPage)
                                    Stock stays in storage until you transfer it to a classroom or lab.
                                @else
                                    Prefer a Storage room to keep items in inventory stock. Use Transfers to deploy to classrooms.
                                @endif
                            </p>
                            <p x-show="errors.room" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.room"></p>
                        </div>
                    </div>
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="add_equipment_quantity" class="{{ $eqLabel }}">Qty</label>
                                <input
                                    id="add_equipment_quantity"
                                    type="number"
                                    min="1"
                                    max="200"
                                    x-model.number="quantity"
                                    @input="clearError('quantity'); syncAssetTag(); onSharedPhotoModeChange()"
                                    class="{{ $eqField }}"
                                    :class="errors.quantity ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                />
                                <p x-show="errors.quantity" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.quantity"></p>
                            </div>
                            <div>
                                <label for="add_equipment_condition" class="{{ $eqLabel }}">Condition</label>
                                <select id="add_equipment_condition" name="equipment_condition_status" x-model="condition" class="{{ $eqField }}">
                                    <option value="Good">Good</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="Under Maintenance">Under maintenance</option>
                                    <option value="Disposed">Disposed</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="{{ $eqLabel }}">Tracking mode</label>
                            <div class="flex h-11 rounded-xl bg-slate-100 p-1">
                                <button type="button" @click="tracking = 'Bulk'; assetTagManual = false; syncAssetTag(); onSharedPhotoModeChange()" class="flex-1 rounded-lg text-sm font-medium transition" :class="tracking === 'Bulk' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Bulk</button>
                                <button type="button" @click="tracking = 'Individual'; assetTagManual = false; syncAssetTag(); onSharedPhotoModeChange()" class="flex-1 rounded-lg text-sm font-medium transition" :class="tracking === 'Individual' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Individual</button>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-400" x-text="tracking === 'Bulk'
                                ? 'One stock record with combined quantity.'
                                : 'Creates separate trackable assets (asset tag, serial, QR per unit).'"></p>
                        </div>
                        <label class="flex items-center justify-between rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-200/80">
                            <span class="text-sm font-medium text-slate-900">Can be borrowed</span>
                            <input id="add_equipment_borrowable" type="checkbox" name="equipment_is_borrowable" value="1" class="peer sr-only">
                            <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-slate-900 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5"></span>
                        </label>
                    </div>
                </div>
                <details class="mt-5 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80" open>
                    <summary class="cursor-pointer text-sm font-medium text-slate-700">Shared defaults / single-item details</summary>
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <div x-show="tracking === 'Bulk' || quantity === 1">
                            <label for="add_equipment_asset_tag" class="{{ $eqLabel }}">Asset tag</label>
                            <input id="add_equipment_asset_tag" type="text" name="equipment_asset_tag" x-model="assetTag" @input="assetTagManual = true" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="add_equipment_brand" class="{{ $eqLabel }}">Brand name</label>
                            <input id="add_equipment_brand" type="text" name="equipment_brand_name" x-model="brand" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="add_equipment_model" class="{{ $eqLabel }}">Model</label>
                            <input id="add_equipment_model" type="text" name="equipment_model" x-model="model" class="{{ $eqField }}" />
                        </div>
                        <div x-show="tracking === 'Bulk' || quantity === 1">
                            <label for="add_equipment_serial" class="{{ $eqLabel }}">Serial number</label>
                            <input id="add_equipment_serial" type="text" name="equipment_serial_number" x-model="serial" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="add_warranty_expiration" class="{{ $eqLabel }}">Warranty expiration</label>
                            <input id="add_warranty_expiration" type="date" name="equipment_warranty_expiration" x-model="warranty" class="{{ $eqField }}" />
                        </div>
                    </div>
                </details>
            </div>

            <div class="eq-modal-scroll min-h-0 flex-1 overflow-y-auto px-6 py-5" x-show="step === 2" x-cloak>
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80">
                    <div class="text-sm text-slate-600">
                        <span class="font-medium text-slate-900" x-text="name"></span>
                        <span class="text-slate-400"> · </span>
                        <span x-text="items.length + ' individually tracked units'"></span>
                        <span class="text-slate-400"> · </span>
                        <span class="text-slate-500">Optional photo per unit</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="regenerateAssetTags()" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Regenerate asset tags</button>
                        <button type="button" @click="applyDefaults()" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Re-apply shared defaults</button>
                    </div>
                </div>
                <div class="rounded-xl ring-1 ring-slate-200">
                    <table class="w-full table-fixed divide-y divide-slate-200 text-left text-sm">
                        <colgroup>
                            <col class="w-[3%]">
                            <col class="w-[14%]">
                            <col class="w-[22%]">
                            <col class="w-[14%]">
                            <col class="w-[12%]">
                            <col class="w-[12%]">
                            <col class="w-[13%]">
                            <col class="w-[10%]">
                        </colgroup>
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-2 py-2.5">#</th>
                                <th class="px-2 py-2.5">Photo</th>
                                <th class="px-2 py-2.5">Asset tag</th>
                                <th class="px-2 py-2.5">Serial</th>
                                <th class="px-2 py-2.5">Brand</th>
                                <th class="px-2 py-2.5">Model</th>
                                <th class="px-2 py-2.5">Condition</th>
                                <th class="px-2 py-2.5">Warranty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="px-2 py-2 text-slate-400" x-text="index + 1"></td>
                                    <td class="px-2 py-2">
                                        <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                                            <button
                                                type="button"
                                                x-show="item._imagePreview"
                                                x-cloak
                                                @click="openEquipmentPhotoViewer(item._imagePreview, (item.equipment_asset_tag || name || 'Equipment') + ' photo')"
                                                class="group relative h-9 w-9 shrink-0 overflow-hidden rounded-md bg-white ring-1 ring-slate-200"
                                                aria-label="View unit photo"
                                            >
                                                <img :src="item._imagePreview" alt="" class="h-full w-full object-cover">
                                            </button>
                                            <div
                                                x-show="!item._imagePreview"
                                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-md bg-slate-50 ring-1 ring-slate-200"
                                            >
                                                <span
                                                    class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                                                    x-html="window.PrismEquipmentIcons ? window.PrismEquipmentIcons.svg(name || '') : ''"
                                                ></span>
                                            </div>
                                            <label class="inline-flex h-8 cursor-pointer items-center rounded-md bg-white px-2 text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                                                <span x-text="item._imagePreview ? 'Change' : 'Add'"></span>
                                                <input
                                                    type="file"
                                                    :name="'items[' + index + '][equipment_image]'"
                                                    :data-item-image-index="index"
                                                    accept="image/jpeg,image/png,image/webp,image/gif"
                                                    class="sr-only"
                                                    @change="onItemImageChange(index, $event)"
                                                >
                                            </label>
                                            <button
                                                type="button"
                                                x-show="item._imagePreview"
                                                x-cloak
                                                @click="clearItemImage(index)"
                                                class="text-[11px] font-semibold text-rose-600 hover:text-rose-700"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="group/eqtip relative min-w-0">
                                            <input type="text" :name="'items[' + index + '][equipment_asset_tag]'" x-model="item.equipment_asset_tag" @input="item._tagManual = true" class="h-9 w-full min-w-0 truncate rounded-md border border-slate-200 px-2 text-sm" />
                                            <div
                                                x-show="String(item.equipment_asset_tag || '').trim()"
                                                x-cloak
                                                class="pointer-events-none absolute left-0 top-[calc(100%+0.35rem)] z-30 max-w-[min(28rem,70vw)] whitespace-normal break-all rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-medium leading-snug text-white shadow-lg opacity-0 invisible transition group-hover/eqtip:visible group-hover/eqtip:opacity-100"
                                                x-text="item.equipment_asset_tag"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="group/eqtip relative min-w-0">
                                            <input type="text" :name="'items[' + index + '][equipment_serial_number]'" x-model="item.equipment_serial_number" class="h-9 w-full min-w-0 truncate rounded-md border border-slate-200 px-2 text-sm" />
                                            <div
                                                x-show="String(item.equipment_serial_number || '').trim()"
                                                x-cloak
                                                class="pointer-events-none absolute left-0 top-[calc(100%+0.35rem)] z-30 max-w-[min(28rem,70vw)] whitespace-normal break-all rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-medium leading-snug text-white shadow-lg opacity-0 invisible transition group-hover/eqtip:visible group-hover/eqtip:opacity-100"
                                                x-text="item.equipment_serial_number"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="group/eqtip relative min-w-0">
                                            <input type="text" :name="'items[' + index + '][equipment_brand_name]'" x-model="item.equipment_brand_name" class="h-9 w-full min-w-0 truncate rounded-md border border-slate-200 px-2 text-sm" />
                                            <div
                                                x-show="String(item.equipment_brand_name || '').trim()"
                                                x-cloak
                                                class="pointer-events-none absolute left-0 top-[calc(100%+0.35rem)] z-30 max-w-[min(28rem,70vw)] whitespace-normal break-all rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-medium leading-snug text-white shadow-lg opacity-0 invisible transition group-hover/eqtip:visible group-hover/eqtip:opacity-100"
                                                x-text="item.equipment_brand_name"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="group/eqtip relative min-w-0">
                                            <input type="text" :name="'items[' + index + '][equipment_model]'" x-model="item.equipment_model" class="h-9 w-full min-w-0 truncate rounded-md border border-slate-200 px-2 text-sm" />
                                            <div
                                                x-show="String(item.equipment_model || '').trim()"
                                                x-cloak
                                                class="pointer-events-none absolute left-0 top-[calc(100%+0.35rem)] z-30 max-w-[min(28rem,70vw)] whitespace-normal break-all rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-medium leading-snug text-white shadow-lg opacity-0 invisible transition group-hover/eqtip:visible group-hover/eqtip:opacity-100"
                                                x-text="item.equipment_model"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <select :name="'items[' + index + '][equipment_condition_status]'" x-model="item.equipment_condition_status" class="h-9 w-full min-w-0 rounded-md border border-slate-200 px-2 text-sm">
                                            <option value="Good">Good</option>
                                            <option value="Damaged">Damaged</option>
                                            <option value="Under Maintenance">Under Maintenance</option>
                                            <option value="Disposed">Disposed</option>
                                        </select>
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="date" :name="'items[' + index + '][equipment_warranty_expiration]'" x-model="item.equipment_warranty_expiration" class="h-9 w-full min-w-0 rounded-md border border-slate-200 px-2 text-sm" />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button type="button" @click="step === 2 ? (step = 1, fullscreen = false) : close()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100" x-text="step === 2 ? 'Back' : 'Cancel'"></button>
                <button
                    type="button"
                    x-show="step === 1 && needsItemStep()"
                    @click="goToItems()"
                    class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800"
                >
                    Continue
                </button>
                <button
                    type="submit"
                    x-show="step === 2 || !needsItemStep()"
                    class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800"
                    x-text="step === 2 ? ('Create ' + items.length + ' assets') : {{ $isStockPage ? "'Add to stock'" : "'Add equipment'" }}"
                ></button>
            </div>
        </form>
    </div>

    <div
        id="editEquipmentModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <form id="editEquipmentForm" method="POST" enctype="multipart/form-data" class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
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
                <div class="mb-5 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Photo (optional)</p>
                    <div class="mt-3 flex items-center gap-4">
                        <button
                            type="button"
                            id="edit_image_button"
                            onclick="openEquipmentPhotoViewer(document.getElementById('edit_image_preview')?.src, document.getElementById('edit_equipment_name')?.value || 'Equipment photo')"
                            class="group relative hidden h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80"
                            aria-label="View equipment photo fullscreen"
                        >
                            <img id="edit_image_preview" src="" alt="Equipment photo" class="h-full w-full object-cover">
                            <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 transition group-hover:bg-slate-950/40">
                                <i data-lucide="expand" class="h-4 w-4 text-white opacity-0 transition group-hover:opacity-100"></i>
                            </span>
                        </button>
                        <div id="edit_image_placeholder" class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                            <span id="edit_layout_icon" class="inline-flex h-8 w-8 items-center justify-center [&_svg]:h-full [&_svg]:w-full"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900">Change equipment photo</p>
                            <p class="mt-0.5 text-xs text-slate-400">JPG, PNG, WebP, or GIF. Max 5 MB. Leave empty to keep the current photo.</p>
                            <p id="edit_image_hint" class="mt-0.5 hidden text-xs text-slate-500">Click the photo to view it full screen.</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <label class="inline-flex h-9 cursor-pointer items-center rounded-lg bg-white px-3 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/80 transition hover:bg-slate-50">
                                    Choose image
                                    <input
                                        id="edit_equipment_image"
                                        type="file"
                                        name="equipment_image"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        class="sr-only"
                                    >
                                </label>
                                <button
                                    type="button"
                                    id="edit_clear_image"
                                    class="hidden h-9 items-center rounded-lg px-3 text-xs font-semibold text-rose-600 hover:bg-rose-50"
                                >
                                    Remove
                                </button>
                                <input type="hidden" name="remove_equipment_image" id="edit_remove_image" value="0">
                            </div>
                            @error('equipment_image')
                                <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
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
                                    <option value="{{ $room->room_id }}">
                                        {{ \App\Support\RoomCategories::isStorageType($room->room_type ?? null) ? 'Storage · '.$room->room_name : $room->room_name }}
                                    </option>
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
                        <div>
                            <label for="edit_purchase_date" class="{{ $eqLabel }}">Purchased</label>
                            <input id="edit_purchase_date" type="date" name="equipment_purchase_date" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_acquired_date" class="{{ $eqLabel }}">Acquired</label>
                            <input id="edit_acquired_date" type="date" name="equipment_acquired_date" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_purchase_cost" class="{{ $eqLabel }}">Purchase cost (₱)</label>
                            <input id="edit_purchase_cost" type="number" name="equipment_purchase_cost" min="0" step="0.01" placeholder="0.00" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label
                                for="edit_warranty_expiration"
                                class="{{ $eqLabel }}"
                            >
                                Warranty expiration
                            </label>

                            <input
                                id="edit_warranty_expiration"
                                type="date"
                                name="equipment_warranty_expiration"
                                class="{{ $eqField }}"
                            />
                        </div>
                    </div>
                </details>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button type="button" onclick="closeEditEquipmentModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                <button type="submit" class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Save changes</button>
            </div>
        </form>
    </div>

    <script>
        let equipmentModalLifecycleRequest = 0;
        let equipmentModalCurrentAssetTag = '';

        function formatEquipmentAssetDate(value) {
            if (!value) return '—';
            const date = new Date(String(value).replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) return String(value);
            return date.toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            });
        }

        function setEquipmentModalText(id, value) {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = value && String(value).trim() !== '' ? value : '—';
            }
        }

        function applyEquipmentStatusBadge(status) {
            const el = document.getElementById('eqAssetModal_status_badge');
            if (!el) return;

            const label = status && String(status).trim() !== '' ? status : 'Unknown';
            el.textContent = label;
            el.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold';

            const map = {
                'Active': 'bg-emerald-50 text-emerald-700',
                'Under Maintenance': 'bg-amber-50 text-amber-700',
                'Borrowed': 'bg-sky-50 text-sky-700',
                'For Replacement': 'bg-orange-50 text-orange-700',
                'Disposed': 'bg-rose-50 text-rose-700',
            };

            el.classList.add(...(map[label] || 'bg-slate-100 text-slate-600').split(' '));
        }

        function applyEquipmentConditionBadge(condition) {
            const el = document.getElementById('eqAssetModal_condition_badge');
            if (!el) return;

            const label = condition && String(condition).trim() !== '' ? condition : '—';
            el.textContent = label;
            el.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium';

            if (label === 'Good') {
                el.classList.add('bg-emerald-50', 'text-emerald-700');
            } else if (label === 'Damaged') {
                el.classList.add('bg-rose-50', 'text-rose-700');
            } else {
                el.classList.add('bg-slate-100', 'text-slate-600');
            }
        }

        function applyEquipmentPlacementBadge(roomType) {
            const el = document.getElementById('eqAssetModal_placement_badge');
            if (!el) return;

            const isStorage = roomType === @json(\App\Support\RoomCategories::STORAGE_TYPE);
            el.textContent = isStorage ? 'Stock' : 'Deployed';
            el.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium';
            el.classList.add(
                ...(isStorage
                    ? ['bg-amber-50', 'text-amber-700']
                    : ['bg-sky-50', 'text-sky-700'])
            );
        }

        function switchEquipmentModalTab(tab) {
            const tabs = ['overview', 'lifecycle', 'activity'];
            tabs.forEach((name) => {
                const button = document.getElementById(`eqAssetModal_tab_${name}`);
                const panel = document.getElementById(`eqAssetModal_panel_${name}`);
                const active = name === tab;

                if (button) {
                    button.classList.toggle('border-[#0025cc]', active);
                    button.classList.toggle('text-[#0025cc]', active);
                    button.classList.toggle('font-semibold', active);
                    button.classList.toggle('border-transparent', !active);
                    button.classList.toggle('text-slate-500', !active);
                    button.classList.toggle('font-medium', !active);
                    button.setAttribute('aria-selected', active ? 'true' : 'false');
                }

                panel?.classList.toggle('hidden', !active);
            });
        }

        function copyEquipmentAssetTag() {
            if (!equipmentModalCurrentAssetTag) return;

            navigator.clipboard?.writeText(equipmentModalCurrentAssetTag).then(() => {
                const btn = document.getElementById('eqAssetModal_copy_tag');
                if (!btn) return;
                const original = btn.innerHTML;
                btn.innerHTML = '<i data-lucide="check" class="h-3.5 w-3.5 text-emerald-600"></i>';
                if (window.lucide) window.lucide.createIcons();
                setTimeout(() => {
                    btn.innerHTML = original;
                    if (window.lucide) window.lucide.createIcons();
                }, 1500);
            });
        }

        function renderEquipmentLifecycleRows(data) {
            const contentEl = document.getElementById('eqAssetModal_lifecycle_content');
            if (!contentEl) return;

            contentEl.innerHTML = '';
            const rows = [
                ['Put in room', formatEquipmentAssetDate(data.deployed_at)],
                ['Last moved', formatEquipmentAssetDate(data.last_moved_at)],
                ['Last maintenance', formatEquipmentAssetDate(data.last_maintenance_at)],
            ];

            if (data.disposed_at) {
                rows.push(['Disposed', formatEquipmentAssetDate(data.disposed_at)]);
            }
            if (data.disposal_reason) {
                rows.push(['Disposal reason', data.disposal_reason]);
            }

            rows.forEach(([label, value]) => {
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between gap-4 px-4 py-3.5';
                row.innerHTML = `<span class="text-sm text-slate-500">${label}</span><span class="text-right text-sm font-medium text-slate-800">${value || '—'}</span>`;
                contentEl.appendChild(row);
            });
        }

        function renderEquipmentActivity(data) {
            const contentEl = document.getElementById('eqAssetModal_activity_content');
            const emptyEl = document.getElementById('eqAssetModal_activity_empty');
            if (!contentEl) return;

            contentEl.innerHTML = '';
            const transfers = Array.isArray(data.transfers) ? data.transfers.slice(0, 6) : [];
            const maintenance = Array.isArray(data.maintenance) ? data.maintenance.slice(0, 6) : [];

            if (!transfers.length && !maintenance.length) {
                contentEl.classList.add('hidden');
                emptyEl?.classList.remove('hidden');
                return;
            }

            emptyEl?.classList.add('hidden');
            contentEl.classList.remove('hidden');

            if (transfers.length) {
                const block = document.createElement('div');
                block.className = 'overflow-hidden rounded-xl border border-slate-200 bg-white';
                block.innerHTML = '<div class="border-b border-slate-100 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Recent moves</div>';
                transfers.forEach((move, index) => {
                    const item = document.createElement('div');
                    item.className = `px-4 py-3.5 ${index ? 'border-t border-slate-100' : ''}`;
                    item.innerHTML = `
                        <p class="text-sm font-medium text-slate-800">${move.from_room_name || 'Unassigned'} → ${move.to_room_name || '—'}</p>
                        <p class="mt-1 text-xs text-slate-400">${formatEquipmentAssetDate(move.created_at)}</p>
                    `;
                    block.appendChild(item);
                });
                contentEl.appendChild(block);
            }

            if (maintenance.length) {
                const block = document.createElement('div');
                block.className = 'overflow-hidden rounded-xl border border-slate-200 bg-white';
                block.innerHTML = '<div class="border-b border-slate-100 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Maintenance</div>';
                maintenance.forEach((row, index) => {
                    const item = document.createElement('div');
                    item.className = `px-4 py-3.5 ${index ? 'border-t border-slate-100' : ''}`;
                    item.innerHTML = `
                        <p class="truncate text-sm font-medium text-slate-800">${row.findings || row.status || 'Maintenance'}</p>
                        <p class="mt-1 text-xs text-slate-400">${formatEquipmentAssetDate(row.at)}</p>
                    `;
                    block.appendChild(item);
                });
                contentEl.appendChild(block);
            }
        }

        function renderEquipmentModalLifecycle(data) {
            const loadingEl = document.getElementById('eqAssetModal_lifecycle_loading');
            const contentEl = document.getElementById('eqAssetModal_lifecycle_content');
            const emptyEl = document.getElementById('eqAssetModal_lifecycle_empty');
            const activityLoadingEl = document.getElementById('eqAssetModal_activity_loading');
            const activityContentEl = document.getElementById('eqAssetModal_activity_content');
            const activityEmptyEl = document.getElementById('eqAssetModal_activity_empty');

            loadingEl?.classList.add('hidden');
            activityLoadingEl?.classList.add('hidden');
            emptyEl?.classList.add('hidden');
            activityEmptyEl?.classList.add('hidden');

            renderEquipmentLifecycleRows(data);
            contentEl?.classList.remove('hidden');

            renderEquipmentActivity(data);
        }

        async function loadEquipmentModalLifecycle(equipmentId) {
            const loadingEl = document.getElementById('eqAssetModal_lifecycle_loading');
            const contentEl = document.getElementById('eqAssetModal_lifecycle_content');
            const emptyEl = document.getElementById('eqAssetModal_lifecycle_empty');
            const activityLoadingEl = document.getElementById('eqAssetModal_activity_loading');
            const activityContentEl = document.getElementById('eqAssetModal_activity_content');
            const activityEmptyEl = document.getElementById('eqAssetModal_activity_empty');
            const requestId = ++equipmentModalLifecycleRequest;

            loadingEl?.classList.remove('hidden');
            activityLoadingEl?.classList.remove('hidden');
            contentEl?.classList.add('hidden');
            activityContentEl?.classList.add('hidden');
            emptyEl?.classList.add('hidden');
            activityEmptyEl?.classList.add('hidden');
            if (contentEl) contentEl.innerHTML = '';
            if (activityContentEl) activityContentEl.innerHTML = '';

            if (!equipmentId) {
                loadingEl?.classList.add('hidden');
                activityLoadingEl?.classList.add('hidden');
                emptyEl?.classList.remove('hidden');
                activityEmptyEl?.classList.remove('hidden');
                return;
            }

            try {
                const response = await fetch(`/maintenance/equipment/lifecycle/${equipmentId}`, {
                    headers: { Accept: 'application/json' },
                });

                if (requestId !== equipmentModalLifecycleRequest) return;

                if (!response.ok) {
                    throw new Error('Failed to load lifecycle');
                }

                const data = await response.json();
                if (requestId !== equipmentModalLifecycleRequest) return;

                renderEquipmentModalLifecycle(data);
            } catch (error) {
                if (requestId !== equipmentModalLifecycleRequest) return;
                loadingEl?.classList.add('hidden');
                activityLoadingEl?.classList.add('hidden');
                contentEl?.classList.add('hidden');
                activityContentEl?.classList.add('hidden');
                emptyEl?.classList.remove('hidden');
                activityEmptyEl?.classList.remove('hidden');
            }
        }

        function openEquipmentModal(asset) {
            if (!asset || typeof asset !== 'object') return;

            equipmentModalCurrentAssetTag = asset.asset_tag || '';
            const displayName = asset.name || 'Equipment';

            setEquipmentModalText('eqAssetModal_drawer_subtitle', `Review ${displayName} information and lifecycle.`);
            setEquipmentModalText('eqAssetModal_profile_name', displayName);
            setEquipmentModalText('eqAssetModal_meta_tag', asset.asset_tag);
            setEquipmentModalText('eqAssetModal_meta_serial', asset.serial_number);
            setEquipmentModalText('eqAssetModal_meta_room', asset.room_name);
            setEquipmentModalText('eqAssetModal_brand', asset.brand);
            setEquipmentModalText('eqAssetModal_model', asset.model);
            setEquipmentModalText('eqAssetModal_category', asset.category_name);
            setEquipmentModalText('eqAssetModal_quantity', String(asset.quantity ?? 1));
            setEquipmentModalText('eqAssetModal_tracking_mode', asset.tracking_mode || 'Individual');
            setEquipmentModalText('eqAssetModal_condition', asset.condition);
            setEquipmentModalText('eqAssetModal_status', asset.inventory_status);
            setEquipmentModalText('eqAssetModal_warranty', formatEquipmentAssetDate(asset.warranty_expiration));
            setEquipmentModalText('eqAssetModal_acquired_date', formatEquipmentAssetDate(asset.acquired_date));
            setEquipmentModalText('eqAssetModal_room', asset.room_name);
            setEquipmentModalText('eqAssetModal_zone', asset.placement_zone || asset.location);

            const categoryBadge = document.getElementById('eqAssetModal_category_badge');
            if (categoryBadge) {
                categoryBadge.textContent = asset.category_name || 'Uncategorized';
            }

            applyEquipmentStatusBadge(asset.inventory_status);
            applyEquipmentConditionBadge(asset.condition);
            applyEquipmentPlacementBadge(asset.room_type);
            switchEquipmentModalTab('overview');

            const profileLink = document.getElementById('eqAssetModal_profile_link');
            if (profileLink) {
                const baseUrl = (asset.view_url || `/maintenance/equipment/view/${asset.id}`).split('?')[0];
                const returnParam = encodeURIComponent(window.location.href);
                profileLink.href = `${baseUrl}?return=${returnParam}`;
            }

            const imageUrl = asset.image_url || '';
            const imageWrap = document.getElementById('modal_image_wrap');
            const image = document.getElementById('modal_image');
            const iconWrap = document.getElementById('modal_layout_icon_wrap');
            const icon = document.getElementById('modal_layout_icon');

            if (imageUrl) {
                image.src = imageUrl;
                image.alt = displayName;
                imageWrap.classList.remove('hidden');
                iconWrap.classList.add('hidden');
                iconWrap.classList.remove('flex');
            } else {
                image.src = '';
                image.alt = '';
                imageWrap.classList.add('hidden');
                iconWrap.classList.remove('hidden');
                iconWrap.classList.add('flex');
                if (icon && window.PrismEquipmentIcons) {
                    icon.innerHTML = window.PrismEquipmentIcons.svg(displayName);
                }
            }

            const modal = document.getElementById('viewEquipmentModal');
            const panel = document.getElementById('viewEquipmentModalPanel');

            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            requestAnimationFrame(() => {
                panel?.classList.remove('translate-x-full');
            });

            loadEquipmentModalLifecycle(asset.id);

            if (window.lucide) window.lucide.createIcons();
        }

        function closeEquipmentModal() {
            equipmentModalLifecycleRequest++;
            const modal = document.getElementById('viewEquipmentModal');
            const panel = document.getElementById('viewEquipmentModalPanel');

            panel?.classList.add('translate-x-full');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';

            setTimeout(() => {
                if (panel?.classList.contains('translate-x-full')) {
                    modal.classList.add('hidden');
                }
            }, 300);
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                const modal = document.getElementById('viewEquipmentModal');
                if (modal && !modal.classList.contains('hidden')) {
                    closeEquipmentModal();
                }
            }
        });
    </script>

    <script>
        function inventoryAddEquipment() {
            return {
                open: false,
                step: 1,
                fullscreen: false,
                tracking: 'Individual',
                name: '',
                category: '',
                categoryManual: false,
                room: @json((string) (old('equipment_room_id', $defaultStorageRoomId ?? ''))),
                quantity: 1,
                condition: 'Good',
                brand: '',
                model: '',
                warranty: '',
                assetTag: '',
                assetTagManual: false,
                serial: '',
                items: [],
                errors: {},
                formError: '',
                imagePreview: null,
                onImageChange(event) {
                    const file = event.target.files?.[0];
                    if (this.imagePreview) {
                        URL.revokeObjectURL(this.imagePreview);
                    }
                    this.imagePreview = file ? URL.createObjectURL(file) : null;
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                clearImage() {
                    if (this.imagePreview) {
                        URL.revokeObjectURL(this.imagePreview);
                    }
                    this.imagePreview = null;
                    if (this.$refs.imageInput) {
                        this.$refs.imageInput.value = '';
                    }
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                onSharedPhotoModeChange() {
                    if (this.needsItemStep()) {
                        this.clearImage();
                    }
                },
                onItemImageChange(index, event) {
                    const item = this.items[index];
                    if (!item) return;
                    const file = event.target.files?.[0] || null;
                    if (item._imagePreview) {
                        URL.revokeObjectURL(item._imagePreview);
                    }
                    item._imageFile = file;
                    item._imagePreview = file ? URL.createObjectURL(file) : null;
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                clearItemImage(index) {
                    const item = this.items[index];
                    if (!item) return;
                    if (item._imagePreview) {
                        URL.revokeObjectURL(item._imagePreview);
                    }
                    item._imagePreview = null;
                    item._imageFile = null;
                    const input = document.querySelector(`[data-item-image-index="${index}"]`);
                    if (input) input.value = '';
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                clearAllItemImages() {
                    (this.items || []).forEach((item, index) => {
                        if (item?._imagePreview) {
                            URL.revokeObjectURL(item._imagePreview);
                        }
                        if (item) {
                            item._imagePreview = null;
                            item._imageFile = null;
                        }
                        const input = document.querySelector(`[data-item-image-index="${index}"]`);
                        if (input) input.value = '';
                    });
                },
                restoreItemImageInputs() {
                    (this.items || []).forEach((item, index) => {
                        if (!item?._imageFile) return;
                        const input = document.querySelector(`[data-item-image-index="${index}"]`);
                        if (!input) return;
                        try {
                            const transfer = new DataTransfer();
                            transfer.items.add(item._imageFile);
                            input.files = transfer.files;
                        } catch (e) {
                            // Browser may reject DataTransfer assignment; native input value still used when unchanged.
                        }
                    });
                },
                needsItemStep() {
                    return this.tracking === 'Individual' && Number(this.quantity) > 1;
                },
                clearError(field) {
                    if (!this.errors[field]) return;
                    const next = { ...this.errors };
                    delete next[field];
                    this.errors = next;
                    this.formError = '';
                },
                clearErrors() {
                    this.errors = {};
                    this.formError = '';
                },
                validateStep1() {
                    const next = {};
                    if (!String(this.name || '').trim()) {
                        next.name = 'Equipment name is required.';
                    }
                    if (!String(this.category || '').trim()) {
                        next.category = 'Please select a category.';
                    }
                    if (!String(this.room || '').trim()) {
                        next.room = 'Please select a room.';
                    }
                    const qty = Number(this.quantity);
                    if (!Number.isFinite(qty) || qty < 1) {
                        next.quantity = 'Quantity must be at least 1.';
                    } else if (qty > 200) {
                        next.quantity = 'Quantity cannot exceed 200.';
                    }
                    this.errors = next;
                    this.formError = Object.keys(next).length
                        ? 'Please fix the highlighted fields before continuing.'
                        : '';
                    if (Object.keys(next).length) {
                        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                    }
                    return Object.keys(next).length === 0;
                },
                validateItems() {
                    const tags = {};
                    const serials = {};
                    for (let i = 0; i < this.items.length; i++) {
                        const tag = String(this.items[i].equipment_asset_tag || '').trim().toLowerCase();
                        const serial = String(this.items[i].equipment_serial_number || '').trim().toLowerCase();
                        if (tag) {
                            if (tags[tag] !== undefined) {
                                this.formError = `Duplicate asset tag on rows ${tags[tag] + 1} and ${i + 1}.`;
                                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                                return false;
                            }
                            tags[tag] = i;
                        }
                        if (serial) {
                            if (serials[serial] !== undefined) {
                                this.formError = `Duplicate serial number on rows ${serials[serial] + 1} and ${i + 1}.`;
                                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                                return false;
                            }
                            serials[serial] = i;
                        }
                    }
                    this.formError = '';
                    return true;
                },
                onNameInput() {
                    if (!String(this.name || '').trim()) {
                        this.categoryManual = false;
                        this.category = '';
                        return;
                    }
                    if (this.categoryManual) {
                        return;
                    }
                    if (typeof detectEquipmentCategoryId === 'function') {
                        this.category = detectEquipmentCategoryId(this.name) || '';
                        if (this.category) this.clearError('category');
                    }
                },
                onCategoryChange() {
                    if (
                        typeof detectEquipmentCategoryId === 'function'
                        && String(this.category) === String(detectEquipmentCategoryId(this.name) || '')
                    ) {
                        return;
                    }
                    this.categoryManual = true;
                },
                reset() {
                    this.step = 1;
                    this.fullscreen = false;
                    this.tracking = 'Individual';
                    this.name = '';
                    this.category = '';
                    this.categoryManual = false;
                    this.room = @json((string) ($defaultStorageRoomId ?? ''));
                    this.quantity = 1;
                    this.condition = 'Good';
                    this.brand = '';
                    this.model = '';
                    this.warranty = '';
                    this.assetTag = '';
                    this.assetTagManual = false;
                    this.serial = '';
                    this.clearAllItemImages();
                    this.items = [];
                    this.clearImage();
                    this.clearErrors();
                    const borrowable = document.getElementById('add_equipment_borrowable');
                    if (borrowable) borrowable.checked = false;
                },
                show() {
                    this.reset();
                    @if (old('equipment_room_id'))
                        this.room = @json((string) old('equipment_room_id'));
                    @endif
                    this.open = true;
                    this.$nextTick(() => {
                        document.getElementById('add_equipment_name')?.dispatchEvent(new Event('equipment-category-reset'));
                        if (window.lucide) window.lucide.createIcons();
                    });
                },
                close() {
                    this.open = false;
                    this.reset();
                    document.body.style.overflow = '';
                },
                slug() {
                    return this.assetTagPart(this.name, 'EQ');
                },
                assetTagPart(value, fallback) {
                    return String(value || '')
                        .toUpperCase()
                        .replace(/[^A-Z0-9]+/g, '')
                        || fallback;
                },
                selectedRoomName() {
                    const select = document.getElementById('add_equipment_room');
                    const option = select?.selectedOptions?.[0];
                    return option?.text?.trim() || '';
                },
                shouldAutoAssetTag() {
                    return this.tracking === 'Bulk' || Number(this.quantity) === 1;
                },
                syncAssetTag() {
                    if (this.assetTagManual || !this.shouldAutoAssetTag()) {
                        return;
                    }
                    const roomName = this.selectedRoomName();
                    const equipmentName = String(this.name || '').trim();
                    if (!roomName || !equipmentName || typeof window.equipmentAssetTags?.generate !== 'function') {
                        this.assetTag = '';
                        return;
                    }
                    window.equipmentAssetTags.resetReserved();
                    const tags = window.equipmentAssetTags.generate(roomName, equipmentName, 1);
                    this.assetTag = tags[0] || '';
                },
                buildAssetTag(index) {
                    const roomName = this.selectedRoomName();
                    const equipmentName = String(this.name || '').trim();
                    if (!roomName || !equipmentName || typeof window.equipmentAssetTags?.generate !== 'function') {
                        return '';
                    }
                    window.equipmentAssetTags.resetReserved();
                    const tags = window.equipmentAssetTags.generate(roomName, equipmentName, index + 1);
                    return tags[index] || tags[tags.length - 1] || '';
                },
                buildItems() {
                    const qty = Math.min(200, Math.max(1, Number(this.quantity) || 1));
                    this.quantity = qty;
                    const previous = this.items || [];
                    const roomName = this.selectedRoomName();
                    const equipmentName = String(this.name || '').trim();

                    window.equipmentAssetTags?.resetReserved?.();

                    let generated = [];
                    if (roomName && equipmentName && typeof window.equipmentAssetTags?.generate === 'function') {
                        generated = window.equipmentAssetTags.generate(roomName, equipmentName, qty);
                    }

                    this.items = Array.from({ length: qty }, (_, i) => ({
                        equipment_asset_tag: previous[i]?._tagManual
                            ? previous[i].equipment_asset_tag
                            : (generated[i] || this.buildAssetTag(i)),
                        equipment_serial_number: previous[i]?.equipment_serial_number ?? '',
                        equipment_brand_name: this.brand || '',
                        equipment_model: this.model || '',
                        equipment_condition_status: this.condition,
                        equipment_warranty_expiration: this.warranty || '',
                        _tagManual: previous[i]?._tagManual || false,
                        _imagePreview: previous[i]?._imagePreview || null,
                        _imageFile: previous[i]?._imageFile || null,
                    }));
                },
                regenerateAssetTags() {
                    window.equipmentAssetTags?.resetReserved?.();
                    const roomName = this.selectedRoomName();
                    const equipmentName = String(this.name || '').trim();
                    const generated = (roomName && equipmentName && typeof window.equipmentAssetTags?.generate === 'function')
                        ? window.equipmentAssetTags.generate(roomName, equipmentName, this.items.length)
                        : [];

                    this.items = this.items.map((item, i) => ({
                        ...item,
                        equipment_asset_tag: generated[i] || this.buildAssetTag(i),
                        _tagManual: false,
                    }));
                    this.$nextTick(() => this.restoreItemImageInputs());
                },
                applyDefaults() {
                    this.items = this.items.map((item) => ({
                        ...item,
                        equipment_brand_name: this.brand || '',
                        equipment_model: this.model || '',
                        equipment_condition_status: this.condition,
                        equipment_warranty_expiration: this.warranty || '',
                    }));
                },
                goToItems() {
                    if (!this.validateStep1()) {
                        return;
                    }
                    this.clearImage();
                    this.buildItems();
                    this.step = 2;
                    this.clearErrors();
                    this.$nextTick(() => {
                        this.restoreItemImageInputs();
                        if (window.lucide) window.lucide.createIcons();
                    });
                },
                prepareSubmit(event) {
                    if (this.needsItemStep() && this.step !== 2) {
                        event.preventDefault();
                        this.goToItems();
                        return;
                    }
                    if (!this.validateStep1()) {
                        event.preventDefault();
                        return;
                    }
                    if (!this.needsItemStep()) {
                        this.syncAssetTag();
                    }
                    if (this.step === 2 && !this.validateItems()) {
                        event.preventDefault();
                        return;
                    }
                    if (this.step === 2) {
                        this.restoreItemImageInputs();
                    }
                },
            };
        }

        function openAddEquipmentModal() {
            const modal = document.getElementById('addEquipmentModal');
            if (modal && modal._x_dataStack && modal._x_dataStack[0]) {
                modal._x_dataStack[0].show();
                return;
            }
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
        }

        function closeAddEquipmentModal() {
            const modal = document.getElementById('addEquipmentModal');
            if (modal && modal._x_dataStack && modal._x_dataStack[0]) {
                modal._x_dataStack[0].close();
                return;
            }
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
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
            purchaseDate,
            acquiredDate,
            purchaseCost,
            warranty,
            borrowable,
            imageUrl
        ) {
            document.getElementById("editEquipmentForm").action =
                "/maintenance/equipment/update/" + id;

            document.getElementById("edit_equipment_name").value = name;

            document.getElementById("edit_asset_tag").value = assetTag;

            document.getElementById("edit_brand").value = brand;

            document.getElementById("edit_model").value = model;

            document.getElementById("edit_serial").value = serial;

            document.getElementById("edit_purchase_date").value = purchaseDate || "";

            document.getElementById("edit_acquired_date").value = acquiredDate || "";

            document.getElementById("edit_purchase_cost").value = purchaseCost || "";

            document.getElementById("edit_warranty_expiration").value = warranty;

            document.getElementById("edit_quantity").value = quantity;

            setEqSelectValue("edit_condition", condition);

            setEqSelectValue("edit_status", status);

            setEqSelectValue("edit_category", category);

            setEqSelectValue("edit_room", room);

            document.getElementById("edit_equipment_borrowable").checked =
                borrowable == 1;

            setEditEquipmentImage(imageUrl, name);

            document
                .getElementById("editEquipmentModal")
                .classList.remove("hidden");

            document.getElementById("editEquipmentModal").classList.add("flex");

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function setEditEquipmentImage(imageUrl, name) {
            const preview = document.getElementById("edit_image_preview");
            const previewButton = document.getElementById("edit_image_button");
            const placeholder = document.getElementById("edit_image_placeholder");
            const layoutIcon = document.getElementById("edit_layout_icon");
            const hint = document.getElementById("edit_image_hint");
            const clearButton = document.getElementById("edit_clear_image");
            const fileInput = document.getElementById("edit_equipment_image");
            const removeInput = document.getElementById("edit_remove_image");
            const equipmentName = name || document.getElementById("edit_equipment_name")?.value || "";

            if (fileInput) {
                fileInput.value = "";
            }
            if (removeInput) {
                removeInput.value = "0";
            }

            if (layoutIcon && window.PrismEquipmentIcons) {
                layoutIcon.innerHTML = window.PrismEquipmentIcons.svg(equipmentName);
            }

            if (imageUrl) {
                preview.src = imageUrl;
                previewButton.classList.remove("hidden");
                placeholder.classList.add("hidden");
                hint?.classList.remove("hidden");
                clearButton.classList.remove("hidden");
                clearButton.classList.add("inline-flex");
            } else {
                preview.src = "";
                previewButton.classList.add("hidden");
                placeholder.classList.remove("hidden");
                hint?.classList.add("hidden");
                clearButton.classList.add("hidden");
                clearButton.classList.remove("inline-flex");
            }

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            const fileInput = document.getElementById("edit_equipment_image");
            const clearButton = document.getElementById("edit_clear_image");

            fileInput?.addEventListener("change", function (event) {
                const file = event.target.files?.[0];
                const preview = document.getElementById("edit_image_preview");
                const previewButton = document.getElementById("edit_image_button");
                const placeholder = document.getElementById("edit_image_placeholder");
                const removeInput = document.getElementById("edit_remove_image");
                const removeButton = document.getElementById("edit_clear_image");

                if (removeInput) {
                    removeInput.value = "0";
                }

                if (!file) {
                    return;
                }

                preview.src = URL.createObjectURL(file);
                previewButton.classList.remove("hidden");
                placeholder.classList.add("hidden");
                document.getElementById("edit_image_hint")?.classList.remove("hidden");
                removeButton.classList.remove("hidden");
                removeButton.classList.add("inline-flex");

                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });

            clearButton?.addEventListener("click", function () {
                setEditEquipmentImage("");
                document.getElementById("edit_remove_image").value = "1";
            });

            document.getElementById("edit_equipment_name")?.addEventListener("input", function () {
                const previewButton = document.getElementById("edit_image_button");
                const layoutIcon = document.getElementById("edit_layout_icon");
                if (!layoutIcon || !window.PrismEquipmentIcons) {
                    return;
                }
                if (previewButton && !previewButton.classList.contains("hidden")) {
                    return;
                }
                layoutIcon.innerHTML = window.PrismEquipmentIcons.svg(this.value);
            });
        });

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

        function openInventoryDisposeModal(equipmentId, equipmentName, roomName) {
            document.getElementById("inventoryDisposeEquipmentId").value = equipmentId;
            document.getElementById("inventoryDisposeEquipmentName").textContent = equipmentName || "Equipment";
            document.getElementById("inventoryDisposeLocation").value = roomName || "";
            document.getElementById("inventoryDisposeReason").value = "";

            const modal = document.getElementById("inventoryDisposeModal");
            modal.classList.remove("hidden");
            modal.classList.add("flex");

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function closeInventoryDisposeModal() {
            const modal = document.getElementById("inventoryDisposeModal");
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }

        function openInventoryTransferToModal(equipmentId, equipmentName, roomName, assetTag) {
            document.getElementById("inventoryTransferEquipmentId").value = equipmentId;
            document.getElementById("inventoryTransferEquipmentName").textContent = equipmentName || "Equipment";
            document.getElementById("inventoryTransferCurrentRoom").textContent = roomName || "—";
            document.getElementById("inventoryTransferAssetTag").textContent = assetTag || "No asset tag";
            document.getElementById("inventoryTransferRoomId").value = "";
            document.getElementById("inventoryTransferRemarks").value = "";

            const modal = document.getElementById("inventoryTransferToModal");
            modal.classList.remove("hidden");
            modal.classList.add("flex");
            if (window.lucide) window.lucide.createIcons();
        }

        function closeInventoryTransferToModal() {
            const modal = document.getElementById("inventoryTransferToModal");
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }

        function openInventoryTransferAllModal() {
            const count = Number(document.getElementById("inventoryTransferAllCount")?.dataset.count || 0);
            if (!count) return;

            document.getElementById("inventoryTransferAllRoomId").value = "";
            document.getElementById("inventoryTransferAllRemarks").value = "";

            const modal = document.getElementById("inventoryTransferAllModal");
            modal.classList.remove("hidden");
            modal.classList.add("flex");
            if (window.lucide) window.lucide.createIcons();
        }

        function closeInventoryTransferAllModal() {
            const modal = document.getElementById("inventoryTransferAllModal");
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }

        function getInventorySelectedIds() {
            return Array.from(document.querySelectorAll('.inventory-stock-checkbox:checked'))
                .map((input) => Number(input.value))
                .filter((id) => Number.isFinite(id) && id > 0);
        }

        function syncInventoryTransferSelection() {
            const selected = getInventorySelectedIds();
            const countEl = document.getElementById('inventoryTransferSelectedCount');
            const btn = document.getElementById('inventoryTransferSelectedBtn');
            const selectAll = document.getElementById('inventorySelectAllPage');
            const boxes = document.querySelectorAll('.inventory-stock-checkbox');

            if (countEl) countEl.textContent = String(selected.length);
            if (btn) btn.disabled = selected.length === 0;

            if (selectAll && boxes.length) {
                selectAll.checked = selected.length === boxes.length;
                selectAll.indeterminate = selected.length > 0 && selected.length < boxes.length;
            }
        }

        function toggleInventorySelectAllPage(source) {
            document.querySelectorAll('.inventory-stock-checkbox').forEach((input) => {
                input.checked = !!source.checked;
            });
            syncInventoryTransferSelection();
        }

        function openInventoryTransferSelectedModal() {
            const ids = getInventorySelectedIds();
            if (!ids.length) return;

            const container = document.getElementById('inventoryTransferSelectedIds');
            if (!container) return;

            container.innerHTML = '';
            ids.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'equipment_ids[]';
                input.value = String(id);
                container.appendChild(input);
            });

            const countLabel = document.getElementById('inventoryTransferSelectedModalCount');
            if (countLabel) countLabel.textContent = String(ids.length);

            const summary = document.getElementById('inventoryTransferSelectedSummary');
            if (summary) {
                const names = Array.from(document.querySelectorAll('.inventory-stock-checkbox:checked'))
                    .map((input) => input.dataset.name || 'Item')
                    .slice(0, 6);
                const extra = ids.length > names.length ? ` +${ids.length - names.length} more` : '';
                summary.textContent = names.join(', ') + extra;
            }

            document.getElementById('inventoryTransferSelectedRoomId').value = '';
            document.getElementById('inventoryTransferSelectedRemarks').value = '';

            const modal = document.getElementById('inventoryTransferSelectedModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (window.lucide) window.lucide.createIcons();
        }

        function closeInventoryTransferSelectedModal() {
            const modal = document.getElementById('inventoryTransferSelectedModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.addEventListener('DOMContentLoaded', () => {
            syncInventoryTransferSelection();
        });
    </script>

    @if ($isStockPage)
    <div
        id="inventoryTransferSelectedModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                        <i data-lucide="check-square" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer selected</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Deploy
                        <span id="inventoryTransferSelectedModalCount" class="font-semibold text-slate-800">0</span>
                        selected item(s) to one classroom or lab. Then select another set for a different room.
                    </p>
                    <p id="inventoryTransferSelectedSummary" class="mt-2 text-xs text-slate-400"></p>
                </div>
                <button type="button" onclick="closeInventoryTransferSelectedModal()" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form action="/maintenance/equipment/transfer-batch" method="POST">
                @csrf
                <div id="inventoryTransferSelectedIds"></div>

                <div class="border-y border-slate-100 px-6 py-5 space-y-5">
                    <div>
                        <label for="inventoryTransferSelectedRoomId" class="mb-2 block text-sm font-medium text-slate-700">Destination room</label>
                        <select id="inventoryTransferSelectedRoomId" name="room_id" required class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
                            <option value="">Select classroom / lab</option>
                            @foreach (($deployRooms ?? collect()) as $room)
                                <option value="{{ $room->room_id }}">{{ $room->room_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="inventoryTransferSelectedRemarks" class="mb-2 block text-sm font-medium text-slate-700">Remarks (optional)</label>
                        <textarea id="inventoryTransferSelectedRemarks" name="remarks" rows="3" placeholder="e.g. 5 mice for Room 204" class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4">
                    <button type="button" onclick="closeInventoryTransferSelectedModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="h-10 rounded-xl bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Transfer selected</button>
                </div>
            </form>
        </div>
    </div>

    <div
        id="inventoryTransferToModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                        <i data-lucide="move" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer to room</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Deploy this stock item to a classroom or lab. It lands in Holding until placed on the floor.
                    </p>
                </div>
                <button type="button" onclick="closeInventoryTransferToModal()" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form action="/maintenance/equipment/transfer" method="POST">
                @csrf
                <input type="hidden" name="equipment_id" id="inventoryTransferEquipmentId" value="" />

                <div class="border-y border-slate-100 px-6 py-5 space-y-5">
                    <div class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-slate-50/60">
                        <div class="flex items-center justify-between gap-6 px-4 py-3.5">
                            <span class="shrink-0 text-sm text-slate-500">Equipment</span>
                            <span id="inventoryTransferEquipmentName" class="min-w-0 truncate text-right text-sm font-semibold text-slate-950"></span>
                        </div>
                        <div class="flex items-center justify-between gap-6 px-4 py-3.5">
                            <span class="shrink-0 text-sm text-slate-500">Asset tag</span>
                            <span id="inventoryTransferAssetTag" class="min-w-0 truncate text-right text-sm font-medium text-slate-800"></span>
                        </div>
                        <div class="flex items-center justify-between gap-6 px-4 py-3.5">
                            <span class="shrink-0 text-sm text-slate-500">Current room</span>
                            <span id="inventoryTransferCurrentRoom" class="min-w-0 truncate text-right text-sm font-medium text-slate-800"></span>
                        </div>
                    </div>

                    <div>
                        <label for="inventoryTransferRoomId" class="mb-2 block text-sm font-medium text-slate-700">Destination room</label>
                        <select id="inventoryTransferRoomId" name="room_id" required class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
                            <option value="">Select classroom / lab</option>
                            @foreach (($deployRooms ?? collect()) as $room)
                                <option value="{{ $room->room_id }}">{{ $room->room_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="inventoryTransferRemarks" class="mb-2 block text-sm font-medium text-slate-700">Remarks (optional)</label>
                        <textarea id="inventoryTransferRemarks" name="remarks" rows="3" placeholder="e.g. Deployed for Room 204 use" class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4">
                    <button type="button" onclick="closeInventoryTransferToModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="h-10 rounded-xl bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Transfer</button>
                </div>
            </form>
        </div>
    </div>

    <div
        id="inventoryTransferAllModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                        <i data-lucide="boxes" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer all stock</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Deploy all
                        <span
                            id="inventoryTransferAllCount"
                            data-count="{{ count($stockTransferIds ?? []) }}"
                            class="font-semibold text-slate-800"
                        >{{ number_format(count($stockTransferIds ?? [])) }}</span>
                        filtered storage item(s) to one classroom or lab.
                    </p>
                </div>
                <button type="button" onclick="closeInventoryTransferAllModal()" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form action="/maintenance/equipment/transfer-batch" method="POST">
                @csrf
                @foreach (($stockTransferIds ?? []) as $transferId)
                    <input type="hidden" name="equipment_ids[]" value="{{ $transferId }}" />
                @endforeach

                <div class="border-y border-slate-100 px-6 py-5 space-y-5">
                    <div>
                        <label for="inventoryTransferAllRoomId" class="mb-2 block text-sm font-medium text-slate-700">Destination room</label>
                        <select id="inventoryTransferAllRoomId" name="room_id" required class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
                            <option value="">Select classroom / lab</option>
                            @foreach (($deployRooms ?? collect()) as $room)
                                <option value="{{ $room->room_id }}">{{ $room->room_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="inventoryTransferAllRemarks" class="mb-2 block text-sm font-medium text-slate-700">Remarks (optional)</label>
                        <textarea id="inventoryTransferAllRemarks" name="remarks" rows="3" placeholder="e.g. Deployed filtered stock to Computer Laboratory 1" class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4">
                    <button type="button" onclick="closeInventoryTransferAllModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="h-10 rounded-xl bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Transfer all</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div
        id="inventoryDisposeModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                        <i data-lucide="archive-x" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">
                        Dispose equipment
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Move this item from inventory into the Disposal module. This is permanent until restored from Disposal.
                    </p>
                </div>
                <button
                    type="button"
                    onclick="closeInventoryDisposeModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form action="/maintenance/disposal/store" method="POST">
                @csrf
                <input type="hidden" name="equipment_id" id="inventoryDisposeEquipmentId" value="" />

                <div class="border-y border-slate-100 px-6 py-5">
                    <div class="space-y-5">
                        <div>
                            <p class="mb-1 text-sm font-medium text-slate-700">Equipment</p>
                            <p id="inventoryDisposeEquipmentName" class="rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900"></p>
                        </div>

                        <div>
                            <label for="inventoryDisposeReason" class="mb-2 block text-sm font-medium text-slate-700">
                                Reason
                            </label>
                            <textarea
                                id="inventoryDisposeReason"
                                name="reason"
                                rows="3"
                                required
                                placeholder="Why is this equipment being disposed?"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            ></textarea>
                        </div>

                        <div>
                            <label for="inventoryDisposeLocation" class="mb-2 block text-sm font-medium text-slate-700">
                                Location
                            </label>
                            <input
                                id="inventoryDisposeLocation"
                                name="location"
                                type="text"
                                placeholder="Area / room"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4">
                    <button
                        type="button"
                        onclick="closeInventoryDisposeModal()"
                        class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="h-10 rounded-xl bg-rose-600 px-5 text-sm font-medium text-white transition hover:bg-rose-700"
                    >
                        Dispose
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('layouts.partials.equipment-layout-icons')
    @include('layouts.partials.equipment-asset-tag')
    @include('layouts.partials.equipment-category-detect')
    @include('layouts.partials.equipment-photo-viewer')

    <style>
        .eq-modal-scroll {
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 transparent;
        }

        .eq-modal-scroll::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .eq-modal-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .eq-modal-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .eq-modal-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

@endsection
