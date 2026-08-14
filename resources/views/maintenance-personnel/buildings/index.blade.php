@extends('layouts.maintenance-layout')

@section('content')

<div class="text-black">

    <h1 class="text-3xl font-bold mb-8">
        Infrastructure Dashboard
    </h1>

    <div class="grid grid-cols-3 gap-6 mb-10">

        <div class="bg-white rounded-2xl p-6 shadow">

            <p class="text-gray-500">
                Buildings
            </p>

            <h2 class="text-3xl font-bold">
                {{ $totalBuildings }}
            </h2>

        </div>

        <div class="bg-white rounded-2xl p-6 shadow">

            <p class="text-gray-500">
                Floors
            </p>

            <h2 class="text-3xl font-bold">
                {{ $totalFloors }}
            </h2>

        </div>

        <div class="bg-white rounded-2xl p-6 shadow">

            <p class="text-gray-500">
                Rooms
            </p>

            <h2 class="text-3xl font-bold">
                {{ $totalRooms }}
            </h2>

        </div>

    </div>

    <table>

        <thead>

            <tr>

                <th>Building</th>

                <th>Floor</th>

                <th>Room</th>

                <th>Equipment</th>

            </tr>

        </thead>

        <tbody>

            @foreach($rooms as $room)

            <tr>

                <td>
                    {{ $room->building_name }}
                </td>

                <td>
                    {{ $room->floor_level }}
                </td>

                <td>
                    {{ $room->room_name }}
                </td>

                <td>
                    {{ $room->equipment_count }}
                </td>

            </tr>

            @endforeach

        </tbody>

    </table>

    <div class="bg-white rounded-2xl shadow p-6">

    <h2 class="text-xl font-bold mb-6">
        Buildings Layout
    </h2>

    @foreach($buildings as $building)

        <div class="mb-8">

            <h3 class="font-bold text-lg">
                🏢 {{ $building->building_name }}
            </h3>

            @foreach($floors->where('floor_building_id', $building->building_id) as $floor)

                <div class="ml-8 mt-4">

                    <h4 class="font-semibold">
                        📍 {{ $floor->floor_level }}
                    </h4>

                    <ul class="ml-6 mt-2">

                        @foreach($rooms->where('room_floor_id', $floor->floor_id) as $room)

                            <li class="py-1">
                                🚪 {{ $room->room_name }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endforeach

        </div>

    @endforeach

</div>

</div>

@endsection