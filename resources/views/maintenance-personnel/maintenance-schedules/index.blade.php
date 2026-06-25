@extends ("layouts.maintenance-layout")

@section ("content")
    <div class="rounded-3xl bg-white p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-black">Maintenance Schedules</h1>

            <button
                onclick="openScheduleModal()"
                class="rounded-xl bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
            >
                + Schedule Maintenance
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-black">
                <thead>
                    <tr class="border-b">
                        <th class="p-3 text-left">Equipment</th>

                        <th class="p-3 text-left">Title</th>

                        <th class="p-3 text-left">Frequency</th>

                        <th class="p-3 text-left">Next Date</th>

                        <th class="p-3 text-left">Last Date</th>

                        <th class="p-3 text-left">Status</th>

                        <th class="p-3 text-center">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr class="border-b">
                            <td class="p-3">{{ $schedule->equipment_name }}</td>

                            <td class="p-3">
                                {{ $schedule->maintenance_schedule_title }}
                            </td>

                            <td class="p-3">
                                {{ $schedule->maintenance_schedule_frequency }}
                            </td>

                            <td class="p-3">
                                {{ $schedule->maintenance_schedule_next_date }}
                            </td>

                            <td class="p-3">
                                {{
                                    $schedule->maintenance_schedule_last_date ??
                                        "-"
                                }}
                            </td>

                            <td class="p-3">
                                @if ($schedule->maintenance_schedule_status == "Active")
                                    <span
                                        class="rounded-full bg-blue-100 px-3 py-1 text-xs text-blue-700"
                                    >
                                        Active
                                    </span>

                                @elseif ($schedule->maintenance_schedule_status == "Completed")
                                    <span
                                        class="rounded-full bg-green-100 px-3 py-1 text-xs text-green-700"
                                    >
                                        Completed
                                    </span>

                                @else
                                    <span
                                        class="rounded-full bg-red-100 px-3 py-1 text-xs text-red-700"
                                    >
                                        Overdue
                                    </span>

                                @endif
                            </td>

                            <td class="p-3">
                                <div class="flex justify-center gap-2">
                                    <button
                                        onclick="viewSchedule(
                                    '{{ $schedule->equipment_name }}',
                                    '{{ $schedule->room_name }}',
                                    '{{ $schedule->maintenance_schedule_title }}',
                                    '{{ $schedule->maintenance_schedule_frequency }}',
                                    '{{ $schedule->maintenance_schedule_next_date }}',
                                    '{{ $schedule->maintenance_schedule_status }}',
                                    '{{ $schedule->maintenance_schedule_description }}'
                                )"
                                        class="rounded-lg bg-indigo-600 px-3 py-2 text-white"
                                    >
                                        View
                                    </button>

                                    <button
                                        onclick="openCompleteModal(
                                    '{{ $schedule->maintenance_schedule_id }}',
                                    '{{ $schedule->equipment_name }}'
                                )"
                                        class="rounded-lg bg-green-600 px-3 py-2 text-white"
                                    >
                                        Complete
                                    </button>

                                    <button
                                        onclick="openRescheduleModal(
                                    '{{ $schedule->maintenance_schedule_id }}',
                                    '{{ $schedule->equipment_name }}'
                                )"
                                        class="rounded-lg bg-yellow-500 px-3 py-2 text-white"
                                    >
                                        Reschedule
                                    </button>

                                    <button
                                        onclick="openDeleteModal(
                                    '{{ $schedule->maintenance_schedule_id }}',
                                    '{{ $schedule->maintenance_schedule_title }}'
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
                                No maintenance schedules found.
                            </td>
                        </tr>

                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- SCHEDULE MAINTENANCE MODAL -->
    <!-- ===================================================== -->

    <div
        id="scheduleModal"
        class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black/50"
    >
        <div class="w-full max-w-xl rounded-3xl bg-white p-6">
            <h2 class="mb-4 text-2xl font-bold text-black">
                Schedule Maintenance
            </h2>

            <!-- form goes here -->

            <form action="/maintenance/schedules/store" method="POST">
                @csrf

                <div class="mb-4">
                    <label class="mb-2 block text-black"> Equipment </label>

                    <select
                        name="equipment_id"
                        class="w-full rounded-xl border p-3 text-black"
                        required
                    >
                        <option value="">Select Equipment</option>

                        @foreach ($equipment as $item)
                            <option value="{{ $item->equipment_id }}">
                                {{ $item->equipment_name }}
                            </option>

                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-black">
                        Maintenance Title
                    </label>

                    <input
                        type="text"
                        name="title"
                        class="w-full rounded-xl border p-3 text-black"
                        required
                    />
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-black"> Description </label>

                    <textarea
                        name="description"
                        rows="4"
                        class="w-full rounded-xl border p-3 text-black"
                    ></textarea>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-black"> Frequency </label>

                    <select
                        name="frequency"
                        class="w-full rounded-xl border p-3 text-black"
                    >
                        <option value="Monthly">Monthly</option>

                        <option value="Quarterly">Quarterly</option>

                        <option value="Semi Annual">Semi Annual</option>

                        <option value="Annual">Annual</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-black">
                        Next Maintenance Date
                    </label>

                    <input
                        type="date"
                        name="next_date"
                        class="w-full rounded-xl border p-3 text-black"
                        required
                    />
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeScheduleModal()"
                        class="rounded-xl bg-slate-200 px-5 py-3"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-blue-600 px-5 py-3 text-white"
                    >
                        Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- VIEW MODAL -->
    <!-- ===================================================== -->

    <div
        id="viewModal"
        class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black/50"
    >
        <div class="w-full max-w-xl rounded-3xl bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-black">Schedule Details</h2>

                <button
                    onclick="closeViewModal()"
                    class="text-xl text-slate-500"
                >
                    ×
                </button>
            </div>

            <div id="scheduleDetails" class="space-y-3 text-black"></div>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- COMPLETE MODAL -->
    <!-- ===================================================== -->

    <div
        id="completeModal"
        class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black/50"
    >
        <div class="w-full max-w-2xl rounded-3xl bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-black">
                    Complete Maintenance
                </h2>

                <button onclick="closeCompleteModal()">✕</button>
            </div>

            <p
                id="completeEquipmentName"
                class="mb-4 font-semibold text-black"
            ></p>

            <form
                action="/maintenance/schedules/complete"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf

                <input
                    type="hidden"
                    id="completeScheduleId"
                    name="schedule_id"
                />

                <div class="mb-4">
                    <label class="mb-2 block text-black"> Findings </label>

                    <textarea
                        name="findings"
                        rows="3"
                        class="w-full rounded-xl border p-3 text-black"
                        required
                    ></textarea>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-black"> Repair Action </label>

                    <textarea
                        name="repair_action"
                        rows="3"
                        class="w-full rounded-xl border p-3 text-black"
                        required
                    ></textarea>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-black"> Status </label>

                    <select
                        name="maintenance_status"
                        class="w-full rounded-xl border p-3 text-black"
                    >
                        <option value="Resolved">Resolved</option>

                        <option value="Pending">Pending</option>

                        <option value="Escalated">Escalated</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-black"> Proof Image </label>

                    <input
                        type="file"
                        name="proof_image"
                        class="w-full rounded-xl border p-3 text-black"
                    />
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeCompleteModal()"
                        class="rounded-xl bg-slate-200 px-5 py-3"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-green-600 px-5 py-3 text-white"
                    >
                        Complete Maintenance
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- RESCHEDULE MODAL -->
    <!-- ===================================================== -->

    <div
        id="rescheduleModal"
        class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black/50"
    >
        <div class="w-full max-w-xl rounded-3xl bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-black">
                    Reschedule Maintenance
                </h2>

                <button onclick="closeRescheduleModal()">✕</button>
            </div>

            <p
                id="rescheduleEquipmentName"
                class="mb-4 font-semibold text-black"
            ></p>

            <form action="/maintenance/schedules/reschedule" method="POST">
                @csrf

                <input
                    type="hidden"
                    id="rescheduleScheduleId"
                    name="schedule_id"
                />

                <div class="mb-4">
                    <label class="mb-2 block text-black">
                        New Maintenance Date
                    </label>

                    <input
                        type="date"
                        name="new_date"
                        class="w-full rounded-xl border p-3 text-black"
                        required
                    />
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-black"> Reason </label>

                    <textarea
                        name="reason"
                        rows="4"
                        class="w-full rounded-xl border p-3 text-black"
                        required
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeRescheduleModal()"
                        class="rounded-xl bg-slate-200 px-5 py-3"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-yellow-500 px-5 py-3 text-white"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- DELETE MODAL -->
    <!-- ===================================================== -->

    <!-- ===================================================== -->
    <!-- DELETE MODAL -->
    <!-- ===================================================== -->

    <div
        id="deleteModal"
        class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black/50"
    >
        <div class="w-full max-w-md rounded-3xl bg-white p-6">
            <h2 class="mb-4 text-2xl font-bold text-black">Delete Schedule</h2>

            <p id="deleteScheduleTitle" class="mb-6 text-slate-600"></p>

            <form action="/maintenance/schedules/delete" method="POST">
                @csrf

                @method ("DELETE")

                <input type="hidden" id="deleteScheduleId" name="schedule_id" />

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeDeleteModal()"
                        class="rounded-xl bg-slate-200 px-5 py-3"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-red-600 px-5 py-3 text-white"
                    >
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openScheduleModal() {
            document.getElementById("scheduleModal").classList.remove("hidden");
        }

        function openViewModal() {
            document.getElementById("viewModal").classList.remove("hidden");
        }

        function openCompleteModal() {
            document.getElementById("completeModal").classList.remove("hidden");
        }

        function openRescheduleModal() {
            document
                .getElementById("rescheduleModal")
                .classList.remove("hidden");
        }

        function openDeleteModal() {
            document.getElementById("deleteModal").classList.remove("hidden");
        }

        function closeScheduleModal() {
            document.getElementById("scheduleModal").classList.add("hidden");
        }

        function viewSchedule(
            equipment,
            room,
            title,
            frequency,
            nextDate,
            status,
            description,
        ) {
            document.getElementById("scheduleDetails").innerHTML = `

        <p>
            <strong>Equipment:</strong>
            ${equipment}
        </p>

        <p>
            <strong>Room:</strong>
            ${room}
        </p>

        <p>
            <strong>Maintenance Type:</strong>
            ${title}
        </p>

        <p>
            <strong>Frequency:</strong>
            ${frequency}
        </p>

        <p>
            <strong>Next Date:</strong>
            ${nextDate}
        </p>

        <p>
            <strong>Status:</strong>
            ${status}
        </p>

        <p>
            <strong>Description:</strong>
            ${description}
        </p>

    `;

            document.getElementById("viewModal").classList.remove("hidden");
        }

        function closeViewModal() {
            document.getElementById("viewModal").classList.add("hidden");
        }

        function openCompleteModal(scheduleId, equipmentName) {
            document.getElementById("completeScheduleId").value = scheduleId;

            document.getElementById("completeEquipmentName").innerText =
                equipmentName;

            document.getElementById("completeModal").classList.remove("hidden");
        }

        function closeCompleteModal() {
            document.getElementById("completeModal").classList.add("hidden");
        }

        function openRescheduleModal(scheduleId, equipmentName) {
            document.getElementById("rescheduleScheduleId").value = scheduleId;

            document.getElementById("rescheduleEquipmentName").innerText =
                equipmentName;

            document
                .getElementById("rescheduleModal")
                .classList.remove("hidden");
        }

        function closeRescheduleModal() {
            document.getElementById("rescheduleModal").classList.add("hidden");
        }

        function openDeleteModal(scheduleId, title) {
            document.getElementById("deleteScheduleId").value = scheduleId;

            document.getElementById("deleteScheduleTitle").innerText =
                'Are you sure you want to delete "' + title + '" ?';

            document.getElementById("deleteModal").classList.remove("hidden");
        }

        function closeDeleteModal() {
            document.getElementById("deleteModal").classList.add("hidden");
        }
    </script>

@endsection
