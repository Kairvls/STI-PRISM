@extends ("layouts.maintenance-layout")

@section ("title", ($isStockPage ?? false) ? "Inventory" : "All Equipment")

@section ("content")
    @php
        $isStockPage = $isStockPage ?? (($scope ?? 'stock') === 'stock');
        $canMaintain = \App\Support\RoleAccess::hasRole(\App\Support\RoleAccess::MAINTENANCE);
        $inAdminPortal = request()->is('admin/*');
        // The Administrator adds, edits and disposes; Maintenance only transfers stock out and views the full register.
        $canManage = $inAdminPortal;
        $canEditStatus = $canMaintain && ! $inAdminPortal;
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
        $equipmentBrands = \App\Support\EquipmentBrands::options();
        $equipmentModels = \App\Support\EquipmentModels::options();
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

    <div class="space-y-3">
        @php
            $hasActiveSearch = request()->filled('search');
            $hasActiveStatus = request()->filled('status');
            $hasActiveCategory = request()->filled('category');
            $hasActiveRoom = ! $isStockPage && request()->filled('room');
            $hasActiveCondition = request()->filled('condition');
            $secondaryFilterCount = ($hasActiveCategory ? 1 : 0)
                + ($hasActiveRoom ? 1 : 0)
                + ($hasActiveCondition ? 1 : 0);
            $hasActiveFilters = $hasActiveSearch
                || $hasActiveStatus
                || $hasActiveCategory
                || $hasActiveRoom
                || $hasActiveCondition;

            $openBalanceReports = $openBalance['reports'] ?? [];
            $openBalancePending = (int) (($openBalance['totals']['pending_stock'] ?? 0));
            $openBalancePendingReplacement = (int) (($openBalance['totals']['pending_replacement'] ?? 0));
            $showOpenBalanceBtn = $isStockPage && $canManage && ! empty($openBalanceReports);

            $exportQuery = http_build_query(request()->only(['search', 'category', 'condition', 'status', 'room']));
            $inventoryUrl = $inAdminPortal ? url('/admin/inventory') : url('/maintenance/equipment/inventory');
            $addEquipmentUrl = $inAdminPortal ? url('/admin/inventory/create') : $inventoryUrl;
            $disposalUrl = $inAdminPortal ? url('/admin/disposal') : url('/maintenance/disposal');
            $historyUrl = $inAdminPortal ? url('/admin/equipment/history') : url('/maintenance/equipment/history');
            $qrToolsUrl = url('/admin/qr-tools');
        @endphp

        <!-- COMPACT PAGE HEADER -->
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex min-w-0 items-center gap-2">
                <h1 class="truncate text-lg font-semibold tracking-tight text-slate-900">
                    {{ $isStockPage ? 'Inventory' : 'All Equipment' }}
                </h1>
                <span class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                    {{ number_format($equipment->total()) }} {{ \Illuminate\Support\Str::plural('item', $equipment->total()) }}
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($isStockPage)
                    <div class="relative">
                        <button
                            type="button"
                            id="inventoryToolsMenuBtn"
                            onclick="toggleEqMenu(this, 'inventoryToolsMenu', event)"
                            aria-haspopup="menu"
                            aria-expanded="false"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                        >
                            <i data-lucide="more-horizontal" class="h-4 w-4 text-slate-500"></i>
                            <span>Actions</span>
                            <span id="inventoryToolsBadge" class="{{ ($pendingTransferCount + ($showOpenBalanceBtn ? $openBalancePending : 0)) > 0 ? '' : 'hidden' }} inline-flex min-w-[1.15rem] items-center justify-center rounded-full bg-slate-900 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">{{ $pendingTransferCount + ($showOpenBalanceBtn ? $openBalancePending : 0) }}</span>
                            <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-slate-400"></i>
                        </button>

                        <div
                            id="inventoryToolsMenu"
                            data-eq-menu
                            role="menu"
                            class="fixed z-[90] hidden w-60 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-950/10"
                        >
                            @if ($showOpenBalanceBtn)
                                <button
                                    type="button"
                                    role="menuitem"
                                    onclick="closeEqMenus(); openInventoryOpenBalanceModal()"
                                    class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-amber-800 transition hover:bg-amber-50"
                                >
                                    <span class="inline-flex items-center gap-2">
                                        <i data-lucide="scale" class="h-3.5 w-3.5 text-amber-600"></i>
                                        RR ready to stock
                                    </span>
                                    @if ($openBalancePending > 0)
                                        <span class="inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-amber-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $openBalancePending }}</span>
                                    @endif
                                </button>
                                <div class="my-1 border-t border-slate-100"></div>
                            @endif
                            @if ($canTransfer)
                                @if (! $showHistory && ! $showReplacementArrived)
                                    <button
                                        type="button"
                                        role="menuitem"
                                        id="inventoryTransferSelectedBtn"
                                        onclick="closeEqMenus(); openInventoryTransferSelectedModal()"
                                        disabled
                                        class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950 disabled:cursor-not-allowed disabled:opacity-45"
                                    >
                                        <span class="inline-flex items-center gap-2">
                                            <i data-lucide="check-square" class="h-3.5 w-3.5 text-slate-400"></i>
                                            Transfer selected
                                        </span>
                                        <span id="inventoryTransferSelectedCount" class="inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-700">0</span>
                                    </button>
                                    @if ($canManage)
                                        <button
                                            type="button"
                                            role="menuitem"
                                            id="inventoryDisposeSelectedBtn"
                                            onclick="closeEqMenus(); openInventoryDisposeSelectedModal()"
                                            disabled
                                            class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-45"
                                        >
                                            <span class="inline-flex items-center gap-2">
                                                <i data-lucide="archive-x" class="h-3.5 w-3.5 text-rose-500"></i>
                                                Dispose selected
                                            </span>
                                            <span id="inventoryDisposeSelectedCount" class="inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">0</span>
                                        </button>
                                    @endif
                                    <button
                                        type="button"
                                        role="menuitem"
                                        onclick="closeEqMenus(); openInventoryTransferAllModal()"
                                        @if (empty($stockTransferIds ?? [])) disabled @endif
                                        class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950 disabled:cursor-not-allowed disabled:opacity-45"
                                    >
                                        <i data-lucide="arrow-right-left" class="h-3.5 w-3.5 text-sky-600"></i>
                                        <span>Transfer all filtered</span>
                                    </button>
                                    <div class="my-1 border-t border-slate-100"></div>
                                @endif
                                <a
                                    href="{{ $transferRequestsUrl }}"
                                    role="menuitem"
                                    class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                >
                                    <span class="inline-flex items-center gap-2">
                                        <i data-lucide="shield-check" class="h-3.5 w-3.5 text-slate-400"></i>
                                        Transfer requests
                                    </span>
                                    @if ($pendingTransferCount > 0)
                                        <span class="inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $pendingTransferCount }}</span>
                                    @endif
                                </a>
                            @endif
                            <a
                                href="{{ $inventoryExportPdfUrl }}?{{ $exportQuery }}"
                                role="menuitem"
                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                            >
                                <i data-lucide="file-down" class="h-3.5 w-3.5 text-slate-400"></i>
                                <span>Export PDF</span>
                            </a>
                        </div>
                    </div>

                    @if ($canManage)
                        <button
                            type="button"
                            onclick="openAddEquipmentModal()"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                            @if (!($defaultStorageRoomId ?? null)) disabled @endif
                        >
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            <span>Add Equipment</span>
                        </button>
                    @endif
                @else
                    <div class="relative">
                        <button
                            type="button"
                            id="allEquipmentUtilitiesBtn"
                            onclick="toggleEqMenu(this, 'allEquipmentUtilitiesMenu', event)"
                            aria-haspopup="menu"
                            aria-expanded="false"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                        >
                            <i data-lucide="more-horizontal" class="h-4 w-4 text-slate-500"></i>
                            <span>Actions</span>
                            <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-slate-400"></i>
                        </button>

                        <div
                            id="allEquipmentUtilitiesMenu"
                            data-eq-menu
                            role="menu"
                            class="fixed z-[90] hidden w-56 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-950/10"
                        >
                            <a
                                href="{{ $inventoryExportPdfUrl }}?{{ $exportQuery }}"
                                role="menuitem"
                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                            >
                                <i data-lucide="file-down" class="h-3.5 w-3.5 text-slate-400"></i>
                                <span>Export PDF</span>
                            </a>
                            @if ($inAdminPortal)
                                <a
                                    href="{{ $qrToolsUrl }}"
                                    role="menuitem"
                                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                >
                                    <i data-lucide="qr-code" class="h-3.5 w-3.5 text-slate-400"></i>
                                    <span>Batch QR / Tagging</span>
                                </a>
                            @endif
                            <a
                                href="{{ $inventoryUrl }}"
                                role="menuitem"
                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                            >
                                <i data-lucide="warehouse" class="h-3.5 w-3.5 text-slate-400"></i>
                                <span>Storage Inventory</span>
                            </a>
                            <a
                                href="{{ $historyUrl }}"
                                role="menuitem"
                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                            >
                                <i data-lucide="history" class="h-3.5 w-3.5 text-slate-400"></i>
                                <span>Equipment History</span>
                            </a>
                            <div class="my-1 border-t border-slate-100"></div>
                            <a
                                href="{{ $disposalUrl }}"
                                role="menuitem"
                                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                            >
                                <i data-lucide="archive-x" class="h-3.5 w-3.5 text-slate-400"></i>
                                <span>Equipment Disposal</span>
                            </a>
                        </div>
                    </div>

                    <a
                        href="{{ $addEquipmentUrl }}"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800"
                    >
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        <span>{{ $canManage ? 'Add Equipment' : 'Storage Inventory' }}</span>
                    </a>
                @endif
            </div>
        </div>

        @if ($isStockPage && ! $canManage)
            <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"></i>
                <p>
                    Transfer stock to a room, a person or a department from here.
                    @if ($transferNeedsApproval)
                        Each transfer is sent to the Administrator for approval first.
                    @endif
                    Adding and new inventory items are done by the Administrator. You can update condition and inventory status, and dispose Defective or Damaged equipment.
                </p>
            </div>
        @endif
        @if ($isStockPage && $canManage && !($defaultStorageRoomId ?? null))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Create a storage room (room type <span class="font-semibold">Storage / Stockroom</span>) before adding equipment to inventory.
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
                {{-- CONSOLIDATED FILTER BAR + COLLAPSIBLE SECONDARY PANEL --}}
                {{-- ===================================================== --}}

                @php
                    $statusOptions = $isStockPage
                        ? [
                            '' => ['label' => 'All statuses', 'count' => null],
                            'Active' => ['label' => \App\Support\EquipmentInventoryStatuses::IN_STOCK_LABEL, 'count' => null],
                            'Under Maintenance' => ['label' => 'Under Maintenance', 'count' => null],
                            'For Replacement' => ['label' => 'For Replacement', 'count' => null],
                            'Replaced' => ['label' => 'Replaced', 'count' => null],
                        ]
                        : [
                            '' => ['label' => 'All Status', 'count' => (int) ($totalEquipment ?? 0)],
                            'Active' => ['label' => 'Active', 'count' => (int) ($activeEquipment ?? 0)],
                            'Under Maintenance' => ['label' => 'Maintenance', 'count' => (int) ($underMaintenanceEquipment ?? 0)],
                            'Borrowed' => ['label' => 'Borrowed', 'count' => (int) ($borrowedEquipment ?? 0)],
                            'For Replacement' => ['label' => 'For Replacement', 'count' => (int) ($forReplacementEquipment ?? 0)],
                            'Replaced' => ['label' => 'Replaced', 'count' => (int) ($replacedEquipment ?? 0)],
                            'Disposed' => ['label' => 'Disposed', 'count' => (int) ($disposedEquipment ?? 0)],
                        ];

                    $selectedCategoryName = $hasActiveCategory
                        ? optional($categories->firstWhere('equipment_category_id', (int) request('category')))->equipment_category_name
                        : null;
                    $selectedRoomName = $hasActiveRoom
                        ? optional($rooms->firstWhere('room_id', (int) request('room')))->room_name
                        : null;
                @endphp

                <div class="border-b border-slate-200 bg-white px-4 py-2.5">
                    <form
                        id="equipmentFilterForm"
                        method="GET"
                        action="{{ url()->current() }}"
                        class="space-y-2"
                    >
                        {{-- COMPACT SINGLE-ROW TOOLBAR (Search + Status + Filters toggle) --}}
                        <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap">
                            {{-- SEARCH (submits on Enter) --}}
                            <div class="relative min-w-[200px] flex-1">
                                <i
                                    data-lucide="search"
                                    class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
                                ></i>
                                <input
                                    type="search"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="Search name, brand, asset tag, or serial…"
                                    class="h-9 w-full rounded-lg border border-slate-200 bg-slate-50/70 pl-9 pr-3 text-xs text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100"
                                >
                            </div>

                            {{-- PRIMARY FILTER: STATUS (auto-submits on change) --}}
                            <div class="relative w-full sm:w-48 lg:shrink-0">
                                <i
                                    data-lucide="list-filter"
                                    class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
                                ></i>
                                <select
                                    name="status"
                                    aria-label="Filter by status"
                                    data-searchable="1"
                                    data-search-placeholder="Search status…"
                                    onchange="this.form.submit()"
                                    class="h-9 w-full appearance-none rounded-lg border border-slate-200 bg-white pl-8 pr-8 text-xs font-medium text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                                >
                                    @foreach ($statusOptions as $statusValue => $statusOption)
                                        <option
                                            value="{{ $statusValue }}"
                                            @selected((string) request('status', '') === (string) $statusValue)
                                        >
                                            {{ $statusOption['label'] }}@if ($statusOption['count'] !== null) ({{ number_format($statusOption['count']) }})@endif
                                        </option>
                                    @endforeach
                                </select>
                                <i
                                    data-lucide="chevron-down"
                                    class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
                                ></i>
                            </div>

                            {{-- COMPACT FILTERS DRAWER TOGGLE --}}
                            <button
                                type="button"
                                id="eqSecondaryFiltersBtn"
                                onclick="toggleEqSecondaryFilters()"
                                aria-expanded="false"
                                aria-controls="eqSecondaryFiltersPanel"
                                class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-xs font-semibold transition {{ $secondaryFilterCount > 0 ? 'border-blue-200 bg-blue-50/70 text-[#0025cc] hover:bg-blue-50' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                            >
                                <i data-lucide="sliders-horizontal" class="h-3.5 w-3.5"></i>
                                <span>Filters</span>
                                @if ($secondaryFilterCount > 0)
                                    <span class="inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-[#0025cc] px-1 text-[10px] font-bold leading-none text-white">{{ $secondaryFilterCount }}</span>
                                @endif
                            </button>
                        </div>

                        {{-- COMPACT COLLAPSIBLE FILTER DRAWER (Single submit path) --}}
                        <div
                            id="eqSecondaryFiltersPanel"
                            class="hidden rounded-lg border border-slate-200 bg-slate-50/80 p-3"
                        >
                            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 {{ $isStockPage ? 'lg:grid-cols-2' : 'lg:grid-cols-3' }}">
                                {{-- CLASSIFICATION --}}
                                <div>
                                    <label for="eqFilterCategory" class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                        Classification
                                    </label>
                                    <div class="relative">
                                        <select
                                            id="eqFilterCategory"
                                            name="category"
                                            data-searchable="1"
                                            data-search-placeholder="Search category…"
                                            class="h-9 w-full appearance-none rounded-lg border border-slate-200 bg-white pl-2.5 pr-8 text-xs text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                                        >
                                            <option value="">All Classifications</option>
                                            @foreach ($categories as $category)
                                                <option
                                                    value="{{ $category->equipment_category_id }}"
                                                    @selected(request('category') == $category->equipment_category_id)
                                                >
                                                    {{ $category->equipment_category_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <i data-lucide="chevron-down" class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                                    </div>
                                </div>

                                {{-- ROOM (All Equipment only) --}}
                                @unless ($isStockPage)
                                    <div>
                                        <label for="eqFilterRoom" class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                            Location / Room
                                        </label>
                                        <div class="relative">
                                            <select
                                                id="eqFilterRoom"
                                                name="room"
                                                data-searchable="1"
                                                data-search-placeholder="Search room…"
                                                class="h-9 w-full appearance-none rounded-lg border border-slate-200 bg-white pl-2.5 pr-8 text-xs text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                                            >
                                                <option value="">All Rooms</option>
                                                @foreach ($rooms as $room)
                                                    <option
                                                        value="{{ $room->room_id }}"
                                                        @selected(request('room') == $room->room_id)
                                                    >
                                                        {{ \App\Support\RoomCategories::isStorageType($room->room_type ?? null) ? 'Storage · '.$room->room_name : $room->room_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <i data-lucide="chevron-down" class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                    </div>
                                @endunless

                                {{-- CONDITION --}}
                                <div>
                                    <label for="eqFilterCondition" class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                                        Condition
                                    </label>
                                    <div class="relative">
                                        <select
                                            id="eqFilterCondition"
                                            name="condition"
                                            data-searchable="1"
                                            data-search-placeholder="Search condition…"
                                            class="h-9 w-full appearance-none rounded-lg border border-slate-200 bg-white pl-2.5 pr-8 text-xs text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                                        >
                                            <option value="">All Conditions</option>
                                            @foreach (\App\Support\EquipmentConditions::ALL as $conditionValue)
                                                @if ($isStockPage && $conditionValue === 'Disposed')
                                                    @continue
                                                @endif
                                                <option value="{{ $conditionValue }}" @selected(request('condition') === $conditionValue)>
                                                    {{ $conditionValue }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <i data-lucide="chevron-down" class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2.5 flex items-center justify-end gap-2 border-t border-slate-200/80 pt-2.5">
                                @if ($hasActiveFilters)
                                    <a
                                        href="{{ url()->current() }}"
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

                        {{-- COMPACT ACTIVE FILTER CHIPS --}}
                        @if ($hasActiveFilters)
                            <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                                <span class="font-medium text-slate-400">Active:</span>
                                @if ($hasActiveSearch)
                                    <a
                                        href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => null]) }}"
                                        class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100"
                                    >
                                        <span>Search: "{{ \Illuminate\Support\Str::limit(request('search'), 24) }}"</span>
                                        <i data-lucide="x" class="h-3 w-3 text-slate-400"></i>
                                    </a>
                                @endif
                                @if ($hasActiveStatus)
                                    <a
                                        href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}"
                                        class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100"
                                    >
                                        <span>Status: {{ $statusOptions[request('status')]['label'] ?? request('status') }}</span>
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
                                @if ($selectedRoomName)
                                    <a
                                        href="{{ request()->fullUrlWithQuery(['room' => null, 'page' => null]) }}"
                                        class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100"
                                    >
                                        <span>Room: {{ $selectedRoomName }}</span>
                                        <i data-lucide="x" class="h-3 w-3 text-slate-400"></i>
                                    </a>
                                @endif
                                @if ($hasActiveCondition)
                                    <a
                                        href="{{ request()->fullUrlWithQuery(['condition' => null, 'page' => null]) }}"
                                        class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100"
                                    >
                                        <span>Condition: {{ request('condition') }}</span>
                                        <i data-lucide="x" class="h-3 w-3 text-slate-400"></i>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </form>
                </div>



                

                <table class="w-full text-left" @if ($isStockPage) id="inventoryStockTable" @endif>
                    <thead class="border-b border-slate-200 bg-slate-50/80">
                        <tr>
                            @if ($selectable)
                                <th class="w-10 px-3 py-2 text-center">
                                    <input
                                        type="checkbox"
                                        id="inventorySelectAllPage"
                                        class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                        onclick="toggleInventorySelectAllPage(this)"
                                        aria-label="Select all on this page"
                                    />
                                </th>
                            @endif
                            <th class="px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                Equipment
                            </th>

                            <th class="px-3.5 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                Location
                            </th>

                            <th class="px-3.5 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                Condition
                            </th>

                            <th class="px-3.5 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                Status
                            </th>

                            <th class="px-3.5 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                {{ $isStockPage ? 'Added' : 'Next maintenance' }}
                            </th>

                            <th class="w-32 px-4 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($equipment as $item)
                            @php
                                $conditionClass = match ($item->equipment_condition_status ?? "") {
                                    "Good" => "bg-emerald-50 text-emerald-700 ring-emerald-200/80",
                                    "Under Maintenance" => "bg-amber-50 text-amber-700 ring-amber-200/80",
                                    "Defective" => "bg-orange-50 text-orange-700 ring-orange-200/80",
                                    "Damaged" => "bg-rose-50 text-rose-700 ring-rose-200/80",
                                    "Disposed" => "bg-slate-100 text-slate-600 ring-slate-200/80",
                                    default => "bg-slate-100 text-slate-600 ring-slate-200/80",
                                };
                                $conditionDotClass = match ($item->equipment_condition_status ?? "") {
                                    "Good" => "bg-emerald-500",
                                    "Under Maintenance" => "bg-amber-500",
                                    "Defective" => "bg-orange-500",
                                    "Damaged" => "bg-rose-500",
                                    default => "bg-slate-400",
                                };

                                $inventoryClass = match ($item->equipment_inventory_status ?? "") {
                                    "Active" => "bg-emerald-50 text-emerald-700 ring-emerald-200/80",
                                    "Borrowed" => "bg-sky-50 text-sky-700 ring-sky-200/80",
                                    "Under Maintenance" => "bg-amber-50 text-amber-700 ring-amber-200/80",
                                    "For Replacement" => "bg-orange-50 text-orange-700 ring-orange-200/80",
                                    "Replaced" => "bg-blue-50 text-blue-700 ring-blue-200/80",
                                    "Pending Disposal" => "bg-rose-50 text-rose-700 ring-rose-200/80",
                                    "Disposed" => "bg-rose-50 text-rose-700 ring-rose-200/80",
                                    default => "bg-slate-100 text-slate-600 ring-slate-200/80",
                                };
                                $inventoryDotClass = match ($item->equipment_inventory_status ?? "") {
                                    "Active" => "bg-emerald-500",
                                    "Borrowed" => "bg-sky-500",
                                    "Under Maintenance" => "bg-amber-500",
                                    "For Replacement" => "bg-orange-500",
                                    "Replaced" => "bg-blue-500",
                                    "Pending Disposal", "Disposed" => "bg-rose-500",
                                    default => "bg-slate-400",
                                };

                                $placementZone = trim((string) (
                                    $item->equipment_placement_zone
                                    ?: $item->equipment_current_location
                                    ?: ''
                                ));
                                $isStorageStock = \App\Support\RoomCategories::isStorageType($item->room_type ?? null);
                                $placementLabel = $isStorageStock ? 'In storage' : 'Deployed';
                                $placementClass = $isStorageStock
                                    ? 'bg-amber-50 text-amber-700 ring-amber-200/80'
                                    : 'bg-sky-50 text-sky-700 ring-sky-200/80';
                                $isStoresRow = \App\Support\EquipmentTrackingModes::isStores(
                                    $item->equipment_tracking_mode ?? null,
                                    isset($item->equipment_category_id) ? (int) $item->equipment_category_id : null,
                                    $item->equipment_category_name ?? null
                                );
                                $canDisposeRow = ! $isStoresRow
                                    && \App\Support\EquipmentConditions::isDisposable($item->equipment_condition_status ?? null)
                                    && ! in_array($item->equipment_inventory_status ?? '', [
                                        \App\Support\EquipmentInventoryStatuses::DISPOSED,
                                        \App\Support\EquipmentInventoryStatuses::PENDING_DISPOSAL,
                                    ], true);
                                $rowPendingTransfer = $canTransfer && isset($pendingTransferIds[(int) $item->equipment_id]);
                                $rowCanTransfer = $canTransfer && ! $rowPendingTransfer;
                                $rowCanEditStock = $canManage && $isStockPage && empty($item->grouped_bulk);
                                $rowCanMarkReplaced = $canManage && ($item->equipment_inventory_status ?? '') === 'For Replacement';
                                $rowCanStockMove = $canManage && $isStoresRow && ($item->equipment_inventory_status ?? '') !== 'Disposed';
                                $rowCanAdminDispose = $canManage && $canDisposeRow;
                                $rowCanMaintEdit = $canEditStatus && empty($item->grouped_bulk);
                                $rowCanMaintDispose = $rowCanMaintEdit && $canDisposeRow;
                                $rowHasMenu = $rowPendingTransfer
                                    || $rowCanTransfer
                                    || $rowCanEditStock
                                    || $rowCanMarkReplaced
                                    || $rowCanStockMove
                                    || $rowCanAdminDispose
                                    || $rowCanMaintEdit
                                    || $rowCanMaintDispose;
                                $rowMenuId = 'eqRowMenu_' . (int) $item->equipment_id . '_' . $loop->index;
                            @endphp
                            <tr class="align-middle transition-colors duration-150 hover:bg-slate-50/80">
                                @if ($selectable)
                                    <td class="px-3 py-2 text-center align-middle">
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
                                            data-tag="{{ $item->equipment_asset_tag }}"
                                            data-category="{{ $item->equipment_category_name }}"
                                            data-qty="{{ $item->equipment_quantity }}"
                                            data-status="{{ $item->equipment_inventory_status ?? '' }}"
                                            data-disposable="{{ $canDisposeRow ? '1' : '0' }}"
                                            onchange="syncInventoryTransferSelection()"
                                            aria-label="Select {{ $item->equipment_name }}"
                                        />
                                        @endif
                                    </td>
                                @endif
                                <td class="px-4 py-2 align-middle">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white ring-1 ring-slate-200/80">
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
                                                <span class="truncate text-[13px] font-semibold leading-tight text-slate-900">
                                                    {{ $item->equipment_name }}
                                                </span>
                                                <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold leading-none ring-1 ring-inset {{ $placementClass }}">
                                                    {{ $placementLabel }}
                                                </span>
                                            </div>

                                            <div class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-[11px] leading-tight text-slate-500">
                                                <span class="font-mono text-[11px] text-slate-500">{{ \App\Support\EquipmentIdentity::unitTagLabel($item) ?: "No Asset Tag" }}</span>
                                                <span class="text-slate-300">·</span>
                                                <span>{{ $item->equipment_category_name ?: 'Uncategorized' }}</span>
                                                <span class="text-slate-300">·</span>
                                                <span>Qty {{ $item->equipment_quantity }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3.5 py-2 align-middle">
                                    <p class="text-[13px] font-medium leading-tight {{ filled($item->room_name) ? 'text-slate-700' : 'text-slate-400' }}">{{ $item->room_name ?: 'Unassigned' }}</p>
                                    @if ($placementZone !== '')
                                        <p class="mt-0.5 text-[11px] leading-tight text-slate-400">{{ $placementZone }}</p>
                                    @endif
                                </td>

                                <td class="px-3.5 py-2 text-center align-middle">
                                    <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $conditionClass }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $conditionDotClass }}"></span>
                                        {{ $item->equipment_condition_status }}
                                    </span>
                                </td>

                                <td class="px-3.5 py-2 text-center align-middle">
                                    <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $inventoryClass }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $inventoryDotClass }}"></span>
                                        {{ \App\Support\EquipmentInventoryStatuses::display($item->equipment_inventory_status, $isStockPage) }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-3.5 py-2 text-left align-middle">
                                    @if ($isStockPage)
                                        @php
                                            $stockedAt = $item->equipment_acquired_date ?: $item->equipment_purchase_date;
                                            $stockedDays = filled($stockedAt)
                                                ? (int) \Carbon\Carbon::parse($stockedAt)->startOfDay()->diffInDays(today(), false)
                                                : null;
                                        @endphp
                                        @if ($stockedDays !== null)
                                            <p class="text-[13px] font-medium leading-tight text-slate-700">{{ \Carbon\Carbon::parse($stockedAt)->format('M j, Y') }}</p>
                                            <p class="mt-0.5 text-[11px] leading-tight {{ $stockedDays > 90 ? 'font-medium text-amber-600' : 'text-slate-400' }}">
                                                @if ($stockedDays <= 0)
                                                    Added today
                                                @else
                                                    {{ $stockedDays }} {{ \Illuminate\Support\Str::plural('day', $stockedDays) }} in storage
                                                @endif
                                            </p>
                                        @else
                                            <span class="text-xs text-slate-400">Not recorded</span>
                                        @endif
                                    @elseif (filled($item->next_maintenance_date ?? null))
                                        @php
                                            $maintenanceOverdue = \App\Support\EquipmentNextMaintenance::isOverdue($item);
                                        @endphp
                                        <div class="flex items-center gap-1.5">
                                            <a
                                                href="{{ $inAdminPortal
                                                    ? url('/admin/operations/schedules').'?'.http_build_query(['q' => $item->equipment_name])
                                                    : url('/maintenance/schedules').'?'.http_build_query(['search' => $item->equipment_name]) }}"
                                                class="text-[13px] font-medium transition hover:text-[#0025cc] {{ $maintenanceOverdue ? 'text-rose-700' : 'text-slate-700' }}"
                                            >{{ \Carbon\Carbon::parse($item->next_maintenance_date)->format('M j, Y') }}</a>
                                            @if ($maintenanceOverdue)
                                                <span class="inline-flex items-center rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold leading-none text-rose-700 ring-1 ring-inset ring-rose-200/80">Overdue</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">Not scheduled</span>
                                    @endif
                                </td>

                                <td class="px-4 py-2 text-left align-middle">
                                    <div class="flex items-center justify-start gap-1">
                                        {{-- PRIMARY ROW ACTION --}}
                                        <button
                                            type="button"
                                            onclick='openEquipmentModal(@json(\App\Support\LayoutEquipmentPayload::fromRow($item, $eqImageUrl($item->equipment_image))))'
                                            class="inline-flex h-7 items-center justify-center gap-1 rounded-md border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950"
                                            aria-label="View {{ $item->equipment_name }}"
                                            title="View details"
                                        >
                                            <i data-lucide="eye" class="h-3.5 w-3.5 text-slate-500"></i>
                                            <span>View</span>
                                        </button>

                                        {{-- COMPACT "..." SECONDARY ACTIONS MENU --}}
                                        @if ($rowHasMenu)
                                            <button
                                                type="button"
                                                onclick="toggleEqMenu(this, '{{ $rowMenuId }}', event)"
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
                                                data-eq-menu
                                                role="menu"
                                                class="fixed z-[90] hidden w-48 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-xl shadow-slate-950/10"
                                            >
                                                @if ($rowPendingTransfer)
                                                    <a
                                                        href="{{ $transferRequestsUrl }}"
                                                        role="menuitem"
                                                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-amber-700 transition hover:bg-amber-50"
                                                    >
                                                        <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                                                        <span>Awaiting approval</span>
                                                    </a>
                                                @elseif ($rowCanTransfer)
                                                    <button
                                                        type="button"
                                                        role="menuitem"
                                                        onclick="closeEqMenus(); openInventoryTransferToModal(
                                                            {{ (int) $item->equipment_id }},
                                                            {{ json_encode($item->equipment_name) }},
                                                            {{ json_encode($item->room_name ?? '') }},
                                                            {{ json_encode($item->equipment_asset_tag ?? '') }},
                                                            {{ json_encode($item->equipment_tracking_mode ?? 'Individual') }},
                                                            {{ (int) ($item->equipment_quantity ?? 1) }}
                                                        )"
                                                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                                    >
                                                        <i data-lucide="arrow-right-left" class="h-3.5 w-3.5 text-sky-600"></i>
                                                        <span>{{ $transferNeedsApproval ? 'Send for approval' : 'Transfer' }}</span>
                                                    </button>
                                                @endif

                                                @if ($rowCanEditStock)
                                                    <button
                                                        type="button"
                                                        role="menuitem"
                                                        onclick="closeEqMenus(); openEditEquipmentModal(
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
                                                            {{ json_encode($eqImageUrl($item->equipment_image)) }},
                                                            {{ json_encode(\App\Support\EquipmentAcquisition::formPayload($item)) }}
                                                        )"
                                                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                                    >
                                                        <i data-lucide="edit-3" class="h-3.5 w-3.5 text-[#0025cc]"></i>
                                                        <span>Edit equipment</span>
                                                    </button>
                                                @endif

                                                @if ($rowCanMarkReplaced)
                                                    <button
                                                        type="button"
                                                        role="menuitem"
                                                        onclick="closeEqMenus(); openInventoryMarkReplacedModal(
                                                            {{ (int) $item->equipment_id }},
                                                            {{ json_encode($item->equipment_name) }}
                                                        )"
                                                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                                    >
                                                        <i data-lucide="replace" class="h-3.5 w-3.5 text-blue-600"></i>
                                                        <span>Mark as Replaced</span>
                                                    </button>
                                                @endif

                                                @if ($rowCanStockMove)
                                                    <button
                                                        type="button"
                                                        role="menuitem"
                                                        onclick="closeEqMenus(); openStockMovementModal(
                                                            {{ (int) $item->equipment_id }},
                                                            {{ json_encode($item->equipment_name) }},
                                                            {{ (int) ($item->equipment_quantity ?? 0) }}
                                                        )"
                                                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                                    >
                                                        <i data-lucide="clipboard-list" class="h-3.5 w-3.5 text-slate-500"></i>
                                                        <span>Stock movement</span>
                                                    </button>
                                                @endif

                                                @if ($rowCanMaintEdit)
                                                    <button
                                                        type="button"
                                                        role="menuitem"
                                                        onclick="closeEqMenus(); openMaintenanceStatusModal(
                                                            {{ (int) $item->equipment_id }},
                                                            {{ json_encode($item->equipment_name) }},
                                                            {{ json_encode($item->equipment_condition_status ?? '') }},
                                                            {{ json_encode($item->equipment_inventory_status ?? '') }}
                                                        )"
                                                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                                    >
                                                        <i data-lucide="edit-3" class="h-3.5 w-3.5 text-[#0025cc]"></i>
                                                        <span>Edit status</span>
                                                    </button>
                                                @endif

                                                @if ($rowCanAdminDispose || $rowCanMaintDispose)
                                                    @if ($rowPendingTransfer || $rowCanTransfer || $rowCanEditStock || $rowCanMarkReplaced || $rowCanStockMove || $rowCanMaintEdit)
                                                        <div class="my-1 border-t border-slate-100"></div>
                                                    @endif
                                                    <button
                                                        type="button"
                                                        role="menuitem"
                                                        onclick="closeEqMenus(); openInventoryDisposeModal(
                                                            {{ (int) $item->equipment_id }},
                                                            {{ json_encode($item->equipment_name) }},
                                                            {{ (int) ($item->equipment_room_id ?? 0) }},
                                                            {{ json_encode($item->equipment_asset_tag ?? '') }},
                                                            {{ json_encode($item->equipment_category_name ?? '') }},
                                                            {{ json_encode($item->equipment_quantity ?? 1) }}
                                                        )"
                                                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-700"
                                                    >
                                                        <i data-lucide="archive-x" class="h-3.5 w-3.5"></i>
                                                        <span>Dispose</span>
                                                    </button>
                                                @endif
                                            </div>
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
                    class="flex flex-col gap-2
                        border-t border-slate-200
                        px-4 py-2.5
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
                                                aria-label="View"
                                                title="View"
                                                data-tooltip="View"
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

        @include('maintenance-personnel.equipment.partials.equipment-asset-drawer', [
            'equipmentActiveMeansInStock' => $isStockPage,
        ])
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
                            <label for="edit_category" class="{{ $eqLabel }}">Classification</label>
                            <select id="edit_category" name="equipment_category_id" required class="{{ $eqField }} bg-slate-100 text-slate-600" disabled>
                                <option value="">Select classification</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->equipment_category_id }}">{{ $category->equipment_category_name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="edit_category_locked" name="equipment_category_id" value="" disabled />
                            <p class="mt-1.5 text-[11px] leading-4 text-slate-500">Classification is set when the item is stocked and cannot be changed here.</p>
                        </div>
                        <div>
                            <label for="edit_room" class="{{ $eqLabel }}">Room</label>
                            <select id="edit_room" name="equipment_room_id" required class="{{ $eqField }} bg-slate-100 text-slate-600" disabled>
                                <option value="">Select room</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->room_id }}">
                                        {{ \App\Support\RoomCategories::isStorageType($room->room_type ?? null) ? 'Storage · '.$room->room_name : $room->room_name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" id="edit_room_locked" name="equipment_room_id" value="" disabled />
                            <p id="edit_room_hint" class="mt-1.5 text-[11px] leading-4 text-slate-500">To move this item, use Transfer from Inventory.</p>
                        </div>
                    </div>
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="edit_quantity" class="{{ $eqLabel }}">Qty</label>
                                <input id="edit_quantity" type="number" min="1" max="{{ \App\Support\InputLimits::QTY }}" name="equipment_quantity" class="{{ $eqField }}" @if (\App\Support\EquipmentStockLedger::enabled()) readonly @endif />
                                @if (\App\Support\EquipmentStockLedger::enabled())
                                    <p class="mt-1.5 text-[11px] leading-4 text-slate-500">On-hand quantity changes through receipts, transfers, issue, spoilage, or a count adjustment.</p>
                                @endif
                            </div>
                            <div>
                                <label for="edit_condition" class="{{ $eqLabel }}">Condition</label>
                                <select id="edit_condition" name="equipment_condition_status" class="{{ $eqField }}">
                                    <option value="Good">Good</option>
                                    <option value="Under Maintenance">Under Maintenance</option>
                                    <option value="Defective">Defective</option>
                                    <option value="Damaged">Damaged</option>
                                </select>
                                <p id="edit_condition_hint" class="mt-1.5 text-[11px] leading-4 text-slate-500">Damaged equipment can be disposed from Equipment Disposal.</p>
                            </div>
                        </div>
                        <div>
                            <label for="edit_status" class="{{ $eqLabel }}">Inventory status</label>
                            <select id="edit_status" name="equipment_inventory_status" class="{{ $eqField }}">
                                <option value="Active">{{ $isStockPage ? \App\Support\EquipmentInventoryStatuses::IN_STOCK_LABEL : 'Active' }}</option>
                                <option value="Under Maintenance">Under maintenance</option>
                            </select>
                            <input type="hidden" id="edit_status_locked" value="" disabled />
                            <p class="mt-1.5 text-[11px] leading-4 text-slate-500">Borrowed, replacement, and disposal are set by their own records.</p>
                            <p id="edit_disposal_lock_note" class="mt-1.5 hidden text-[11px] leading-4 text-slate-500">This unit is already disposed. Condition and inventory status stay with Equipment Disposal.</p>
                        </div>
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
                            <select
                                id="edit_brand"
                                name="equipment_brand_name"
                                data-searchable="1"
                                data-search-placeholder="Search brands…"
                                data-add-url="{{ url('/admin/inventory/brands/quick-add') }}"
                                data-add-label="Add new brand"
                                data-add-placeholder="New brand name"
                                class="{{ $eqField }}"
                            >
                                <option value="">Select brand</option>
                                @foreach ($equipmentBrands as $brandOption)
                                    <option value="{{ $brandOption }}">{{ $brandOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="edit_model" class="{{ $eqLabel }}">Model</label>
                            <select
                                id="edit_model"
                                name="equipment_model"
                                data-searchable="1"
                                data-search-placeholder="Search models…"
                                data-add-url="{{ url('/admin/inventory/models/quick-add') }}"
                                data-add-label="Add new model"
                                data-add-placeholder="New model name"
                                class="{{ $eqField }}"
                            >
                                <option value="">Select model</option>
                                @foreach ($equipmentModels as $modelOption)
                                    <option value="{{ $modelOption }}">{{ $modelOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="edit_serial" class="{{ $eqLabel }}">Serial number</label>
                            <input id="edit_serial" type="text" name="equipment_serial_number" maxlength="{{ \App\Support\InputLimits::SERIAL }}" placeholder="e.g. SN-ABC/12+34" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_purchase_date" class="{{ $eqLabel }}">Purchased</label>
                            <input id="edit_purchase_date" type="date" name="equipment_purchase_date" class="{{ $eqField }} bg-slate-100 text-slate-600" readonly tabindex="-1" />
                            <p class="mt-1 text-[11px] text-slate-500">From intake or procurement; not editable here.</p>
                        </div>
                        <div>
                            <label for="edit_acquired_date" class="{{ $eqLabel }}">Acquired</label>
                            <input id="edit_acquired_date" type="date" name="equipment_acquired_date" class="{{ $eqField }} bg-slate-100 text-slate-600" readonly tabindex="-1" />
                        </div>
                        <div>
                            <label for="edit_purchase_cost" class="{{ $eqLabel }}">Purchase cost (₱)</label>
                            <input id="edit_purchase_cost" type="number" name="equipment_purchase_cost" min="0" max="{{ \App\Support\InputLimits::COST }}" step="0.01" placeholder="0.00" class="{{ $eqField }} bg-slate-100 text-slate-600" readonly tabindex="-1" />
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
                                max="{{ \App\Support\EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS }}"
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

            <div class="border-t border-slate-100 px-6 py-4">
                <label for="edit_edit_reason" class="{{ $eqLabel }}">Reason for change <span class="text-rose-500">*</span></label>
                <textarea
                    id="edit_edit_reason"
                    name="equipment_edit_reason"
                    rows="2"
                    required
                    maxlength="500"
                    placeholder="e.g. Corrected asset tag typo, updated warranty date"
                    class="mt-1.5 w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                ></textarea>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeEditEquipmentModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Save changes</button>
                </div>
            </div>
        </form>
    </div>
    @endif

    @if ($canEditStatus)
    <div
        id="maintenanceStatusModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <form id="maintenanceStatusForm" method="POST" class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
            @csrf
            <div class="flex items-start justify-between gap-4 px-6 pt-6">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">Edit equipment</h2>
                    <p id="maintenanceStatusName" class="mt-1 truncate text-sm text-slate-500"></p>
                </div>
                <button type="button" onclick="closeMaintenanceStatusModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="maintenance_condition" class="{{ $eqLabel }}">Condition</label>
                    <select id="maintenance_condition" name="equipment_condition_status" required class="{{ $eqField }}">
                        <option value="Good">Good</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                        <option value="Defective">Defective</option>
                        <option value="Damaged">Damaged</option>
                    </select>
                    <p id="maintenance_condition_hint" class="mt-1.5 text-[11px] leading-4 text-slate-500">Damaged equipment can be disposed from Equipment Disposal.</p>
                </div>
                <div>
                    <label for="maintenance_status" class="{{ $eqLabel }}">Inventory status</label>
                    <select id="maintenance_status" name="equipment_inventory_status" required class="{{ $eqField }}">
                        <option value="Active">{{ $isStockPage ? \App\Support\EquipmentInventoryStatuses::IN_STOCK_LABEL : 'Active' }}</option>
                        <option value="Under Maintenance">Under maintenance</option>
                    </select>
                    <input type="hidden" id="maintenance_status_locked" value="" disabled />
                    <p id="maintenance_disposal_lock_note" class="mt-1.5 hidden text-[11px] leading-4 text-slate-500">This unit is already disposed. Condition and inventory status stay with Equipment Disposal.</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button type="button" onclick="closeMaintenanceStatusModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                <button type="submit" id="maintenanceStatusSave" class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50">Save changes</button>
            </div>
        </form>
    </div>
    <script>
        function openMaintenanceStatusModal(id, name, condition, status) {
            const form = document.getElementById("maintenanceStatusForm");
            form.action = @json(url('/maintenance/equipment/inventory/status')) + "/" + id;
            document.getElementById("maintenanceStatusName").textContent = name || "Equipment";
            setEqSelectValue("maintenance_condition", condition || "Good");
            lockDocumentStatus("maintenance_status", "maintenance_status_locked", status || "Active");
            const locked = status === "Disposed" || status === "Pending Disposal";
            const conditionSelect = document.getElementById("maintenance_condition");
            if (conditionSelect) {
                conditionSelect.querySelectorAll("option[data-locked-disposed]").forEach(function (opt) { opt.remove(); });
                if (locked && condition && !Array.from(conditionSelect.options).some(function (opt) { return opt.value === condition; })) {
                    const opt = new Option(condition, condition);
                    opt.dataset.lockedDisposed = "1";
                    conditionSelect.add(opt);
                    conditionSelect.value = condition;
                }
                conditionSelect.disabled = locked;
            }
            const statusSelect = document.getElementById("maintenance_status");
            if (statusSelect && locked) {
                statusSelect.disabled = true;
            }
            document.getElementById("maintenance_condition_hint").classList.toggle("hidden", locked);
            document.getElementById("maintenance_disposal_lock_note").classList.toggle("hidden", !locked);
            document.getElementById("maintenanceStatusSave").disabled = locked;
            const modal = document.getElementById("maintenanceStatusModal");
            modal.classList.remove("hidden");
            modal.classList.add("flex");
            if (window.lucide) window.lucide.createIcons();
        }

        function closeMaintenanceStatusModal() {
            if (window.closeEqSelectPanel) closeEqSelectPanel();
            const modal = document.getElementById("maintenanceStatusModal");
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }
    </script>
    @endif



    <script>
        function syncEditDisposalLock(condition, status) {
            const locked = status === "Disposed" || status === "Pending Disposal";
            const select = document.getElementById("edit_condition");
            if (select) {
                select.querySelectorAll("option[data-locked-disposed]").forEach(function (opt) {
                    opt.remove();
                });
                if (locked && condition) {
                    if (!Array.from(select.options).some(function (opt) { return opt.value === condition; })) {
                        const opt = new Option(condition, condition);
                        opt.dataset.lockedDisposed = "1";
                        select.add(opt);
                    }
                    select.value = condition;
                }
                select.disabled = locked;
            }
            const statusSelect = document.getElementById("edit_status");
            if (statusSelect && locked) {
                statusSelect.disabled = true;
            }
            const conditionSelect = document.getElementById("edit_condition");
            if (!locked && condition === "Disposed" && conditionSelect) {
                conditionSelect.value = "Defective";
            }
            const hint = document.getElementById("edit_condition_hint");
            const note = document.getElementById("edit_disposal_lock_note");
            if (hint) hint.classList.toggle("hidden", locked);
            if (note) note.classList.toggle("hidden", !locked);
        }

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
            imageUrl,
            acquisition
        ) {
            document.getElementById("editEquipmentForm").action =
                @json($equipmentActionBase.'/update/') + id;

            document.getElementById("edit_equipment_name").value = name;

            document.getElementById("edit_asset_tag").value = assetTag;

            (function setCatalogSelect(id, value) {
                const select = document.getElementById(id);
                if (!select) return;
                const label = String(value || '').trim();
                if (label && !Array.from(select.options).some((opt) => opt.value === label)) {
                    select.add(new Option(label, label));
                }
                select.value = label;
            })("edit_brand", brand);
            (function setCatalogSelect(id, value) {
                const select = document.getElementById(id);
                if (!select) return;
                const label = String(value || '').trim();
                if (label && !Array.from(select.options).some((opt) => opt.value === label)) {
                    select.add(new Option(label, label));
                }
                select.value = label;
            })("edit_model", model);

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

            lockDocumentStatus("edit_status", "edit_status_locked", status);

            syncEditDisposalLock(condition, status);

            lockEditCategoryAndRoom(category, room);

            setEditEquipmentImage(imageUrl, name);

            setEditAcquisition(acquisition || {});

            syncEditProcurementLocks(!!(acquisition && acquisition.linked_rr));

            window.editEquipmentSnapshot = snapshotEditEquipmentForm(
                document.getElementById("edit_condition")?.value || condition,
                document.getElementById("edit_status")?.value
                    || document.getElementById("edit_status_locked")?.value
                    || status
            );

            const reasonField = document.getElementById("edit_edit_reason");
            if (reasonField) reasonField.value = "";

            document
                .getElementById("editEquipmentModal")
                .classList.remove("hidden");

            document.getElementById("editEquipmentModal").classList.add("flex");

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function lockEditCategoryAndRoom(categoryId, roomId) {
            setEqSelectValue("edit_category", categoryId);
            setEqSelectValue("edit_room", roomId);
            const catSelect = document.getElementById("edit_category");
            const roomSelect = document.getElementById("edit_room");
            const catHidden = document.getElementById("edit_category_locked");
            const roomHidden = document.getElementById("edit_room_locked");
            [catSelect, roomSelect].forEach(function (el) {
                if (!el) return;
                el.disabled = true;
                el.removeAttribute("name");
            });
            if (catHidden) {
                catHidden.disabled = false;
                catHidden.value = categoryId || "";
            }
            if (roomHidden) {
                roomHidden.disabled = false;
                roomHidden.value = roomId || "";
            }
        }

        function syncEditProcurementLocks(linkedRr) {
            ["edit_purchase_date", "edit_acquired_date", "edit_purchase_cost"].forEach(function (id) {
                const el = document.getElementById(id);
                if (!el) return;
                el.readOnly = true;
                el.removeAttribute("name");
            });
            ["edit_brand", "edit_model"].forEach(function (id) {
                const el = document.getElementById(id);
                if (!el) return;
                el.disabled = linkedRr;
                if (linkedRr) {
                    el.removeAttribute("name");
                } else {
                    el.setAttribute("name", id === "edit_brand" ? "equipment_brand_name" : "equipment_model");
                }
            });
        }

        function snapshotEditEquipmentForm(condition, status) {
            return {
                name: document.getElementById("edit_equipment_name")?.value || "",
                assetTag: document.getElementById("edit_asset_tag")?.value || "",
                serial: document.getElementById("edit_serial")?.value || "",
                brand: document.getElementById("edit_brand")?.value || "",
                model: document.getElementById("edit_model")?.value || "",
                condition: condition || "",
                status: status || "",
                warranty: document.getElementById("edit_warranty_expiration")?.value || "",
                usefulLife: document.getElementById("edit_useful_life_years")?.value || "",
                notes: document.getElementById("edit_acquisition_notes")?.value || "",
            };
        }

        function buildEditEquipmentChangeSummary() {
            const snap = window.editEquipmentSnapshot;
            if (!snap) return "";
            const lines = [];
            const current = snapshotEditEquipmentForm(
                document.getElementById("edit_condition")?.value,
                document.getElementById("edit_status")?.value || document.getElementById("edit_status_locked")?.value
            );
            const pairs = [
                ["name", "Name"],
                ["assetTag", "Asset tag"],
                ["serial", "Serial"],
                ["brand", "Brand"],
                ["model", "Model"],
                ["condition", "Condition"],
                ["status", "Status"],
                ["warranty", "Warranty"],
                ["usefulLife", "Useful life"],
                ["notes", "Notes"],
            ];
            pairs.forEach(function (pair) {
                const key = pair[0];
                const label = pair[1];
                if (String(snap[key] || "") !== String(current[key] || "")) {
                    lines.push(label);
                }
            });
            const fileInput = document.getElementById("edit_equipment_image");
            if (fileInput && fileInput.files && fileInput.files.length) {
                lines.push("Photo");
            }
            if (document.getElementById("edit_remove_image")?.value === "1") {
                lines.push("Photo removed");
            }
            return lines.length ? ("Updating: " + lines.join(", ") + ".") : "No field changes detected besides your reason.";
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

        (function () {
            const form = document.getElementById("editEquipmentForm");
            if (!form) return;
            form.addEventListener("submit", function (event) {
                if (form.dataset.purConfirmAccepted === "1") {
                    delete form.dataset.purConfirmAccepted;
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                const reason = (document.getElementById("edit_edit_reason")?.value || "").trim();
                if (reason.length < 3) {
                    document.getElementById("edit_edit_reason")?.focus();
                    return;
                }
                const summary = buildEditEquipmentChangeSummary();
                const message = summary + " Continue?";
                if (typeof window.purConfirm !== "function") {
                    form.dataset.purConfirmAccepted = "1";
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                    return;
                }
                window.purConfirm({
                    title: "Save equipment changes?",
                    text: message,
                    confirmText: "Save changes",
                    kind: "caution",
                }).then(function (ok) {
                    if (!ok) return;
                    form.dataset.purConfirmAccepted = "1";
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                });
            }, true);
        })();

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

        function lockDocumentStatus(selectId, hiddenId, status) {
            const select = document.getElementById(selectId);
            const hidden = document.getElementById(hiddenId);
            if (!select) return;
            const manual = ["Active", "Under Maintenance"];
            const current = status || "Active";
            select.querySelectorAll("option[data-locked-doc]").forEach(function (opt) { opt.remove(); });
            if (manual.indexOf(current) === -1) {
                const opt = new Option(current, current);
                opt.dataset.lockedDoc = "1";
                select.add(opt);
                select.value = current;
                select.disabled = true;
                select.removeAttribute("name");
                if (hidden) {
                    hidden.disabled = false;
                    hidden.name = "equipment_inventory_status";
                    hidden.value = current;
                }
                return;
            }
            select.disabled = false;
            select.name = "equipment_inventory_status";
            setEqSelectValue(selectId, current);
            if (hidden) {
                hidden.disabled = true;
                hidden.removeAttribute("name");
                hidden.value = "";
            }
        }

        function openStockMovementModal(equipmentId, equipmentName, onHand) {
            const modal = document.getElementById("stockMovementModal");
            if (!modal) return;
            document.getElementById("stockMovementEquipmentId").value = equipmentId;
            document.getElementById("stockMovementName").textContent = equipmentName || "Stock";
            document.getElementById("stockMovementOnHand").textContent = String(onHand ?? 0);
            document.getElementById("stockMovementQuantity").value = "";
            document.getElementById("stockMovementReason").value = "";
            const kind = document.getElementById("stockMovementKind");
            if (kind) kind.value = "issue";
            syncStockMovementHint();
            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }

        function closeStockMovementModal() {
            const modal = document.getElementById("stockMovementModal");
            if (!modal) return;
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }

        function syncStockMovementHint() {
            const kind = document.getElementById("stockMovementKind");
            const hint = document.getElementById("stockMovementHint");
            const label = document.getElementById("stockMovementQtyLabel");
            if (!kind || !hint || !label) return;
            if (kind.value === "adjustment") {
                label.textContent = "Counted on hand";
                hint.textContent = "Enter what you counted. The difference from the book is posted as an adjustment.";
            } else if (kind.value === "spoilage") {
                label.textContent = "Quantity to write off";
                hint.textContent = "Removes damaged or unusable bulk stock. This does not create a disposal record.";
            } else {
                label.textContent = "Quantity to issue";
                hint.textContent = "Removes stock that was given out. The reason should say who received it.";
            }
        }

        function clearInventoryDisposeReasonError() {
            const reason = document.getElementById("inventoryDisposeReason");
            const reasonError = document.getElementById("inventoryDisposeReasonError");
            if (reason) {
                reason.classList.remove("border-rose-400", "ring-4", "ring-rose-100");
            }
            if (reasonError) {
                reasonError.textContent = "";
                reasonError.classList.add("hidden");
            }
        }

        function inventoryEquipmentIdentity(item) {
            const block = document.createElement("div");
            block.className = "min-w-0";

            const name = document.createElement("p");
            name.className = "font-semibold text-slate-900";
            name.textContent = item.name || "Equipment";

            const tag = document.createElement("p");
            tag.className = "mt-1 text-xs text-slate-400";
            tag.textContent = item.tag || "No Asset Tag";

            const meta = document.createElement("p");
            meta.className = "mt-0.5 text-xs text-slate-400";
            const category = item.category || "Uncategorized";
            const qty = item.qty === undefined || item.qty === null || item.qty === "" ? "1" : String(item.qty);
            meta.textContent = category + " · Qty " + qty;

            block.append(name, tag, meta);
            return block;
        }

        function fillInventoryDisposeEquipment(items) {
            const box = document.getElementById("inventoryDisposeEquipmentName");
            box.replaceChildren();
            items.forEach((item, index) => {
                const block = inventoryEquipmentIdentity(item);
                if (index > 0) {
                    block.classList.add("mt-3", "border-t", "border-slate-200", "pt-3");
                }
                box.appendChild(block);
            });
        }

        function openInventoryDisposeModal(equipmentId, equipmentName, roomId, assetTag, category, quantity) {
            document.getElementById("inventoryDisposeIds").innerHTML = "";
            document.getElementById("inventoryDisposeEquipmentId").value = equipmentId;
            document.getElementById("inventoryDisposeTitle").textContent = "Dispose equipment";
            fillInventoryDisposeEquipment([{
                name: equipmentName,
                tag: assetTag,
                category: category,
                qty: quantity,
            }]);
            setEqSelectValue("inventoryDisposeLocation", roomId ? String(roomId) : "");
            document.getElementById("inventoryDisposeReason").value = "";
            clearInventoryDisposeReasonError();
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

            document.getElementById("inventoryDisposeEquipmentId").value = "";
            document.getElementById("inventoryDisposeTitle").textContent = "Dispose " + boxes.length + (boxes.length === 1 ? " item" : " items");
            fillInventoryDisposeEquipment(boxes.map((box) => ({
                name: box.dataset.name,
                tag: box.dataset.tag,
                category: box.dataset.category,
                qty: box.dataset.qty,
            })));
            setEqSelectValue("inventoryDisposeLocation", "");
            document.getElementById("inventoryDisposeReason").value = "";
            clearInventoryDisposeReasonError();
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
            document.body.style.overflow = "hidden";

            if (window.lucide) {
                window.lucide.createIcons();
            }

            const reason = document.getElementById("inventoryDisposeReason");
            if (reason) {
                setTimeout(function () {
                    reason.focus();
                }, 40);
            }
        }

        function closeInventoryDisposeModal() {
            const modal = document.getElementById("inventoryDisposeModal");
            modal.classList.add("hidden");
            modal.classList.remove("flex");
            document.body.style.overflow = "";
        }

        function validateInventoryDisposeForm(event) {
            const reason = document.getElementById("inventoryDisposeReason");
            const reasonError = document.getElementById("inventoryDisposeReasonError");
            if (!reason) return true;

            const value = String(reason.value || "").trim();
            if (value !== "") {
                clearInventoryDisposeReasonError();
                return true;
            }

            event.preventDefault();
            reason.classList.add("border-rose-400", "ring-4", "ring-rose-100");
            if (reasonError) {
                reasonError.textContent = "Enter a reason for disposal.";
                reasonError.classList.remove("hidden");
            }
            reason.focus();
            return false;
        }

        document.addEventListener("keydown", function (event) {
            if (event.key !== "Escape") return;
            const modal = document.getElementById("inventoryDisposeModal");
            if (!modal || modal.classList.contains("hidden")) return;
            closeInventoryDisposeModal();
        });

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

            const toolsBadge = document.getElementById('inventoryToolsBadge');
            if (toolsBadge) {
                const pendingTransfers = {{ (int) ($pendingTransferCount ?? 0) }};
                const badgeTotal = selected.length > 0 ? selected.length : pendingTransfers;
                toolsBadge.textContent = String(badgeTotal);
                toolsBadge.classList.toggle('hidden', badgeTotal <= 0);
            }

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
                tag: input.dataset.tag || '',
                category: input.dataset.category || '',
                qty: input.dataset.qty || '1',
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

            <form
                action="{{ $equipmentActionBase }}/transfer-batch"
                method="POST"
                class="flex min-h-0 flex-1 flex-col"
                data-pur-confirm="{{ $transferNeedsApproval ? 'These items will be sent for the Administrator\'s approval. They stay where they are until approved.' : 'The selected items will be transferred to the destinations you chose.' }}"
                data-pur-confirm-title="{{ $transferNeedsApproval ? 'Send for approval?' : 'Save transfer?' }}"
                data-pur-confirm-ok="{{ $transferNeedsApproval ? 'Send for approval' : 'Save' }}"
                data-pur-confirm-close="1"
            >
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
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">{{ $transferNeedsApproval ? 'Send for approval' : 'Save' }}</button>
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

            <form
                action="{{ $equipmentActionBase }}/transfer-batch"
                method="POST"
                data-pur-confirm="{{ $transferNeedsApproval ? 'Every filtered storage item will be sent for the Administrator\'s approval. They stay where they are until approved.' : 'Every filtered storage item will be transferred to the destination you chose.' }}"
                data-pur-confirm-title="{{ $transferNeedsApproval ? 'Send for approval?' : 'Save transfer?' }}"
                data-pur-confirm-ok="{{ $transferNeedsApproval ? 'Send for approval' : 'Save' }}"
                data-pur-confirm-close="1"
            >
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
                    <button type="submit" class="h-10 rounded-xl bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">{{ $transferNeedsApproval ? 'Send for approval' : 'Save' }}</button>
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
    @endif

    @if ($canManage)
    <div
        id="stockMovementModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
        onclick="if (event.target === this) closeStockMovementModal()"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Stock movement</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        <span id="stockMovementName" class="font-medium text-slate-800"></span>
                        · On hand <span id="stockMovementOnHand">0</span>
                    </p>
                </div>
                <button type="button" onclick="closeStockMovementModal()" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <form action="{{ url('/admin/inventory/stock-movement') }}" method="POST">
                @csrf
                <input type="hidden" name="equipment_id" id="stockMovementEquipmentId" value="" />
                <div class="space-y-4 border-y border-slate-100 px-6 py-5">
                    <div>
                        <label for="stockMovementKind" class="mb-2 block text-sm font-medium text-slate-700">Type</label>
                        <select id="stockMovementKind" name="kind" onchange="syncStockMovementHint()" class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900">
                            <option value="issue">Issue</option>
                            <option value="spoilage">Spoilage / write-off</option>
                            <option value="adjustment">Count adjustment</option>
                        </select>
                        <p id="stockMovementHint" class="mt-1.5 text-[11px] leading-4 text-slate-500"></p>
                    </div>
                    <div>
                        <label for="stockMovementQuantity" id="stockMovementQtyLabel" class="mb-2 block text-sm font-medium text-slate-700">Quantity</label>
                        <input id="stockMovementQuantity" name="quantity" type="number" min="0" required class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm" />
                    </div>
                    <div>
                        <label for="stockMovementReason" class="mb-2 block text-sm font-medium text-slate-700">Reason <span class="text-red-500">*</span></label>
                        <textarea id="stockMovementReason" name="reason" rows="3" required maxlength="1000" class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm" placeholder="Why is this quantity changing?"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 px-6 py-4">
                    <button type="button" onclick="closeStockMovementModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="h-10 rounded-xl bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Post</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if ($canManage || $canEditStatus)
    <div
        id="inventoryDisposeModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
        onclick="if (event.target === this) closeInventoryDisposeModal()"
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
                        Only Defective or Damaged equipment can be sent to Equipment Disposal. It stays pending until you finalize it there, and it can be restored with the condition it had before.
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

            <form action="{{ $disposeAction }}" method="POST" novalidate onsubmit="return validateInventoryDisposeForm(event)">
                @csrf
                <input type="hidden" name="equipment_id" id="inventoryDisposeEquipmentId" value="" />
                <div id="inventoryDisposeIds"></div>

                <div class="border-y border-slate-100 px-6 py-5">
                    <div class="space-y-5">
                        <div>
                            <p class="mb-1 text-sm font-medium text-slate-700">Equipment</p>
                            <div id="inventoryDisposeEquipmentName" class="max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900"></div>
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
                                maxlength="1000"
                                placeholder="Why is this equipment being disposed?"
                                oninput="clearInventoryDisposeReasonError()"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            ></textarea>
                            <p id="inventoryDisposeReasonError" class="mt-1.5 hidden text-xs font-medium text-rose-600"></p>
                        </div>

                        <div>
                            <label for="inventoryDisposeLocation" class="mb-2 block text-sm font-medium text-slate-700">
                                Location <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="inventoryDisposeLocation"
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
                        {{ \App\Support\DisposalRequests::requiresApproval() ? 'Send for approval' : 'Dispose' }}
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

    <script>
        (function () {
            let activeMenu = null;
            let activeTrigger = null;
            let menuOpenedAt = 0;

            function closeEqMenus() {
                document.querySelectorAll('[data-eq-menu]').forEach(function (menu) {
                    menu.classList.add('hidden');
                });
                if (activeTrigger) {
                    activeTrigger.setAttribute('aria-expanded', 'false');
                }
                activeMenu = null;
                activeTrigger = null;
            }

            function positionEqMenu(trigger, menu) {
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
                if (left < margin) {
                    left = Math.max(margin, rect.left);
                }
                if (left + menuWidth > window.innerWidth - margin) {
                    left = Math.max(margin, window.innerWidth - menuWidth - margin);
                }

                const spaceBelow = window.innerHeight - rect.bottom - margin;
                let top = rect.bottom + gap;
                if (spaceBelow < menuHeight && rect.top - margin > menuHeight) {
                    top = rect.top - menuHeight - gap;
                }
                if (top < margin) {
                    top = margin;
                }

                menu.style.left = Math.round(left) + 'px';
                menu.style.top = Math.round(top) + 'px';
                menu.style.visibility = '';
            }

            window.closeEqMenus = closeEqMenus;

            window.toggleEqMenu = function (trigger, menuId, evt) {
                if (evt && typeof evt.stopPropagation === 'function') {
                    evt.stopPropagation();
                }

                const menu = document.getElementById(menuId);
                if (!menu || !trigger) return;

                const isSame = activeMenu === menu && !menu.classList.contains('hidden');
                closeEqMenus();
                if (isSame) return;

                positionEqMenu(trigger, menu);
                trigger.setAttribute('aria-expanded', 'true');
                activeMenu = menu;
                activeTrigger = trigger;
                menuOpenedAt = Date.now();

                if (window.lucide && menu.querySelector('i[data-lucide]')) {
                    window.lucide.createIcons();
                }
            };

            window.toggleEqSecondaryFilters = function () {
                const panel = document.getElementById('eqSecondaryFiltersPanel');
                const btn = document.getElementById('eqSecondaryFiltersBtn');
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
                closeEqMenus();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && activeMenu) {
                    closeEqMenus();
                }
            });

            window.addEventListener('scroll', function (event) {
                if (!activeMenu) return;
                if (Date.now() - menuOpenedAt < 100) return;
                if (event.target && activeMenu.contains(event.target)) return;
                closeEqMenus();
            }, true);

            window.addEventListener('resize', function () {
                if (activeMenu) closeEqMenus();
            });
        })();
    </script>

@endsection
