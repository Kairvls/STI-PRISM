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

        #viewUserModal .view-user-shell {
            overflow: hidden;
            border: 1px solid #e8eaed;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.12);
        }

        #viewUserModal .view-user-hero {
            position: relative;
            padding: 1.35rem 1.35rem 1.15rem;
            background:
                linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border-bottom: 1px solid #f1f5f9;
        }

        #viewUserModal .view-user-avatar {
            appearance: none;
            border: none;
            padding: 0;
            width: 3rem;
            height: 3rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #0f172a;
            color: #fff;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            flex-shrink: 0;
            overflow: hidden;
            cursor: default;
        }

        #viewUserModal .view-user-avatar:disabled {
            cursor: default;
            opacity: 1;
        }

        #viewUserModal .view-user-avatar.has-photo {
            background: #e2e8f0;
            cursor: zoom-in;
        }

        #viewUserModal .view-user-avatar.has-photo:hover {
            box-shadow: 0 0 0 2px #c7d2fe;
        }

        #viewUserModal .view-user-avatar.has-photo:focus-visible {
            outline: 2px solid #0025cc;
            outline-offset: 2px;
        }

        #viewUserModal .view-user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            pointer-events: none;
        }

        #viewUserPictureViewer {
            position: fixed;
            inset: 0;
            z-index: 13000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.82);
            backdrop-filter: blur(4px);
        }

        #viewUserPictureViewer.is-open {
            display: flex;
        }

        #viewUserPictureViewer .view-user-picture-frame {
            position: relative;
            max-width: min(92vw, 560px);
            max-height: 88vh;
        }

        #viewUserPictureViewer img {
            display: block;
            width: auto;
            height: auto;
            max-width: min(92vw, 560px);
            max-height: 88vh;
            border-radius: 1rem;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
            object-fit: contain;
            background: #0f172a;
        }

        #viewUserPictureViewer .view-user-picture-close {
            position: absolute;
            top: -0.65rem;
            right: -0.65rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.15rem;
            height: 2.15rem;
            border: none;
            border-radius: 999px;
            background: #fff;
            color: #0f172a;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        #viewUserPictureViewer .view-user-picture-close:hover {
            background: #f1f5f9;
        }

        #viewUserModal .view-user-name {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.02em;
            line-height: 1.25;
        }

        #viewUserModal .view-user-meta {
            margin-top: 0.2rem;
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 500;
        }

        #viewUserModal .view-user-status {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.65rem;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            background: #f1f5f9;
            color: #64748b;
        }

        #viewUserModal .view-user-status.is-active {
            background: #ecfdf5;
            color: #047857;
        }

        #viewUserModal .view-user-status-dot {
            width: 0.375rem;
            height: 0.375rem;
            border-radius: 999px;
            background: currentColor;
        }

        #viewUserModal .view-user-section {
            padding: 1rem 1.35rem 0.35rem;
        }

        #viewUserModal .view-user-section-label {
            margin: 0 0 0.55rem;
            font-size: 0.65rem;
            font-weight: 650;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        #viewUserModal .view-user-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem 1rem;
        }

        #viewUserModal .view-user-field {
            min-width: 0;
        }

        #viewUserModal .view-user-field.is-wide {
            grid-column: 1 / -1;
        }

        #viewUserModal .view-user-label {
            display: block;
            margin-bottom: 0.2rem;
            font-size: 0.68rem;
            font-weight: 500;
            color: #94a3b8;
        }

        #viewUserModal .view-user-value {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.35;
            word-break: break-word;
        }

        #viewUserModal .view-user-copy-row {
            display: flex;
            align-items: flex-start;
            gap: 0.4rem;
            min-width: 0;
        }

        #viewUserModal .view-user-copy-row .view-user-value {
            flex: 1;
            min-width: 0;
        }

        #viewUserModal .view-user-copy-btn {
            position: relative;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            margin-top: -0.1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.45rem;
            background: #fff;
            color: #64748b;
            cursor: pointer;
            transition: color 0.15s ease, border-color 0.15s ease, background 0.15s ease;
        }

        #viewUserModal .view-user-copy-btn:hover {
            color: #0025cc;
            border-color: #c7d2fe;
            background: #f8faff;
        }

        #viewUserModal .view-user-copy-btn.is-copied {
            color: #059669;
            border-color: #a7f3d0;
            background: #ecfdf5;
        }

        #viewUserModal .view-user-copy-btn.is-copied::after {
            content: 'Copied!';
            position: absolute;
            left: 50%;
            bottom: calc(100% + 0.4rem);
            transform: translateX(-50%);
            padding: 0.28rem 0.5rem;
            border-radius: 0.4rem;
            background: #0f172a;
            color: #fff;
            font-size: 0.68rem;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
            pointer-events: none;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.18);
            z-index: 2;
        }

        #viewUserModal .view-user-copy-btn.is-copied::before {
            content: '';
            position: absolute;
            left: 50%;
            bottom: calc(100% + 0.15rem);
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #0f172a;
            pointer-events: none;
            z-index: 2;
        }

        #viewUserModal .view-user-copy-btn svg {
            width: 0.85rem;
            height: 0.85rem;
        }

        #viewUserModal .view-user-chip {
            display: inline-flex;
            align-items: center;
            max-width: 100%;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.3;
        }

        #viewUserModal .view-user-extra-list {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            min-width: 0;
        }

        #viewUserModal .view-user-extra-item {
            display: block;
            color: #0025cc;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.35;
            white-space: nowrap;
        }

        #viewUserModal .view-user-extra-item.is-muted {
            color: #94a3b8;
            font-weight: 500;
        }

        #viewUserModal .view-user-footer {
            display: flex;
            justify-content: flex-end;
            padding: 0.85rem 1.35rem 1.15rem;
        }

        #viewUserModal .view-user-close-btn {
            appearance: none;
            border: none;
            background: #0025cc;
            color: #fff;
            border-radius: 999px;
            padding: 0.55rem 1.15rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        #viewUserModal .view-user-close-btn:hover {
            background: #001fad;
        }

        @media (max-width: 420px) {
            #viewUserModal .view-user-grid {
                grid-template-columns: 1fr;
            }
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
                            $profilePictureUrl = null;
                            $rawPicture = trim((string) ($user->user_profile_picture ?? ''));
                            if ($rawPicture !== '') {
                                if (preg_match('#^https?://#i', $rawPicture) || str_starts_with($rawPicture, '/')) {
                                    $profilePictureUrl = $rawPicture;
                                } else {
                                    $normalized = ltrim(preg_replace('#^storage/#', '', str_replace('\\', '/', $rawPicture)), '/');
                                    $profilePictureUrl = asset('storage/'.$normalized);
                                }
                            }
                        @endphp
                        <tr class="user-row transition hover:bg-gray-50/70" data-account-status="{{ $isActive ? 'active' : 'inactive' }}">
                            <td class="text-sm font-semibold text-gray-900">{{ $user->user_employee_id ?: '-' }}</td>
                            <td class="text-sm text-gray-700">{{ $user->user_full_name }}</td>
                            <td>
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $user->role_name ?: '-' }}</span>
                            </td>
                            <td>
                                @if(count($extraNames))
                                    <div class="flex flex-col gap-0.5">
                                        @foreach($extraNames as $extraName)
                                            <span class="whitespace-nowrap text-xs font-medium text-[#0025cc]">• {{ $extraName }}</span>
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
                                        onclick="openEditEmailModal(this)"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-800"
                                        title="Edit Office 365 email"
                                        data-user-id="{{ $user->user_id }}"
                                        data-full-name="{{ $user->user_full_name }}"
                                        data-email="{{ $user->user_email_address ?: '' }}"
                                    >
                                        <i data-lucide="mail" class="h-4 w-4 pointer-events-none"></i>
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
                                        data-profile-picture="{{ $profilePictureUrl ?: '' }}"
                                        data-procurement="{{ $isPurchaser && (int) $user->user_role_id === 3 ? 'Always on' : ($isPurchaser ? 'Enabled' : (($isMaintenance || $isAdminUser) ? ($canProcurement ? 'Enabled' : 'Disabled') : '—')) }}"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4 pointer-events-none"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="pur-empty">No user accounts found.</td></tr>
                    @endforelse
                    <tr id="usersEmptyFilterRow" class="hidden">
                        <td colspan="8" class="pur-empty">No accounts match this filter.</td>
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
        <div class="pur-modal-panel max-w-md !bg-transparent !p-0 !shadow-none !border-0" onclick="event.stopPropagation()">
            <div class="view-user-shell">
                <div class="view-user-hero">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <button type="button" class="view-user-avatar" id="viewUserAvatar" aria-label="Profile picture" disabled>—</button>
                            <div class="min-w-0">
                                <h3 class="view-user-name" id="viewUserFullName">—</h3>
                                <p class="view-user-meta">
                                    <span id="viewUserUsername">—</span>
                                    <span class="mx-1 text-slate-300">·</span>
                                    <span id="viewUserEmployeeId">—</span>
                                </p>
                                <span class="view-user-status" id="viewUserStatusPill">
                                    <span class="view-user-status-dot"></span>
                                    <span id="viewUserStatus">—</span>
                                </span>
                            </div>
                        </div>
                        <button type="button" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" onclick="closeViewUserModal()" aria-label="Close">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="view-user-section">
                    <p class="view-user-section-label">Access</p>
                    <div class="view-user-grid">
                        <div class="view-user-field">
                            <span class="view-user-label">Primary role</span>
                            <span class="view-user-chip" id="viewUserRole">—</span>
                        </div>
                        <div class="view-user-field">
                            <span class="view-user-label">Procurement</span>
                            <span class="view-user-value" id="viewUserProcurement">—</span>
                        </div>
                        <div class="view-user-field is-wide">
                            <span class="view-user-label">Additional roles</span>
                            <div class="view-user-extra-list" id="viewUserExtraRoles">
                                <span class="view-user-extra-item is-muted">None</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="view-user-section" style="padding-bottom: 0.85rem;">
                    <p class="view-user-section-label">Contact</p>
                    <div class="view-user-grid">
                        <div class="view-user-field is-wide">
                            <span class="view-user-label">Office 365 email</span>
                            <div class="view-user-copy-row">
                                <span class="view-user-value" id="viewUserEmail">—</span>
                                <button type="button" class="view-user-copy-btn" data-copy-target="viewUserEmail" data-copy-label="Copy email" title="Copy email" aria-label="Copy email" onclick="copyViewUserField(this)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="view-user-field is-wide">
                            <span class="view-user-label">Phone</span>
                            <div class="view-user-copy-row">
                                <span class="view-user-value" id="viewUserContact">—</span>
                                <button type="button" class="view-user-copy-btn" data-copy-target="viewUserContact" data-copy-label="Copy phone" title="Copy phone" aria-label="Copy phone" onclick="copyViewUserField(this)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="view-user-footer">
                    <button type="button" class="view-user-close-btn" onclick="closeViewUserModal()">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="viewUserPictureViewer" role="dialog" aria-modal="true" aria-label="Profile picture" aria-hidden="true" onclick="closeViewUserPictureViewer()">
    <div class="view-user-picture-frame" onclick="event.stopPropagation()">
        <button type="button" class="view-user-picture-close" onclick="closeViewUserPictureViewer()" aria-label="Close picture">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        <img id="viewUserPictureViewerImage" src="" alt="Profile picture">
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
                        <input type="text" name="employee_id" class="pur-input mt-1.5" placeholder="e.g. OMC0126F" required />
                    </div>
                    <div>
                        <label class="pur-label">Full Name</label>
                        <input type="text" name="full_name" class="pur-input mt-1.5" placeholder="e.g. Juan Dela Cruz" required />
                    </div>
                    <div>
                        <label class="pur-label">Username</label>
                        <input type="text" name="username" class="pur-input mt-1.5" required />
                    </div>
                    <div>
                        <label class="pur-label">Email</label>
                        <input type="email" name="email" class="pur-input mt-1.5" placeholder="e.g. juan.delacruz@sti.edu.ph" required />
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="min-w-0">
                            <label class="pur-label" for="createUserPassword">Password</label>
                            <div class="relative mt-1.5">
                                <input
                                    type="password"
                                    name="password"
                                    id="createUserPassword"
                                    class="pur-input w-full pr-11"
                                    required
                                    autocomplete="new-password"
                                />
                                <button
                                    type="button"
                                    id="createUserPasswordToggle"
                                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-gray-400 transition hover:text-gray-700"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                    onclick="toggleCreateUserPassword()"
                                >
                                    <i data-lucide="eye" class="create-password-icon-show h-4 w-4 pointer-events-none"></i>
                                    <i data-lucide="eye-off" class="create-password-icon-hide hidden h-4 w-4 pointer-events-none"></i>
                                </button>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <label class="pur-label">Contact Number</label>
                            @include('partials.phone-input', [
                                'name' => 'contact_number',
                                'value' => old('contact_number'),
                                'id' => 'admin-index-user-contact-number',
                                'inputClass' => 'mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100',
                            ])
                        </div>
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

<div id="editEmailModal" class="fixed inset-0 z-[12000] hidden">
    <div class="pur-modal !z-[12000]" onclick="closeEditEmailModal()">
        <div class="pur-modal-panel max-w-lg" onclick="event.stopPropagation()">
            <div class="pur-modal-header">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3>Edit Office 365 email</h3>
                        <p id="editEmailSubtitle" class="mt-0.5 text-sm font-normal text-gray-500">Must match the Microsoft sign-in email exactly.</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-900" onclick="closeEditEmailModal()" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <form method="POST" id="editEmailForm" action="#">
                @csrf
                <div class="pur-modal-body space-y-4">
                    <div>
                        <label class="pur-label" for="editUserEmail">Email</label>
                        <input
                            type="email"
                            name="email"
                            id="editUserEmail"
                            class="pur-input mt-1.5"
                            required
                            autocomplete="off"
                            placeholder="name@sti.edu.ph"
                        />
                        <p class="mt-1.5 text-xs text-gray-500">PaAyo matches this to the Microsoft account after Office 365 sign-in.</p>
                    </div>
                    @error('email')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="pur-modal-footer">
                    <button type="button" class="pur-btn-secondary" onclick="closeEditEmailModal()">Cancel</button>
                    <button type="submit" class="pur-btn-primary">Save email</button>
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

    async function syncAdminUsersCsrf(form) {
        try {
            var res = await fetch('/user/csrf-token', {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store'
            });
            if (!res.ok) {
                return false;
            }
            var data = await res.json();
            var token = data && data.token ? String(data.token) : '';
            if (!token) {
                return false;
            }
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) {
                meta.setAttribute('content', token);
            }
            if (form) {
                var input = form.querySelector('input[name="_token"]');
                if (input) {
                    input.value = token;
                }
            }
            return true;
        } catch (e) {
            return false;
        }
    }

    function bindAdminUsersCsrfForm(formId) {
        var form = document.getElementById(formId);
        if (!form || form.dataset.csrfBound === '1') {
            return;
        }
        form.dataset.csrfBound = '1';
        form.addEventListener('submit', function (event) {
            if (form.dataset.csrfReady === '1') {
                form.dataset.csrfReady = '0';
                return;
            }
            event.preventDefault();
            syncAdminUsersCsrf(form).then(function (ok) {
                if (!ok) {
                    window.alert('Your session token expired. Refresh the page and try again.');
                    return;
                }
                form.dataset.csrfReady = '1';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });
        });
    }

    window.closeViewUserPictureViewer = function() {
        var viewer = document.getElementById('viewUserPictureViewer');
        var image = document.getElementById('viewUserPictureViewerImage');
        if (viewer) {
            viewer.classList.remove('is-open');
            viewer.setAttribute('aria-hidden', 'true');
        }
        if (image) {
            image.removeAttribute('src');
            image.alt = 'Profile picture';
        }
    };

    window.openViewUserPictureViewer = function(url, alt) {
        if (!url) return;
        var viewer = document.getElementById('viewUserPictureViewer');
        var image = document.getElementById('viewUserPictureViewerImage');
        if (!viewer || !image) return;
        image.src = url;
        image.alt = alt || 'Profile picture';
        viewer.classList.add('is-open');
        viewer.setAttribute('aria-hidden', 'false');
    };

    window.closeViewUserModal = function() {
        closeViewUserPictureViewer();
        var modal = document.getElementById('viewUserModal');
        if (modal) modal.classList.add('hidden');
    };

    window.copyViewUserField = function(btn) {
        if (!btn) return;
        var targetId = btn.getAttribute('data-copy-target');
        var target = targetId ? document.getElementById(targetId) : null;
        var text = target ? String(target.textContent || '').trim() : '';
        if (!text || text === '—') return;

        var markCopied = function() {
            btn.classList.add('is-copied');
            btn.setAttribute('title', 'Copied!');
            btn.setAttribute('aria-label', 'Copied!');
            window.clearTimeout(btn._copyTimer);
            btn._copyTimer = window.setTimeout(function() {
                btn.classList.remove('is-copied');
                var restore = btn.getAttribute('data-copy-label') || 'Copy';
                btn.setAttribute('title', restore);
                btn.setAttribute('aria-label', restore);
            }, 1600);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(markCopied).catch(function() {
                fallbackCopy(text, markCopied);
            });
            return;
        }
        fallbackCopy(text, markCopied);
    };

    function fallbackCopy(text, onSuccess) {
        try {
            var area = document.createElement('textarea');
            area.value = text;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.left = '-9999px';
            document.body.appendChild(area);
            area.select();
            var ok = document.execCommand('copy');
            document.body.removeChild(area);
            if (ok && typeof onSuccess === 'function') onSuccess();
        } catch (e) {}
    }

    window.openViewUserModal = function(btn) {
        if (!btn) return;
        var setText = function(id, value) {
            var el = document.getElementById(id);
            if (el) el.textContent = value || '—';
        };
        var fullName = btn.getAttribute('data-full-name') || '—';
        var status = btn.getAttribute('data-status') || '—';
        var extraRoles = btn.getAttribute('data-extra-roles') || '—';
        setText('viewUserEmployeeId', btn.getAttribute('data-employee-id'));
        setText('viewUserFullName', fullName);
        setText('viewUserUsername', btn.getAttribute('data-username'));
        setText('viewUserRole', btn.getAttribute('data-role'));
        setText('viewUserProcurement', btn.getAttribute('data-procurement'));
        setText('viewUserStatus', status);
        setText('viewUserEmail', btn.getAttribute('data-email'));
        setText('viewUserContact', btn.getAttribute('data-contact'));

        var extraList = document.getElementById('viewUserExtraRoles');
        if (extraList) {
            extraList.innerHTML = '';
            var rawExtra = String(extraRoles || '').trim();
            var isEmpty = !rawExtra || rawExtra === '—' || rawExtra === '-' || rawExtra.toLowerCase() === 'none';
            if (isEmpty) {
                var noneItem = document.createElement('span');
                noneItem.className = 'view-user-extra-item is-muted';
                noneItem.textContent = 'None';
                extraList.appendChild(noneItem);
            } else {
                rawExtra.split(',').map(function (part) {
                    return String(part || '').trim();
                }).filter(Boolean).forEach(function (roleName) {
                    var item = document.createElement('span');
                    item.className = 'view-user-extra-item';
                    item.textContent = '• ' + roleName;
                    extraList.appendChild(item);
                });
            }
        }

        var avatar = document.getElementById('viewUserAvatar');
        if (avatar) {
            var pictureUrl = (btn.getAttribute('data-profile-picture') || '').trim();
            var parts = String(fullName).trim().split(/\s+/).filter(Boolean);
            var initials = parts.length >= 2
                ? (parts[0].charAt(0) + parts[parts.length - 1].charAt(0))
                : (parts[0] ? parts[0].slice(0, 2) : '—');
            initials = initials.toUpperCase();
            var pictureAlt = fullName !== '—' ? fullName : 'Profile picture';

            avatar.innerHTML = '';
            avatar.classList.remove('has-photo');
            avatar.disabled = true;
            avatar.removeAttribute('title');
            avatar.onclick = null;
            avatar.onkeydown = null;

            if (pictureUrl) {
                var img = document.createElement('img');
                img.src = pictureUrl;
                img.alt = pictureAlt;
                img.loading = 'lazy';
                img.onerror = function () {
                    avatar.classList.remove('has-photo');
                    avatar.disabled = true;
                    avatar.removeAttribute('title');
                    avatar.onclick = null;
                    avatar.innerHTML = '';
                    avatar.textContent = initials;
                };
                avatar.classList.add('has-photo');
                avatar.disabled = false;
                avatar.setAttribute('title', 'View profile picture');
                avatar.setAttribute('aria-label', 'View profile picture');
                avatar.onclick = function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    openViewUserPictureViewer(pictureUrl, pictureAlt);
                };
                avatar.appendChild(img);
            } else {
                avatar.setAttribute('aria-label', 'Profile picture');
                avatar.textContent = initials;
            }
        }

        var statusPill = document.getElementById('viewUserStatusPill');
        if (statusPill) {
            statusPill.classList.toggle('is-active', String(status).toLowerCase() === 'active');
        }

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
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    };

    window.closeCreateUserModal = function() {
        if (typeof window.closeAllPrismPhoneDropdowns === 'function') {
            window.closeAllPrismPhoneDropdowns();
        }
        var modal = document.getElementById('createUserModal');
        if (modal) modal.classList.add('hidden');
        var input = document.getElementById('createUserPassword');
        var toggle = document.getElementById('createUserPasswordToggle');
        if (input) input.type = 'password';
        if (toggle) {
            toggle.setAttribute('aria-pressed', 'false');
            toggle.setAttribute('aria-label', 'Show password');
            var showIcon = toggle.querySelector('.create-password-icon-show');
            var hideIcon = toggle.querySelector('.create-password-icon-hide');
            if (showIcon) showIcon.classList.remove('hidden');
            if (hideIcon) hideIcon.classList.add('hidden');
        }
    };

    window.toggleCreateUserPassword = function() {
        var input = document.getElementById('createUserPassword');
        var toggle = document.getElementById('createUserPasswordToggle');
        if (!input || !toggle) return;
        var showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        toggle.setAttribute('aria-pressed', showing ? 'false' : 'true');
        toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        var showIcon = toggle.querySelector('.create-password-icon-show');
        var hideIcon = toggle.querySelector('.create-password-icon-hide');
        if (showIcon) showIcon.classList.toggle('hidden', !showing);
        if (hideIcon) hideIcon.classList.toggle('hidden', showing);
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    };

    window.closeEditRolesModal = function() {
        var modal = document.getElementById('editRolesModal');
        if (modal) modal.classList.add('hidden');
    };

    window.closeEditEmailModal = function() {
        var modal = document.getElementById('editEmailModal');
        if (modal) modal.classList.add('hidden');
    };

    window.openEditEmailModal = function(btn) {
        if (!btn) return;
        var userId = btn.getAttribute('data-user-id');
        var form = document.getElementById('editEmailForm');
        if (form) form.action = '/admin/users/' + userId + '/email';
        var subtitle = document.getElementById('editEmailSubtitle');
        if (subtitle) {
            subtitle.textContent = 'Office 365 email for ' + (btn.getAttribute('data-full-name') || 'user') + ' — must match Microsoft sign-in exactly.';
        }
        var input = document.getElementById('editUserEmail');
        if (input) {
            input.value = btn.getAttribute('data-email') || '';
            requestAnimationFrame(function () { input.focus(); input.select(); });
        }
        var modal = mountUserModal(document.getElementById('editEmailModal'));
        if (modal) modal.classList.remove('hidden');
        syncAdminUsersCsrf(form);
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
        var pictureViewer = document.getElementById('viewUserPictureViewer');
        if (pictureViewer && pictureViewer.classList.contains('is-open')) {
            closeViewUserPictureViewer();
            return;
        }
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

        bindAdminUsersCsrfForm('editEmailForm');
        bindAdminUsersCsrfForm('editRolesForm');
        var createForm = document.querySelector('#createUserModal form');
        if (createForm) {
            if (!createForm.id) {
                createForm.id = 'createUserForm';
            }
            bindAdminUsersCsrfForm(createForm.id);
        }

        @if(session('edit_email_user_id'))
        (function () {
            var form = document.getElementById('editEmailForm');
            if (form) form.action = '/admin/users/{{ (int) session('edit_email_user_id') }}/email';
            var subtitle = document.getElementById('editEmailSubtitle');
            if (subtitle) {
                subtitle.textContent = 'Office 365 email for {{ addslashes((string) session('edit_email_full_name', 'user')) }} — must match Microsoft sign-in exactly.';
            }
            var input = document.getElementById('editUserEmail');
            if (input) input.value = @json(old('email', ''));
            var modal = mountUserModal(document.getElementById('editEmailModal'));
            if (modal) modal.classList.remove('hidden');
        })();
        @endif
    });
</script>
@endpush


@endsection
