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

    $hasActiveSearch = filled($filters['search'] ?? null);
    $hasActiveStatus = filled($filters['status'] ?? null);
    $hasActiveRoom = filled($filters['room'] ?? null);
    $hasActiveCategory = filled($filters['category'] ?? null);
    $hasActiveDepartment = filled($filters['department'] ?? null);
    $secondaryFilterCount = ($hasActiveRoom ? 1 : 0)
        + ($hasActiveCategory ? 1 : 0)
        + ($hasActiveDepartment ? 1 : 0);
    $hasFilters = $hasActiveSearch
        || $hasActiveStatus
        || $hasActiveRoom
        || $hasActiveCategory
        || $hasActiveDepartment;

    $selectedRoomName = $hasActiveRoom
        ? optional($rooms->firstWhere('room_id', (int) $filters['room']))->room_name
        : null;
    $selectedCategoryName = $hasActiveCategory
        ? optional($categories->firstWhere('equipment_category_id', (int) $filters['category']))->equipment_category_name
        : null;
    $selectedDepartmentName = $hasActiveDepartment
        ? optional(($transferDepartments ?? collect())->firstWhere('department_id', (int) $filters['department']))->department_name
        : null;

    $equipmentTotal = $equipment->total();

    $conditionClass = fn ($condition) => match ($condition) {
        'Good' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/80',
        'Under Maintenance' => 'bg-amber-50 text-amber-700 ring-amber-200/80',
        'Defective' => 'bg-orange-50 text-orange-700 ring-orange-200/80',
        'Damaged' => 'bg-rose-50 text-rose-700 ring-rose-200/80',
        'Disposed' => 'bg-slate-100 text-slate-600 ring-slate-200/80',
        default => 'bg-slate-100 text-slate-600 ring-slate-200/80',
    };
    $conditionDotClass = fn ($condition) => match ($condition) {
        'Good' => 'bg-emerald-500',
        'Under Maintenance' => 'bg-amber-500',
        'Defective' => 'bg-orange-500',
        'Damaged' => 'bg-rose-500',
        default => 'bg-slate-400',
    };

    $statusClass = fn ($status) => match ($status) {
        'Active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/80',
        'Borrowed' => 'bg-sky-50 text-sky-700 ring-sky-200/80',
        'Under Maintenance' => 'bg-amber-50 text-amber-700 ring-amber-200/80',
        'For Replacement' => 'bg-orange-50 text-orange-700 ring-orange-200/80',
        'Replaced' => 'bg-blue-50 text-blue-700 ring-blue-200/80',
        default => 'bg-slate-100 text-slate-600 ring-slate-200/80',
    };
    $statusDotClass = fn ($status) => match ($status) {
        'Active' => 'bg-emerald-500',
        'Borrowed' => 'bg-sky-500',
        'Under Maintenance' => 'bg-amber-500',
        'For Replacement' => 'bg-orange-500',
        'Replaced' => 'bg-blue-500',
        default => 'bg-slate-400',
    };

    $transferLabel = $transferNeedsApproval ? 'Send for approval' : 'Transfer';
    $disposalNeedsApproval = $disposalNeedsApproval ?? false;
    $pendingDisposalCount = $pendingDisposalCount ?? 0;
    $deployedExportPdfUrl = request()->is('admin/*')
        ? '/admin/inventory/deployed/export-pdf'
        : '/maintenance/equipment/deployed/export-pdf';
    $exportQuery = http_build_query(request()->only(['search', 'room', 'category', 'department', 'status', 'on_floor', 'include_storage']));
    $pendingApprovalsBadge = (! $transferNeedsApproval ? (int) $pendingTransferCount : 0)
        + (! $disposalNeedsApproval ? (int) $pendingDisposalCount : 0);
@endphp

<div class="space-y-4">
    {{-- COMPACT PAGE HEADER (matching All Equipment) --}}
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex min-w-0 items-center gap-2">
            <h1 class="truncate text-lg font-semibold tracking-tight text-slate-900">
                Deployed Equipment
            </h1>
            <span class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                {{ number_format($equipmentTotal) }} {{ \Illuminate\Support\Str::plural('item', $equipmentTotal) }}
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <button
                    type="button"
                    id="deployedActionsMenuBtn"
                    onclick="toggleDeployedMenu(this, 'deployedActionsMenu', event)"
                    aria-haspopup="menu"
                    aria-expanded="false"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                >
                    <i data-lucide="more-horizontal" class="h-4 w-4 text-slate-500"></i>
                    <span>Actions</span>
                    <span
                        id="deployedActionsBadge"
                        data-base-count="{{ $pendingApprovalsBadge }}"
                        class="{{ $pendingApprovalsBadge > 0 ? '' : 'hidden' }} inline-flex min-w-[1.15rem] items-center justify-center rounded-full bg-slate-900 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white"
                    >{{ $pendingApprovalsBadge }}</span>
                    <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-slate-400"></i>
                </button>

                <div
                    id="deployedActionsMenu"
                    data-deployed-menu
                    role="menu"
                    class="fixed z-[90] hidden w-60 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-xl shadow-slate-950/10"
                >
                    <p class="px-2.5 pb-1 pt-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Bulk tools</p>
                    <button
                        type="button"
                        id="deployedTransferSelectedBtn"
                        role="menuitem"
                        onclick="closeDeployedMenus(); openDeployedBatchTransfer()"
                        disabled
                        class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950 disabled:cursor-not-allowed disabled:opacity-45"
                    >
                        <span class="inline-flex items-center gap-2">
                            <i data-lucide="arrow-right-left" class="h-3.5 w-3.5 text-sky-600"></i>
                            <span>Transfer selected</span>
                        </span>
                        <span id="deployedSelectedCount" class="hidden rounded-full bg-sky-600 px-1.5 py-0.5 text-[10px] font-bold text-white">0</span>
                    </button>
                    <button
                        type="button"
                        id="deployedDisposeSelectedBtn"
                        role="menuitem"
                        onclick="closeDeployedMenus(); openDeployedBatchDispose()"
                        disabled
                        class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-45"
                    >
                        <span class="inline-flex items-center gap-2">
                            <i data-lucide="archive-x" class="h-3.5 w-3.5 text-rose-600"></i>
                            <span>Dispose selected</span>
                        </span>
                        <span id="deployedDisposeSelectedCount" class="hidden rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">0</span>
                    </button>
                    <button
                        type="button"
                        id="deployedTransferAllBtn"
                        role="menuitem"
                        onclick="closeDeployedMenus(); openDeployedTransferAll()"
                        @if (empty($deployedTransferIds ?? [])) disabled @endif
                        class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950 disabled:cursor-not-allowed disabled:opacity-45"
                    >
                        <span class="inline-flex items-center gap-2">
                            <i data-lucide="boxes" class="h-3.5 w-3.5 text-slate-500"></i>
                            <span>Transfer all filtered</span>
                        </span>
                        @if (! empty($deployedTransferIds ?? []))
                            <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">{{ number_format(count($deployedTransferIds)) }}</span>
                        @endif
                    </button>

                    @if ((! $transferNeedsApproval && $pendingTransferCount > 0) || (! $disposalNeedsApproval && $pendingDisposalCount > 0))
                        <div class="my-1 border-t border-slate-100"></div>
                        <p class="px-2.5 pb-1 pt-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Approvals</p>
                        @if (! $transferNeedsApproval && $pendingTransferCount > 0)
                            <a
                                href="{{ $transferRequestsUrl }}"
                                role="menuitem"
                                class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <i data-lucide="shield-check" class="h-3.5 w-3.5 text-slate-400"></i>
                                    <span>Transfer requests</span>
                                </span>
                                <span class="rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $pendingTransferCount }}</span>
                            </a>
                        @endif
                        @if (! $disposalNeedsApproval && $pendingDisposalCount > 0)
                            <a
                                href="{{ $disposalRequestsUrl }}"
                                role="menuitem"
                                class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <i data-lucide="clipboard-check" class="h-3.5 w-3.5 text-slate-400"></i>
                                    <span>Disposal approvals</span>
                                </span>
                                <span class="rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $pendingDisposalCount }}</span>
                            </a>
                        @endif
                    @endif

                    <div class="my-1 border-t border-slate-100"></div>
                    <a
                        href="{{ $deployedExportPdfUrl }}?{{ $exportQuery }}"
                        role="menuitem"
                        class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                    >
                        <i data-lucide="file-down" class="h-3.5 w-3.5 text-slate-400"></i>
                        <span>Export PDF</span>
                    </a>
                </div>
            </div>

            <a
                href="{{ $deployedExportPdfUrl }}?{{ $exportQuery }}"
                class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800"
            >
                <i data-lucide="file-down" class="h-3.5 w-3.5"></i>
                <span>Export PDF</span>
            </a>
        </div>
    </div>

    @if ($transferNeedsApproval || $disposalNeedsApproval)
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-xs text-amber-900">
            <p class="flex items-center gap-2">
                <i data-lucide="shield-alert" class="h-4 w-4 shrink-0"></i>
                <span>
                    {{ $transferNeedsApproval && $disposalNeedsApproval ? 'Transfers and disposals require' : ($transferNeedsApproval ? 'Transfers require' : 'Disposals require') }}
                    Administrator approval before taking effect.
                </span>
            </p>
            <div class="flex flex-wrap items-center gap-2">
                @if ($transferNeedsApproval)
                    <a href="{{ $transferRequestsUrl }}" class="inline-flex h-7 items-center gap-1.5 rounded-lg bg-white px-2.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-200 transition hover:bg-amber-100">
                        Transfer requests
                        @if ($pendingTransferCount > 0)
                            <span class="rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $pendingTransferCount }}</span>
                        @endif
                    </a>
                @endif
                @if ($disposalNeedsApproval)
                    <a href="{{ $disposalRequestsUrl }}?status=all" class="inline-flex h-7 items-center gap-1.5 rounded-lg bg-white px-2.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-200 transition hover:bg-amber-100">
                        Disposal requests
                        @if ($pendingDisposalCount > 0)
                            <span class="rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $pendingDisposalCount }}</span>
                        @endif
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if (($onLoanOffCampus ?? 0) > 0)
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 rounded-xl border border-sky-200 bg-sky-50 px-3.5 py-2.5 text-xs text-sky-900">
            <i data-lucide="hand-helping" class="h-4 w-4 shrink-0 text-sky-700"></i>
            <span class="min-w-0 flex-1 font-medium">
                {{ $onLoanOffCampus }} borrowed {{ $onLoanOffCampus === 1 ? 'item is' : 'items are' }} off campus and not listed here.
            </span>
            <a href="{{ $deployedUrl }}?status=Borrowed" class="shrink-0 font-semibold text-sky-800 hover:underline">View borrowed</a>
            @if (! empty($borrowingUrl))
                <a href="{{ $borrowingUrl }}" class="shrink-0 font-semibold text-sky-800 hover:underline">Open Borrowing</a>
            @endif
        </div>
    @endif

    {{-- DEPLOYED EQUIPMENT TABLE CARD (matching All Equipment) --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 bg-white px-4 py-2.5">
            @if (! empty($attentionFocus))
                <div class="mb-2.5">
                    @include('partials.attention-focus-chip', ['focus' => $attentionFocus, 'total' => $equipmentTotal])
                </div>
            @endif

            <form
                id="deployedFilterForm"
                method="GET"
                action="{{ $deployedUrl }}"
                class="space-y-2"
            >
                {{-- COMPACT SINGLE-ROW TOOLBAR (Search + Status + Filters toggle) --}}
                <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap">
                    <div class="relative min-w-[200px] flex-1">
                        <i
                            data-lucide="search"
                            class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
                        ></i>
                        <input
                            type="search"
                            name="search"
                            value="{{ $filters['search'] }}"
                            placeholder="Search name, tag, serial, room…"
                            class="h-9 w-full rounded-lg border border-slate-200 bg-slate-50/70 pl-9 pr-3 text-xs text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100"
                        >
                    </div>

                    <div class="relative w-full sm:w-44 lg:shrink-0">
                        <i
                            data-lucide="list-filter"
                            class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
                        ></i>
                        <select
                            name="status"
                            aria-label="Filter by status"
                            onchange="this.form.submit()"
                            class="h-9 w-full appearance-none rounded-lg border border-slate-200 bg-white pl-8 pr-8 text-xs font-medium text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                        >
                            <option value="">All Status</option>
                            @foreach (['Active', 'Borrowed', 'Under Maintenance', 'For Replacement', 'Replaced'] as $status)
                                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                        <i
                            data-lucide="chevron-down"
                            class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
                        ></i>
                    </div>

                    <button
                        type="button"
                        id="deployedSecondaryFiltersBtn"
                        onclick="toggleDeployedSecondaryFilters()"
                        aria-expanded="false"
                        aria-controls="deployedSecondaryFiltersPanel"
                        class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-xs font-semibold transition {{ $secondaryFilterCount > 0 ? 'border-blue-200 bg-blue-50/70 text-[#0025cc] hover:bg-blue-50' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                    >
                        <i data-lucide="sliders-horizontal" class="h-3.5 w-3.5"></i>
                        <span>Filters</span>
                        @if ($secondaryFilterCount > 0)
                            <span class="inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-[#0025cc] px-1 text-[10px] font-bold leading-none text-white">{{ $secondaryFilterCount }}</span>
                        @endif
                    </button>
                </div>

                {{-- COMPACT COLLAPSIBLE FILTER DRAWER --}}
                <div
                    id="deployedSecondaryFiltersPanel"
                    class="hidden rounded-lg border border-slate-200 bg-slate-50/80 p-3"
                >
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 {{ ($transferDepartments ?? collect())->isNotEmpty() ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }}">
                        <div>
                            <label for="deployedFilterRoom" class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                Location / Room
                            </label>
                            <div class="relative">
                                <select
                                    id="deployedFilterRoom"
                                    name="room"
                                    data-searchable="1"
                                    data-search-placeholder="Search room…"
                                    class="h-9 w-full appearance-none rounded-lg border border-slate-200 bg-white pl-2.5 pr-8 text-xs text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                                >
                                    <option value="">All Rooms</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->room_id }}" @selected((string) $filters['room'] === (string) $room->room_id)>
                                            {{ \App\Support\RoomCategories::isStorageType($room->room_type ?? null) ? 'Storage · '.$room->room_name : $room->room_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <i data-lucide="chevron-down" class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                            </div>
                        </div>

                        <div>
                            <label for="deployedFilterCategory" class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                Classification
                            </label>
                            <div class="relative">
                                <select
                                    id="deployedFilterCategory"
                                    name="category"
                                    data-searchable="1"
                                    data-search-placeholder="Search classification…"
                                    class="h-9 w-full appearance-none rounded-lg border border-slate-200 bg-white pl-2.5 pr-8 text-xs text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                                >
                                    <option value="">All Classifications</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->equipment_category_id }}" @selected((string) $filters['category'] === (string) $category->equipment_category_id)>
                                            {{ $category->equipment_category_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <i data-lucide="chevron-down" class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                            </div>
                        </div>

                        @if (($transferDepartments ?? collect())->isNotEmpty())
                            <div>
                                <label for="deployedFilterDepartment" class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                    Department
                                </label>
                                <div class="relative">
                                    <select
                                        id="deployedFilterDepartment"
                                        name="department"
                                        data-searchable="1"
                                        data-search-placeholder="Search department…"
                                        class="h-9 w-full appearance-none rounded-lg border border-slate-200 bg-white pl-2.5 pr-8 text-xs text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                                    >
                                        <option value="">All Departments</option>
                                        @foreach ($transferDepartments as $department)
                                            <option value="{{ $department->department_id }}" @selected((string) ($filters['department'] ?? '') === (string) $department->department_id)>
                                                {{ $department->department_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <i data-lucide="chevron-down" class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-2.5 flex items-center justify-end gap-2 border-t border-slate-200/80 pt-2.5">
                        @if ($hasFilters)
                            <a
                                href="{{ $deployedUrl }}"
                                class="inline-flex h-8 items-center gap-1 rounded-lg px-2.5 text-xs font-medium text-slate-600 transition hover:bg-slate-200/60 hover:text-slate-900"
                            >
                                <i data-lucide="rotate-ccw" class="h-3 w-3"></i>
                                <span>Reset</span>
                            </a>
                        @endif
                        <button
                            type="submit"
                            class="inline-flex h-8 items-center rounded-lg bg-[#0025cc] px-3.5 text-xs font-semibold text-white transition hover:bg-blue-800"
                        >
                            Apply filters
                        </button>
                    </div>
                </div>

                {{-- ACTIVE FILTER CHIPS --}}
                @if ($hasFilters)
                    <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                        <span class="font-medium text-slate-400">Active:</span>
                        @if ($hasActiveSearch)
                            <a
                                href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => null]) }}"
                                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100"
                            >
                                <span>Search: "{{ \Illuminate\Support\Str::limit($filters['search'], 24) }}"</span>
                                <i data-lucide="x" class="h-3 w-3 text-slate-400"></i>
                            </a>
                        @endif
                        @if ($hasActiveStatus)
                            <a
                                href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}"
                                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100"
                            >
                                <span>Status: {{ $filters['status'] }}</span>
                                <i data-lucide="x" class="h-3 w-3 text-slate-400"></i>
                            </a>
                        @endif
                        @if ($selectedRoomName)
                            <a
                                href="{{ request()->fullUrlWithQuery(['room' => null, 'page' => null]) }}"
                                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100"
                            >
                                <span>Room: {{ $selectedRoomName }}</span>
                                <i data-lucide="x" class="h-3 w-3 text-slate-400"></i>
                            </a>
                        @endif
                        @if ($selectedCategoryName)
                            <a
                                href="{{ request()->fullUrlWithQuery(['category' => null, 'page' => null]) }}"
                                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100"
                            >
                                <span>Classification: {{ $selectedCategoryName }}</span>
                                <i data-lucide="x" class="h-3 w-3 text-slate-400"></i>
                            </a>
                        @endif
                        @if ($selectedDepartmentName)
                            <a
                                href="{{ request()->fullUrlWithQuery(['department' => null, 'page' => null]) }}"
                                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100"
                            >
                                <span>Department: {{ $selectedDepartmentName }}</span>
                                <i data-lucide="x" class="h-3 w-3 text-slate-400"></i>
                            </a>
                        @endif
                    </div>
                @endif
            </form>
        </div>

        {{-- CONTEXTUAL SELECTION BAR (shown only when rows are checked) --}}
        <div
            id="deployedSelectionBar"
            class="hidden flex-wrap items-center justify-between gap-2 border-b border-blue-100 bg-blue-50/70 px-4 py-2 text-xs text-slate-700"
        >
            <span class="font-semibold text-slate-900">
                <span id="deployedSelectionSummary">0</span> selected
            </span>
            <div class="flex flex-wrap items-center gap-1.5">
                <button
                    type="button"
                    id="deployedQuickTransferBtn"
                    onclick="openDeployedBatchTransfer()"
                    disabled
                    class="inline-flex h-7 items-center gap-1.5 rounded-md border border-sky-200 bg-white px-2.5 text-xs font-semibold text-sky-800 transition hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-45"
                >
                    <i data-lucide="arrow-right-left" class="h-3.5 w-3.5"></i>
                    <span>Transfer selected</span>
                </button>
                <button
                    type="button"
                    id="deployedQuickDisposeBtn"
                    onclick="openDeployedBatchDispose()"
                    disabled
                    class="inline-flex h-7 items-center gap-1.5 rounded-md border border-rose-200 bg-white px-2.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-45"
                >
                    <i data-lucide="archive-x" class="h-3.5 w-3.5"></i>
                    <span>Dispose selected</span>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-slate-200 bg-slate-50/80">
                    <tr>
                        <th class="w-10 px-3 py-2 text-center">
                            <input
                                type="checkbox"
                                id="deployedSelectAll"
                                class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                aria-label="Select every unit on this page"
                            >
                        </th>
                        <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Equipment</th>
                        <th class="px-3.5 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Location</th>
                        <th class="px-3.5 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Assigned To</th>
                        <th class="px-3.5 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Condition</th>
                        <th class="px-3.5 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-3.5 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Deployed</th>
                        <th class="w-32 px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
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
                            $canMarkReplacedRow = ($item->equipment_inventory_status ?? null) === 'For Replacement'
                                && ! $item->open_loan
                                && ! $item->transfer_pending
                                && ! $item->disposal_pending;
                            $canDisposeRow = \App\Support\EquipmentConditions::isDisposable($item->equipment_condition_status ?? null)
                                && ($item->equipment_inventory_status ?? null) !== 'Disposed'
                                && ! $item->open_loan
                                && ! $item->transfer_pending
                                && ! $item->disposal_pending;
                            $deployedAt = $item->last_deployed_at ?? $item->equipment_created_at ?? $item->equipment_acquired_date ?? null;
                            $rowMenuId = 'deployedRowMenu_' . (int) $item->equipment_id . '_' . $loop->index;
                        @endphp
                        <tr class="align-middle transition-colors duration-150 hover:bg-slate-50/80">
                            <td class="px-3 py-3 text-center align-middle">
                                @if ($canTransferRow || $canDisposeRow || $canMarkReplacedRow)
                                    <input
                                        type="checkbox"
                                        class="deployed-unit-checkbox h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                        value="{{ $item->equipment_id }}"
                                        data-label="{{ $unitLabel }}"
                                        data-name="{{ $item->equipment_name }}"
                                        data-tag="{{ $item->equipment_asset_tag }}"
                                        data-category="{{ $item->equipment_category_name }}"
                                        data-qty="{{ (int) ($item->equipment_quantity ?? 1) }}"
                                        data-transferable="{{ $canTransferRow ? '1' : '0' }}"
                                        data-disposable="{{ $canDisposeRow ? '1' : '0' }}"
                                        aria-label="Select {{ $unitLabel }}"
                                    >
                                @elseif ($item->transfer_pending)
                                    <input type="checkbox" disabled class="h-4 w-4 cursor-not-allowed rounded border-slate-200 opacity-40" aria-label="{{ $unitLabel }} is awaiting transfer approval" title="Awaiting transfer approval">
                                @else
                                    <input type="checkbox" disabled class="h-4 w-4 cursor-not-allowed rounded border-slate-200 opacity-40" title="{{ $item->transfer_lock }}" aria-label="{{ $item->transfer_lock }}">
                                @endif
                            </td>

                            <td class="px-4 py-3 align-middle">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white ring-1 ring-slate-200/80">
                                        @if (filled($item->equipment_image ?? null))
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
                                                    <i data-lucide="expand" class="h-3 w-3 text-white opacity-0 transition group-hover:opacity-100"></i>
                                                </span>
                                            </button>
                                        @else
                                            <span
                                                class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                                                data-equipment-layout-icon="{{ $item->equipment_name }}"
                                            ></span>
                                        @endif
                                    </div>
                                    <div class="flex min-w-0 flex-col">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="truncate text-[15px] font-semibold leading-snug text-slate-900">
                                                {{ $item->equipment_name }}
                                            </span>
                                            <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold leading-none ring-1 ring-inset {{ $inStorage ? 'bg-amber-50 text-amber-700 ring-amber-200/80' : 'bg-sky-50 text-sky-700 ring-sky-200/80' }}">
                                                {{ $inStorage ? 'In storage' : 'Deployed' }}
                                            </span>
                                        </div>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-[11px] leading-tight text-slate-500">
                                            <span class="font-mono text-[11px] text-slate-500">{{ $item->equipment_asset_tag ?: 'No Asset Tag' }}</span>
                                            <span class="text-slate-300">·</span>
                                            <span>{{ $item->equipment_category_name ?: 'Uncategorized' }}</span>
                                            <span class="text-slate-300">·</span>
                                            @if ($isBulk)
                                                <span>Qty {{ number_format((int) $item->equipment_quantity) }}</span>
                                            @else
                                                <span>{{ $item->equipment_serial_number ? 'SN ' . $item->equipment_serial_number : 'No serial' }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-3.5 py-3 align-middle">
                                <p class="text-[13px] font-medium leading-tight {{ filled($item->room_name) ? 'text-slate-700' : 'text-slate-400' }}">
                                    {{ $item->room_name ?: 'Unassigned' }}
                                </p>
                                @if (filled($zone) && strcasecmp((string) $zone, 'Holding') !== 0)
                                    <p class="mt-0.5 text-[11px] leading-tight text-slate-400">{{ $zone }}</p>
                                @endif
                            </td>

                            <td class="px-3.5 py-3 align-middle">
                                @php
                                    $custodianName = $item->active_assignment['name'] ?? null;
                                    $custodianUrl = $item->active_assignment['person_url'] ?? null;
                                    $deptName = $item->unit_department_name ?? null;
                                @endphp
                                @if (filled($custodianName))
                                    @if (filled($custodianUrl))
                                        <a href="{{ $custodianUrl }}" class="inline-flex items-center gap-1.5 text-[13px] font-medium leading-tight text-slate-800 transition hover:text-[#0025cc] hover:underline">
                                            <i data-lucide="user-round-check" class="h-3.5 w-3.5 shrink-0 text-teal-600"></i>
                                            <span class="truncate">{{ $custodianName }}</span>
                                        </a>
                                    @else
                                        <p class="inline-flex items-center gap-1.5 text-[13px] font-medium leading-tight text-slate-800">
                                            <i data-lucide="user-round-check" class="h-3.5 w-3.5 shrink-0 text-teal-600"></i>
                                            <span class="truncate">{{ $custodianName }}</span>
                                        </p>
                                    @endif
                                    @if (filled($deptName))
                                        <p class="mt-0.5 text-[11px] leading-tight text-slate-500">{{ $deptName }}</p>
                                    @endif
                                @elseif (filled($deptName))
                                    <p class="text-[13px] font-medium leading-tight text-slate-700">{{ $deptName }}</p>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>

                            <td class="px-3.5 py-3 text-center align-middle">
                                <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $conditionClass($item->equipment_condition_status) }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $conditionDotClass($item->equipment_condition_status) }}"></span>
                                    {{ $item->equipment_condition_status ?: '—' }}
                                </span>
                            </td>

                            <td class="px-3.5 py-3 text-center align-middle">
                                <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $statusClass($item->equipment_inventory_status) }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDotClass($item->equipment_inventory_status) }}"></span>
                                    {{ $item->equipment_inventory_status ?: '—' }}
                                </span>
                                @if ($item->open_loan)
                                    <p class="mt-0.5 text-[11px] text-slate-500">
                                        {{ $isBulk && $item->open_loan->quantity > 0 ? $item->open_loan->quantity.' borrowed · ' : '' }}{{ $item->open_loan->borrower ?: 'Borrower not recorded' }}
                                    </p>
                                    @if ($item->open_loan->due)
                                        <p class="text-[11px] {{ $item->open_loan->overdue ? 'font-semibold text-rose-600' : 'text-slate-400' }}">
                                            {{ $item->open_loan->overdue ? 'Overdue' : 'Due' }} {{ \Illuminate\Support\Carbon::parse($item->open_loan->due)->format('M j, Y') }}
                                        </p>
                                    @endif
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-3.5 py-3 text-left align-middle">
                                @if (filled($deployedAt))
                                    @php
                                        $deployedCarbon = \Illuminate\Support\Carbon::parse($deployedAt);
                                    @endphp
                                    <p class="text-[13px] font-medium leading-tight text-slate-700">{{ $deployedCarbon->format('M j, Y') }}</p>
                                    <p class="mt-0.5 text-[11px] leading-tight text-slate-400">{{ $deployedCarbon->format('g:i A') }}</p>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-left align-middle">
                                <div class="flex items-center justify-start gap-1">
                                    <button
                                        type="button"
                                        onclick='openEquipmentModal(@json(\App\Support\LayoutEquipmentPayload::fromRow($item, $eqImageUrl($item->equipment_image ?? null))))'
                                        class="inline-flex h-7 items-center justify-center gap-1 rounded-md border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950"
                                        aria-label="View {{ $item->equipment_name }}"
                                        title="View details"
                                    >
                                        <i data-lucide="eye" class="h-3.5 w-3.5 text-slate-500"></i>
                                        <span>View</span>
                                    </button>

                                    <button
                                        type="button"
                                        onclick="toggleDeployedMenu(this, '{{ $rowMenuId }}', event)"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                        aria-label="More actions for {{ $item->equipment_name }}"
                                        title="More actions"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                                    >
                                        <i data-lucide="more-horizontal" class="h-3.5 w-3.5"></i>
                                    </button>

                                    <div
                                        id="{{ $rowMenuId }}"
                                        data-deployed-menu
                                        role="menu"
                                        class="fixed z-[90] hidden w-48 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-xl shadow-slate-950/10"
                                    >
                                        @if ($item->transfer_pending)
                                            <a
                                                href="{{ $transferRequestsUrl }}"
                                                role="menuitem"
                                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-amber-700 transition hover:bg-amber-50"
                                            >
                                                <i data-lucide="hourglass" class="h-3.5 w-3.5"></i>
                                                <span>Awaiting transfer approval</span>
                                            </a>
                                        @elseif ($item->disposal_pending)
                                            <a
                                                href="{{ $disposalRequestsUrl }}"
                                                role="menuitem"
                                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-50"
                                            >
                                                <i data-lucide="hourglass" class="h-3.5 w-3.5"></i>
                                                <span>Disposal pending</span>
                                            </a>
                                        @elseif ($item->transfer_lock)
                                            <div class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs text-slate-400" title="{{ $item->transfer_lock }}">
                                                <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0"></i>
                                                <span class="truncate">{{ $item->transfer_lock }}</span>
                                            </div>
                                        @else
                                            <button
                                                type="button"
                                                role="menuitem"
                                                onclick="closeDeployedMenus(); openDeployedTransfer(this)"
                                                data-id="{{ $item->equipment_id }}"
                                                data-name="{{ $item->equipment_name }}"
                                                data-room="{{ $item->room_name }}"
                                                data-tag="{{ $item->equipment_asset_tag }}"
                                                data-serial="{{ $item->equipment_serial_number }}"
                                                data-tracking="{{ $item->equipment_tracking_mode ?? 'Individual' }}"
                                                data-qty="{{ (int) ($item->equipment_quantity ?? 1) }}"
                                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                            >
                                                <i data-lucide="arrow-right-left" class="h-3.5 w-3.5 text-sky-600"></i>
                                                <span>{{ $transferLabel }}</span>
                                            </button>
                                        @endif

                                        <button
                                            type="button"
                                            role="menuitem"
                                            onclick="closeDeployedMenus(); openDeployedTransferLogs(this)"
                                            data-id="{{ $item->equipment_id }}"
                                            data-title="{{ $identifier ? $item->equipment_name.' · '.$identifier : $item->equipment_name }}"
                                            class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                        >
                                            <i data-lucide="route" class="h-3.5 w-3.5 text-slate-400"></i>
                                            <span>Transfer history</span>
                                        </button>

                                        @if ($canMarkReplacedRow)
                                            <button
                                                type="button"
                                                role="menuitem"
                                                onclick="closeDeployedMenus(); openDeployedMarkReplaced(this)"
                                                data-id="{{ $item->equipment_id }}"
                                                data-label="{{ $unitLabel }}"
                                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-blue-700 transition hover:bg-blue-50"
                                            >
                                                <i data-lucide="replace" class="h-3.5 w-3.5"></i>
                                                <span>Mark as Replaced</span>
                                            </button>
                                        @elseif ($canDisposeRow)
                                            <div class="my-1 border-t border-slate-100"></div>
                                            <button
                                                type="button"
                                                role="menuitem"
                                                onclick="closeDeployedMenus(); openDeployedDispose(this)"
                                                data-id="{{ $item->equipment_id }}"
                                                data-label="{{ $unitLabel }}"
                                                data-room="{{ $item->room_name }}"
                                                data-room-id="{{ (int) ($item->equipment_room_id ?? 0) }}"
                                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-rose-700 transition hover:bg-rose-50"
                                            >
                                                <i data-lucide="archive-x" class="h-3.5 w-3.5 text-rose-600"></i>
                                                <span>{{ $disposalNeedsApproval ? 'Request disposal' : 'Dispose equipment' }}</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <p class="text-sm font-semibold text-slate-900">No deployed equipment matches your filters</p>
                                <p class="mt-1 text-xs text-slate-500">Try another filter, or transfer items from Inventory.</p>
                                @if ($hasFilters)
                                    <a
                                        href="{{ $deployedUrl }}"
                                        class="mt-3 inline-flex text-xs font-semibold text-[#0025cc] hover:underline"
                                    >
                                        Reset filters
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($equipment->hasPages() || $equipmentTotal > 0)
            <div class="flex flex-col gap-2 border-t border-slate-100 bg-slate-50/40 px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
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
@include('layouts.partials.equipment-layout-icons')
@include('layouts.partials.equipment-photo-viewer')

{{-- Transfer one unit --}}
<div
    id="deployedTransferModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    onclick="if (event.target === this) closeDeployedModal('deployedTransferModal')"
>
    <div class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
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
    <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="shrink-0 border-b border-slate-200 px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                        <i data-lucide="check-square" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer selected</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Choose a destination for each of
                        <span id="deployedBatchCount" class="font-semibold text-slate-800">0</span>
                        selected unit(s). Destinations can differ.
                    </p>
                </div>
                <button type="button" onclick="closeDeployedModal('deployedBatchTransferModal')" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
        </div>

        <form action="{{ $transferActionBase }}/transfer-batch" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf

            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6">
                @if ($transferNeedsApproval ?? false)
                    <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-xs leading-5 text-amber-900">
                        <i data-lucide="shield-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <p>
                            <span class="font-semibold">Needs the Administrator's approval.</span>
                            Items with the same destination are grouped into one request.
                        </p>
                    </div>
                @endif

                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-slate-800">Per-item destinations</p>
                    <button
                        type="button"
                        id="deployedBatchApplyAll"
                        onclick="applyFirstBatchDestinationToAll(document.getElementById('deployedBatchRows'))"
                        class="text-xs font-semibold text-[#0025cc] transition hover:text-blue-800"
                    >
                        Apply first to all
                    </button>
                </div>

                <div id="deployedBatchRows" class="space-y-3"></div>

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

    @include('maintenance-personnel.equipment.partials.inventory-transfer-batch-row-template', [
        'templateId' => 'deployedBatchRowTemplate',
        'prefixBase' => 'depSel',
        'roomOptions' => $deployRooms,
    ])
</div>

{{-- Transfer all filtered deployed units to one destination --}}
<div
    id="deployedTransferAllModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    onclick="if (event.target === this) closeDeployedModal('deployedTransferAllModal')"
>
    <div class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="shrink-0 border-b border-slate-200 px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                        <i data-lucide="boxes" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer all deployed equipment</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Move all
                        <span class="font-semibold text-slate-800">{{ number_format(count($deployedTransferIds ?? [])) }}</span>
                        filtered unit(s) to one room, person or department.
                    </p>
                </div>
                <button type="button" onclick="closeDeployedModal('deployedTransferAllModal')" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
        </div>

        <form action="{{ $transferActionBase }}/transfer-batch" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf
            @foreach (($deployedTransferIds ?? []) as $transferId)
                <input type="hidden" name="equipment_ids[]" value="{{ $transferId }}">
            @endforeach

            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6">
                @include('maintenance-personnel.equipment.partials.inventory-transfer-destination', [
                    'prefix' => 'deployedTransferAll',
                    'roomOptions' => $deployRooms,
                ])

                <div>
                    <div class="mb-2 flex items-center justify-between gap-4">
                        <label for="deployedTransferAllRemarks" class="text-sm font-semibold text-slate-700">Remarks</label>
                        <span class="text-xs text-slate-400">Optional</span>
                    </div>
                    <textarea id="deployedTransferAllRemarks" name="remarks" rows="3" placeholder="e.g. Relocate filtered equipment to Computer Laboratory 1" class="w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"></textarea>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button type="button" onclick="closeDeployedModal('deployedTransferAllModal')" class="inline-flex h-10 items-center justify-center px-2 text-sm font-medium text-slate-600 transition hover:text-slate-950">Cancel</button>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">
                    {{ $transferNeedsApproval ? 'Send for approval' : 'Transfer all' }}
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

{{-- Mark For Replacement as Replaced when there is no successor --}}
<div
    id="deployedMarkReplacedModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    onclick="if (event.target === this) closeDeployedModal('deployedMarkReplacedModal')"
>
    <div class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
        <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
            <div class="min-w-0">
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                    <i data-lucide="replace" class="h-4 w-4"></i>
                </div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-950">Mark as Replaced</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    No replacement unit will be installed. This closes For Replacement so the unit can be moved to Disposal.
                </p>
            </div>
            <button type="button" onclick="closeDeployedModal('deployedMarkReplacedModal')" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <form action="{{ $markReplacedAction }}" method="POST">
            @csrf
            <input type="hidden" name="equipment_id" id="deployedMarkReplacedEquipmentId" value="">
            <div class="space-y-5 border-y border-slate-100 px-6 py-5">
                <div>
                    <p class="mb-1 text-sm font-medium text-slate-700">Equipment</p>
                    <div id="deployedMarkReplacedEquipmentName" class="rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900"></div>
                </div>
                <div>
                    <label for="deployedMarkReplacedReason" class="mb-2 block text-sm font-medium text-slate-700">
                        Reason <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="deployedMarkReplacedReason"
                        name="reason"
                        rows="3"
                        required
                        maxlength="1000"
                        placeholder="Why is there no successor for this unit?"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    ></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button type="button" onclick="closeDeployedModal('deployedMarkReplacedModal')" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                    Cancel
                </button>
                <button type="submit" class="h-10 rounded-xl bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">
                    Mark as Replaced
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Dispose a Defective or Damaged unit --}}
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
                        Only Defective or Damaged equipment. Disposing moves them to Equipment Disposal until they are restored there.
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
                    <label for="deployedDisposeLocation" class="mb-2 block text-sm font-medium text-slate-700">Location <span class="text-red-500">*</span></label>
                    <select
                        id="deployedDisposeLocation"
                        name="room_id"
                        required
                        data-searchable="1"
                        data-search-placeholder="Search rooms…"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >
                        <option value="">Select room</option>
                        @foreach ($disposalRooms as $room)
                            <option value="{{ $room->room_id }}">
                                {{ \App\Support\RoomCategories::isStorageType($room->room_type ?? null) ? 'Storage · '.$room->room_name : $room->room_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[11px] leading-4 text-slate-500">The equipment is transferred to this room.</p>
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
        if (button) button.disabled = total === 0;
        if (count) {
            count.textContent = String(total);
            count.classList.toggle('hidden', total === 0);
        }
    }

    function updateDeployedSelection() {
        const boxes = deployedUnitCheckboxes();
        const selected = boxes.filter((box) => box.checked);
        const transferableCount = deployedSelected('transferable').length;
        const disposableCount = deployedSelected('disposable').length;

        syncDeployedSelectionButton('deployedTransferSelectedBtn', 'deployedSelectedCount', transferableCount);
        syncDeployedSelectionButton('deployedDisposeSelectedBtn', 'deployedDisposeSelectedCount', disposableCount);

        const quickTransfer = document.getElementById('deployedQuickTransferBtn');
        const quickDispose = document.getElementById('deployedQuickDisposeBtn');
        if (quickTransfer) quickTransfer.disabled = transferableCount === 0;
        if (quickDispose) quickDispose.disabled = disposableCount === 0;

        const bar = document.getElementById('deployedSelectionBar');
        const summary = document.getElementById('deployedSelectionSummary');
        if (summary) summary.textContent = String(selected.length);
        if (bar) {
            bar.classList.toggle('hidden', selected.length === 0);
            bar.classList.toggle('flex', selected.length > 0);
        }

        const actionsBadge = document.getElementById('deployedActionsBadge');
        if (actionsBadge) {
            const base = Number(actionsBadge.dataset.baseCount || 0);
            const totalBadge = base + selected.length;
            actionsBadge.textContent = String(totalBadge);
            actionsBadge.classList.toggle('hidden', totalBadge === 0);
        }

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
        document.getElementById('deployedTransferTag').textContent = data.tag || 'No tag';
        document.getElementById('deployedTransferSerial').textContent = data.serial || 'No serial';
        document.getElementById('deployedTransferRoom').textContent = data.room || 'No room';
        document.getElementById('deployedTransferRemarks').value = '';
        resetInventoryTransferDestination('deployedTransfer');
        setInventoryTransferQuantity('deployedTransfer', data.tracking, data.qty);
        showDeployedModal('deployedTransferModal');
    }

    function openDeployedBatchTransfer() {
        const selected = deployedSelected('transferable');
        if (selected.length === 0) return;

        const items = selected.map((box) => ({
            id: box.value,
            name: box.dataset.name || box.dataset.label || 'Equipment',
            tag: box.dataset.tag || '',
            category: box.dataset.category || '',
            qty: box.dataset.qty || '1',
        }));

        document.getElementById('deployedBatchCount').textContent = String(items.length);
        buildInventoryBatchTransferRows(
            document.getElementById('deployedBatchRows'),
            document.getElementById('deployedBatchRowTemplate'),
            items
        );

        const applyAll = document.getElementById('deployedBatchApplyAll');
        if (applyAll) applyAll.classList.toggle('hidden', items.length < 2);

        document.getElementById('deployedBatchRemarks').value = '';
        showDeployedModal('deployedBatchTransferModal');
    }

    function openDeployedTransferAll() {
        if (@json(empty($deployedTransferIds ?? []))) return;
        resetInventoryTransferDestination('deployedTransferAll');
        document.getElementById('deployedTransferAllRemarks').value = '';
        showDeployedModal('deployedTransferAllModal');
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
                        <span class="mx-2 text-slate-400">?</span>
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

    function resetDeployedDisposeForm(roomId) {
        document.getElementById('deployedDisposeReason').value = '';
        if (window.setEqSelectValue) {
            setEqSelectValue('deployedDisposeLocation', roomId ? String(roomId) : '');
        }
    }

    function openDeployedMarkReplaced(button) {
        document.getElementById('deployedMarkReplacedEquipmentId').value = button.dataset.id;
        document.getElementById('deployedMarkReplacedEquipmentName').textContent = button.dataset.label || 'Equipment';
        document.getElementById('deployedMarkReplacedReason').value = '';
        showDeployedModal('deployedMarkReplacedModal');
    }

    function openDeployedDispose(button) {
        document.getElementById('deployedDisposeIds').innerHTML = '';
        document.getElementById('deployedDisposeEquipmentId').value = button.dataset.id;
        document.getElementById('deployedDisposeTitle').textContent = @json($disposalNeedsApproval ? 'Request disposal' : 'Dispose equipment');
        document.getElementById('deployedDisposeEquipmentName').textContent = button.dataset.label || 'Equipment';
        resetDeployedDisposeForm(button.dataset.roomId);
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
        closeDeployedMenus();
        ['deployedTransferModal', 'deployedBatchTransferModal', 'deployedTransferAllModal', 'deployedLogsModal', 'deployedMarkReplacedModal', 'deployedDisposeModal'].forEach(closeDeployedModal);
    });

    (function () {
        let activeMenu = null;
        let activeTrigger = null;
        let menuOpenedAt = 0;

        function closeDeployedMenus() {
            document.querySelectorAll('[data-deployed-menu]').forEach(function (menu) {
                menu.classList.add('hidden');
            });
            if (activeTrigger) {
                activeTrigger.setAttribute('aria-expanded', 'false');
            }
            activeMenu = null;
            activeTrigger = null;
        }

        function positionDeployedMenu(trigger, menu) {
            if (menu.parentNode !== document.body) {
                document.body.appendChild(menu);
            }

            menu.style.position = 'fixed';
            menu.style.zIndex = '9999';
            menu.style.visibility = 'hidden';
            menu.classList.remove('hidden');

            const rect = trigger.getBoundingClientRect();
            const menuWidth = menu.offsetWidth || 200;
            const menuHeight = menu.offsetHeight || 160;
            const gap = 6;
            const margin = 12;

            let left = rect.right - menuWidth;
            if (left < margin) left = Math.max(margin, rect.left);
            if (left + menuWidth > window.innerWidth - margin) {
                left = Math.max(margin, window.innerWidth - menuWidth - margin);
            }

            const spaceBelow = window.innerHeight - rect.bottom - margin;
            let top = rect.bottom + gap;
            if (spaceBelow < menuHeight && rect.top - margin > menuHeight) {
                top = rect.top - menuHeight - gap;
            }
            if (top < margin) top = margin;

            menu.style.left = Math.round(left) + 'px';
            menu.style.top = Math.round(top) + 'px';
            menu.style.visibility = '';
        }

        window.closeDeployedMenus = closeDeployedMenus;

        window.toggleDeployedMenu = function (trigger, menuId, evt) {
            if (evt && typeof evt.stopPropagation === 'function') {
                evt.stopPropagation();
            }

            const menu = document.getElementById(menuId);
            if (!menu || !trigger) return;

            const isSame = activeMenu === menu && !menu.classList.contains('hidden');
            closeDeployedMenus();
            if (isSame) return;

            positionDeployedMenu(trigger, menu);
            trigger.setAttribute('aria-expanded', 'true');
            activeMenu = menu;
            activeTrigger = trigger;
            menuOpenedAt = Date.now();

            if (window.lucide && menu.querySelector('i[data-lucide]')) {
                window.lucide.createIcons();
            }
        };

        window.toggleDeployedSecondaryFilters = function () {
            const panel = document.getElementById('deployedSecondaryFiltersPanel');
            const btn = document.getElementById('deployedSecondaryFiltersBtn');
            if (!panel || !btn) return;
            const willOpen = panel.classList.contains('hidden');
            panel.classList.toggle('hidden', !willOpen);
            btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (willOpen && window.lucide) {
                window.lucide.createIcons();
            }
        };

        document.addEventListener('click', function (event) {
            if (!activeMenu || !activeTrigger) return;
            if (activeMenu.contains(event.target) || activeTrigger.contains(event.target)) return;
            closeDeployedMenus();
        });

        window.addEventListener('scroll', function (event) {
            if (!activeMenu) return;
            if (Date.now() - menuOpenedAt < 100) return;
            if (event.target && activeMenu.contains(event.target)) return;
            closeDeployedMenus();
        }, true);

        window.addEventListener('resize', function () {
            if (activeMenu) closeDeployedMenus();
        });
    })();
</script>
@endsection
