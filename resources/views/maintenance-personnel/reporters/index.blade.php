@extends('layouts.maintenance-layout')

@section('content')

<div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">

    @if(session('success'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl flex items-center gap-2 text-sm font-medium">
            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">

        <div class="bg-blue-50/60 border border-blue-100 rounded-2xl p-5 transition hover:shadow-md hover:shadow-blue-50">
            <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-1">Total Reporters</p>
            <h2 class="text-3xl font-black text-blue-900">
                {{ $reporters->count() }}
            </h2>
        </div>

        <div class="bg-emerald-50/60 border border-emerald-100 rounded-2xl p-5 transition hover:shadow-md hover:shadow-emerald-50">
            <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mb-1">With Email</p>
            <h2 class="text-3xl font-black text-emerald-900">
                {{ $reporters->whereNotNull('reporter_email_address')->count() }}
            </h2>
        </div>

        <div class="bg-purple-50/60 border border-purple-100 rounded-2xl p-5 transition hover:shadow-md hover:shadow-purple-50">
            <p class="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-1">With Contact</p>
            <h2 class="text-3xl font-black text-purple-900">
                {{ $reporters->whereNotNull('reporter_contact_number')->count() }}
            </h2>
        </div>

        <div class="bg-amber-50/60 border border-amber-100 rounded-2xl p-5 transition hover:shadow-md hover:shadow-amber-50">
            <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider mb-1">Registered</p>
            <h2 class="text-3xl font-black text-amber-900">
                {{ $reporters->count() }}
            </h2>
        </div>

    </div>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Reporters</h1>
            <p class="text-sm text-slate-500">Manage directory records and system contact profiles</p>
        </div>
        <button
            onclick="openCreateModal()"
            class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white px-5 py-3 rounded-xl font-medium shadow-sm shadow-blue-200 transition text-sm flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Reporter
        </button>
    </div>

    <div class="mb-6 relative">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
        <input
            type="text"
            id="searchInput"
            placeholder="Search reporters by name, ID, or email details..."
            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 pl-11 pr-4 text-sm placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition">
    </div>

    <div class="overflow-x-auto border border-slate-100 rounded-2xl">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-600 font-semibold">
                    <th class="p-4 font-semibold">Employee ID</th>
                    <th class="p-4 font-semibold">Name</th>
                    <th class="p-4 font-semibold">Email</th>
                    <th class="p-4 font-semibold">Contact</th>
                    <th class="p-4 font-semibold text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="reporterTable" class="divide-y divide-slate-100 text-slate-700">
                @forelse($reporters as $reporter)
                <tr class="reporter-row hover:bg-slate-50/50 transition">
                    <td class="p-4 font-mono tracking-wider font-medium text-sm text-slate-500">{{ $reporter->reporter_employee_id }}</td>
                    <td class="p-4 font-semibold text-slate-900">{{ $reporter->reporter_full_name }}</td>
                    <td class="p-4 text-slate-600">{{ $reporter->reporter_email_address ?? '—' }}</td>
                    <td class="p-4 text-slate-600">{{ $reporter->reporter_contact_number ?? '—' }}</td>
                    <td class="p-4 text-center">
                        <div class="flex justify-center items-center gap-1.5">
                            <button
                                onclick="viewReporter(
                                    '{{ $reporter->reporter_employee_id }}',
                                    '{{ $reporter->reporter_full_name }}',
                                    '{{ $reporter->reporter_email_address }}',
                                    '{{ $reporter->reporter_contact_number }}'
                                )"
                                class="bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                View
                            </button>
                            <button
                                onclick="editReporter(
                                    '{{ $reporter->reporter_id }}',
                                    '{{ $reporter->reporter_employee_id }}',
                                    '{{ $reporter->reporter_full_name }}',
                                    '{{ $reporter->reporter_email_address }}',
                                    '{{ $reporter->reporter_contact_number }}'
                                )"
                                class="bg-amber-50 text-amber-600 hover:bg-amber-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                Edit
                            </button>
                            <button
                                onclick="openDeleteModal(
                                    '{{ $reporter->reporter_id }}'
                                )"
                                class="bg-rose-50 text-rose-600 hover:bg-rose-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-12 text-slate-400 bg-slate-50/20">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <span>No reporters found.</span>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

<div id="createModal" class="fixed inset-0 hidden bg-slate-900/40 backdrop-blur-sm items-center justify-center z-50 p-4">
    <form
        action="/maintenance/reporters/store"
        method="POST"
        class="bg-white rounded-3xl p-6 w-full max-w-lg shadow-xl border border-slate-100 transform transition-all">
        @csrf

        <h2 class="text-xl font-bold text-slate-900 mb-5">Add New Reporter</h2>

        <div class="space-y-4 mb-6">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Employee ID</label>
                <input
                    name="employee_id"
                    placeholder="e.g., OMC****F"
                    class="w-full border border-slate-200 p-3 text-black rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition"
                    required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Full Name</label>
                <input
                    name="full_name"
                    placeholder="e.g., Joseph Diaz"
                    class="w-full border border-slate-200 p-3 text-black rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition"
                    required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Email Address</label>
                <input
                    type="email"
                    name="email"
                    placeholder="diaz.doe@sti.edu.ph"
                    class="w-full border border-slate-200 p-3 text-black rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Contact Number</label>
                <input
                    type="text"
                    name="contact"
                    placeholder="09103102012"
                    maxlength="11"
                    pattern="[0-9]*"
                    inputmode="numeric"
                    class="w-full border border-slate-200 p-3 text-black rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition">
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-50 pt-4">
            <button
                type="button"
                onclick="closeCreateModal()"
                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-700 text-sm font-medium rounded-xl transition">
                Cancel
            </button>
            <button
                type="submit"
                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-medium rounded-xl shadow-sm transition">
                Save Record
            </button>
        </div>
    </form>
</div>

<div id="viewModal" class="fixed inset-0 hidden bg-slate-900/40 backdrop-blur-sm items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl p-6 w-full max-w-lg shadow-xl border border-slate-100">
        <h2 class="text-xl font-bold text-slate-900 mb-5">Reporter Profile Details</h2>

        <div id="reporterDetails" class="bg-slate-50 border border-slate-100 rounded-2xl p-5 text-sm text-slate-700"></div>

        <div class="mt-6 flex justify-end border-t border-slate-50 pt-4">
            <button
                onclick="closeViewModal()"
                class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-sm transition">
                Close View
            </button>
        </div>
    </div>
</div>

<div id="editModal" class="fixed inset-0 hidden bg-slate-900/40 backdrop-blur-sm items-center justify-center z-50 p-4">
    <form
        action="/maintenance/reporters/update"
        method="POST"
        class="bg-white rounded-3xl p-6 w-full max-w-lg shadow-xl border border-slate-100">
        @csrf

        <input type="hidden" name="reporter_id" id="editReporterId">

        <h2 class="text-xl font-bold text-slate-900 mb-5">Modify Reporter Profile</h2>

        <div class="space-y-4 mb-6">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Employee ID</label>
                <input
                    type="text"
                    name="employee_id"
                    id="editEmployeeId"
                    class="w-full border border-slate-200 p-3 text-black rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition"
                    required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Full Name</label>
                <input
                    type="text"
                    name="full_name"
                    id="editFullName"
                    class="w-full border border-slate-200 p-3 text-black rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition"
                    required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Email Address</label>
                <input
                    type="email"
                    name="email"
                    id="editEmail"
                    class="w-full border border-slate-200 p-3 text-black rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Contact Number</label>
                <input
                    type="text"
                    name="contact"
                    id="editContact"
                    class="w-full border border-slate-200 p-3 text-black rounded-xl text-sm focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition">
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-50 pt-4">
            <button
                type="button"
                onclick="closeEditModal()"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-medium transition">
                Cancel
            </button>
            <button
                type="submit"
                class="bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-sm shadow-amber-100 transition">
                Apply Changes
            </button>
        </div>
    </form>
</div>

<div id="deleteModal" class="fixed inset-0 hidden bg-slate-900/40 backdrop-blur-sm items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl p-6 w-full max-w-md shadow-xl border border-slate-100">
        <h2 class="text-xl font-bold text-rose-600 mb-2">Delete Reporter</h2>
        <p class="text-sm text-slate-600 mb-6">
            Are you sure you want to completely remove this reporter profile? This adjustment cannot be reversed.
        </p>

        <form action="/maintenance/reporters/delete" method="POST">
            @csrf
            <input type="hidden" name="reporter_id" id="deleteReporterId">

            <div class="flex justify-end gap-3 border-t border-slate-50 pt-4">
                <button
                    type="button"
                    onclick="closeDeleteModal()"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl text-sm font-medium transition">
                    Keep Record
                </button>
                <button
                    type="submit"
                    class="bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-sm transition">
                    Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const createModal = document.getElementById('createModal');
const viewModal = document.getElementById('viewModal');
const editModal = document.getElementById('editModal');
const deleteModal = document.getElementById('deleteModal');

function openCreateModal() {
    createModal.classList.remove('hidden');
    createModal.classList.add('flex');
}

function closeCreateModal() {
    createModal.classList.add('hidden');
    createModal.classList.remove('flex');
}

function closeViewModal() {
    viewModal.classList.add('hidden');
    viewModal.classList.remove('flex');
}

function closeEditModal() {
    editModal.classList.add('hidden');
    editModal.classList.remove('flex');
}

function closeDeleteModal() {
    deleteModal.classList.add('hidden');
    deleteModal.classList.remove('flex');
}

function viewReporter(employee, name, email, contact) {
    document.getElementById('reporterDetails').innerHTML = `
        <div class="grid grid-cols-1 gap-4">
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Employee ID</span>
                <span class="font-mono font-semibold text-slate-900 bg-slate-200/50 px-2 py-0.5 rounded text-xs">${employee || '—'}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Full Name</span>
                <span class="font-semibold text-slate-900">${name || '—'}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Email Address</span>
                <span class="text-slate-700">${email || '—'}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Contact Number</span>
                <span class="text-slate-700">${contact || '—'}</span>
            </div>
        </div>
    `;
    viewModal.classList.remove('hidden');
    viewModal.classList.add('flex');
}

function editReporter(id, employee, name, email, contact) {
    document.getElementById('editReporterId').value = id;
    document.getElementById('editEmployeeId').value = employee;
    document.getElementById('editFullName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editContact').value = contact;

    editModal.classList.remove('hidden');
    editModal.classList.add('flex');
}

function openDeleteModal(id) {
    document.getElementById('deleteReporterId').value = id;
    deleteModal.classList.remove('hidden');
    deleteModal.classList.add('flex');
}

document.getElementById('searchInput').addEventListener('keyup', function() {
    let value = this.value.toLowerCase();
    document.querySelectorAll('.reporter-row').forEach(function(row) {
        row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
    });
});
</script>

@endsection