@extends('layouts.maintenance-layout')

@section('content')

<div class="bg-white rounded-3xl p-6">

    <div class="flex items-center justify-between mb-6">

        <h1 class="text-3xl font-bold text-black">
            Borrowing Records
        </h1>

        <button
            onclick="openBorrowModal()"
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl">

            Borrow Equipment

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
                        Borrower
                    </th>

                    <th class="p-3 text-left">
                        Department
                    </th>

                    <th class="p-3 text-left">
                        Status
                    </th>

                    <th class="p-3 text-left">
                        Borrow Date
                    </th>

                    <th class="p-3 text-left">
                        Expected Return
                    </th>

                    <th class="p-3 text-left">
                        Actual Return
                    </th>

                    <th class="p-3 text-center">
                        Actions
                    </th>

                </tr>

            </thead>

            <tbody>

                @forelse($borrowings as $record)

                <tr class="border-b">

                    <td class="p-3">
                        {{ $record->equipment_name }}
                    </td>

                    <td class="p-3">
                        {{ $record->borrowing_borrower_name }}
                    </td>

                    <td class="p-3">
                        {{ $record->borrowing_borrower_department }}
                    </td>

                    <td class="p-3">

                        @if($record->borrowing_status == 'Borrowed')

                            <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs">
                                Borrowed
                            </span>

                        @elseif($record->borrowing_status == 'Returned')

                            <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs">
                                Returned
                            </span>

                        @else

                            <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs">
                                Overdue
                            </span>

                        @endif

                    </td>

                    <td class="p-3">
                        {{ $record->borrowing_date }}
                    </td>

                    <td class="p-3">
                        {{ $record->borrowing_expected_return_date }}
                    </td>

                    <td class="p-3">
                        {{ $record->borrowing_actual_return_date ?? '-' }}
                    </td>

                    <td class="p-3">

                        <div class="flex justify-center gap-2">

                            <button
                                onclick="viewBorrowing(
                                    '{{ $record->equipment_name }}',
                                    '{{ $record->borrowing_borrower_name }}',
                                    '{{ $record->borrowing_borrower_department }}',
                                    '{{ $record->borrowing_quantity }}',
                                    '{{ $record->borrowing_date }}',
                                    '{{ $record->borrowing_expected_return_date }}',
                                    '{{ $record->borrowing_actual_return_date }}',
                                    '{{ $record->borrowing_purpose }}',
                                    '{{ $record->borrowing_destination_location }}',
                                    '{{ $record->borrowing_authorized_by }}',
                                    '{{ $record->borrowing_remarks }}',
                                    '{{ $record->borrowing_status }}'
                                )"
                                class="px-3 py-2 bg-indigo-600 text-white rounded-lg">

                                View

                            </button>

                            @if($record->borrowing_status == 'Borrowed')

                            <button
                                onclick="openReturnModal(
                                    '{{ $record->borrowing_record_id }}',
                                    '{{ $record->equipment_name }}'
                                )"
                                class="px-3 py-2 bg-emerald-600 text-white rounded-lg">

                                Return

                            </button>

                            @endif

                        </div>

                    </td>

                </tr>

                @empty

                <tr>

                    <td
                        colspan="8"
                        class="text-center py-10 text-slate-500">

                        No borrowing records found.

                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

<!-- ===================================================== -->
<!-- BORROW MODAL -->
<!-- ===================================================== -->

<div
    id="borrowModal"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">

    <div class="bg-white rounded-3xl w-full max-w-4xl p-6 overflow-y-auto max-h-[90vh]">

        <div class="flex items-center justify-between mb-6">

            <h2 class="text-2xl font-bold text-black">
                Borrow Equipment
            </h2>

            <button
                type="button"
                onclick="closeBorrowModal()"
                class="text-3xl text-slate-500">

                &times;

            </button>

        </div>

        <form
            method="POST"
            action="/maintenance/borrowing/store">

            @csrf

            <div class="grid md:grid-cols-2 gap-4">

                <div>

                    <label class="block mb-2 font-medium text-black">
                        Equipment
                    </label>

                    <select
                        name="borrowing_equipment_id"
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

                <div>

                    <label class="block mb-2 font-medium text-black">
                        Borrower Name
                    </label>

                    <input
                        type="text"
                        name="borrowing_borrower_name"
                        class="w-full border rounded-xl p-3 text-black"
                        required>

                </div>

                <div>

                    <label class="block mb-2 font-medium text-black">
                        Department
                    </label>

                    <input
                        type="text"
                        name="borrowing_borrower_department"
                        class="w-full border rounded-xl p-3 text-black">

                </div>

                <div>

                    <label class="block mb-2 font-medium text-black">
                        Quantity
                    </label>

                    <input
                        type="number"
                        name="borrowing_quantity"
                        value="1"
                        min="1"
                        class="w-full border rounded-xl p-3 text-black">

                </div>

                <div>

                    <label class="block mb-2 font-medium text-black">
                        Borrow Date
                    </label>

                    <input
                        type="date"
                        name="borrowing_date"
                        class="w-full border rounded-xl p-3 text-black"
                        required>

                </div>

                <div>

                    <label class="block mb-2 font-medium text-black">
                        Expected Return Date
                    </label>

                    <input
                        type="date"
                        name="borrowing_expected_return_date"
                        class="w-full border rounded-xl p-3 text-black"
                        required>

                </div>

                <div>

                    <label class="block mb-2 font-medium text-black">
                        Condition
                    </label>

                    <input
                        type="text"
                        name="borrowing_equipment_condition"
                        class="w-full border rounded-xl p-3 text-black">

                </div>

                <div>

                    <label class="block mb-2 font-medium text-black">
                        Authorized By
                    </label>

                    <input
                        type="text"
                        name="borrowing_authorized_by"
                        class="w-full border rounded-xl p-3 text-black">

                </div>

            </div>

            <div class="mt-4">

                <label class="block mb-2 font-medium text-black">
                    Purpose
                </label>

                <textarea
                    name="borrowing_purpose"
                    rows="3"
                    class="w-full border rounded-xl p-3 text-black"></textarea>

            </div>

            <div class="mt-4">

                <label class="block mb-2 font-medium text-black">
                    Destination
                </label>

                <input
                    type="text"
                    name="borrowing_destination_location"
                    class="w-full border rounded-xl p-3 text-black">

            </div>

            <div class="mt-4">

                <label class="block mb-2 font-medium text-black">
                    Remarks
                </label>

                <textarea
                    name="borrowing_remarks"
                    rows="3"
                    class="w-full border rounded-xl p-3 text-black"></textarea>

            </div>

            <div class="flex justify-end gap-3 mt-6">

                <button
                    type="button"
                    onclick="closeBorrowModal()"
                    class="px-5 py-3 border rounded-xl">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="px-5 py-3 bg-blue-600 text-white rounded-xl">

                    Save Borrowing

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
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">

    <div class="bg-white rounded-3xl w-full max-w-xl p-6">

        <div class="flex justify-between items-center mb-6">

            <h2 class="text-2xl font-bold">
                Borrowing Details
            </h2>

            <button
                onclick="closeViewModal()"
                class="text-3xl">

                &times;

            </button>

        </div>

        <div
            id="viewBorrowDetails"
            class="space-y-3 text-black">

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- RETURN MODAL -->
<!-- ===================================================== -->

<div
    id="returnModal"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">

    <div class="bg-white rounded-3xl w-full max-w-lg p-6">

        <div class="flex justify-between items-center mb-6">

            <h2 class="text-2xl font-bold">
                Return Equipment
            </h2>

            <button
                onclick="closeReturnModal()"
                class="text-3xl">

                &times;

            </button>

        </div>

        <form
            method="POST"
            action="/maintenance/borrowing/return">

            @csrf

            <input
                type="hidden"
                id="returnBorrowingId"
                name="borrowing_record_id">

            <div class="mb-4">

                <label class="block text-sm text-slate-500">

                    Equipment

                </label>

                <p
                    id="returnEquipmentName"
                    class="font-semibold text-lg">
                </p>

            </div>

            <!-- CONDITION UPON RETURN -->

            <div class="mb-4">

                <label class="block mb-2">
                    Condition Upon Return
                </label>

                <select
                    name="return_condition"
                    class="w-full border rounded-xl p-3 text-black">

                    <option value="Good">
                        Good
                    </option>

                    <option value="Damaged">
                        Damaged
                    </option>

                    <option value="For Repair">
                        For Repair
                    </option>

                </select>

            </div>

            <!-- RETURN REMARKS -->

            <div class="mb-4">

                <label class="block mb-2">
                    Return Remarks
                </label>

                <textarea
                    name="remarks"
                    rows="3"
                    class="w-full border rounded-xl p-3 text-black"></textarea>

            </div>

            <div class="flex justify-end gap-3">

                <button
                    type="button"
                    onclick="closeReturnModal()"
                    class="px-5 py-3 border rounded-xl">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="px-5 py-3 bg-emerald-600 text-white rounded-xl">

                    Confirm Return

                </button>

            </div>

        </form>

    </div>

</div>

<script>

function openBorrowModal(){

    document
        .getElementById('borrowModal')
        .classList.remove('hidden');

    document
        .getElementById('borrowModal')
        .classList.add('flex');
}

function closeBorrowModal(){

    document
        .getElementById('borrowModal')
        .classList.add('hidden');

    document
        .getElementById('borrowModal')
        .classList.remove('flex');
}

function viewBorrowing(
    equipment,
    borrower,
    department,
    quantity,
    borrowDate,
    expectedReturn,
    actualReturn,
    purpose,
    destination,
    authorized,
    remarks,
    status
){

    document.getElementById(
        'viewBorrowDetails'
    ).innerHTML = `

        <div class="space-y-3">

            <p><strong>Equipment:</strong> ${equipment}</p>

            <p><strong>Borrower:</strong> ${borrower}</p>

            <p><strong>Department:</strong> ${department}</p>

            <p><strong>Quantity:</strong> ${quantity}</p>

            <p><strong>Borrow Date:</strong> ${borrowDate}</p>

            <p><strong>Expected Return:</strong> ${expectedReturn}</p>

            <p><strong>Actual Return:</strong> ${actualReturn ?? '-'}</p>

            <p><strong>Destination:</strong> ${destination}</p>

            <p><strong>Authorized By:</strong> ${authorized}</p>

            <p><strong>Purpose:</strong> ${purpose}</p>

            <p><strong>Remarks:</strong> ${remarks}</p>

            <p>

                <strong>Status:</strong>

                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs">

                    ${status}

                </span>

            </p>

        </div>

    `;

    document
        .getElementById('viewModal')
        .classList.remove('hidden');

    document
        .getElementById('viewModal')
        .classList.add('flex');
}

function closeViewModal(){

    document
        .getElementById('viewModal')
        .classList.add('hidden');

    document
        .getElementById('viewModal')
        .classList.remove('flex');
}

function openReturnModal(
    id,
    equipment
){

    document.getElementById(
        'returnBorrowingId'
    ).value = id;

    document.getElementById(
        'returnEquipmentName'
    ).innerText = equipment;

    document
        .getElementById('returnModal')
        .classList.remove('hidden');
}

function closeReturnModal(){

    document
        .getElementById('returnModal')
        .classList.add('hidden');

    document
        .getElementById('returnModal')
        .classList.remove('flex');
}

</script>

@endsection