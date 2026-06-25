@extends ("layouts.maintenance-layout")

@section ("title", "Equipment Details")

@section ("content")
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-black text-slate-900">
                    {{ $equipment->equipment_name }}
                </h1>

                <p class="text-slate-500">Equipment Information</p>
            </div>

            <a
                href="/maintenance/equipment/inventory"
                class="rounded-xl border border-slate-300 px-4 py-2"
            >
                Back
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="text-xs text-slate-500"> Asset Tag </label>

                    <p class="font-semibold">
                        {{
                            $equipment->equipment_asset_tag ??
                                "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label class="text-xs text-slate-500">
                        Equipment Name
                    </label>

                    <p class="font-semibold">
                        {{ $equipment->equipment_name }}
                    </p>
                </div>

                <div>
                    <label class="text-xs text-slate-500"> Brand </label>

                    <p class="font-semibold">
                        {{
                            $equipment->equipment_brand_name ??
                                "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label class="text-xs text-slate-500"> Model </label>

                    <p class="font-semibold">
                        {{
                            $equipment->equipment_model ??
                                "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label class="text-xs text-slate-500">
                        Serial Number
                    </label>

                    <p class="font-semibold">
                        {{
                            $equipment->equipment_serial_number ??
                                "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label class="text-xs text-slate-500"> Category </label>

                    <p class="font-semibold">
                        {{ $equipment->equipment_category_name }}
                    </p>
                </div>

                <div>
                    <label class="text-xs text-slate-500"> Room </label>

                    <p class="font-semibold">{{ $equipment->room_name }}</p>
                </div>

                <div>
                    <label class="text-xs text-slate-500"> Quantity </label>

                    <p class="font-semibold">
                        {{ $equipment->equipment_quantity }}
                    </p>
                </div>

                <div>
                    <label class="text-xs text-slate-500"> Condition </label>

                    <p class="font-semibold">
                        {{ $equipment->equipment_condition_status }}
                    </p>
                </div>

                <div>
                    <label class="text-xs text-slate-500"> Status </label>

                    <p class="font-semibold">
                        {{ $equipment->equipment_inventory_status }}
                    </p>
                </div>

                <div>
                    <label class="text-xs text-slate-500">
                        Purchase Date
                    </label>

                    <p class="font-semibold">
                        {{
                            $equipment->equipment_purchase_date ??
                                "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label class="text-xs text-slate-500">
                        Warranty Expiration
                    </label>

                    <p class="font-semibold">
                        {{
                            $equipment->equipment_warranty_expiration ??
                                "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label class="text-xs text-slate-500"> Borrowable </label>

                    <p class="font-semibold">
                        {{
                            $equipment->equipment_is_borrowable
                                ? "Yes"
                                : "No"
                        }}
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
