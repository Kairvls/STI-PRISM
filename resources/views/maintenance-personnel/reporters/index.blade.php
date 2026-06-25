@extends ("layouts.maintenance-layout")

@section ("content")
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
        @if (session("success"))
            <div
                class="mb-6 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
            >
                <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{
                    session(
                        "success",
                    )
                }}
            </div>
        @endif

        <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div
                class="rounded-2xl border border-blue-100 bg-blue-50/60 p-5 transition hover:shadow-md hover:shadow-blue-50"
            >
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-blue-600">Total Reporters</p>
                <h2 class="text-3xl font-black text-blue-900">
                    {{ $reporters->count() }}
                </h2>
            </div>

            <div
                class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-5 transition hover:shadow-md hover:shadow-emerald-50"
            >
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-emerald-600">With Email</p>
                <h2 class="text-3xl font-black text-emerald-900">
                    {{
                        $reporters
                            ->whereNotNull("reporter_email_address")
                            ->count()
                    }}
                </h2>
            </div>

            <div
                class="rounded-2xl border border-purple-100 bg-purple-50/60 p-5 transition hover:shadow-md hover:shadow-purple-50"
            >
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-purple-600">With Contact</p>
                <h2 class="text-3xl font-black text-purple-900">
                    {{
                        $reporters
                            ->whereNotNull("reporter_contact_number")
                            ->count()
                    }}
                </h2>
            </div>

            <div
                class="rounded-2xl border border-amber-100 bg-amber-50/60 p-5 transition hover:shadow-md hover:shadow-amber-50"
            >
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-amber-600">Registered</p>
                <h2 class="text-3xl font-black text-amber-900">
                    {{ $reporters->count() }}
                </h2>
            </div>
        </div>

        <div
            class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Reporters
                </h1>
                <p class="text-sm text-slate-500">Manage directory records and system contact profiles</p>
            </div>
            <button
                onclick="openCreateModal()"
                class="flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-medium text-white shadow-sm shadow-blue-200 transition hover:bg-blue-700 active:bg-blue-800"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Reporter
            </button>
        </div>

        <div class="relative mb-6">
            <div
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input
                type="text"
                id="searchInput"
                placeholder="Search reporters by name, ID, or email details..."
                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm placeholder-slate-400 transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/10"
            />
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-100">
            <table class="w-full border-collapse text-left text-sm">
                <thead>
                    <tr
                        class="border-b border-slate-100 bg-slate-50/70 font-semibold text-slate-600"
                    >
                        <th class="p-4 font-semibold">Employee ID</th>
                        <th class="p-4 font-semibold">Name</th>
                        <th class="p-4 font-semibold">Email</th>
                        <th class="p-4 font-semibold">Contact</th>
                        <th class="p-4 text-center font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody
                    id="reporterTable"
                    class="divide-y divide-slate-100 text-slate-700"
                >
                    @forelse ($reporters as $reporter)
                        <tr
                            class="reporter-row transition hover:bg-slate-50/50"
                        >
                            <td
                                class="p-4 font-mono text-sm font-medium tracking-wider text-slate-500"
                            >
                                {{ $reporter->reporter_employee_id }}
                            </td>
                            <td class="p-4 font-semibold text-slate-900">
                                {{ $reporter->reporter_full_name }}
                            </td>
                            <td class="p-4 text-slate-600">
                                {{
                                    $reporter->reporter_email_address ??
                                        "—"
                                }}
                            </td>
                            <td class="p-4 text-slate-600">
                                {{
                                    $reporter->reporter_contact_number ??
                                        "—"
                                }}
                            </td>
                            <td class="p-4 text-center">
                                <div
                                    class="flex items-center justify-center gap-1.5"
                                >
                                    <button
                                        onclick="viewReporter(
                                    '{{ $reporter->reporter_employee_id }}',
                                    '{{ $reporter->reporter_full_name }}',
                                    '{{ $reporter->reporter_email_address }}',
                                    '{{ $reporter->reporter_contact_number }}'
                                )"
                                        class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-100"
                                    >
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
                                        class="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-600 transition hover:bg-amber-100"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        onclick="openDeleteModal(
                                    '{{ $reporter->reporter_id }}'
                                )"
                                        class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-100"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="5"
                                class="bg-slate-50/20 py-12 text-center text-slate-400"
                            >
                                <div
                                    class="flex flex-col items-center justify-center gap-2"
                                >
                                    <svg class="h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    <span>No reporters found.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div
        id="createModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
    >
        <form
            action="/maintenance/reporters/store"
            method="POST"
            class="w-full max-w-lg transform rounded-3xl border border-slate-100 bg-white p-6 shadow-xl transition-all"
        >
            @csrf

            <h2 class="mb-5 text-xl font-bold text-slate-900">
                Add New Reporter
            </h2>

            <div class="mb-6 space-y-4">
                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500"
                        >Employee ID</label
                    >
                    <input
                        name="employee_id"
                        placeholder="e.g., OMC****F"
                        class="w-full rounded-xl border border-slate-200 p-3 text-sm text-black transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                        required
                    />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500"
                        >Full Name</label
                    >
                    <input
                        name="full_name"
                        placeholder="e.g., Joseph Diaz"
                        class="w-full rounded-xl border border-slate-200 p-3 text-sm text-black transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                        required
                    />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500"
                        >Email Address</label
                    >
                    <input
                        type="email"
                        name="email"
                        placeholder="diaz.doe@sti.edu.ph"
                        class="w-full rounded-xl border border-slate-200 p-3 text-sm text-black transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                    />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500"
                        >Contact Number</label
                    >
                    <input
                        type="text"
                        name="contact"
                        placeholder="09103102012"
                        maxlength="11"
                        pattern="[0-9]*"
                        inputmode="numeric"
                        class="w-full rounded-xl border border-slate-200 p-3 text-sm text-black transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                    />
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-50 pt-4">
                <button
                    type="button"
                    onclick="closeCreateModal()"
                    class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200 active:bg-slate-300"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 active:bg-blue-800"
                >
                    Save Record
                </button>
            </div>
        </form>
    </div>

    <div
        id="viewModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
    >
        <div
            class="w-full max-w-lg rounded-3xl border border-slate-100 bg-white p-6 shadow-xl"
        >
            <h2 class="mb-5 text-xl font-bold text-slate-900">
                Reporter Profile Details
            </h2>

            <div
                id="reporterDetails"
                class="rounded-2xl border border-slate-100 bg-slate-50 p-5 text-sm text-slate-700"
            ></div>

            <div class="mt-6 flex justify-end border-t border-slate-50 pt-4">
                <button
                    onclick="closeViewModal()"
                    class="rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-900"
                >
                    Close View
                </button>
            </div>
        </div>
    </div>

    <div
        id="editModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
    >
        <form
            action="/maintenance/reporters/update"
            method="POST"
            class="w-full max-w-lg rounded-3xl border border-slate-100 bg-white p-6 shadow-xl"
        >
            @csrf

            <input type="hidden" name="reporter_id" id="editReporterId" />

            <h2 class="mb-5 text-xl font-bold text-slate-900">
                Modify Reporter Profile
            </h2>

            <div class="mb-6 space-y-4">
                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500"
                        >Employee ID</label
                    >
                    <input
                        type="text"
                        name="employee_id"
                        id="editEmployeeId"
                        class="w-full rounded-xl border border-slate-200 p-3 text-sm text-black transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                        required
                    />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500"
                        >Full Name</label
                    >
                    <input
                        type="text"
                        name="full_name"
                        id="editFullName"
                        class="w-full rounded-xl border border-slate-200 p-3 text-sm text-black transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                        required
                    />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500"
                        >Email Address</label
                    >
                    <input
                        type="email"
                        name="email"
                        id="editEmail"
                        class="w-full rounded-xl border border-slate-200 p-3 text-sm text-black transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                    />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-500"
                        >Contact Number</label
                    >
                    <input
                        type="text"
                        name="contact"
                        id="editContact"
                        class="w-full rounded-xl border border-slate-200 p-3 text-sm text-black transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                    />
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-50 pt-4">
                <button
                    type="button"
                    onclick="closeEditModal()"
                    class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-medium text-white shadow-sm shadow-amber-100 transition hover:bg-amber-600 active:bg-amber-700"
                >
                    Apply Changes
                </button>
            </div>
        </form>
    </div>

    <div
        id="deleteModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
    >
        <div
            class="w-full max-w-md rounded-3xl border border-slate-100 bg-white p-6 shadow-xl"
        >
            <h2 class="mb-2 text-xl font-bold text-rose-600">
                Delete Reporter
            </h2>
            <p class="mb-6 text-sm text-slate-600">Are you sure you want to completely remove this reporter profile? This adjustment cannot be reversed.</p>

            <form action="/maintenance/reporters/delete" method="POST">
                @csrf
                <input type="hidden" name="reporter_id" id="deleteReporterId" />

                <div
                    class="flex justify-end gap-3 border-t border-slate-50 pt-4"
                >
                    <button
                        type="button"
                        onclick="closeDeleteModal()"
                        class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200"
                    >
                        Keep Record
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-rose-700 active:bg-rose-800"
                    >
                        Confirm Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const createModal = document.getElementById("createModal");
        const viewModal = document.getElementById("viewModal");
        const editModal = document.getElementById("editModal");
        const deleteModal = document.getElementById("deleteModal");

        function openCreateModal() {
            createModal.classList.remove("hidden");
            createModal.classList.add("flex");
        }

        function closeCreateModal() {
            createModal.classList.add("hidden");
            createModal.classList.remove("flex");
        }

        function closeViewModal() {
            viewModal.classList.add("hidden");
            viewModal.classList.remove("flex");
        }

        function closeEditModal() {
            editModal.classList.add("hidden");
            editModal.classList.remove("flex");
        }

        function closeDeleteModal() {
            deleteModal.classList.add("hidden");
            deleteModal.classList.remove("flex");
        }

        function viewReporter(employee, name, email, contact) {
            document.getElementById("reporterDetails").innerHTML = `
        <div class="grid grid-cols-1 gap-4">
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Employee ID</span>
                <span class="font-mono font-semibold text-slate-900 bg-slate-200/50 px-2 py-0.5 rounded text-xs">${employee || "—"}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Full Name</span>
                <span class="font-semibold text-slate-900">${name || "—"}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Email Address</span>
                <span class="text-slate-700">${email || "—"}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Contact Number</span>
                <span class="text-slate-700">${contact || "—"}</span>
            </div>
        </div>
    `;
            viewModal.classList.remove("hidden");
            viewModal.classList.add("flex");
        }

        function editReporter(id, employee, name, email, contact) {
            document.getElementById("editReporterId").value = id;
            document.getElementById("editEmployeeId").value = employee;
            document.getElementById("editFullName").value = name;
            document.getElementById("editEmail").value = email;
            document.getElementById("editContact").value = contact;

            editModal.classList.remove("hidden");
            editModal.classList.add("flex");
        }

        function openDeleteModal(id) {
            document.getElementById("deleteReporterId").value = id;
            deleteModal.classList.remove("hidden");
            deleteModal.classList.add("flex");
        }

        document
            .getElementById("searchInput")
            .addEventListener("keyup", function () {
                let value = this.value.toLowerCase();
                document
                    .querySelectorAll(".reporter-row")
                    .forEach(function (row) {
                        row.style.display = row.innerText
                            .toLowerCase()
                            .includes(value)
                            ? ""
                            : "none";
                    });
            });
    </script>

@endsection
