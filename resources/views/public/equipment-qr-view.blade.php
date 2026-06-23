<!DOCTYPE html>
<html>

<head>

    <title>
        {{ $equipment->equipment_name }}
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-slate-100">

<div class="max-w-5xl mx-auto p-6">

    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">

        <h1 class="text-4xl font-black text-slate-900 mb-8">

            Equipment Details

        </h1>

        <h2 class="text-2xl font-bold mb-6">

            {{ $equipment->equipment_name }}

        </h2>

        <!-- EQUIPMENT INFO -->

        <div class="grid md:grid-cols-2 gap-4 text-black">

            <div>
                <strong>Asset Tag:</strong>
                {{ $equipment->equipment_asset_tag ?? 'N/A' }}
            </div>

            <div>
                <strong>Brand:</strong>
                {{ $equipment->equipment_brand_name ?? 'N/A' }}
            </div>

            <div>
                <strong>Model:</strong>
                {{ $equipment->equipment_model ?? 'N/A' }}
            </div>

            <div>
                <strong>Serial Number:</strong>
                {{ $equipment->equipment_serial_number ?? 'N/A' }}
            </div>

            <div>
                <strong>Category:</strong>
                {{ $equipment->equipment_category_name }}
            </div>

            <div>
                <strong>Room:</strong>
                {{ $equipment->room_name }}
            </div>

            <div>
                <strong>Condition:</strong>
                {{ $equipment->equipment_condition_status ?? 'N/A' }}
            </div>

            <div>
                <strong>Status:</strong>
                {{ $equipment->equipment_inventory_status }}
            </div>

            <div>
                <strong>Borrowable:</strong>
                {{ $equipment->equipment_is_borrowable ? 'Yes' : 'No' }}
            </div>

        </div>

    </div>

    <!-- MAINTENANCE HISTORY -->

    <div class="mt-8 bg-white rounded-3xl border border-slate-200 p-8">

        <h2 class="text-2xl font-bold mb-6">

            Maintenance History

        </h2>

        @forelse($maintenanceHistory as $history)

        <div class="border-l-4 border-indigo-500 pl-5 mb-6">

            <p class="text-sm text-slate-500">

                {{ \Carbon\Carbon::parse($history->equipment_maintenance_created_at)->format('M d, Y - g:i A') }}

            </p>

            <div class="mt-2">

                <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-semibold">

                    {{ $history->equipment_maintenance_status }}

                </span>

            </div>

            <p class="mt-3">

                <strong>Findings:</strong>

                {{ $history->equipment_maintenance_findings }}
            </p>

            <p class="mt-2">

                <strong>Repair Action:</strong>

                {{ $history->equipment_maintenance_repair_action }}
            </p>

            @if($history->equipment_maintenance_proof_image)

            <a
                href="/storage/{{ $history->equipment_maintenance_proof_image }}"
                target="_blank"
                class="text-blue-600 font-medium mt-2 inline-block">

                View Proof Image

            </a>

            @endif

        </div>

        @empty

        <p class="text-slate-500">

            No maintenance history found.

        </p>

        @endforelse

    </div>

    <!-- TRANSFER HISTORY -->

    <div class="mt-8 bg-white rounded-3xl border border-slate-200 p-8">

        <h2 class="text-2xl font-bold mb-6">

            Transfer History

        </h2>

        @forelse($transferHistory as $transfer)

        <div class="border-l-4 border-emerald-500 pl-5 mb-6">

            <p>

                {{ $transfer->from_room_name }}

                ↓

                {{ $transfer->to_room_name }}

            </p>

            <p class="text-sm text-slate-500 mt-2">
                {{ \Carbon\Carbon::parse($transfer->created_at)->format('M d, Y - g:i A') }}
            </p>

            <p class="mt-2">

                {{ $transfer->remarks }}
            </p>

        </div>

        @empty

        <p class="text-slate-500">

            No transfer history found.

        </p>

        @endforelse

    </div>

</div>

</body>
</html>