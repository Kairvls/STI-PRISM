@extends ("layouts.maintenance-layout")

@section ("content")
    <div class="rounded-3xl bg-white p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-black">Disposal Records</h1>

            <button
                onclick="openDisposeModal()"
                class="rounded-xl bg-red-600 px-5 py-3 text-white hover:bg-red-700"
            >
                Dispose Equipment
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-black">
                <thead>
                    <tr class="border-b">
                        <th class="p-3 text-left">Equipment</th>

                        <th class="p-3 text-left">Category</th>

                        <th class="p-3 text-left">Condition</th>

                        <th class="p-3 text-left">Reason</th>

                        <th class="p-3 text-left">Disposal Area</th>

                        <th class="p-3 text-left">Disposed Date</th>

                        <th class="p-3 text-center">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($disposals as $record)
                        <tr class="border-b">
                            <td class="p-3">{{ $record->equipment_name }}</td>

                            <td class="p-3">
                                {{ $record->equipment_category_name }}
                            </td>

                            <td class="p-3">
                                {{ $record->equipment_condition_status }}
                            </td>

                            <td class="p-3">{{ $record->disposal_reason }}</td>

                            <td class="p-3">
                                {{ $record->disposal_area_location }}
                            </td>

                            <td class="p-3">
                                {{
                                    \Carbon\Carbon::parse(
                                        $record->disposal_disposed_at,
                                    )->format("M d, Y h:i A")
                                }}
                            </td>

                            <td class="p-3">
                                <div class="flex justify-center gap-2">
                                    <button
                                        onclick="viewDisposal(
                                    '{{ $record->equipment_name }}',
                                    '{{ $record->equipment_category_name }}',
                                    '{{ $record->equipment_condition_status }}',
                                    '{{ $record->disposal_reason }}',
                                    '{{ $record->disposal_area_location }}',
                                    '{{ $record->disposal_disposed_at }}'
                                )"
                                        class="rounded-lg bg-indigo-600 px-3 py-2 text-white"
                                    >
                                        View
                                    </button>

                                    <button
                                        onclick="openDeleteModal(
                                    '{{ $record->disposal_record_id }}'
                                )"
                                        class="rounded-lg bg-red-600 px-3 py-2 text-white"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td
                                colspan="7"
                                class="py-10 text-center text-slate-500"
                            >
                                No disposal records found.
                            </td>
                        </tr>

                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- DISPOSE MODAL -->

    <div
        id="disposeModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50"
    >
        <div class="w-full max-w-xl rounded-3xl bg-white p-6">
            <h2 class="mb-6 text-2xl font-bold text-black">
                Dispose Equipment
            </h2>

            <form action="/maintenance/disposal/store" method="POST">
                @csrf

                <div class="mb-4">
                    <label class="mb-2 block text-black"> Equipment </label>

                    <select
                        name="equipment_id"
                        class="w-full rounded-xl border p-3 text-black"
                    >
                        @foreach ($equipment as $item)
                            <option value="{{ $item->equipment_id }}">
                                {{ $item->equipment_name }}
                            </option>

                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-black">
                        Disposal Reason
                    </label>

                    <select
                        name="reason"
                        class="w-full rounded-xl border p-3 text-black"
                    >
                        <option>Beyond Repair</option>

                        <option>Obsolete</option>

                        <option>Damaged</option>

                        <option>Lost</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-black"> Disposal Area </label>

                    <input
                        type="text"
                        name="location"
                        class="w-full rounded-xl border p-3 text-black"
                    />
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeDisposeModal()"
                        class="rounded-lg bg-slate-300 px-4 py-2"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-white"
                    >
                        Dispose
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- VIEW DISPOSAL MODAL -->
    <!-- ===================================================== -->

    <div
        id="viewModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50"
    >
        <div class="w-full max-w-xl rounded-3xl bg-white p-6">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-black">Disposal Details</h2>

                <button
                    onclick="closeViewModal()"
                    class="text-2xl text-slate-500"
                >
                    ×
                </button>
            </div>

            <div id="viewDisposalDetails" class="space-y-3 text-black"></div>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- DELETE DISPOSAL MODAL -->
    <!-- ===================================================== -->

    <div
        id="deleteModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50"
    >
        <div class="w-full max-w-md rounded-3xl bg-white p-6">
            <h2 class="mb-4 text-2xl font-bold text-black">
                Delete Disposal Record
            </h2>

            <p class="mb-6 text-slate-600">Are you sure you want to delete this disposal record?</p>

            <form action="/maintenance/disposal/delete" method="POST">
                @csrf

                @method ("DELETE")

                <input type="hidden" id="deleteDisposalId" name="disposal_id" />

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeDeleteModal()"
                        class="rounded-lg bg-slate-300 px-4 py-2"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-white"
                    >
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDisposeModal() {
            document.getElementById("disposeModal").classList.remove("hidden");

            document.getElementById("disposeModal").classList.add("flex");
        }

        function closeDisposeModal() {
            document.getElementById("disposeModal").classList.add("hidden");

            document.getElementById("disposeModal").classList.remove("flex");
        }

        function viewDisposal(
            equipment,
            category,
            condition,
            reason,
            location,
            date,
        ) {
            document.getElementById("viewDisposalDetails").innerHTML = `

        <p>
            <strong>Equipment:</strong>
            ${equipment}
        </p>

        <p>
            <strong>Category:</strong>
            ${category}
        </p>

        <p>
            <strong>Condition:</strong>
            ${condition}
        </p>

        <p>
            <strong>Reason:</strong>
            ${reason}
        </p>

        <p>
            <strong>Disposal Area:</strong>
            ${location}
        </p>

        <p>
            <strong>Disposed Date:</strong>
            ${date}
        </p>

    `;

            document.getElementById("viewModal").classList.remove("hidden");

            document.getElementById("viewModal").classList.add("flex");
        }

        function closeViewModal() {
            document.getElementById("viewModal").classList.add("hidden");

            document.getElementById("viewModal").classList.remove("flex");
        }

        function openDeleteModal(id) {
            document.getElementById("deleteDisposalId").value = id;

            document.getElementById("deleteModal").classList.remove("hidden");

            document.getElementById("deleteModal").classList.add("flex");
        }

        function closeDeleteModal() {
            document.getElementById("deleteModal").classList.add("hidden");

            document.getElementById("deleteModal").classList.remove("flex");
        }
    </script>

@endsection
