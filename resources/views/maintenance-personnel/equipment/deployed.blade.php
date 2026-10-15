@extends('layouts.maintenance-layout')

@section('title', 'Deployed Equipment')

@section('content')
@php
    $eqImageUrl = function ($path) {
        if (! filled($path)) {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/storage/')) {
            return $path;
        }

        return asset('storage/'.$path);
    };

    $hasFilters = filled($filters['search'] ?? null)
        || filled($filters['room'] ?? null)
        || filled($filters['category'] ?? null)
        || filled($filters['department'] ?? null)
        || filled($filters['status'] ?? null);

    $equipmentTotal = $equipment->total();

    $conditionClass = fn ($condition) => match ($condition) {
        'Good', 'Excellent' => 'bg-emerald-50 text-emerald-700',
        'Fair' => 'bg-amber-50 text-amber-700',
        'Poor', 'Damaged', 'Defective' => 'bg-rose-50 text-rose-700',
        default => 'bg-slate-100 text-slate-600',
    };
    $statusClass = fn ($status) => match ($status) {
        'Active' => 'bg-emerald-50 text-emerald-700',
        'Borrowed' => 'bg-sky-50 text-sky-700',
        'Under Maintenance' => 'bg-amber-50 text-amber-700',
        'For Replacement' => 'bg-rose-50 text-rose-700',
        default => 'bg-slate-100 text-slate-600',
    };

    $transferLabel = $transferNeedsApproval ? 'Send for approval' : 'Transfer';
    $disposalNeedsApproval = $disposalNeedsApproval ?? false;
    $pendingDisposalCount = $pendingDisposalCount ?? 0;
    $selectClass = 'box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white';
@endphp

<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

<div class="space-y-6">
    @if ($transferNeedsApproval || $disposalNeedsApproval)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="flex items-start gap-2">
                <i data-lucide="shield-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                {{ $transferNeedsApproval && $disposalNeedsApproval ? 'Transfers and disposals need' : ($transferNeedsApproval ? 'Transfers need' : 'Disposals need') }}
                the Administrator's approval. Nothing changes until the request is approved.
            </p>
            <div class="flex flex-wrap gap-2">
                @if ($transferNeedsApproval)
                    <a href="{{ $transferRequestsUrl }}" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-200 transition hover:bg-amber-100">
                        Transfer requests
                        @if ($pendingTransferCount > 0)
                            <span class="rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $pendingTransferCount }}</span>
                        @endif
                    </a>
                @endif
                @if ($disposalNeedsApproval)
                    <a href="{{ $disposalRequestsUrl }}?status=all" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-200 transition hover:bg-amber-100">
                        Disposal requests
                        @if ($pendingDisposalCount > 0)
                            <span class="rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $pendingDisposalCount }}</span>
                        @endif
                    </a>
                @endif
            </div>
        </div>
    @endif

    @include('layouts.partials.maintenance-stat-cards', [
        'cards' => [
            ['label' => 'Rooms with equipment', 'hint' => 'Rooms currently holding deployed units', 'value' => number_format($roomsWithStock)],
            ['label' => 'Total deployed qty', 'hint' => 'Combined quantity of every deployed unit', 'value' => number_format($totalDeployedQty)],
            ['label' => 'On floor', 'hint' => 'Excluding holding / storage staging', 'value' => number_format($onFloorQty)],
            ['label' => 'Transfers this month', 'hint' => number_format($transfersLastMonth).' last month', 'value' => number_format($transfersThisMonth)],
        ],
    ])

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Deployed equipment</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            {{ number_format($equipmentTotal) }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Equipment in classrooms, labs and offices. View details, transfer units, or check their transfer logs.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if (! $transferNeedsApproval && $pendingTransferCount > 0)
                        <a href="{{ $transferRequestsUrl }}" class="box-border inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">
                            <i data-lucide="shield-check" class="h-3.5 w-3.5"></i>
                            Transfer requests
                            <span class="rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $pendingTransferCount }}</span>
                        </a>
                    @endif
                    @if (! $disposalNeedsApproval && $pendingDisposalCount > 0)
                        <a href="{{ $disposalRequestsUrl }}" class="box-border inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">
                            <i data-lucide="clipboard-check" class="h-3.5 w-3.5"></i>
                            Disposal approvals
                            <span class="rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $pendingDisposalCount }}</span>
                        </a>
                    @endif
                    <button
                        type="button"
                        id="deployedDisposeSelectedBtn"
                        onclick="openDeployedBatchDispose()"
                        disabled
                        title="Only units marked For Replacement and not on loan can be disposed"
                        class="box-border inline-flex h-9 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-400 transition disabled:cursor-not-allowed enabled:border-rose-600 enabled:bg-rose-600 enabled:text-white enabled:hover:bg-rose-700"
                    >
                        <i data-lucide="archive-x" class="h-3.5 w-3.5"></i>
                        Dispose selected
                        <span id="deployedDisposeSelectedCount" class="hidden rounded-full bg-white/20 px-1.5 py-0.5 text-[10px] font-bold"></span>
                    </button>
                    <button
                        type="button"
                        id="deployedTransferSelectedBtn"
                        onclick="openDeployedBatchTransfer()"
                        disabled
                        class="box-border inline-flex h-9 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-400 transition disabled:cursor-not-allowed enabled:border-[#0025cc] enabled:bg-[#0025cc] enabled:text-white enabled:hover:bg-blue-800"
                    >
                        <i data-lucide="arrow-right-left" class="h-3.5 w-3.5"></i>
                        Transfer selected
                        <span id="deployedSelectedCount" class="hidden rounded-full bg-white/20 px-1.5 py-0.5 text-[10px] font-bold"></span>
                    </button>
                </div>
            </div>

            @if (! empty($attentionFocus))
                <div class="mt-4">
                    @include('partials.attention-focus-chip', ['focus' => $attentionFocus, 'total' => $equipmentTotal])
                </div>
            @endif

            @if (($onLoanOffCampus ?? 0) > 0)
                <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg border border-sky-200 bg-sky-50 px-3.5 py-2.5 text-xs text-sky-800">
                    <i data-lucide="hand-helping" class="h-4 w-4 shrink-0"></i>
                    <span>
                        {{ $onLoanOffCampus }} item{{ $onLoanOffCampus === 1 ? ' is' : 's are' }} out on loan and not counted here, since borrowed equipment is off campus.
                        When returned, it goes back to storage (Inventory).
                    </span>
                    <a href="{{ $deployedUrl }}?status=Borrowed" class="font-semibold underline-offset-2 hover:underline">Show on-loan items</a>
                    @if (! empty($borrowingUrl))
                        <a href="{{ $borrowingUrl }}" class="font-semibold underline-offset-2 hover:underline">Open Borrowing</a>
                    @endif
                </div>
            @endif

            <form
                method="GET"
                action="{{ $deployedUrl }}"
                class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center"
            >
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                    </svg>
                    <input
                        type="search"
                        name="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Search name, tag, serial, room..."
                        class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                    >
                </div>

                <select name="room" class="{{ $selectClass }}">
                    <option value="">All rooms</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->room_id }}" @selected((string) $filters['room'] === (string) $room->room_id)>
                            {{ \App\Support\RoomCategories::isStorageType($room->room_type ?? null) ? 'Storage · '.$room->room_name : $room->room_name }}
                        </option>
                    @endforeach
                </select>

                <select name="category" class="{{ $selectClass }}">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->equipment_category_id }}" @selected((string) $filters['category'] === (string) $category->equipment_category_id)>
                            {{ $category->equipment_category_name }}
                        </option>
                    @endforeach
                </select>

                @if (($transferDepartments ?? collect())->isNotEmpty())
                    <select name="department" class="{{ $selectClass }}" title="Office the equipment is charged to">
                        <option value="">All departments</option>
                        @foreach ($transferDepartments as $department)
                            <option value="{{ $department->department_id }}" @selected((string) ($filters['department'] ?? '') === (string) $department->department_id)>
                                {{ $department->department_name }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <select name="status" class="{{ $selectClass }}">
                    <option value="">All status</option>
                    @foreach (['Active', 'Borrowed', 'Under Maintenance', 'For Replacement'] as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                    @endforeach
                </select>

                <button
                    type="submit"
                    class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold leading-none text-white transition hover:bg-blue-800"
                >
                    <i data-lucide="filter" class="h-4 w-4 shrink-0"></i>
                    Apply
                </button>

                @if ($hasFilters)
                    <a
                        href="{{ $deployedUrl }}"
                        class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50"
                    >
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="w-10 py-3 pl-5 pr-0">
                            <input
                                type="checkbox"
                                id="deployedSelectAll"
                                class="h-4 w-4 rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]"
                                aria-label="Select every unit on this page"
                            >
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Equipment</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Location</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Condition</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Department</th>
                        <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($equipment as $item)
                        @php
                            $isBulk = ($item->equipment_tracking_mode ?? 'Individual') === 'Bulk';
                            $identifier = collect([
                                $item->equipment_asset_tag ? 'Tag: '.$item->equipment_asset_tag : null,
                                $item->equipment_serial_number ? 'Serial: '.$item->equipment_serial_number : null,
                            ])->filter()->implode(' · ');
                            $unitLabel = $identifier ? $item->equipment_name.' ('.$identifier.')' : $item->equipment_name;
                            $zone = $item->equipment_placement_zone ?: $item->equipment_current_location;
                            $inStorage = \App\Support\RoomCategories::isStorageType($item->room_type ?? null);
                            $canTransferRow = ! $item->transfer_pending && ! $item->transfer_lock;
                            $canDisposeRow = $item->equipment_inventory_status === 'For Replacement'
                                && ! $item->open_loan
                                && ! $item->transfer_pending
                                && ! $item->disposal_pending;
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="py-4 pl-5 pr-0">
                                @if ($canTransferRow || $canDisposeRow)
                                    <input
                                        type="checkbox"
                                        class="deployed-unit-checkbox h-4 w-4 rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]"
                                        value="{{ $item->equipment_id }}"
                                        data-label="{{ $unitLabel }}"
                                        data-transferable="{{ $canTransferRow ? '1' : '0' }}"
                                        data-disposable="{{ $canDisposeRow ? '1' : '0' }}"
                                        aria-label="Select {{ $unitLabel }}"
                                    >
                                @elseif ($item->transfer_pending)
                                    <input type="checkbox" disabled class="h-4 w-4 cursor-not-allowed rounded border-gray-200 bg-gray-100" aria-label="{{ $unitLabel }} is awaiting transfer approval">
                                @else
                                    <input type="checkbox" disabled class="h-4 w-4 cursor-not-allowed rounded border-gray-200 bg-gray-100" title="{{ $item->transfer_lock }}" aria-label="{{ $item->transfer_lock }}">
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-950">{{ $item->equipment_name }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $item->equipment_asset_tag ?: 'No asset tag' }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    {{ $item->equipment_category_name ?: 'Uncategorized' }}
                                    ·
                                    @if ($isBulk)
                                        Qty {{ number_format((int) $item->equipment_quantity) }}
                                    @else
                                        Serial {{ $item->equipment_serial_number ?: '—' }}
                                    @endif
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <p class="text-gray-800">{{ $item->room_name ?: '—' }}</p>
                                    @if ($inStorage)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">Storage</span>
                                    @endif
                                </div>
                                @if (filled($zone))
                                    <p class="mt-0.5 text-xs text-gray-400">{{ $zone }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-medium {{ $conditionClass($item->equipment_condition_status) }}">
                                    {{ $item->equipment_condition_status ?: '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-medium {{ $statusClass($item->equipment_inventory_status) }}">
                                    {{ $item->equipment_inventory_status ?: '—' }}
                                </span>
                                @if ($item->open_loan)
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $isBulk && $item->open_loan->quantity > 0 ? $item->open_loan->quantity.' on loan · ' : '' }}{{ $item->open_loan->borrower ?: 'Borrower not recorded' }}
                                    </p>
                                    @if ($item->open_loan->due)
                                        <p class="text-xs {{ $item->open_loan->overdue ? 'font-semibold text-rose-600' : 'text-gray-400' }}">
                                            {{ $item->open_loan->overdue ? 'Overdue since' : 'Due' }} {{ \Illuminate\Support\Carbon::parse($item->open_loan->due)->format('M j, Y') }}
                                        </p>
                                    @endif
                                @endif
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ ($item->unit_department_name ?? null) ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        onclick='openEquipmentModal(@json(\App\Support\LayoutEquipmentPayload::fromRow($item, $eqImageUrl($item->equipment_image ?? null))))'
                                        class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                        title="View equipment details"
                                    >
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                        
                                    </button>
                                    @if ($item->transfer_pending)
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-3 py-3 text-xs font-semibold text-amber-700" title="A transfer request for this unit is waiting for the Administrator">
                                            <i data-lucide="hourglass" class="h-3.5 w-3.5"></i>
                                            Awaiting approval
                                        </span>
                                    @elseif ($item->disposal_pending)
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-rose-50 px-3 py-3 text-xs font-semibold text-rose-700" title="A disposal request for this unit is waiting for the Administrator">
                                            <i data-lucide="hourglass" class="h-3.5 w-3.5"></i>
                                            Disposal pending
                                        </span>
                                    @elseif ($item->transfer_lock)
                                        <span
                                            class="inline-flex h-10 items-center justify-center rounded-lg bg-gray-100 px-3 text-gray-400"
                                            title="{{ $item->transfer_lock }}"
                                            aria-label="{{ $item->transfer_lock }}"
                                        >
                                            <i data-lucide="lock" class="h-3.5 w-3.5"></i>
                                        </span>
                                    @else
                                        <button
                                            type="button"
                                            onclick="openDeployedTransfer(this)"
                                            data-id="{{ $item->equipment_id }}"
                                            data-name="{{ $item->equipment_name }}"
                                            data-room="{{ $item->room_name }}"
                                            data-tag="{{ $item->equipment_asset_tag }}"
                                            data-serial="{{ $item->equipment_serial_number }}"
                                            class="inline-flex items-center gap-1 rounded-lg bg-[#0025cc] px-3 py-3 text-xs font-semibold text-white transition hover:bg-blue-800"
                                        >
                                            <i data-lucide="arrow-right-left" class="h-3.5 w-3.5"></i>
                                            
                                        </button>
                                    @endif
                                    <button
                                        type="button"
                                        onclick="openDeployedTransferLogs(this)"
                                        data-id="{{ $item->equipment_id }}"
                                        data-title="{{ $identifier ? $item->equipment_name.' · '.$identifier : $item->equipment_name }}"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-900"
                                        title="Transfer logs"
                                        aria-label="Transfer logs for {{ $unitLabel }}"
                                    >
                                        <i data-lucide="route" class="h-3.5 w-3.5"></i>
                                    </button>
                                    @if ($canDisposeRow)
                                        <button
                                            type="button"
                                            onclick="openDeployedDispose(this)"
                                            data-id="{{ $item->equipment_id }}"
                                            data-label="{{ $unitLabel }}"
                                            data-room="{{ $item->room_name }}"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-rose-200 text-rose-600 transition hover:bg-rose-50"
                                            title="{{ $disposalNeedsApproval ? 'Request disposal' : 'Dispose equipment' }}"
                                            aria-label="Dispose {{ $unitLabel }}"
                                        >
                                            <i data-lucide="archive-x" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-900">No deployed equipment matches your filters</p>
                                <p class="mt-1 text-sm text-gray-500">Try another room, category, or status, or clear the search. Equipment still in storage is transferred from Inventory.</p>
                                @if ($hasFilters)
                                    <a
                                        href="{{ $deployedUrl }}"
                                        class="mt-4 inline-flex text-sm font-semibold text-[#0025cc] hover:underline"
                                    >
                                        Clear filters
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($equipment->hasPages() || $equipmentTotal > 0)
            <div class="flex flex-col gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    Showing <span class="font-semibold text-slate-700">{{ $equipment->firstItem() }}</span>
                    to <span class="font-semibold text-slate-700">{{ $equipment->lastItem() }}</span>
                    of <span class="font-semibold text-slate-700">{{ $equipmentTotal }}</span> equipment
                </p>
                <div>{{ $equipment->links() }}</div>
            </div>
        @endif
    </div>
</div>

@include('maintenance-personnel.equipment.partials.equipment-asset-drawer')

{{-- Transfer one unit --}}
<div
    id="deployedTransferModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    onclick="if (event.target === this) closeDeployedModal('deployedTransferModal')"
>
    <div class="flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex shrink-0 items-start justify-between border-b border-slate-200 px-6 py-5">
            <div class="min-w-0">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-[#0025cc]">
                    <i data-lucide="arrow-right-left" class="h-4 w-4"></i>
                </div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer equipment</h2>
            </div>
            <button type="button" onclick="closeDeployedModal('deployedTransferModal')" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <form action="{{ $transferActionBase }}/transfer" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <input type="hidden" id="deployedTransferEquipmentId" name="equipment_id">

            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6">
                <div class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-slate-50/60">
                    @foreach (['Name' => 'Equipment', 'Tag' => 'Asset tag', 'Serial' => 'Serial number', 'Room' => 'Current room'] as $suffix => $label)
                        <div class="flex items-center justify-between gap-6 px-4 py-3">
                            <span class="shrink-0 text-sm text-slate-500">{{ $label }}</span>
                            <span id="deployedTransfer{{ $suffix }}" class="min-w-0 truncate text-right text-sm font-medium text-slate-900">—</span>
                        </div>
                    @endforeach
                </div>

                @include('maintenance-personnel.equipment.partials.inventory-transfer-destination', [
                    'prefix' => 'deployedTransfer',
                    'roomOptions' => $rooms,
                    'roomPlaceholder' => 'Select a room',
                ])

                <div>
                    <div class="mb-2 flex items-center justify-between gap-4">
                        <label for="deployedTransferRemarks" class="text-sm font-semibold text-slate-700">Remarks</label>
                        <span class="text-xs text-slate-400">Optional</span>
                    </div>
                    <textarea id="deployedTransferRemarks" name="remarks" rows="3" placeholder="Why is it moving?" class="w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"></textarea>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button type="button" onclick="closeDeployedModal('deployedTransferModal')" class="inline-flex h-10 items-center justify-center px-2 text-sm font-medium text-slate-600 transition hover:text-slate-950">Cancel</button>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">
                    {{ $transferNeedsApproval ? 'Send for approval' : 'Transfer equipment' }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Transfer the selected units --}}
<div
    id="deployedBatchTransferModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    onclick="if (event.target === this) closeDeployedModal('deployedBatchTransferModal')"
>
    <div class="flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex shrink-0 items-start justify-between gap-6 border-b border-slate-200 px-6 py-5">
            <div class="min-w-0">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                    <i data-lucide="check-square" class="h-4 w-4"></i>
                </div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer selected</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Move <span id="deployedBatchCount" class="font-semibold text-slate-800">0</span> selected unit(s) together.
                </p>
                <p id="deployedBatchSummary" class="mt-2 text-xs text-slate-400"></p>
            </div>
            <button type="button" onclick="closeDeployedModal('deployedBatchTransferModal')" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <form action="{{ $transferActionBase }}/transfer-batch" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div id="deployedBatchIds"></div>

            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6">
                @include('maintenance-personnel.equipment.partials.inventory-transfer-destination', [
                    'prefix' => 'deployedBatchTransfer',
                    'roomOptions' => $deployRooms,
                ])
                <p class="text-xs text-slate-400">Batch transfers go to classrooms, labs and offices. To return a unit to storage, transfer it on its own.</p>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-4">
                        <label for="deployedBatchRemarks" class="text-sm font-semibold text-slate-700">Remarks</label>
                        <span class="text-xs text-slate-400">Optional</span>
                    </div>
                    <textarea id="deployedBatchRemarks" name="remarks" rows="3" placeholder="Why are they moving?" class="w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"></textarea>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button type="button" onclick="closeDeployedModal('deployedBatchTransferModal')" class="inline-flex h-10 items-center justify-center px-2 text-sm font-medium text-slate-600 transition hover:text-slate-950">Cancel</button>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">
                    {{ $transferNeedsApproval ? 'Send for approval' : 'Transfer selected' }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Transfer logs for one unit --}}
<div
    id="deployedLogsModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    onclick="if (event.target === this) closeDeployedModal('deployedLogsModal')"
>
    <div class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex shrink-0 items-start justify-between border-b border-slate-200 px-6 py-5">
            <div class="min-w-0">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                    <i data-lucide="route" class="h-4 w-4"></i>
                </div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer logs</h2>
                <p id="deployedLogsTitle" class="mt-1 truncate text-sm text-slate-500"></p>
            </div>
            <button type="button" onclick="closeDeployedModal('deployedLogsModal')" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <div id="deployedLogsContent" class="min-h-0 flex-1 overflow-y-auto p-6"></div>
    </div>
</div>

{{-- Dispose a unit marked For Replacement --}}
<div
    id="deployedDisposeModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    onclick="if (event.target === this) closeDeployedModal('deployedDisposeModal')"
>
    <div class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
        <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
            <div class="min-w-0">
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                    <i data-lucide="archive-x" class="h-4 w-4"></i>
                </div>
                <h2 id="deployedDisposeTitle" class="text-lg font-semibold tracking-tight text-slate-950">Dispose equipment</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    @if ($disposalNeedsApproval)
                        The Administrator has to approve this first. Once approved, the equipment moves to the Disposal module.
                    @else
                        Units marked For Replacement. Disposing moves them to the Disposal module until they are restored there.
                    @endif
                </p>
            </div>
            <button type="button" onclick="closeDeployedModal('deployedDisposeModal')" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <form action="{{ $disposeAction }}" method="POST">
            @csrf
            <input type="hidden" name="equipment_id" id="deployedDisposeEquipmentId">
            <div id="deployedDisposeIds"></div>

            <div class="max-h-[60vh] space-y-5 overflow-y-auto border-y border-slate-100 px-6 py-5">
                <div>
                    <p class="mb-1 text-sm font-medium text-slate-700">Equipment</p>
                    <div id="deployedDisposeEquipmentName" class="max-h-32 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900"></div>
                </div>

                <div>
                    <label for="deployedDisposeReason" class="mb-2 block text-sm font-medium text-slate-700">
                        Reason <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="deployedDisposeReason"
                        name="reason"
                        rows="3"
                        required
                        maxlength="1000"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    ></textarea>
                </div>

                <div>
                    <label for="deployedDisposeMethod" class="mb-2 block text-sm font-medium text-slate-700">Method</label>
                    <select
                        id="deployedDisposeMethod"
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
                    <label for="deployedDisposeResidual" class="mb-2 block text-sm font-medium text-slate-700">Residual value (₱)</label>
                    <input
                        id="deployedDisposeResidual"
                        name="residual_value"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="Optional salvage / residual amount"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >
                </div>

                <div>
                    <label for="deployedDisposeLocation" class="mb-2 block text-sm font-medium text-slate-700">Location</label>
                    <input
                        id="deployedDisposeLocation"
                        name="location"
                        type="text"
                        maxlength="255"
                        placeholder="Area / room"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button type="button" onclick="closeDeployedModal('deployedDisposeModal')" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                    Cancel
                </button>
                <button type="submit" class="h-10 rounded-xl bg-rose-600 px-5 text-sm font-medium text-white transition hover:bg-rose-700">
                    {{ $disposalNeedsApproval ? 'Send for approval' : 'Dispose' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function deployedEscape(value) {
        return String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function showDeployedModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide) window.lucide.createIcons();
    }

    function closeDeployedModal(id) {
        const modal = document.getElementById(id);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function deployedUnitCheckboxes() {
        return Array.from(document.querySelectorAll('.deployed-unit-checkbox'));
    }

    function deployedSelected(flag) {
        return deployedUnitCheckboxes().filter((box) => box.checked && box.dataset[flag] === '1');
    }

    function syncDeployedSelectionButton(buttonId, countId, total) {
        const button = document.getElementById(buttonId);
        const count = document.getElementById(countId);
        button.disabled = total === 0;
        count.textContent = String(total);
        count.classList.toggle('hidden', total === 0);
    }

    function updateDeployedSelection() {
        const boxes = deployedUnitCheckboxes();
        const selected = boxes.filter((box) => box.checked);

        syncDeployedSelectionButton('deployedTransferSelectedBtn', 'deployedSelectedCount', deployedSelected('transferable').length);
        syncDeployedSelectionButton('deployedDisposeSelectedBtn', 'deployedDisposeSelectedCount', deployedSelected('disposable').length);

        const selectAll = document.getElementById('deployedSelectAll');
        if (selectAll) {
            selectAll.disabled = boxes.length === 0;
            selectAll.checked = boxes.length > 0 && selected.length === boxes.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < boxes.length;
        }
    }

    function openDeployedTransfer(button) {
        const data = button.dataset;
        document.getElementById('deployedTransferEquipmentId').value = data.id;
        document.getElementById('deployedTransferName').textContent = data.name || 'Equipment';
        document.getElementById('deployedTransferTag').textContent = data.tag || '—';
        document.getElementById('deployedTransferSerial').textContent = data.serial || '—';
        document.getElementById('deployedTransferRoom').textContent = data.room || '—';
        document.getElementById('deployedTransferRemarks').value = '';
        resetInventoryTransferDestination('deployedTransfer');
        showDeployedModal('deployedTransferModal');
    }

    function openDeployedBatchTransfer() {
        const selected = deployedSelected('transferable');
        if (selected.length === 0) return;

        const ids = document.getElementById('deployedBatchIds');
        ids.innerHTML = '';
        selected.forEach((box) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'equipment_ids[]';
            input.value = box.value;
            ids.appendChild(input);
        });

        const labels = selected.map((box) => box.dataset.label || 'Equipment');
        document.getElementById('deployedBatchCount').textContent = String(selected.length);
        document.getElementById('deployedBatchSummary').textContent = labels.slice(0, 3).join(' · ')
            + (labels.length > 3 ? ` · +${labels.length - 3} more` : '');
        document.getElementById('deployedBatchRemarks').value = '';
        resetInventoryTransferDestination('deployedBatchTransfer');
        showDeployedModal('deployedBatchTransferModal');
    }

    async function openDeployedTransferLogs(button) {
        const content = document.getElementById('deployedLogsContent');
        document.getElementById('deployedLogsTitle').textContent = button.dataset.title || '';
        content.innerHTML = '<p class="py-12 text-center text-sm text-slate-500">Loading transfer logs...</p>';
        showDeployedModal('deployedLogsModal');

        try {
            const response = await fetch('/maintenance/equipment/transfers/' + encodeURIComponent(button.dataset.id), {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const logs = await response.json();

            if (!Array.isArray(logs) || logs.length === 0) {
                content.innerHTML = `
                    <div class="py-12 text-center">
                        <p class="text-sm font-medium text-slate-900">No transfer logs yet</p>
                        <p class="mt-1 text-sm text-slate-500">Moves of this unit will be listed here.</p>
                    </div>`;
                return;
            }

            content.innerHTML = '<div class="space-y-3">' + logs.map((log) => `
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm font-semibold text-slate-900">
                        ${deployedEscape(log.from_room_name ?? 'No previous room')}
                        <span class="mx-2 text-slate-400">→</span>
                        ${deployedEscape(log.to_room_name ?? 'Unknown room')}
                    </p>
                    ${log.to_destination_label ? `<p class="mt-1 text-xs font-medium text-[#1d5d93]">Handed to ${deployedEscape(log.to_destination_label)}</p>` : ''}
                    <p class="mt-1 text-xs text-slate-400">${deployedEscape(log.created_at ?? '')}</p>
                    ${log.remarks ? `<p class="mt-3 border-t border-slate-100 pt-3 text-sm leading-6 text-slate-700">${deployedEscape(log.remarks)}</p>` : ''}
                </div>`).join('') + '</div>';
        } catch (error) {
            content.innerHTML = '<p class="py-12 text-center text-sm font-medium text-rose-600">Could not load the transfer logs. Try again.</p>';
        }
    }

    function resetDeployedDisposeForm(location) {
        document.getElementById('deployedDisposeReason').value = 'Marked For Replacement.';
        document.getElementById('deployedDisposeMethod').value = '';
        document.getElementById('deployedDisposeResidual').value = '';
        document.getElementById('deployedDisposeLocation').value = location || '';
    }

    function openDeployedDispose(button) {
        document.getElementById('deployedDisposeIds').innerHTML = '';
        document.getElementById('deployedDisposeEquipmentId').value = button.dataset.id;
        document.getElementById('deployedDisposeTitle').textContent = @json($disposalNeedsApproval ? 'Request disposal' : 'Dispose equipment');
        document.getElementById('deployedDisposeEquipmentName').textContent = button.dataset.label || 'Equipment';
        resetDeployedDisposeForm(button.dataset.room);
        showDeployedModal('deployedDisposeModal');
    }

    function openDeployedBatchDispose() {
        const selected = deployedSelected('disposable');
        if (selected.length === 0) return;

        const ids = document.getElementById('deployedDisposeIds');
        ids.innerHTML = '';
        selected.forEach((box) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'equipment_ids[]';
            input.value = box.value;
            ids.appendChild(input);
        });
        document.getElementById('deployedDisposeEquipmentId').value = '';

        const noun = selected.length === 1 ? 'unit' : 'units';
        document.getElementById('deployedDisposeTitle').textContent = (@json($disposalNeedsApproval) ? 'Request disposal of ' : 'Dispose ')
            + selected.length + ' ' + noun;
        document.getElementById('deployedDisposeEquipmentName').innerHTML = '<ul class="space-y-1">'
            + selected.map((box) => `<li>${deployedEscape(box.dataset.label || 'Equipment')}</li>`).join('')
            + '</ul>';
        resetDeployedDisposeForm('');
        showDeployedModal('deployedDisposeModal');
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('deployedSelectAll')?.addEventListener('change', function () {
            deployedUnitCheckboxes().forEach((box) => { box.checked = this.checked; });
            updateDeployedSelection();
        });

        deployedUnitCheckboxes().forEach((box) => box.addEventListener('change', updateDeployedSelection));
        updateDeployedSelection();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        ['deployedTransferModal', 'deployedBatchTransferModal', 'deployedLogsModal', 'deployedDisposeModal'].forEach(closeDeployedModal);
    });
</script>
@endsection
