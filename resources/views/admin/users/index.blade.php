@extends('layouts.admin-layout')

@section('title', 'User Management')

@section('content')

<div class="flex justify-between mb-6">

    <h1 class="text-3xl font-bold">
        User Management
    </h1>

    <button 
        onclick="openCreateUserModal()"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition"
    >
        Create Account
    </button>

</div>

<table class="w-full bg-white rounded shadow">

    <thead class="bg-gray-200">

        <tr>
            <th class="p-3 text-left">Employee ID</th>
            <th class="p-3 text-left">Full Name</th>
            <th class="p-3 text-left">Role</th>
            <th class="p-3 text-left">Action</th>
        </tr>

    </thead>

    <tbody>

        <tr class="border-t">

            <td class="p-3">2026-001</td>
            <td class="p-3">Juan Dela Cruz</td>
            <td class="p-3">Maintenance Personnel</td>

            <td class="p-3">

                <button class="text-blue-600">
                    Edit
                </button>

            </td>

        </tr>

    </tbody>

</table>

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
                <div class="px-6 py-5 space-y-4 max-h-[calc(100vh-280px)] overflow-y-auto">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Employee ID</label>
                        <input 
                            type="text"
                            name="employee_id"
                            class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Full Name</label>
                        <input 
                            type="text"
                            name="full_name"
                            class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Username</label>
                        <input 
                            type="text"
                            name="username"
                            class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Email</label>
                        <input 
                            type="email"
                            name="email"
                            class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Contact Number</label>
                        <input 
                            type="text"
                            name="contact_number"
                            class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Password</label>
                        <input 
                            type="password"
                            name="password"
                            class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Role</label>
                        <select 
                            name="role"
                            class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-blue-500"
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
                    <button type="button" class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950" onclick="closeCreateUserModal()">Cancel</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openCreateUserModal() {
        const modal = document.getElementById('createUserModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeCreateUserModal() {
        const modal = document.getElementById('createUserModal');
        if (modal) modal.classList.add('hidden');
    }
</script>

@endsection