@extends ("layouts.maintenance-layout")

@section ("title", ($isStockPage ?? false) ? "Inventory" : "All Equipment")

@section ("content")
    @php
        $isStockPage = $isStockPage ?? (($scope ?? 'stock') === 'stock');
        $canMaintain = \App\Support\RoleAccess::hasRole(\App\Support\RoleAccess::MAINTENANCE);
        $inAdminPortal = request()->is('admin/*');
        // The Administrator adds, edits and disposes; Maintenance only transfers stock out and views the full register.
        $canManage = $inAdminPortal;
        $canTransfer = $isStockPage && ($inAdminPortal || $canMaintain);
        $equipmentActionBase = $inAdminPortal ? '/admin/inventory' : '/maintenance/equipment';
        $disposeAction = $inAdminPortal ? '/admin/inventory/dispose' : '/maintenance/disposal/store';
        $markReplacedAction = $inAdminPortal ? '/admin/inventory/mark-replaced' : '/maintenance/disposal/mark-replaced';
        $inventoryExportPdfUrl = $inAdminPortal ? '/admin/inventory/export-pdf' : '/maintenance/equipment/inventory/export-pdf';
        $showHistory = $isStockPage && ($movementHistory ?? null) !== null && request('view') === 'history';
        $showReplacementArrived = $isStockPage && request('view') === 'replacement';
        $selectable = $canTransfer && ! $showHistory && ! $showReplacementArrived;
        $acquisitionReady = \App\Support\EquipmentAcquisition::ready();
        $acquisitionSuppliers = \App\Support\EquipmentAcquisition::supplierOptions();
        $acquisitionPeople = $isStockPage ? \App\Support\Custodians::assignable() : collect();
        $transferNeedsApproval = $canTransfer && \App\Support\TransferRequests::requiresApproval();
        $pendingTransferIds = $canTransfer ? \App\Support\TransferRequests::pendingEquipmentIds() : [];
        $pendingTransferCount = $canTransfer ? \App\Support\TransferRequests::pendingCount() : 0;
        $transferRequestsUrl = $inAdminPortal ? '/admin/inventory/transfer-requests' : '/maintenance/equipment/transfer-requests';
        $transferPeople = $canTransfer ? $acquisitionPeople : collect();
        $transferDepartments = $canTransfer ? \App\Support\Departments::options() : collect();
        $replacementCandidates = $isStockPage ? \App\Support\EquipmentAcquisition::replacementCandidates() : collect();
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
                @if ($canTransfer)
                <a
                    href="{{ $transferRequestsUrl }}"
                    class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"
                    title="Transfer requests"
                    data-tooltip="Transfer requests"
                    aria-label="Transfer requests"
                >
                    <i data-lucide="shield-check" class="h-4 w-4"></i>
                    @if ($pendingTransferCount > 0)
                        <span class="absolute -right-1 -top-1 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-amber-500 px-1 py-0.5 text-[10px] font-bold leading-none text-white">{{ $pendingTransferCount }}</span>
                    @endif
                </a>
                @endif
                @if ($canTransfer && ! $showHistory && ! $showReplacementArrived)
                <button
                    type="button"
                    id="inventoryTransferSelectedBtn"
                    onclick="openInventoryTransferSelectedModal()"
                    class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    disabled
                    title="Transfer selected"
                    data-tooltip="Transfer selected"
                    aria-label="Transfer selected"
                >
                    <i data-lucide="check-square" class="h-4 w-4"></i>
                    <span id="inventoryTransferSelectedCount" class="absolute -right-1 -top-1 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-slate-700 px-1 py-0.5 text-[10px] font-bold leading-none text-white">0</span>
                </button>
                @if ($canManage)
                <button
                    type="button"
                    id="inventoryDisposeSelectedBtn"
                    onclick="openInventoryDisposeSelectedModal()"
                    class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg bg-[#e32626] text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                    disabled
                    data-pur-tip-title="Dispose selected"
                    data-pur-tip="Move selected Replaced storage units into Disposal"
                    data-pur-tip-accent="#e32626"
                    aria-label="Dispose selected"
                    title="Dispose selected"
                >
                    <i data-lucide="archive-x" class="h-4 w-4"></i>
                    <span id="inventoryDisposeSelectedCount" class="absolute -right-1 -top-1 inline-flex min-w-[1.1rem] items-center justify-center rounded-full bg-white px-1 py-0.5 text-[10px] font-bold leading-none text-[#e32626]">0</span>
                </button>
                @endif
                <button
                    type="button"
                    onclick="openInventoryTransferAllModal()"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-800 transition hover:bg-sky-100 disabled:cursor-not-allowed disabled:opacity-50"
                    @if (empty($stockTransferIds ?? [])) disabled @endif
                    data-pur-tip-title="Transfer all"
                    data-pur-tip="Move every storage unit shown by the current filters"
                    data-pur-tip-accent="#38bdf8"
                    aria-label="Transfer all"
                >
                    <i data-lucide="arrow-right-left" class="h-4 w-4"></i>
                </button>
                @endif
                @if ($canManage)
                @php
                    $openBalanceReports = $openBalance['reports'] ?? [];
                    $openBalancePending = (int) (($openBalance['totals']['pending_stock'] ?? 0));
                    $openBalancePendingReplacement = (int) (($openBalance['totals']['pending_replacement'] ?? 0));
                    $showOpenBalanceBtn = ! empty($openBalanceReports);
                @endphp
                @if ($showOpenBalanceBtn)
                <button
                    type="button"
                    onclick="openInventoryOpenBalanceModal()"
                    class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-[13px] font-semibold text-amber-800 transition hover:bg-amber-100"
                >
                    <i data-lucide="scale" class="h-4 w-4"></i>
                    RR ready to stock
                    @if ($openBalancePending > 0)
                        <span class="rounded-full bg-amber-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $openBalancePending }}</span>
                    @endif
                    @if ($openBalancePendingReplacement > 0)
                        <span class="rounded-full bg-violet-100 px-1.5 py-0.5 text-[10px] font-bold text-violet-800" title="Pending replacement stock">{{ $openBalancePendingReplacement }} replacement</span>
                    @endif
                </button>
                @endif
                <button
                    type="button"
                    onclick="openBatchAddEquipmentModal()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    @if (!($defaultStorageRoomId ?? null)) disabled @endif
                >
                    <i data-lucide="layers" class="h-4 w-4"></i>
                    Batch add
                </button>
                <button
                    type="button"
                    onclick="openAddEquipmentModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 font-semibold font-sans-serif text-[13px] text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                    @if (!($defaultStorageRoomId ?? null)) disabled @endif
                >
                    <i data-lucide="plus" class="w-4 h-4"></i>

                    Add to inventory
                </button>
                @endif
            </div>
            @if (! $canManage)
                <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"></i>
                    <p>
                        Transfer stock to a room, a person or a department from here.
                        @if ($transferNeedsApproval)
                            Each transfer is sent to the Administrator for approval first.
                        @endif
                        Adding, editing and disposing inventory items is done by the Administrator.
                    </p>
                </div>
            @endif
            @if ($canManage && !($defaultStorageRoomId ?? null))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    Create a storage room (room type <span class="font-semibold">Storage / Stockroom</span>) before adding equipment to inventory.
                </div>
            @endif
        @elseif (! $canManage)
            <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"></i>
                <p>View only. Editing and disposing equipment is done by the Administrator.</p>
            </div>
        @endif

        <!-- DASHBOARD CARDS -->
        @if ($isStockPage)
            @php
                $stockMonthlyHint = $equipmentMonthlyPercentage === null
                    ? 'New activity vs last month'
                    : (($equipmentMonthlyPercentage > 0 ? '+' : '') . number_format($equipmentMonthlyPercentage, 2) . '% vs last month');
            @endphp
        @else
            @php
                $equipmentMonthlyHint = $equipmentMonthlyPercentage === null
                    ? 'New activity vs last month'
                    : (($equipmentMonthlyPercentage > 0 ? '+' : '') . number_format($equipmentMonthlyPercentage, 2) . '% vs last month');
            @endphp
        @endif

        <!-- FILTER SECTION 
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <form data-auto-filter method="GET">
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
                            <option value="">All Classifications</option>

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
                                value="Replaced"
                                {{
                                    request("status") == "Replaced"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                Replaced
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

        @if ($isStockPage)
            @php
                $replacementArrivedCount = (int) ($pendingReplacementReceivableCount ?? ($pendingReplacementLines ?? collect())->count());
                $viewTabs = [
                    'stock' => [
                        'label' => 'In storage',
                        'icon' => 'warehouse',
                        'count' => $equipment->total(),
                        'url' => request()->fullUrlWithQuery(['view' => null, 'history' => null, 'history_search' => null, 'history_page' => null]),
                    ],
                    'replacement' => [
                        'label' => 'Replacement arrived',
                        'icon' => 'package-check',
                        'count' => $replacementArrivedCount,
                        'url' => request()->fullUrlWithQuery(['view' => 'replacement', 'page' => null, 'history' => null, 'history_search' => null, 'history_page' => null]),
                    ],
                    'history' => [
                        'label' => 'Movement history',
                        'icon' => 'history',
                        'count' => $movementCounts['all'] ?? 0,
                        'url' => request()->fullUrlWithQuery(['view' => 'history', 'page' => null]),
                    ],
                ];
                $activeViewTab = $showHistory ? 'history' : ($showReplacementArrived ? 'replacement' : 'stock');
            @endphp
            <div id="inventory-tables" class="-mb-3 flex scroll-mt-24 items-center">
                <div class="inline-flex rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Inventory view">
                    @foreach ($viewTabs as $tabKey => $tab)
                        <a
                            href="{{ $tab['url'] }}#inventory-tables"
                            role="tab"
                            aria-selected="{{ $activeViewTab === $tabKey ? 'true' : 'false' }}"
                            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition {{ $activeViewTab === $tabKey ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}"
                        >
                            <i data-lucide="{{ $tab['icon'] }}" class="h-4 w-4"></i>
                            {{ $tab['label'] }}
                            <span class="rounded-full px-1.5 text-[11px] font-semibold {{ $activeViewTab === $tabKey ? 'bg-slate-100 text-slate-600' : 'bg-white text-slate-500' }}">{{ number_format($tab['count']) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($showReplacementArrived)
            @include('maintenance-personnel.equipment.partials.inventory-replacement-arrived')
        @elseif (! $showHistory)
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
                    class="border-b border-slate-200 bg-white px-5 py-4
                        @if (! $isStockPage)
                            flex flex-col gap-3 xl:flex-row xl:items-center
                        @endif"
                >

                    @if ($isStockPage)
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                                    <i data-lucide="warehouse" class="h-4 w-4 text-slate-400"></i>
                                    In storage
                                </h2>
                                <div
                                    class="inline-flex w-fit items-center gap-2
                                        rounded-lg border border-slate-200
                                        bg-slate-50 px-3 py-2
                                        text-xs font-medium text-slate-500"
                                >
                                    <i data-lucide="package" class="h-3.5 w-3.5"></i>
                                    {{ number_format($equipment->total()) }} total
                                </div>
                            </div>
                            <a
                                href="{{ $inventoryExportPdfUrl }}?{{ http_build_query(request()->only(['search', 'category', 'condition', 'status'])) }}"
                                class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                data-pur-tip-title="Export PDF"
                                data-pur-tip="Download the current In storage list as a PDF"
                                data-pur-tip-accent="#64748b"
                            >
                                <i data-lucide="file-down" class="h-4 w-4"></i>
                                Export PDF
                            </a>
                        </div>
                    @else
                    {{-- ================================================= --}}
                    {{-- LEFT SIDE --}}
                    {{-- STATUS TABS (All Equipment only) --}}
                    {{-- ================================================= --}}

                    <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3">

                        @php
                            $statusOptions = [
                                '' => ['label' => 'All Status', 'count' => (int) ($totalEquipment ?? 0)],
                                'Active' => ['label' => 'Active', 'count' => (int) ($activeEquipment ?? 0)],
                                'Under Maintenance' => ['label' => 'Maintenance', 'count' => (int) ($underMaintenanceEquipment ?? 0)],
                                'Borrowed' => ['label' => 'Borrowed', 'count' => (int) ($borrowedEquipment ?? 0)],
                                'For Replacement' => ['label' => 'For Replacement', 'count' => (int) ($forReplacementEquipment ?? 0)],
                                'Replaced' => ['label' => 'Replaced', 'count' => (int) ($replacedEquipment ?? 0)],
                                'Disposed' => ['label' => 'Disposed', 'count' => (int) ($disposedEquipment ?? 0)],
                            ];
                        @endphp

                        <div class="relative w-56">

                            <i
                                data-lucide="list-filter"

                                class="pointer-events-none absolute
                                    left-3 top-1/2 h-4 w-4
                                    -translate-y-1/2 text-slate-400"
                            ></i>

                            <select
                                aria-label="Filter by status"
                                data-searchable="1"
                                data-search-placeholder="Search status…"
                                onchange="if (this.value) window.location.href = this.value"

                                class="h-10 w-full appearance-none
                                    rounded-lg border border-slate-200
                                    bg-white pl-10 pr-9
                                    text-sm font-medium text-slate-700
                                    outline-none transition
                                    focus:border-slate-400
                                    focus:ring-2 focus:ring-slate-100"
                            >

                                @foreach ($statusOptions as $statusValue => $statusOption)

                                    <option
                                        value="{{ request()->fullUrlWithQuery([
                                            'status' => $statusValue === '' ? null : $statusValue,
                                            'page' => null,
                                        ]) }}"

                                        @selected((string) request('status', '') === $statusValue)
                                    >
                                        {{ $statusOption['label'] }} ({{ number_format($statusOption['count']) }})
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

                        <div
                            class="inline-flex w-fit items-center gap-2
                                rounded-lg border border-slate-200
                                bg-slate-50 px-3 py-2
                                text-xs font-medium text-slate-500"
                        >
                            <i data-lucide="package" class="h-3.5 w-3.5"></i>
                            {{ number_format($equipment->total()) }} total
                        </div>

                    </div>
                    @endif


                    {{-- ================================================= --}}
                    {{-- RIGHT SIDE --}}
                    {{-- SEARCH AND FILTERS --}}
                    {{-- DOES NOT WRAP ON XL SCREENS --}}
                    {{-- ================================================= --}}

                    <form method="GET" action="{{ url()->current() }}"

                        class="flex flex-wrap items-center gap-2
                            xl:flex-nowrap
                            @if ($isStockPage) mt-4 w-full @else shrink-0 @endif"
                    >

                        {{-- ================================================= --}}
                        {{-- PRESERVE STATUS (All Equipment left-side filter) --}}
                        {{-- ================================================= --}}

                        @if (! $isStockPage && request()->filled('status'))

                            <input
                                type="hidden"
                                name="status"
                                value="{{ request('status') }}"
                            >

                        @endif

                        {{-- ================================================= --}}
                        {{-- SEARCH --}}
                        {{-- ================================================= --}}

                        <div class="relative min-w-0 @if ($isStockPage) flex-1 @endif">

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

                                class="h-10 rounded-lg
                                    border border-slate-200
                                    bg-white pl-10 pr-3
                                    text-sm text-slate-700
                                    outline-none transition
                                    placeholder:text-slate-400
                                    focus:border-slate-400
                                    focus:ring-2 focus:ring-slate-100
                                    @if ($isStockPage) w-full @else w-48 @endif"
                            >

                        </div>


                        {{-- ================================================= --}}
                        {{-- CATEGORY --}}
                        {{-- ================================================= --}}

                        <div class="relative">

                            <select
                                name="category"
                                data-searchable="1"
                                data-search-placeholder="Search category…"

                                class="h-10 w-40 appearance-none
                                    rounded-lg border border-slate-200
                                    bg-white pl-3 pr-9
                                    text-sm text-slate-600
                                    outline-none transition
                                    focus:border-slate-400
                                    focus:ring-2 focus:ring-slate-100"
                            >

                                <option value="">
                                    All Classifications
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

                        @if ($isStockPage)
                        {{-- ================================================= --}}
                        {{-- CONDITION (In storage) --}}
                        {{-- ================================================= --}}
                        <div class="relative">
                            <select
                                name="condition"
                                data-searchable="1"
                                data-search-placeholder="Search condition…"
                                class="h-10 w-40 appearance-none rounded-lg border border-slate-200 bg-white pl-3 pr-9 text-sm text-slate-600 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                            >
                                <option value="">All conditions</option>
                                @foreach (\App\Support\EquipmentConditions::ALL as $conditionValue)
                                    @if ($conditionValue === 'Disposed')
                                        @continue
                                    @endif
                                    <option value="{{ $conditionValue }}" @selected(request('condition') === $conditionValue)>
                                        {{ $conditionValue }}
                                    </option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        </div>

                        {{-- ================================================= --}}
                        {{-- STATUS (In storage) --}}
                        {{-- ================================================= --}}
                        <div class="relative">
                            <select
                                name="status"
                                data-searchable="1"
                                data-search-placeholder="Search status…"
                                class="h-10 w-44 appearance-none rounded-lg border border-slate-200 bg-white pl-3 pr-9 text-sm text-slate-600 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                            >
                                <option value="">All statuses</option>
                                @foreach ([
                                    'Active' => 'Active',
                                    'Under Maintenance' => 'Under Maintenance',
                                    'For Replacement' => 'For Replacement',
                                    'Replaced' => 'Replaced',
                                ] as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}" @selected(request('status') === $statusValue)>
                                        {{ $statusLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        </div>
                        @endif


                        @unless ($isStockPage)
                        {{-- ================================================= --}}
                        {{-- ROOM (All Equipment only) --}}
                        {{-- ================================================= --}}

                        <div class="relative">

                            <select
                                name="room"
                                data-searchable="1"
                                data-search-placeholder="Search room…"

                                class="h-10 w-52 appearance-none
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
                        @endunless


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
                                data-lucide="filter"
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
                            || (! $isStockPage && request()->filled('room'))
                            || ($isStockPage && request()->filled('status'))
                            || request()->filled('condition')
                        )

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'search' => null,
                                    'category' => null,
                                    'room' => null,
                                    'status' => $isStockPage ? null : request('status'),
                                    'condition' => null,
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
                            @if ($selectable)
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
                                Location
                            </th>

                            <th class="px-4 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black">
                                Condition
                            </th>

                            <th class="px-4 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black">
                                Status
                            </th>

                            <th class="px-4 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black">
                                {{ $isStockPage ? 'Added' : 'Next maintenance' }}
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
                                    "Under Maintenance" => "bg-amber-50 text-amber-700",
                                    "Defective" => "bg-orange-50 text-orange-700",
                                    "Damaged" => "bg-rose-50 text-rose-700",
                                    "Disposed" => "bg-slate-200 text-slate-700",
                                    default => "bg-slate-100 text-slate-600",
                                };

                                $inventoryClass = match ($item->equipment_inventory_status ?? "") {
                                    "Active" => "bg-emerald-50 text-emerald-700",
                                    "Borrowed" => "bg-sky-50 text-sky-700",
                                    "Under Maintenance" => "bg-amber-50 text-amber-700",
                                    "For Replacement" => "bg-orange-50 text-orange-700",
                                    "Replaced" => "bg-blue-50 text-blue-700",
                                    "Disposed" => "bg-rose-50 text-rose-700",
                                    default => "bg-slate-100 text-slate-600",
                                };

                                $placementZone = trim((string) (
                                    $item->equipment_placement_zone
                                    ?: $item->equipment_current_location
                                    ?: ''
                                ));
                                $isStorageStock = \App\Support\RoomCategories::isStorageType($item->room_type ?? null);
                                $placementLabel = $isStorageStock ? 'In storage' : 'Deployed';
                                $placementClass = $isStorageStock
                                    ? 'bg-amber-50 text-amber-700 ring-amber-200'
                                    : 'bg-sky-50 text-sky-700 ring-sky-200';
                            @endphp
                            <tr class="border-b border-slate-100 transition duration-200 hover:bg-slate-50">
                                @if ($selectable)
                                    <td class="px-4 py-4 text-center">
                                        @if (isset($pendingTransferIds[(int) $item->equipment_id]))
                                            <input
                                                type="checkbox"
                                                class="h-4 w-4 cursor-not-allowed rounded border-slate-200 opacity-40"
                                                disabled
                                                aria-label="{{ $item->equipment_name }} is awaiting transfer approval"
                                                data-tooltip="Awaiting transfer approval"
                                            />
                                        @else
                                        <input
                                            type="checkbox"
                                            class="inventory-stock-checkbox h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                            value="{{ (int) $item->equipment_id }}"
                                            data-name="{{ $item->equipment_name }}"
                                            data-status="{{ $item->equipment_inventory_status ?? '' }}"
                                            data-disposable="{{ ($item->equipment_inventory_status ?? '') === 'Replaced' ? '1' : '0' }}"
                                            onchange="syncInventoryTransferSelection()"
                                            aria-label="Select {{ $item->equipment_name }}"
                                        />
                                        @endif
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
                                                {{ $item->equipment_asset_tag ?: "No Asset Tag" }}
                                            </span>
                                            <span class="mt-0.5 text-xs text-slate-400">
                                                {{ $item->equipment_category_name ?: 'Uncategorized' }} · Qty {{ $item->equipment_quantity }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <p class="text-sm {{ filled($item->room_name) ? 'text-slate-700' : 'text-slate-400' }}">{{ $item->room_name ?: 'Unassigned' }}</p>
                                    @if ($placementZone !== '')
                                        <p class="mt-0.5 text-xs text-slate-400">{{ $placementZone }}</p>
                                    @endif
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

                                <td class="whitespace-nowrap px-4 py-4 text-center">
                                    @if ($isStockPage)
                                        @php
                                            $stockedAt = $item->equipment_acquired_date ?: $item->equipment_purchase_date;
                                            $stockedDays = filled($stockedAt)
                                                ? (int) \Carbon\Carbon::parse($stockedAt)->startOfDay()->diffInDays(today(), false)
                                                : null;
                                        @endphp
                                        @if ($stockedDays !== null)
                                            <p class="text-sm text-slate-700">{{ \Carbon\Carbon::parse($stockedAt)->format('M j, Y') }}</p>
                                            <p class="mt-0.5 text-xs {{ $stockedDays > 90 ? 'font-medium text-amber-600' : 'text-slate-400' }}">
                                                @if ($stockedDays <= 0)
                                                    Added today
                                                @else
                                                    {{ $stockedDays }} {{ \Illuminate\Support\Str::plural('day', $stockedDays) }} in storage
                                                @endif
                                            </p>
                                        @else
                                            <span class="text-sm text-slate-400">Not recorded</span>
                                        @endif
                                    @elseif (filled($item->next_maintenance_date ?? null))
                                        @php
                                            $maintenanceOverdue = \App\Support\EquipmentNextMaintenance::isOverdue($item);
                                        @endphp
                                        <a
                                            href="{{ $inAdminPortal
                                                ? url('/admin/operations/schedules').'?'.http_build_query(['q' => $item->equipment_name])
                                                : url('/maintenance/schedules').'?'.http_build_query(['search' => $item->equipment_name]) }}"
                                            class="text-sm transition hover:text-[#0025cc] {{ $maintenanceOverdue ? 'font-semibold text-rose-700' : 'text-slate-700' }}"
                                        >{{ \Carbon\Carbon::parse($item->next_maintenance_date)->format('M j, Y') }}</a>
                                        @if ($maintenanceOverdue)
                                            <span class="ml-1.5 inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">Overdue</span>
                                        @endif
                                    @else
                                        <span class="text-sm text-slate-400">No scheduled</span>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex justify-center gap-2">

                                        <button
                                            type="button"
                                            onclick='openEquipmentModal(@json(\App\Support\LayoutEquipmentPayload::fromRow($item, $eqImageUrl($item->equipment_image))))'
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                            data-tooltip="View"
                                            aria-label="View"
                                            title="View"
                                        >
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </button>

                                        @if ($canTransfer && isset($pendingTransferIds[(int) $item->equipment_id]))
                                            <a
                                                href="{{ $transferRequestsUrl }}"
                                                class="flex h-9 items-center justify-center gap-x-1.5 rounded-lg bg-amber-50 px-3 text-xs font-semibold text-amber-700 ring-1 ring-amber-200 transition hover:bg-amber-100"
                                                data-tooltip="A transfer for this item is waiting for the Administrator"
                                            >
                                                <i data-lucide="clock" class="h-4 w-4"></i>
                                                Awaiting approval
                                            </a>
                                        @elseif ($canTransfer)
                                            <button
                                                type="button"
                                                onclick="openInventoryTransferToModal(
                                                    {{ (int) $item->equipment_id }},
                                                    {{ json_encode($item->equipment_name) }},
                                                    {{ json_encode($item->room_name ?? '') }},
                                                    {{ json_encode($item->equipment_asset_tag ?? '') }},
                                                    {{ json_encode($item->equipment_tracking_mode ?? 'Individual') }},
                                                    {{ (int) ($item->equipment_quantity ?? 1) }}
                                                )"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-800 transition hover:bg-sky-100"
                                                data-pur-tip-title="{{ $transferNeedsApproval ? 'Send for approval' : 'Transfer' }}"
                                                data-pur-tip="Move this storage unit to a room, person, or department"
                                                data-pur-tip-accent="#38bdf8"
                                                aria-label="Transfer"
                                            >
                                                <i data-lucide="arrow-right-left" class="h-3.5 w-3.5"></i>
                                            </button>
                                        @endif

                                        @if ($canManage)
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

                                                '{{ $item->equipment_useful_life_years ?? '' }}',

                                                '{{ $item->equipment_is_borrowable }}',

                                                {{ json_encode($eqImageUrl($item->equipment_image)) }},

                                                {{ json_encode(\App\Support\EquipmentAcquisition::formPayload($item)) }}

                                            )"
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                            data-tooltip="Edit equipment"
                                            aria-label="Edit equipment"
                                        >
                                            <i data-lucide="edit-3" class="h-4 w-4"></i>
                                        </button>

                                        @if (($item->equipment_inventory_status ?? '') === 'For Replacement')
                                            <button
                                                type="button"
                                                onclick="openInventoryMarkReplacedModal(
                                                    {{ (int) $item->equipment_id }},
                                                    {{ json_encode($item->equipment_name) }}
                                                )"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 transition hover:bg-blue-100"
                                                data-pur-tip-title="Mark as Replaced"
                                                data-pur-tip="No successor will be installed. Closes For Replacement so this unit can be disposed."
                                                data-pur-tip-accent="#60a5fa"
                                                aria-label="Mark as Replaced"
                                            >
                                                <i data-lucide="replace" class="h-3.5 w-3.5"></i>
                                            </button>
                                        @elseif (($item->equipment_inventory_status ?? '') === 'Replaced')
                                            <button
                                                type="button"
                                                onclick="openInventoryDisposeModal(
                                                    {{ (int) $item->equipment_id }},
                                                    {{ json_encode($item->equipment_name) }},
                                                    {{ json_encode($item->room_name ?? '') }}
                                                )"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#e32626] text-white transition hover:bg-red-700"
                                                data-pur-tip-title="Dispose equipment"
                                                data-pur-tip="Move this replaced storage unit into Disposal"
                                                data-pur-tip-accent="#e32626"
                                                aria-label="Dispose equipment"
                                                title="Dispose"
                                            >
                                                <i data-lucide="archive-x" class="h-4 w-4"></i>
                                            </button>
                                        @endif
                                        @endif

                                    </div>
                                </td>
                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="{{ $selectable ? 7 : 6 }}"
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
                                                    || request()->filled('condition')
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
                                                || request()->filled('condition')

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
                                                || request()->filled('condition')

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
                                            || request()->filled('condition')
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
        @endif

        @if ($showHistory)
            @php
                $historyDirection = (string) request('history', '');
                $historyTabs = [
                    '' => ['label' => 'All', 'count' => $movementCounts['all'] ?? 0],
                    'out' => ['label' => 'Moved out', 'count' => $movementCounts['out'] ?? 0],
                    'in' => ['label' => 'Returned to storage', 'count' => $movementCounts['in'] ?? 0],
                    'between' => ['label' => 'Between storage rooms', 'count' => $movementCounts['between'] ?? 0],
                ];
                $directionBadge = [
                    'out' => 'bg-sky-50 text-sky-700 ring-sky-200/70',
                    'in' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
                    'between' => 'bg-slate-100 text-slate-600 ring-slate-200',
                ];
                $directionIcon = ['out' => 'arrow-up-right', 'in' => 'arrow-down-left', 'between' => 'arrow-left-right'];
            @endphp
            <div id="movement-history" class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="min-w-0">
                        <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                            <i data-lucide="history" class="h-4 w-4 text-slate-400"></i>
                            Movement history
                        </h2>
                    </div>
                    <form method="GET" action="{{ url()->current() }}#movement-history" class="flex shrink-0 items-center gap-2">
                        @foreach (request()->except(['history_search', 'history_page']) as $key => $value)
                            @if (is_scalar($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <div class="relative">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="text"
                                name="history_search"
                                value="{{ request('history_search') }}"
                                placeholder="Search equipment, tag, room, person"
                                class="h-10 w-64 rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                            >
                        </div>
                        <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">Search</button>
                        @if (request()->filled('history_search'))
                            <a
                                href="{{ request()->fullUrlWithQuery(['history_search' => null, 'history_page' => null]) }}#movement-history"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
                                data-tooltip="Clear search"
                            ><i data-lucide="x" class="h-4 w-4"></i></a>
                        @endif
                    </form>
                </div>

                <div class="flex items-center gap-1 overflow-x-auto whitespace-nowrap border-b border-slate-100 px-5 py-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($historyTabs as $tabKey => $tab)
                        <a
                            href="{{ request()->fullUrlWithQuery(['history' => $tabKey !== '' ? $tabKey : null, 'history_page' => null]) }}#movement-history"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm transition {{ $historyDirection === $tabKey ? 'bg-gray-100/80 font-medium text-black' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}"
                        >
                            {{ $tab['label'] }}
                            <span class="rounded-full bg-white px-1.5 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-200">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b border-slate-200 bg-slate-50/80">
                            <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-5 py-3">Date</th>
                                <th class="px-4 py-3">Equipment</th>
                                <th class="px-4 py-3">Movement</th>
                                <th class="px-4 py-3">Moved by</th>
                                <th class="px-4 py-3">Remarks</th>
                                <th class="px-4 py-3">Now in</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($movementHistory as $move)
                                @php
                                    $movedAt = $move->created_at ? \Illuminate\Support\Carbon::parse($move->created_at) : null;
                                    $movePayload = \App\Support\LayoutEquipmentPayload::fromRow($move, $eqImageUrl($move->equipment_image ?? null));
                                @endphp
                                <tr class="align-top transition hover:bg-slate-50/60">
                                    <td class="whitespace-nowrap px-5 py-3.5">
                                        @if ($movedAt)
                                            <p class="text-sm font-medium text-slate-900">{{ $movedAt->format('M j, Y') }}</p>
                                            <p class="text-xs text-slate-500">{{ $movedAt->format('g:i A') }} · {{ $movedAt->diffForHumans() }}</p>
                                        @else
                                            <span class="text-sm text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <button
                                            type="button"
                                            onclick='openEquipmentModal(@json($movePayload))'
                                            class="text-left text-sm font-semibold text-slate-900 hover:text-[#0025cc] hover:underline"
                                        >{{ $move->equipment_name }}</button>
                                        <p class="text-xs text-slate-500">
                                            {{ $move->equipment_asset_tag ?: 'No asset tag' }}
                                            @if (($move->equipment_tracking_mode ?? '') === 'Bulk')
                                                · Bulk · Qty {{ (int) $move->equipment_quantity }}
                                            @endif
                                        </p>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $directionBadge[$move->direction] }}">
                                            <i data-lucide="{{ $directionIcon[$move->direction] }}" class="h-3 w-3"></i>
                                            {{ $move->direction_label }}
                                        </span>
                                        <p class="mt-1.5 flex flex-wrap items-center gap-1 text-sm text-slate-700">
                                            <span>{{ $move->from_room_name ?: 'Unknown room' }}</span>
                                            <i data-lucide="arrow-right" class="h-3.5 w-3.5 text-slate-400"></i>
                                            <span class="font-medium text-slate-900">{{ $move->to_room_name ?: 'Unknown room' }}</span>
                                        </p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-sm text-slate-700">{{ $move->moved_by_name ?: 'System' }}</td>
                                    <td class="max-w-xs px-4 py-3.5 text-sm text-slate-600">{{ $move->remarks ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3.5">
                                        <p class="text-sm text-slate-700">{{ $move->current_room_name ?: '—' }}</p>
                                        @if (in_array($move->equipment_inventory_status, ['Disposed', 'Under Maintenance', 'Borrowed', 'For Replacement', 'Replaced'], true))
                                            <p class="text-xs text-slate-500">{{ $move->equipment_inventory_status }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex justify-center">
                                            <button
                                                type="button"
                                                onclick='openEquipmentModal(@json($movePayload))'
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                                data-tooltip="View"
                                                aria-label="View"
                                                title="View"
                                            >
                                                <i data-lucide="eye" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center">
                                        <i data-lucide="history" class="mx-auto h-6 w-6 text-slate-300"></i>
                                        <p class="mt-2 text-sm font-medium text-slate-700">
                                            {{ request()->filled('history_search') || $historyDirection !== '' ? 'No movements match this filter.' : 'No equipment has been moved yet.' }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">Transfers out of or back into a storage room are recorded here automatically.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($movementHistory->hasPages())
                    <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500">
                            Showing <span class="font-semibold text-slate-700">{{ $movementHistory->firstItem() }}</span>
                            to <span class="font-semibold text-slate-700">{{ $movementHistory->lastItem() }}</span>
                            of <span class="font-semibold text-slate-700">{{ $movementHistory->total() }}</span> movements
                        </p>
                        <div>{{ $movementHistory->links() }}</div>
                    </div>
                @endif
            </div>
        @endif

        @if ($isStockPage && $canManage)
            @include('maintenance-personnel.equipment.partials.inventory-open-balance')
        @endif
    </div>

    @php
        $eqField = 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
        $eqLabel = 'mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500';
    @endphp

        @include('maintenance-personnel.equipment.partials.equipment-asset-drawer')
    @if ($isStockPage && $canManage)
        @include('maintenance-personnel.equipment.partials.add-equipment-wizard', ['isStockPage' => true, 'wizardTitle' => 'Add to inventory'])
        @include('maintenance-personnel.equipment.partials.batch-add-wizard')
    @endif

    @if ($canManage)
    <div
        id="editEquipmentModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <form id="editEquipmentForm" method="POST" enctype="multipart/form-data" class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
            @csrf
            <div class="flex items-start justify-between px-6 pt-6">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">Edit equipment</h2>
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
                            <input id="edit_equipment_name" type="text" name="equipment_name" required maxlength="{{ \App\Support\InputLimits::NAME }}" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_category" class="{{ $eqLabel }}">Classification <span class="text-rose-500">*</span></label>
                            <select id="edit_category" name="equipment_category_id" required class="{{ $eqField }}">
                                <option value="">Select classification</option>
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
                                <input id="edit_quantity" type="number" min="1" max="{{ \App\Support\InputLimits::QTY }}" name="equipment_quantity" class="{{ $eqField }}" />
                            </div>
                            <div>
                                <label for="edit_condition" class="{{ $eqLabel }}">Condition</label>
                                <select id="edit_condition" name="equipment_condition_status" class="{{ $eqField }}">
                                    <option value="Good">Good</option>
                                    <option value="Under Maintenance">Under Maintenance</option>
                                    <option value="Defective">Defective</option>
                                    <option value="Damaged">Damaged</option>
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
                                <option value="Replaced">Replaced</option>
                                <option value="Disposed">Disposed</option>
                            </select>
                        </div>
                        <label class="flex items-center justify-between rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-200/80">
                            <span class="text-sm font-medium text-slate-900">Can be borrowed</span>
                            <input id="edit_equipment_borrowable" type="checkbox" name="equipment_is_borrowable" value="1" class="peer sr-only">
                            <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-[#0025cc] after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5"></span>
                        </label>
                    </div>
                </div>
                <details class="mt-5 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80">
                    <summary class="cursor-pointer text-sm font-medium text-slate-700">More details</summary>
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="edit_asset_tag" class="{{ $eqLabel }}">Asset tag</label>
                            <input id="edit_asset_tag" type="text" name="equipment_asset_tag" maxlength="{{ \App\Support\InputLimits::ASSET_TAG }}" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_brand" class="{{ $eqLabel }}">Brand name</label>
                            <input id="edit_brand" type="text" name="equipment_brand_name" maxlength="{{ \App\Support\InputLimits::BRAND }}" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_model" class="{{ $eqLabel }}">Model</label>
                            <input id="edit_model" type="text" name="equipment_model" maxlength="{{ \App\Support\InputLimits::MODEL }}" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_serial" class="{{ $eqLabel }}">Serial number</label>
                            <input id="edit_serial" type="text" name="equipment_serial_number" maxlength="{{ \App\Support\InputLimits::SERIAL }}" class="{{ $eqField }}" />
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
                            <input id="edit_purchase_cost" type="number" name="equipment_purchase_cost" min="0" max="{{ \App\Support\InputLimits::COST }}" step="0.01" placeholder="0.00" class="{{ $eqField }}" />
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
                        <div>
                            <label for="edit_useful_life_years" class="{{ $eqLabel }}">Useful lifespan (years)</label>
                            <input
                                id="edit_useful_life_years"
                                type="number"
                                name="equipment_useful_life_years"
                                min="1"
                                max="50"
                                step="1"
                                placeholder="Default 5"
                                class="{{ $eqField }}"
                            />
                            <p class="mt-1 text-[11px] text-slate-500">Used for replacement broadcasts when equipment nears end of life.</p>
                        </div>
                    </div>
                    @if ($acquisitionReady)
                        <div class="mt-5 border-t border-slate-200/80 pt-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Acquisition</p>
                            <p id="edit_acquisition_linked" class="mt-2 hidden rounded-xl bg-white px-3 py-2 text-xs text-slate-600 ring-1 ring-slate-200/80">
                                Linked to a Receiving Report. Supplier, PO, ATP / RIS and RR come from the procurement documents and cannot be edited here.
                            </p>
                            <div id="edit_acquisition_fields" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label for="edit_acquisition_source" class="{{ $eqLabel }}">How was it acquired?</label>
                                    <select id="edit_acquisition_source" name="equipment_acquisition_source" class="{{ $eqField }}">
                                        <option value="">Not recorded</option>
                                        @foreach (\App\Support\EquipmentAcquisition::SOURCES as $sourceKey => $sourceLabel)
                                            <option value="{{ $sourceKey }}">{{ $sourceLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="edit_acquisition_supplier" class="{{ $eqLabel }}">Supplier / donor</label>
                                    <select
                                        id="edit_acquisition_supplier"
                                        name="acquisition_supplier"
                                        data-searchable="1"
                                        data-search-placeholder="Search suppliers…"
                                        onchange="syncEditSupplierName()"
                                        class="{{ $eqField }}"
                                    >
                                        <option value="">Not recorded</option>
                                        <option value="{{ \App\Support\EquipmentAcquisition::OTHER_SUPPLIER }}">Other — type the name</option>
                                        @foreach ($acquisitionSuppliers as $supplierOption)
                                            <option value="{{ $supplierOption->supplier_id }}">{{ $supplierOption->supplier_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="edit_supplier_name_wrap" class="hidden">
                                    <label for="edit_supplier_name" class="{{ $eqLabel }}">Supplier / donor name</label>
                                    <input id="edit_supplier_name" type="text" name="equipment_supplier_name" maxlength="255" class="{{ $eqField }}" />
                                </div>
                                <div>
                                    <label for="edit_reference_number" class="{{ $eqLabel }}">Reference no.</label>
                                    <input id="edit_reference_number" type="text" name="equipment_reference_number" maxlength="120" placeholder="OR / invoice / DR no." class="{{ $eqField }}" />
                                </div>
                                <div class="sm:col-span-2 lg:col-span-4">
                                    <label for="edit_acquisition_notes" class="{{ $eqLabel }}">Notes</label>
                                    <input id="edit_acquisition_notes" type="text" name="equipment_acquisition_notes" maxlength="500" placeholder="e.g. Donated by Batch 2019 alumni" class="{{ $eqField }}" />
                                </div>
                            </div>
                        </div>
                    @endif
                </details>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button type="button" onclick="closeEditEquipmentModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                <button type="submit" class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Save changes</button>
            </div>
        </form>
    </div>
    @endif



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
            usefulLifeYears,
            borrowable,
            imageUrl,
            acquisition
        ) {
            document.getElementById("editEquipmentForm").action =
                @json($equipmentActionBase.'/update/') + id;

            document.getElementById("edit_equipment_name").value = name;

            document.getElementById("edit_asset_tag").value = assetTag;

            document.getElementById("edit_brand").value = brand;

            document.getElementById("edit_model").value = model;

            document.getElementById("edit_serial").value = serial;

            document.getElementById("edit_purchase_date").value = purchaseDate || "";

            document.getElementById("edit_acquired_date").value = acquiredDate || "";

            document.getElementById("edit_purchase_cost").value = purchaseCost || "";

            document.getElementById("edit_warranty_expiration").value = warranty;

            const usefulLifeInput = document.getElementById("edit_useful_life_years");
            if (usefulLifeInput) {
                usefulLifeInput.value = usefulLifeYears || "";
            }

            document.getElementById("edit_quantity").value = quantity;

            setEqSelectValue("edit_condition", condition);

            setEqSelectValue("edit_status", status);

            setEqSelectValue("edit_category", category);

            setEqSelectValue("edit_room", room);

            document.getElementById("edit_equipment_borrowable").checked =
                borrowable == 1;

            setEditEquipmentImage(imageUrl, name);

            setEditAcquisition(acquisition || {});

            document
                .getElementById("editEquipmentModal")
                .classList.remove("hidden");

            document.getElementById("editEquipmentModal").classList.add("flex");

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function setEditAcquisition(acquisition) {
            const fields = document.getElementById("edit_acquisition_fields");
            if (!fields) {
                return;
            }
            const linked = !!acquisition.linked_rr;
            document.getElementById("edit_acquisition_linked")?.classList.toggle("hidden", !linked);
            fields.classList.toggle("hidden", linked);
            fields.querySelectorAll("input, select").forEach((el) => {
                el.disabled = linked;
            });

            setEqSelectValue("edit_acquisition_source", acquisition.source || "");
            setEqSelectValue("edit_acquisition_supplier", acquisition.supplier || "");
            document.getElementById("edit_supplier_name").value = acquisition.supplier_name || "";
            document.getElementById("edit_reference_number").value = acquisition.reference || "";
            document.getElementById("edit_acquisition_notes").value = acquisition.notes || "";
            syncEditSupplierName();
        }

        function syncEditSupplierName() {
            const select = document.getElementById("edit_acquisition_supplier");
            const wrap = document.getElementById("edit_supplier_name_wrap");
            const input = document.getElementById("edit_supplier_name");
            if (!select || !wrap || !input) {
                return;
            }
            const isOther = select.value === @json(\App\Support\EquipmentAcquisition::OTHER_SUPPLIER);
            wrap.classList.toggle("hidden", !isOther);
            input.disabled = select.disabled || !isOther;
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
            document.getElementById("inventoryDisposeIds").innerHTML = "";
            document.getElementById("inventoryDisposeEquipmentId").value = equipmentId;
            document.getElementById("inventoryDisposeTitle").textContent = "Dispose equipment";
            document.getElementById("inventoryDisposeEquipmentName").textContent = equipmentName || "Equipment";
            document.getElementById("inventoryDisposeLocation").value = roomName || "";
            document.getElementById("inventoryDisposeReason").value = "";
            showInventoryDisposeModal();
        }

        function openInventoryDisposeSelectedModal() {
            const boxes = getInventoryDisposableSelected();
            if (!boxes.length) return;

            const container = document.getElementById("inventoryDisposeIds");
            container.innerHTML = "";
            boxes.forEach((box) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "equipment_ids[]";
                input.value = box.value;
                container.appendChild(input);
            });

            const list = document.createElement("ul");
            list.className = "space-y-1";
            boxes.forEach((box) => {
                const item = document.createElement("li");
                item.textContent = box.dataset.name || "Item";
                list.appendChild(item);
            });

            document.getElementById("inventoryDisposeEquipmentId").value = "";
            document.getElementById("inventoryDisposeTitle").textContent = "Dispose " + boxes.length + (boxes.length === 1 ? " item" : " items");
            const nameBox = document.getElementById("inventoryDisposeEquipmentName");
            nameBox.innerHTML = "";
            nameBox.appendChild(list);
            document.getElementById("inventoryDisposeLocation").value = "";
            document.getElementById("inventoryDisposeReason").value = "";
            showInventoryDisposeModal();
        }

        function openInventoryMarkReplacedModal(equipmentId, equipmentName) {
            document.getElementById("inventoryMarkReplacedEquipmentId").value = equipmentId;
            document.getElementById("inventoryMarkReplacedEquipmentName").textContent = equipmentName || "Equipment";
            document.getElementById("inventoryMarkReplacedReason").value = "";
            const modal = document.getElementById("inventoryMarkReplacedModal");
            modal.classList.remove("hidden");
            modal.classList.add("flex");
            if (window.lucide) window.lucide.createIcons();
        }

        function closeInventoryMarkReplacedModal() {
            const modal = document.getElementById("inventoryMarkReplacedModal");
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }

        function showInventoryDisposeModal() {
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

        function openInventoryTransferToModal(equipmentId, equipmentName, roomName, assetTag, trackingMode, quantity) {
            document.getElementById("inventoryTransferEquipmentId").value = equipmentId;
            document.getElementById("inventoryTransferEquipmentName").textContent = equipmentName || "Equipment";
            document.getElementById("inventoryTransferCurrentRoom").textContent = roomName || "—";
            document.getElementById("inventoryTransferAssetTag").textContent = assetTag || "No asset tag";
            resetInventoryTransferDestination("inventoryTransfer");
            setInventoryTransferQuantity("inventoryTransfer", trackingMode, quantity);
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

            resetInventoryTransferDestination("inventoryTransferAll");
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

        function getInventoryDisposableSelected() {
            return Array.from(document.querySelectorAll('.inventory-stock-checkbox:checked'))
                .filter((input) => input.dataset.disposable === '1');
        }

        function syncInventoryTransferSelection() {
            const selected = getInventorySelectedIds();
            const disposableSelected = getInventoryDisposableSelected();
            const countEl = document.getElementById('inventoryTransferSelectedCount');
            const btn = document.getElementById('inventoryTransferSelectedBtn');
            const selectAll = document.getElementById('inventorySelectAllPage');
            const boxes = document.querySelectorAll('.inventory-stock-checkbox');

            if (countEl) countEl.textContent = String(selected.length);
            if (btn) btn.disabled = selected.length === 0;

            const disposeCount = document.getElementById('inventoryDisposeSelectedCount');
            const disposeBtn = document.getElementById('inventoryDisposeSelectedBtn');
            if (disposeCount) disposeCount.textContent = String(disposableSelected.length);
            if (disposeBtn) disposeBtn.disabled = disposableSelected.length === 0;

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
            const selected = Array.from(document.querySelectorAll('.inventory-stock-checkbox:checked'));
            if (!selected.length) return;

            const items = selected.map((input) => ({
                id: input.value,
                name: input.dataset.name || 'Item',
            }));

            const countLabel = document.getElementById('inventoryTransferSelectedModalCount');
            if (countLabel) countLabel.textContent = String(items.length);

            buildInventoryBatchTransferRows(
                document.getElementById('inventoryTransferSelectedRows'),
                document.getElementById('inventoryTransferSelectedRowTemplate'),
                items
            );

            const applyAll = document.getElementById('inventoryTransferSelectedApplyAll');
            if (applyAll) applyAll.classList.toggle('hidden', items.length < 2);

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
                            <span id="inventoryTransferSelectedModalCount" class="font-semibold text-slate-800">0</span>
                            selected item(s). Destinations can differ.
                        </p>
                    </div>
                    <button type="button" onclick="closeInventoryTransferSelectedModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
            </div>

            <form action="{{ $equipmentActionBase }}/transfer-batch" method="POST" class="flex min-h-0 flex-1 flex-col">
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
                            id="inventoryTransferSelectedApplyAll"
                            onclick="applyFirstBatchDestinationToAll(document.getElementById('inventoryTransferSelectedRows'))"
                            class="text-xs font-semibold text-[#0025cc] transition hover:text-blue-800"
                        >
                            Apply first to all
                        </button>
                    </div>

                    <div id="inventoryTransferSelectedRows" class="space-y-3"></div>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <label for="inventoryTransferSelectedRemarks" class="text-sm font-semibold text-slate-700">Remarks</label>
                            <span class="text-xs text-slate-400">Optional</span>
                        </div>
                        <textarea id="inventoryTransferSelectedRemarks" name="remarks" rows="3" placeholder="e.g. 5 mice for Room 204" class="w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"></textarea>
                    </div>
                </div>

                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button type="button" onclick="closeInventoryTransferSelectedModal()" class="inline-flex h-10 items-center justify-center px-2 text-sm font-medium text-slate-600 transition hover:text-slate-950">Cancel</button>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">{{ $transferNeedsApproval ? 'Send for approval' : 'Transfer selected' }}</button>
                </div>
            </form>
        </div>

        @include('maintenance-personnel.equipment.partials.inventory-transfer-batch-row-template', [
            'templateId' => 'inventoryTransferSelectedRowTemplate',
            'prefixBase' => 'invSel',
            'roomOptions' => $deployRooms ?? collect(),
        ])
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
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer equipment</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Send this item to a room, a person or a department. It lands in Holding until placed on the floor.
                    </p>
                </div>
                <button type="button" onclick="closeInventoryTransferToModal()" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form action="{{ $equipmentActionBase }}/transfer" method="POST">
                @csrf
                <input type="hidden" name="equipment_id" id="inventoryTransferEquipmentId" value="" />

                <div class="max-h-[60vh] overflow-y-auto border-y border-slate-100 px-6 py-5 space-y-5">
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

                    @include('maintenance-personnel.equipment.partials.inventory-transfer-destination', ['prefix' => 'inventoryTransfer'])

                    <div>
                        <label for="inventoryTransferRemarks" class="mb-2 block text-sm font-medium text-slate-700">Remarks (optional)</label>
                        <textarea id="inventoryTransferRemarks" name="remarks" rows="3" placeholder="e.g. Deployed for Room 204 use" class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4">
                    <button type="button" onclick="closeInventoryTransferToModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="h-10 rounded-xl bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">{{ $transferNeedsApproval ? 'Send for approval' : 'Transfer' }}</button>
                </div>
            </form>
        </div>
    </div>

    <div
        id="inventoryTransferAllModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                        <i data-lucide="boxes" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer all equipment in storage</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Deploy all
                        <span
                            id="inventoryTransferAllCount"
                            data-count="{{ count($stockTransferIds ?? []) }}"
                            class="font-semibold text-slate-800"
                        >{{ number_format(count($stockTransferIds ?? [])) }}</span>
                        filtered storage item(s) to one room, person or department.
                    </p>
                </div>
                <button type="button" onclick="closeInventoryTransferAllModal()" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form action="{{ $equipmentActionBase }}/transfer-batch" method="POST">
                @csrf
                @foreach (($stockTransferIds ?? []) as $transferId)
                    <input type="hidden" name="equipment_ids[]" value="{{ $transferId }}" />
                @endforeach

                <div class="max-h-[60vh] overflow-y-auto border-y border-slate-100 px-6 py-5 space-y-5">
                    @include('maintenance-personnel.equipment.partials.inventory-transfer-destination', ['prefix' => 'inventoryTransferAll'])

                    <div>
                        <label for="inventoryTransferAllRemarks" class="mb-2 block text-sm font-medium text-slate-700">Remarks (optional)</label>
                        <textarea id="inventoryTransferAllRemarks" name="remarks" rows="3" placeholder="e.g. Deployed filtered equipment to Computer Laboratory 1" class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4">
                    <button type="button" onclick="closeInventoryTransferAllModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="h-10 rounded-xl bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">{{ $transferNeedsApproval ? 'Send for approval' : 'Transfer all' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if ($canManage)
    <div
        id="inventoryMarkReplacedModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
        onclick="if (event.target === this) closeInventoryMarkReplacedModal()"
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
                <button
                    type="button"
                    onclick="closeInventoryMarkReplacedModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <form action="{{ $markReplacedAction }}" method="POST">
                @csrf
                <input type="hidden" name="equipment_id" id="inventoryMarkReplacedEquipmentId" value="" />
                <div class="space-y-5 border-y border-slate-100 px-6 py-5">
                    <div>
                        <p class="mb-1 text-sm font-medium text-slate-700">Equipment</p>
                        <div id="inventoryMarkReplacedEquipmentName" class="rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900"></div>
                    </div>
                    <div>
                        <label for="inventoryMarkReplacedReason" class="mb-2 block text-sm font-medium text-slate-700">
                            Reason <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="inventoryMarkReplacedReason"
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
                    <button type="button" onclick="closeInventoryMarkReplacedModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                        Cancel
                    </button>
                    <button type="submit" class="h-10 rounded-xl bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">
                        Mark as Replaced
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                    <h2 id="inventoryDisposeTitle" class="text-lg font-semibold tracking-tight text-slate-950">
                        Dispose equipment
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Only units marked Replaced can be moved into Disposal. This is permanent until restored from Disposal.
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

            <form action="{{ $disposeAction }}" method="POST">
                @csrf
                <input type="hidden" name="equipment_id" id="inventoryDisposeEquipmentId" value="" />
                <div id="inventoryDisposeIds"></div>

                <div class="border-y border-slate-100 px-6 py-5">
                    <div class="space-y-5">
                        <div>
                            <p class="mb-1 text-sm font-medium text-slate-700">Equipment</p>
                            <div id="inventoryDisposeEquipmentName" class="max-h-32 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900"></div>
                        </div>

                        <div>
                            <label for="inventoryDisposeReason" class="mb-2 block text-sm font-medium text-slate-700">
                                Reason <span class="text-red-500">*</span>
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
                            <label for="inventoryDisposeMethod" class="mb-2 block text-sm font-medium text-slate-700">
                                Method
                            </label>
                            <select
                                id="inventoryDisposeMethod"
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
                            <label for="inventoryDisposeResidual" class="mb-2 block text-sm font-medium text-slate-700">
                                Residual value (₱)
                            </label>
                            <input
                                id="inventoryDisposeResidual"
                                name="residual_value"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="Optional salvage / residual amount"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            />
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
    @endif

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

        .monitoring-scroll {
            scrollbar-gutter: stable;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .monitoring-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .monitoring-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .monitoring-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: content-box;
        }
    </style>

@endsection
