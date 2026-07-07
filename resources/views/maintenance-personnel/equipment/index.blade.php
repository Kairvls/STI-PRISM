@extends ("layouts.maintenance-layout")

@section ("title", "Equipment Inventory")

@section ("content")
    <div class="space-y-6">
        <!-- PAGE HEADER -->
        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
        >
            <div>
                <h1 class="text-4xl font-black text-slate-900">
                    Inventory & Status
                </h1>

                <p class="mt-1 text-slate-500">Monitor equipment inventory, condition, and operational status.</p>
            </div>

            <button
                type="button"
                onclick="openAddEquipmentModal()"
                class="inline-flex items-center gap-2 rounded-xl bg-[rgba(0,55,199,0.85)] px-4 py-3 font-semibold text-sm text-white transition hover:bg-[rgba(0,44,155,0.85)]"
            >
                <i data-lucide="plus" class="w-4 h-4"></i>

                Add Equipment
            </button>
        </div>

        <!-- DASHBOARD CARDS -->
        <!-- ========================================================= -->
        <!-- DASHBOARD STATS -->
        <!-- ========================================================= -->
        <div
            class="overflow-hidden rounded-lg border-t border-b border-slate-300 bg-gray-100 shadow-sm"
        >
            <div
                class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-y-0 xl:grid-cols-[380px_1fr_1fr_1fr] "
            >
                <!-- Total Equipment -->
                <div class="flex items-center justify-between px-8 py-6">

                    <!-- Left Content -->
                    <div class="flex flex-col">
                        <p class="text-sm font-medium text-slate-500">
                            Total Equipment
                        </p>

                        <h2 class="mt-2 text-5xl font-medium text-slate-900">
                            {{ $equipment->total() }}
                        </h2>

                        <p class="mt-3 text-sm">
                            <span class="font-semibold text-emerald-500">
                                +12.45%
                            </span>

                            <span class="text-slate-500">
                                From last month
                            </span>
                        </p>
                    </div>

                    <!-- Right Graph -->
                    <div class="ml-6 h-20 w-40 shrink-0">
                        <svg
                            viewBox="0 0 300 100"
                            class="h-full w-full"
                            fill="none"
                        >
                            <path
                                d="M0 62
                                L35 28
                                L62 58
                                L82 52
                                L112 82
                                L162 82
                                L200 42
                                L232 64
                                L270 64
                                L300 18"
                                stroke="#3b82f6"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />

                            <path
                                d="M0 62
                                L35 28
                                L62 58
                                L82 52
                                L112 82
                                L162 82
                                L200 42
                                L232 64
                                L270 64
                                L300 18
                                L300 100
                                L0 100 Z"
                                fill="#3b82f6"
                                fill-opacity=".08"
                            />
                        </svg>
                    </div>

                </div>

                <!-- Active -->
                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                    ></span>

                    <p class="text-md font-medium text-slate-600">
                        Active
                    </p>

                    <h2 class="text-5xl font-medium text-slate-900">
                        {{
                            $equipment
                                ->where("equipment_inventory_status", "Active")
                                ->count()
                        }}
                    </h2>

                    <p class="text-base">
                        <span class="font-semibold text-emerald-500">
                            +8.32%
                        </span>

                        <span class="text-slate-500">
                            From last month
                        </span>
                    </p>
                </div>

                <!-- Under Maintenance -->
                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                    ></span>

                    <p class="text-md font-medium text-slate-600">
                        Under Maintenance
                    </p>

                    <h2 class="text-5xl font-medium text-slate-900">
                        {{
                            $equipment
                                ->where(
                                    "equipment_inventory_status",
                                    "Under Maintenance",
                                )
                                ->count()
                        }}
                    </h2>

                    <p class="text-base">
                        <span class="font-semibold text-red-500">
                            -4.67%
                        </span>

                        <span class="text-slate-500">
                            From last month
                        </span>
                    </p>
                </div>

                <!-- Disposed -->
                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                    ></span>

                    <p class="text-md font-medium text-slate-600">
                        Disposed
                    </p>

                    <h2 class="text-5xl font-medium text-slate-900">
                        {{
                            $equipment
                                ->where("equipment_inventory_status", "Disposed")
                                ->count()
                        }}
                    </h2>

                    <p class="text-base">
                        <span class="font-semibold text-emerald-500">
                            +2.15%
                        </span>

                        <span class="text-slate-500">
                            From last month
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- FILTER SECTION 
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <form method="GET">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
                    <div class="lg:col-span-2">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search equipment..."
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <select
                            name="category"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black"
                        >
                            <option value="">All Categories</option>

                            @foreach ($categories as $category)
                                <option
                                    value="{{ $category->equipment_category_id }}"
                                    {{
                                        request("category") ==
                                        $category->equipment_category_id
                                            ? "selected"
                                            : ""
                                    }}
                                >
                                    {{ $category->equipment_category_name }}
                                </option>

                            @endforeach
                        </select>
                    </div>

                    <div>
                        <select
                            name="room"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black"
                        >
                            <option value="">All Rooms</option>

                            @foreach ($rooms as $room)
                                <option
                                    value="{{ $room->room_id }}"
                                    {{
                                        request("room") == $room->room_id
                                            ? "selected"
                                            : ""
                                    }}
                                >
                                    {{ $room->room_name }}
                                </option>

                            @endforeach
                        </select>
                    </div>

                    <div>
                        <select
                            name="status"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black"
                        >
                            <option value="">All Status</option>

                            <option
                                value="Active"
                                {{
                                    request("status") == "Active"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                Active
                            </option>

                            <option
                                value="Under Maintenance"
                                {{
                                    request("status") == "Under Maintenance"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                Under Maintenance
                            </option>

                            <option
                                value="Borrowed"
                                {{
                                    request("status") == "Borrowed"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                Borrowed
                            </option>

                            <option
                                value="For Replacement"
                                {{
                                    request("status") == "For Replacement"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                For Replacement
                            </option>

                            <option
                                value="Disposed"
                                {{
                                    request("status") == "Disposed"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                Disposed
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button
                        type="submit"
                        class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700"
                    >
                        Search
                    </button>
                </div>
            </form>
        </div>-->

        <!-- EQUIPMENT TABLE -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
        >
            <div class="overflow-x-auto">

                <!-- ========================================================= -->
                <!-- TABLE TOOLBAR -->
                <!-- ========================================================= -->
                <div
                    class="flex flex-col gap-4 border-b border-slate-200 bg-white px-5 py-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <!-- LEFT -->
                    <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap">

                        <button
                            class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white"
                        >
                            All
                        </button>

                        <button
                            class="rounded-lg px-3 py-2 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                        >
                            Active
                        </button>

                        <button
                            class="rounded-lg px-3 py-2 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                        >
                            Maintenance
                        </button>

                        <button
                            class="rounded-lg px-3 py-2 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                        >
                            Borrowed
                        </button>

                        <button
                            class="rounded-lg px-3 py-2 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                        >
                            Disposed
                        </button>

                    </div>

                    <!-- RIGHT -->
                    <form method="GET">
                        <div class="flex flex-wrap items-center gap-2">

                            <!-- Search -->
                            <div class="relative">

                                <i
                                    data-lucide="search"
                                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                ></i>

                                <input
                                    type="text"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="Search equipment..."
                                    class="h-10 w-64 rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-3 text-sm focus:border-blue-500 focus:bg-white focus:outline-none"
                                >
                            </div>

                            <!-- Category 
                            <select
                                name="category"
                                class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm"
                            >
                                <option value="">Category</option>

                                @foreach ($categories as $category)
                                    <option
                                        value="{{ $category->equipment_category_id }}"
                                        {{
                                            request("category") ==
                                            $category->equipment_category_id
                                                ? "selected"
                                                : ""
                                        }}
                                    >
                                        {{ $category->equipment_category_name }}
                                    </option>
                                @endforeach
                            </select>-->

                            <!-- Room 
                            <select
                                name="room"
                                class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm"
                            >
                                <option value="">Room</option>

                                @foreach ($rooms as $room)
                                    <option
                                        value="{{ $room->room_id }}"
                                        {{
                                            request("room") == $room->room_id
                                                ? "selected"
                                                : ""
                                        }}
                                    >
                                        {{ $room->room_name }}
                                    </option>
                                @endforeach
                            </select>-->

                            <!-- Status -->
                            <select
                                name="status"
                                class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm"
                            >
                                <option value="">Status</option>

                                <option value="Active">Active</option>
                                <option value="Under Maintenance">Under Maintenance</option>
                                <option value="Borrowed">Borrowed</option>
                                <option value="For Replacement">For Replacement</option>
                                <option value="Disposed">Disposed</option>
                            </select>

                            <button
                                type="submit"
                                class="inline-flex h-10 items-center rounded-xl bg-[rgba(0,55,199,0.85)] px-4 text-sm font-semibold text-white transition hover:bg-[rgba(0,44,155,0.85)]"
                            >
                                Apply
                            </button>

                        </div>
                    </form>
                </div>

                <table class="w-full">
                    <thead class="border-b border-slate-200 bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                Equipment
                            </th>

                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                Brand
                            </th>

                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                Category
                            </th>

                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                Room
                            </th>

                            <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                Qty
                            </th>

                            <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                Condition
                            </th>

                            <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                Status
                            </th>

                            <th class="w-32 px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($equipment as $item)
                            <tr class="border-b border-slate-100 transition duration-200 hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-900">
                                            {{ $item->equipment_name }}
                                        </span>

                                        <span class="mt-1 text-xs text-slate-400">
                                            {{ $item->equipment_asset_tag ?? "No Asset Tag" }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"
                                    >
                                        {{ $item->equipment_brand_name }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs text-slate-700"
                                    >
                                        {{ $item->equipment_category_name }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-sm text-slate-700">
                                    {{ $item->room_name }}
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="inline-flex h-8 min-w-[32px] items-center justify-center rounded-lg bg-slate-100 px-2 text-sm font-semibold text-slate-800"
                                    >
                                        {{ $item->equipment_quantity }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700"
                                    >
                                        <span class="h-2 w-2 rounded-full bg-green-500"></span>

                                        {{ $item->equipment_condition_status }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700"
                                    >
                                        <span class="h-2 w-2 rounded-full bg-blue-500"></span>

                                        {{ $item->equipment_inventory_status }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
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
                                            class="flex h-9 items-center justify-center gap-x-1.5 rounded-lg  bg-slate-100 px-3 text-xs  text-slate-800 transition shadow-sm hover:bg-slate-200 hover:text-gray-600"
                                        >
                                            <i data-lucide="eye" class="h-4 w-4"></i>
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
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[rgba(0,55,199,0.85)] text-white transition hover:bg-[rgba(0,44,155,0.85)]"
                                        >
                                            <i data-lucide="edit-3" class="h-4 w-4"></i>
                                        </button>

                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td
                                    colspan="9"
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

        <!-- PAGINATION -->
        <div>{{ $equipment->links() }}</div>
    </div>

    <!-- VIEW EQUIPMENT MODAL -->

    <!-- ========================================================= -->
    <!-- VIEW EQUIPMENT MODAL -->
    <!-- ========================================================= -->
    <div
        id="viewEquipmentModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-3 backdrop-blur-sm sm:p-5"
    >
        <!-- MODAL CONTAINER -->
        <div
            class="flex max-h-[70vh] w-full max-w-4xl flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-2xl"
        >
            <!-- ================================================= -->
            <!-- HEADER -->
            <!-- ================================================= -->
            <div
                class="flex shrink-0 items-center justify-between px-5 pb-3 pt-5 sm:px-7 sm:pt-6"
            >
                <h2 class="text-base font-bold text-slate-900">
                    Equipment Details
                </h2>

                <button
                    type="button"
                    onclick="closeEquipmentModal()"
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <!-- ================================================= -->
            <!-- SCROLLABLE CONTENT -->
            <!-- ================================================= -->
            <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-5 sm:px-7">

                <!-- SECTION LABEL -->
                <p class="mb-3 text-xs font-medium text-slate-500">
                    Complete inventory, location, and equipment information.
                </p>

                <!-- ================================================= -->
                <!-- MAIN INFORMATION PANEL -->
                <!-- ================================================= -->
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5"
                >
                    <!-- PANEL HEADER -->
                    <!--<div class="mb-5">
                        <h3 class="text-sm font-bold text-slate-900">
                            Equipment Informations
                        </h3>

                        <p class="mt-1 text-xs text-slate-400">
                            Complete inventory, location, and equipment information.
                        </p>
                    </div>-->

                    <!-- ================================================= -->
                    <!-- INFORMATION GRID -->
                    <!-- ================================================= -->
                    <div class="grid grid-cols-1 gap-x-4 gap-y-4 md:grid-cols-2">

                        <!-- EQUIPMENT NAME -->
                        <div>
                            <p class="mb-1.5 text-xs font-semibold text-slate-700">
                                Equipment Name
                            </p>

                            <div
                                id="modal_name"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                            ></div>
                        </div>

                        <!-- ASSET TAG -->
                        <div>
                            <p class="mb-1.5 text-xs font-semibold text-slate-700">
                                Asset Tag
                            </p>

                            <div
                                id="modal_asset_tag"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                            ></div>
                        </div>

                        <!-- CATEGORY -->
                        <div>
                            <p class="mb-1.5 text-xs font-semibold text-slate-700">
                                Category
                            </p>

                            <div
                                id="modal_category"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                            ></div>
                        </div>

                        <!-- ROOM -->
                        <div>
                            <p class="mb-1.5 text-xs font-semibold text-slate-700">
                                Room
                            </p>

                            <div
                                id="modal_room"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                            ></div>
                        </div>

                        <!-- BRAND -->
                        <div>
                            <p class="mb-1.5 text-xs font-semibold text-slate-700">
                                Brand
                            </p>

                            <div
                                id="modal_brand"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                            ></div>
                        </div>

                        <!-- MODEL -->
                        <div>
                            <p class="mb-1.5 text-xs font-semibold text-slate-700">
                                Model
                            </p>

                            <div
                                id="modal_model"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                            ></div>
                        </div>

                        <!-- SERIAL NUMBER -->
                        <div>
                            <p class="mb-1.5 text-xs font-semibold text-slate-700">
                                Serial Number
                            </p>

                            <div
                                id="modal_serial"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                            ></div>
                        </div>

                        <!-- QUANTITY -->
                        <div>
                            <p class="mb-1.5 text-xs font-semibold text-slate-700">
                                Quantity
                            </p>

                            <div
                                id="modal_quantity"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                            ></div>
                        </div>

                        <!-- CONDITION -->
                        <div>
                            <p class="mb-1.5 text-xs font-semibold text-slate-700">
                                Condition
                            </p>

                            <div
                                id="modal_condition"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                            ></div>
                        </div>

                        <!-- STATUS -->
                        <div>
                            <p class="mb-1.5 text-xs font-semibold text-slate-700">
                                Inventory Status
                            </p>

                            <div
                                id="modal_status"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                            ></div>
                        </div>

                        <!-- PURCHASE DATE -->
                        <div>
                            <p class="mb-1.5 text-xs font-semibold text-slate-700">
                                Purchase Date
                            </p>

                            <div
                                id="modal_purchase_date"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                            ></div>
                        </div>

                        <!-- WARRANTY EXPIRATION -->
                        <div>
                            <p class="mb-1.5 text-xs font-semibold text-slate-700">
                                Warranty Expiration
                            </p>

                            <div
                                id="modal_warranty"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                            ></div>
                        </div>

                        <!-- BORROWABLE -->
                        <div class="md:col-span-2">
                            <p class="mb-1.5 text-xs font-semibold text-slate-700">
                                Borrowable
                            </p>

                            <div
                                id="modal_borrowable"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                            ></div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ================================================= -->
            <!-- FOOTER -->
            <!-- ================================================= -->
            <div
                class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 px-5 py-4 sm:px-7"
            >
                <button
                    type="button"
                    onclick="closeEquipmentModal()"
                    class="rounded-lg bg-slate-950 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-800"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- ADD EQUIPMENT MODAL -->

    <!-- ========================================================= -->
    <!-- ADD EQUIPMENT MODAL -->
    <!-- ========================================================= -->
    <div
    id="addEquipmentModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- ADD EQUIPMENT MODAL -->
    <!-- ===================================== -->
    <div
        class="flex max-h-[80vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div
            class="flex shrink-0 items-start justify-between gap-6 px-6 pb-5 pt-6 border-b border-dashed border-slate-500"
        >
            <div class="min-w-0">
                <p
                    class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                >
                    Equipment Inventory
                </p>

                <h2
                    class="mt-1.5 text-lg font-bold tracking-tight text-slate-950"
                >
                    Add equipment
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Register equipment and assign its initial inventory details.
                </p>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeAddEquipmentModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- ADD EQUIPMENT FORM -->
        <!-- ===================================== -->
        <form
            action="/maintenance/equipment/store"
            method="POST"
            class="flex min-h-0 flex-1 flex-col"
        >
            @csrf

            <!-- ===================================== -->
            <!-- SCROLLABLE FORM CONTENT -->
            <!-- ===================================== -->
            <div
                class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100 px-6 py-6"
            >
                <div class="space-y-8">

                    <!-- ===================================== -->
                    <!-- PLACEMENT -->
                    <!-- ===================================== -->
                    <section>
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-slate-900">
                                Placement
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Assign the equipment category and room.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                            <!-- CATEGORY -->
                            <div>
                                <label
                                    for="add_equipment_category"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Category
                                </label>

                                <select
                                    id="add_equipment_category"
                                    name="equipment_category_id"
                                    required
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                >
                                    <option value="">
                                        Select category
                                    </option>

                                    @foreach ($categories as $category)
                                        <option
                                            value="{{ $category->equipment_category_id }}"
                                        >
                                            {{ $category->equipment_category_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- ROOM -->
                            <div>
                                <label
                                    for="add_equipment_room"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Room
                                </label>

                                <select
                                    id="add_equipment_room"
                                    name="equipment_room_id"
                                    required
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                >
                                    <option value="">
                                        Select room
                                    </option>

                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->room_id }}">
                                            {{ $room->room_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- ===================================== -->
                    <!-- SECTION DIVIDER -->
                    <!-- ===================================== -->
                    <div class="border-t border-slate-100"></div>

                    <!-- ===================================== -->
                    <!-- EQUIPMENT DETAILS -->
                    <!-- ===================================== -->
                    <section>
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-slate-900">
                                Equipment details
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Enter identification and technical information.
                            </p>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-5 md:grid-cols-12"
                        >
                            <!-- EQUIPMENT NAME -->
                            <div class="md:col-span-7">
                                <label
                                    for="add_equipment_name"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Equipment name
                                </label>

                                <input
                                    id="add_equipment_name"
                                    type="text"
                                    name="equipment_name"
                                    required
                                    placeholder="e.g. Dell OptiPlex Desktop"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- ASSET TAG -->
                            <div class="md:col-span-5">
                                <div
                                    class="mb-2 flex items-center justify-between gap-4"
                                >
                                    <label
                                        for="add_equipment_asset_tag"
                                        class="text-sm font-medium text-slate-700"
                                    >
                                        Asset tag
                                    </label>

                                    <span class="text-xs text-slate-400">
                                        Optional
                                    </span>
                                </div>

                                <input
                                    id="add_equipment_asset_tag"
                                    type="text"
                                    name="equipment_asset_tag"
                                    placeholder="e.g. STI-PC-001"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- BRAND -->
                            <div class="md:col-span-4">
                                <label
                                    for="add_equipment_brand"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Brand
                                </label>

                                <input
                                    id="add_equipment_brand"
                                    type="text"
                                    name="equipment_brand_name"
                                    placeholder="e.g. Dell"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- MODEL -->
                            <div class="md:col-span-4">
                                <label
                                    for="add_equipment_model"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Model
                                </label>

                                <input
                                    id="add_equipment_model"
                                    type="text"
                                    name="equipment_model"
                                    placeholder="e.g. OptiPlex 7010"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- SERIAL NUMBER -->
                            <div class="md:col-span-4">
                                <label
                                    for="add_equipment_serial"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Serial number
                                </label>

                                <input
                                    id="add_equipment_serial"
                                    type="text"
                                    name="equipment_serial_number"
                                    placeholder="Enter serial number"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- ===================================== -->
                    <!-- SECTION DIVIDER -->
                    <!-- ===================================== -->
                    <div class="border-t border-slate-100"></div>

                    <!-- ===================================== -->
                    <!-- INVENTORY INFORMATION -->
                    <!-- ===================================== -->
                    <section>
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-slate-900">
                                Inventory information
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Set the initial quantity and equipment condition.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                            <!-- QUANTITY -->
                            <div>
                                <label
                                    for="add_equipment_quantity"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Quantity
                                </label>

                                <input
                                    id="add_equipment_quantity"
                                    type="number"
                                    min="1"
                                    value="1"
                                    name="equipment_quantity"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- CONDITION -->
                            <div>
                                <label
                                    for="add_equipment_condition"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Condition
                                </label>

                                <select
                                    id="add_equipment_condition"
                                    name="equipment_condition_status"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                >
                                    <option value="Good">
                                        Good
                                    </option>

                                    <option value="Damaged">
                                        Damaged
                                    </option>

                                    <option value="Under Maintenance">
                                        Under maintenance
                                    </option>

                                    <option value="Disposed">
                                        Disposed
                                    </option>
                                </select>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="border-t border-dashed border-slate-500"></div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div
                class="flex shrink-0 items-center justify-between gap-4 px-6 py-4"
            >
                <p class="hidden text-xs text-slate-400 sm:block">
                    Required fields must be completed before saving.
                </p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        onclick="closeAddEquipmentModal()"
                        class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200 active:bg-black"
                    >
                        Add equipment
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

    <!-- EDIT EQUIPMENT MODAL -->
    <!-- ========================================================= -->
    <!-- EDIT EQUIPMENT MODAL -->
    <!-- ========================================================= -->
    <div
    id="editEquipmentModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- EDIT EQUIPMENT MODAL -->
    <!-- ===================================== -->
    <div
        class="flex max-h-[80vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div
            class="flex shrink-0 items-start justify-between gap-6 px-6 pb-5 pt-6 border-b border-dashed border-slate-500"
        >
            <div class="min-w-0">
                <p
                    class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                >
                    Equipment Inventory
                </p>

                <h2
                    class="mt-1.5 text-lg font-bold tracking-tight text-slate-950"
                >
                    Edit equipment
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Update equipment details, placement, and inventory information.
                </p>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeEditEquipmentModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- EDIT EQUIPMENT FORM -->
        <!-- ===================================== -->
        <form
            id="editEquipmentForm"
            method="POST"
            class="flex min-h-0 flex-1 flex-col"
        >
            @csrf
            @method("PUT")

            <!-- ===================================== -->
            <!-- SCROLLABLE FORM CONTENT -->
            <!-- ===================================== -->
            <div
                class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100 px-6 py-6"
            >
                <div class="space-y-8">

                    <!-- ===================================== -->
                    <!-- PLACEMENT -->
                    <!-- ===================================== -->
                    <section>
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-slate-900">
                                Placement
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Update the equipment category and assigned room.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                            <!-- CATEGORY -->
                            <div>
                                <label
                                    for="edit_category"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Category
                                </label>

                                <select
                                    id="edit_category"
                                    name="equipment_category_id"
                                    required
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                >
                                    <option value="">
                                        Select category
                                    </option>

                                    @foreach ($categories as $category)
                                        <option
                                            value="{{ $category->equipment_category_id }}"
                                        >
                                            {{ $category->equipment_category_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- ROOM -->
                            <div>
                                <label
                                    for="edit_room"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Room
                                </label>

                                <select
                                    id="edit_room"
                                    name="equipment_room_id"
                                    required
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                >
                                    <option value="">
                                        Select room
                                    </option>

                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->room_id }}">
                                            {{ $room->room_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- ===================================== -->
                    <!-- SECTION DIVIDER -->
                    <!-- ===================================== -->
                    <div class="border-t border-slate-100"></div>

                    <!-- ===================================== -->
                    <!-- EQUIPMENT DETAILS -->
                    <!-- ===================================== -->
                    <section>
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-slate-900">
                                Equipment details
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Update identification and technical information.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-12">

                            <!-- EQUIPMENT NAME -->
                            <div class="md:col-span-7">
                                <label
                                    for="edit_equipment_name"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Equipment name
                                </label>

                                <input
                                    id="edit_equipment_name"
                                    type="text"
                                    name="equipment_name"
                                    required
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- ASSET TAG -->
                            <div class="md:col-span-5">
                                <div
                                    class="mb-2 flex items-center justify-between gap-4"
                                >
                                    <label
                                        for="edit_asset_tag"
                                        class="text-sm font-medium text-slate-700"
                                    >
                                        Asset tag
                                    </label>

                                    <span class="text-xs text-slate-400">
                                        Optional
                                    </span>
                                </div>

                                <input
                                    id="edit_asset_tag"
                                    type="text"
                                    name="equipment_asset_tag"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- BRAND -->
                            <div class="md:col-span-4">
                                <label
                                    for="edit_brand"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Brand
                                </label>

                                <input
                                    id="edit_brand"
                                    type="text"
                                    name="equipment_brand_name"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- MODEL -->
                            <div class="md:col-span-4">
                                <label
                                    for="edit_model"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Model
                                </label>

                                <input
                                    id="edit_model"
                                    type="text"
                                    name="equipment_model"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- SERIAL NUMBER -->
                            <div class="md:col-span-4">
                                <label
                                    for="edit_serial"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Serial number
                                </label>

                                <input
                                    id="edit_serial"
                                    type="text"
                                    name="equipment_serial_number"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- ===================================== -->
                    <!-- SECTION DIVIDER -->
                    <!-- ===================================== -->
                    <div class="border-t border-slate-100"></div>

                    <!-- ===================================== -->
                    <!-- INVENTORY INFORMATION -->
                    <!-- ===================================== -->
                    <section>
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-slate-900">
                                Inventory information
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Update quantity, physical condition, and inventory status.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

                            <!-- QUANTITY -->
                            <div>
                                <label
                                    for="edit_quantity"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Quantity
                                </label>

                                <input
                                    id="edit_quantity"
                                    type="number"
                                    min="1"
                                    name="equipment_quantity"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                />
                            </div>

                            <!-- CONDITION -->
                            <div>
                                <label
                                    for="edit_condition"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Condition
                                </label>

                                <select
                                    id="edit_condition"
                                    name="equipment_condition_status"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                >
                                    <option value="Good">
                                        Good
                                    </option>

                                    <option value="Damaged">
                                        Damaged
                                    </option>

                                    <option value="Under Maintenance">
                                        Under maintenance
                                    </option>

                                    <option value="Disposed">
                                        Disposed
                                    </option>
                                </select>
                            </div>

                            <!-- INVENTORY STATUS -->
                            <div>
                                <label
                                    for="edit_status"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Inventory status
                                </label>

                                <select
                                    id="edit_status"
                                    name="equipment_inventory_status"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                >
                                    <option value="Active">
                                        Active
                                    </option>

                                    <option value="Under Maintenance">
                                        Under maintenance
                                    </option>

                                    <option value="Borrowed">
                                        Borrowed
                                    </option>

                                    <option value="For Replacement">
                                        For replacement
                                    </option>

                                    <option value="Disposed">
                                        Disposed
                                    </option>
                                </select>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="border-t border-dashed border-slate-500"></div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div
                class="flex shrink-0 items-center justify-between gap-4 px-6 py-4"
            >
                <p class="hidden text-xs text-slate-400 sm:block">
                    Changes will update the equipment inventory record.
                </p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        onclick="closeEditEquipmentModal()"
                        class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200 active:bg-black"
                    >
                        Save changes
                    </button>
                </div>
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
            borrowable,
        ) {
            document.getElementById("modal_asset_tag").textContent = assetTag;
            document.getElementById("modal_name").textContent = name;
            document.getElementById("modal_brand").textContent = brand;
            document.getElementById("modal_model").textContent = model;
            document.getElementById("modal_serial").textContent = serial;
            document.getElementById("modal_category").textContent = category;
            document.getElementById("modal_room").textContent = room;
            document.getElementById("modal_quantity").textContent = quantity;
            document.getElementById("modal_condition").textContent = condition;
            document.getElementById("modal_status").textContent = status;
            document.getElementById("modal_purchase_date").textContent =
                purchaseDate;
            document.getElementById("modal_warranty").textContent = warranty;
            document.getElementById("modal_borrowable").textContent =
                borrowable;

            const modal = document.getElementById("viewEquipmentModal");

            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }

        function closeEquipmentModal() {
            const modal = document.getElementById("viewEquipmentModal");

            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }
    </script>

    <script>
        function openAddEquipmentModal() {
            const modal = document.getElementById("addEquipmentModal");

            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }

        function closeAddEquipmentModal() {
            const modal = document.getElementById("addEquipmentModal");

            modal.classList.add("hidden");
            modal.classList.remove("flex");
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
            status,
        ) {
            document.getElementById("editEquipmentForm").action =
                "/maintenance/equipment/update/" + id;

            document.getElementById("edit_equipment_name").value = name;

            document.getElementById("edit_asset_tag").value = assetTag;

            document.getElementById("edit_brand").value = brand;

            document.getElementById("edit_model").value = model;

            document.getElementById("edit_serial").value = serial;

            document.getElementById("edit_quantity").value = quantity;

            document.getElementById("edit_condition").value = condition;

            document.getElementById("edit_status").value = status;

            document
                .getElementById("editEquipmentModal")
                .classList.remove("hidden");

            document.getElementById("editEquipmentModal").classList.add("flex");
        }

        function closeEditEquipmentModal() {
            document
                .getElementById("editEquipmentModal")
                .classList.add("hidden");

            document
                .getElementById("editEquipmentModal")
                .classList.remove("flex");
        }
    </script>

@endsection
