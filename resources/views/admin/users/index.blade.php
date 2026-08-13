@extends('layouts.admin-layout')

@section('title', 'User Management')

@section('content')

<div class="space-y-6">

    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <div class="flex items-center justify-between">
        <div>
            <h1 class="admin-page-title">
                User Management
            </h1>
            <p class="admin-page-subtitle">
                View, manage, and control user accounts across the system.
            </p>
        </div>
    </div>


    {{-- ===================================================== --}}
    {{-- SUCCESS MESSAGE --}}
    {{-- ===================================================== --}}

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- ===================================================== --}}
    {{-- ERROR MESSAGE --}}
    {{-- ===================================================== --}}

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif


    {{-- ===================================================== --}}
    {{-- STAT CARDS --}}
    {{-- ===================================================== --}}

    <div class="grid grid-cols-4 gap-4">

        {{-- TOTAL USERS --}}
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i data-lucide="users" class="h-5 w-5"></i>
                </div>
            </div>
            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Total Users</p>
            <p class="mt-0.5 text-2xl font-bold text-gray-900">8</p>
        </div>

        {{-- ACTIVE USERS --}}
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <i data-lucide="check-circle" class="h-5 w-5"></i>
                </div>
            </div>
            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Active</p>
            <p class="mt-0.5 text-2xl font-bold text-gray-900">5</p>
        </div>

        {{-- INACTIVE USERS --}}
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <i data-lucide="pause-circle" class="h-5 w-5"></i>
                </div>
            </div>
            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Inactive</p>
            <p class="mt-0.5 text-2xl font-bold text-gray-900">2</p>
        </div>

        {{-- DEACTIVATED USERS --}}
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                    <i data-lucide="ban" class="h-5 w-5"></i>
                </div>
            </div>
            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Deactivated</p>
            <p class="mt-0.5 text-2xl font-bold text-gray-900">1</p>
        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- ACTION BAR --}}
    {{-- ===================================================== --}}

    <div class="flex items-center justify-between gap-4">

        {{-- SEARCH --}}
        <div class="relative flex-1 max-w-md">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
            <input
                type="text"
                id="userSearchInput"
                placeholder="Search by name, employee ID, or role..."
                class="h-10 w-full rounded-lg border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
            />
        </div>

        {{-- CREATE ACCOUNT BUTTON --}}
        <button
            onclick="openCreateUserModal()"
            class="inline-flex h-10 items-center gap-2 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 active:scale-95"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            Create Account
        </button>

    </div>


    {{-- ===================================================== --}}
    {{-- USERS TABLE --}}
    {{-- ===================================================== --}}

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Employee ID</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Full Name</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Role</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-50">

                    {{-- ROW 1: Active Maintenance Personnel --}}
                    <tr class="transition hover:bg-gray-50/50">
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">2026-001</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Juan Dela Cruz</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-200">
                                Maintenance Personnel
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Active
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewUser('2026-001', 'Juan Dela Cruz', 'Maintenance Personnel', 'Active')" title="View user details" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </button>
                                <button onclick="editUser('2026-001')" title="Edit user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <i data-lucide="pencil-line" class="h-4 w-4"></i>
                                </button>
                                <button onclick="deactivateUser('2026-001', 'Juan Dela Cruz')" title="Deactivate user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-amber-400 transition hover:bg-amber-50 hover:text-amber-600">
                                    <i data-lucide="pause-circle" class="h-4 w-4"></i>
                                </button>
                                <button onclick="deleteUser('2026-001', 'Juan Dela Cruz')" title="Delete user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-rose-400 transition hover:bg-rose-50 hover:text-rose-600">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- ROW 2: Active Purchaser --}}
                    <tr class="transition hover:bg-gray-50/50">
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">2026-002</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Maria Santos</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full bg-cyan-50 px-2.5 py-0.5 text-xs font-semibold text-cyan-700 ring-1 ring-inset ring-cyan-200">
                                Purchaser
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Active
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewUser('2026-002', 'Maria Santos', 'Purchaser', 'Active')" title="View user details" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </button>
                                <button onclick="editUser('2026-002')" title="Edit user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <i data-lucide="pencil-line" class="h-4 w-4"></i>
                                </button>
                                <button onclick="deactivateUser('2026-002', 'Maria Santos')" title="Deactivate user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-amber-400 transition hover:bg-amber-50 hover:text-amber-600">
                                    <i data-lucide="pause-circle" class="h-4 w-4"></i>
                                </button>
                                <button onclick="deleteUser('2026-002', 'Maria Santos')" title="Delete user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-rose-400 transition hover:bg-rose-50 hover:text-rose-600">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- ROW 3: Inactive President --}}
                    <tr class="transition hover:bg-gray-50/50">
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">2026-003</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Dr. Roberto Reyes</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                President
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                Inactive
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewUser('2026-003', 'Dr. Roberto Reyes', 'President', 'Inactive')" title="View user details" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </button>
                                <button onclick="editUser('2026-003')" title="Edit user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <i data-lucide="pencil-line" class="h-4 w-4"></i>
                                </button>
                                <button onclick="reactivateUser('2026-003', 'Dr. Roberto Reyes')" title="Reactivate user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-emerald-400 transition hover:bg-emerald-50 hover:text-emerald-600">
                                    <i data-lucide="play" class="h-4 w-4"></i>
                                </button>
                                <button onclick="deleteUser('2026-003', 'Dr. Roberto Reyes')" title="Delete user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-rose-400 transition hover:bg-rose-50 hover:text-rose-600">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- ROW 4: Deactivated Accounting --}}
                    <tr class="transition hover:bg-gray-50/50">
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">2026-004</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Ana Gonzales</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-[#0037c7] ring-1 ring-inset ring-blue-200">
                                Accounting
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                Deactivated
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewUser('2026-004', 'Ana Gonzales', 'Accounting', 'Deactivated')" title="View user details" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </button>
                                <button onclick="editUser('2026-004')" title="Edit user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <i data-lucide="pencil-line" class="h-4 w-4"></i>
                                </button>
                                <button onclick="reactivateUser('2026-004', 'Ana Gonzales')" title="Reactivate user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-emerald-400 transition hover:bg-emerald-50 hover:text-emerald-600">
                                    <i data-lucide="play" class="h-4 w-4"></i>
                                </button>
                                <button onclick="deleteUser('2026-004', 'Ana Gonzales')" title="Delete user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-rose-400 transition hover:bg-rose-50 hover:text-rose-600">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- ROW 5: Active Receiving Officer --}}
                    <tr class="transition hover:bg-gray-50/50">
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">2026-005</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Pedro Lim</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-700 ring-1 ring-inset ring-teal-200">
                                Receiving Officer
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Active
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewUser('2026-005', 'Pedro Lim', 'Receiving Officer', 'Active')" title="View user details" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </button>
                                <button onclick="editUser('2026-005')" title="Edit user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <i data-lucide="pencil-line" class="h-4 w-4"></i>
                                </button>
                                <button onclick="deactivateUser('2026-005', 'Pedro Lim')" title="Deactivate user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-amber-400 transition hover:bg-amber-50 hover:text-amber-600">
                                    <i data-lucide="pause-circle" class="h-4 w-4"></i>
                                </button>
                                <button onclick="deleteUser('2026-005', 'Pedro Lim')" title="Delete user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-rose-400 transition hover:bg-rose-50 hover:text-rose-600">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- ROW 6: Inactive Maintenance Personnel --}}
                    <tr class="transition hover:bg-gray-50/50">
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">2026-006</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Carlos Mendoza</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-200">
                                Maintenance Personnel
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                Inactive
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="viewUser('2026-006', 'Carlos Mendoza', 'Maintenance Personnel', 'Inactive')" title="View user details" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </button>
                                <button onclick="editUser('2026-006')" title="Edit user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <i data-lucide="pencil-line" class="h-4 w-4"></i>
                                </button>
                                <button onclick="reactivateUser('2026-006', 'Carlos Mendoza')" title="Reactivate user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-emerald-400 transition hover:bg-emerald-50 hover:text-emerald-600">
                                    <i data-lucide="play" class="h-4 w-4"></i>
                                </button>
                                <button onclick="deleteUser('2026-006', 'Carlos Mendoza')" title="Delete user" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-rose-400 transition hover:bg-rose-50 hover:text-rose-600">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

        {{-- TABLE FOOTER --}}
        <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3">
            <p class="text-xs text-gray-500">
                Showing <span class="font-semibold text-gray-700">6</span> of <span class="font-semibold text-gray-700">8</span> users
            </p>
            <div class="flex items-center gap-2">
                <button class="inline-flex h-8 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:bg-gray-50 disabled:opacity-40" disabled>
                    Previous
                </button>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-xs font-semibold text-white">1</span>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-xs font-semibold text-gray-600 transition hover:bg-gray-50">2</span>
                <button class="inline-flex h-8 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:bg-gray-50">
                    Next
                </button>
            </div>
        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- CREATE USER MODAL --}}
    {{-- ===================================================== --}}

    <div id="createUserModal" class="fixed inset-0 z-50 hidden">
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
                            <input
                                type="text"
                                name="employee_id"
                                class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Full Name</label>
                            <input
                                type="text"
                                name="full_name"
                                class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Username</label>
                            <input
                                type="text"
                                name="username"
                                class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Email</label>
                            <input
                                type="email"
                                name="email"
                                class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Contact Number</label>
                            <input
                                type="text"
                                name="contact_number"
                                class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Password</label>
                            <input
                                type="password"
                                name="password"
                                class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Role</label>
                            <select
                                name="role"
                                class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                                required
                            >
                                <option value="">Select a role...</option>
                                <option value="2">Maintenance Personnel</option>
                                <option value="3">Purchaser</option>
                                <option value="4">President</option>
                                <option value="5">Accounting</option>
                                <option value="6">Receiving Officer</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-6 py-4">
                        <button type="button" class="rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-950" onclick="closeCreateUserModal()">Cancel</button>
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
</div>
</div>


{{-- ===================================================== --}}
{{-- USER MANAGEMENT JAVASCRIPT --}}
{{-- (SWEETALERT2 CONFIRMATIONS + MODAL CONTROLS) --}}
{{-- ===================================================== --}}

<script>

    // =====================================================
    // SWEETALERT2 CONFIRMATION: DELETE USER
    // =====================================================

    function deleteUser(employeeId, fullName) {

        Swal.fire({
            title: 'Delete User Account?',
            html: `
                You are about to permanently delete
                <br>
                <strong>${fullName}</strong> (${employeeId}).
                <br><br>
                This action <strong>cannot be undone</strong>.
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete account',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            showLoaderOnConfirm: false,
            allowOutsideClick: () => !Swal.isLoading(),
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Demo Mode',
                    text: `Account ${fullName} would be deleted (no backend action taken)`,
                    icon: 'info',
                    confirmButtonColor: '#1e293b',
                    confirmButtonText: 'Got it',
                });
            }
        });

    }


    // =====================================================
    // SWEETALERT2 CONFIRMATION: DEACTIVATE USER
    // =====================================================

    function deactivateUser(employeeId, fullName) {

        Swal.fire({
            title: 'Deactivate User?',
            html: `
                This will temporarily disable
                <br>
                <strong>${fullName}</strong> (${employeeId}).
                <br><br>
                They will not be able to log in until reactivated.
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d97706',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, deactivate',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Demo Mode',
                    text: `${fullName} would be deactivated (no backend action taken)`,
                    icon: 'info',
                    confirmButtonColor: '#1e293b',
                    confirmButtonText: 'Got it',
                });
            }
        });

    }


    // =====================================================
    // SWEETALERT2 CONFIRMATION: REACTIVATE USER
    // =====================================================

    function reactivateUser(employeeId, fullName) {

        Swal.fire({
            title: 'Reactivate User?',
            html: `
                This will restore access for
                <br>
                <strong>${fullName}</strong> (${employeeId}).
                <br><br>
                They will be able to log in again.
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, reactivate',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Demo Mode',
                    text: `${fullName} would be reactivated (no backend action taken)`,
                    icon: 'info',
                    confirmButtonColor: '#1e293b',
                    confirmButtonText: 'Got it',
                });
            }
        });

    }


    // =====================================================
    // VIEW USER (information modal)
    // =====================================================

    function viewUser(employeeId, fullName, role, status) {

        Swal.fire({
            title: 'User Details',
            html: `
                <div style="text-align: left; line-height: 2;">
                    <strong>Employee ID:</strong> ${employeeId}<br>
                    <strong>Full Name:</strong> ${fullName}<br>
                    <strong>Role:</strong> ${role}<br>
                    <strong>Status:</strong> ${status}<br>
                </div>
            `,
            icon: 'info',
            confirmButtonColor: '#1e293b',
            confirmButtonText: 'Close',
        });

    }


    // =====================================================
    // EDIT USER (placeholder)
    // =====================================================

    function editUser(employeeId) {

        Swal.fire({
            title: 'Edit User',
            text: `Edit mode for Employee ID ${employeeId} would open here (not yet implemented)`,
            icon: 'info',
            confirmButtonColor: '#1e293b',
            confirmButtonText: 'Close',
        });

    }


    // =====================================================
    // CREATE USER MODAL CONTROLS
    // =====================================================

    function openCreateUserModal() {
        const modal = document.getElementById('createUserModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeCreateUserModal() {
        const modal = document.getElementById('createUserModal');
        if (modal) modal.classList.add('hidden');
    }


    // =====================================================
    // CLOSE CREATE USER MODAL WITH ESCAPE KEY
    // =====================================================

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeCreateUserModal();
        }
    });


    // =====================================================
    // LIVE SEARCH (client-side filtering of table rows)
    // =====================================================

    document.addEventListener('DOMContentLoaded', function () {

        const searchInput = document.getElementById('userSearchInput');

        if (searchInput) {

            searchInput.addEventListener('input', function () {

                const query = this.value.toLowerCase().trim();

                const rows = document.querySelectorAll('tbody tr');

                rows.forEach(function (row) {

                    const text = row.textContent.toLowerCase();

                    if (text.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }

                });

            });

        }

    });

</script>

@endsection

