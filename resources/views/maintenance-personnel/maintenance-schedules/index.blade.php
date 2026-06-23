@extends('layouts.maintenance-layout')

@section('content')

<div class="bg-white rounded-3xl p-6">

    <div class="flex items-center justify-between mb-6">

        <h1 class="text-3xl font-bold text-black">
            Maintenance Schedules
        </h1>

        <button
            onclick="openScheduleModal()"
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl">

            + Schedule Maintenance

        </button>

    </div>

    <div class="overflow-x-auto">

        <table class="w-full text-black">

            <thead>

                <tr class="border-b">

                    <th class="p-3 text-left">
                        Equipment
                    </th>

                    <th class="p-3 text-left">
                        Title
                    </th>

                    <th class="p-3 text-left">
                        Frequency
                    </th>

                    <th class="p-3 text-left">
                        Next Date
                    </th>

                    <th class="p-3 text-left">
                        Last Date
                    </th>

                    <th class="p-3 text-left">
                        Status
                    </th>

                    <th class="p-3 text-center">
                        Actions
                    </th>

                </tr>

            </thead>

            <tbody>

                @forelse($schedules as $schedule)

                <tr class="border-b">

                    <td class="p-3">

                        {{ $schedule->equipment_name }}

                    </td>

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

                        {{ $schedule->maintenance_schedule_last_date ?? '-' }}

                    </td>

                    <td class="p-3">

                        @if($schedule->maintenance_schedule_status == 'Active')

                            <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs">

                                Active

                            </span>

                        @elseif($schedule->maintenance_schedule_status == 'Completed')

                            <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs">

                                Completed

                            </span>

                        @else

                            <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs">

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
                                class="px-3 py-2 bg-indigo-600 text-white rounded-lg">

                                View

                            </button>

                            <button
                                onclick="openCompleteModal(
                                    '{{ $schedule->maintenance_schedule_id }}',
                                    '{{ $schedule->equipment_name }}'
                                )"
                                class="px-3 py-2 bg-green-600 text-white rounded-lg">

                                Complete

                            </button>

                            <button
                                onclick="openRescheduleModal(
                                    '{{ $schedule->maintenance_schedule_id }}',
                                    '{{ $schedule->equipment_name }}'
                                )"
                                class="px-3 py-2 bg-yellow-500 text-white rounded-lg">

                                Reschedule

                            </button>

                            <button
                                onclick="openDeleteModal(
                                    '{{ $schedule->maintenance_schedule_id }}',
                                    '{{ $schedule->maintenance_schedule_title }}'
                                )"
                                class="px-3 py-2 bg-red-600 text-white rounded-lg">

                                Delete

                            </button>

                        </div>

                    </td>

                </tr>

                @empty

                <tr>

                    <td
                        colspan="7"
                        class="text-center py-10 text-slate-500">

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
    class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">

    <div class="bg-white rounded-3xl w-full max-w-xl p-6">

        <h2 class="text-2xl font-bold mb-4 text-black">

            Schedule Maintenance

        </h2>

        <!-- form goes here -->

        <form
            action="/maintenance/schedules/store"
            method="POST">

            @csrf

            <div class="mb-4">

                <label class="block mb-2 text-black">
                    Equipment
                </label>

                <select
                    name="equipment_id"
                    class="w-full border rounded-xl p-3 text-black"
                    required>

                    <option value="">
                        Select Equipment
                    </option>

                    @foreach($equipment as $item)

                        <option value="{{ $item->equipment_id }}">

                            {{ $item->equipment_name }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="mb-4">

                <label class="block mb-2 text-black">
                    Maintenance Title
                </label>

                <input
                    type="text"
                    name="title"
                    class="w-full border rounded-xl p-3 text-black"
                    required>

            </div>

            <div class="mb-4">

                <label class="block mb-2 text-black">
                    Description
                </label>

                <textarea
                    name="description"
                    rows="4"
                    class="w-full border rounded-xl p-3 text-black"></textarea>

            </div>

            <div class="mb-4">

                <label class="block mb-2 text-black">
                    Frequency
                </label>

                <select
                    name="frequency"
                    class="w-full border rounded-xl p-3 text-black">

                    <option value="Monthly">
                        Monthly
                    </option>

                    <option value="Quarterly">
                        Quarterly
                    </option>

                    <option value="Semi Annual">
                        Semi Annual
                    </option>

                    <option value="Annual">
                        Annual
                    </option>

                </select>

            </div>

            <div class="mb-4">

                <label class="block mb-2 text-black">
                    Next Maintenance Date
                </label>

                <input
                    type="date"
                    name="next_date"
                    class="w-full border rounded-xl p-3 text-black"
                    required>

            </div>

            <div class="flex justify-end gap-3">

                <button
                    type="button"
                    onclick="closeScheduleModal()"
                    class="px-5 py-3 bg-slate-200 rounded-xl">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="px-5 py-3 bg-blue-600 text-white rounded-xl">

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
    class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">

    <div class="bg-white rounded-3xl w-full max-w-xl p-6">

        <div class="flex justify-between items-center mb-4">

            <h2 class="text-2xl font-bold text-black">

                Schedule Details

            </h2>

            <button
                onclick="closeViewModal()"
                class="text-slate-500 text-xl">

                ×

            </button>

        </div>

        <div
            id="scheduleDetails"
            class="space-y-3 text-black">

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- COMPLETE MODAL -->
<!-- ===================================================== -->

<div
    id="completeModal"
    class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">

    <div class="bg-white rounded-3xl w-full max-w-2xl p-6">

        <div class="flex justify-between items-center mb-4">

            <h2 class="text-2xl font-bold text-black">

                Complete Maintenance

            </h2>

            <button
                onclick="closeCompleteModal()">

                ✕

            </button>

        </div>

        <p
            id="completeEquipmentName"
            class="font-semibold mb-4 text-black">
        </p>

        <form
            action="/maintenance/schedules/complete"
            method="POST"
            enctype="multipart/form-data">

            @csrf

            <input
                type="hidden"
                id="completeScheduleId"
                name="schedule_id">

            <div class="mb-4">

                <label class="block mb-2 text-black">

                    Findings

                </label>

                <textarea
                    name="findings"
                    rows="3"
                    class="w-full border rounded-xl p-3 text-black"
                    required></textarea>

            </div>

            <div class="mb-4">

                <label class="block mb-2 text-black">

                    Repair Action

                </label>

                <textarea
                    name="repair_action"
                    rows="3"
                    class="w-full border rounded-xl p-3 text-black"
                    required></textarea>

            </div>

            <div class="mb-4">

                <label class="block mb-2 text-black">

                    Status

                </label>

                <select
                    name="maintenance_status"
                    class="w-full border rounded-xl p-3 text-black">

                    <option value="Resolved">

                        Resolved

                    </option>

                    <option value="Pending">

                        Pending

                    </option>

                    <option value="Escalated">

                        Escalated

                    </option>

                </select>

            </div>

            <div class="mb-4">

                <label class="block mb-2 text-black">

                    Proof Image

                </label>

                <input
                    type="file"
                    name="proof_image"
                    class="w-full border rounded-xl p-3 text-black">

            </div>

            <div class="flex justify-end gap-3">

                <button
                    type="button"
                    onclick="closeCompleteModal()"
                    class="px-5 py-3 bg-slate-200 rounded-xl">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="px-5 py-3 bg-green-600 text-white rounded-xl">

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
    class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">

    <div class="bg-white rounded-3xl w-full max-w-xl p-6">

        <div class="flex justify-between items-center mb-4">

            <h2 class="text-2xl font-bold text-black">

                Reschedule Maintenance

            </h2>

            <button
                onclick="closeRescheduleModal()">

                ✕

            </button>

        </div>

        <p
            id="rescheduleEquipmentName"
            class="font-semibold mb-4 text-black">
        </p>

        <form
            action="/maintenance/schedules/reschedule"
            method="POST">

            @csrf

            <input
                type="hidden"
                id="rescheduleScheduleId"
                name="schedule_id">

            <div class="mb-4">

                <label class="block mb-2 text-black">

                    New Maintenance Date

                </label>

                <input
                    type="date"
                    name="new_date"
                    class="w-full border rounded-xl p-3 text-black"
                    required>

            </div>

            <div class="mb-4">

                <label class="block mb-2 text-black">

                    Reason

                </label>

                <textarea
                    name="reason"
                    rows="4"
                    class="w-full border rounded-xl p-3 text-black"
                    required></textarea>

            </div>

            <div class="flex justify-end gap-3">

                <button
                    type="button"
                    onclick="closeRescheduleModal()"
                    class="px-5 py-3 bg-slate-200 rounded-xl">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="px-5 py-3 bg-yellow-500 text-white rounded-xl">

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
    class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">

    <div class="bg-white rounded-3xl w-full max-w-md p-6">

        <h2 class="text-2xl font-bold mb-4 text-black">

            Delete Schedule

        </h2>

        <p
            id="deleteScheduleTitle"
            class="text-slate-600 mb-6">
        </p>

        <form
            action="/maintenance/schedules/delete"
            method="POST">

            @csrf

            @method('DELETE')

            <input
                type="hidden"
                id="deleteScheduleId"
                name="schedule_id">

            <div class="flex justify-end gap-3">

                <button
                    type="button"
                    onclick="closeDeleteModal()"
                    class="px-5 py-3 bg-slate-200 rounded-xl">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="px-5 py-3 bg-red-600 text-white rounded-xl">

                    Delete

                </button>

            </div>

        </form>

    </div>

</div>

<script>

function openScheduleModal(){
    document.getElementById('scheduleModal').classList.remove('hidden');
}

function openViewModal(){
    document.getElementById('viewModal').classList.remove('hidden');
}

function openCompleteModal(){
    document.getElementById('completeModal').classList.remove('hidden');
}

function openRescheduleModal(){
    document.getElementById('rescheduleModal').classList.remove('hidden');
}

function openDeleteModal(){
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeScheduleModal(){

    document
        .getElementById('scheduleModal')
        .classList.add('hidden');
}



function viewSchedule(
    equipment,
    room,
    title,
    frequency,
    nextDate,
    status,
    description
){

    document.getElementById(
        'scheduleDetails'
    ).innerHTML = `

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

    document
        .getElementById('viewModal')
        .classList.remove('hidden');
}

function closeViewModal(){

    document
        .getElementById('viewModal')
        .classList.add('hidden');
}





function openCompleteModal(
    scheduleId,
    equipmentName
){

    document.getElementById(
        'completeScheduleId'
    ).value = scheduleId;

    document.getElementById(
        'completeEquipmentName'
    ).innerText = equipmentName;

    document
        .getElementById('completeModal')
        .classList.remove('hidden');
}

function closeCompleteModal(){

    document
        .getElementById('completeModal')
        .classList.add('hidden');
}




function openRescheduleModal(
    scheduleId,
    equipmentName
){

    document.getElementById(
        'rescheduleScheduleId'
    ).value = scheduleId;

    document.getElementById(
        'rescheduleEquipmentName'
    ).innerText = equipmentName;

    document
        .getElementById('rescheduleModal')
        .classList.remove('hidden');
}

function closeRescheduleModal(){

    document
        .getElementById('rescheduleModal')
        .classList.add('hidden');
}



function openDeleteModal(
    scheduleId,
    title
){

    document.getElementById(
        'deleteScheduleId'
    ).value = scheduleId;

    document.getElementById(
        'deleteScheduleTitle'
    ).innerText =
        'Are you sure you want to delete "' +
        title +
        '" ?';

    document
        .getElementById('deleteModal')
        .classList.remove('hidden');
}

function closeDeleteModal(){

    document
        .getElementById('deleteModal')
        .classList.add('hidden');
}

</script>

@endsection