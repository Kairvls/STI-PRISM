@extends ("layouts.maintenance-layout")

@section ("content")
    @php
        $canMaintain = request()->is('admin/*');
        $disposedOnly = $disposedOnly ?? false;
        $disposalUrl = url($canMaintain ? '/admin/disposal' : ($disposedOnly ? '/school-admin/disposal' : '/maintenance/disposal'));
    @endphp

    @unless ($disposedOnly)
    <div class="mb-6">
        @include('maintenance-personnel.disposal.partials.tabs')
    </div>
    @endunless

    @unless ($canMaintain)
    <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
        <i data-lucide="eye" class="h-4 w-4 shrink-0 text-slate-400"></i>
        @if ($disposedOnly)
            View only. Equipment that has been permanently disposed.
        @else
            View only. Disposals you request from Deployed Equipment wait for the Administrator's approval under My requests.
        @endif
    </div>
    @endunless

    @if ($canMaintain)
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
    @endif

    @php
        $disposalMonthlyHint = $disposalMonthlyPercentage === null
            ? 'New activity vs last month'
            : (($disposalMonthlyPercentage > 0 ? '+' : '') . number_format($disposalMonthlyPercentage, 2) . '% vs last month');
    @endphp
    <div class="mb-6 mt-6">
        @if ($disposedOnly)
        @include('layouts.partials.maintenance-stat-cards', [
            'cards' => [
                ['label' => 'Disposed Equipment', 'hint' => 'Permanently disposed', 'value' => number_format($disposedOnlyStats['total'] ?? 0)],
                ['label' => 'Disposed This Month', 'hint' => now()->format('F Y'), 'value' => number_format($disposedOnlyStats['thisMonth'] ?? 0)],
                ['label' => 'Purchase Value Written Off', 'hint' => 'Total purchase cost of disposed items', 'value' => '₱'.number_format($disposedOnlyStats['writtenOff'] ?? 0, 2)],
            ],
        ])
        @else
        @include('layouts.partials.maintenance-stat-cards', [
            'cards' => [
                ['label' => 'Total Disposal Records', 'hint' => $disposalMonthlyHint, 'value' => number_format($totalDisposalRecords)],
                ['label' => 'Damaged Disposals', 'hint' => number_format($damagedDisposalsPercentage, 2) . '% of disposal records', 'value' => number_format($damagedDisposals)],
                ['label' => 'Disposed Equipment', 'hint' => number_format($disposedEquipmentPercentage, 2) . '% of all equipment', 'value' => number_format($disposedEquipment)],
            ],
        ])
        @endif
    </div>

    <div class="rounded-3xl bg-white">
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
                <input type="hidden" name="archive" value="{{ !empty($showArchive) ? 1 : 0 }}" />

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
                            placeholder="Search equipment, asset tag, serial, category, reason, or area..."

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

                    <div class="relative {{ $disposedOnly ? 'hidden' : '' }}">

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
                            href="{{ url()->current() }}?archive={{ !empty($showArchive) ? 1 : 0 }}"

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

                    @if ($canMaintain)
                    <div class="hidden h-6 w-px bg-slate-200 xl:block"></div>

                    <div class="flex shrink-0 items-center rounded-lg bg-slate-100 p-1">
                        <a
                            href="{{ $disposalUrl }}?archive=0"
                            class="flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-xs font-semibold transition
                                {{ empty($showArchive)
                                    ? 'bg-white text-slate-900 shadow-sm'
                                    : 'text-slate-400 hover:text-slate-600' }}"
                        >
                            <i data-lucide="folder-open" class="h-3.5 w-3.5"></i>
                            Active
                        </a>
                        <a
                            href="{{ $disposalUrl }}?archive=1"
                            class="flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-xs font-semibold transition
                                {{ !empty($showArchive)
                                    ? 'bg-white text-slate-900 shadow-sm'
                                    : 'text-slate-400 hover:text-slate-600' }}"
                        >
                            <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                            Archive
                        </a>
                    </div>
                    @endif

                </div>

            </form>



            {{-- ===================================================== --}}
            {{-- TABLE --}}
            {{-- ===================================================== --}}

            <div class="overflow-x-auto">

                <table class="w-full min-w-[1100px] text-left">

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
                                Purchased
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

                                $brandModel = collect([$record->equipment_brand_name ?? null, $record->equipment_model ?? null])
                                    ->filter(fn ($v) => filled($v))
                                    ->implode(' · ');

                                $fmtDate = fn ($v) => filled($v) ? \Carbon\Carbon::parse($v)->format('M d, Y') : '';

                                $purchaseDate = $fmtDate($record->equipment_purchase_date ?? null);
                                $acquiredDate = $fmtDate($record->equipment_acquired_date ?? null);

                                $ageFrom = $record->equipment_purchase_date ?? $record->equipment_acquired_date ?? null;
                                $ageAtDisposal = '';
                                if (filled($ageFrom) && filled($record->disposal_disposed_at)) {
                                    $ageAtDisposal = \Carbon\Carbon::parse($ageFrom)
                                        ->diffForHumans(\Carbon\Carbon::parse($record->disposal_disposed_at), [
                                            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                                            'parts' => 2,
                                        ]);
                                }
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

                                            @if ($brandModel !== '')
                                                <p class="mt-0.5 max-w-[260px] truncate text-xs text-slate-500" data-tooltip="{{ $brandModel }}">
                                                    {{ $brandModel }}
                                                </p>
                                            @endif

                                            @php
                                                $assetTag = filled($record->equipment_asset_tag ?? null) ? $record->equipment_asset_tag : '—';
                                                $serial = filled($record->equipment_serial_number ?? null) ? $record->equipment_serial_number : '—';
                                            @endphp
                                            <p
                                                class="mt-1 max-w-[260px] truncate font-mono text-[11px] text-slate-500"
                                                data-tooltip="Asset tag: {{ $assetTag }} · Serial: {{ $serial }}"
                                            >
                                                <span class="font-semibold text-slate-700">{{ $assetTag }}</span>
                                                <span class="text-slate-300">·</span>
                                                SN: {{ $serial }}
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
                                {{-- PURCHASED --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">
                                    <p class="whitespace-nowrap text-xs font-medium text-slate-700">
                                        {{ $purchaseDate !== '' ? $purchaseDate : ($acquiredDate !== '' ? 'Acquired '.$acquiredDate : '—') }}
                                    </p>
                                    <p class="mt-0.5 whitespace-nowrap text-[11px] text-slate-400">
                                        @if (isset($record->equipment_purchase_cost) && $record->equipment_purchase_cost !== null && $record->equipment_purchase_cost !== '')
                                            ₱{{ number_format((float) $record->equipment_purchase_cost, 2) }}
                                        @else
                                            No cost recorded
                                        @endif
                                        @if ($ageAtDisposal !== '')
                                            · {{ $ageAtDisposal }} old
                                        @endif
                                    </p>
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

                                        @if ($canMaintain && empty($showArchive) && ! $isFinallyDisposed)
                                            <button
                                                type="button"
                                                class="js-restore-disposal flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50"
                                                data-disposal-id="{{ (int) $record->disposal_record_id }}"
                                                data-equipment-name="{{ e($record->equipment_name ?? 'this equipment') }}"
                                                data-tooltip="Restore to Inventory"
                                                aria-label="Restore to Inventory"
                                            >
                                                <i data-lucide="rotate-ccw" class="pointer-events-none h-3.5 w-3.5"></i>
                                            </button>

                                            <button
                                                type="button"
                                                class="js-finalize-dispose flex h-9 w-9 items-center justify-center rounded-xl bg-red-600 text-white transition hover:bg-red-700"
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
                                                'method' => (string) ($record->disposal_method ?? ''),
                                                'residual' => isset($record->disposal_residual_value) && $record->disposal_residual_value !== null
                                                    ? number_format((float) $record->disposal_residual_value, 2)
                                                    : '',
                                                'location' => (string) ($record->disposal_area_location ?? ''),
                                                'date' => (string) ($record->disposal_disposed_at ?? ''),
                                                'inventoryStatus' => (string) ($record->equipment_inventory_status ?? ''),
                                                'disposedBy' => (string) ($record->disposed_by_name ?? ''),
                                                'assetTag' => (string) ($record->equipment_asset_tag ?? ''),
                                                'serial' => (string) ($record->equipment_serial_number ?? ''),
                                                'brand' => (string) ($record->equipment_brand_name ?? ''),
                                                'model' => (string) ($record->equipment_model ?? ''),
                                                'quantity' => (string) ($record->equipment_quantity ?? ''),
                                                'room' => (string) ($record->room_name ?? $record->equipment_current_location ?? ''),
                                                'department' => (string) ($record->department_name ?? ''),
                                                'purchaseDate' => $purchaseDate,
                                                'purchaseCost' => isset($record->equipment_purchase_cost) && $record->equipment_purchase_cost !== null && $record->equipment_purchase_cost !== ''
                                                    ? number_format((float) $record->equipment_purchase_cost, 2, '.', '')
                                                    : '',
                                                'acquiredDate' => $acquiredDate,
                                                'source' => (string) ($record->equipment_acquisition_source ?? ''),
                                                'supplier' => (string) ($record->equipment_supplier_name ?? ''),
                                                'reference' => (string) ($record->equipment_reference_number ?? ''),
                                                'warranty' => $fmtDate($record->equipment_warranty_expiration ?? null),
                                                'usefulLife' => ! empty($record->equipment_useful_life_years) ? (int) $record->equipment_useful_life_years.' years' : '',
                                                'age' => $ageAtDisposal,
                                                'qrCode' => (string) ($record->equipment_qr_code ?? ''),
                                                'image' => filled($record->equipment_image ?? null) ? asset('storage/'.$record->equipment_image) : '',
                                                'profileUrl' => ! $record->disposal_equipment_id ? '' : ($disposedOnly
                                                    ? route('school-admin.operations.equipment.show', $record->disposal_equipment_id)
                                                    : url('/maintenance/equipment/view/'.$record->disposal_equipment_id)),
                                                'equipmentId' => (int) ($record->disposal_equipment_id ?? 0),
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
                                        {{-- ARCHIVE / UNARCHIVE (finalized only) --}}
                                        {{-- ================================= --}}

                                        @if ($canMaintain && !empty($showArchive))
                                            <form method="POST" action="{{ $disposalUrl }}/unarchive" class="inline">
                                                @csrf
                                                <input type="hidden" name="disposal_id" value="{{ (int) $record->disposal_record_id }}" />
                                                <button
                                                    type="submit"
                                                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50 active:scale-95"
                                                    data-tooltip="Restore from archive"
                                                    aria-label="Restore from archive"
                                                >
                                                    <i data-lucide="archive-restore" class="h-3.5 w-3.5"></i>
                                                </button>
                                            </form>
                                        @elseif ($canMaintain && $isFinallyDisposed)
                                            <button
                                                type="button"
                                                onclick='openArchiveModal(@js($record->disposal_record_id))'
                                                class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#007a3f] transition hover:bg-slate-50 active:scale-95"
                                                data-tooltip="Archive disposal record"
                                                aria-label="Archive disposal record"
                                            >
                                                <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                                            </button>
                                        @endif

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

                                                    : (!empty($showArchive)
                                                        ? 'Archive is empty'
                                                        : ($disposedOnly ? 'No disposed equipment yet' : 'No disposal records yet'))
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

                                                    : (!empty($showArchive)
                                                        ? 'Finalized disposals you archive will appear here.'
                                                        : ($disposedOnly
                                                            ? 'Equipment appears here once its disposal is finalized.'
                                                            : 'Equipment moved to disposal will appear here with its reason, location, and disposal date.'))
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

                                        @elseif ($canMaintain)

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

    @if ($canMaintain)
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
        <form action="{{ $disposalUrl }}/store" method="POST" onsubmit="return validateDisposeSelection(this)">
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
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <label
                                for="disposeEquipmentSearch"
                                class="block text-sm font-medium text-slate-700"
                            >
                                Equipment <span class="text-red-500">*</span>
                            </label>
                            <span id="disposeEquipmentCount" class="text-xs font-medium text-slate-400">None selected</span>
                        </div>

                        <input
                            id="disposeEquipmentSearch"
                            type="search"
                            placeholder="Search name, asset tag or room..."
                            oninput="filterDisposeEquipment(this.value)"
                            class="mb-2 w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >

                        <div id="disposeEquipmentList" class="max-h-56 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200">
                            @forelse ($equipment as $item)
                                <label
                                    class="dispose-equipment-option flex cursor-pointer items-start gap-3 px-3.5 py-2.5 text-sm transition hover:bg-slate-50"
                                    data-search="{{ strtolower($item->equipment_name.' '.($item->equipment_asset_tag ?? '').' '.($item->equipment_serial_number ?? '').' '.($item->equipment_room_label ?? '')) }}"
                                >
                                    <input
                                        type="checkbox"
                                        name="equipment_ids[]"
                                        value="{{ $item->equipment_id }}"
                                        onchange="syncDisposeEquipmentCount()"
                                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500"
                                    >
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-slate-900">{{ $item->equipment_name }}</span>
                                        <span class="block truncate text-xs text-slate-500">
                                            {{ $item->equipment_asset_tag ?: 'No asset tag' }}
                                            · {{ $item->equipment_room_label ?: 'No room' }}
                                            · {{ $item->equipment_inventory_status ?: '—' }}
                                        </span>
                                    </span>
                                </label>
                            @empty
                                <p class="px-3.5 py-6 text-center text-sm text-slate-500">No equipment available to dispose.</p>
                            @endforelse
                            <p id="disposeEquipmentEmpty" class="hidden px-3.5 py-6 text-center text-sm text-slate-500">No equipment matches your search.</p>
                        </div>
                    </div>

                    <!-- ===================================== -->
                    <!-- DISPOSAL REASON -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="disposeReason"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Disposal reason <span class="text-red-500">*</span>
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

                    <div>
                        <label
                            for="disposeMethod"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Disposal method
                        </label>
                        <select
                            id="disposeMethod"
                            name="method"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                            <option value="">Select method (optional)</option>
                            <option value="Scrap">Scrap</option>
                            <option value="Donate">Donate</option>
                            <option value="Sell / Auction">Sell / Auction</option>
                            <option value="Return to supplier">Return to supplier</option>
                            <option value="Destroy">Destroy</option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="disposeResidualValue"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Residual value (₱)
                        </label>
                        <input
                            id="disposeResidualValue"
                            type="number"
                            name="residual_value"
                            min="0"
                            step="0.01"
                            placeholder="Optional salvage / residual amount"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        />
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
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-red-700 transition hover:bg-slate-50 focus:outline-none"
                >
                    Dispose equipment
                </button>
            </div>
        </form>
    </div>
</div>
    @endif

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
            class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.18)]"
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
                <a
                    id="viewDisposalProfileLink"
                    href="#"
                    class="hidden items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                    Open full profile
                </a>
                <button
                    type="button"
                    onclick="closeViewModal()"
                    class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fad] focus:outline-none focus:ring-4 focus:ring-slate-200"
                >
                    Done
                </button>
            </div>
        </div>
    </div>

    @if ($canMaintain)
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

            <form action="{{ $disposalUrl }}/restore" method="POST">
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
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-[#0025cc] transition hover:bg-slate-50 focus:outline-none"
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
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-red-600 text-white">
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

            <form action="{{ $disposalUrl }}/confirm" method="POST">
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
                        class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-100"
                    >
                        Confirm dispose
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- ARCHIVE DISPOSAL MODAL -->
    <!-- ===================================================== -->

    <div
        id="archiveModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
        onclick="if (event.target === this) closeArchiveModal()"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                        <i data-lucide="archive" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">
                        Archive disposal record?
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        This finalized disposal will move to Archive. You can restore it to the active list anytime.
                    </p>
                </div>
                <button
                    type="button"
                    onclick="closeArchiveModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <form action="{{ $disposalUrl }}/archive" method="POST">
                @csrf
                <input type="hidden" id="archiveDisposalId" name="disposal_id" />
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button
                        type="button"
                        onclick="closeArchiveModal()"
                        class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-[#007a3f] transition hover:bg-slate-50 focus:outline-none"
                    >
                        Archive record
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <script>
        function openDisposeModal() {
            document.querySelectorAll('#disposeEquipmentList input[type="checkbox"]').forEach(function (box) {
                box.checked = false;
            });
            var search = document.getElementById("disposeEquipmentSearch");
            if (search) {
                search.value = "";
                filterDisposeEquipment("");
            }
            syncDisposeEquipmentCount();
            document.getElementById("disposeModal").classList.remove("hidden");
            document.getElementById("disposeModal").classList.add("flex");
        }

        function filterDisposeEquipment(term) {
            var needle = String(term || "").trim().toLowerCase();
            var shown = 0;
            document.querySelectorAll(".dispose-equipment-option").forEach(function (option) {
                var match = needle === "" || (option.dataset.search || "").indexOf(needle) !== -1;
                option.classList.toggle("hidden", !match);
                option.classList.toggle("flex", match);
                if (match) shown++;
            });
            var empty = document.getElementById("disposeEquipmentEmpty");
            if (empty) empty.classList.toggle("hidden", shown > 0 || document.querySelectorAll(".dispose-equipment-option").length === 0);
        }

        function syncDisposeEquipmentCount() {
            var total = document.querySelectorAll('#disposeEquipmentList input[type="checkbox"]:checked').length;
            var label = document.getElementById("disposeEquipmentCount");
            if (label) label.textContent = total === 0 ? "None selected" : total + " selected";
        }

        function validateDisposeSelection(form) {
            if (form.querySelectorAll('#disposeEquipmentList input[type="checkbox"]:checked').length > 0) {
                return true;
            }
            alert("Select at least one piece of equipment to dispose.");
            return false;
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

            function sectionTitle(text) {
                return '<p class="mt-6 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">' + text + "</p>";
            }

            function field(label, value, options) {
                options = options || {};
                return (
                    '<div class="' + (options.wide ? "sm:col-span-2 " : "") + 'min-w-0">' +
                        '<dt class="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-400">' + label + "</dt>" +
                        '<dd class="mt-1 break-words text-sm font-semibold text-slate-900' + (options.mono ? " font-mono" : "") + '">' +
                            (options.html !== undefined ? options.html : displayDisposalValue(value)) +
                        "</dd>" +
                    "</div>"
                );
            }

            function peso(value) {
                var number = Number(value);
                return value !== "" && value != null && !isNaN(number)
                    ? "₱" + number.toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : "—";
            }

            var photoHtml = data.image
                ? '<img src="' + escapeDisposalHtml(data.image) + '" alt="" class="h-24 w-24 shrink-0 rounded-xl border border-slate-200 object-cover" />'
                : '<div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 text-slate-300"><i data-lucide="image-off" class="h-6 w-6"></i></div>';

            var qrHtml = data.qrCode
                ? '<img src="' + escapeDisposalHtml(@js(url('/maintenance/equipment/qr-image')) + "/" + encodeURIComponent(data.qrCode)) + '" alt="QR" class="h-24 w-24 shrink-0 rounded-xl border border-slate-200 bg-white p-1.5" />'
                : "";

            var profileHtml =
                sectionTitle("Equipment profile") +
                '<div class="mt-2 flex gap-4 rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4">' +
                    photoHtml +
                    '<dl class="grid min-w-0 flex-1 grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">' +
                        field("Asset tag", data.assetTag, { mono: true }) +
                        field("Serial number", data.serial, { mono: true }) +
                        field("Brand", data.brand) +
                        field("Model", data.model) +
                    "</dl>" +
                    qrHtml +
                "</div>" +
                '<dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-3 rounded-2xl border border-slate-200/80 p-4 sm:grid-cols-3">' +
                    field("Category", data.category || "Uncategorized") +
                    field("Quantity", data.quantity) +
                    field("Last room", data.room) +
                    field("Department", data.department) +
                    field("QR code", data.qrCode, { mono: true }) +
                "</dl>" +
                sectionTitle("Purchase &amp; acquisition") +
                '<dl class="mt-2 grid grid-cols-2 gap-x-4 gap-y-3 rounded-2xl border border-slate-200/80 p-4 sm:grid-cols-3">' +
                    field("Purchase date", data.purchaseDate) +
                    field("Purchase cost", null, { html: peso(data.purchaseCost) }) +
                    field("Acquired date", data.acquiredDate) +
                    field("Source", data.source) +
                    field("Supplier", data.supplier) +
                    field("Reference no.", data.reference, { mono: true }) +
                    field("Warranty until", data.warranty) +
                    field("Useful life", data.usefulLife) +
                    field("Age at disposal", data.age) +
                "</dl>" +
                sectionTitle("Disposal record");

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
                profileHtml +
                '<div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">' +
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
                    '<div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4">' +
                        '<div class="mb-2 flex h-8 w-8 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-200/80">' +
                            '<i data-lucide="recycle" class="h-3.5 w-3.5"></i>' +
                        "</div>" +
                        '<p class="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-400">Method</p>' +
                        '<p class="mt-1 text-sm font-semibold text-slate-900">' + displayDisposalValue(data.method) + "</p>" +
                    "</div>" +
                    '<div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4">' +
                        '<div class="mb-2 flex h-8 w-8 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-200/80">' +
                            '<i data-lucide="banknote" class="h-3.5 w-3.5"></i>' +
                        "</div>" +
                        '<p class="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-400">Residual value</p>' +
                        '<p class="mt-1 text-sm font-semibold text-slate-900">' + (data.residual && !isNaN(Number(data.residual)) ? ("₱" + Number(data.residual).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })) : (data.residual ? ("₱" + displayDisposalValue(data.residual)) : "—")) + "</p>" +
                    "</div>" +
                    '<div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4">' +
                        '<div class="mb-2 flex h-8 w-8 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-200/80">' +
                            '<i data-lucide="calendar-clock" class="h-3.5 w-3.5"></i>' +
                        "</div>" +
                        '<p class="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-400">Disposed date</p>' +
                        '<p class="mt-1 text-sm font-semibold text-slate-900">' + formattedDate + "</p>" +
                    "</div>" +
                    '<div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4">' +
                        '<div class="mb-2 flex h-8 w-8 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-200/80">' +
                            '<i data-lucide="user-round" class="h-3.5 w-3.5"></i>' +
                        "</div>" +
                        '<p class="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-400">Disposed by</p>' +
                        '<p class="mt-1 text-sm font-semibold text-slate-900">' + displayDisposalValue(data.disposedBy) + "</p>" +
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
                "</div>" +
                sectionTitle("Lifecycle timeline") +
                '<div id="viewDisposalTimeline" class="mt-2 rounded-2xl border border-slate-200/80 p-4"></div>';

            loadDisposalTimeline(data.equipmentId);

            var profileLink = document.getElementById("viewDisposalProfileLink");
            if (profileLink) {
                profileLink.href = data.profileUrl || "#";
                profileLink.classList.toggle("hidden", !data.profileUrl);
                profileLink.classList.toggle("inline-flex", !!data.profileUrl);
            }

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

        var DISPOSAL_TIMELINE_VISIBLE = 5;
        var disposalTimelineRequest = 0;
        var disposalTimelineColors = {
            acquisition: "bg-indigo-500",
            created: "bg-slate-400",
            qr: "bg-sky-500",
            transfer: "bg-violet-500",
            borrow: "bg-cyan-500",
            condition: "bg-amber-500",
            maintenance: "bg-orange-500",
            report: "bg-rose-500",
            disposal: "bg-rose-700",
            assignment: "bg-teal-500",
        };

        function formatTimelineDate(value) {
            if (!value) return "—";
            var parsed = new Date(String(value).replace(" ", "T"));
            if (Number.isNaN(parsed.getTime())) return escapeDisposalHtml(value);
            return escapeDisposalHtml(parsed.toLocaleString(undefined, {
                year: "numeric",
                month: "short",
                day: "numeric",
                hour: "numeric",
                minute: "2-digit",
            }));
        }

        function timelineEventHtml(event, isLast) {
            var meta = event.meta || {};
            var dot = typeof meta.dot === "string" && meta.dot.indexOf("bg-") === 0
                ? meta.dot
                : (disposalTimelineColors[event.type] || "bg-slate-400");
            var actor = meta.actor || meta.transferred_by || meta.borrower || "";

            return (
                '<div class="relative flex gap-3 ' + (isLast ? "" : "pb-4") + '">' +
                    (isLast ? "" : '<span class="absolute bottom-0 left-[6px] top-4 w-px bg-slate-200"></span>') +
                    '<span class="relative z-10 mt-1.5 h-3 w-3 shrink-0 rounded-full ring-4 ring-white ' + dot + '"></span>' +
                    '<div class="min-w-0 flex-1">' +
                        '<div class="flex flex-wrap items-center gap-2">' +
                            '<p class="text-sm font-semibold text-slate-900">' + displayDisposalValue(event.title, "Event") + "</p>" +
                            '<span class="rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 ring-1 ring-slate-200/80">' +
                                displayDisposalValue(event.type_label || event.type) +
                            "</span>" +
                        "</div>" +
                        '<p class="mt-0.5 text-xs text-slate-400">' +
                            formatTimelineDate(event.occurred_at) +
                            (actor ? " · " + escapeDisposalHtml(actor) : "") +
                        "</p>" +
                        (event.description
                            ? '<p class="mt-1 text-[13px] leading-5 text-slate-600">' + escapeDisposalHtml(event.description) + "</p>"
                            : "") +
                    "</div>" +
                "</div>"
            );
        }

        function renderDisposalTimeline(container, events) {
            if (!events.length) {
                container.innerHTML = '<p class="py-3 text-center text-sm text-slate-400">No lifecycle events recorded yet.</p>';
                return;
            }

            var latest = events.slice(0, DISPOSAL_TIMELINE_VISIBLE);
            var older = events.slice(DISPOSAL_TIMELINE_VISIBLE);

            var html = latest.map(function (event, index) {
                return timelineEventHtml(event, index === latest.length - 1 && !older.length);
            }).join("");

            if (older.length) {
                html +=
                    '<div id="viewDisposalTimelineOlder" class="hidden pt-4">' +
                        older.map(function (event, index) {
                            return timelineEventHtml(event, index === older.length - 1);
                        }).join("") +
                    "</div>" +
                    '<button type="button" id="viewDisposalTimelineToggle" ' +
                        'class="mt-3 inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" ' +
                        'data-count="' + older.length + '">' +
                        '<i data-lucide="chevron-down" class="h-3.5 w-3.5"></i>' +
                        '<span>Show ' + older.length + ' older ' + (older.length === 1 ? "event" : "events") + "</span>" +
                    "</button>";
            }

            container.innerHTML = html;

            var toggle = document.getElementById("viewDisposalTimelineToggle");
            var olderEl = document.getElementById("viewDisposalTimelineOlder");
            if (toggle && olderEl) {
                toggle.addEventListener("click", function () {
                    var opening = olderEl.classList.contains("hidden");
                    var count = Number(toggle.getAttribute("data-count"));
                    olderEl.classList.toggle("hidden", !opening);
                    toggle.innerHTML =
                        '<i data-lucide="' + (opening ? "chevron-up" : "chevron-down") + '" class="h-3.5 w-3.5"></i>' +
                        "<span>" + (opening ? "Hide older events" : "Show " + count + " older " + (count === 1 ? "event" : "events")) + "</span>";
                    if (window.lucide) window.lucide.createIcons();
                });
            }

            if (window.lucide) window.lucide.createIcons();
        }

        function loadDisposalTimeline(equipmentId) {
            var container = document.getElementById("viewDisposalTimeline");
            if (!container) return;

            if (!equipmentId) {
                container.innerHTML = '<p class="py-3 text-center text-sm text-slate-400">No equipment record linked to this disposal.</p>';
                return;
            }

            var requestId = ++disposalTimelineRequest;
            container.innerHTML =
                '<div class="flex items-center justify-center gap-2 py-4 text-sm text-slate-400">' +
                    '<i data-lucide="loader-circle" class="h-4 w-4 animate-spin"></i> Loading timeline…' +
                "</div>";
            if (window.lucide) window.lucide.createIcons();

            fetch(@js(url('/maintenance/equipment/timeline')) + "/" + encodeURIComponent(equipmentId), {
                headers: { Accept: "application/json" },
                credentials: "same-origin",
            })
                .then(function (response) {
                    if (!response.ok) throw new Error("HTTP " + response.status);
                    return response.json();
                })
                .then(function (payload) {
                    if (requestId !== disposalTimelineRequest) return;
                    renderDisposalTimeline(container, Array.isArray(payload.events) ? payload.events : []);
                })
                .catch(function () {
                    if (requestId !== disposalTimelineRequest) return;
                    container.innerHTML = '<p class="py-3 text-center text-sm text-rose-500">Could not load the lifecycle timeline.</p>';
                });
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

        function openArchiveModal(id) {
            document.getElementById("archiveDisposalId").value = id;
            document.getElementById("archiveModal").classList.remove("hidden");
            document.getElementById("archiveModal").classList.add("flex");
        }

        function closeArchiveModal() {
            document.getElementById("archiveModal").classList.add("hidden");
            document.getElementById("archiveModal").classList.remove("flex");
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
        window.openArchiveModal = openArchiveModal;
        window.closeArchiveModal = closeArchiveModal;
    </script>

@endsection
