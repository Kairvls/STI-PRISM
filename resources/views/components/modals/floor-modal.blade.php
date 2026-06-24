<div id="floorModal" class="hidden">

    <form
        action="/maintenance/floors/store"
        method="POST"
    >

        @csrf

        <select name="building_id">

            @foreach($buildings as $building)

                <option
                    value="{{ $building->building_id }}"
                >
                    {{ $building->building_name }}
                </option>

            @endforeach

        </select>

        <select name="floor_level">

            <option value="2nd Floor">
                2nd Floor
            </option>

            <option value="3rd Floor">
                3rd Floor
            </option>

        </select>

        <button type="submit">
            Save Floor
        </button>

    </form>

</div>