<!--<aside class="flex h-[900px] flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl">-->
<!--<aside
    class="flex h-auto min-h-0 w-full shrink-0 flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl xl:h-[700px] xl:w-[420px]"
>-->
<aside
    data-monitor-drawer
    class="flex min-h-0 w-full flex-1 flex-col overflow-hidden overscroll-contain rounded-[28px] border border-slate-200 bg-white xl:h-[calc(100vh-14rem-20px)] xl:min-h-[550px] xl:max-h-[620px]"
    x-init="
        $el.addEventListener('wheel', (event) => {
            if (typeof trapDrawerWheel === 'function') {
                trapDrawerWheel(event);
            }
        }, { passive: false });
    "
>
    <!-- Empty state: no room selected -->
    <div x-show="selectedRoom === null" class="flex h-full min-h-0 flex-col overflow-hidden">

        {{-- Panel header --}}
        <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
                <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-slate-400">Room Inspector</p>
                <h2 class="mt-0.5 text-base font-black text-slate-950">Room details</h2>
            </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <!--<button
                                class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50"
                                disabled
                            >
                                <i data-lucide="bookmark" class="h-3.5 w-3.5"></i>
                                Save View
                            </button>-->
                            <button
                                type="button"
                                @click="toggleDrawer()"
                                class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-700"
                                aria-label="Hide panel"
                                data-tooltip="Hide panel"
                            >
                                <i data-lucide="panel-right-close" class="h-3.5 w-3.5"></i>
                            </button>
                        </div>
        </div>

        {{-- Icon tab bar (disabled/ghost) --}}
        <div class="flex shrink-0 items-center border-b border-slate-100 px-4 py-2 gap-1">
            @foreach ([['icon'=>'layout-dashboard','label'=>'Overview'],['icon'=>'box','label'=>'Equipments'],['icon'=>'bar-chart-2','label'=>'Analytics'],['icon'=>'calendar','label'=>'Schedule']] as $t)
            <div class="flex flex-1 flex-col items-center gap-1 rounded-xl px-2 py-2 text-slate-300">
                <i data-lucide="{{ $t['icon'] }}" class="h-4 w-4"></i>
                <span class="text-[9px] font-semibold">{{ $t['label'] }}</span>
            </div>
            @endforeach
        </div>

        {{-- Empty prompt body --}}
        <div class="drawer-scroll flex min-h-0 flex-1 flex-col items-center justify-center gap-4 overflow-y-auto overscroll-contain bg-[#F8FAFC] p-8">
            <div class="flex h-20 w-20 items-center justify-center rounded-2xl border border-blue-100 bg-white shadow-sm">
                <i data-lucide="mouse-pointer-click" class="h-8 w-8 text-[#005EA6]/40"></i>
            </div>
            <div class="text-center">
                <p class="text-sm font-bold text-slate-700">Select a room on the layout</p>
                <p class="mt-1.5 text-xs leading-5 text-slate-400">Equipments, ticket trends, recurring issues, and maintenance schedules will load here.</p>
            </div>

            {{-- Ghost metric rows --}}
            <div class="w-full space-y-2 pt-2">
                @foreach (range(1,3) as $_)
                <div class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white px-3 py-2.5 shadow-sm">
                    <div class="h-7 w-7 shrink-0 rounded-lg bg-blue-50"></div>
                    <div class="flex-1 space-y-1.5">
                        <div class="h-2 w-24 rounded-full bg-slate-100"></div>
                        <div class="h-1.5 w-16 rounded-full bg-[#F8FAFC]"></div>
                    </div>
                    <div class="h-2 w-8 rounded-full bg-blue-100"></div>
                </div>
                @endforeach
            </div>
        </div>
    </div>


    {{-- Lazy-loaded single-room inspector --}}
    <div
        x-show="selectedRoom !== null"
        x-cloak
        class="flex h-full min-h-0 flex-col overflow-hidden"
    >
        <div
            id="roomInspectorMount"
            class="flex h-full min-h-0 flex-col overflow-hidden"
            x-ref="roomInspectorMount"
        >
            <div
                class="flex h-full min-h-0 flex-col items-center justify-center gap-2 bg-[#F8FAFC] p-8"
                data-inspector-loading
            >
                <p class="text-sm font-semibold text-slate-600">Loading…</p>
                <p class="text-xs text-slate-400">Fetching room details</p>
            </div>
        </div>
    </div>

</aside>

<style>
    [data-monitor-drawer],
    [data-monitor-drawer] .drawer-scroll {
        overscroll-behavior: contain;
    }

    [data-monitor-drawer] .drawer-scroll {
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 transparent;
    }

    [data-monitor-drawer] .drawer-scroll::-webkit-scrollbar {
        width: 5px;
    }

    [data-monitor-drawer] .drawer-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    [data-monitor-drawer] .drawer-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }

    [data-monitor-drawer] .drawer-scroll::-webkit-scrollbar-thumb:hover {
        background: #005EA6;
    }

    /* Light scrollbar for add-equipment / item-details modal */
    .eq-modal-scroll {
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 transparent;
    }

    .eq-modal-scroll::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .eq-modal-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .eq-modal-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }

    .eq-modal-scroll::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>

<script>
    document.addEventListener("alpine:init", () => {
        Alpine.data(
            "equipmentCard",
            (
                equipmentId,

                roomId,
            ) => ({
                roomId: roomId,

                menu: false,
                saving: false,

                panel: "",

                transferRoom: "",
                transferRoomLabel: "",
                transferRoomSearch: "",
                transferRoomOpen: false,
                archiveReason: "",

                equipmentId: equipmentId,

                form: null,

                selectTransferRoom(id, label) {
                    this.transferRoom = String(id || "");
                    this.transferRoomLabel = label || "";
                    this.transferRoomOpen = false;
                    this.transferRoomSearch = "";
                },

                matchesTransferRoomSearch(name) {
                    const query = String(this.transferRoomSearch || "").trim().toLowerCase();

                    if (!query) {
                        return true;
                    }

                    return String(name || "").toLowerCase().includes(query);
                },

                hasTransferRoomMatches() {
                    const query = String(this.transferRoomSearch || "").trim().toLowerCase();

                    if (!query) {
                        return true;
                    }

                    const rooms = window.infrastructure?.roomCatalog || [];

                    return rooms.some((room) => {
                        if (Number(room.id) === Number(this.roomId)) {
                            return false;
                        }

                        return String(room.name || "").toLowerCase().includes(query);
                    });
                },

                equipment() {
                    const layout = window.infrastructure.roomLayout;

                    if (layout.open && layout.id === this.roomId) {
                        return layout.equipment.find(
                            (equipment) => equipment.id === this.equipmentId,
                        );
                    }

                    const room = window.infrastructure.roomCatalog.find(
                        (room) => room.id === this.roomId,
                    );

                    if (!room) {
                        return null;
                    }

                    return room.equipment.find(
                        (equipment) => equipment.id === this.equipmentId,
                    );
                },

                placementZones() {
                    return window.infrastructure?.placementZonesForRoom?.(
                        this.roomId,
                        this.form?.location,
                    ) || ['Holding', 'Floor'];
                },

                placementZoneLabel(zone) {
                    return window.infrastructure?.placementZoneLabel?.(zone)
                        || (String(zone || '').trim() === 'Holding' ? 'Holding Area' : (zone || 'â€”'));
                },

                // =====================================
                // Keep edit form synced with live equipment
                // Place BELOW equipment()
                // =====================================

                updateEquipmentPlacement() {
                    if (!this.form) {
                        return;
                    }

                    const pos = window.infrastructure?.zonePosition?.(
                        this.form.location,
                    ) || { x: 50, y: 50 };

                    this.form.x = Array.isArray(pos) ? pos[0] : (pos.x ?? 50);

                    this.form.y = Array.isArray(pos) ? pos[1] : (pos.y ?? 50);

                    this.form.placement_zone = this.form.location;
                },

                // inside Alpine.data('equipmentCard')

                saveEquipment() {
                    this.saving = true;

                    const equipment = this.equipment();

                    fetch(
                        `/admin/infrastructure/equipment/${this.equipmentId}`,
                        {
                            method: "PUT",

                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": document.querySelector(
                                    'meta[name="csrf-token"]',
                                ).content,
                            },

                            body: JSON.stringify({
                                equipment_name: equipment.name,

                                equipment_category_id: equipment.category,

                                equipment_quantity: equipment.quantity,

                                equipment_condition_status: equipment.condition,

                                equipment_current_location: equipment.location,

                                equipment_placement_zone: equipment.location,

                                equipment_position_x: equipment.x,

                                equipment_position_y: equipment.y,
                            }),
                        },
                    )
                        .then((res) => {
                            if (!res.ok) {
                                throw new Error();
                            }

                            return res.json();
                        })

                        .then(async () => {
                            this.panel = "";

                            this.saving = false;

                            await window.infrastructure.refreshRoomEquipment(
                                this.roomId,
                            );
                        })

                        .catch(() => {
                            this.saving = false;

                            alert("Unable to save equipment.");
                        });
                },

                transferEquipment() {
                    if (this.transferRoom === "") {
                        alert("Please select a destination room.");

                        return;
                    }

                    fetch(
                        `/admin/infrastructure/equipment/${this.equipmentId}/transfer`,
                        {
                            method: "POST",

                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": document.querySelector(
                                    'meta[name="csrf-token"]',
                                ).content,
                            },

                            body: JSON.stringify({
                                room_id: this.transferRoom,
                            }),
                        },
                    )
                        .then((res) => {
                            if (!res.ok) {
                                throw new Error();
                            }

                            this.panel = "";
                            this.transferRoom = "";
                            this.transferRoomLabel = "";
                            this.transferRoomOpen = false;
                            this.transferRoomSearch = "";

                            return window.infrastructure.refreshRoomEquipment(
                                this.roomId,
                            );
                        })

                        .catch(() => {
                            alert("Transfer failed.");
                        });
                },

                archiveEquipment() {
                    fetch(
                        `/admin/infrastructure/equipment/${this.equipmentId}`,
                        {
                            method: "DELETE",

                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": document.querySelector(
                                    'meta[name="csrf-token"]',
                                ).content,
                            },

                            body: JSON.stringify({
                                reason: this.archiveReason,
                            }),
                        },
                    )
                        .then((res) => {
                            if (!res.ok) {
                                throw new Error();
                            }

                            this.panel = "";

                            return window.infrastructure.refreshRoomEquipment(
                                this.roomId,
                            );
                        })

                        .catch(() => {
                            alert("Archive failed.");
                        });
                },

                init() {
                    // Initial load
                    this.form = this.equipment();

                    // Keep following future object replacements
                    this.$watch(
                        () => this.equipment(),

                        (equipment) => {
                            if (!equipment) {
                                return;
                            }

                            if (!this.form) {
                                this.form = equipment;

                                return;
                            }

                            Object.assign(this.form, equipment);
                        },
                    );

                    this.$watch("panel", (value) => {
                        const isModal = value === "edit" || value === "transfer" || value === "archive";
                        document.body.style.overflow = isModal ? "hidden" : "";

                        if (value !== "transfer") {
                            this.transferRoomOpen = false;
                            this.transferRoomSearch = "";
                        }

                        if (isModal) {
                            this.$nextTick(() => {
                                if (window.lucide) {
                                    lucide.createIcons();
                                }
                            });
                        }
                    });
                },
            }),
        );
    });
</script>

@include('layouts.partials.equipment-category-detect')
