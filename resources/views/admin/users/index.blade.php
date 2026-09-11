@extends('layouts.admin-layout')

@section('title', 'User Management')

@section('content')

<div class="admin-page space-y-6">

    

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total users</p>
            <p class="admin-stat-card-value mt-2">{{ $totalUsers }}</p>
        </div>
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Recently active</p>
            <p class="admin-stat-card-value mt-2">{{ $activeUsers }}</p>
        </div>
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Roles in use</p>
            <p class="admin-stat-card-value mt-2">{{ $roleCount }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-5 py-4">
            <div class="relative min-w-[220px] flex-1">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="userSearchInput" placeholder="Search by name, employee ID, or role..." class="h-10 w-full rounded-lg border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-900 outline-none">
            </div>
            <div
                id="userFilterSlider"
                class="relative inline-flex max-w-full items-center overflow-x-auto rounded-xl bg-slate-200/70 p-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            >
                <span
                    class="user-filter-thumb pointer-events-none absolute top-1 left-0 z-0 h-9 rounded-lg bg-white shadow-sm will-change-transform"
                    style="transform: translate3d(0, 0, 0); transition: transform 220ms cubic-bezier(0.22, 1, 0.36, 1), width 220ms cubic-bezier(0.22, 1, 0.36, 1);"
                    aria-hidden="true"
                ></span>
                <button type="button" data-filter="all" class="user-filter-btn relative z-10 flex h-9 shrink-0 items-center rounded-lg px-4 text-xs font-semibold text-slate-950">All</button>
                <button type="button" data-filter="active" class="user-filter-btn relative z-10 flex h-9 shrink-0 items-center rounded-lg px-4 text-xs font-semibold text-slate-500 hover:text-slate-900">Active</button>
                <button type="button" data-filter="inactive" class="user-filter-btn relative z-10 flex h-9 shrink-0 items-center rounded-lg px-4 text-xs font-semibold text-slate-500 hover:text-slate-900">Inactive</button>
            </div>
            <button type="button" onclick="openCreateUserModal()" class="admin-btn-primary h-10">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Create Account
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Employee ID</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Full Name</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Username</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Primary role</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Additional roles</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Procurement</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Last active</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="usersTableBody">
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
                        <tr class="user-row" data-account-status="{{ $isActive ? 'active' : 'inactive' }}">
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $user->user_employee_id ?: '-' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $user->user_full_name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-600">{{ $user->user_username }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">{{ $user->role_name ?: '-' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @if(count($extraNames))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($extraNames as $extraName)
                                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200">{{ $extraName }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($isPurchaser && (int) $user->user_role_id === 3)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Always on</span>
                                @elseif($isPurchaser)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Enabled</span>
                                @elseif($isMaintenance || $isAdminUser)
                                    <form method="POST" action="{{ route('admin.users.procurement-access', $user->user_id) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="user_can_procurement" value="{{ $canProcurement ? 0 : 1 }}">
                                        <button type="submit"
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset transition {{ $canProcurement ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 ring-slate-200 hover:bg-slate-200' }}"
                                            title="{{ $canProcurement ? 'Click to disable procurement' : 'Click to enable Purchaser portal (Decision A)' }}">
                                            {{ $canProcurement ? 'Enabled' : 'Disabled' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($isActive)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-500">
                                @if(!empty($user->last_active_at))
                                    {{ \Carbon\Carbon::parse($user->last_active_at)->diffForHumans() }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button"
                                        onclick="openEditRolesModal(this)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
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
                                        class="view-user-btn inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
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
                        <tr><td colspan="9" class="px-5 py-16 text-center text-sm text-gray-400">No user accounts found.</td></tr>
                    @endforelse
                    <tr id="usersEmptyFilterRow" class="hidden">
                        <td colspan="9" class="px-5 py-16 text-center text-sm text-gray-400">No accounts match this filter.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="usersPager" class="print-hidden flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-5 py-4">
            <p class="text-xs text-gray-500">
                Showing
                <span id="usersShowingFrom" class="font-semibold text-gray-700">0</span>
                Â
                <span id="usersShowingTo" class="font-semibold text-gray-700">0</span>
                of
                <span id="usersVisibleCount" class="font-semibold text-gray-700">{{ $totalUsers }}</span>
                users
            </p>
            <div id="usersPageControls" class="flex items-center gap-1">
                <button type="button" id="usersPagePrev" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900" title="Previous page">&lt;</button>
                <span id="usersPageNum" class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-slate-900 px-3 text-sm font-semibold text-white">1</span>
                <button type="button" id="usersPageNext" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900" title="Next page">&gt;</button>
            </div>
        </div>
    </div>

</div>

<div id="viewUserModal" class="fixed inset-0 hidden" style="z-index: 12000;">
    <div class="flex min-h-screen items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]" onclick="closeViewUserModal()">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">User Details</h3>
                        <p class="mt-1 text-sm text-slate-600">Account information</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" onclick="closeViewUserModal()" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="space-y-3 px-6 py-5 text-sm">
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2"><span class="text-slate-500">Employee ID</span><span id="viewUserEmployeeId" class="font-semibold text-slate-900"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2"><span class="text-slate-500">Full Name</span><span id="viewUserFullName" class="font-semibold text-slate-900"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2"><span class="text-slate-500">Username</span><span id="viewUserUsername" class="font-semibold text-slate-900"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2"><span class="text-slate-500">Primary role</span><span id="viewUserRole" class="font-semibold text-slate-900"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2"><span class="text-slate-500">Additional roles</span><span id="viewUserExtraRoles" class="font-semibold text-slate-900 text-right"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2"><span class="text-slate-500">Procurement</span><span id="viewUserProcurement" class="font-semibold text-slate-900"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2"><span class="text-slate-500">Status</span><span id="viewUserStatus" class="font-semibold text-slate-900"></span></div>
                <div class="flex justify-between gap-4 border-b border-gray-50 py-2"><span class="text-slate-500">Email</span><span id="viewUserEmail" class="font-semibold text-slate-900"></span></div>
                <div class="flex justify-between gap-4 py-2"><span class="text-slate-500">Contact</span><span id="viewUserContact" class="font-semibold text-slate-900"></span></div>
            </div>
            <div class="flex items-center justify-end border-t border-gray-100 px-6 py-4">
                <button type="button" class="rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-950" onclick="closeViewUserModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<div id="createUserModal" class="fixed inset-0 hidden" style="z-index: 12000;">
    <div class="flex min-h-screen items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]" onclick="closeCreateUserModal()">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">Create User Account</h3>
                        <p class="mt-1 text-sm text-slate-600">Add a new user to the system</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" onclick="closeCreateUserModal()" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <form method="POST" action="/admin/users/store" class="space-y-0">
                @csrf
                <div class="space-y-4 overflow-y-auto px-6 py-5" style="max-height: calc(100vh - 280px);">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Employee ID</label>
                        <input type="text" name="employee_id" class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Full Name</label>
                        <input type="text" name="full_name" class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Username</label>
                        <input type="text" name="username" class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" name="email" class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Contact Number</label>
                        @include('partials.phone-input', [
                            'name' => 'contact_number',
                            'value' => old('contact_number'),
                            'id' => 'admin-index-user-contact-number',
                            'inputClass' => 'mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100',
                        ])
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Password</label>
                        <input type="password" name="password" class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Primary role <span class="font-normal text-slate-400">(used for Office 365 login)</span></label>
                        <select name="primary_role" id="createUserRole" class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100" required>
                            <option value="">Select primary role...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->role_id }}">{{ $role->role_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Additional roles <span class="font-normal text-slate-400">(optional)</span></label>
                        <div class="mt-2 space-y-2 rounded-xl border border-gray-200 bg-slate-50 px-3 py-3">
                            @foreach($roles as $role)
                                @if((int) $role->role_id !== 1)
                                    <label class="create-additional-role-row flex items-center gap-2 text-sm text-slate-700" data-role-id="{{ $role->role_id }}">
                                        <input type="checkbox" name="additional_roles[]" value="{{ $role->role_id }}" class="create-additional-role h-4 w-4 rounded border-gray-300 text-slate-900 focus:ring-slate-200">
                                        <span>{{ $role->role_name }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500">Primary role is always included. Extra roles unlock those portals without changing login destination.</p>
                    </div>
                    <div id="createProcurementAccessWrap" class="hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="user_can_procurement" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-slate-900 focus:ring-slate-200">
                            <span>
                                <span class="block text-sm font-semibold text-slate-900">Enable procurement workflow</span>
                                <span class="mt-0.5 block text-xs leading-relaxed text-slate-500">Assigns Purchaser access. Use the portal switcher to create and drive RIS → ATP → RFC/CA → RR → Liquidation. Admin portal stays accept/sign + monitor only.</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-6 py-4">
                    <button type="button" class="rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-950" onclick="closeCreateUserModal()">Cancel</button>
                    <button type="submit" class="admin-btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editRolesModal" class="fixed inset-0 hidden" style="z-index: 12000;">
    <div class="flex min-h-screen items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]" onclick="closeEditRolesModal()">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">Edit Roles</h3>
                        <p class="mt-1 text-sm text-slate-600" id="editRolesSubtitle">Update primary and additional roles</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" onclick="closeEditRolesModal()" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <form method="POST" id="editRolesForm" class="space-y-0">
                @csrf
                <div class="space-y-4 overflow-y-auto px-6 py-5" style="max-height: calc(100vh - 280px);">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Primary role</label>
                        <select name="primary_role" id="editPrimaryRole" class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->role_id }}" class="edit-primary-role-option" data-role-id="{{ $role->role_id }}">{{ $role->role_name }}</option>
                            @endforeach
                        </select>
                        <p id="editAdminPrimaryHint" class="mt-1.5 hidden text-xs text-slate-500">Administrator primary role is locked. Add Purchaser below or enable procurement workflow to perform docs via portal switch.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Additional roles</label>
                        <div class="mt-2 space-y-2 rounded-xl border border-gray-200 bg-slate-50 px-3 py-3">
                            @foreach($roles as $role)
                                @if((int) $role->role_id !== 1)
                                    <label class="edit-additional-role-row flex items-center gap-2 text-sm text-slate-700" data-role-id="{{ $role->role_id }}">
                                        <input type="checkbox" name="additional_roles[]" value="{{ $role->role_id }}" class="edit-additional-role h-4 w-4 rounded border-gray-300 text-slate-900 focus:ring-slate-200">
                                        <span>{{ $role->role_name }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div id="editProcurementAccessWrap" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="user_can_procurement" value="1" id="editCanProcurement" class="mt-1 h-4 w-4 rounded border-gray-300 text-slate-900 focus:ring-slate-200">
                            <span>
                                <span class="block text-sm font-semibold text-slate-900">Enable procurement workflow</span>
                                <span class="mt-0.5 block text-xs leading-relaxed text-slate-500">Grants Purchaser access. Open Purchaser via the portal switcher to create RIS → ATP → RFC/CA → RR → Liquidation. Accounting/Receiving approvals stay with those portals.</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-6 py-4">
                    <button type="button" class="rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-950" onclick="closeEditRolesModal()">Cancel</button>
                    <button type="submit" class="admin-btn-primary">Save Roles</button>
                </div>
            </form>
        </div>
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
    };

    window.closeCreateUserModal = function() {
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
        var showingFrom = document.getElementById('usersShowingFrom');
        var showingTo = document.getElementById('usersShowingTo');
        var pageControls = document.getElementById('usersPageControls');
        var prevBtn = document.getElementById('usersPagePrev');
        var nextBtn = document.getElementById('usersPageNext');
        var pageNum = document.getElementById('usersPageNum');

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
            if (pageNum) pageNum.textContent = String(currentPage);
            if (pageControls) pageControls.style.display = shown > pageSize ? 'flex' : 'none';
            if (prevBtn) {
                prevBtn.disabled = currentPage <= 1;
                prevBtn.classList.toggle('opacity-40', currentPage <= 1);
            }
            if (nextBtn) {
                nextBtn.disabled = currentPage >= pageCount;
                nextBtn.classList.toggle('opacity-40', currentPage >= pageCount);
            }
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
        if (prevBtn) prevBtn.addEventListener('click', function () {
            if (currentPage <= 1) return;
            currentPage -= 1;
            applyUserFilters();
        });
        if (nextBtn) nextBtn.addEventListener('click', function () {
            currentPage += 1;
            applyUserFilters();
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
