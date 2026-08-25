@extends ("layouts.maintenance-layout")

@section ("content")

    <div
        class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-end"
    >
        <button
            type="button"
            onclick="openDisposeModal()"
            class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 font-semibold text-[13px] text-white transition hover:bg-blue-800"
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
                    {{-- CATEGORY FILTER --}}
                    {{-- ================================================= --}}

                    <div class="relative">

                        <select
                            name="category"

                            class="h-9 min-w-[175px]
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

                            class="h-9 min-w-[165px]
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

                            <!--<th class="px-5 py-3">
                                Reason
                            </th>-->

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
                                    "Good" => "bg-emerald-50 text-emerald-700",
                                    "Fair" => "bg-sky-50 text-sky-700",
                                    "Damaged" => "bg-amber-50 text-amber-700",
                                    "Critical" => "bg-rose-50 text-rose-700",
                                    "Disposed" => "bg-slate-800 text-white",
                                    default => "bg-slate-100 text-slate-600",
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

                                    "Disposed" =>
                                        "bg-slate-800",

                                    default =>
                                        "bg-slate-400",
                                };

                                $isFinallyDisposed =
                                    $conditionStatus === 'Disposed';
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

                                                data-tooltip="{{ $record->equipment_name }}"
                                            >
                                                {{ $record->equipment_name }}
                                            </p>


                                            <p
                                                class="mt-0.5 text-[11px]
                                                    text-slate-400"
                                            >
                                                @if ($isFinallyDisposed)
                                                    Final disposal — cannot be restored
                                                @elseif (($record->equipment_condition_status ?? '') === 'Damaged'
                                                    || ($record->equipment_inventory_status ?? '') === 'For Replacement')
                                                    Damaged / queued — can still be restored
                                                @else
                                                    In disposal — can still be restored
                                                @endif
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
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $conditionClass }}">
                                        {{ $conditionStatus }}
                                    </span>
                                </td>



                                {{-- ===================================== --}}
                                {{-- DISPOSAL REASON --}}
                                {{-- ===================================== --}}

                                <!--<td class="px-5 py-4">

                                    <p
                                        class="max-w-[230px] truncate
                                            text-xs leading-5 text-slate-600"

                                        data-tooltip="{{ $record->disposal_reason }}"
                                    >
                                        {{
                                            $record->disposal_reason
                                                ?? "No reason provided"
                                        }}
                                    </p>

                                </td>-->



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

                                            data-tooltip="{{ $record->disposal_area_location }}"
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
                                        @php
                                            // Final only when condition is Disposed.
                                            // Damaged items can still be restored.
                                            $isFinallyDisposed =
                                                ($record->equipment_condition_status ?? '') === 'Disposed';
                                        @endphp

                                        @if (! $isFinallyDisposed)
                                            <button
                                                type="button"
                                                class="js-restore-disposal flex h-9 w-9 items-center justify-center rounded-xl bg-white text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-slate-900"
                                                data-disposal-id="{{ (int) $record->disposal_record_id }}"
                                                data-equipment-name="{{ e($record->equipment_name ?? 'this equipment') }}"
                                                data-tooltip="Restore to Inventory"
                                                aria-label="Restore to Inventory"
                                            >
                                                <i data-lucide="rotate-ccw" class="pointer-events-none h-3.5 w-3.5"></i>
                                            </button>

                                            <button
                                                type="button"
                                                class="js-finalize-dispose flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-white transition hover:bg-slate-800"
                                                data-disposal-id="{{ (int) $record->disposal_record_id }}"
                                                data-equipment-name="{{ e($record->equipment_name ?? 'this equipment') }}"
                                                data-tooltip="Finalize disposal (cannot restore)"
                                                aria-label="Finalize disposal"
                                            >
                                                <i data-lucide="archive-x" class="pointer-events-none h-3.5 w-3.5"></i>
                                            </button>
                                        @endif

                                        {{-- ================================= --}}
                                        {{-- VIEW BUTTON --}}
                                        {{-- ================================= --}}

                                        <button
                                            type="button"
                                            onclick="event.stopPropagation(); window.openDisposalViewModal && window.openDisposalViewModal(this);"
                                            data-payload="{{ base64_encode(json_encode([
                                                'equipment' => (string) ($record->equipment_name ?? ''),
                                                'category' => (string) ($record->equipment_category_name ?? ''),
                                                'condition' => (string) ($record->equipment_condition_status ?? ''),
                                                'reason' => (string) ($record->disposal_reason ?? ''),
                                                'location' => (string) ($record->disposal_area_location ?? ''),
                                                'date' => (string) ($record->disposal_disposed_at ?? ''),
                                                'inventoryStatus' => (string) ($record->equipment_inventory_status ?? ''),
                                            ], JSON_UNESCAPED_UNICODE)) }}"
                                            class="flex h-9 w-9 items-center
                                                justify-center rounded-xl
                                                bg-slate-100 text-slate-600
                                                transition
                                                hover:bg-slate-200
                                                hover:text-slate-900"
                                            data-tooltip="View disposal details"
                                            aria-label="View disposal details"
                                        >
                                            <i
                                                data-lucide="eye"
                                                class="pointer-events-none h-3.5 w-3.5"
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

                                            data-tooltip="Delete disposal record"

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
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
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
        class="fixed inset-0 hidden items-center justify-center bg-[#0b1220]/70 p-4 backdrop-blur-[2px]"
        style="display: none; z-index: 10060;"
        onclick="if (event.target === this) closeViewModal()"
    >
        <div
            class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.18)]"
            role="dialog"
            aria-modal="true"
            aria-labelledby="viewDisposalTitle"
        >
            {{-- Header --}}
            <div class="relative shrink-0 overflow-hidden px-6 pb-5 pt-6">
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-rose-50 via-white to-slate-50"></div>

                <div class="relative flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 ring-1 ring-rose-200/80">
                            <i data-lucide="archive-x" class="h-5 w-5"></i>
                        </div>

                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                            Disposal record
                        </p>

                        <h2
                            id="viewDisposalTitle"
                            class="mt-1 truncate text-xl font-semibold tracking-tight text-slate-950"
                        >
                            Equipment details
                        </h2>

                        <p id="viewDisposalSubtitle" class="mt-1.5 text-sm text-slate-500">
                            Review how this asset was written off.
                        </p>
                    </div>

                    <button
                        type="button"
                        onclick="closeViewModal()"
                        class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-white/80 hover:text-slate-900"
                        aria-label="Close modal"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="min-h-0 flex-1 overflow-y-auto px-6 pb-2 pt-1">
                <div id="viewDisposalDetails" class="space-y-4 pb-4"></div>
            </div>

            {{-- Footer --}}
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-6 py-4">
                <button
                    type="button"
                    onclick="closeViewModal()"
                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200"
                >
                    Done
                </button>
            </div>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- RESTORE VALIDATION MODAL -->
    <!-- ===================================================== -->

    <div
        id="restoreDisposalModal"
        class="fixed inset-0 hidden items-center justify-center bg-[#0b1220]/70 p-4"
        style="display: none; z-index: 10060;"
        onclick="if (event.target === this) window.closeRestoreDisposalModal && window.closeRestoreDisposalModal()"
    >
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
            role="dialog"
            aria-modal="true"
            aria-labelledby="restoreDisposalTitle"
        >
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    </div>

                    <h2
                        id="restoreDisposalTitle"
                        class="text-lg font-semibold tracking-tight text-slate-950"
                    >
                        Restore to inventory?
                    </h2>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        You are about to restore
                        <span id="restoreDisposalEquipmentName" class="font-semibold text-slate-800">this equipment</span>
                        back to Inventory as Active. The disposal record for this item will be removed.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="window.closeRestoreDisposalModal && window.closeRestoreDisposalModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form action="/maintenance/disposal/restore" method="POST">
                @csrf
                <input type="hidden" id="restoreDisposalId" name="disposal_id" value="" />

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button
                        type="button"
                        onclick="window.closeRestoreDisposalModal && window.closeRestoreDisposalModal()"
                        class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                    >
                        Confirm restore
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- FINALIZE DISPOSE VALIDATION MODAL -->
    <!-- ===================================================== -->

    <div
        id="finalizeDisposeModal"
        class="fixed inset-0 hidden items-center justify-center bg-[#0b1220]/70 p-4"
        style="display: none; z-index: 10060;"
        onclick="if (event.target === this) window.closeFinalizeDisposeModal && window.closeFinalizeDisposeModal()"
    >
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
            role="dialog"
            aria-modal="true"
            aria-labelledby="finalizeDisposeTitle"
        >
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-white">
                        <i data-lucide="archive-x" class="h-4 w-4"></i>
                    </div>

                    <h2
                        id="finalizeDisposeTitle"
                        class="text-lg font-semibold tracking-tight text-slate-950"
                    >
                        Finalize disposal?
                    </h2>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        You are about to permanently dispose
                        <span id="finalizeDisposeEquipmentName" class="font-semibold text-slate-800">this equipment</span>.
                        Condition will become <span class="font-medium text-slate-800">Disposed</span>,
                        it will stay in Disposal, and it cannot be restored.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="closeFinalizeDisposeModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form action="/maintenance/disposal/confirm" method="POST">
                @csrf
                <input type="hidden" id="finalizeDisposeId" name="disposal_id" value="" />

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button
                        type="button"
                        onclick="closeFinalizeDisposeModal()"
                        class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200"
                    >
                        Confirm dispose
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- DELETE DISPOSAL MODAL -->
    <!-- ===================================================== -->

    <div
    id="deleteModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
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

        function decodeDisposalPayload(button) {
            var encoded = button.getAttribute("data-payload") || "";
            if (!encoded) {
                return {};
            }

            try {
                var binary = atob(encoded);
                var bytes = Uint8Array.from(binary, function (char) {
                    return char.charCodeAt(0);
                });
                return JSON.parse(new TextDecoder().decode(bytes));
            } catch (error) {
                try {
                    return JSON.parse(decodeURIComponent(escape(atob(encoded))));
                } catch (fallbackError) {
                    console.error("Disposal view payload decode failed:", fallbackError);
                    return {};
                }
            }
        }

        function escapeDisposalHtml(value) {
            return String(value == null ? "" : value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#39;");
        }

        function displayDisposalValue(value, fallback) {
            var text = String(value == null ? "" : value).trim();
            return text !== "" ? escapeDisposalHtml(text) : (fallback || "—");
        }

        function openDisposalViewModal(button) {
            var data = decodeDisposalPayload(button);
            var modalEl = document.getElementById("viewModal");
            var titleEl = document.getElementById("viewDisposalTitle");
            var subtitleEl = document.getElementById("viewDisposalSubtitle");
            var detailsEl = document.getElementById("viewDisposalDetails");

            if (!modalEl || !titleEl || !subtitleEl || !detailsEl) {
                console.error("Disposal view modal elements are missing.");
                alert("Unable to open disposal details.");
                return;
            }

            var conditionClasses = {
                Good: "bg-emerald-50 text-emerald-700 ring-emerald-100",
                Fair: "bg-sky-50 text-sky-700 ring-sky-100",
                Damaged: "bg-amber-50 text-amber-700 ring-amber-100",
                Critical: "bg-rose-50 text-rose-700 ring-rose-100",
                "Under Maintenance": "bg-amber-50 text-amber-700 ring-amber-100",
            };

            var statusClasses = {
                Disposed: "bg-rose-50 text-rose-700 ring-rose-100",
                "For Replacement": "bg-orange-50 text-orange-700 ring-orange-100",
            };

            var conditionLabel = String(data.condition || "").trim() || "Unknown";
            var statusLabel = String(data.inventoryStatus || "").trim() || "Disposed";
            var conditionClass =
                conditionClasses[conditionLabel] || "bg-slate-100 text-slate-600 ring-slate-200";
            var statusClass =
                statusClasses[statusLabel] || "bg-slate-100 text-slate-600 ring-slate-200";

            var formattedDate = displayDisposalValue(data.date);
            if (data.date) {
                var parsed = new Date(data.date);
                if (!Number.isNaN(parsed.getTime())) {
                    formattedDate = escapeDisposalHtml(
                        parsed.toLocaleString(undefined, {
                            year: "numeric",
                            month: "short",
                            day: "numeric",
                            hour: "numeric",
                            minute: "2-digit",
                        })
                    );
                }
            }

            titleEl.textContent = String(data.equipment || "").trim() || "Equipment details";
            subtitleEl.textContent =
                statusLabel === "For Replacement"
                    ? "Queued for disposal - not fully written off yet."
                    : "This asset has been recorded as disposed.";

            detailsEl.innerHTML =
                '<div class="flex flex-wrap items-center gap-2">' +
                    '<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ' + statusClass + '">' +
                        '<span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>' +
                        displayDisposalValue(statusLabel) +
                    "</span>" +
                    '<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ' + conditionClass + '">' +
                        "Condition · " + displayDisposalValue(conditionLabel) +
                    "</span>" +
                "</div>" +
                '<div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">' +
                    '<div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4">' +
                        '<div class="mb-2 flex h-8 w-8 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-200/80">' +
                            '<i data-lucide="layers" class="h-3.5 w-3.5"></i>' +
                        "</div>" +
                        '<p class="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-400">Category</p>' +
                        '<p class="mt-1 text-sm font-semibold text-slate-900">' + displayDisposalValue(data.category, "Uncategorized") + "</p>" +
                    "</div>" +
                    '<div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4">' +
                        '<div class="mb-2 flex h-8 w-8 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-200/80">' +
                            '<i data-lucide="map-pin" class="h-3.5 w-3.5"></i>' +
                        "</div>" +
                        '<p class="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-400">Disposal area</p>' +
                        '<p class="mt-1 text-sm font-semibold text-slate-900">' + displayDisposalValue(data.location) + "</p>" +
                    "</div>" +
                    '<div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 sm:col-span-2">' +
                        '<div class="mb-2 flex h-8 w-8 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-200/80">' +
                            '<i data-lucide="calendar-clock" class="h-3.5 w-3.5"></i>' +
                        "</div>" +
                        '<p class="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-400">Disposed date</p>' +
                        '<p class="mt-1 text-sm font-semibold text-slate-900">' + formattedDate + "</p>" +
                    "</div>" +
                "</div>" +
                '<div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">' +
                    '<div class="mb-2 flex items-center gap-2">' +
                        '<div class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">' +
                            '<i data-lucide="file-text" class="h-3.5 w-3.5"></i>' +
                        "</div>" +
                        '<p class="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-400">Disposal reason</p>' +
                    "</div>" +
                    '<p class="whitespace-pre-wrap text-sm leading-6 text-slate-700">' + displayDisposalValue(data.reason, "No reason provided.") + "</p>" +
                "</div>";

            modalEl.classList.remove("hidden");
            modalEl.classList.add("flex");
            modalEl.style.display = "flex";
            modalEl.style.zIndex = "10060";

            // Keep modal above layout overflow/clipping by attaching to body.
            if (modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function closeViewModal() {
            var modalEl = document.getElementById("viewModal");
            if (!modalEl) return;
            modalEl.classList.add("hidden");
            modalEl.classList.remove("flex");
            modalEl.style.display = "none";
        }

        function openRestoreDisposalModal(id, equipmentName) {
            var modal = document.getElementById("restoreDisposalModal");
            var idInput = document.getElementById("restoreDisposalId");
            var nameEl = document.getElementById("restoreDisposalEquipmentName");

            if (!modal || !idInput || !nameEl) {
                console.error("Restore disposal modal markup is missing.");
                alert("Unable to open restore confirmation.");
                return;
            }

            idInput.value = id;
            nameEl.textContent = equipmentName || "this equipment";

            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            modal.classList.remove("hidden");
            modal.classList.add("flex");
            modal.style.display = "flex";
            modal.style.zIndex = "10060";

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function closeRestoreDisposalModal() {
            var modal = document.getElementById("restoreDisposalModal");
            if (!modal) return;
            modal.classList.add("hidden");
            modal.classList.remove("flex");
            modal.style.display = "none";
        }

        function openFinalizeDisposeModal(id, equipmentName) {
            var modal = document.getElementById("finalizeDisposeModal");
            var idInput = document.getElementById("finalizeDisposeId");
            var nameEl = document.getElementById("finalizeDisposeEquipmentName");

            if (!modal || !idInput || !nameEl) {
                console.error("Finalize dispose modal markup is missing.");
                alert("Unable to open dispose confirmation.");
                return;
            }

            idInput.value = id;
            nameEl.textContent = equipmentName || "this equipment";

            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            modal.classList.remove("hidden");
            modal.classList.add("flex");
            modal.style.display = "flex";
            modal.style.zIndex = "10060";

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function closeFinalizeDisposeModal() {
            var modal = document.getElementById("finalizeDisposeModal");
            if (!modal) return;
            modal.classList.add("hidden");
            modal.classList.remove("flex");
            modal.style.display = "none";
        }

        document.addEventListener("click", function (event) {
            var restoreButton = event.target.closest(".js-restore-disposal");
            if (restoreButton) {
                event.preventDefault();
                event.stopPropagation();
                openRestoreDisposalModal(
                    restoreButton.getAttribute("data-disposal-id"),
                    restoreButton.getAttribute("data-equipment-name") || "this equipment"
                );
                return;
            }

            var disposeButton = event.target.closest(".js-finalize-dispose");
            if (!disposeButton) return;

            event.preventDefault();
            event.stopPropagation();

            openFinalizeDisposeModal(
                disposeButton.getAttribute("data-disposal-id"),
                disposeButton.getAttribute("data-equipment-name") || "this equipment"
            );
        });

        function openDeleteModal(id) {
            document.getElementById("deleteDisposalId").value = id;
            document.getElementById("deleteModal").classList.remove("hidden");
            document.getElementById("deleteModal").classList.add("flex");
        }

        function closeDeleteModal() {
            document.getElementById("deleteModal").classList.add("hidden");
            document.getElementById("deleteModal").classList.remove("flex");
        }

        window.openDisposeModal = openDisposeModal;
        window.closeDisposeModal = closeDisposeModal;
        window.openDisposalViewModal = openDisposalViewModal;
        window.closeViewModal = closeViewModal;
        window.closeDisposalViewModal = closeViewModal;
        window.openRestoreDisposalModal = openRestoreDisposalModal;
        window.closeRestoreDisposalModal = closeRestoreDisposalModal;
        window.openFinalizeDisposeModal = openFinalizeDisposeModal;
        window.closeFinalizeDisposeModal = closeFinalizeDisposeModal;
        window.openDeleteModal = openDeleteModal;
        window.closeDeleteModal = closeDeleteModal;
    </script>

@endsection
