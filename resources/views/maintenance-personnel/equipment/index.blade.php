@extends('layouts.maintenance-layout')

@section('title', 'Equipment Inventory')

@section('content')

<div class="space-y-6">

    <!-- PAGE HEADER -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

        <div>
            <h1 class="text-4xl font-black text-slate-900">
                Inventory & Status
            </h1>

            <p class="text-slate-500 mt-1">
                Monitor equipment inventory, condition, and operational status.
            </p>
        </div>

        <button
            type="button"
            onclick="openAddEquipmentModal()"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-semibold transition">

            <i data-lucide="plus"></i>

            Add Equipment

        </button>

    </div>

    <!-- DASHBOARD CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <p class="text-sm text-slate-500">
                Total Equipment
            </p>

            <h2 class="text-3xl font-black text-slate-900 mt-2">
                {{ $equipment->total() }}
            </h2>
        </div>

        <div class="bg-white rounded-2xl border border-green-200 p-5">
            <p class="text-sm text-green-600">
                Active
            </p>

            <h2 class="text-3xl font-black text-green-700 mt-2">
                {{ $equipment->where('equipment_inventory_status','Active')->count() }}
            </h2>
        </div>

        <div class="bg-white rounded-2xl border border-amber-200 p-5">
            <p class="text-sm text-amber-600">
                Under Maintenance
            </p>

            <h2 class="text-3xl font-black text-amber-700 mt-2">
                {{ $equipment->where('equipment_inventory_status','Under Maintenance')->count() }}
            </h2>
        </div>

        <div class="bg-white rounded-2xl border border-red-200 p-5">
            <p class="text-sm text-red-600">
                Disposed
            </p>

            <h2 class="text-3xl font-black text-red-700 mt-2">
                {{ $equipment->where('equipment_inventory_status','Disposed')->count() }}
            </h2>
        </div>

    </div>

    <!-- FILTER SECTION -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5">

        <form method="GET">

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

                <div class="lg:col-span-2">

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search equipment..."
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-500 text-black"
                    >

                </div>

                <div>

                    <select
                        name="category"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black">

                        <option value="">
                            All Categories
                        </option>

                        @foreach($categories as $category)

                            <option
                                value="{{ $category->equipment_category_id }}"
                                {{ request('category') == $category->equipment_category_id ? 'selected' : '' }}>

                                {{ $category->equipment_category_name }}

                            </option>

                        @endforeach

                    </select>

                </div>

                <div>

                    <select
                        name="room"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black">

                        <option value="">
                            All Rooms
                        </option>

                        @foreach($rooms as $room)

                            <option
                                value="{{ $room->room_id }}"
                                {{ request('room') == $room->room_id ? 'selected' : '' }}>

                                {{ $room->room_name }}

                            </option>

                        @endforeach

                    </select>

                </div>

                <div>

                    <select
                        name="status"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black">

                        <option value="">
                            All Status
                        </option>

                        <option value="Active"
                            {{ request('status') == 'Active' ? 'selected' : '' }}>
                            Active
                        </option>

                        <option value="Under Maintenance"
                            {{ request('status') == 'Under Maintenance' ? 'selected' : '' }}>
                            Under Maintenance
                        </option>

                        <option value="Borrowed"
                            {{ request('status') == 'Borrowed' ? 'selected' : '' }}>
                            Borrowed
                        </option>

                        <option value="For Replacement"
                            {{ request('status') == 'For Replacement' ? 'selected' : '' }}>
                            For Replacement
                        </option>

                        <option value="Disposed"
                            {{ request('status') == 'Disposed' ? 'selected' : '' }}>
                            Disposed
                        </option>

                    </select>

                </div>

            </div>

            <div class="mt-4 flex justify-end">

                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold">

                    Search

                </button>

            </div>

        </form>

    </div>

    <!-- EQUIPMENT TABLE -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-50">

                    <tr class="text-slate-700 text-sm">

                        <th class="p-4 text-left">
                            Asset Tag
                        </th>

                        <th class="p-4 text-left">
                            Equipment
                        </th>

                        <th class="p-4 text-left">
                            Brand
                        </th>

                        <th class="p-4 text-left">
                            Category
                        </th>

                        <th class="p-4 text-left">
                            Room
                        </th>

                        <th class="p-4 text-center">
                            Qty
                        </th>

                        <th class="p-4 text-center">
                            Condition
                        </th>

                        <th class="p-4 text-center">
                            Status
                        </th>

                        <th class="p-4 text-center">
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($equipment as $item)

                    <tr class="border-t hover:bg-slate-50 transition">

                        <td class="p-4 text-black">

                            {{ $item->equipment_asset_tag ?? 'N/A' }}

                        </td>

                        <td class="p-4 font-semibold text-black">

                            {{ $item->equipment_name }}

                        </td>

                        <td class="p-4 text-black">

                            {{ $item->equipment_brand_name }}

                        </td>

                        <td class="p-4 text-black">

                            {{ $item->equipment_category_name }}

                        </td>

                        <td class="p-4 text-black">

                            {{ $item->room_name }}

                        </td>

                        <td class="p-4 text-center text-black">

                            {{ $item->equipment_quantity }}

                        </td>

                        <td class="p-4 text-center">

                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">

                                {{ $item->equipment_condition_status }}

                            </span>

                        </td>

                        <td class="p-4 text-center">

                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">

                                {{ $item->equipment_inventory_status }}

                            </span>

                        </td>

                        <td class="p-4">

                            <div class="flex justify-center gap-2">

                                <button
                                    type="button"

                                    onclick="openEquipmentModal(

                                        '{{ $item->equipment_asset_tag ?? 'N/A' }}',

                                        '{{ $item->equipment_name }}',

                                        '{{ $item->equipment_brand_name ?? 'N/A' }}',

                                        '{{ $item->equipment_model ?? 'N/A' }}',

                                        '{{ $item->equipment_serial_number ?? 'N/A' }}',

                                        '{{ $item->equipment_category_name }}',

                                        '{{ $item->room_name }}',

                                        '{{ $item->equipment_quantity }}',

                                        '{{ $item->equipment_condition_status }}',

                                        '{{ $item->equipment_inventory_status }}',

                                        '{{ $item->equipment_purchase_date ?? 'N/A' }}',

                                        '{{ $item->equipment_warranty_expiration ?? 'N/A' }}',

                                        '{{ $item->equipment_is_borrowable ? 'Yes' : 'No' }}'

                                    )"

                                    class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100">

                                    View

                                </button>

                                <button
                                    type="button"

                                    onclick="openEditEquipmentModal(

                                        '{{ $item->equipment_id }}',

                                        '{{ $item->equipment_category_id }}',

                                        '{{ $item->equipment_room_id }}',

                                        '{{ $item->equipment_asset_tag }}',

                                        '{{ $item->equipment_name }}',

                                        '{{ $item->equipment_brand_name }}',

                                        '{{ $item->equipment_model }}',

                                        '{{ $item->equipment_serial_number }}',

                                        '{{ $item->equipment_quantity }}',

                                        '{{ $item->equipment_condition_status }}',

                                        '{{ $item->equipment_inventory_status }}'

                                    )"

                                    class="px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">

                                    Edit

                                </button>

                            </div>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="9"
                            class="p-10 text-center text-slate-500">

                            No equipment found.

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    <!-- PAGINATION -->
    <div>

        {{ $equipment->links() }}

    </div>

</div>

<!-- VIEW EQUIPMENT MODAL -->

<div
    id="viewEquipmentModal"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">

    <div class="bg-white w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden">

        <div class="flex items-center justify-between px-6 py-5 border-b">

            <h2 class="text-2xl font-bold text-slate-900">
                Equipment Details
            </h2>

            <button
                onclick="closeEquipmentModal()"
                class="text-slate-500 hover:text-red-500 text-2xl">

                &times;

            </button>

        </div>

        <div class="p-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <p class="text-xs text-slate-500">Asset Tag</p>
                    <p id="modal_asset_tag" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Equipment Name</p>
                    <p id="modal_name" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Brand</p>
                    <p id="modal_brand" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Model</p>
                    <p id="modal_model" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Serial Number</p>
                    <p id="modal_serial" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Category</p>
                    <p id="modal_category" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Room</p>
                    <p id="modal_room" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Quantity</p>
                    <p id="modal_quantity" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Condition</p>
                    <p id="modal_condition" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Status</p>
                    <p id="modal_status" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Purchase Date</p>
                    <p id="modal_purchase_date" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Warranty Expiration</p>
                    <p id="modal_warranty" class="font-semibold"></p>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Borrowable</p>
                    <p id="modal_borrowable" class="font-semibold"></p>
                </div>

            </div>

        </div>

    </div>

</div>


<!-- ADD EQUIPMENT MODAL -->

<div
    id="addEquipmentModal"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">

    <div class="bg-white w-full max-w-6xl rounded-3xl shadow-2xl overflow-hidden">

        <div class="flex items-center justify-between px-6 py-5 border-b">

            <h2 class="text-2xl font-bold text-slate-900">
                Add Equipment
            </h2>

            <button
                onclick="closeAddEquipmentModal()"
                class="text-2xl text-slate-500">

                &times;

            </button>

        </div>

        <form
            action="/maintenance/equipment/store"
            method="POST">

            @csrf

            <div class="p-6">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                    <!-- CATEGORY -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Category
                        </label>

                        <select
                            name="equipment_category_id"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3"
                            required>

                            <option value="">
                                Select Category
                            </option>

                            @foreach($categories as $category)

                                <option value="{{ $category->equipment_category_id }}">
                                    {{ $category->equipment_category_name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- ROOM -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Room
                        </label>

                        <select
                            name="equipment_room_id"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3"
                            required>

                            <option value="">
                                Select Room
                            </option>

                            @foreach($rooms as $room)

                                <option value="{{ $room->room_id }}">
                                    {{ $room->room_name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- ASSET TAG -->

                    <div>

                        <label class="block text-sm font-medium mb-2 text-black">
                            Asset Tag
                        </label>

                        <input
                            type="text"
                            name="equipment_asset_tag"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black">

                    </div>

                    <!-- NAME -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Equipment Name
                        </label>

                        <input
                            type="text"
                            name="equipment_name"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3">

                    </div>

                    <!-- BRAND -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Brand
                        </label>

                        <input
                            type="text"
                            name="equipment_brand_name"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3">

                    </div>

                    <!-- MODEL -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Model
                        </label>

                        <input
                            type="text"
                            name="equipment_model"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3">

                    </div>

                    <!-- SERIAL -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Serial Number
                        </label>

                        <input
                            type="text"
                            name="equipment_serial_number"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3">

                    </div>

                    <!-- QUANTITY -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Quantity
                        </label>

                        <input
                            type="number"
                            min="1"
                            value="1"
                            name="equipment_quantity"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3">

                    </div>

                    <!-- CONDITION -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Condition
                        </label>

                        <select
                            name="equipment_condition_status"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3">

                            <option>Good</option>
                            <option>Damaged</option>
                            <option>Under Maintenance</option>
                            <option>Disposed</option>

                        </select>

                    </div>

                </div>

            </div>

            <div class="border-t px-6 py-4 flex justify-end gap-3">

                <button
                    type="button"
                    onclick="closeAddEquipmentModal()"
                    class="px-5 py-3 border rounded-xl">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="px-5 py-3 bg-blue-600 text-white rounded-xl">

                    Save Equipment

                </button>

            </div>

        </form>

    </div>

</div>


<!-- EDIT EQUIPMENT MODAL -->

<div
    id="addEquipmentModal"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">

    <div class="bg-white w-full max-w-6xl rounded-3xl shadow-2xl overflow-hidden">

        <div class="flex items-center justify-between px-6 py-5 border-b">

            <h2 class="text-2xl font-bold text-slate-900">
                Add Equipment
            </h2>

            <button
                onclick="closeAddEquipmentModal()"
                class="text-2xl text-slate-500">

                &times;

            </button>

        </div>

        <form
            id="editEquipmentForm"
            method="POST">

            @csrf

            <div class="p-6">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                    <!-- CATEGORY -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Category
                        </label>

                        <select
                            name="equipment_category_id"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3"
                            required>

                            <option value="">
                                Select Category
                            </option>

                            @foreach($categories as $category)

                                <option value="{{ $category->equipment_category_id }}">
                                    {{ $category->equipment_category_name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- ROOM -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Room
                        </label>

                        <select
                            name="equipment_room_id"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3"
                            required>

                            <option value="">
                                Select Room
                            </option>

                            @foreach($rooms as $room)

                                <option value="{{ $room->room_id }}">
                                    {{ $room->room_name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- ASSET TAG -->

                    <div>

                        <label class="block text-sm font-medium mb-2 text-black">
                            Asset Tag
                        </label>

                        <input
                            type="text"
                            name="equipment_asset_tag"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black">

                    </div>

                    <!-- NAME -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Equipment Name
                        </label>

                        <input
                            type="text"
                            name="equipment_name"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3">

                    </div>

                    <!-- BRAND -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Brand
                        </label>

                        <input
                            type="text"
                            name="equipment_brand_name"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3">

                    </div>

                    <!-- MODEL -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Model
                        </label>

                        <input
                            type="text"
                            name="equipment_model"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3">

                    </div>

                    <!-- SERIAL -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Serial Number
                        </label>

                        <input
                            type="text"
                            name="equipment_serial_number"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3">

                    </div>

                    <!-- QUANTITY -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Quantity
                        </label>

                        <input
                            type="number"
                            min="1"
                            value="1"
                            name="equipment_quantity"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3">

                    </div>

                    <!-- CONDITION -->

                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Condition
                        </label>

                        <select
                            name="equipment_condition_status"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3">

                            <option>Good</option>
                            <option>Damaged</option>
                            <option>Under Maintenance</option>
                            <option>Disposed</option>

                        </select>

                    </div>

                </div>

            </div>

            <div class="border-t px-6 py-4 flex justify-end gap-3">

                <button
                    type="button"
                    onclick="closeAddEquipmentModal()"
                    class="px-5 py-3 border rounded-xl">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="px-5 py-3 bg-blue-600 text-white rounded-xl">

                    Save Equipment

                </button>

            </div>

        </form>

    </div>

</div>

<script>

function openEquipmentModal(
    assetTag,
    name,
    brand,
    model,
    serial,
    category,
    room,
    quantity,
    condition,
    status,
    purchaseDate,
    warranty,
    borrowable
){

    document.getElementById('modal_asset_tag').textContent = assetTag;
    document.getElementById('modal_name').textContent = name;
    document.getElementById('modal_brand').textContent = brand;
    document.getElementById('modal_model').textContent = model;
    document.getElementById('modal_serial').textContent = serial;
    document.getElementById('modal_category').textContent = category;
    document.getElementById('modal_room').textContent = room;
    document.getElementById('modal_quantity').textContent = quantity;
    document.getElementById('modal_condition').textContent = condition;
    document.getElementById('modal_status').textContent = status;
    document.getElementById('modal_purchase_date').textContent = purchaseDate;
    document.getElementById('modal_warranty').textContent = warranty;
    document.getElementById('modal_borrowable').textContent = borrowable;

    const modal = document.getElementById('viewEquipmentModal');

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEquipmentModal(){

    const modal = document.getElementById('viewEquipmentModal');

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

</script>


<script>
    function openAddEquipmentModal(){

        const modal = document.getElementById(
            'addEquipmentModal'
        );

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeAddEquipmentModal(){

        const modal = document.getElementById(
            'addEquipmentModal'
        );

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

<script>
    function openEditEquipmentModal(
        id,
        category,
        room,
        assetTag,
        name,
        brand,
        model,
        serial,
        quantity,
        condition,
        status
    ){

        document
            .getElementById(
                'editEquipmentForm'
            )
            .action =
            '/maintenance/equipment/update/' + id;

        document.getElementById(
            'edit_equipment_name'
        ).value = name;

        document.getElementById(
            'edit_asset_tag'
        ).value = assetTag;

        document.getElementById(
            'edit_brand'
        ).value = brand;

        document.getElementById(
            'edit_model'
        ).value = model;

        document.getElementById(
            'edit_serial'
        ).value = serial;

        document.getElementById(
            'edit_quantity'
        ).value = quantity;

        document.getElementById(
            'edit_condition'
        ).value = condition;

        document.getElementById(
            'edit_status'
        ).value = status;

        document
            .getElementById(
                'editEquipmentModal'
            )
            .classList
            .remove('hidden');

        document
            .getElementById(
                'editEquipmentModal'
            )
            .classList
            .add('flex');
    }

    function closeEditEquipmentModal(){

        document
            .getElementById(
                'editEquipmentModal'
            )
            .classList
            .add('hidden');

        document
            .getElementById(
                'editEquipmentModal'
            )
            .classList
            .remove('flex');
    }
</script>

@endsection