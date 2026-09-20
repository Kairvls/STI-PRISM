@extends('layouts.admin-layout')

@section('title', 'User Management')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
    <style>
        /* User modals: border-only inputs (no grey fill) */
        #createUserModal .pur-input,
        #createUserModal .pur-select,
        #editRolesModal .pur-input,
        #editRolesModal .pur-select,
        #createUserModal .iti__tel-input,
        #createUserModal .iti--separate-dial-code .iti__selected-flag {
            background: #fff !important;
        }
        #createUserModal .iti__tel-input:focus,
        #createUserModal .pur-input:focus,
        #createUserModal .pur-select:focus,
        #editRolesModal .pur-input:focus,
        #editRolesModal .pur-select:focus {
            background: #fff !important;
        }
    </style>
@endpush

@section('content')

<div class="admin-page space-y-6">

    @if(session('success'))
        <div class="pur-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="pur-alert-error">{{ session('error') }}</div>
    @endif

    @include('layouts.partials.maintenance-stat-cards', [
        'cards' => [
            [
                'label' => 'Total users',
                'value' => number_format($totalUsers),
            ],
            [
                'label' => 'Recently active',
                'value' => number_format($activeUsers),
            ],
            [
                'label' => 'Roles in use',
                'value' => number_format($roleCount),
            ],
        ],
    ])

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">User accounts</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $totalUsers }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Create accounts and assign primary and additional roles.</p>
                </div>

                <button type="button" onclick="openCreateUserModal()" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-4 text-[13px] font-medium text-white transition hover:bg-[#001fa8]">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Create Account
                </button>
            </div>

            <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="relative w-full lg:max-w-sm">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                    <input
                        type="text"
                        id="userSearchInput"
                        placeholder="Search by name, employee ID, or role…"
                        class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white"
                    >
                </div>

                <div
                    id="userFilterSlider"
                    class="relative inline-flex max-w-full items-center overflow-x-auto rounded-lg bg-slate-100 p-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <span
                        class="user-filter-thumb pointer-events-none absolute top-1 left-0 z-0 h-8 rounded-md bg-white shadow-sm will-change-transform"
                        style="transform: translate3d(0, 0, 0); transition: transform 220ms cubic-bezier(0.22, 1, 0.36, 1), width 220ms cubic-bezier(0.22, 1, 0.36, 1);"
                        aria-hidden="true"
                    ></span>
                    <button type="button" data-filter="all" class="user-filter-btn relative z-10 flex h-8 shrink-0 items-center rounded-md px-3.5 text-xs font-semibold text-slate-950">All</button>
                    <button type="button" data-filter="active" class="user-filter-btn relative z-10 flex h-8 shrink-0 items-center rounded-md px-3.5 text-xs font-semibold text-slate-500 hover:text-slate-900">Active</button>
                    <button type="button" data-filter="inactive" class="user-filter-btn relative z-10 flex h-8 shrink-0 items-center rounded-md px-3.5 text-xs font-semibold text-slate-500 hover:text-slate-900">Inactive</button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="pur-table w-full min-w-[980px] text-left">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Primary role</th>
                        <th>Additional roles</th>
                        <th>Procurement</th>
                        <th>Status</th>
                        <th>Last active</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    @forelse($users as $user)
                        @php
                            $isActive = !empty($user->last_active_at)
                                && \Carbon\Carbon::parse($user->last_active_at)->gte(now()->subDays(30));
                            $roleMeta = $extraRolesByUser[$user->user_id] ?? ['extra' => collect(), 'all_ids' => [(int) $user->user_role_id], 'extra_names' => []];
                            $allRoleIds = $roleMeta['all_ids'] ?? [(int) $user->user_role_id];
                            $isMaintenance = in_array(2, $allRoleIds, true);
                            $isPurchaser = in_array(3, $allRoleIds, true);
                            $isAdminUser = in_array(1, $allRoleIds, true) || (int) $user->user_role_id === 1;
                            $canProcurement = (bool) ($user->user_can_procurement ?? false) || $isPurchaser;
                            $extraNames = $roleMeta['extra_names'] ?? [];
                            $extraLabel = count($extraNames) ? implode(', ', $extraNames) : '—';
                        @endphp
                        <tr class="user-row transition hover:bg-gray-50/70" data-account-status="{{ $isActive ? 'active' : 'inactive' }}">
                            <td class="text-sm font-semibold text-gray-900">{{ $user->user_employee_id ?: '-' }}</td>
                            <td class="text-sm text-gray-700">{{ $user->user_full_name }}</td>
                            <td class="text-sm text-gray-600">{{ $user->user_username }}</td>
                            <td>
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $user->role_name ?: '-' }}</span>
                            </td>
                            <td>
                                @if(count($extraNames))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($extraNames as $extraName)
                                            <span class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">{{ $extraName }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td>
                                @if($isPurchaser && (int) $user->user_role_id === 3)
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Always on</span>
                                @elseif($isPurchaser)
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Enabled</span>
                                @elseif($isMaintenance || $isAdminUser)
                                    <form method="POST" action="{{ route('admin.users.procurement-access', $user->user_id) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="user_can_procurement" value="{{ $canProcurement ? 0 : 1 }}">
                                        <button type="submit"
                                            class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium transition {{ $canProcurement ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100' }}"
                                            title="{{ $canProcurement ? 'Click to disable procurement' : 'Click to enable Purchaser portal (Decision A)' }}">
                                            {{ $canProcurement ? 'Enabled' : 'Disabled' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td>
                                @if($isActive)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-sm text-gray-500">
                                @if(!empty($user->last_active_at))
                                    {{ \Carbon\Carbon::parse($user->last_active_at)->diffForHumans() }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="inline-flex items-center justify-center gap-1">
                                    <button type="button"
                                        onclick="openEditRolesModal(this)"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-800"
                                        title="Edit roles"
                                        data-user-id="{{ $user->user_id }}"
                                        data-full-name="{{ $user->user_full_name }}"
                                        data-primary-role="{{ (int) $user->user_role_id }}"
                                        data-role-ids="{{ implode(',', $allRoleIds) }}"
                                        data-can-procurement="{{ $canProcurement ? '1' : '0' }}"
                                        data-is-admin="{{ $isAdminUser ? '1' : '0' }}"
                                    >
                                        <i data-lucide="shield" class="h-4 w-4 pointer-events-none"></i>
                                    </button>
                                    <button type="button"
                                        onclick="openViewUserModal(this)"
                                        class="view-user-btn inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-800"
                                        title="View user details"
                                        data-employee-id="{{ $user->user_employee_id ?: '-' }}"
                                        data-full-name="{{ $user->user_full_name }}"
                                        data-role="{{ $user->role_name ?: '-' }}"
                                        data-extra-roles="{{ $extraLabel }}"
                                        data-username="{{ $user->user_username }}"
                                        data-email="{{ $user->user_email_address ?: '-' }}"
                                        data-contact="{{ $user->user_contact_number ?: '-' }}"
                                        data-status="{{ $isActive ? 'Active' : 'Inactive' }}"
                                        data-procurement="{{ $isPurchaser && (int) $user->user_role_id === 3 ? 'Always on' : ($isPurchaser ? 'Enabled' : (($isMaintenance || $isAdminUser) ? ($canProcurement ? 'Enabled' : 'Disabled') : '—')) }}"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4 pointer-events-none"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="pur-empty">No user accounts found.</td></tr>
                    @endforelse
                    <tr id="usersEmptyFilterRow" class="hidden">
                        <td colspan="9" class="pur-empty">No accounts match this filter.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="usersPager" class="print-hidden flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-slate-500">
                Showing
                <span id="usersShowingFrom" class="font-semibold text-slate-700">0</span>
                to
                <span id="usersShowingTo" class="font-semibold text-slate-700">0</span>
                of
                <span id="usersVisibleCount" class="font-semibold text-slate-700">{{ $totalUsers }}</span>
                users
            </p>
            <div
                id="usersPageControls"
                class="page-carousel inline-flex items-center overflow-hidden rounded-lg bg-slate-800 text-white shadow-sm"
                style="display:none"
                data-page-carousel-client
            >
                <button
                    type="button"
                    id="usersCarouselPrev"
                    class="flex h-10 w-10 shrink-0 items-center justify-center text-white transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-30"
                    aria-label="Previous page numbers"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div id="usersCarouselViewport" class="overflow-hidden" style="width: 12.5rem">
                    <div id="usersCarouselTrack" class="flex transition-transform duration-300 ease-out"></div>
                </div>
                <button
                    type="button"
                    id="usersCarouselNext"
                    class="flex h-10 w-10 shrink-0 items-center justify-center text-white transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-30"
                    aria-label="Next page numbers"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

</div>

<div id="viewUserModal" class="fixed inset-0 z-[12000] hidden">
    <div class="pur-modal !z-[12000]" onclick="closeViewUserModal()">
        <div class="pur-modal-panel max-w-lg" onclick="event.stopPropagation()">
            <div class="pur-modal-header">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3>User Details</h3>
                        <p class="mt-0.5 text-sm font-normal text-gray-500">Account information</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-900" onclick="closeViewUserModal()" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="pur-modal-body space-y-1 text-sm">
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2.5"><span class="text-gray-500">Employee ID</span><span id="viewUserEmployeeId" class="font-semibold text-gray-950"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2.5"><span class="text-gray-500">Full Name</span><span id="viewUserFullName" class="font-semibold text-gray-950"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2.5"><span class="text-gray-500">Username</span><span id="viewUserUsername" class="font-semibold text-gray-950"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2.5"><span class="text-gray-500">Primary role</span><span id="viewUserRole" class="font-semibold text-gray-950"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2.5"><span class="text-gray-500">Additional roles</span><span id="viewUserExtraRoles" class="text-right font-semibold text-gray-950"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2.5"><span class="text-gray-500">Procurement</span><span id="viewUserProcurement" class="font-semibold text-gray-950"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2.5"><span class="text-gray-500">Status</span><span id="viewUserStatus" class="font-semibold text-gray-950"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2.5"><span class="text-gray-500">Email</span><span id="viewUserEmail" class="font-semibold text-gray-950"></span></div>
                <div class="flex justify-between gap-4 py-2.5"><span class="text-gray-500">Contact</span><span id="viewUserContact" class="font-semibold text-gray-950"></span></div>
            </div>
            <div class="pur-modal-footer">
                <button type="button" class="pur-btn-secondary" onclick="closeViewUserModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<div id="createUserModal" class="fixed inset-0 z-[12000] hidden">
    <div class="pur-modal !z-[12000]" onclick="closeCreateUserModal()">
        <div class="pur-modal-panel max-w-lg" onclick="event.stopPropagation()">
            <div class="pur-modal-header">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3>Create User Account</h3>
                        <p class="mt-0.5 text-sm font-normal text-gray-500">Add a new user to the system</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-900" onclick="closeCreateUserModal()" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <form method="POST" action="/admin/users/store">
                @csrf
                <div class="pur-modal-body space-y-4 overflow-y-auto" style="max-height: calc(100vh - 220px);">
                    <div>
                        <label class="pur-label">Employee ID</label>
                        <input type="text" name="employee_id" class="pur-input mt-1.5" required />
                    </div>
                    <div>
                        <label class="pur-label">Full Name</label>
                        <input type="text" name="full_name" class="pur-input mt-1.5" required />
                    </div>
                    <div>
                        <label class="pur-label">Username</label>
                        <input type="text" name="username" class="pur-input mt-1.5" required />
                    </div>
                    <div>
                        <label class="pur-label">Email</label>
                        <input type="email" name="email" class="pur-input mt-1.5" required />
                    </div>
                    <div>
                        <label class="pur-label">Contact Number</label>
                        @include('partials.phone-input', [
                            'name' => 'contact_number',
                            'value' => old('contact_number'),
                            'id' => 'admin-index-user-contact-number',
                            'inputClass' => 'mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100',
                        ])
                    </div>
                    <div>
                        <label class="pur-label">Password</label>
                        <input type="password" name="password" class="pur-input mt-1.5" required />
                    </div>
                    <div>
                        <label class="pur-label">Primary role <span class="font-normal text-gray-400">(used for Office 365 login)</span></label>
                        <select name="primary_role" id="createUserRole" class="pur-select mt-1.5" required>
                            <option value="">Select primary role...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->role_id }}">{{ $role->role_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="pur-label">Additional roles <span class="font-normal text-gray-400">(optional)</span></label>
                        <div class="mt-1.5 space-y-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-3">
                            @foreach($roles as $role)
                                @if((int) $role->role_id !== 1)
                                    <label class="create-additional-role-row flex items-center gap-2 text-sm text-gray-700" data-role-id="{{ $role->role_id }}">
                                        <input type="checkbox" name="additional_roles[]" value="{{ $role->role_id }}" class="create-additional-role h-4 w-4 rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]">
                                        <span>{{ $role->role_name }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500">Primary role is always included. Extra roles unlock those portals without changing login destination.</p>
                    </div>
                    <div id="createProcurementAccessWrap" class="hidden rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" name="user_can_procurement" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]">
                            <span>
                                <span class="block text-sm font-semibold text-gray-950">Enable procurement workflow</span>
                                <span class="mt-0.5 block text-xs leading-relaxed text-gray-500">Assigns Purchaser access. Use the portal switcher to create and drive RIS → ATP → RFC/CA → RR → Liquidation. Administrator portal stays accept/sign + monitor only.</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="pur-modal-footer">
                    <button type="button" class="pur-btn-secondary" onclick="closeCreateUserModal()">Cancel</button>
                    <button type="submit" class="pur-btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editRolesModal" class="fixed inset-0 z-[12000] hidden">
    <div class="pur-modal !z-[12000]" onclick="closeEditRolesModal()">
        <div class="pur-modal-panel max-w-lg" onclick="event.stopPropagation()">
            <div class="pur-modal-header">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3>Edit Roles</h3>
                        <p class="mt-0.5 text-sm font-normal text-gray-500" id="editRolesSubtitle">Update primary and additional roles</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-900" onclick="closeEditRolesModal()" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <form method="POST" id="editRolesForm">
                @csrf
                <div class="pur-modal-body space-y-4 overflow-y-auto" style="max-height: calc(100vh - 220px);">
                    <div>
                        <label class="pur-label">Primary role</label>
                        <select name="primary_role" id="editPrimaryRole" class="pur-select mt-1.5" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->role_id }}" class="edit-primary-role-option" data-role-id="{{ $role->role_id }}">{{ $role->role_name }}</option>
                            @endforeach
                        </select>
                        <p id="editAdminPrimaryHint" class="mt-1.5 hidden text-xs text-gray-500">Administrator primary role is locked. Add Purchaser below or enable procurement workflow to perform docs via portal switch.</p>
                    </div>
                    <div>
                        <label class="pur-label">Additional roles</label>
                        <div class="mt-1.5 space-y-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-3">
                            @foreach($roles as $role)
                                @if((int) $role->role_id !== 1)
                                    <label class="edit-additional-role-row flex items-center gap-2 text-sm text-gray-700" data-role-id="{{ $role->role_id }}">
                                        <input type="checkbox" name="additional_roles[]" value="{{ $role->role_id }}" class="edit-additional-role h-4 w-4 rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]">
                                        <span>{{ $role->role_name }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div id="editProcurementAccessWrap" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" name="user_can_procurement" value="1" id="editCanProcurement" class="mt-1 h-4 w-4 rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]">
                            <span>
                                <span class="block text-sm font-semibold text-gray-950">Enable procurement workflow</span>
                                <span class="mt-0.5 block text-xs leading-relaxed text-gray-500">Grants Purchaser access. Open Purchaser via the portal switcher to create RIS → ATP → RFC/CA → RR → Liquidation. Accounting/Receiving approvals stay with those portals.</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="pur-modal-footer">
                    <button type="button" class="pur-btn-secondary" onclick="closeEditRolesModal()">Cancel</button>
                    <button type="submit" class="pur-btn-primary">Save Roles</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function mountUserModal(modal) {
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        return modal;
    }

    window.closeViewUserModal = function() {
        var modal = document.getElementById('viewUserModal');
        if (modal) modal.classList.add('hidden');
    };

    window.openViewUserModal = function(btn) {
        if (!btn) return;
        var setText = function(id, value) {
            var el = document.getElementById(id);
            if (el) el.textContent = value || '-';
        };
        setText('viewUserEmployeeId', btn.getAttribute('data-employee-id'));
        setText('viewUserFullName', btn.getAttribute('data-full-name'));
        setText('viewUserUsername', btn.getAttribute('data-username'));
        setText('viewUserRole', btn.getAttribute('data-role'));
        setText('viewUserExtraRoles', btn.getAttribute('data-extra-roles'));
        setText('viewUserProcurement', btn.getAttribute('data-procurement'));
        setText('viewUserStatus', btn.getAttribute('data-status'));
        setText('viewUserEmail', btn.getAttribute('data-email'));
        setText('viewUserContact', btn.getAttribute('data-contact'));
        var modal = mountUserModal(document.getElementById('viewUserModal'));
        if (modal) modal.classList.remove('hidden');
    };

    window.openCreateUserModal = function() {
        var modal = mountUserModal(document.getElementById('createUserModal'));
        if (modal) modal.classList.remove('hidden');
        syncCreateAdditionalRolesVisibility();
        syncCreateProcurementAccess();
        // Phone widget must init while visible so the country dropdown positions correctly.
        var phone = modal && modal.querySelector('[data-phone-input]');
        if (phone && typeof window.refreshPrismPhoneInput === 'function') {
            requestAnimationFrame(function () {
                window.refreshPrismPhoneInput(phone);
            });
        }
    };

    window.closeCreateUserModal = function() {
        if (typeof window.closeAllPrismPhoneDropdowns === 'function') {
            window.closeAllPrismPhoneDropdowns();
        }
        var modal = document.getElementById('createUserModal');
        if (modal) modal.classList.add('hidden');
    };

    window.closeEditRolesModal = function() {
        var modal = document.getElementById('editRolesModal');
        if (modal) modal.classList.add('hidden');
    };

    window.openEditRolesModal = function(btn) {
        if (!btn) return;
        var userId = btn.getAttribute('data-user-id');
        var form = document.getElementById('editRolesForm');
        if (form) form.action = '/admin/users/' + userId + '/roles';
        var subtitle = document.getElementById('editRolesSubtitle');
        if (subtitle) subtitle.textContent = 'Roles for ' + (btn.getAttribute('data-full-name') || 'user');
        var isAdminTarget = btn.getAttribute('data-is-admin') === '1';
        var primary = document.getElementById('editPrimaryRole');
        if (primary) {
            primary.value = btn.getAttribute('data-primary-role') || '';
            primary.disabled = isAdminTarget;
            // Disabled fields are not submitted — mirror primary for Admin edits.
            var locked = document.getElementById('editPrimaryRoleLocked');
            if (!locked && form) {
                locked = document.createElement('input');
                locked.type = 'hidden';
                locked.name = 'primary_role';
                locked.id = 'editPrimaryRoleLocked';
                form.appendChild(locked);
            }
            if (locked) {
                if (isAdminTarget) {
                    locked.value = '1';
                    locked.disabled = false;
                } else {
                    locked.value = '';
                    locked.disabled = true;
                }
            }
        }
        var hint = document.getElementById('editAdminPrimaryHint');
        if (hint) hint.classList.toggle('hidden', !isAdminTarget);
        var ids = (btn.getAttribute('data-role-ids') || '').split(',').filter(Boolean);
        document.querySelectorAll('.edit-additional-role').forEach(function (cb) {
            var roleId = cb.value;
            cb.checked = ids.indexOf(roleId) !== -1 && roleId !== (primary ? primary.value : '');
        });
        var canProc = document.getElementById('editCanProcurement');
        if (canProc) canProc.checked = btn.getAttribute('data-can-procurement') === '1';
        syncEditAdditionalRolesVisibility();
        syncEditProcurementAccess();
        var modal = mountUserModal(document.getElementById('editRolesModal'));
        if (modal) modal.classList.remove('hidden');
    };

    function syncAdditionalRolesVisibility(primarySelectId, rowSelector, checkboxSelector) {
        var primarySelect = document.getElementById(primarySelectId);
        var primaryId = primarySelect ? String(primarySelect.value || '') : '';
        document.querySelectorAll(rowSelector).forEach(function (row) {
            var roleId = String(row.getAttribute('data-role-id') || '');
            var isPrimary = primaryId !== '' && roleId === primaryId;
            row.classList.toggle('hidden', isPrimary);
            if (isPrimary) {
                var cb = row.querySelector(checkboxSelector);
                if (cb) cb.checked = false;
            }
        });
    }

    function syncCreateAdditionalRolesVisibility() {
        syncAdditionalRolesVisibility('createUserRole', '.create-additional-role-row', '.create-additional-role');
    }

    function syncEditAdditionalRolesVisibility() {
        syncAdditionalRolesVisibility('editPrimaryRole', '.edit-additional-role-row', '.edit-additional-role');
    }

    function hasProcurementEligibleSelected(primarySelect, additionalSelector) {
        var primary = primarySelect ? primarySelect.value : '';
        // Administrator (1) or Maintenance (2) — Decision A + existing Maintenance path
        if (primary === '1' || primary === '2') return true;
        var found = false;
        document.querySelectorAll(additionalSelector).forEach(function (cb) {
            if (cb.checked && (cb.value === '1' || cb.value === '2') && !cb.closest('.hidden')) found = true;
        });
        return found;
    }

    function syncCreateProcurementAccess() {
        var roleSelect = document.getElementById('createUserRole');
        var wrap = document.getElementById('createProcurementAccessWrap');
        if (!roleSelect || !wrap) return;
        wrap.classList.toggle('hidden', !hasProcurementEligibleSelected(roleSelect, '.create-additional-role'));
    }

    function syncEditProcurementAccess() {
        var roleSelect = document.getElementById('editPrimaryRole');
        var wrap = document.getElementById('editProcurementAccessWrap');
        if (!roleSelect || !wrap) return;
        wrap.classList.toggle('hidden', !hasProcurementEligibleSelected(roleSelect, '.edit-additional-role'));
    }

    function updateUserFilterSlider(activeFilter, animate) {
        var track = document.getElementById('userFilterSlider');
        if (!track) return;
        var thumb = track.querySelector('.user-filter-thumb');
        var buttons = track.querySelectorAll('.user-filter-btn');
        if (!thumb || !buttons.length) return;

        var activeBtn = null;
        for (var i = 0; i < buttons.length; i++) {
            var isActive = buttons[i].getAttribute('data-filter') === activeFilter;
            buttons[i].style.color = isActive ? '#020617' : '#64748b';
            if (isActive) activeBtn = buttons[i];
        }
        if (!activeBtn) activeBtn = buttons[0];

        var x = activeBtn.offsetLeft;
        var w = activeBtn.offsetWidth;
        if (!animate) {
            var previous = thumb.style.transition;
            thumb.style.transition = 'none';
            thumb.style.width = w + 'px';
            thumb.style.transform = 'translate3d(' + x + 'px, 0, 0)';
            void thumb.offsetWidth;
            thumb.style.transition = previous || 'transform 220ms cubic-bezier(0.22, 1, 0.36, 1), width 220ms cubic-bezier(0.22, 1, 0.36, 1)';
            return;
        }
        thumb.style.width = w + 'px';
        thumb.style.transform = 'translate3d(' + x + 'px, 0, 0)';
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        closeViewUserModal();
        closeCreateUserModal();
        closeEditRolesModal();
    });

    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('userSearchInput');
        var visibleCount = document.getElementById('usersVisibleCount');
        var emptyRow = document.getElementById('usersEmptyFilterRow');
        var rows = document.querySelectorAll('#usersTableBody tr.user-row');
        var currentFilter = 'all';
        var pageSize = 10;
        var currentPage = 1;
        var carouselIndex = 0;
        var showingFrom = document.getElementById('usersShowingFrom');
        var showingTo = document.getElementById('usersShowingTo');
        var pageControls = document.getElementById('usersPageControls');
        var track = document.getElementById('usersCarouselTrack');
        var viewport = document.getElementById('usersCarouselViewport');
        var carouselPrev = document.getElementById('usersCarouselPrev');
        var carouselNext = document.getElementById('usersCarouselNext');
        var ITEM_WIDTH = 40;
        var VISIBLE = 5;

        function renderCarouselWindow(pageCount) {
            if (!track || !viewport || !carouselPrev || !carouselNext) return;
            var visible = Math.min(VISIBLE, Math.max(1, pageCount));
            var maxIndex = Math.max(0, pageCount - visible);
            carouselIndex = Math.min(maxIndex, Math.max(0, currentPage - Math.ceil(visible / 2)));
            viewport.style.width = (visible * 2.5) + 'rem';
            track.style.transform = 'translateX(' + (-carouselIndex * ITEM_WIDTH) + 'px)';
            carouselPrev.disabled = carouselIndex <= 0;
            carouselNext.disabled = carouselIndex >= maxIndex;
        }

        function rebuildPageButtons(pageCount) {
            if (!track) return;
            track.innerHTML = '';
            for (var page = 1; page <= pageCount; page++) {
                var isCurrent = page === currentPage;
                var el = document.createElement(isCurrent ? 'span' : 'button');
                el.textContent = String(page);
                el.setAttribute('data-page', String(page));
                el.className = isCurrent
                    ? 'flex h-10 w-10 shrink-0 items-center justify-center bg-blue-500/40 text-sm font-medium text-white'
                    : 'flex h-10 w-10 shrink-0 items-center justify-center text-sm font-medium text-white/90 transition hover:bg-white/10';
                if (isCurrent) {
                    el.setAttribute('aria-current', 'page');
                } else {
                    el.type = 'button';
                    el.addEventListener('click', function () {
                        currentPage = Number(this.getAttribute('data-page')) || 1;
                        applyUserFilters();
                    });
                }
                track.appendChild(el);
            }
            renderCarouselWindow(pageCount);
        }

        function applyUserFilters() {
            var query = (searchInput ? searchInput.value : '').toLowerCase().trim();
            var matched = [];

            rows.forEach(function (row) {
                var matchesSearch = !query || row.textContent.toLowerCase().includes(query);
                var status = row.getAttribute('data-account-status') || 'inactive';
                var matchesStatus = currentFilter === 'all' || status === currentFilter;
                row.style.display = 'none';
                if (matchesSearch && matchesStatus) matched.push(row);
            });

            var shown = matched.length;
            var pageCount = Math.max(1, Math.ceil(shown / pageSize));
            if (currentPage > pageCount) currentPage = pageCount;
            var start = (currentPage - 1) * pageSize;
            var end = Math.min(start + pageSize, shown);
            matched.slice(start, end).forEach(function (row) { row.style.display = ''; });

            if (visibleCount) visibleCount.textContent = String(shown);
            if (showingFrom) showingFrom.textContent = String(shown ? start + 1 : 0);
            if (showingTo) showingTo.textContent = String(shown ? end : 0);
            if (pageControls) pageControls.style.display = shown > pageSize ? 'inline-flex' : 'none';
            if (shown > pageSize) rebuildPageButtons(pageCount);
            if (emptyRow) emptyRow.classList.toggle('hidden', shown > 0 || rows.length === 0);
        }

        document.querySelectorAll('.user-filter-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var filter = this.getAttribute('data-filter');
                if (filter === currentFilter) return;
                currentFilter = filter;
                updateUserFilterSlider(currentFilter, true);
                currentPage = 1;
                applyUserFilters();
            });
        });

        if (searchInput) searchInput.addEventListener('input', function () {
            currentPage = 1;
            applyUserFilters();
        });
        if (carouselPrev) carouselPrev.addEventListener('click', function () {
            var pageCount = Math.max(1, Math.ceil((Number(visibleCount && visibleCount.textContent) || 0) / pageSize));
            var visible = Math.min(VISIBLE, pageCount);
            var maxIndex = Math.max(0, pageCount - visible);
            carouselIndex = Math.max(0, carouselIndex - 1);
            if (track) track.style.transform = 'translateX(' + (-carouselIndex * ITEM_WIDTH) + 'px)';
            carouselPrev.disabled = carouselIndex <= 0;
            if (carouselNext) carouselNext.disabled = carouselIndex >= maxIndex;
        });
        if (carouselNext) carouselNext.addEventListener('click', function () {
            var pageCount = Math.max(1, Math.ceil((Number(visibleCount && visibleCount.textContent) || 0) / pageSize));
            var visible = Math.min(VISIBLE, pageCount);
            var maxIndex = Math.max(0, pageCount - visible);
            carouselIndex = Math.min(maxIndex, carouselIndex + 1);
            if (track) track.style.transform = 'translateX(' + (-carouselIndex * ITEM_WIDTH) + 'px)';
            if (carouselPrev) carouselPrev.disabled = carouselIndex <= 0;
            carouselNext.disabled = carouselIndex >= maxIndex;
        });
        updateUserFilterSlider(currentFilter, false);
        applyUserFilters();
        window.addEventListener('resize', function () {
            updateUserFilterSlider(currentFilter, false);
        });

        var roleSelect = document.getElementById('createUserRole');
        if (roleSelect) {
            roleSelect.addEventListener('change', function () {
                syncCreateAdditionalRolesVisibility();
                syncCreateProcurementAccess();
            });
        }
        document.querySelectorAll('.create-additional-role').forEach(function (cb) {
            cb.addEventListener('change', syncCreateProcurementAccess);
        });
        var editPrimary = document.getElementById('editPrimaryRole');
        if (editPrimary) {
            editPrimary.addEventListener('change', function () {
                syncEditAdditionalRolesVisibility();
                syncEditProcurementAccess();
            });
        }
        document.querySelectorAll('.edit-additional-role').forEach(function (cb) {
            cb.addEventListener('change', syncEditProcurementAccess);
        });
        syncCreateAdditionalRolesVisibility();
        syncCreateProcurementAccess();
    });
</script>
@endpush


@endsection
