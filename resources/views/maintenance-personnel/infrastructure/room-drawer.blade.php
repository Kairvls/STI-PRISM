<div class="h-[700px] overflow-y-auto rounded-3xl bg-white p-6 shadow-lg">
    <h2 class="mb-6 text-2xl font-bold text-black">Room Monitoring</h2>

    <div x-show="selectedRoom == null">
        <div
            class="flex h-full items-center justify-center text-center text-black text-slate-400"
        >
            Select a room on the map.
        </div>
    </div>

    @foreach ($rooms as $room)
        <div
            x-show="
                selectedRoom ==
                {{ $room->room_id }}
            "
        >
            <h3 class="mb-2 text-2xl font-bold text-black">
                {{ $room->room_name }}
            </h3>

            <p class="mb-6 text-sm text-slate-500">
                {{
                    $room->room_type ??
                        "No Room Type"
                }}
            </p>

            <div class="space-y-4">
                <div class="rounded-xl bg-slate-50 p-4 text-black">
                    <div class="font-semibold">Equipment Count</div>

                    <div class="mt-2 text-2xl font-bold">0</div>
                </div>

                <div class="rounded-xl bg-slate-50 p-4 text-black">
                    <div class="font-semibold">Active Reports</div>

                    <div class="mt-2 text-2xl font-bold">0</div>
                </div>

                <div class="rounded-xl bg-slate-50 p-4 text-black">
                    <div class="font-semibold">Frequent Problems</div>

                    <div class="mt-2 text-slate-500">No Data</div>
                </div>

                <div class="rounded-xl bg-slate-50 p-4 text-black">
                    <div class="font-semibold">Maintenance Schedule</div>

                    <div class="mt-2 text-slate-500">No Schedule</div>
                </div>

                <div class="rounded-xl bg-slate-50 p-4 text-black">
                    <div class="font-semibold">Transfer History</div>

                    <div class="mt-2 text-slate-500">No Transfers</div>
                </div>
            </div>
        </div>

    @endforeach
</div>
