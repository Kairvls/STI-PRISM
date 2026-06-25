<div
    class="relative h-[700px] overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-100 to-slate-200"
>
    {{-- GRID BACKGROUND --}}

    <div
        class="absolute inset-0 opacity-20"
        style="
            background-image:
                linear-gradient(#94a3b8 1px, transparent 1px),
                linear-gradient(90deg, #94a3b8 1px, transparent 1px);

            background-size: 20px 20px;
        "
    ></div>

    @foreach ($rooms as $room)
        <div
            x-show="
                activeFloor ==
                '{{ $room->floor->floor_level }}'
            "
            @click="
                selectedRoom =
                {{ $room->room_id }}
            "
            class="room-block absolute cursor-move select-none rounded-2xl shadow-lg transition-all duration-150 hover:shadow-xl"
            data-id="{{ $room->room_id }}"
            data-x="{{ $room->room_x }}"
            data-y="{{ $room->room_y }}"
            style="
                left:{{ $room->room_x }}px;
                top:{{ $room->room_y }}px;

                width:{{ $room->room_width }}px;
                height:{{ $room->room_height }}px;

                background:
                {{ $room->room_color ?? '#93C5FD' }};
            "
        >
            {{ $room->room_name }}
        </div>

    @endforeach
</div>

<script>
    window.layoutEditorEnabled = false;

    document.addEventListener("DOMContentLoaded", () => {
        interact(".room-block").draggable({
            inertia: true,

            modifiers: [
                interact.modifiers.snap({
                    targets: [
                        interact.snappers.grid({
                            x: 20,
                            y: 20,
                        }),
                    ],

                    range: Infinity,
                }),
            ],

            listeners: {
                move(event) {
                    if (!window.layoutEditorEnabled) {
                        return;
                    }

                    const target = event.target;

                    let x = (parseFloat(target.dataset.x) || 0) + event.dx;

                    let y = (parseFloat(target.dataset.y) || 0) + event.dy;

                    target.style.left = `${x}px`;

                    target.style.top = `${y}px`;

                    target.dataset.x = x;

                    target.dataset.y = y;
                },
            },
        });
    });
</script>
