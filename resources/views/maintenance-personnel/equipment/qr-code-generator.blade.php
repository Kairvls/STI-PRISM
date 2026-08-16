@extends ("layouts.maintenance-layout")

@section ("title", "QR Code Tools")

@section ("content")
    <div class="space-y-6">
        {{-- ===================================================== --}}
        {{-- QR TOOLS DASHBOARD --}}
        {{-- ===================================================== --}}

        <div
            class="mb-6 mt-6 overflow-hidden rounded-lg border-y border-slate-300 bg-gray-100 shadow-sm"
        >
            <div
                class="grid grid-cols-1 divide-y divide-slate-200
                    md:grid-cols-2 md:divide-y-0
                    xl:grid-cols-[380px_1fr_1fr_1fr]"
            >

                {{-- ================================================= --}}
                {{-- TOTAL EQUIPMENT --}}
                {{-- ================================================= --}}

                <div class="flex items-center justify-between px-8 py-6">

                    <div class="flex flex-col">

                        <p class="text-sm font-medium text-slate-500">
                            Total Equipment
                        </p>


                        <h2 class="mt-2 text-5xl font-medium text-slate-900">

                            {{ number_format($totalQrEquipment) }}

                        </h2>


                        <p class="mt-3 text-sm">

                            <span class="font-semibold text-slate-900">

                                {{ number_format($generatedQrCodes) }}

                            </span>

                            <span class="text-slate-500">
                                QR codes generated
                            </span>

                        </p>

                    </div>


                    {{-- ================================================= --}}
                    {{-- REAL QR COVERAGE GRAPH --}}
                    {{-- GENERATED VS NOT GENERATED --}}
                    {{-- ================================================= --}}

                    @php

                        // =================================================
                        // QR STATUS COUNTS
                        // =================================================

                        $qrStatusCounts = collect([

                            $generatedQrCodes,

                            $notGeneratedQrCodes,

                        ]);


                        // =================================================
                        // GRAPH SETTINGS
                        // =================================================

                        $graphWidth = 300;

                        $graphHeight = 100;

                        $graphTopPadding = 10;

                        $graphBottomPadding = 10;


                        // =================================================
                        // MAXIMUM VALUE
                        // =================================================

                        $maxQrStatusCount =
                            max(
                                1,
                                $qrStatusCounts->max()
                            );


                        // =================================================
                        // NUMBER OF POINTS
                        // =================================================

                        $qrPointCount =
                            $qrStatusCounts->count();


                        // =================================================
                        // BUILD GRAPH POINTS
                        // =================================================

                        $qrGraphPoints =
                            $qrStatusCounts

                                ->values()

                                ->map(function (
                                    $count,
                                    $index
                                ) use (
                                    $graphWidth,
                                    $graphHeight,
                                    $graphTopPadding,
                                    $graphBottomPadding,
                                    $maxQrStatusCount,
                                    $qrPointCount
                                ) {

                                    $x =
                                        $qrPointCount > 1

                                            ? (
                                                $index
                                                / ($qrPointCount - 1)
                                            )
                                            * $graphWidth

                                            : $graphWidth / 2;


                                    $usableHeight =
                                        $graphHeight
                                        - $graphTopPadding
                                        - $graphBottomPadding;


                                    $y =
                                        $graphHeight
                                        - $graphBottomPadding
                                        - (
                                            ($count / $maxQrStatusCount)
                                            * $usableHeight
                                        );


                                    return
                                        round($x, 2)
                                        . ','
                                        . round($y, 2);

                                })

                                ->implode(' ');


                        // =================================================
                        // BUILD AREA POINTS
                        // =================================================

                        $qrGraphAreaPoints =
                            '0,100 '
                            . $qrGraphPoints
                            . ' 300,100';

                    @endphp


                    <div class="ml-6 h-20 w-40 shrink-0">

                        <svg
                            viewBox="0 0 300 100"
                            class="h-full w-full"
                            fill="none"
                            aria-label="QR code generation coverage"
                        >

                            <polygon
                                points="{{ $qrGraphAreaPoints }}"
                                fill="currentColor"
                                fill-opacity=".08"
                                class="text-slate-900"
                            />


                            <polyline
                                points="{{ $qrGraphPoints }}"
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


                {{-- ================================================= --}}
                {{-- GENERATED --}}
                {{-- ================================================= --}}

                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%]
                            border-l border-slate-200 xl:block"
                    ></span>


                    <p class="text-md font-medium text-slate-600">
                        Generated
                    </p>


                    <h2 class="text-5xl font-medium text-slate-900">

                        {{ number_format($generatedQrCodes) }}

                    </h2>


                    <p class="text-base">

                        <span class="font-semibold text-emerald-600">

                            {{
                                number_format(
                                    $generatedQrPercentage,
                                    2
                                )
                            }}%

                        </span>

                        <span class="text-slate-500">
                            of all equipment
                        </span>

                    </p>

                </div>


                {{-- ================================================= --}}
                {{-- NOT GENERATED --}}
                {{-- ================================================= --}}

                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%]
                            border-l border-slate-200 xl:block"
                    ></span>


                    <p class="text-md font-medium text-slate-600">
                        Not Generated
                    </p>


                    <h2 class="text-5xl font-medium text-slate-900">

                        {{ number_format($notGeneratedQrCodes) }}

                    </h2>


                    <p class="text-base">

                        <span
                            class="font-semibold
                            {{
                                $notGeneratedQrCodes > 0
                                    ? 'text-amber-600'
                                    : 'text-emerald-600'
                            }}"
                        >

                            {{
                                number_format(
                                    $notGeneratedQrPercentage,
                                    2
                                )
                            }}%

                        </span>

                        <span class="text-slate-500">
                            of all equipment
                        </span>

                    </p>

                </div>


                {{-- ================================================= --}}
                {{-- TOTAL QR SCANS --}}
                {{-- ================================================= --}}

                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%]
                            border-l border-slate-200 xl:block"
                    ></span>


                    <p class="text-md font-medium text-slate-600">
                        Total QR Scans
                    </p>


                    <h2 class="text-5xl font-medium text-slate-900">

                        {{ number_format($totalQrScans) }}

                    </h2>


                    <p class="text-base">

                        <span class="font-semibold text-slate-900">

                            {{ number_format($totalQrScans) }}

                        </span>

                        <span class="text-slate-500">
                            recorded scan events
                        </span>

                    </p>

                </div>

            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- EQUIPMENT QR CODE LIST --}}
        {{-- ========================================================= --}}

        <section
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
            x-data="qrBatchPrint()"
        >

            {{-- ===================================================== --}}
            {{-- HEADER --}}
            {{-- ===================================================== --}}

            <div
                class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4
                    sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">

                    {{-- HEADER ICON --}}
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center
                            rounded-lg bg-slate-100 text-slate-600"
                    >
                        <i data-lucide="qr-code" class="h-4 w-4"></i>
                    </div>


                    {{-- HEADER TEXT --}}
                    <div>

                        <h2 class="text-sm font-semibold text-slate-900">
                            Equipment QR Codes
                        </h2>

                        <p class="mt-0.5 text-xs text-slate-400">
                            Select multiple rows to print different QR labels on one A4 sheet
                        </p>

                    </div>

                </div>


                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        x-show="selected.length > 0"
                        x-cloak
                        @click="printSelected()"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium text-white transition hover:bg-slate-800"
                    >
                        <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                        Print
                        <span x-text="selected.length"></span>
                        selected
                    </button>

                    {{-- TOTAL COUNT --}}
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

                        {{ $equipment->total() }} total
                    </div>
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

                <div
                    class="flex flex-col gap-3
                        lg:flex-row lg:items-center"
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
                            placeholder="Search equipment, asset tag, serial number, or QR code..."

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
                    {{-- LOCATION FILTER --}}
                    {{-- ================================================= --}}

                    <div class="relative">

                        <select
                            name="room"

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
                                All Locations
                            </option>

                            @foreach ($rooms as $room)

                                <option
                                    value="{{ $room->room_id }}"

                                    @selected(
                                        request('room')
                                        == $room->room_id
                                    )
                                >
                                    {{ $room->room_name }}{{ $room->floor_level ? ' · '.$room->floor_level : '' }}
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
                    {{-- QR STATUS FILTER --}}
                    {{-- ================================================= --}}

                    <div class="relative">

                        <select
                            name="qr_status"

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
                                All QR Status
                            </option>

                            <option
                                value="generated"
                                @selected(request('qr_status') === 'generated')
                            >
                                Generated
                            </option>

                            <option
                                value="not_generated"
                                @selected(request('qr_status') === 'not_generated')
                            >
                                Not Generated
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
                    {{-- APPLY FILTERS --}}
                    {{-- ================================================= --}}

                    <button
                        type="submit"

                        class="inline-flex h-10 items-center
                            justify-center gap-2 rounded-lg
                            bg-slate-950 px-4
                            text-sm font-semibold text-white
                            transition
                            hover:bg-slate-800"
                    >

                        <i
                            data-lucide="sliders-horizontal"
                            class="h-4 w-4"
                        ></i>

                        Apply

                    </button>


                    {{-- ================================================= --}}
                    {{-- CLEAR FILTERS --}}
                    {{-- ONLY SHOW WHEN FILTERS ARE ACTIVE --}}
                    {{-- ================================================= --}}

                    @if (
                        request()->filled('search')
                        || request()->filled('category')
                        || request()->filled('room')
                        || request()->filled('qr_status')
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

                <table class="w-full min-w-[1000px] text-left">

                    {{-- ================================================= --}}
                    {{-- TABLE HEADER --}}
                    {{-- ================================================= --}}

                    <thead class="border-b border-slate-200 bg-slate-50/70">

                        <tr
                            class="text-[12px] font-semibold uppercase
                                tracking-[0.08em] text-black"
                        >

                            <th class="w-12 px-5 py-3">
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                    :checked="allSelectableSelected()"
                                    @change="toggleSelectAll($event.target.checked)"
                                    aria-label="Select all generated QR codes on this page"
                                >
                            </th>

                            <th class="px-5 py-3">
                                Equipment
                            </th>

                            <th class="px-5 py-3">
                                Category
                            </th>

                            <th class="px-5 py-3">
                                Location
                            </th>

                            <th class="px-5 py-3">
                                QR Code
                            </th>

                            <th class="px-5 py-3 text-center">
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
                                // CHECK IF EQUIPMENT HAS A QR CODE
                                $hasQrCode =
                                    !empty($item->equipment_qr_code);
                            @endphp


                            <tr
                                class="group transition-colors
                                    hover:bg-slate-50/70"
                            >

                                {{-- ===================================== --}}
                                {{-- SELECT --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">
                                    @if ($hasQrCode)
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                            value="{{ $item->equipment_qr_code }}"
                                            :checked="isSelected(@js($item->equipment_qr_code))"
                                            @change="toggleCode(@js($item->equipment_qr_code), $event.target.checked)"
                                            aria-label="Select {{ $item->equipment_name }} for printing"
                                        >
                                    @else
                                        <span class="block h-4 w-4"></span>
                                    @endif
                                </td>

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


                                        {{-- EQUIPMENT NAME --}}
                                        <div class="min-w-0">

                                            <p
                                                class="max-w-[260px] truncate
                                                    text-sm font-semibold
                                                    text-slate-800"
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
                                {{-- LOCATION --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">
                                    @if ($item->room_name)
                                        <p class="text-sm font-medium text-slate-800">
                                            {{ $item->room_name }}
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">
                                            {{ collect([$item->building_name, $item->floor_level])->filter()->implode(' · ') ?: 'Room' }}
                                        </p>
                                    @else
                                        <span class="text-sm text-slate-400">Unassigned</span>
                                    @endif
                                </td>



                                {{-- ===================================== --}}
                                {{-- QR CODE STATUS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    @if ($hasQrCode)

                                        <div class="flex items-center gap-3">

                                            {{-- STATUS ICON --}}
                                            <div
                                                class="flex h-8 w-8 shrink-0
                                                    items-center justify-center
                                                    rounded-lg bg-emerald-50
                                                    text-emerald-600"
                                            >
                                                <i
                                                    data-lucide="qr-code"
                                                    class="h-3.5 w-3.5"
                                                ></i>
                                            </div>


                                            {{-- QR INFORMATION --}}
                                            <div class="min-w-0">

                                                <div
                                                    class="flex items-center gap-1.5
                                                        text-[12px] font-medium
                                                        text-emerald-700"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5
                                                            rounded-full
                                                            bg-emerald-500"
                                                    ></span>

                                                    Generated
                                                </div>


                                                <p
                                                    class="mt-1 max-w-[260px]
                                                        truncate font-mono
                                                        text-[11px]
                                                        text-slate-400"
                                                    data-tooltip="{{ $item->equipment_qr_code }}"
                                                >
                                                    {{ $item->equipment_qr_code }}
                                                </p>

                                            </div>

                                        </div>


                                    @else

                                        <div class="flex items-center gap-3">

                                            {{-- STATUS ICON --}}
                                            <div
                                                class="flex h-8 w-8 shrink-0
                                                    items-center justify-center
                                                    rounded-lg bg-slate-100
                                                    text-slate-400"
                                            >
                                                <i
                                                    data-lucide="qr-code"
                                                    class="h-3.5 w-3.5"
                                                ></i>
                                            </div>


                                            <div>

                                                <div
                                                    class="flex items-center gap-1.5
                                                        text-[12px] font-medium
                                                        text-slate-500"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5
                                                            rounded-full
                                                            bg-slate-300"
                                                    ></span>

                                                    Not generated
                                                </div>


                                                <p
                                                    class="mt-1 text-[11px]
                                                        text-slate-400"
                                                >
                                                    Generate a QR code to continue
                                                </p>

                                            </div>

                                        </div>

                                    @endif

                                </td>



                                {{-- ===================================== --}}
                                {{-- ACTIONS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4 text-center">

                                    <div
                                        class="relative inline-block"
                                        x-data="qrActionMenu()"
                                    >

                                        {{-- ACTION MENU BUTTON --}}
                                        <button
                                            type="button"
                                            x-ref="trigger"

                                            @click="toggle()"

                                            @click.outside="open = false"

                                            class="flex h-8 w-8 items-center
                                                justify-center rounded-lg
                                                text-slate-400 transition
                                                hover:bg-slate-200/70
                                                hover:text-slate-700"
                                            data-tooltip="Actions"
                                            aria-label="QR actions"
                                        >
                                            <i
                                                data-lucide="ellipsis"
                                                class="h-4 w-4"
                                            ></i>
                                        </button>



                                        {{-- ACTION DROPDOWN --}}
                                        <div
                                            x-ref="menu"

                                            x-cloak

                                            x-show="open"

                                            x-transition

                                            class="fixed z-[200] w-44 overflow-hidden
                                                rounded-xl
                                                border border-slate-200
                                                bg-white p-1.5
                                                text-left
                                                shadow-lg shadow-slate-900/10"
                                        >

                                            {{-- ================================= --}}
                                            {{-- PREVIEW --}}
                                            {{-- ================================= --}}

                                            @if ($hasQrCode)

                                                <button
                                                    type="button"

                                                    @click="

                                                        openQrModal({

                                                            equipment: @js($item->equipment_name),

                                                            assetTag: @js($item->equipment_asset_tag),

                                                            serial: @js($item->equipment_serial_number),

                                                            room: @js($item->room_name),

                                                            category: @js($item->equipment_category_name),

                                                            qrImage: @js(url('/maintenance/equipment/qr-image/'.$item->equipment_qr_code)),

                                                            qrCode: @js($item->equipment_qr_code)

                                                        })"


                                                    class="flex w-full items-center
                                                        gap-2.5 rounded-lg
                                                        px-3 py-2
                                                        text-xs font-medium
                                                        text-slate-600
                                                        transition
                                                        hover:bg-slate-50
                                                        hover:text-slate-900"
                                                >
                                                    <i
                                                        data-lucide="eye"
                                                        class="h-3.5 w-3.5"
                                                    ></i>

                                                    Preview QR code
                                                </button>

                                            

                                            {{-- ===================================== --}}
                                            {{-- PRINT QR DIRECTLY --}}
                                            {{-- ===================================== --}}

                                            <button
                                                type="button"

                                                @click="
                                                    open = false;

                                                    openQrPrint(
                                                        @js($item->equipment_qr_code)
                                                    );
                                                "

                                                class="flex w-full items-center
                                                    gap-2.5 rounded-lg
                                                    px-3 py-2
                                                    text-xs font-medium
                                                    text-slate-600
                                                    transition
                                                    hover:bg-slate-50
                                                    hover:text-slate-900"
                                            >

                                                <i
                                                    data-lucide="printer"
                                                    class="h-3.5 w-3.5"
                                                ></i>

                                                Print QR

                                            </button>

                                            


                                            {{-- ================================= --}}
                                            {{-- COPY QR ID --}}
                                            {{-- COPY THEN SHOW MINIMAL TOAST --}}
                                            {{-- ================================= --}}

                                            <button
                                                type="button"
                                                @click="
                                                    open = false;
                                                    copyQrId(@js($item->equipment_qr_code));
                                                "
                                                class="flex w-full items-center
                                                    gap-2.5 rounded-lg
                                                    px-3 py-2
                                                    text-xs font-medium
                                                    text-slate-600
                                                    transition
                                                    hover:bg-slate-50
                                                    hover:text-slate-900"
                                            >
                                                <i
                                                    data-lucide="copy"
                                                    class="h-3.5 w-3.5"
                                                ></i>

                                                Copy QR ID
                                            </button>

                                            @endif

                                            <div class="my-1 border-t border-slate-100"></div>



                                            {{-- ================================= --}}
                                            {{-- GENERATE / REGENERATE --}}
                                            {{-- ================================= --}}

                                            <form
                                                method="POST"

                                                action="/maintenance/equipment/qr/generate/{{ $item->equipment_id }}"

                                                @submit.prevent="confirmQrGenerate($el, {{ $hasQrCode ? 'true' : 'false' }}, @js($item->equipment_name))"
                                            >

                                                @csrf


                                                <button
                                                    type="submit"

                                                    class="flex w-full items-center
                                                        gap-2.5 rounded-lg
                                                        px-3 py-2
                                                        text-xs font-medium
                                                        text-slate-600
                                                        transition
                                                        hover:bg-slate-50
                                                        hover:text-slate-900"
                                                >
                                                    <i
                                                        data-lucide="{{
                                                            $hasQrCode
                                                                ? "refresh-cw"
                                                                : "qr-code"
                                                        }}"
                                                        class="h-3.5 w-3.5"
                                                    ></i>


                                                    {{
                                                        $hasQrCode
                                                            ? "Regenerate QR"
                                                            : "Generate QR"
                                                    }}

                                                </button>

                                            </form>



                                            {{-- ================================= --}}
                                            {{-- QR ACTIONS --}}
                                            {{-- ================================= --}}

                                            <!--@if ($hasQrCode)

                                                <div
                                                    class="my-1
                                                        border-t border-slate-100"
                                                ></div>


                                                {{-- PRINT --}}
                                                <button
                                                    type="button"

                                                    @click="
                                                        open = false;

                                                        openQrModal(
                                                            @js($item->equipment_name),
                                                            @js($item->equipment_qr_code)
                                                        );

                                                        $nextTick(() => {
                                                            printQr();
                                                        });
                                                    "

                                                    class="flex w-full items-center
                                                        gap-2.5 rounded-lg
                                                        px-3 py-2
                                                        text-xs font-medium
                                                        text-slate-600
                                                        transition
                                                        hover:bg-slate-50
                                                        hover:text-slate-900"
                                                >
                                                    <i
                                                        data-lucide="printer"
                                                        class="h-3.5 w-3.5"
                                                    ></i>

                                                    Print QR code
                                                </button>



                                                {{-- DOWNLOAD --}}
                                                <button
                                                    type="button"

                                                    @click="
                                                        open = false;

                                                        openQrModal(
                                                            @js($item->equipment_name),
                                                            @js($item->equipment_qr_code)
                                                        );

                                                        $nextTick(() => {
                                                            downloadQr();
                                                        });
                                                    "

                                                    class="flex w-full items-center
                                                        gap-2.5 rounded-lg
                                                        px-3 py-2
                                                        text-xs font-medium
                                                        text-slate-600
                                                        transition
                                                        hover:bg-slate-50
                                                        hover:text-slate-900"
                                                >
                                                    <i
                                                        data-lucide="download"
                                                        class="h-3.5 w-3.5"
                                                    ></i>

                                                    Download QR code
                                                </button>

                                            @endif-->

                                        </div>

                                    </div>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="6"
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
                                                data-lucide="qr-code"
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
                                                || request()->filled('qr_status')

                                                    ? 'No matching equipment'

                                                    : 'No equipment available'
                                            }}

                                        </h3>


                                        {{-- ================================================= --}}
                                        {{-- DESCRIPTION --}}
                                        {{-- ================================================= --}}

                                        <p
                                            class="mt-1.5 max-w-xs
                                                text-xs leading-5 text-slate-400"
                                        >

                                            {{
                                                request()->filled('search')
                                                || request()->filled('category')
                                                || request()->filled('qr_status')

                                                    ? 'No equipment matches your current search or filters. Try adjusting them.'

                                                    : 'Equipment added to the inventory will appear here for QR code management.'
                                            }}

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
        {{-- PLACE AFTER TABLE CONTAINER --}}
        {{-- KEEP INSIDE EQUIPMENT QR CODE SECTION --}}
        {{-- ===================================================== --}}

        @if ($equipment->hasPages())

            <div
                class="flex flex-col gap-3 border-t border-slate-200
                    px-5 py-4 sm:flex-row sm:items-center
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

            {{-- ===================================================== --}}
            {{-- BATCH PRINT BAR --}}
            {{-- ===================================================== --}}

            <div
                x-show="selected.length > 0"
                x-cloak
                x-transition
                class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm text-slate-600">
                    <span class="font-semibold text-slate-900" x-text="selected.length"></span>
                    QR label<span x-show="selected.length !== 1">s</span> selected
                    <span class="text-slate-400">·</span>
                    <span class="text-slate-500">Prints up to 14 per A4 page</span>
                </p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="clearSelection()"
                        class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-500 transition hover:bg-white hover:text-slate-900"
                    >
                        Clear
                    </button>

                    <button
                        type="button"
                        @click="printSelected()"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                    >
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print selected
                    </button>
                </div>
            </div>

        </section>
    </div>

    

    {{-- ===================================================== --}}
    {{-- QR PREVIEW MODAL --}}
    {{-- ===================================================== --}}

    <div
        x-data="qrPreviewModal()"
        x-show="open"
        x-cloak
        x-transition.opacity
        style="display: none;"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[2px]"
    >

        <div
            @click.outside="close()"
            x-transition
            class="flex w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-[0_24px_64px_rgba(15,23,42,.16)]"
        >

            {{-- HEADER --}}
            <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-2">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">
                        Preview QR Label
                    </h2>
                    <p class="mt-1 truncate text-sm text-slate-500" x-text="equipment"></p>
                </div>

                <button
                    type="button"
                    @click="close()"
                    aria-label="Close modal"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            {{-- CONTENT --}}
            <div class="px-6 py-6">
                <div class="flex flex-col items-center">
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <img
                            :src="qrImage"
                            alt="Equipment QR code"
                            class="h-52 w-52 object-contain"
                        >
                    </div>
                    <p
                        class="mt-4 max-w-full break-all text-center font-mono text-sm font-semibold tracking-wide text-slate-900"
                        x-text="qrCode"
                    ></p>
                </div>

                <dl class="mt-8 divide-y divide-slate-100 border-t border-slate-100">
                    <div class="flex items-baseline justify-between gap-6 py-3">
                        <dt class="text-sm text-slate-500">Asset Tag</dt>
                        <dd
                            class="truncate text-sm font-medium text-slate-900"
                            x-text="assetTag || 'Not Assigned'"
                        ></dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-6 py-3">
                        <dt class="text-sm text-slate-500">Serial Number</dt>
                        <dd
                            class="truncate text-sm font-medium text-slate-900"
                            x-text="serial || 'Not Available'"
                        ></dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-6 py-3">
                        <dt class="text-sm text-slate-500">Room</dt>
                        <dd
                            class="truncate text-sm font-medium text-slate-900"
                            x-text="room"
                        ></dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-6 py-3">
                        <dt class="text-sm text-slate-500">Category</dt>
                        <dd
                            class="truncate text-sm font-medium text-slate-900"
                            x-text="category"
                        ></dd>
                    </div>
                </dl>
            </div>

            {{-- FOOTER --}}
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                <button
                    type="button"
                    @click="close()"
                    class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                >
                    Cancel
                </button>

                <div class="relative">
                    <button
                        type="button"
                        @click="downloadOpen = !downloadOpen"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        <i data-lucide="download" class="h-4 w-4"></i>
                        Download
                        <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-slate-400"></i>
                    </button>

                    <div
                        x-show="downloadOpen"
                        x-cloak
                        x-transition
                        @click.outside="downloadOpen = false"
                        class="absolute bottom-full right-0 z-50 mb-2 w-48 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg"
                    >
                        <button
                            type="button"
                            @click="downloadQrFile('pdf')"
                            class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                        >
                            <i data-lucide="file-text" class="h-4 w-4 text-slate-400"></i>
                            PDF Label
                        </button>

                        <button
                            type="button"
                            @click="downloadQrFile('png')"
                            class="flex w-full items-center gap-2.5 border-t border-slate-100 px-3.5 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                        >
                            <i data-lucide="image" class="h-4 w-4 text-slate-400"></i>
                            PNG Image
                        </button>

                        <button
                            type="button"
                            @click="downloadQrFile('svg')"
                            class="flex w-full items-center gap-2.5 border-t border-slate-100 px-3.5 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                        >
                            <i data-lucide="shapes" class="h-4 w-4 text-slate-400"></i>
                            SVG Vector
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    @click="printQr()"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                >
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    Print QR
                </button>
            </div>

        </div>

    </div>

    <style>

        /* ===================================== */
        /* PRINT QR LOADING LINE */
        /* ===================================== */

        .qr-print-loading-line {

            width: 35%;

            animation:
                qrPrintLoadingLine
                0.9s
                ease-in-out
                infinite;

        }


        /* ===================================== */
        /* LOADING LINE ANIMATION */
        /* ===================================== */

        @keyframes qrPrintLoadingLine {

            0% {

                transform:
                    translateX(-110%);

            }


            100% {

                transform:
                    translateX(300%);

            }

        }

        

    </style>

    <script>
        window.qrPageSelectableCodes = @json(
            $equipment
                ->filter(fn ($item) => !empty($item->equipment_qr_code))
                ->pluck('equipment_qr_code')
                ->values()
        );

        function qrBatchPrint() {
            return {
                selected: [],
                pageCodes: window.qrPageSelectableCodes || [],

                isSelected(code) {
                    return this.selected.includes(code);
                },

                toggleCode(code, checked) {
                    if (checked) {
                        if (!this.selected.includes(code)) {
                            this.selected.push(code);
                        }
                        return;
                    }

                    this.selected = this.selected.filter((item) => item !== code);
                },

                allSelectableSelected() {
                    return this.pageCodes.length > 0
                        && this.pageCodes.every((code) => this.selected.includes(code));
                },

                someSelectableSelected() {
                    return this.pageCodes.some((code) => this.selected.includes(code));
                },

                toggleSelectAll(checked) {
                    if (checked) {
                        this.selected = Array.from(
                            new Set([...this.selected, ...this.pageCodes])
                        );
                        return;
                    }

                    this.selected = this.selected.filter(
                        (code) => !this.pageCodes.includes(code)
                    );
                },

                clearSelection() {
                    this.selected = [];
                },

                printSelected() {
                    if (!this.selected.length) {
                        return;
                    }

                    const printUrl =
                        '/maintenance/equipment/qr/print?codes=' +
                        this.selected.map(encodeURIComponent).join(',');

                    Swal.fire({
                        title: 'Preparing Print',
                        html: `
                            <div class="mt-2 text-sm text-slate-500">
                                Preparing ${this.selected.length} QR label${this.selected.length === 1 ? '' : 's'} for printing...
                            </div>
                            <div class="mt-5 h-1 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="qr-print-loading-line h-full rounded-full bg-slate-900"></div>
                            </div>
                        `,
                        width: 380,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'rounded-2xl border border-slate-200 shadow-xl',
                        },
                    });

                    setTimeout(() => {
                        window.open(printUrl, '_blank');
                        Swal.close();
                    }, 700);
                },
            };
        }

        function copyQrId(qrId) {
            if (!qrId) {
                return;
            }

            navigator.clipboard
                .writeText(qrId)
                .then(function () {
                    if (typeof window.showMpToast === 'function') {
                        window.showMpToast(qrId, {
                            title: 'QR ID copied',
                            type: 'success',
                            timer: 2800,
                        });
                        return;
                    }

                    Swal.fire({
                        toast: true,
                        position: 'bottom-end',
                        icon: 'success',
                        title: 'QR ID copied',
                        text: qrId,
                        showConfirmButton: false,
                        timer: 2200,
                        timerProgressBar: true,
                    });
                })
                .catch(function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Copy Failed',
                        text: 'The QR ID could not be copied.',
                        confirmButtonText: 'Close',
                        customClass: {
                            popup: 'rounded-2xl',
                            confirmButton:
                                'rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-medium text-white',
                        },
                        buttonsStyling: false,
                    });
                });
        }

        // =====================================
        // GLOBAL QR PRINT FUNCTION
        // USED BY ACTION MENU AND PREVIEW MODAL
        // =====================================

        function openQrPrint(qrCode) {

            // =====================================
            // VALIDATE QR CODE
            // =====================================

            if (!qrCode) {

                Swal.fire({

                    icon: 'error',

                    title: 'QR Code Unavailable',

                    text: 'This equipment does not have a QR code.',

                    confirmButtonText: 'Close',

                    buttonsStyling: false,

                    customClass: {

                        popup: 'rounded-2xl',

                        confirmButton:
                            'rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-medium text-white'

                    }

                });

                return;
            }


            // =====================================
            // BUILD PRINT URL
            // =====================================

            const printUrl =
                '/maintenance/equipment/qr/' +
                encodeURIComponent(qrCode) +
                '/print';


            // =====================================
            // SHOW SWEETALERT FIRST
            // =====================================

            // =====================================
            // SHOW SWEETALERT WITH LOADING LINE
            // =====================================

            Swal.fire({

                title: 'Preparing Print',

                html: `

                    <div class="mt-2 text-sm text-slate-500">
                        Preparing QR label for printing...
                    </div>


                    <!-- ===================================== -->
                    <!-- ANIMATED LOADING LINE -->
                    <!-- ===================================== -->

                    <div
                        class="mt-5 h-1 w-full overflow-hidden
                            rounded-full bg-slate-100"
                    >

                        <div
                            class="qr-print-loading-line
                                h-full rounded-full
                                bg-slate-900"
                        ></div>

                    </div>

                `,

                width: 380,

                allowOutsideClick: false,

                allowEscapeKey: false,

                showConfirmButton: false,

                customClass: {

                    popup:
                        'rounded-2xl border border-slate-200 shadow-xl',

                    title:
                        'text-lg font-semibold text-slate-900',

                    htmlContainer:
                        'text-sm text-slate-500'

                }

            });


            // =====================================
            // WAIT FOR SWEETALERT TO BE VISIBLE
            // =====================================

            setTimeout(() => {

                // =====================================
                // CLOSE SWEETALERT FIRST
                // =====================================

                Swal.close();


                // =====================================
                // OPEN PRINT PAGE AFTER ALERT
                // =====================================

                window.open(
                    printUrl,
                    '_blank'
                );

            }, 900);

        }

        

        function confirmQrGenerate(form, isRegenerate, equipmentName) {
            if (!isRegenerate) {
                form.submit();
                return;
            }

            Swal.fire({
                icon: 'warning',
                title: 'Regenerate this QR code?',
                html:
                    '<p class="text-sm text-slate-600">This will replace the QR for <span class="font-semibold text-slate-900">' +
                    equipmentName +
                    '</span>.</p>' +
                    '<p class="mt-2 text-sm text-slate-500">Printed labels and saved scans for the old code may no longer match this equipment.</p>',
                showCancelButton: true,
                confirmButtonText: 'Regenerate QR',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                buttonsStyling: false,
                customClass: {
                    popup: 'rounded-3xl border border-slate-200 shadow-2xl',
                    title: 'text-lg font-semibold text-slate-900',
                    htmlContainer: 'text-left',
                    confirmButton: 'rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-medium text-white',
                    cancelButton: 'rounded-xl px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100',
                    actions: 'gap-2',
                },
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        function qrActionMenu() {
            return {
                open: false,
                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        this.$nextTick(() => this.place());
                    }
                },
                place() {
                    const trigger = this.$refs.trigger;
                    const menu = this.$refs.menu;
                    if (!trigger || !menu) {
                        return;
                    }

                    const rect = trigger.getBoundingClientRect();
                    const menuHeight = menu.offsetHeight || 160;
                    const menuWidth = menu.offsetWidth || 176;
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

        function openQrModal(data){

            window.dispatchEvent(

                new CustomEvent("open-qr-preview",{

                    detail:data

                })

            );

        }

        function qrPreviewModal(){

            return{

                open:false,

                downloadOpen: false,

                equipment:'',

                assetTag:'',

                serial:'',

                room:'',

                category:'',

                qrImage:'',

                qrCode:'',

                init(){

                    window.addEventListener("open-qr-preview",(event)=>{

                        this.open = true;

                        this.equipment = event.detail.equipment;

                        this.assetTag = event.detail.assetTag;

                        this.serial = event.detail.serial;

                        this.room = event.detail.room;

                        this.category = event.detail.category;

                        this.qrImage = event.detail.qrImage;

                        this.qrCode = event.detail.qrCode;

                    });

                },

                close(){

                    this.downloadOpen = false;

                    this.open = false;

                },

                // =====================================
                // PRINT QR
                // OPEN PRINT PAGE IN NEW TAB
                // =====================================

                printQr() {

                    this.downloadOpen = false;

                    openQrPrint(
                        this.qrCode
                    );

                },


                // =====================================
                // DOWNLOAD QR FILE
                // USED BY PDF, PNG, AND SVG
                // =====================================

                downloadQrFile(type) {

                    // =====================================
                    // VALIDATE QR CODE
                    // =====================================

                    if (!this.qrCode) {

                        Swal.fire({

                            icon: 'error',

                            title: 'QR Code Unavailable',

                            text:
                                'This equipment does not have a QR code.',

                            confirmButtonText: 'Close',

                            buttonsStyling: false,

                            customClass: {

                                popup:
                                    'rounded-2xl border border-slate-200 shadow-xl',

                                confirmButton:
                                    'rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-medium text-white'

                            }

                        });

                        return;
                    }


                    // =====================================
                    // DOWNLOAD TYPE CONFIGURATION
                    // =====================================

                    const downloadTypes = {

                        pdf: {

                            title:
                                'Preparing PDF',

                            message:
                                'Preparing QR label for download...',

                            extension:
                                'pdf'

                        },


                        png: {

                            title:
                                'Preparing PNG',

                            message:
                                'Preparing QR image for download...',

                            extension:
                                'png'

                        },


                        svg: {

                            title:
                                'Preparing SVG',

                            message:
                                'Preparing SVG vector for download...',

                            extension:
                                'svg'

                        }

                    };


                    // =====================================
                    // GET SELECTED DOWNLOAD CONFIGURATION
                    // =====================================

                    const config =
                        downloadTypes[type];


                    // =====================================
                    // STOP INVALID DOWNLOAD TYPE
                    // =====================================

                    if (!config) {

                        return;

                    }


                    // =====================================
                    // BUILD DOWNLOAD URL
                    // =====================================

                    const downloadUrl =
                        '/maintenance/equipment/qr/' +
                        encodeURIComponent(this.qrCode) +
                        '/' +
                        config.extension;


                    // =====================================
                    // CLOSE DOWNLOAD DROPDOWN
                    // =====================================

                    this.downloadOpen = false;


                    // =====================================
                    // SHOW DOWNLOAD LOADING ALERT
                    // =====================================

                    Swal.fire({

                        title:
                            config.title,

                        html: `

                            <div class="mt-2 text-sm text-slate-500">

                                ${config.message}

                            </div>


                            <!-- ===================================== -->
                            <!-- ANIMATED LOADING LINE -->
                            <!-- ===================================== -->

                            <div
                                class="mt-5 h-1 w-full overflow-hidden
                                    rounded-full bg-slate-100"
                            >

                                <div
                                    class="qr-print-loading-line
                                        h-full rounded-full
                                        bg-slate-900"
                                ></div>

                            </div>

                        `,

                        width: 380,

                        allowOutsideClick: false,

                        allowEscapeKey: false,

                        showConfirmButton: false,

                        customClass: {

                            popup:
                                'rounded-2xl border border-slate-200 shadow-xl',

                            title:
                                'text-lg font-semibold text-slate-900',

                            htmlContainer:
                                'text-sm text-slate-500'

                        }

                    });


                    // =====================================
                    // START DOWNLOAD AFTER FEEDBACK
                    // =====================================

                    setTimeout(() => {

                        window.location.href =
                            downloadUrl;


                        // =====================================
                        // CLOSE LOADING ALERT
                        // =====================================

                        setTimeout(() => {

                            Swal.close();

                        }, 500);

                    }, 800);

                }

            };

        }
    </script>

@endsection
