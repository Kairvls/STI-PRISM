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

        <input
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