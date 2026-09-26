<div id="roomModal" class="hidden">

    <form
        action="/maintenance/rooms/store"
        method="POST"
    >

        @csrf

        <select name="floor_id">

            @foreach($floors as $floor)

                <option
                    value="{{ $floor->floor_id }}"
                >
                    {{ $floor->building_name }}
                    -
                    {{ $floor->floor_level }}
                </option>

            @endforeach

        </select>

        <label for="room_name" class="mb-1 block text-sm font-medium text-slate-700">
            Room name <span class="text-red-500">*</span>
        </label>

        <input
            id="room_name"
            type="text"
            name="room_name"
            placeholder="Room Name"
            required
        >

        <button type="submit">
            Save Room
        </button>

    </form>

</div>