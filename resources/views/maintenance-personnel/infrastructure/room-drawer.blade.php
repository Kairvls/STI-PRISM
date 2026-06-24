<div
    class="
        bg-white
        rounded-3xl
        shadow-lg
        p-6
        h-[700px]
        overflow-y-auto
    "
>

    <h2 class="text-2xl font-bold mb-6 text-black">
        Room Monitoring
    </h2>

    <div
        x-show="selectedRoom == null"
    >

        <div
            class="
                h-full
                flex
                items-center
                justify-center
                text-slate-400
                text-center
                text-black
            "
        >

            Select a room on the map.

        </div>

    </div>

    @foreach($rooms as $room)

        <div

            x-show="
                selectedRoom ==
                {{ $room->room_id }}
            "

        >

            <h3 class="text-2xl font-bold mb-2 text-black">

                {{ $room->room_name }}

            </h3>

            <p class="text-sm text-slate-500 mb-6">

                {{ $room->room_type ?? 'No Room Type' }}

            </p>

            <div class="space-y-4">

                <div class="bg-slate-50 rounded-xl p-4 text-black">

                    <div class="font-semibold">
                        Equipment Count
                    </div>

                    <div class="text-2xl font-bold mt-2">
                        0
                    </div>

                </div>

                <div class="bg-slate-50 rounded-xl p-4 text-black">

                    <div class="font-semibold">
                        Active Reports
                    </div>

                    <div class="text-2xl font-bold mt-2">
                        0
                    </div>

                </div>

                <div class="bg-slate-50 rounded-xl p-4 text-black">

                    <div class="font-semibold">
                        Frequent Problems
                    </div>

                    <div class="mt-2 text-slate-500">
                        No Data
                    </div>

                </div>

                <div class="bg-slate-50 rounded-xl p-4 text-black">

                    <div class="font-semibold">
                        Maintenance Schedule
                    </div>

                    <div class="mt-2 text-slate-500">
                        No Schedule
                    </div>

                </div>

                <div class="bg-slate-50 rounded-xl p-4 text-black">

                    <div class="font-semibold">
                        Transfer History
                    </div>

                    <div class="mt-2 text-slate-500">
                        No Transfers
                    </div>

                </div>

            </div>

        </div>

    @endforeach

</div>