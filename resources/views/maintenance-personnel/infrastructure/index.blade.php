@extends ("layouts.maintenance-layout")

@section ("content")
    <div
        x-data="{
            activeFloor: '2nd Floor',

            selectedRoom: null,

            editMode: false,

            toggleEditor() {
                this.editMode = !this.editMode;

                window.layoutEditorEnabled = this.editMode;
            },
        }"
        x-init="window.layoutEditorEnabled = false"
    >
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">
                    Infrastructure Monitoring
                </h1>

                <p class="text-slate-500">Interactive Campus Layout & Room Monitoring</p>
            </div>

            <div class="flex gap-3">
                <button
                    @click="$dispatch('open-wizard')"
                    class="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white"
                >
                    Configure Campus
                </button>

                <button
                    @click="toggleEditor()"
                    :class="editMode
                        ? 'bg-green-600 text-white'
                        : 'bg-yellow-500 text-white'"
                    class="rounded-xl px-5 py-3 font-semibold"
                >
                    <span
                        x-text="editMode ? 'Editor Enabled' : 'Layout Editor'"
                    ></span>
                </button>

                <button
                    id="save-layout-btn"
                    class="rounded-xl bg-green-600 px-5 py-3 font-semibold text-white"
                >
                    Save Layout
                </button>
            </div>
        </div>

        <div class="mb-6 flex gap-3">
            <button
                @click="activeFloor = '2nd Floor'"
                :class="activeFloor === '2nd Floor'
                    ? 'bg-[#005EA6] text-white'
                    : 'bg-white text-slate-700'"
                class="rounded-xl px-6 py-3 shadow"
            >
                2nd Floor
            </button>

            <button
                @click="activeFloor = '3rd Floor'"
                :class="activeFloor === '3rd Floor'
                    ? 'bg-[#005EA6] text-white'
                    : 'bg-white text-slate-700'"
                class="rounded-xl px-6 py-3 shadow"
            >
                3rd Floor
            </button>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-8">
                @include ("maintenance-personnel.infrastructure.floor-canvas")
            </div>

            <div class="col-span-4">
                @include ("maintenance-personnel.infrastructure.room-drawer")
            </div>
        </div>

        @include ("maintenance-personnel.infrastructure.wizard-modal")
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const saveButton = document.getElementById("save-layout-btn");

            if (!saveButton) return;

            saveButton.addEventListener("click", () => {
                const rooms = [];

                document
                    .querySelectorAll(".room-block")

                    .forEach((room) => {
                        rooms.push({
                            id: room.dataset.id,

                            x: room.dataset.x || 0,

                            y: room.dataset.y || 0,
                        });
                    });

                fetch("/maintenance/infrastructure/save-layout", {
                    method: "POST",

                    headers: {
                        "Content-Type": "application/json",

                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]',
                        ).content,
                    },

                    body: JSON.stringify({
                        rooms,
                    }),
                })
                    .then((response) => response.json())

                    .then((data) => {
                        console.log(data);

                        alert("Layout Saved Successfully");
                    })

                    .catch((error) => {
                        console.error(error);

                        alert("Failed To Save Layout");
                    });
            });
        });
    </script>

@endsection
