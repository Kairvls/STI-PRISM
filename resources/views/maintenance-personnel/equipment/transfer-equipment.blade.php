@extends ("layouts.maintenance-layout")

@section ("title", "Transfer & History")

@section ("content")
    <div class="space-y-6">
        <!-- PAGE HEADER -->
        <div>
            <h1 class="text-4xl font-black text-slate-900">
                Transfer & History
            </h1>

            <p class="mt-1 text-slate-500">Track equipment movements and maintenance history.</p>
        </div>

        <!-- TABLE -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-black">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="p-4 text-left">Equipment</th>

                            <th class="p-4 text-left">Category</th>

                            <th class="p-4 text-left">Current Room</th>

                            <th class="p-4 text-left">Status</th>

                            <th class="p-4 text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($equipment as $item)
                            <tr class="border-t transition hover:bg-slate-50">
                                <td class="p-4 font-semibold">
                                    {{ $item->equipment_name }}
                                </td>

                                <td class="p-4">
                                    {{ $item->equipment_category_name }}
                                </td>

                                <td class="p-4">{{ $item->room_name }}</td>

                                <td class="p-4">
                                    <span
                                        class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700"
                                    >
                                        {{ $item->equipment_inventory_status }}
                                    </span>
                                </td>

                                <td class="p-4">
                                    <div class="flex justify-center gap-2">
                                        <!-- TRANSFER -->

                                        <button
                                            type="button"
                                            onclick="openTransferModal(
                                        '{{ $item->equipment_id }}',
                                        '{{ $item->equipment_name }}',
                                        '{{ $item->room_name }}'
                                    )"
                                            class="rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700"
                                        >
                                            Transfer
                                        </button>

                                        <!-- HISTORY -->

                                        <button
                                            type="button"
                                            onclick="openHistoryModal(
                                        '{{ $item->equipment_id }}',
                                        '{{ $item->equipment_name }}'
                                    )"
                                            class="rounded-lg bg-indigo-600 px-3 py-2 text-white hover:bg-indigo-700"
                                        >
                                            History
                                        </button>

                                        <!-- Add Maintenance -->

                                        <button
                                            type="button"
                                            onclick="openMaintenanceModal(
                                        '{{ $item->equipment_id }}',
                                        '{{ $item->equipment_name }}'
                                    )"
                                            class="rounded-lg bg-amber-600 px-3 py-2 text-white hover:bg-amber-700"
                                        >
                                            Add Maintenance
                                        </button>

                                        <!-- Transfer Logs -->

                                        <button
                                            type="button"
                                            onclick="openTransferHistory(
                                        '{{ $item->equipment_id }}',
                                        '{{ $item->equipment_name }}'
                                    )"
                                            class="rounded-lg bg-slate-700 px-3 py-2 text-white"
                                        >
                                            Transfer Logs
                                        </button>
                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="p-10 text-center text-slate-500"
                                >
                                    No equipment found.
                                </td>
                            </tr>

                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- TRANSFER MODAL -->
    <!-- ===================================================== -->

    <div
        id="transferModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
    >
        <div class="w-full max-w-xl overflow-hidden rounded-3xl bg-white">
            <div class="flex items-center justify-between border-b px-6 py-5">
                <h2 class="text-2xl font-bold text-slate-900">
                    Transfer Equipment
                </h2>

                <button
                    type="button"
                    onclick="closeTransferModal()"
                    class="text-2xl text-slate-500"
                >
                    &times;
                </button>
            </div>

            <form action="/maintenance/equipment/transfer" method="POST">
                @csrf

                <input
                    type="hidden"
                    id="transfer_equipment_id"
                    name="equipment_id"
                />

                <div class="space-y-4 p-6">
                    <div>
                        <label class="text-sm text-slate-500">
                            Equipment
                        </label>

                        <p
                            id="transferEquipmentName"
                            class="text-lg font-semibold text-black"
                        ></p>
                    </div>

                    <div>
                        <label class="text-sm text-slate-500">
                            Current Room
                        </label>

                        <p
                            id="transferCurrentRoom"
                            class="font-semibold text-black"
                        ></p>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-black"
                        >
                            Transfer To
                        </label>

                        <select
                            name="room_id"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black"
                        >
                            <option value="">Select New Room</option>

                            @foreach ($rooms as $room)
                                <option value="{{ $room->room_id }}">
                                    {{ $room->room_name }}
                                </option>

                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-black"
                        >
                            Remarks
                        </label>

                        <textarea
                            name="remarks"
                            rows="3"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black"
                        ></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t px-6 py-4">
                    <button
                        type="button"
                        onclick="closeTransferModal()"
                        class="rounded-xl border px-4 py-2"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-white"
                    >
                        Transfer Equipment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- HISTORY MODAL -->
    <!-- ===================================================== -->

    <div
        id="historyModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
    >
        <div class="w-full max-w-4xl overflow-hidden rounded-3xl bg-white">
            <div class="flex items-center justify-between border-b px-6 py-5">
                <h2
                    id="historyEquipmentName"
                    class="text-2xl font-bold text-slate-900"
                >
                    Maintenance History
                </h2>

                <button
                    onclick="closeHistoryModal()"
                    class="text-2xl text-slate-500"
                >
                    &times;
                </button>
            </div>

            <div class="p-6">
                <div id="historyContent">
                    <div class="py-10 text-center text-slate-500">
                        No maintenance history found.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- ADD MAINTENANCE MODAL -->
    <!-- ===================================================== -->

    <div
        id="maintenanceModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
    >
        <div class="w-full max-w-2xl overflow-hidden rounded-3xl bg-white">
            <div class="flex justify-between border-b px-6 py-5">
                <h2
                    id="maintenanceEquipmentName"
                    class="text-2xl font-bold text-black"
                >
                    Add Maintenance Record
                </h2>

                <button type="button" onclick="closeMaintenanceModal()">
                    &times;
                </button>
            </div>

            <form
                action="/maintenance/equipment/history/store"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf

                <input
                    type="hidden"
                    id="maintenance_equipment_id"
                    name="equipment_id"
                />

                <div class="space-y-4 p-6">
                    <div>
                        <label class="text-black">Status</label>

                        <select
                            name="status"
                            class="w-full rounded-xl border p-3 text-black"
                        >
                            <option>Pending</option>
                            <option>Processing</option>
                            <option>Resolved</option>
                            <option>For Replacement</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-black">Findings</label>

                        <textarea
                            name="findings"
                            rows="3"
                            class="w-full rounded-xl border p-3 text-black"
                        ></textarea>
                    </div>

                    <div>
                        <label class="text-black">Repair Action</label>

                        <textarea
                            name="repair_action"
                            rows="3"
                            class="w-full rounded-xl border p-3 text-black"
                        ></textarea>
                    </div>

                    <div>
                        <label class="text-black">Proof Image</label>

                        <input
                            type="file"
                            name="proof_image"
                            class="w-full rounded-xl border p-3 text-black"
                        />
                    </div>
                </div>

                <div class="flex justify-end border-t p-4">
                    <button class="rounded-xl bg-blue-600 px-5 py-2 text-white">
                        Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function openHistoryModal(id, name) {
            document.getElementById("historyEquipmentName").innerText =
                name + " History";

            document.getElementById("historyContent").innerHTML = `
        <div class="text-center py-10">
            Loading...
        </div>
    `;

            document.getElementById("historyModal").classList.remove("hidden");

            document.getElementById("historyModal").classList.add("flex");

            try {
                const response = await fetch(
                    "/maintenance/equipment/history/" + id,
                );

                const data = await response.json();

                if (data.length === 0) {
                    document.getElementById("historyContent").innerHTML = `
                <div class="text-center text-slate-500 py-10">
                    No maintenance history found.
                </div>
            `;

                    return;
                }

                let html = "";

                data.forEach((item) => {
                    html += `

                <div class="border-l-4 border-indigo-500 pl-4 mb-6">

                    <div class="font-semibold text-indigo-600">

                        <span
                        class="inline-block px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-semibold">

                        ${item.equipment_maintenance_status}

                        </span>

                    </div>

                    <div class="text-sm text-slate-500 mb-2">

                        ${item.equipment_maintenance_created_at ?? ""}

                    </div>

                    <div class="mb-2">

                        <strong>Findings:</strong><br>

                        ${item.equipment_maintenance_findings ?? "N/A"}

                    </div>

                    <div>

                            <strong>Repair Action:</strong><br>

                            ${item.equipment_maintenance_repair_action ?? "N/A"}

                        </div>

                        ${
                            item.equipment_maintenance_proof_image
                                ? `
                        <div class="mt-3">

                            <a
                                href="/storage/${item.equipment_maintenance_proof_image}"
                                target="_blank"
                                class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 font-medium">

                                📷 View Proof Image

                            </a>

                        </div>
                        `
                                : ""
                        }

                </div>

            `;
                });

                document.getElementById("historyContent").innerHTML = html;
            } catch (error) {
                document.getElementById("historyContent").innerHTML = `
            <div class="text-center text-red-500 py-10">
                Failed to load history.
            </div>
        `;
            }
        }

        function closeHistoryModal() {
            document.getElementById("historyModal").classList.add("hidden");

            document.getElementById("historyModal").classList.remove("flex");
        }

        function openTransferModal(id, name, room) {
            document.getElementById("transfer_equipment_id").value = id;

            document.getElementById("transferEquipmentName").innerText = name;

            document.getElementById("transferCurrentRoom").innerText = room;

            document.getElementById("transferModal").classList.remove("hidden");

            document.getElementById("transferModal").classList.add("flex");
        }

        function closeTransferModal() {
            document.getElementById("transferModal").classList.add("hidden");

            document.getElementById("transferModal").classList.remove("flex");
        }

        function openMaintenanceModal(id, name) {
            document.getElementById("maintenance_equipment_id").value = id;

            document.getElementById("maintenanceEquipmentName").innerText =
                name + " Maintenance";

            document
                .getElementById("maintenanceModal")
                .classList.remove("hidden");

            document.getElementById("maintenanceModal").classList.add("flex");
        }

        function closeMaintenanceModal() {
            document.getElementById("maintenanceModal").classList.add("hidden");

            document
                .getElementById("maintenanceModal")
                .classList.remove("flex");
        }
    </script>

@endsection
