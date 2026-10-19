{{-- Admin design tokens (Inter/Outfit + STI blue) — matches Maintenance Personnel --}}
@include('layouts.partials.admin-design')
@include('layouts.partials.admin-grayscale-theme', ['keepColors' => true])

<div id="sidebar">

    {{-- ====================================== --}}
    {{-- HEADER --}}
    {{-- ====================================== --}}

    <div class="sidebar-header p-5">

        <div class="logo-icon">

            <img src="{{ asset('image/Paayo_Sidebar.png') }}" alt="">

        </div>

        <div>

            <h2>PaAyo</h2>

            <span>{{ \App\Support\RoleAccess::sidebarPortalLabel('admin') }}</span>

        </div>

    </div>

    <div class="sidebar-content">

        {{-- ====================================== --}}
        {{-- SEARCH --}}
        {{-- ====================================== --}}

        <!--<div class="sidebar-search">

            <div class="sidebar-dropdown">

                <div id="dropdownTrigger" class="dropdown-trigger">

                    <div class="flex items-center gap-2">

                        <i data-lucide="search"></i>

                        <span id="selectedSection">Search...</span>

                    </div>

                </div>

                <div id="dropdownMenu" class="dropdown-menu">

                    <div class="dropdown-item" data-target="dashboard-section">
                        Dashboard
                    </div>

                    <div class="dropdown-item" data-target="campus-section">
                        Campus / Location
                    </div>

                    <div class="dropdown-item" data-target="monitoring-section">
                        Equipment
                    </div>

                    <div class="dropdown-item" data-target="procurement-section">
                        Procurement
                    </div>

                    <div class="dropdown-item" data-target="users-section">
                        People
                    </div>

                    <div class="dropdown-item" data-target="settings-section">
                        Settings
                    </div>

                </div>

            </div>

        </div>-->

        {{-- ====================================== --}}
        {{-- DASHBOARD --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="dashboard-section">

            DASHBOARD

        </div>

        <a
            href="{{ route('admin.dashboard') }}"
            class="menu-item {{ request()->is('admin/dashboard') ? 'active' : '' }}"
        >

            <i data-lucide="layout-dashboard"></i>

            <span>Dashboard</span>

        </a>

        {{-- ====================================== --}}
        {{-- CAMPUS / LOCATION --}}
        {{-- ====================================== --}}

        @php
            $pendingTransferApprovals = \App\Support\TransferRequests::pendingCount();
            $pendingDisposalApprovals = \App\Support\DisposalRequests::pendingCount();

            $campusLinks = [
                [
                    'url' => route('admin.infrastructure.index'),
                    'label' => 'Rooms Layout',
                    'icon' => 'map',
                    'active' => request()->is('admin/infrastructure*'),
                ],
                [
                    'url' => route('admin.rooms.index'),
                    'label' => 'Rooms',
                    'icon' => 'door-open',
                    'active' => request()->is('admin/rooms*')
                        || (
                            request()->is('maintenance/reports/details/*')
                            && str_contains((string) request()->query('return'), '/admin/rooms')
                        ),
                ],
                [
                    'url' => route('admin.departments.index'),
                    'label' => 'Departments',
                    'icon' => 'network',
                    'active' => request()->is('admin/departments*'),
                ],
            ];
            $campusGroupActive = collect($campusLinks)->contains('active', true);

            $equipmentGroups = [
                [
                    'label' => 'Register & stock',
                    'icon' => 'package',
                    'links' => [
                        [
                            'url' => route('admin.equipment-all.index'),
                            'label' => 'All Equipment',
                            'icon' => 'layers',
                            'active' => request()->is('admin/equipment/all*', 'admin/operations/equipment*'),
                        ],
                        [
                            'url' => route('admin.inventory.index'),
                            'label' => 'Equipment Inventory',
                            'icon' => 'package',
                            'active' => request()->is('admin/inventory')
                                || (request()->is('admin/inventory/*') && ! request()->is('admin/inventory/transfer-requests*', 'admin/inventory/deployed*')),
                        ],
                        [
                            'url' => route('admin.inventory.deployed'),
                            'label' => 'Deployed Equipment',
                            'icon' => 'boxes',
                            'active' => request()->is('admin/inventory/deployed*'),
                        ],
                        [
                            'url' => route('admin.equipment-categories.index'),
                            'label' => 'Classification',
                            'icon' => 'tags',
                            'active' => request()->is('admin/equipment/categories*'),
                        ],
                        [
                            'url' => route('admin.qr-tools.index'),
                            'label' => 'Property Tagging',
                            'icon' => 'qr-code',
                            'active' => request()->is('admin/qr-tools*'),
                        ],
                        [
                            'url' => route('admin.computer-sets.index'),
                            'label' => 'Computer Sets',
                            'icon' => 'monitor',
                            'active' => request()->is('admin/computer-sets*'),
                        ],
                    ],
                ],
                [
                    'label' => 'Operations',
                    'icon' => 'wrench',
                    'links' => [
                        [
                            'url' => route('admin.schedules.index'),
                            'label' => 'Maintenance Schedule',
                            'icon' => 'calendar-days',
                            'active' => request()->is('admin/schedules*', 'admin/semester-inspections*', 'admin/operations/schedules*'),
                        ],
                        [
                            'url' => route('admin.property-assignments.index'),
                            'label' => 'Property Assignment',
                            'icon' => 'clipboard-signature',
                            'active' => request()->is('admin/property-assignments*', 'maintenance/property-assignments*'),
                        ],
                        [
                            'url' => route('admin.borrowing.index'),
                            'label' => 'Borrowing',
                            'icon' => 'hand-helping',
                            'active' => request()->is('admin/borrowing*', 'admin/operations/borrowing*'),
                        ],
                        [
                            'url' => route('admin.inventory.transfer-requests'),
                            'label' => 'Transfer Approvals',
                            'icon' => 'shield-check',
                            'active' => request()->is('admin/inventory/transfer-requests*'),
                            'badge' => $pendingTransferApprovals,
                        ],
                        [
                            'url' => route('admin.disposal.index'),
                            'label' => 'Equipment Disposal',
                            'icon' => 'trash-2',
                            'active' => request()->is('admin/disposal*'),
                            'badge' => $pendingDisposalApprovals,
                        ],
                        [
                            'url' => route('admin.suggested-issues.index'),
                            'label' => 'Suggested Issues',
                            'icon' => 'list-checks',
                            'active' => request()->is('admin/equipment/suggested-issues*'),
                        ],
                    ],
                ],
                [
                    'label' => 'Monitoring',
                    'icon' => 'radar',
                    'links' => [
                        [
                            'url' => route('admin.operations.building-layout'),
                            'label' => 'Campus Monitoring',
                            'icon' => 'building',
                            'active' => request()->is('admin/operations/building-layout*'),
                        ],
                        [
                            'url' => route('admin.operations.movements'),
                            'label' => 'Equipment Movements',
                            'icon' => 'arrow-left-right',
                            'active' => request()->is('admin/operations/movements*'),
                        ],
                        [
                            'url' => route('admin.equipment-history.index'),
                            'label' => 'Equipment History',
                            'icon' => 'history',
                            'active' => request()->is('admin/equipment/history*'),
                        ],
                        [
                            'url' => route('admin.equipment.replacements'),
                            'label' => 'Replacement Log',
                            'icon' => 'replace',
                            'active' => request()->is('admin/equipment/replacements*'),
                        ],
                    ],
                ],
            ];

            $reportsSectionActive = request()->is('admin/reports*');
            $reportLinks = [
                [
                    'url' => url('/admin/reports/maintenance-history'),
                    'label' => 'Maintenance',
                    'icon' => 'wrench',
                    'active' => request()->is('admin/reports/maintenance-history*'),
                ],
                [
                    'url' => url('/admin/reports/receiving'),
                    'label' => 'Receiving',
                    'icon' => 'package-check',
                    'active' => request()->is('admin/reports/receiving*'),
                ],
                [
                    'url' => url('/admin/reports/approval-logs'),
                    'label' => 'Approvals',
                    'icon' => 'stamp',
                    'active' => request()->is('admin/reports/approval-logs*', 'admin/reports/audit-logs*'),
                ],
                [
                    'url' => url('/admin/reports/user-login-logs'),
                    'label' => 'User access',
                    'icon' => 'shield',
                    'active' => request()->is('admin/reports/user-login-logs*'),
                ],
            ];

            $boGroupActive = request()->is('admin/operations/back-orders*');
            $activeBoPayment = $boGroupActive ? request('payment') : null;
            $boLinks = [
                ['payment' => null, 'label' => 'All Back Orders', 'icon' => 'layers'],
                ['payment' => 'rfc', 'label' => 'Request for Check', 'icon' => 'clipboard-check'],
                ['payment' => 'ca', 'label' => 'Cash Advance', 'icon' => 'banknote'],
            ];
        @endphp

        <div class="menu-title" id="campus-section">

            CAMPUS

        </div>

        <div class="menu-group {{ $campusGroupActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $campusGroupActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $campusGroupActive ? 'true' : 'false' }}"
            >
                <i data-lucide="building-2"></i>
                <span>Location</span>
                <i data-lucide="chevron-down" class="menu-group-chevron"></i>
            </button>
            <div class="menu-sub" @if (! $campusGroupActive) hidden @endif>
                @foreach ($campusLinks as $link)
                    <a href="{{ $link['url'] }}" class="menu-sub-item {{ $link['active'] ? 'active' : '' }}">
                        <i data-lucide="{{ $link['icon'] }}"></i>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="menu-title" id="monitoring-section">

            EQUIPMENT

        </div>

        @foreach ($equipmentGroups as $group)
            @php
                $groupActive = collect($group['links'])->contains('active', true);
                $groupBadge = collect($group['links'])->sum(fn ($link) => $link['badge'] ?? 0);
            @endphp
            <div class="menu-group {{ $groupActive ? 'is-open' : '' }}" data-menu-group>
                <button
                    type="button"
                    class="menu-item menu-group-toggle {{ $groupActive ? 'active-parent' : '' }}"
                    data-menu-group-toggle
                    aria-expanded="{{ $groupActive ? 'true' : 'false' }}"
                >
                    <i data-lucide="{{ $group['icon'] }}"></i>
                    <span>{{ $group['label'] }}</span>
                    @if ($groupBadge > 0)
                        <span class="ml-auto rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white" title="{{ $groupBadge }} waiting for approval">{{ $groupBadge }}</span>
                    @endif
                    <i data-lucide="chevron-down" class="menu-group-chevron" @if ($groupBadge > 0) style="margin-left: 8px" @endif></i>
                </button>
                <div class="menu-sub" @if (! $groupActive) hidden @endif>
                    @foreach ($group['links'] as $link)
                        <a href="{{ $link['url'] }}" class="menu-sub-item {{ $link['active'] ? 'active' : '' }}">
                            <i data-lucide="{{ $link['icon'] }}"></i>
                            <span>{{ $link['label'] }}</span>
                            @if (($link['badge'] ?? 0) > 0)
                                <span class="ml-auto rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">{{ $link['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- ====================================== --}}
        {{-- PROCUREMENT --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="procurement-section">

            PROCUREMENT

        </div>

        <a
            href="{{ route('admin.operations.procurement') }}"
            class="menu-item {{ request()->is('admin/operations/procurement*', 'admin/operations/documents*') ? 'active' : '' }}"
        >
            <i data-lucide="git-branch"></i>
            <span>Monitor Procurement</span>
        </a>

        <div class="menu-group {{ $boGroupActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $boGroupActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $boGroupActive ? 'true' : 'false' }}"
            >
                <i data-lucide="package-x"></i>
                <span>Back Order Monitor</span>
                <i data-lucide="chevron-down" class="menu-group-chevron"></i>
            </button>
            <div class="menu-sub" @if(! $boGroupActive) hidden @endif>
                @foreach($boLinks as $boLink)
                    <a
                        href="{{ route('admin.back-orders.index', array_filter(['payment' => $boLink['payment']])) }}"
                        class="menu-sub-item {{ $boGroupActive && $activeBoPayment === $boLink['payment'] ? 'active' : '' }}"
                    >
                        <i data-lucide="{{ $boLink['icon'] }}"></i>
                        <span>{{ $boLink['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <a
            href="{{ route('purchaser.history.index') }}"
            class="menu-item {{ request()->is('purchaser/history*') ? 'active' : '' }}"
        >
            <i data-lucide="history"></i>
            <span>Purchase History</span>
        </a>

        {{-- ====================================== --}}
        {{-- PEOPLE --}}
        {{-- ====================================== --}}

        @php
            $peopleLinks = [
                [
                    'url' => route('admin.persons.index'),
                    'label' => 'Faculty & Staff',
                    'icon' => 'users',
                    'active' => request()->is('admin/persons*'),
                ],
                [
                    'url' => route('admin.reporters.index'),
                    'label' => 'Reporters Directory',
                    'icon' => 'contact',
                    'active' => request()->is('admin/reporters*'),
                ],
                [
                    'url' => route('admin.users'),
                    'label' => 'System Users',
                    'icon' => 'user-cog',
                    'active' => request()->is('admin/users*'),
                ],
            ];
            $peopleGroupActive = collect($peopleLinks)->contains('active', true);
        @endphp

        <div class="menu-title" id="users-section">

            PERSONNEL

        </div>

        <div class="menu-group {{ $peopleGroupActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $peopleGroupActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $peopleGroupActive ? 'true' : 'false' }}"
            >
                <i data-lucide="users"></i>
                <span>User Management</span>
                <i data-lucide="chevron-down" class="menu-group-chevron"></i>
            </button>
            <div class="menu-sub" @if (! $peopleGroupActive) hidden @endif>
                @foreach ($peopleLinks as $link)
                    <a href="{{ $link['url'] }}" class="menu-sub-item {{ $link['active'] ? 'active' : '' }}">
                        <i data-lucide="{{ $link['icon'] }}"></i>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ====================================== --}}
        {{-- SETTINGS --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="settings-section">

            SETTINGS

        </div>

        <div class="menu-group {{ $reportsSectionActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $reportsSectionActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $reportsSectionActive ? 'true' : 'false' }}"
            >
                <i data-lucide="file-text"></i>
                <span>System Reports</span>
                <i data-lucide="chevron-down" class="menu-group-chevron"></i>
            </button>
            <div class="menu-sub" @if(! $reportsSectionActive) hidden @endif>
                @foreach($reportLinks as $reportLink)
                    <a
                        href="{{ $reportLink['url'] }}"
                        class="menu-sub-item {{ $reportLink['active'] ? 'active' : '' }}"
                    >
                        <i data-lucide="{{ $reportLink['icon'] }}"></i>
                        <span>{{ $reportLink['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <!--<a
            href="{{ route('admin.profile') }}"
            class="menu-item {{ request()->is('admin/profile*') || request()->is('admin/security*') ? 'active' : '' }}"
        >

            <i data-lucide="user-cog"></i>

            <span>Account Settings</span>

        </a>-->

        <a
            href="{{ url('/admin/settings/campus-setup-pin') }}"
            class="menu-item {{ request()->is('admin/settings*') ? 'active' : '' }}"
        >

            <i data-lucide="settings"></i>

            <span>Campus Setup</span>

        </a>

    </div>

</div>

@include('layouts.partials.admin-sidebar-assets')
