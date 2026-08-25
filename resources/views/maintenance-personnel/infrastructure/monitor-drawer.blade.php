<!--<aside class="flex h-[900px] flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl">-->
<!--<aside
    class="flex h-auto min-h-0 w-full shrink-0 flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl xl:h-[700px] xl:w-[420px]"
>-->
<aside
    data-monitor-drawer
    class="flex min-h-0 w-full flex-1 flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white xl:h-[calc(100vh-14rem-20px)] xl:min-h-[550px] xl:max-h-[620px]"
>
    <!-- Empty state: no room selected — Insight Builder style -->
    <div x-show="selectedRoom === null" class="flex h-full min-h-0 flex-col overflow-hidden">

        {{-- Panel header --}}
        <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
                <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-slate-400">Room Inspector</p>
                <h2 class="mt-0.5 text-base font-black text-slate-950">Insight Builder</h2>
            </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button
                                class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50"
                                disabled
                            >
                                <i data-lucide="bookmark" class="h-3.5 w-3.5"></i>
                                Save View
                            </button>
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
            @foreach ([['icon'=>'layout-dashboard','label'=>'Overview'],['icon'=>'box','label'=>'Assets'],['icon'=>'bar-chart-2','label'=>'Analytics'],['icon'=>'calendar','label'=>'Schedule']] as $t)
            <div class="flex flex-1 flex-col items-center gap-1 rounded-xl px-2 py-2 text-slate-300">
                <i data-lucide="{{ $t['icon'] }}" class="h-4 w-4"></i>
                <span class="text-[9px] font-semibold">{{ $t['label'] }}</span>
            </div>
            @endforeach
        </div>

        {{-- Empty prompt body --}}
        <div class="drawer-scroll flex min-h-0 flex-1 flex-col items-center justify-center gap-4 overflow-y-auto bg-[#F8FAFC] p-8">
            <div class="flex h-20 w-20 items-center justify-center rounded-2xl border border-blue-100 bg-white shadow-sm">
                <i data-lucide="mouse-pointer-click" class="h-8 w-8 text-[#005EA6]/40"></i>
            </div>
            <div class="text-center">
                <p class="text-sm font-bold text-slate-700">Select a room on the map</p>
                <p class="mt-1.5 text-xs leading-5 text-slate-400">Assets, ticket trends, recurring issues, and maintenance schedules will load here.</p>
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

    @foreach ($rooms as $room)
        <div
            x-show="selectedRoom === {{ $room->room_id }}"
            x-cloak
            x-data="{

                tab:'overview',

                addEquipmentModal:false,

                editRoomModal:false,

                transferAssetsModal:false,

                equipmentRenderKey:0,

                initialEquipmentIds:@js($room->equipment->pluck('equipment_id')->values()),

                liveStackEquipment(){

                    const room = window.infrastructure?.roomCatalog?.find(
                        item => item.id === {{ $room->room_id }}
                    );

                    if(!room || !Array.isArray(room.equipment)){

                        return [];

                    }

                    return room.equipment
                        .filter(eq => !this.initialEquipmentIds.includes(eq.id))
                        .sort((a, b) => Number(b.id || 0) - Number(a.id || 0));

                },

                selectedEquipment:'',

                destinationRoom:'',

                transferMode:'single',

                transferAllRoom:'',

                transferTargets:{},

                transferSelectedIds:[],

                transferApplyRoom:'',

                transferError:'',

                roomSaving:false,

                roomForm:{
                    name:@js($room->room_name),
                    type:@js($room->room_type),
                    color:@js($room->room_color ?: '#60A5FA'),
                    status:@js($room->room_status)
                },

                saving:false,

                addStep:1,

                addItems:[],

                addFullscreen:false,

                addBorrowable:false,

                addErrors:{},

                addFormError:'',

                addAssetTagManual:false,

                applyPlacementZone:'',

                splitPlacementZone:'Holding',

                openPlacementDropdown:null,

                placementMenuStyle:null,

                placementZones(){
                    return window.infrastructure?.placementZonesForRoom?.({{ $room->room_id }})
                        || ['Holding', 'Floor'];
                },

                placementZoneLabel(zone){
                    return window.infrastructure?.placementZoneLabel?.(zone)
                        || (String(zone || '').trim() === 'Holding' ? 'Holding Area' : (zone || '—'));
                },

                closePlacementDropdown(){
                    this.openPlacementDropdown = null;
                    this.placementMenuStyle = null;
                },

                freezeModalScroll(run){
                    const scrollers = Array.from(document.querySelectorAll('.eq-modal-scroll'));
                    const tops = scrollers.map((el) => el.scrollTop);
                    const lefts = scrollers.map((el) => el.scrollLeft);
                    const result = typeof run === 'function' ? run() : null;
                    const restore = () => {
                        scrollers.forEach((el, i) => {
                            el.scrollTop = tops[i];
                            el.scrollLeft = lefts[i];
                        });
                        if (document.activeElement && document.activeElement !== document.body) {
                            document.activeElement.blur();
                        }
                    };
                    restore();
                    this.$nextTick(restore);
                    return result;
                },

                suppressGhostClick(){
                    const suppress = (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        document.removeEventListener('click', suppress, true);
                    };
                    document.addEventListener('click', suppress, true);
                    setTimeout(() => document.removeEventListener('click', suppress, true), 120);
                },

                togglePlacementDropdown(id, event){
                    event?.preventDefault?.();
                    event?.stopPropagation?.();
                    this.suppressGhostClick();

                    if (this.openPlacementDropdown === id) {
                        this.freezeModalScroll(() => this.closePlacementDropdown());
                        return;
                    }

                    const el = event?.currentTarget;
                    if (!el) {
                        this.openPlacementDropdown = id;
                        this.placementMenuStyle = null;
                        return;
                    }

                    const rect = el.getBoundingClientRect();
                    const menuMax = 240;
                    const spaceBelow = window.innerHeight - rect.bottom;
                    const openUp = spaceBelow < menuMax && rect.top > spaceBelow;

                    this.freezeModalScroll(() => {
                        this.placementMenuStyle = {
                            position: 'fixed',
                            left: `${Math.round(rect.left)}px`,
                            width: `${Math.round(rect.width)}px`,
                            zIndex: 10050,
                            maxHeight: `${menuMax}px`,
                            overflowY: 'auto',
                            ...(openUp
                                ? { bottom: `${Math.round(window.innerHeight - rect.top + 4)}px`, top: 'auto' }
                                : { top: `${Math.round(rect.bottom + 4)}px`, bottom: 'auto' }),
                        };
                        this.openPlacementDropdown = id;
                    });
                },

                pickPlacementZone(target, zone){
                    this.suppressGhostClick();
                    this.freezeModalScroll(() => {
                        if (target === 'apply') {
                            this.applyPlacementZone = zone;
                        } else if (target === 'split') {
                            this.splitPlacementZone = zone;
                        } else if (target === 'form') {
                            this.addForm.location = zone;
                        } else if (typeof target === 'number' && !Number.isNaN(target) && this.addItems[target]) {
                            this.addItems[target].equipment_placement_zone = zone;
                        }
                        this.closePlacementDropdown();
                    });
                },

                activePlacementValue(){
                    const id = this.openPlacementDropdown;
                    if (id === 'apply') return this.applyPlacementZone;
                    if (id === 'split') return this.splitPlacementZone;
                    if (id === 'form') return this.addForm.location;
                    if (String(id || '').startsWith('row-')) {
                        const index = Number(String(id).replace('row-', ''));
                        return this.addItems[index]?.equipment_placement_zone || '';
                    }
                    return '';
                },

                addForm:{

                    room_id: {{ $room->room_id }},

                    name:'',

                    category:'',

                    quantity:1,

                    tracking:'Individual',

                    condition:'Good',

                    location:'Holding',

                    brand:'',

                    model:'',

                    warranty:'',

                    assetTag:'',

                    serial:''

                },

                categoryManual:false,

                blankAddForm(){
                    return {
                        room_id: {{ $room->room_id }},
                        name:'',
                        category:'',
                        quantity:1,
                        tracking:'Individual',
                        condition:'Good',
                        location:'Holding',
                        brand:'',
                        model:'',
                        warranty:'',
                        assetTag:'',
                        serial:''
                    };
                },

                resetAddEquipment(){
                    this.addForm = this.blankAddForm();
                    this.addStep = 1;
                    this.addItems = [];
                    this.addFullscreen = false;
                    this.addBorrowable = false;
                    this.addAssetTagManual = false;
                    this.categoryManual = false;
                    this.applyPlacementZone = '';
                    this.splitPlacementZone = 'Holding';
                    this.closePlacementDropdown();
                    this.addErrors = {};
                    this.addFormError = '';
                },

                needsItemDetails(){
                    return this.addForm.tracking === 'Individual'
                        && Number(this.addForm.quantity) > 1;
                },

                clearAddError(field){
                    if (!this.addErrors?.[field]) return;
                    const next = { ...this.addErrors };
                    delete next[field];
                    this.addErrors = next;
                    this.addFormError = '';
                },

                validateAddStep1(){
                    const next = {};
                    if (!String(this.addForm.name || '').trim()) {
                        next.name = 'Equipment name is required.';
                    }
                    const qty = Number(this.addForm.quantity);
                    if (!Number.isFinite(qty) || qty < 1) {
                        next.quantity = 'Quantity must be at least 1.';
                    } else if (qty > 200) {
                        next.quantity = 'Quantity cannot exceed 200.';
                    }
                    this.addErrors = next;
                    this.addFormError = Object.keys(next).length
                        ? 'Please fix the highlighted fields before continuing.'
                        : '';
                    if (Object.keys(next).length) {
                        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                    }
                    return Object.keys(next).length === 0;
                },

                validateAddItems(){
                    const tags = {};
                    const serials = {};
                    for (let i = 0; i < this.addItems.length; i++) {
                        const tag = String(this.addItems[i].equipment_asset_tag || '').trim().toLowerCase();
                        const serial = String(this.addItems[i].equipment_serial_number || '').trim().toLowerCase();
                        if (tag) {
                            if (tags[tag] !== undefined) {
                                this.addFormError = `Duplicate asset tag on rows ${tags[tag] + 1} and ${i + 1}.`;
                                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                                return false;
                            }
                            tags[tag] = i;
                        }
                        if (serial) {
                            if (serials[serial] !== undefined) {
                                this.addFormError = `Duplicate serial number on rows ${serials[serial] + 1} and ${i + 1}.`;
                                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                                return false;
                            }
                            serials[serial] = i;
                        }
                    }
                    this.addFormError = '';
                    return true;
                },

                makeItemRow(index, previous){
                    return {
                        // Keep unique identity if already edited; always refresh shared defaults from step 1.
                        equipment_asset_tag: previous?._tagManual
                            ? previous.equipment_asset_tag
                            : (previous?.equipment_asset_tag ?? this.buildAssetTag(index)),
                        equipment_serial_number: previous?.equipment_serial_number ?? '',
                        equipment_brand_name: this.addForm.brand || '',
                        equipment_model: this.addForm.model || '',
                        equipment_condition_status: this.addForm.condition,
                        equipment_warranty_expiration: this.addForm.warranty || '',
                        equipment_placement_zone: previous?.equipment_placement_zone
                            || this.addForm.location
                            || 'Holding',
                        _tagManual: previous?._tagManual || false,
                    };
                },

                addRoomName(){
                    return @js($room->room_name);
                },

                shouldAutoAddAssetTag(){
                    return this.addForm.tracking === 'Bulk'
                        || Number(this.addForm.quantity) === 1;
                },

                syncAddAssetTag(){
                    if (this.addAssetTagManual || !this.shouldAutoAddAssetTag()) {
                        return;
                    }
                    const roomName = this.addRoomName();
                    const equipmentName = String(this.addForm.name || '').trim();
                    if (!roomName || !equipmentName || typeof window.equipmentAssetTags?.generate !== 'function') {
                        this.addForm.assetTag = '';
                        return;
                    }
                    window.equipmentAssetTags.resetReserved();
                    const tags = window.equipmentAssetTags.generate(roomName, equipmentName, 1);
                    this.addForm.assetTag = tags[0] || '';
                },

                assetLabel(index){
                    const name = String(this.addForm.name || 'Asset').trim() || 'Asset';
                    const pad = String(index + 1).padStart(3, '0');
                    return `${name} ${pad}`;
                },

                resolveZonePosition(zone){
                    const resolver = window.infrastructure?.zonePosition?.bind(window.infrastructure);
                    if (typeof resolver === 'function') {
                        const pos = resolver(zone);
                        if (Array.isArray(pos)) {
                            return { x: Number(pos[0]) || 50, y: Number(pos[1]) || 50 };
                        }
                        if (pos && typeof pos === 'object') {
                            return {
                                x: Number(pos.x) || 50,
                                y: Number(pos.y) || 50,
                            };
                        }
                    }
                    return { x: 50, y: 50 };
                },

                applyPlacementToAll(){
                    const zone = this.applyPlacementZone;
                    if (!zone) {
                        this.addFormError = 'Choose a placement to apply.';
                        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                        return;
                    }
                    this.addItems = this.addItems.map((item) => ({
                        ...item,
                        equipment_placement_zone: zone,
                    }));
                    this.addFormError = '';
                },

                applyPlacementRange(start, end, zone){
                    const from = Math.max(1, Number(start) || 1);
                    const to = Math.min(this.addItems.length, Number(end) || this.addItems.length);
                    if (!zone || from > to) return;
                    this.addItems = this.addItems.map((item, i) => {
                        const n = i + 1;
                        if (n < from || n > to) return item;
                        return { ...item, equipment_placement_zone: zone };
                    });
                },

                assetTagPart(value, fallback){
                    return String(value || '')
                        .toUpperCase()
                        .replace(/[^A-Z0-9]+/g, '')
                        || fallback;
                },

                buildAssetTag(index){
                    const roomName = this.addRoomName();
                    const equipmentName = String(this.addForm.name || '').trim();
                    if (!roomName || !equipmentName || typeof window.equipmentAssetTags?.generate !== 'function') {
                        return '';
                    }
                    window.equipmentAssetTags.resetReserved();
                    const tags = window.equipmentAssetTags.generate(roomName, equipmentName, index + 1);
                    return tags[index] || tags[tags.length - 1] || '';
                },

                buildAddItems(){
                    const qty = Math.min(200, Math.max(1, Number(this.addForm.quantity) || 1));
                    this.addForm.quantity = qty;
                    const previous = this.addItems || [];
                    const roomName = this.addRoomName();
                    const equipmentName = String(this.addForm.name || '').trim();

                    window.equipmentAssetTags?.resetReserved?.();

                    let generated = [];
                    if (roomName && equipmentName && typeof window.equipmentAssetTags?.generate === 'function') {
                        generated = window.equipmentAssetTags.generate(roomName, equipmentName, qty);
                    }

                    this.addItems = Array.from({ length: qty }, (_, i) => {
                        const row = this.makeItemRow(i, previous[i]);
                        if (!row._tagManual && generated[i]) {
                            row.equipment_asset_tag = generated[i];
                        }
                        return row;
                    });
                },

                regenerateAssetTags(){
                    window.equipmentAssetTags?.resetReserved?.();
                    const roomName = this.addRoomName();
                    const equipmentName = String(this.addForm.name || '').trim();
                    const generated = (roomName && equipmentName && typeof window.equipmentAssetTags?.generate === 'function')
                        ? window.equipmentAssetTags.generate(roomName, equipmentName, this.addItems.length)
                        : [];

                    this.addItems = this.addItems.map((item, i) => ({
                        ...item,
                        equipment_asset_tag: generated[i] || this.buildAssetTag(i),
                        _tagManual: false,
                    }));
                },

                applySharedDefaultsToItems(){
                    this.addItems = this.addItems.map((item) => ({
                        ...item,
                        equipment_brand_name: this.addForm.brand || '',
                        equipment_model: this.addForm.model || '',
                        equipment_condition_status: this.addForm.condition,
                        equipment_warranty_expiration: this.addForm.warranty || '',
                    }));
                },

                continueAddEquipment(){
                    if (!this.validateAddStep1()) {
                        return;
                    }
                    const qty = Math.min(200, Math.max(1, Number(this.addForm.quantity) || 1));
                    this.addForm.quantity = qty;

                    if (this.needsItemDetails()) {
                        this.buildAddItems();
                        this.applyPlacementZone = '';
                        this.splitPlacementZone = 'Holding';
                        this.closePlacementDropdown();
                        this.addStep = 2;
                        this.addErrors = {};
                        this.addFormError = '';
                        this.$nextTick(() => {
                            if (window.lucide) window.lucide.createIcons();
                        });
                        return;
                    }

                    this.syncAddAssetTag();
                    this.storeEquipment();
                },

                async storeEquipment(){

                    if (this.addStep === 1 && !this.validateAddStep1()) {
                        return;
                    }
                    if (this.addStep === 2 && !this.validateAddItems()) {
                        return;
                    }

                    if (!this.needsItemDetails()) {
                        this.syncAddAssetTag();
                    }

                    this.saving=true;
                    this.addFormError = '';

                    try{
                    const position = this.resolveZonePosition(
                        this.addForm.location || 'Holding'
                    );

                        const payload = {

                                    equipment_room_id:this.addForm.room_id,

                                    equipment_name:this.addForm.name,

                                    equipment_category_id:this.addForm.category || null,

                                    equipment_quantity: Number(this.addForm.quantity) || 1,

                                    equipment_tracking_mode:this.addForm.tracking,

                                    equipment_condition_status:this.addForm.condition,

                                    equipment_current_location:this.addForm.location || 'Holding',

                                    equipment_placement_zone:this.addForm.location || 'Holding',

                                    equipment_brand_name: this.addForm.brand || null,

                                    equipment_model: this.addForm.model || null,

                                    equipment_warranty_expiration: this.addForm.warranty || null,

                                    equipment_asset_tag: this.addForm.assetTag || null,

                                    equipment_serial_number: this.addForm.serial || null,

                                    equipment_is_borrowable: this.addBorrowable ? 1 : 0,

                                    equipment_position_x:position.x,

                                    equipment_position_y:position.y

                                };

                        if (this.addForm.tracking === 'Individual' && this.addItems.length > 0) {
                            payload.items = this.addItems.map(({ _tagManual, ...item }) => {
                                const zone = item.equipment_placement_zone || this.addForm.location || 'Holding';
                                return {
                                    ...item,
                                    equipment_placement_zone: zone,
                                    equipment_current_location: zone,
                                };
                            });
                            payload.equipment_quantity = this.addItems.length;
                            payload.equipment_asset_tag = null;
                            payload.equipment_serial_number = null;
                        }

                        const createdTags = this.addForm.tracking === 'Individual' && this.addItems.length > 0
                            ? this.addItems.map((item) => item.equipment_asset_tag).filter(Boolean)
                            : (payload.equipment_asset_tag ? [payload.equipment_asset_tag] : []);

                        const response=await fetch(

                            '/maintenance/infrastructure/equipment',

                            {

                                method:'POST',

                                headers:{

                                    'Content-Type':'application/json',

                                    'Accept':'application/json',

                                    'X-CSRF-TOKEN':document.querySelector(
                                        'meta[name=csrf-token]'
                                    ).content

                                },

                                body:JSON.stringify(payload)

                            }

                        );

                        if(!response.ok){
                            let message = 'Unable to create equipment.';
                            const fieldErrors = {};
                            try {
                                const err = await response.json();
                                if (err?.errors) {
                                    Object.entries(err.errors).forEach(([key, messages]) => {
                                        const msg = Array.isArray(messages) ? messages[0] : String(messages);
                                        if (key.includes('equipment_name')) fieldErrors.name = msg;
                                        else if (key.includes('equipment_category')) fieldErrors.category = msg;
                                        else if (key.includes('location') || key.includes('current_location')) fieldErrors.location = msg;
                                        else if (key.includes('quantity')) fieldErrors.quantity = msg;
                                    });
                                    message = Object.values(err.errors).flat().join(' ');
                                } else if (err?.message) {
                                    message = err.message;
                                }
                            } catch (_) {}
                            if (Object.keys(fieldErrors).length) {
                                this.addErrors = fieldErrors;
                                this.addStep = 1;
                            }
                            this.addFormError = message;
                            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                            return;
                        }

                        const roomId = this.addForm.room_id;
                        window.equipmentAssetTags?.register?.(createdTags);
                        this.resetAddEquipment();
                        this.addEquipmentModal = false;
                        document.body.style.overflow = '';

                        await window.infrastructure.refreshRoomEquipment(roomId);

                        this.equipmentRenderKey += 1;

                    }

                    catch(e){

                        this.addFormError = e?.message || 'Unable to create equipment.';
                        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });

                    }

                    finally{

                        this.saving=false;

                    }

                },

                async saveRoom(){

                    this.roomSaving = true;

                    try{

                        const response = await fetch(
                            `/maintenance/infrastructure/rooms/{{ $room->room_id }}`,
                            {

                                method:'PUT',

                                headers:{
                                    'Content-Type':'application/json',
                                    'Accept':'application/json',
                                    'X-CSRF-TOKEN':document.querySelector(
                                        'meta[name=csrf-token]'
                                    ).content
                                },

                                body:JSON.stringify({

                                    room_name:this.roomForm.name,

                                    room_type:this.roomForm.type,

                                    room_color:this.roomForm.color,

                                    room_status:this.roomForm.status

                                })

                            }
                        );

                        if(!response.ok){

                            throw new Error();

                        }

                        const payload = await response.json();

                        window.infrastructure.applyRoomUpdate(
                            {{ $room->room_id }},
                            {
                                name: payload?.room?.name ?? this.roomForm.name,
                                type: this.roomForm.type,
                                color: payload?.room?.color ?? this.roomForm.color,
                                status: this.roomForm.status,
                            }
                        );

                        this.editRoomModal = false;

                        await window.infrastructure.refreshRoomEquipment({{ $room->room_id }});

                    }

                    catch(e){

                        alert('Unable to update room.');

                    }

                    finally{

                        this.roomSaving = false;

                    }

                },

                roomEquipmentCount(){
                    const room = window.infrastructure?.roomCatalog?.find(
                        (item) => item.id === {{ $room->room_id }}
                    );
                    if (room && Array.isArray(room.equipment)) {
                        return room.equipment.length;
                    }
                    return {{ (int) $room->equipment->count() }};
                },

                openTransferModal(){
                    this.resetTransferForm();
                    this.transferAssetsModal = true;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                resetTransferForm(){
                    this.transferMode = 'single';
                    this.selectedEquipment = '';
                    this.destinationRoom = '';
                    this.transferAllRoom = '';
                    this.transferTargets = {};
                    this.transferSelectedIds = [];
                    this.transferApplyRoom = '';
                    this.transferError = '';
                    this.initTransferTargets();
                },

                transferSourceEquipment(){
                    const room = window.infrastructure?.roomCatalog?.find(
                        (item) => item.id === {{ $room->room_id }}
                    );
                    if (room && Array.isArray(room.equipment) && room.equipment.length) {
                        return room.equipment.map((item) => ({
                            id: Number(item.id),
                            name: item.name || item.asset_tag || ('Asset #' + item.id),
                        }));
                    }
                    return @js($room->equipment->map(fn ($e) => [
                        'id' => (int) $e->equipment_id,
                        'name' => $e->equipment_name,
                    ])->values());
                },

                initTransferTargets(){
                    const next = {};
                    this.transferSourceEquipment().forEach((item) => {
                        next[item.id] = this.transferTargets[item.id] || '';
                    });
                    this.transferTargets = next;
                },

                setTransferMode(mode){
                    this.transferMode = mode;
                    this.transferError = '';
                    if (mode === 'custom') {
                        this.initTransferTargets();
                    }
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                toggleTransferSelected(id){
                    const key = Number(id);
                    if (this.transferSelectedIds.includes(key)) {
                        this.transferSelectedIds = this.transferSelectedIds.filter((item) => item !== key);
                    } else {
                        this.transferSelectedIds = [...this.transferSelectedIds, key];
                    }
                },

                selectAllTransferEquipment(){
                    this.transferSelectedIds = this.transferSourceEquipment().map((item) => item.id);
                },

                clearTransferSelection(){
                    this.transferSelectedIds = [];
                },

                applyDestinationToSelected(){
                    if (!this.transferApplyRoom) {
                        this.transferError = 'Choose a destination room to apply.';
                        return;
                    }
                    if (!this.transferSelectedIds.length) {
                        this.transferError = 'Select at least one equipment item first.';
                        return;
                    }
                    const next = { ...this.transferTargets };
                    this.transferSelectedIds.forEach((id) => {
                        next[id] = this.transferApplyRoom;
                    });
                    this.transferTargets = next;
                    this.transferError = '';
                },

                setAllCustomDestinations(roomId){
                    if (!roomId) return;
                    const next = {};
                    this.transferSourceEquipment().forEach((item) => {
                        next[item.id] = roomId;
                    });
                    this.transferTargets = next;
                    this.transferError = '';
                },

                buildTransferPayload(){
                    if (this.transferMode === 'single') {
                        if (!this.selectedEquipment || !this.destinationRoom) {
                            this.transferError = 'Select equipment and a destination room.';
                            return null;
                        }
                        return [{
                            equipment_id: Number(this.selectedEquipment),
                            room_id: Number(this.destinationRoom),
                        }];
                    }

                    if (this.transferMode === 'all') {
                        if (!this.transferAllRoom) {
                            this.transferError = 'Select a destination room for all equipment.';
                            return null;
                        }
                        const items = this.transferSourceEquipment();
                        if (!items.length) {
                            this.transferError = 'This room has no equipment to transfer.';
                            return null;
                        }
                        return items.map((item) => ({
                            equipment_id: item.id,
                            room_id: Number(this.transferAllRoom),
                        }));
                    }

                    const transfers = this.transferSourceEquipment()
                        .map((item) => ({
                            equipment_id: item.id,
                            room_id: Number(this.transferTargets[item.id] || 0),
                        }))
                        .filter((entry) => entry.room_id);

                    if (!transfers.length) {
                        this.transferError = 'Assign at least one equipment item to a destination room.';
                        return null;
                    }

                    return transfers;
                },

                async transferAsset(){
                    this.transferError = '';
                    const transfers = this.buildTransferPayload();
                    if (!transfers) {
                        this.$nextTick(() => {
                            if (window.lucide) window.lucide.createIcons();
                        });
                        return;
                    }

                    this.roomSaving = true;

                    try {
                        let response;
                        if (this.transferMode === 'single' && transfers.length === 1) {
                            response = await fetch(
                                `/maintenance/infrastructure/equipment/${transfers[0].equipment_id}/transfer`,
                                {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    },
                                    body: JSON.stringify({ room_id: transfers[0].room_id }),
                                },
                            );
                        } else {
                            response = await fetch(
                                `/maintenance/infrastructure/rooms/{{ $room->room_id }}/transfer-equipment`,
                                {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    },
                                    body: JSON.stringify({ transfers }),
                                },
                            );
                        }

                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const firstError = payload?.errors
                                ? Object.values(payload.errors).flat()[0]
                                : null;
                            throw new Error(firstError || payload.message || 'Unable to transfer asset.');
                        }

                        this.transferAssetsModal = false;
                        this.resetTransferForm();
                        await window.infrastructure.refreshRoomEquipment({{ $room->room_id }});
                        window.location.reload();
                    } catch (e) {
                        this.transferError = e?.message || 'Unable to transfer asset.';
                        this.$nextTick(() => {
                            if (window.lucide) window.lucide.createIcons();
                        });
                    } finally {
                        this.roomSaving = false;
                    }
                },

            }"
            class="flex h-full min-h-0 flex-col overflow-hidden"
        >
            {{-- ── Insight Builder: Room header (white, clean) ── --}}
            <div class="shrink-0 border-b border-slate-100">

                {{-- Title row --}}
                <div class="flex items-start justify-between gap-3 px-5 py-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-slate-400">
                            {{ $room->floor->building->building_name ?? "STI Ormoc" }} · {{ $room->floor->floor_level }}
                        </p>
                        <h2 class="mt-0.5 truncate text-base font-black text-slate-950" x-text="roomForm.name || 'Not Specified'"></h2>
                        <p class="mt-0.5 truncate text-xs font-medium text-slate-400" x-text="roomForm.type || 'No Room Type'"></p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <button
                            class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50"
                            disabled
                        >
                            <i data-lucide="bookmark" class="h-3 w-3"></i>
                            Save View
                        </button>
                        <button
                            type="button"
                            @click="toggleDrawer()"
                            class="flex h-7 w-7 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400 shadow-sm transition hover:bg-slate-50 hover:text-slate-600"
                            aria-label="Hide panel"
                            data-tooltip="Hide panel"
                        >
                            <i data-lucide="panel-right-close" class="h-3.5 w-3.5"></i>
                        </button>
                        <button
                            @click="selectedRoom = null"
                            class="flex h-7 w-7 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400 shadow-sm transition hover:bg-slate-50 hover:text-slate-600"
                            aria-label="Close room details"
                        >
                            <i data-lucide="x" class="h-3.5 w-3.5"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Insight Builder: Icon tab bar ── --}}
            <div class="flex shrink-0 items-stretch border-b border-slate-100">
                @foreach ([
                    ['key'=>'overview',   'icon'=>'layout-dashboard', 'label'=>'Overview'],
                    ['key'=>'equipment',  'icon'=>'box',              'label'=>'Assets'],
                    ['key'=>'analytics',  'icon'=>'bar-chart-2',      'label'=>'Analytics'],
                    ['key'=>'schedule',   'icon'=>'calendar-clock',   'label'=>'Schedule'],
                    ['key'=>'history',    'icon'=>'history',          'label'=>'History'],
                ] as $t)
                <button
                    @click="tab = '{{ $t['key'] }}'"
                    :class="tab === '{{ $t['key'] }}'
                        ? 'border-b-2 border-[#005EA6] text-[#005EA6] bg-blue-50/60'
                        : 'border-b-2 border-transparent text-slate-400 hover:text-[#005EA6] hover:bg-[#F8FAFC]'"
                    class="flex flex-1 flex-col items-center gap-1 px-1 pb-2.5 pt-3 text-[8.5px] font-semibold uppercase tracking-wide transition"
                >
                    <i data-lucide="{{ $t['icon'] }}" class="h-4 w-4"></i>
                    <span>{{ $t['label'] }}</span>
                </button>
                @endforeach
            </div>

            <div
                class="drawer-scroll relative min-h-0 flex-1 overflow-y-auto overflow-x-hidden bg-white p-5"
            >
                <div x-show="tab === 'overview'" x-cloak class="space-y-6">
                    @php
                        $eqCount = (int) $room->monitoring['equipment_count'];
                        $eqQty = (int) $room->monitoring['equipment_quantity'];
                        $eqDenom = max(1, $eqCount);
                        $eqGood = (int) $room->monitoring['equipment_good'];
                        $eqMaint = (int) $room->monitoring['equipment_maintenance'];
                        $eqDamaged = (int) $room->monitoring['equipment_damaged'];
                        $eqDisposed = (int) $room->monitoring['equipment_disposed'];
                        $pctGood = round(($eqGood / $eqDenom) * 100);
                        $pctMaint = round(($eqMaint / $eqDenom) * 100);
                        $pctDamaged = round(($eqDamaged / $eqDenom) * 100);
                        $pctDisposed = round(($eqDisposed / $eqDenom) * 100);
                        $healthRows = [
                            ['label' => 'Good', 'count' => $eqGood, 'pct' => $pctGood, 'bar' => '#005EA6'],
                            ['label' => 'Maintenance', 'count' => $eqMaint, 'pct' => $pctMaint, 'bar' => '#93C5FD'],
                            ['label' => 'Damaged', 'count' => $eqDamaged, 'pct' => $pctDamaged, 'bar' => '#64748B'],
                            ['label' => 'Disposed', 'count' => $eqDisposed, 'pct' => $pctDisposed, 'bar' => '#CBD5E1'],
                        ];
                    @endphp

                    {{-- ── Status hero ── --}}
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-50/80 via-white to-[#F8FAFC]"></div>
                        <div class="relative flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-[.16em] text-slate-400">Current status</p>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <span
                                        class="h-2.5 w-2.5 shrink-0 rounded-full bg-[#005EA6] ring-4 ring-blue-50"
                                        :class="currentRoom?.monitoring?.room_information?.status === 'Critical'
                                            ? 'bg-slate-500 ring-slate-100'
                                            : currentRoom?.monitoring?.room_information?.status === 'Maintenance Needed'
                                                ? 'bg-blue-400 ring-blue-50'
                                                : 'bg-[#005EA6] ring-blue-50'"
                                    ></span>
                                    <p
                                        class="truncate text-lg font-black text-slate-900"
                                        x-text="currentRoom?.monitoring?.room_information?.status || 'Normal'"
                                    ></p>
                                </div>
                                <p class="mt-1 text-[11px] font-medium text-slate-400">
                                    Updated
                                    <span
                                        class="text-[#005EA6]"
                                        x-text="currentRoom.monitoring.room_information.last_updated
                                            ? timeAgo(currentRoom.monitoring.room_information.last_updated)
                                            : 'Unknown'"
                                    ></span>
                                </p>
                            </div>
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-[#005EA6]">
                                <i data-lucide="activity" class="h-5 w-5"></i>
                            </div>
                        </div>
                    </div>

                    {{-- ── Key metrics ── --}}
                    <div class="grid grid-cols-2 divide-x divide-slate-200 border-y border-slate-200 bg-white">
                        <div class="px-4 py-3">
                            <p class="text-[11px] font-medium tracking-[0.16em] text-slate-400 uppercase">Assets</p>
                            <p class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ $room->monitoring["equipment_quantity"] }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">registered</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] font-medium tracking-[0.16em] text-slate-400 uppercase">Reports</p>
                            <p class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ $room->monitoring["active_reports"] }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">unresolved</p>
                        </div>
                    </div>

                    {{-- Schedule --}}
                    <section>
                        <p class="mb-3 text-[11px] font-medium tracking-[0.18em] text-slate-400 uppercase">Schedule</p>
                        <div class="space-y-0 border-y border-slate-200">
                            <div class="flex items-start gap-3 py-3.5">
                                <i data-lucide="clipboard-check" class="mt-0.5 h-4 w-4 shrink-0 text-[#005EA6]"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[11px] text-slate-400">Last inspection</p>
                                    <p
                                        class="mt-0.5 text-sm font-medium leading-5 text-slate-900"
                                        x-text="currentRoom?.monitoring?.room_information?.last_inspection
                                            ? formatDate(currentRoom.monitoring.room_information.last_inspection)
                                            : 'Never'"
                                    ></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 border-t border-slate-100 py-3.5">
                                <i data-lucide="calendar-clock" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[11px] text-slate-400">Next maintenance</p>
                                    <p
                                        class="mt-0.5 text-sm font-medium leading-5 text-slate-900"
                                        x-text="currentRoom?.monitoring?.room_information?.next_maintenance
                                            ? formatDate(currentRoom.monitoring.room_information.next_maintenance)
                                            : 'No schedule'"
                                    ></p>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- ── Equipment health bar ── --}}
                    @php
                        $eqTotal = max(1, (int) $room->monitoring['equipment_quantity']);
                        $eqGood = (int) $room->monitoring['equipment_good'];
                        $eqMaint = (int) $room->monitoring['equipment_maintenance'];
                        $eqDamaged = (int) $room->monitoring['equipment_damaged'];
                        $eqDisposed = (int) $room->monitoring['equipment_disposed'];
                        $pctGood = round(($eqGood / $eqTotal) * 100);
                        $pctMaint = round(($eqMaint / $eqTotal) * 100);
                        $pctDamaged = round(($eqDamaged / $eqTotal) * 100);
                        $pctDisposed = round(($eqDisposed / $eqTotal) * 100);
                    @endphp
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-slate-800">Equipment health</p>
                                <p class="text-[11px] text-slate-400">Condition breakdown</p>
                            </div>
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold text-[#005EA6]">{{ $eqTotal }} total</span>
                        </div>

                        {{-- Segmented bar — blue & gray tones --}}
                        <div class="mt-3 flex h-2.5 overflow-hidden rounded-full bg-slate-100">
                            @if ($eqGood > 0)
                                <div class="bg-[#005EA6] transition-all" style="width: {{ $pctGood }}%"></div>
                            @endif
                            @if ($eqMaint > 0)
                                <div class="bg-blue-300 transition-all" style="width: {{ $pctMaint }}%"></div>
                            @endif
                            @if ($eqDamaged > 0)
                                <div class="bg-slate-400 transition-all" style="width: {{ $pctDamaged }}%"></div>
                            @endif
                            @if ($eqDisposed > 0)
                                <div class="bg-slate-200 transition-all" style="width: {{ $pctDisposed }}%"></div>
                            @endif
                        </div>
                    </div>

                    {{-- Modern legend list (outside card) --}}
                    <div class="space-y-3 px-1">
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <span class="text-[13px] text-slate-600">Good</span>
                                <span class="text-[13px] font-medium tabular-nums text-slate-950">{{ $eqGood }}<span class="ml-1 font-normal text-slate-400">{{ $pctGood }}%</span></span>
                            </div>
                            <div class="h-px bg-slate-100">
                                <div class="h-px" style="width: {{ $pctGood }}%; background: #005EA6"></div>
                            </div>
                        </div>
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <span class="text-[13px] text-slate-600">Maint.</span>
                                <span class="text-[13px] font-medium tabular-nums text-slate-950">{{ $eqMaint }}<span class="ml-1 font-normal text-slate-400">{{ $pctMaint }}%</span></span>
                            </div>
                            <div class="h-px bg-slate-100">
                                <div class="h-px" style="width: {{ $pctMaint }}%; background: #93C5FD"></div>
                            </div>
                        </div>
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <span class="text-[13px] text-slate-600">Damaged</span>
                                <span class="text-[13px] font-medium tabular-nums text-slate-950">{{ $eqDamaged }}<span class="ml-1 font-normal text-slate-400">{{ $pctDamaged }}%</span></span>
                            </div>
                            <div class="h-px bg-slate-100">
                                <div class="h-px" style="width: {{ $pctDamaged }}%; background: #64748B"></div>
                            </div>
                        </div>
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <span class="text-[13px] text-slate-600">Disposed</span>
                                <span class="text-[13px] font-medium tabular-nums text-slate-950">{{ $eqDisposed }}<span class="ml-1 font-normal text-slate-400">{{ $pctDisposed }}%</span></span>
                            </div>
                            <div class="h-px bg-slate-100">
                                <div class="h-px" style="width: {{ $pctDisposed }}%; background: #CBD5E1"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Quick actions --}}
                    <section>
                        <p class="mb-3 text-[11px] font-medium tracking-[0.18em] text-slate-400 uppercase">Quick actions</p>
                        <div class="grid grid-cols-4 overflow-hidden rounded-xl border border-slate-200">
                            <button
                                type="button"
                                @click="categoryManual = false; resetAddEquipment(); addEquipmentModal = true"
                                class="flex flex-col items-center gap-1.5 px-1 py-3.5 text-center transition hover:bg-slate-50"
                            >
                                <i data-lucide="plus" class="h-4 w-4 text-[#005EA6]"></i>
                                <span class="text-[11px] font-medium text-slate-800">Equipment</span>
                            </button>
                            <button
                                type="button"
                                @click="editRoomModal = true"
                                class="flex flex-col items-center gap-1.5 border-l border-slate-200 px-1 py-3.5 text-center transition hover:bg-slate-50"
                            >
                                <i data-lucide="pencil" class="h-4 w-4 text-slate-700"></i>
                                <span class="text-[11px] font-medium text-slate-800">Room</span>
                            </button>
                            <button
                                type="button"
                                @click="openTransferModal()"
                                class="flex flex-col items-center gap-1.5 border-l border-slate-200 px-1 py-3.5 text-center transition hover:bg-slate-50"
                            >
                                <i data-lucide="arrow-right-left" class="h-4 w-4 text-slate-700"></i>
                                <span class="text-[11px] font-medium text-slate-800">Transfer</span>
                            </button>
                            <a
                                href="{{ url('/maintenance/rooms') }}?history={{ $room->room_id }}"
                                class="flex flex-col items-center gap-1.5 border-l border-slate-200 px-1 py-3.5 text-center transition hover:bg-slate-50"
                            >
                                <i data-lucide="history" class="h-4 w-4 text-slate-400"></i>
                                <span class="text-[11px] font-medium text-slate-800">History</span>
                            </a>
                        </div>
                    </section>
                </div>

                <!-- ========================================= -->
                <!-- Add Equipment Modal (Inventory-matched UI) -->
                <!-- ========================================= -->

                <template x-if="addEquipmentModal">
                    <div
                        x-transition.opacity
                        x-init="document.body.style.overflow = 'hidden'"
                        @click.self="
                            if (!(addFullscreen && addStep === 2)) {
                                addEquipmentModal = false;
                                resetAddEquipment();
                                document.body.style.overflow = '';
                            }
                        "
                        @keydown.escape.window="
                            if (addFullscreen && addStep === 2) {
                                addFullscreen = false;
                            } else {
                                addEquipmentModal = false;
                                resetAddEquipment();
                                document.body.style.overflow = '';
                            }
                        "
                        class="fixed inset-0 z-[9999] flex items-center justify-center bg-[#0b1220]/70"
                        :class="addFullscreen && addStep === 2 ? 'p-0' : 'p-4'"
                    >
                        <div
                            class="relative flex w-full flex-col overflow-hidden border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
                            :class="addFullscreen && addStep === 2
                                ? 'h-[100dvh] max-h-[100dvh] max-w-none rounded-none border-0 shadow-none'
                                : (addStep === 2
                                    ? 'max-h-[90vh] max-w-[80rem] rounded-2xl'
                                    : 'max-h-[90vh] max-w-4xl rounded-2xl')"
                        >
                            <div class="flex items-start justify-between px-6 pt-6">
                                <div>
                                    <h2 class="text-lg font-semibold tracking-tight text-slate-900" x-text="addStep === 2 ? 'Assign equipment' : 'Add equipment'"></h2>
                                    <p class="mt-1 text-sm text-slate-500" x-text="addStep === 2
                                        ? (addItems.length + ' individual assets will be created. Set placement (Holding Area, Floor, or a row), asset tag, and serial per unit.')
                                        : 'Identity on the left, status on the right.'"></p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <button
                                        type="button"
                                        x-show="addStep === 2"
                                        x-cloak
                                        @click="addFullscreen = !addFullscreen; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                                        class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                                        :title="addFullscreen ? 'Exit full screen' : 'Full screen'"
                                        :aria-label="addFullscreen ? 'Exit full screen' : 'Full screen'"
                                    >
                                        <i :data-lucide="addFullscreen ? 'minimize-2' : 'maximize-2'" class="h-4 w-4"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="addEquipmentModal = false; resetAddEquipment(); document.body.style.overflow = '';"
                                        class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                                        aria-label="Close"
                                    >
                                        <i data-lucide="x" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>

                            <div
                                x-show="addFormError"
                                x-cloak
                                class="mx-6 mt-4 flex items-start gap-3 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100"
                            >
                                <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                                <p class="min-w-0 flex-1 leading-relaxed" x-text="addFormError"></p>
                                <button type="button" @click="addFormError = ''" class="shrink-0 rounded-lg p-1 text-rose-400 transition hover:bg-rose-100 hover:text-rose-700" aria-label="Dismiss">
                                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                </button>
                            </div>

                            <div class="eq-modal-scroll min-h-0 flex-1 overflow-y-auto px-6 py-5" x-show="addStep === 1">
                                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">What & where</p>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Equipment name <span class="text-rose-500">*</span></label>
                                            <input
                                                type="text"
                                                x-model="addForm.name"
                                                @input="
                                                    clearAddError('name');
                                                    if (!String(addForm.name || '').trim()) {
                                                        categoryManual = false;
                                                        addForm.category = '';
                                                    } else if (!categoryManual && typeof detectEquipmentCategoryId === 'function') {
                                                        addForm.category = detectEquipmentCategoryId(addForm.name) || '';
                                                    }
                                                    syncAddAssetTag();
                                                "
                                                placeholder="e.g. Mouse"
                                                class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                                                :class="addErrors.name ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                            />
                                            <p x-show="addErrors.name" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="addErrors.name"></p>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
                                            <select
                                                x-model="addForm.category"
                                                @change="
                                                    clearAddError('category');
                                                    if (
                                                        typeof detectEquipmentCategoryId === 'function'
                                                        && String(addForm.category) === String(detectEquipmentCategoryId(addForm.name) || '')
                                                    ) {
                                                        return;
                                                    }
                                                    categoryManual = true;
                                                "
                                                class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                                            >
                                                <option value="">Select category</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->equipment_category_id }}">
                                                        {{ $category->equipment_category_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <p class="mt-1.5 text-xs text-slate-400">Filled from the equipment name. You can still choose another category.</p>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Room</label>
                                            <div class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10 flex items-center text-slate-700">
                                                {{ $room->room_name }}
                                            </div>
                                        </div>
                                        <div x-show="!needsItemDetails()">
                                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Placement</label>
                                            <div class="relative">
                                                <button
                                                    type="button"
                                                    data-placement-trigger
                                                    @mousedown.prevent="togglePlacementDropdown('form', $event)"
                                                    class="flex h-11 w-full items-center justify-between gap-2 rounded-xl border-0 bg-slate-50 px-3.5 text-left text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition hover:bg-white focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                                                >
                                                    <span class="min-w-0 truncate" x-text="placementZoneLabel(addForm.location)"></span>
                                                    <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400"></i>
                                                </button>
                                            </div>
                                            <p class="mt-1.5 text-xs text-slate-400">Holding Area = unplaced. Floor = floor icon. Row = assign to a row table.</p>
                                        </div>
                                        <div x-show="needsItemDetails()" x-cloak>
                                            <p class="rounded-xl bg-white px-3.5 py-3 text-xs text-slate-500 ring-1 ring-slate-200/80">
                                                Placement is set per unit on the next step (Holding Area, Floor, or a row).
                                            </p>
                                        </div>
                                    </div>

                                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status</p>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Qty</label>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    max="200"
                                                    x-model.number="addForm.quantity"
                                                    @input="clearAddError('quantity'); syncAddAssetTag()"
                                                    class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                                                    :class="addErrors.quantity ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                                />
                                                <p x-show="addErrors.quantity" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="addErrors.quantity"></p>
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Condition</label>
                                                <select x-model="addForm.condition" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10">
                                                    <option value="Good">Good</option>
                                                    <option value="Damaged">Damaged</option>
                                                    <option value="Under Maintenance">Under maintenance</option>
                                                    <option value="Disposed">Disposed</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Tracking mode</label>
                                            <div class="flex h-11 rounded-xl bg-slate-100 p-1">
                                                <button type="button" @click="addForm.tracking = 'Bulk'; addAssetTagManual = false; syncAddAssetTag()" class="flex-1 rounded-lg text-sm font-medium transition" :class="addForm.tracking === 'Bulk' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Bulk</button>
                                                <button type="button" @click="addForm.tracking = 'Individual'; addAssetTagManual = false; syncAddAssetTag()" class="flex-1 rounded-lg text-sm font-medium transition" :class="addForm.tracking === 'Individual' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Individual</button>
                                            </div>
                                            <p class="mt-1.5 text-xs text-slate-400" x-text="addForm.tracking === 'Bulk'
                                                ? 'One stock record with combined quantity.'
                                                : (Number(addForm.quantity) > 1
                                                    ? (Number(addForm.quantity) + ' individual assets will be created (asset tag, serial, QR per unit).')
                                                    : 'Creates a separate trackable asset (asset tag, serial, QR).')"></p>
                                        </div>
                                        <label class="flex items-center justify-between rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-200/80">
                                            <span class="text-sm font-medium text-slate-900">Can be borrowed</span>
                                            <input type="checkbox" x-model="addBorrowable" class="peer sr-only">
                                            <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-slate-900 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5"></span>
                                        </label>
                                    </div>
                                </div>

                                <details class="mt-5 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80" open>
                                    <summary class="cursor-pointer text-sm font-medium text-slate-700">Shared defaults / single-item details</summary>
                                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                                        <div x-show="addForm.tracking === 'Bulk' || Number(addForm.quantity) === 1">
                                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Asset tag</label>
                                            <input type="text" x-model="addForm.assetTag" @input="addAssetTagManual = true" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10" />
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Brand name</label>
                                            <input type="text" x-model="addForm.brand" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10" />
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Model</label>
                                            <input type="text" x-model="addForm.model" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10" />
                                        </div>
                                        <div x-show="addForm.tracking === 'Bulk' || Number(addForm.quantity) === 1">
                                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Serial number</label>
                                            <input type="text" x-model="addForm.serial" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10" />
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Warranty expiration</label>
                                            <input type="date" x-model="addForm.warranty" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10" />
                                        </div>
                                    </div>
                                </details>
                            </div>

                            <div
                                class="eq-modal-scroll min-h-0 flex-1 overflow-y-auto px-6 py-5 [scrollbar-gutter:stable]"
                                x-show="addStep === 2"
                                x-cloak
                            >
                                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80">
                                    <div class="text-sm text-slate-600">
                                        <span class="font-medium text-slate-900" x-text="addForm.name"></span>
                                        <span class="text-slate-400"> · </span>
                                        <span x-text="addItems.length + ' individually tracked units'"></span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" @click="regenerateAssetTags()" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Regenerate asset tags</button>
                                        <button type="button" @click="applySharedDefaultsToItems()" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Re-apply shared defaults</button>
                                    </div>
                                </div>

                                <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                                    <div class="min-w-[12rem] flex-1">
                                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Apply placement to all</label>
                                        <div class="relative">
                                            <button
                                                type="button"
                                                data-placement-trigger
                                                @mousedown.prevent="togglePlacementDropdown('apply', $event)"
                                                class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 text-left text-sm text-slate-900"
                                            >
                                                <span class="min-w-0 truncate" x-text="applyPlacementZone ? placementZoneLabel(applyPlacementZone) : 'Select placement'"></span>
                                                <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="applyPlacementToAll()"
                                        class="h-10 rounded-lg bg-slate-900 px-4 text-xs font-medium text-white hover:bg-slate-800"
                                        x-text="'Apply to all ' + addItems.length"
                                    ></button>
                                    <div class="flex flex-wrap items-end gap-2 border-l border-slate-200 pl-3" x-show="addItems.length >= 2">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Split range</label>
                                            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                                <span>#</span>
                                                <input type="number" min="1" :max="addItems.length" x-ref="splitFrom" :value="1" class="h-10 w-14 rounded-lg border border-slate-200 px-2 text-sm" />
                                                <span>–</span>
                                                <input type="number" min="1" :max="addItems.length" x-ref="splitTo" :value="Math.ceil(addItems.length / 2)" class="h-10 w-14 rounded-lg border border-slate-200 px-2 text-sm" />
                                            </div>
                                        </div>
                                        <div class="relative w-44">
                                            <button
                                                type="button"
                                                data-placement-trigger
                                                @mousedown.prevent="togglePlacementDropdown('split', $event)"
                                                class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 text-left text-sm text-slate-900"
                                            >
                                                <span class="min-w-0 truncate" x-text="placementZoneLabel(splitPlacementZone)"></span>
                                                <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400"></i>
                                            </button>
                                        </div>
                                        <button
                                            type="button"
                                            @click="applyPlacementRange($refs.splitFrom.value, $refs.splitTo.value, splitPlacementZone)"
                                            class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                        >
                                            Apply to range
                                        </button>
                                    </div>
                                </div>

                                <div class="eq-modal-scroll overflow-x-auto rounded-xl ring-1 ring-slate-200 [scrollbar-gutter:stable]">
                                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            <tr>
                                                <th class="px-3 py-2.5">Asset</th>
                                                <th class="px-3 py-2.5">Placement</th>
                                                <th class="px-3 py-2.5">Asset tag</th>
                                                <th class="px-3 py-2.5">Serial number</th>
                                                <th class="px-3 py-2.5">Brand</th>
                                                <th class="px-3 py-2.5">Model</th>
                                                <th class="px-3 py-2.5">Condition</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <template x-for="(item, index) in addItems" :key="index">
                                                <tr>
                                                    <td class="px-3 py-2">
                                                        <div class="font-medium text-slate-800" x-text="assetLabel(index)"></div>
                                                        <div class="text-[11px] text-slate-400" x-text="'#' + (index + 1)"></div>
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <div class="relative w-44">
                                                            <button
                                                                type="button"
                                                                data-placement-trigger
                                                                @mousedown.prevent="togglePlacementDropdown('row-' + index, $event)"
                                                                class="flex h-9 w-full items-center justify-between gap-2 rounded-md border border-slate-200 bg-white px-2 text-left text-sm text-slate-900"
                                                            >
                                                                <span class="min-w-0 truncate" x-text="placementZoneLabel(item.equipment_placement_zone)"></span>
                                                                <i data-lucide="chevron-down" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <input type="text" x-model="item.equipment_asset_tag" @input="item._tagManual = true" class="h-9 w-56 min-w-[14rem] rounded-md border border-slate-200 px-2 text-sm" />
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <input type="text" x-model="item.equipment_serial_number" class="h-9 w-36 rounded-md border border-slate-200 px-2 text-sm" />
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <input type="text" x-model="item.equipment_brand_name" class="h-9 w-28 rounded-md border border-slate-200 px-2 text-sm" />
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <input type="text" x-model="item.equipment_model" class="h-9 w-28 rounded-md border border-slate-200 px-2 text-sm" />
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <select x-model="item.equipment_condition_status" class="h-9 rounded-md border border-slate-200 px-2 text-sm">
                                                            <option value="Good">Good</option>
                                                            <option value="Damaged">Damaged</option>
                                                            <option value="Under Maintenance">Under Maintenance</option>
                                                            <option value="Disposed">Disposed</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2 px-6 py-4">
                                <button
                                    type="button"
                                    @click="addStep === 2 ? (addStep = 1, addFullscreen = false) : (addEquipmentModal = false, resetAddEquipment(), document.body.style.overflow = '')"
                                    class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                                    x-text="addStep === 2 ? 'Back' : 'Cancel'"
                                ></button>
                                <button
                                    type="button"
                                    x-show="addStep === 1 && needsItemDetails()"
                                    @click="continueAddEquipment()"
                                    :disabled="saving"
                                    class="h-10 rounded-xl bg-slate-900 px-5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Continue
                                </button>
                                <button
                                    type="button"
                                    x-show="addStep === 2 || !needsItemDetails()"
                                    @click="addStep === 2 ? storeEquipment() : continueAddEquipment()"
                                    :disabled="saving"
                                    class="h-10 rounded-xl bg-slate-900 px-5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                                    x-text="saving
                                        ? 'Saving…'
                                        : (addStep === 2
                                            ? ('Create ' + addItems.length + ' assets')
                                            : 'Add equipment')"
                                ></button>
                            </div>
                        </div>

                        {{-- Placement menu portaled above modal overflow (stacking context) --}}
                        <div
                            x-show="openPlacementDropdown"
                            x-cloak
                            @click.outside="
                                if (!$event.target.closest('[data-placement-trigger]')) {
                                    freezeModalScroll(() => closePlacementDropdown());
                                }
                            "
                            :style="placementMenuStyle"
                            class="overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-xl ring-1 ring-slate-200/80"
                        >
                            <template x-if="openPlacementDropdown === 'apply'">
                                <button
                                    type="button"
                                    tabindex="-1"
                                    @mousedown.prevent="pickPlacementZone('apply', '')"
                                    class="flex w-full items-center px-3 py-2 text-left text-sm text-slate-400 hover:bg-slate-50"
                                >Select placement</button>
                            </template>
                            <template x-for="zone in placementZones()" :key="'portal-' + zone">
                                <button
                                    type="button"
                                    tabindex="-1"
                                    @mousedown.prevent="pickPlacementZone(
                                        openPlacementDropdown === 'apply' ? 'apply'
                                            : (openPlacementDropdown === 'split' ? 'split'
                                                : (openPlacementDropdown === 'form' ? 'form'
                                                    : Number(String(openPlacementDropdown || '').replace('row-', '')))),
                                        zone
                                    )"
                                    class="flex w-full items-center px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                                    :class="activePlacementValue() === zone ? 'bg-slate-50 font-medium text-slate-900' : ''"
                                    x-text="placementZoneLabel(zone)"
                                ></button>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="editRoomModal">
                    <div
                        x-transition.opacity
                        @click.self="editRoomModal = false"
                        class="
                            fixed inset-0 z-[9999]
                            flex items-center justify-center
                            bg-[#0b1220]/70
                            p-4
                            sm:p-6
                        "
                    >

                        <!-- ===================================================== -->
                        <!-- MODAL CONTAINER -->
                        <!-- ===================================================== -->

                        <div
                            class="
                                flex
                                max-h-[calc(100dvh-2rem)]
                                w-full
                                max-w-2xl
                                flex-col
                                overflow-hidden
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                shadow-2xl
                            "
                        >


                            <!-- ================================================= -->
                            <!-- HEADER -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    flex
                                    shrink-0
                                    items-start
                                    justify-between
                                    border-b
                                    border-slate-100
                                    px-6
                                    py-4
                                "
                            >

                                <div>

                                    <h2
                                        class="
                                            text-lg
                                            font-semibold
                                            tracking-tight
                                            text-slate-950
                                        "
                                    >
                                        Edit Room
                                    </h2>


                                    <p class="mt-0.5 text-xs text-slate-500">
                                        Modify room details and environment configuration.
                                    </p>

                                </div>


                                <!-- CLOSE BUTTON -->

                                <button
                                    type="button"
                                    @click="editRoomModal = false"
                                    class="
                                        flex
                                        h-8
                                        w-8
                                        items-center
                                        justify-center
                                        rounded-lg
                                        text-slate-400
                                        transition

                                        hover:bg-slate-100
                                        hover:text-slate-700
                                    "
                                >
                                    <i
                                        data-lucide="x"
                                        class="h-4 w-4"
                                    ></i>
                                </button>

                            </div>



                            <!-- ================================================= -->
                            <!-- SCROLLABLE BODY -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    min-h-0
                                    flex-1
                                    overflow-y-auto
                                    overscroll-contain
                                    px-6
                                    py-5
                                "
                            >


                                <!-- ================================================= -->
                                <!-- FORM GRID -->
                                <!-- ================================================= -->

                                <div
                                    class="
                                        grid
                                        grid-cols-1
                                        gap-x-6
                                        gap-y-5
                                        sm:grid-cols-2
                                    "
                                >


                                    <!-- ================================================= -->
                                    <!-- ROOM NAME -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Room Name
                                        </label>


                                        <input
                                            x-model="roomForm.name"
                                            type="text"
                                            placeholder="e.g. Lab 204"
                                            class="
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-3.5
                                                text-sm
                                                text-slate-900
                                                outline-none
                                                transition

                                                placeholder:text-slate-400

                                                hover:border-slate-300

                                                focus:border-[#005EA6]
                                                focus:ring-2
                                                focus:ring-blue-100
                                            "
                                        />

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- OPERATIONAL STATUS -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Operational Status
                                        </label>


                                        <select
                                            x-model="roomForm.status"
                                            class="
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-3.5
                                                text-sm
                                                text-slate-700
                                                outline-none
                                                transition

                                                hover:border-slate-300

                                                focus:border-[#005EA6]
                                                focus:ring-2
                                                focus:ring-blue-100
                                            "
                                        >

                                            <option>
                                                Normal
                                            </option>

                                            <option>
                                                Maintenance Needed
                                            </option>

                                            <option>
                                                Critical
                                            </option>

                                        </select>

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- ROOM TYPE -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Room Type
                                        </label>


                                        <select
                                            x-model="roomForm.type"
                                            class="
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-3.5
                                                text-sm
                                                text-slate-700
                                                outline-none
                                                transition

                                                hover:border-slate-300

                                                focus:border-[#005EA6]
                                                focus:ring-2
                                                focus:ring-blue-100
                                            "
                                        >

                                            <option value="Lecture Room">
                                                Lecture Room
                                            </option>

                                            <option value="Computer Laboratory">
                                                Computer Laboratory
                                            </option>

                                            <option value="HM Room">
                                                HM Room / Bar
                                            </option>

                                            <option value="Hotel Room Simulation">
                                                Hotel Room Simulation
                                            </option>

                                            <option value="Faculty Room">
                                                Faculty Room
                                            </option>

                                            <option value="Office">
                                                Office
                                            </option>

                                            <option value="Library">
                                                Library
                                            </option>

                                            <option value="School Clinic">
                                                School Clinic
                                            </option>

                                        </select>

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- BLUEPRINT IDENTITY -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Blueprint Identity
                                        </label>


                                        <!-- COLOR CONTROL -->

                                        <div
                                            class="
                                                flex
                                                h-11
                                                items-center
                                                gap-3
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-2.5
                                                transition

                                                hover:border-slate-300

                                                focus-within:border-[#005EA6]
                                                focus-within:ring-2
                                                focus-within:ring-blue-100
                                            "
                                        >


                                            <!-- COLOR PICKER -->

                                            <input
                                                x-model="roomForm.color"
                                                type="color"
                                                class="
                                                    h-8
                                                    w-10
                                                    cursor-pointer
                                                    rounded-md
                                                    border-0
                                                    bg-transparent
                                                    p-0
                                                "
                                            />


                                            <!-- HEX VALUE -->

                                            <input
                                                x-model="roomForm.color"
                                                type="text"
                                                placeholder="#005EA6"
                                                class="
                                                    min-w-0
                                                    flex-1
                                                    border-0
                                                    bg-transparent
                                                    p-0
                                                    text-sm
                                                    font-medium
                                                    uppercase
                                                    tracking-wide
                                                    text-slate-700
                                                    outline-none
                                                "
                                            />

                                        </div>


                                        <!-- COLOR DESCRIPTION -->

                                        <div
                                            class="
                                                mt-2
                                                flex
                                                items-start
                                                gap-1.5
                                                text-xs
                                                leading-4
                                                text-slate-500
                                            "
                                        >

                                            <i
                                                data-lucide="info"
                                                class="
                                                    mt-0.5
                                                    h-3.5
                                                    w-3.5
                                                    shrink-0
                                                "
                                            ></i>


                                            <p>
                                                Custom color mapping for the management dashboard.
                                            </p>

                                        </div>

                                    </div>

                                </div>

                            </div>



                            <!-- ================================================= -->
                            <!-- FOOTER -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    shrink-0
                                    border-t
                                    border-slate-100
                                    bg-white
                                    px-6
                                    py-4
                                "
                            >

                                <div
                                    class="
                                        flex
                                        flex-col-reverse
                                        justify-end
                                        gap-3
                                        sm:flex-row
                                    "
                                >


                                    <!-- DISCARD CHANGES -->

                                    <button
                                        type="button"
                                        @click="editRoomModal = false"
                                        class="
                                            min-w-[160px]
                                            rounded-lg
                                            border
                                            border-slate-200
                                            bg-white
                                            px-5
                                            py-2.5
                                            text-sm
                                            font-medium
                                            text-slate-600
                                            transition

                                            hover:border-slate-300
                                            hover:bg-slate-50
                                            hover:text-slate-900
                                        "
                                    >
                                        Discard Changes
                                    </button>



                                    <!-- UPDATE RECORDS -->

                                    <button
                                        type="button"
                                        @click="saveRoom()"
                                        :disabled="roomSaving"
                                        class="
                                            min-w-[160px]
                                            rounded-lg
                                            bg-[rgba(0,55,199,0.85)]
                                            px-5
                                            py-2.5
                                            text-sm
                                            font-semibold
                                            text-white
                                            shadow-sm
                                            transition

                                            hover:bg-[rgba(0, 44, 155, 0.85)]

                                            disabled:cursor-not-allowed
                                            disabled:opacity-60
                                        "
                                    >

                                        <span
                                            x-text="
                                                roomSaving
                                                    ? 'Saving...'
                                                    : 'Update Records'
                                            "
                                        ></span>

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>
                </template>

                <template x-if="transferAssetsModal">
                    <div
                        x-transition.opacity
                        @click.self="transferAssetsModal = false; resetTransferForm()"
                        class="fixed inset-0 z-[9999] flex items-center justify-center bg-[#0b1220]/70 p-4 sm:p-6"
                    >
                        <div
                            class="flex max-h-[calc(90dvh-2rem)] w-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                            :class="transferMode === 'custom' ? 'max-w-3xl' : 'max-w-2xl'"
                        >
                            <div class="flex shrink-0 items-start justify-between border-b border-slate-100 px-6 py-4">
                                <div>
                                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer Asset</h2>
                                    <p class="mt-0.5 text-xs text-slate-500">Move equipment from {{ $room->room_name }} to other rooms.</p>
                                </div>
                                <button
                                    type="button"
                                    @click="transferAssetsModal = false; resetTransferForm()"
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                    aria-label="Close"
                                >
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-6 py-5">
                                <div class="mb-5 inline-flex w-full flex-wrap gap-1 rounded-xl bg-slate-100 p-1">
                                    <button
                                        type="button"
                                        @click="setTransferMode('single')"
                                        class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold transition"
                                        :class="transferMode === 'single' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                                    >One by one</button>
                                    <button
                                        type="button"
                                        @click="setTransferMode('all')"
                                        class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold transition"
                                        :class="transferMode === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                                    >Transfer all</button>
                                    <button
                                        type="button"
                                        @click="setTransferMode('custom')"
                                        class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold transition"
                                        :class="transferMode === 'custom' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                                    >Split by room</button>
                                </div>

                                <div
                                    x-show="transferError"
                                    x-cloak
                                    class="mb-4 flex items-start gap-2 rounded-xl bg-rose-50 px-3.5 py-3 text-sm text-rose-700 ring-1 ring-rose-100"
                                >
                                    <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                                    <p x-text="transferError"></p>
                                </div>

                                <div x-show="transferMode === 'single'" class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-xs font-medium text-slate-600">Equipment</label>
                                        <select
                                            x-model="selectedEquipment"
                                            class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3.5 text-sm text-slate-700 outline-none transition hover:border-slate-300 focus:border-[#005EA6] focus:ring-2 focus:ring-blue-100"
                                        >
                                            <option value="">Select Equipment</option>
                                            <template x-for="item in transferSourceEquipment()" :key="'single-eq-' + item.id">
                                                <option :value="item.id" x-text="item.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-xs font-medium text-slate-600">Destination Room</label>
                                        <select
                                            x-model="destinationRoom"
                                            class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3.5 text-sm text-slate-700 outline-none transition hover:border-slate-300 focus:border-[#005EA6] focus:ring-2 focus:ring-blue-100"
                                        >
                                            <option value="">Select Room</option>
                                            @foreach ($rooms as $destination)
                                                @if ($destination->room_id != $room->room_id && !($destination->room_is_archived ?? false))
                                                    <option value="{{ $destination->room_id }}">{{ $destination->room_name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div x-show="transferMode === 'all'" x-cloak class="space-y-4">
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                        Transfer
                                        <span class="font-semibold text-slate-900" x-text="transferSourceEquipment().length"></span>
                                        <span x-text="transferSourceEquipment().length === 1 ? ' item' : ' items'"></span>
                                        from <span class="font-semibold text-slate-900">{{ $room->room_name }}</span> to one destination room.
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-xs font-medium text-slate-600">Destination Room</label>
                                        <select
                                            x-model="transferAllRoom"
                                            class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3.5 text-sm text-slate-700 outline-none transition hover:border-slate-300 focus:border-[#005EA6] focus:ring-2 focus:ring-blue-100"
                                        >
                                            <option value="">Select Room</option>
                                            @foreach ($rooms as $destination)
                                                @if ($destination->room_id != $room->room_id && !($destination->room_is_archived ?? false))
                                                    <option value="{{ $destination->room_id }}">{{ $destination->room_name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div x-show="transferMode === 'custom'" x-cloak class="space-y-4">
                                    <p class="text-sm text-slate-600">
                                        Assign each item to a room. Example: send TV and chairs to one room, tables and curtains to another.
                                    </p>

                                    <div class="flex flex-wrap items-end gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        <div class="min-w-[12rem] flex-1">
                                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-slate-500">Apply destination to selected</label>
                                            <select
                                                x-model="transferApplyRoom"
                                                class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm"
                                            >
                                                <option value="">Select Room</option>
                                                @foreach ($rooms as $destination)
                                                    @if ($destination->room_id != $room->room_id && !($destination->room_is_archived ?? false))
                                                        <option value="{{ $destination->room_id }}">{{ $destination->room_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <button
                                            type="button"
                                            @click="applyDestinationToSelected()"
                                            class="h-10 rounded-lg bg-slate-900 px-3 text-xs font-medium text-white hover:bg-slate-800"
                                        >Apply to selected</button>
                                        <button
                                            type="button"
                                            @click="selectAllTransferEquipment()"
                                            class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                        >Select all</button>
                                        <button
                                            type="button"
                                            @click="clearTransferSelection()"
                                            class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                        >Clear</button>
                                    </div>

                                    <div class="overflow-hidden rounded-xl ring-1 ring-slate-200">
                                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                            <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                                <tr>
                                                    <th class="w-10 px-3 py-2.5"></th>
                                                    <th class="px-3 py-2.5">Equipment</th>
                                                    <th class="px-3 py-2.5">Destination room</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                <template x-if="!transferSourceEquipment().length">
                                                    <tr>
                                                        <td colspan="3" class="px-3 py-6 text-center text-sm text-slate-500">No equipment in this room.</td>
                                                    </tr>
                                                </template>
                                                <template x-for="item in transferSourceEquipment()" :key="'custom-eq-' + item.id">
                                                    <tr>
                                                        <td class="px-3 py-2.5">
                                                            <input
                                                                type="checkbox"
                                                                class="h-4 w-4 rounded border-slate-300 text-[#005EA6] focus:ring-[#005EA6]"
                                                                :checked="transferSelectedIds.includes(item.id)"
                                                                @change="toggleTransferSelected(item.id)"
                                                            />
                                                        </td>
                                                        <td class="px-3 py-2.5 font-medium text-slate-800" x-text="item.name"></td>
                                                        <td class="px-3 py-2.5">
                                                            <select
                                                                class="h-9 w-full min-w-[10rem] rounded-md border border-slate-200 px-2 text-sm"
                                                                :value="transferTargets[item.id] || ''"
                                                                @change="transferTargets = { ...transferTargets, [item.id]: $event.target.value }"
                                                            >
                                                                <option value="">Keep here / skip</option>
                                                                @foreach ($rooms as $destination)
                                                                    @if ($destination->room_id != $room->room_id && !($destination->room_is_archived ?? false))
                                                                        <option value="{{ $destination->room_id }}">{{ $destination->room_name }}</option>
                                                                    @endif
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="mt-6 flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 shadow-sm ring-1 ring-slate-200">
                                        <i data-lucide="arrow-right-left" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-slate-800">Asset Transfer</h4>
                                        <p class="mt-1 text-xs leading-5 text-slate-500" x-text="
                                            transferMode === 'all'
                                                ? 'All equipment in this room will move to the selected destination.'
                                                : (transferMode === 'custom'
                                                    ? 'Only items with a destination room will be moved. Unassigned items stay here.'
                                                    : 'The selected equipment will be moved from the current room to the destination room.')
                                        "></p>
                                    </div>
                                </div>
                            </div>

                            <div class="shrink-0 border-t border-slate-100 bg-white px-6 py-4">
                                <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                                    <button
                                        type="button"
                                        @click="transferAssetsModal = false; resetTransferForm()"
                                        class="min-w-[110px] rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                                    >Cancel</button>
                                    <button
                                        type="button"
                                        @click="transferAsset()"
                                        :disabled="roomSaving || !transferSourceEquipment().length"
                                        class="min-w-[145px] rounded-lg bg-[#005EA6] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#004a85] disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        <span x-text="roomSaving ? 'Transferring…' : (transferMode === 'single' ? 'Transfer Asset' : 'Transfer selected')"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div
                    x-show="tab === 'equipment'"
                    x-data="{
                        search: '',

                        filter: 'all',
                    }"
                    class="space-y-4"
                >
                    <!-- ===================================================== -->
                    <!-- SEARCH & FILTER -->
                    <!-- ===================================================== -->

                    <div
                        class="
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                        "
                    >

                        <!-- ================================================= -->
                        <!-- TOOLBAR HEADER -->
                        <!-- ================================================= -->

                        <div
                            class="
                                flex
                                items-center
                                justify-between
                                border-b
                                border-slate-100
                                px-5
                                py-4
                            "
                        >

                            <div>

                                <h3 class="text-sm font-semibold text-slate-900">
                                    Equipment List
                                </h3>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Search and filter equipment in this room.
                                </p>

                            </div>

                            <div
                                class="
                                    flex
                                    h-8
                                    w-8
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-slate-50
                                    text-slate-400
                                "
                            >
                                <i
                                    data-lucide="search"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                        </div>



                        <!-- ================================================= -->
                        <!-- SEARCH CONTROLS -->
                        <!-- ================================================= -->

                        <div class="p-4">

                            <div
                                class="
                                    flex
                                    flex-col
                                    gap-3

                                    sm:flex-row
                                "
                            >

                                <!-- SEARCH -->

                                <div class="relative flex-1">
                                <i
                                    data-lucide="search"
                                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                ></i>

                                <input
                                    x-model="search"
                                    type="text"
                                    placeholder="Search equipment..."
                                    class="w-full rounded-xl border border-slate-200 py-2.5 pl-10 pr-3 text-sm focus:border-[#005EA6] focus:outline-none"
                                />
                            </div>
                            

                            <select
                                x-model="filter"
                                class="rounded-xl border border-slate-200 px-3 text-sm"
                            >
                                <option value="all">All Conditions</option>

                                <option value="good">Good</option>

                                <option value="maintenance">Maintenance</option>

                                <option value="damaged">Damaged</option>

                                <option value="disposed">Disposed</option>
                            </select>

                        </div>

                    </div>

                </div>

                    <template
                        x-for="
                            item in (equipmentRenderKey, liveStackEquipment())
                        "
                        :key="'live-stack-' + item.id"
                    >
                        <article
                            x-show="
                                (search === '' ||
                                    (item.name || '')
                                        .toLowerCase()
                                        .includes(search.toLowerCase())) &&
                                (filter === 'all' ||
                                    (filter === 'good' &&
                                        (item.condition || '') === 'Good') ||
                                    (filter === 'maintenance' &&
                                        (item.condition || '') === 'Under Maintenance') ||
                                    (filter === 'damaged' &&
                                        (item.condition || '') === 'Damaged') ||
                                    (filter === 'disposed' &&
                                        (item.condition || '') === 'Disposed'))
                            "
                            class="
                                group
                                overflow-hidden
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                transition

                                hover:border-slate-300
                                hover:shadow-sm
                            "
                        >

                            <!-- ================================================= -->
                            <!-- CARD -->
                            <!-- ================================================= -->

                            <div class="flex items-start justify-between gap-4 p-4">

                                <!-- LEFT -->

                                <div class="flex min-w-0 flex-1 gap-3">

                                    <!-- ICON -->

                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100"
                                    >
                                        <i
                                            data-lucide="monitor-cog"
                                            class="h-5 w-5 text-slate-500"
                                        ></i>
                                    </div>


                                    <!-- CONTENT -->

                                    <div class="min-w-0 flex-1">

                                        <!-- NAME -->

                                        <h3
                                            class="truncate text-sm font-semibold text-slate-900"
                                            x-text="item.name"
                                        ></h3>


                                        <!-- CATEGORY -->

                                        <p
                                            class="mt-1 truncate text-xs text-slate-500"
                                            x-text="item.category || 'Uncategorized'"
                                        ></p>


                                        <!-- LOCATION -->

                                        <div
                                            class="mt-2 flex items-center gap-1.5 text-[11px] text-slate-400"
                                        >

                                            <i
                                                data-lucide="map-pin"
                                                class="h-3 w-3"
                                            ></i>

                                            <span
                                                x-text="
                                                    item.location ||
                                                    item.placement_zone ||
                                                    'No location'
                                                "
                                            ></span>

                                        </div>

                                    </div>

                                </div>



                                <!-- RIGHT -->

                                <div class="flex shrink-0 flex-col items-end gap-2">

                                    <!-- STATUS -->

                                    <span
                                        class="
                                            inline-flex
                                            items-center
                                            gap-1.5
                                            rounded-md
                                            px-2.5
                                            py-1
                                            text-[11px]
                                            font-medium
                                        "
                                        :class="
                                            (item.condition || '') === 'Good'
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : (item.condition || '') === 'Under Maintenance'
                                                    ? 'bg-amber-50 text-amber-700'
                                                    : (item.condition || '') === 'Damaged'
                                                        ? 'bg-red-50 text-red-700'
                                                        : (item.condition || '') === 'Disposed'
                                                            ? 'bg-slate-100 text-slate-600'
                                                            : 'bg-slate-100 text-slate-500'
                                        "
                                    >

                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                (item.condition || '') === 'Good'
                                                    ? 'bg-emerald-500'
                                                    : (item.condition || '') === 'Under Maintenance'
                                                        ? 'bg-amber-500'
                                                        : (item.condition || '') === 'Damaged'
                                                            ? 'bg-red-500'
                                                            : (item.condition || '') === 'Disposed'
                                                                ? 'bg-slate-500'
                                                                : 'bg-slate-400'
                                            "
                                        ></span>

                                        <span
                                            x-text="item.condition || 'Unknown'"
                                        ></span>

                                    </span>


                                    <!-- QUANTITY -->

                                    <template x-if="item.quantity">

                                        <span
                                            class="
                                                rounded-md
                                                bg-slate-100
                                                px-2
                                                py-1
                                                text-[11px]
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Qty:
                                            <span x-text="item.quantity"></span>
                                        </span>

                                    </template>

                                </div>

                            </div>

                        </article>
                    </template>

                    @forelse ($room->equipment->sortByDesc("equipment_id") as $item)
                        @php
                            $healthy = $item->equipment_condition_status === "Good";
                            $maintenance = $item->equipment_condition_status === "Under Maintenance";
                            $damaged = $item->equipment_condition_status === "Damaged";
                            $disposed = $item->equipment_condition_status === "Disposed";
                        @endphp
                        <article
                            x-show="

                                    (

                                        search === ''

                                        ||

                                        '{{ strtolower($item->equipment_name) }}'

                                        .includes(search.toLowerCase())

                                    )

                                    &&

                                    (

                                        filter === 'all'

                                        ||

                                        (

                                            filter === 'good'

                                            &&

                                            '{{ $item->equipment_condition_status }}'

                                            === 'Good'

                                        )

                                        ||

                                        (

                                            filter === 'maintenance'

                                            &&

                                            '{{ $item->equipment_condition_status }}'

                                            === 'Under Maintenance'

                                        )

                                        ||

                                        (

                                            filter === 'damaged'

                                            &&

                                            '{{ $item->equipment_condition_status }}'

                                            === 'Damaged'

                                        )

                                        ||

                                        (
                                            filter === 'disposed'

                                            &&

                                            '{{ $item->equipment_condition_status }}'

                                            === 'Disposed'
                                        )

                                    )

                            "
                            class="relative overflow-visible rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:border-slate-200 hover:bg-white hover:shadow-md"
                        >
                            <!-- Equipment Alpine Component -->
                            <div
                                x-data="equipmentCard(

                                    {{ $item->equipment_id }},

                                    {{ $room->room_id }}

                                )"
                            >
                                <!-- Equipment Header -->

                                <!-- ========================= -->
                                <!-- Equipment Header -->
                                <!-- Replace your current header + 4-column grid -->
                                <!-- ========================= -->

                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="flex min-w-0 gap-3">
                                        <span
                                            class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full
                                            {{
                                                $healthy
                                                    ? 'bg-emerald-500'
                                                    : ($maintenance
                                                        ? 'bg-amber-400'
                                                        : ($damaged
                                                            ? 'bg-red-600'
                                                            : ($disposed
                                                                ? 'bg-zinc-500'
                                                                : 'bg-gray-300')))
                                            }}"
                                        ></span>

                                        <div class="min-w-0">
                                            <h3
                                                class="truncate text-sm font-bold text-slate-800"
                                            >
                                                {{ $item->equipment_name }}
                                            </h3>

                                            <p class="mt-1 text-[11px] text-slate-500">
                                                {{
                                                    $item->equipment_asset_tag ?:
                                                        "No Asset Tag"
                                                }}

                                                ·

                                                @if ($item->equipment_tracking_mode == "Bulk")
                                                    Qty {{ $item->equipment_quantity }}

                                                @else
                                                    Individual Asset
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span
                                            class="rounded-full px-2 py-1 text-[9px] font-extrabold
                                            {{
                                                $healthy
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : ($maintenance
                                                        ? 'bg-amber-100 text-amber-700'
                                                        : ($damaged
                                                            ? 'bg-red-100 text-red-700'
                                                            : ($disposed
                                                                ? 'bg-slate-200 text-slate-700'
                                                                : 'bg-gray-100 text-gray-700')))
                                            }}"
                                        >
                                            {{ $item->equipment_condition_status }}
                                        </span>

                                        <div
                                            x-data="{ menu: false }"
                                            :class="menu
                                                ? 'relative z-40'
                                                : 'relative z-10'"
                                            class="rounded-xl border border-slate-100 bg-white p-1 shadow-sm"
                                        >
                                            <div class="relative z-30">
                                                <button
                                                    @click="menu = !menu"
                                                    class="rounded-lg p-2 hover:bg-slate-100"
                                                >
                                                    <i
                                                        data-lucide="ellipsis-vertical"
                                                        class="h-4 w-4"
                                                    ></i>
                                                </button>

                                                <!-- your dropdown stays here -->

                                                <div
                                                    x-show="menu"
                                                    @click.outside="menu = false"
                                                    x-transition.origin.top.right
                                                    class="
                                                        absolute
                                                        right-0
                                                        top-full
                                                        z-50
                                                        
                                                        w-[180px]
                                                        overflow-hidden
                                                        rounded-xl
                                                        border
                                                        border-slate-200
                                                        bg-white
                                                        shadow-xl
                                                    "
                                                >

                                                    <!-- ================================================= -->
                                                    <!-- VIEW DETAILS -->
                                                    <!-- ================================================= -->

                                                    <button
                                                        @click="
                                                            panel = panel === 'details'
                                                                ? ''
                                                                : 'details';

                                                            menu = false;
                                                        "
                                                        class="
                                                            flex
                                                            w-full
                                                            items-center
                                                            gap-3
                                                            px-4
                                                            py-3
                                                            text-left
                                                            text-[12px]
                                                            text-slate-700
                                                            transition

                                                            hover:bg-slate-50
                                                        "
                                                    >

                                                        <i
                                                            data-lucide="eye"
                                                            class="h-4 w-4 text-slate-400"
                                                        ></i>

                                                        <span class="flex-1">
                                                            View Details
                                                        </span>

                                                    </button>



                                                    <!-- ================================================= -->
                                                    <!-- EDIT -->
                                                    <!-- ================================================= -->

                                                    <button
                                                        @click="
                                                            panel = 'edit';
                                                            menu = false;
                                                        "
                                                        class="
                                                            flex
                                                            w-full
                                                            items-center
                                                            gap-3
                                                            px-4
                                                            py-3
                                                            text-left
                                                            text-[12px]
                                                            text-slate-700
                                                            transition

                                                            hover:bg-slate-50
                                                        "
                                                    >

                                                        <i
                                                            data-lucide="square-pen"
                                                            class="h-4 w-4 text-slate-400"
                                                        ></i>

                                                        <span class="flex-1">
                                                            Edit Equipment
                                                        </span>

                                                    </button>



                                                    <!-- ================================================= -->
                                                    <!-- TRANSFER -->
                                                    <!-- ================================================= -->

                                                    <button
                                                        @click="
                                                            transferRoom = '';
                                                            panel = 'transfer';
                                                            menu = false;
                                                        "
                                                        class="
                                                            flex
                                                            w-full
                                                            items-center
                                                            gap-3
                                                            px-4
                                                            py-3
                                                            text-left
                                                            text-[12px]
                                                            text-slate-700
                                                            transition

                                                            hover:bg-slate-50
                                                        "
                                                    >

                                                        <i
                                                            data-lucide="arrow-right-left"
                                                            class="h-4 w-4 text-slate-400"
                                                        ></i>

                                                        <span class="flex-1">
                                                            Transfer Equipment
                                                        </span>

                                                    </button>



                                                    <!-- DIVIDER -->

                                                    <div class="mx-2 border-t border-slate-100"></div>



                                                    <!-- ================================================= -->
                                                    <!-- ARCHIVE -->
                                                    <!-- ================================================= -->

                                                    <button
                                                        @click="
                                                            archiveReason = '';
                                                            panel = 'archive';
                                                            menu = false;
                                                        "
                                                        class="
                                                            flex
                                                            w-full
                                                            items-center
                                                            gap-3
                                                            px-4
                                                            py-3
                                                            text-left
                                                            text-[12px]
                                                            text-red-600
                                                            transition

                                                            hover:bg-red-50
                                                        "
                                                    >

                                                        <i
                                                            data-lucide="archive"
                                                            class="h-4 w-4"
                                                        ></i>

                                                        <span class="flex-1">
                                                            Archive Equipment
                                                        </span>

                                                    </button>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="mt-3 flex items-center gap-2 border-t border-slate-200 pt-3 text-[11px] text-slate-500"
                                >
                                    <i
                                        data-lucide="map-pin"
                                        class="h-3.5 w-3.5 text-[#005EA6]"
                                    ></i>

                                    <span>
                                        {{
                                            $item->equipment_placement_zone ?:
                                                ($item->equipment_current_location ?:
                                                    "Placement not plotted")
                                        }}
                                    </span>
                                </div>

                                <div
                                    x-show="panel === 'details'"
                                    x-collapse
                                    class="
                                        mt-5
                                        overflow-hidden
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                    "
                                >

                                    <!-- ===================================================== -->
                                    <!-- PANEL HEADER -->
                                    <!-- ===================================================== -->

                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            border-b
                                            border-slate-100
                                            px-5
                                            py-4
                                        "
                                    >

                                        <!-- HEADER INFORMATION -->

                                        <div>

                                            <h4 class="text-sm font-semibold text-slate-900">
                                                Equipment Information
                                            </h4>

                                            <p class="mt-0.5 text-xs text-slate-500">
                                                Asset identification, purchase, and assignment details.
                                            </p>

                                        </div>


                                        <!-- CLOSE PANEL -->

                                        <button
                                            type="button"
                                            @click="panel = ''"
                                            class="
                                                flex
                                                h-8
                                                w-8
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-lg
                                                text-slate-400
                                                transition

                                                hover:bg-slate-100
                                                hover:text-slate-700
                                            "
                                        >
                                            <i data-lucide="x" class="h-4 w-4"></i>
                                        </button>

                                    </div>



                                    <!-- ===================================================== -->
                                    <!-- PANEL BODY -->
                                    <!-- ===================================================== -->

                                    <div class="p-5">


                                        <!-- ================================================= -->
                                        <!-- INFORMATION GRID -->
                                        <!-- ================================================= -->

                                        <div
                                            class="
                                                grid
                                                grid-cols-2
                                                gap-x-6
                                                gap-y-5
                                            "
                                        >


                                            <!-- ASSET TAG -->

                                            <div class="min-w-0">

                                                <p
                                                    class="
                                                        text-[10px]
                                                        font-medium
                                                        uppercase
                                                        tracking-wide
                                                        text-slate-400
                                                    "
                                                >
                                                    Asset Tag
                                                </p>

                                                <p
                                                    class="
                                                        mt-1
                                                        truncate
                                                        text-sm
                                                        font-medium
                                                        text-slate-800
                                                    "
                                                >
                                                    {{
                                                        $item->equipment_asset_tag
                                                            ?: "Not Assigned"
                                                    }}
                                                </p>

                                            </div>



                                            <!-- SERIAL NUMBER -->

                                            <div class="min-w-0">

                                                <p
                                                    class="
                                                        text-[10px]
                                                        font-medium
                                                        uppercase
                                                        tracking-wide
                                                        text-slate-400
                                                    "
                                                >
                                                    Serial Number
                                                </p>

                                                <p
                                                    class="
                                                        mt-1
                                                        truncate
                                                        text-sm
                                                        font-medium
                                                        text-slate-800
                                                    "
                                                >
                                                    {{
                                                        $item->equipment_serial_number
                                                            ?? "Unavailable"
                                                    }}
                                                </p>

                                            </div>



                                            <!-- WARRANTY -->

                                            <div class="min-w-0">

                                                <p
                                                    class="
                                                        text-[10px]
                                                        font-medium
                                                        uppercase
                                                        tracking-wide
                                                        text-slate-400
                                                    "
                                                >
                                                    Warranty
                                                </p>

                                                <p
                                                    class="
                                                        mt-1
                                                        text-sm
                                                        font-medium
                                                        text-slate-800
                                                    "
                                                >
                                                    {{
                                                        $item->equipment_warranty_expiration
                                                            ?? "Unknown"
                                                    }}
                                                </p>

                                            </div>



                                            <!-- SUPPLIER -->

                                            <div class="min-w-0">

                                                <p
                                                    class="
                                                        text-[10px]
                                                        font-medium
                                                        uppercase
                                                        tracking-wide
                                                        text-slate-400
                                                    "
                                                >
                                                    Supplier
                                                </p>

                                                <p
                                                    class="
                                                        mt-1
                                                        truncate
                                                        text-sm
                                                        font-medium
                                                        text-slate-800
                                                    "
                                                >
                                                    {{
                                                        $item->equipment_supplier
                                                            ?? "Not Assigned"
                                                    }}
                                                </p>

                                            </div>



                                            <!-- PURCHASE DATE -->

                                            <div class="min-w-0">

                                                <p
                                                    class="
                                                        text-[10px]
                                                        font-medium
                                                        uppercase
                                                        tracking-wide
                                                        text-slate-400
                                                    "
                                                >
                                                    Purchase Date
                                                </p>

                                                <p
                                                    class="
                                                        mt-1
                                                        text-sm
                                                        font-medium
                                                        text-slate-800
                                                    "
                                                >
                                                    {{
                                                        $item->equipment_purchase_date
                                                            ?? "Unknown"
                                                    }}
                                                </p>

                                            </div>



                                            <!-- ASSIGNED TECHNICIAN -->

                                            <div class="min-w-0">

                                                <p
                                                    class="
                                                        text-[10px]
                                                        font-medium
                                                        uppercase
                                                        tracking-wide
                                                        text-slate-400
                                                    "
                                                >
                                                    Assigned Technician
                                                </p>

                                                <p
                                                    class="
                                                        mt-1
                                                        truncate
                                                        text-sm
                                                        font-medium
                                                        text-slate-800
                                                    "
                                                >
                                                    {{
                                                        $item->equipment_assigned_to
                                                            ?? "Unassigned"
                                                    }}
                                                </p>

                                            </div>

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- QR CODE SECTION -->
                                        <!-- ================================================= -->

                                        <div
                                            class="
                                                mt-6
                                                border-t
                                                border-slate-100
                                                pt-5
                                            "
                                        >

                                            <div
                                                class="
                                                    flex
                                                    items-center
                                                    justify-between
                                                    gap-4
                                                "
                                            >

                                                <!-- QR INFORMATION -->

                                                <div class="min-w-0">

                                                    <div class="flex items-center gap-2">

                                                        <i
                                                            data-lucide="qr-code"
                                                            class="h-4 w-4 text-slate-400"
                                                        ></i>

                                                        <p
                                                            class="
                                                                text-sm
                                                                font-medium
                                                                text-slate-800
                                                            "
                                                        >
                                                            QR Code
                                                        </p>

                                                    </div>


                                                    <p
                                                        class="
                                                            mt-1
                                                            max-w-[220px]
                                                            text-xs
                                                            leading-5
                                                            text-slate-500
                                                        "
                                                    >
                                                        Asset QR identification will be available in the Asset
                                                        Management phase.
                                                    </p>

                                                </div>



                                                <!-- QR PLACEHOLDER -->

                                                <div
                                                    class="
                                                        flex
                                                        h-20
                                                        w-20
                                                        shrink-0
                                                        items-center
                                                        justify-center
                                                        rounded-lg
                                                        border
                                                        border-dashed
                                                        border-slate-200
                                                        bg-slate-50
                                                    "
                                                >

                                                    <i
                                                        data-lucide="qr-code"
                                                        class="h-7 w-7 text-slate-300"
                                                    ></i>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                                <div
                                    x-show="panel === 'edit'"
                                    x-transition
                                    class="
                                        mt-5
                                        overflow-hidden
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                    "
                                >

                                    <!-- ===================================================== -->
                                    <!-- PANEL HEADER -->
                                    <!-- ===================================================== -->

                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            border-b
                                            border-slate-100
                                            px-5
                                            py-4
                                        "
                                    >

                                        <div>

                                            <h4 class="text-sm font-semibold text-slate-900">
                                                Edit Equipment
                                            </h4>

                                            <p class="mt-0.5 text-xs text-slate-500">
                                                Update equipment information and placement.
                                            </p>

                                        </div>


                                        <!-- CLOSE PANEL -->

                                        <button
                                            type="button"
                                            @click="panel = ''"
                                            class="
                                                flex
                                                h-8
                                                w-8
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-lg
                                                text-slate-400
                                                transition

                                                hover:bg-slate-100
                                                hover:text-slate-700
                                            "
                                        >
                                            <i data-lucide="x" class="h-4 w-4"></i>
                                        </button>

                                    </div>



                                    <!-- ===================================================== -->
                                    <!-- FORM BODY -->
                                    <!-- ===================================================== -->

                                    <div class="space-y-5 p-5">


                                        <!-- ================================================= -->
                                        <!-- EQUIPMENT NAME -->
                                        <!-- ================================================= -->

                                        <div>

                                            <label
                                                class="
                                                    mb-2
                                                    block
                                                    text-xs
                                                    font-medium
                                                    text-slate-600
                                                "
                                            >
                                                Equipment Name
                                            </label>


                                            <input
                                                x-model="form.name"
                                                type="text"
                                                class="
                                                    h-10
                                                    w-full
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-3
                                                    text-sm
                                                    text-slate-900
                                                    outline-none
                                                    transition

                                                    hover:border-slate-300

                                                    focus:border-[#005EA6]
                                                    focus:ring-2
                                                    focus:ring-blue-100
                                                "
                                            />

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- CATEGORY -->
                                        <!-- ================================================= -->

                                        <div>

                                            <label
                                                class="
                                                    mb-2
                                                    block
                                                    text-xs
                                                    font-medium
                                                    text-slate-600
                                                "
                                            >
                                                Category
                                            </label>


                                            <select
                                                x-model="form.category"
                                                class="
                                                    h-10
                                                    w-full
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-3
                                                    text-sm
                                                    text-slate-700
                                                    outline-none
                                                    transition

                                                    hover:border-slate-300

                                                    focus:border-[#005EA6]
                                                    focus:ring-2
                                                    focus:ring-blue-100
                                                "
                                            >

                                                <option value="">
                                                    Select Category
                                                </option>


                                                @foreach ($categories as $category)

                                                    <option
                                                        value="{{ $category->equipment_category_id }}"
                                                    >
                                                        {{ $category->equipment_category_name }}
                                                    </option>

                                                @endforeach

                                            </select>

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- PLACEMENT -->
                                        <!-- ================================================= -->

                                        <div>

                                            <label
                                                class="
                                                    mb-2
                                                    block
                                                    text-xs
                                                    font-medium
                                                    text-slate-600
                                                "
                                            >
                                                Placement zone
                                            </label>


                                            <div class="relative">

                                                <!-- LOCATION ICON -->

                                                <div
                                                    class="
                                                        pointer-events-none
                                                        absolute
                                                        left-3
                                                        top-1/2
                                                        -translate-y-1/2
                                                        text-slate-400
                                                    "
                                                >
                                                    <i
                                                        data-lucide="map-pin"
                                                        class="h-3.5 w-3.5"
                                                    ></i>
                                                </div>


                                                <select
                                                    x-model="form.location"
                                                    @change="updateEquipmentPlacement()"
                                                    class="
                                                        h-10
                                                        w-full
                                                        rounded-lg
                                                        border
                                                        border-slate-200
                                                        bg-white
                                                        pl-8
                                                        pr-3
                                                        text-sm
                                                        text-slate-700
                                                        outline-none
                                                        transition

                                                        hover:border-slate-300

                                                        focus:border-[#005EA6]
                                                        focus:ring-2
                                                        focus:ring-blue-100
                                                    "
                                                >

                                                    <option value="" disabled>
                                                        Select placement
                                                    </option>

                                                    <template x-for="zone in placementZones()" :key="'edit-zone-' + zone">
                                                        <option :value="zone" x-text="placementZoneLabel(zone)"></option>
                                                    </template>

                                                </select>

                                            </div>

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- QUANTITY -->
                                        <!-- Only visible for Bulk equipment -->
                                        <!-- ================================================= -->

                                        @if ($item->equipment_tracking_mode == "Bulk")

                                            <div>

                                                <label
                                                    class="
                                                        mb-2
                                                        block
                                                        text-xs
                                                        font-medium
                                                        text-slate-600
                                                    "
                                                >
                                                    Quantity
                                                </label>


                                                <input
                                                    x-model="form.quantity"
                                                    type="number"
                                                    min="1"
                                                    class="
                                                        h-10
                                                        w-full
                                                        rounded-lg
                                                        border
                                                        border-slate-200
                                                        bg-white
                                                        px-3
                                                        text-sm
                                                        text-slate-900
                                                        outline-none
                                                        transition

                                                        hover:border-slate-300

                                                        focus:border-[#005EA6]
                                                        focus:ring-2
                                                        focus:ring-blue-100
                                                    "
                                                />

                                            </div>

                                        @endif



                                        <!-- ================================================= -->
                                        <!-- CONDITION -->
                                        <!-- ================================================= -->

                                        <div>

                                            <label
                                                class="
                                                    mb-2
                                                    block
                                                    text-xs
                                                    font-medium
                                                    text-slate-600
                                                "
                                            >
                                                Condition
                                            </label>


                                            <select
                                                x-model="form.condition"
                                                class="
                                                    h-10
                                                    w-full
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-3
                                                    text-sm
                                                    text-slate-700
                                                    outline-none
                                                    transition

                                                    hover:border-slate-300

                                                    focus:border-[#005EA6]
                                                    focus:ring-2
                                                    focus:ring-blue-100
                                                "
                                            >

                                                <option>
                                                    Good
                                                </option>

                                                <option>
                                                    Under Maintenance
                                                </option>

                                                <option>
                                                    Damaged
                                                </option>

                                                <option>
                                                    Disposed
                                                </option>

                                            </select>

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- ACTIONS -->
                                        <!-- ================================================= -->

                                        <div
                                            class="
                                                flex
                                                justify-end
                                                gap-3
                                                border-t
                                                border-slate-100
                                                pt-4
                                            "
                                        >

                                            <!-- CANCEL -->

                                            <button
                                                type="button"
                                                @click="panel = ''"
                                                class="
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-4
                                                    py-2
                                                    text-sm
                                                    font-medium
                                                    text-slate-600
                                                    transition

                                                    hover:border-slate-300
                                                    hover:bg-slate-50
                                                    hover:text-slate-900
                                                "
                                            >
                                                Cancel
                                            </button>


                                            <!-- SAVE CHANGES -->

                                            <button
                                                type="button"
                                                @click="saveEquipment()"
                                                :disabled="saving"
                                                class="
                                                    rounded-lg
                                                    bg-[rgba(0,55,199,0.85)]
                                                    px-5
                                                    py-2
                                                    text-sm
                                                    font-semibold
                                                    text-white
                                                    shadow-sm
                                                    transition

                                                    hover:bg-[rgba(0, 44, 155, 0.85)]

                                                    disabled:cursor-not-allowed
                                                    disabled:opacity-60
                                                "
                                            >

                                                <span
                                                    x-text="
                                                        saving
                                                            ? 'Saving...'
                                                            : 'Save'
                                                    "
                                                ></span>

                                            </button>

                                        </div>

                                    </div>

                                </div>

                                <div
                                    x-show="panel === 'transfer'"
                                    x-collapse
                                    class="
                                        mt-5
                                        
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                    "
                                >

                                    <!-- ===================================================== -->
                                    <!-- PANEL HEADER -->
                                    <!-- ===================================================== -->

                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            border-b
                                            border-slate-100
                                            px-5
                                            py-4
                                        "
                                    >

                                        <!-- HEADER INFORMATION -->

                                        <div>

                                            <h4 class="text-sm font-semibold text-slate-900">
                                                Transfer Equipment
                                            </h4>

                                            <p class="mt-0.5 text-xs text-slate-500">
                                                Move this equipment to another room.
                                            </p>

                                        </div>


                                        <!-- CLOSE PANEL -->

                                        <button
                                            type="button"
                                            @click="panel = ''"
                                            class="
                                                flex
                                                h-8
                                                w-8
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-lg
                                                text-slate-400
                                                transition

                                                hover:bg-slate-100
                                                hover:text-slate-700
                                            "
                                        >
                                            <i data-lucide="x" class="h-4 w-4"></i>
                                        </button>

                                    </div>



                                    <!-- ===================================================== -->
                                    <!-- PANEL BODY -->
                                    <!-- ===================================================== -->

                                    <div class="p-5">


                                        <!-- ================================================= -->
                                        <!-- DESTINATION ROOM -->
                                        <!-- ================================================= -->

                                        <div>

                                            <label
                                                class="
                                                    mb-2
                                                    block
                                                    text-xs
                                                    font-medium
                                                    text-slate-600
                                                "
                                            >
                                                Destination Room
                                            </label>


                                            <div class="relative">

                                                <!-- LOCATION ICON -->

                                                <div
                                                    class="
                                                        pointer-events-none
                                                        absolute
                                                        left-3
                                                        top-1/2
                                                        -translate-y-1/2
                                                        text-slate-400
                                                    "
                                                >
                                                    <i
                                                        data-lucide="map-pin"
                                                        class="h-3.5 w-3.5"
                                                    ></i>
                                                </div>


                                                <div 
                                                    x-data="{ 
                                                        open: false, 
                                                        transferRoom: '', 
                                                        rooms: [
                                                            {{-- We can pass the PHP rooms data straight to JS if needed, or just let Alpine handle the click --}}
                                                        ]
                                                    }" 
                                                    class="relative w-full"
                                                    @click.outside="open = false"
                                                >
                                                    <button 
                                                        type="button"
                                                        @click="open = !open"
                                                        class="h-10 w-full rounded-lg border border-slate-200 bg-white pl-8 pr-10 text-left text-sm text-slate-700 outline-none transition hover:border-slate-300 focus:border-[#005EA6] focus:ring-2 focus:ring-blue-100"
                                                    >
                                                        <span x-text="transferRoom ? document.getElementById('room-opt-' + transferRoom)?.innerText : 'Select destination room'"></span>
                                                        
                                                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-500">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                        </span>
                                                    </button>

                                                    <input type="hidden" name="transferRoom" x-model="transferRoom">

                                                    <div 
                                                        x-show="open" 
                                                        x-transition
                                                        class="absolute z-50 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg py-1 text-sm text-slate-700 max-h-[160px] overflow-y-auto"
                                                        style="display: none;"
                                                    >
                                                        <div 
                                                            @click="transferRoom = ''; open = false" 
                                                            class="cursor-pointer px-4 py-2 hover:bg-slate-100 text-slate-400"
                                                        >
                                                            Select destination room
                                                        </div>

                                                        @foreach ($rooms as $destination)
                                                            @if ($destination->room_id != $room->room_id)
                                                                <div 
                                                                    id="room-opt-{{ $destination->room_id }}"
                                                                    @click="transferRoom = '{{ $destination->room_id }}'; open = false"
                                                                    class="cursor-pointer px-4 py-2 hover:bg-blue-50 hover:text-[#005EA6] transition-colors"
                                                                    :class="transferRoom == '{{ $destination->room_id }}' ? 'bg-blue-50 text-[#005EA6] font-medium' : ''"
                                                                >
                                                                    {{ $destination->room_name }}
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>

                                            </div>

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- ACTIONS -->
                                        <!-- ================================================= -->

                                        <div
                                            class="
                                                mt-5
                                                flex
                                                justify-end
                                                gap-3
                                                border-t
                                                border-slate-100
                                                pt-4
                                            "
                                        >

                                            <!-- CANCEL -->

                                            <button
                                                type="button"
                                                @click="panel = ''"
                                                class="
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-4
                                                    py-2
                                                    text-sm
                                                    font-medium
                                                    text-slate-600
                                                    transition

                                                    hover:border-slate-300
                                                    hover:bg-slate-50
                                                    hover:text-slate-900
                                                "
                                            >
                                                Cancel
                                            </button>


                                            <!-- TRANSFER -->

                                            <button
                                                type="button"
                                                @click="transferEquipment()"
                                                :disabled="!transferRoom"
                                                class="
                                                    rounded-lg
                                                    bg-[rgba(0,55,199,0.85)]
                                                    px-5
                                                    py-2
                                                    text-sm
                                                    font-semibold
                                                    text-white
                                                    shadow-sm
                                                    transition

                                                    hover:bg-[rgba(0, 44, 155, 0.85)]

                                                    disabled:cursor-not-allowed
                                                    disabled:opacity-50
                                                "
                                            >
                                                Transfer
                                            </button>

                                        </div>

                                    </div>

                                </div>

                                <div
                                    x-show="panel === 'archive'"
                                    x-collapse
                                    class="
                                        mt-5
                                        overflow-hidden
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                    "
                                >

                                    <!-- ===================================================== -->
                                    <!-- PANEL HEADER -->
                                    <!-- ===================================================== -->

                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            border-b
                                            border-slate-100
                                            px-5
                                            py-4
                                        "
                                    >

                                        <!-- HEADER INFORMATION -->

                                        <div>

                                            <div class="flex items-center gap-2">

                                                <div
                                                    class="
                                                        flex
                                                        h-7
                                                        w-7
                                                        items-center
                                                        justify-center
                                                        rounded-lg
                                                        bg-red-50
                                                        text-red-600
                                                    "
                                                >
                                                    <i
                                                        data-lucide="archive"
                                                        class="h-3.5 w-3.5"
                                                    ></i>
                                                </div>


                                                <h4 class="text-sm font-semibold text-slate-900">
                                                    Archive Equipment
                                                </h4>

                                            </div>


                                            <p class="mt-1.5 text-xs leading-5 text-slate-500">
                                                Remove this equipment from the active room inventory.
                                            </p>

                                        </div>


                                        <!-- CLOSE PANEL -->

                                        <button
                                            type="button"
                                            @click="panel = ''"
                                            class="
                                                flex
                                                h-8
                                                w-8
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-lg
                                                text-slate-400
                                                transition

                                                hover:bg-slate-100
                                                hover:text-slate-700
                                            "
                                        >
                                            <i data-lucide="x" class="h-4 w-4"></i>
                                        </button>

                                    </div>



                                    <!-- ===================================================== -->
                                    <!-- PANEL BODY -->
                                    <!-- ===================================================== -->

                                    <div class="p-5">


                                        <!-- ================================================= -->
                                        <!-- ARCHIVE WARNING -->
                                        <!-- ================================================= -->

                                        <div
                                            class="
                                                flex
                                                items-start
                                                gap-3
                                                rounded-lg
                                                border
                                                border-red-100
                                                bg-red-50/60
                                                p-3.5
                                            "
                                        >

                                            <i
                                                data-lucide="triangle-alert"
                                                class="
                                                    mt-0.5
                                                    h-4
                                                    w-4
                                                    shrink-0
                                                    text-red-500
                                                "
                                            ></i>


                                            <p class="text-xs leading-5 text-slate-600">
                                                This equipment will no longer appear in the active inventory.
                                                Historical maintenance and report records will remain available.
                                            </p>

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- ARCHIVE REASON -->
                                        <!-- ================================================= -->

                                        <div class="mt-5">

                                            <div
                                                class="
                                                    mb-2
                                                    flex
                                                    items-center
                                                    justify-between
                                                "
                                            >

                                                <label
                                                    class="
                                                        text-xs
                                                        font-medium
                                                        text-slate-600
                                                    "
                                                >
                                                    Reason
                                                </label>


                                                <span class="text-xs text-slate-400">
                                                    Optional
                                                </span>

                                            </div>


                                            <textarea
                                                x-model="archiveReason"
                                                rows="3"
                                                placeholder="Add a reason for archiving this equipment..."
                                                class="
                                                    w-full
                                                    resize-none
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-3
                                                    py-2.5
                                                    text-sm
                                                    leading-5
                                                    text-slate-900
                                                    outline-none
                                                    transition

                                                    placeholder:text-slate-400

                                                    hover:border-slate-300

                                                    focus:border-red-400
                                                    focus:ring-2
                                                    focus:ring-red-100
                                                "
                                            ></textarea>

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- ACTIONS -->
                                        <!-- ================================================= -->

                                        <div
                                            class="
                                                mt-5
                                                flex
                                                justify-end
                                                gap-3
                                                border-t
                                                border-slate-100
                                                pt-4
                                            "
                                        >

                                            <!-- CANCEL -->

                                            <button
                                                type="button"
                                                @click="panel = ''"
                                                class="
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-4
                                                    py-2
                                                    text-sm
                                                    font-medium
                                                    text-slate-600
                                                    transition

                                                    hover:border-slate-300
                                                    hover:bg-slate-50
                                                    hover:text-slate-900
                                                "
                                            >
                                                Cancel
                                            </button>


                                            <!-- ARCHIVE -->

                                            <button
                                                type="button"
                                                @click="archiveEquipment()"
                                                class="
                                                    rounded-lg
                                                    bg-red-600
                                                    px-5
                                                    py-2
                                                    text-sm
                                                    font-semibold
                                                    text-white
                                                    shadow-sm
                                                    transition

                                                    hover:bg-red-700
                                                "
                                            >
                                                Archive
                                            </button>

                                        </div>

                                    </div>

                                </div>
                            </div>
                        </article>
                    @empty
                        <div
                            class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400"
                        >
                            No equipment provisioned in this room.
                        </div>
                    @endforelse
                </div>

                <div
                    x-show="tab === 'analytics'"
                    x-cloak
                >

                    <!-- ===================================================== -->
                    <!-- REPORT VOLUME -->
                    <!-- ===================================================== -->

                    <div>

                        <!-- SECTION HEADER -->

                        <div class="flex items-center justify-between">

                            <div>

                                <h3 class="text-sm font-semibold text-slate-900">
                                    Report Volume
                                </h3>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Reports recorded for this room.
                                </p>

                            </div>


                            <div
                                class="
                                    flex
                                    h-8
                                    w-8
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-slate-50
                                    text-slate-400
                                "
                            >
                                <i
                                    data-lucide="chart-no-axes-column"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                        </div>



                        <!-- ================================================= -->
                        <!-- REPORT STATISTICS -->
                        <!-- ================================================= -->

                        <div class="mt-4 grid grid-cols-3 gap-3">

                            @foreach ([
                                "Today" => "today_reports",
                                "Weekly" => "week_reports",
                                "Monthly" => "month_reports"
                            ] as $label => $key)

                                <div
                                    class="
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                        px-3
                                        py-4
                                        text-center
                                        transition

                                        hover:border-slate-300
                                        hover:shadow-sm
                                    "
                                >

                                    <!-- REPORT COUNT -->

                                    <p
                                        class="
                                            text-xl
                                            font-semibold
                                            tracking-tight
                                            text-slate-900
                                        "
                                    >
                                        {{ $room->monitoring[$key] }}
                                    </p>


                                    <!-- REPORT PERIOD -->

                                    <p
                                        class="
                                            mt-1
                                            text-[11px]
                                            font-medium
                                            text-slate-500
                                        "
                                    >
                                        {{ $label }}
                                    </p>

                                </div>

                            @endforeach

                        </div>

                    </div>



                    <!-- ===================================================== -->
                    <!-- SECTION DIVIDER -->
                    <!-- ===================================================== -->

                    <div class="my-6 border-t border-slate-100"></div>



                    <!-- ===================================================== -->
                    <!-- FREQUENT PROBLEMS -->
                    <!-- ===================================================== -->

                    <div>

                        <!-- SECTION HEADER -->

                        <div>

                            <h3 class="text-sm font-semibold text-slate-900">
                                Frequent Problems
                            </h3>

                            <p class="mt-0.5 text-xs text-slate-500">
                                Issues reported repeatedly in this room.
                            </p>

                        </div>



                        <!-- ================================================= -->
                        <!-- PROBLEM LIST -->
                        <!-- ================================================= -->

                        <div class="mt-4">

                            @forelse ($room->monitoring["frequent_problems"] as $problem)

                                <div
                                    class="
                                        flex
                                        items-start
                                        gap-3
                                        border-b
                                        border-slate-100
                                        py-3.5

                                        first:pt-0
                                        last:border-b-0
                                        last:pb-0
                                    "
                                >

                                    <!-- PROBLEM ICON -->

                                    <div
                                        class="
                                            mt-0.5
                                            flex
                                            h-8
                                            w-8
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-red-50
                                            text-red-500
                                        "
                                    >
                                        <i
                                            data-lucide="triangle-alert"
                                            class="h-3.5 w-3.5"
                                        ></i>
                                    </div>



                                    <!-- PROBLEM INFORMATION -->

                                    <div class="min-w-0 flex-1">

                                        <p
                                            class="
                                                text-xs
                                                leading-5
                                                text-slate-700
                                            "
                                        >
                                            {{ $problem->report_problem_description }}
                                        </p>


                                        <p
                                            class="
                                                mt-1
                                                text-[11px]
                                                text-slate-400
                                            "
                                        >
                                            Recurring issue
                                        </p>

                                    </div>



                                    <!-- OCCURRENCE COUNT -->

                                    <div
                                        class="
                                            shrink-0
                                            rounded-md
                                            bg-slate-100
                                            px-2
                                            py-1
                                            text-[11px]
                                            font-semibold
                                            text-slate-600
                                        "
                                    >
                                        {{ $problem->occurrences }}×
                                    </div>

                                </div>


                            @empty

                                <!-- ================================================= -->
                                <!-- EMPTY STATE -->
                                <!-- ================================================= -->

                                <div
                                    class="
                                        flex
                                        flex-col
                                        items-center
                                        justify-center
                                        rounded-xl
                                        border
                                        border-dashed
                                        border-slate-200
                                        px-5
                                        py-8
                                        text-center
                                    "
                                >

                                    <div
                                        class="
                                            flex
                                            h-9
                                            w-9
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-slate-50
                                            text-slate-400
                                        "
                                    >
                                        <i
                                            data-lucide="circle-check"
                                            class="h-4 w-4"
                                        ></i>
                                    </div>


                                    <p
                                        class="
                                            mt-3
                                            text-sm
                                            font-medium
                                            text-slate-700
                                        "
                                    >
                                        No recurring problems
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            max-w-[240px]
                                            text-xs
                                            leading-5
                                            text-slate-400
                                        "
                                    >
                                        No frequently reported issues have been recorded for
                                        this room.
                                    </p>

                                </div>

                            @endforelse

                        </div>

                    </div>

                </div>

                <div
                    x-show="tab === 'schedule'"
                    x-cloak
                >

                    <!-- ===================================================== -->
                    <!-- UPCOMING MAINTENANCE -->
                    <!-- ===================================================== -->

                    <div>

                        <!-- SECTION HEADER -->

                        <div class="flex items-center justify-between">

                            <div>

                                <h3 class="text-sm font-semibold text-slate-900">
                                    Upcoming Maintenance
                                </h3>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Scheduled maintenance activities for this room.
                                </p>

                            </div>


                            <!-- HEADER ICON -->

                            <div
                                class="
                                    flex
                                    h-8
                                    w-8
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-slate-50
                                    text-slate-400
                                "
                            >
                                <i
                                    data-lucide="calendar-days"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                        </div>



                        <!-- ================================================= -->
                        <!-- SCHEDULE LIST -->
                        <!-- ================================================= -->

                        <div class="mt-4">

                            @forelse ($room->monitoring["schedules"] as $schedule)

                                <article
                                    class="
                                        flex
                                        items-start
                                        gap-3
                                        border-b
                                        border-slate-100
                                        py-4

                                        first:pt-0
                                        last:border-b-0
                                        last:pb-0
                                    "
                                >


                                    <!-- ================================================= -->
                                    <!-- DATE BLOCK -->
                                    <!-- ================================================= -->

                                    <div
                                        class="
                                            flex
                                            h-12
                                            w-12
                                            shrink-0
                                            flex-col
                                            items-center
                                            justify-center
                                            rounded-lg
                                            border
                                            border-slate-200
                                            bg-slate-50
                                        "
                                    >

                                        <!-- DAY -->

                                        <span
                                            class="
                                                text-base
                                                font-semibold
                                                leading-none
                                                text-slate-900
                                            "
                                        >
                                            {{
                                                \Carbon\Carbon::parse(
                                                    $schedule->maintenance_schedule_next_date
                                                )->format("d")
                                            }}
                                        </span>


                                        <!-- MONTH -->

                                        <span
                                            class="
                                                mt-1
                                                text-[9px]
                                                font-semibold
                                                uppercase
                                                tracking-wide
                                                text-slate-400
                                            "
                                        >
                                            {{
                                                \Carbon\Carbon::parse(
                                                    $schedule->maintenance_schedule_next_date
                                                )->format("M")
                                            }}
                                        </span>

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- SCHEDULE INFORMATION -->
                                    <!-- ================================================= -->

                                    <div class="min-w-0 flex-1">

                                        <!-- TITLE -->

                                        <h4
                                            class="
                                                truncate
                                                text-sm
                                                font-medium
                                                text-slate-800
                                            "
                                        >
                                            {{ $schedule->maintenance_schedule_title }}
                                        </h4>



                                        <!-- EQUIPMENT -->

                                        <div
                                            class="
                                                mt-1.5
                                                flex
                                                items-center
                                                gap-1.5
                                                text-xs
                                                text-slate-500
                                            "
                                        >

                                            <i
                                                data-lucide="wrench"
                                                class="
                                                    h-3
                                                    w-3
                                                    shrink-0
                                                    text-slate-400
                                                "
                                            ></i>


                                            <span class="truncate">
                                                {{ $schedule->equipment_name }}
                                            </span>

                                        </div>



                                        <!-- STATUS -->

                                        <div class="mt-2">

                                            <span
                                                class="
                                                    inline-flex
                                                    items-center
                                                    gap-1.5
                                                    rounded-md
                                                    bg-blue-50
                                                    px-2
                                                    py-1
                                                    text-[10px]
                                                    font-medium
                                                    text-[#005EA6]
                                                "
                                            >

                                                <span
                                                    class="
                                                        h-1.5
                                                        w-1.5
                                                        rounded-full
                                                        bg-[#005EA6]
                                                    "
                                                ></span>


                                                {{ $schedule->maintenance_schedule_status }}

                                            </span>

                                        </div>

                                    </div>

                                </article>


                            @empty

                                <!-- ================================================= -->
                                <!-- EMPTY STATE -->
                                <!-- ================================================= -->

                                <div
                                    class="
                                        flex
                                        flex-col
                                        items-center
                                        justify-center
                                        rounded-xl
                                        border
                                        border-dashed
                                        border-slate-200
                                        px-5
                                        py-8
                                        text-center
                                    "
                                >

                                    <!-- EMPTY STATE ICON -->

                                    <div
                                        class="
                                            flex
                                            h-10
                                            w-10
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-slate-50
                                            text-slate-400
                                        "
                                    >

                                        <i
                                            data-lucide="calendar-check"
                                            class="h-5 w-5"
                                        ></i>

                                    </div>


                                    <p
                                        class="
                                            mt-3
                                            text-sm
                                            font-medium
                                            text-slate-700
                                        "
                                    >
                                        No upcoming maintenance
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            max-w-[240px]
                                            text-xs
                                            leading-5
                                            text-slate-400
                                        "
                                    >
                                        There are no active maintenance schedules for equipment
                                        in this room.
                                    </p>

                                </div>

                            @endforelse

                        </div>

                    </div>

                </div>

                <div
                    x-show="tab === 'history'"
                    x-cloak
                >

                    <!-- ===================================================== -->
                    <!-- ROOM ACTIVITY -->
                    <!-- ===================================================== -->

                    <div>

                        <!-- SECTION HEADER -->

                        <div class="flex items-center justify-between">

                            <div>

                                <h3 class="text-sm font-semibold text-slate-900">
                                    Room Activity
                                </h3>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Recent changes and events recorded for this room.
                                </p>

                            </div>


                            <!-- HEADER ICON -->

                            <div
                                class="
                                    flex
                                    h-8
                                    w-8
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-slate-50
                                    text-slate-400
                                "
                            >
                                <i
                                    data-lucide="history"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                        </div>



                        <!-- ================================================= -->
                        <!-- ACTIVITY TIMELINE -->
                        <!-- ================================================= -->

                        <div class="mt-5">

                            @forelse ($room->monitoring["history"] as $history)

                                <div
                                    class="
                                        group
                                        relative
                                        flex
                                        gap-4
                                        pb-6

                                        last:pb-0
                                    "
                                >

                                    <!-- ================================================= -->
                                    <!-- TIMELINE -->
                                    <!-- ================================================= -->

                                    <div
                                        class="
                                            relative
                                            flex
                                            w-5
                                            shrink-0
                                            justify-center
                                        "
                                    >

                                        <!-- TIMELINE LINE -->

                                        <div
                                            class="
                                                absolute
                                                bottom-0
                                                top-3
                                                w-px
                                                bg-slate-200

                                                group-last:hidden
                                            "
                                        ></div>


                                        <!-- TIMELINE DOT -->

                                        <div
                                            class="
                                                relative
                                                z-10
                                                mt-1
                                                flex
                                                h-3
                                                w-3
                                                items-center
                                                justify-center
                                                rounded-full
                                                bg-white
                                                ring-2
                                                ring-[#005EA6]
                                            "
                                        >

                                            <div
                                                class="
                                                    h-1
                                                    w-1
                                                    rounded-full
                                                    bg-[#005EA6]
                                                "
                                            ></div>

                                        </div>

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- ACTIVITY INFORMATION -->
                                    <!-- ================================================= -->

                                    <div
                                        class="
                                            min-w-0
                                            flex-1
                                            border-b
                                            border-slate-100
                                            pb-5

                                            group-last:border-b-0
                                            group-last:pb-0
                                        "
                                    >


                                        <!-- ACTIVITY TITLE -->

                                        <p
                                            class="
                                                text-sm
                                                font-medium
                                                text-slate-800
                                            "
                                        >
                                            {{ $history->activity_title }}
                                        </p>



                                        <!-- ACTIVITY DESCRIPTION -->

                                        <p
                                            class="
                                                mt-1
                                                text-xs
                                                leading-5
                                                text-slate-500
                                            "
                                        >
                                            {{ $history->activity_description }}
                                        </p>



                                        <!-- ACTIVITY DATE -->

                                        <div
                                            class="
                                                mt-2
                                                flex
                                                items-center
                                                gap-1.5
                                                text-[11px]
                                                text-slate-400
                                            "
                                        >

                                            <i
                                                data-lucide="clock-3"
                                                class="h-3 w-3 shrink-0"
                                            ></i>


                                            <span>
                                                {{
                                                    \Carbon\Carbon::parse(
                                                        $history->created_at
                                                    )->format("M d, Y · h:i A")
                                                }}
                                            </span>

                                        </div>

                                    </div>

                                </div>


                            @empty

                                <!-- ================================================= -->
                                <!-- EMPTY STATE -->
                                <!-- ================================================= -->

                                <div
                                    class="
                                        flex
                                        flex-col
                                        items-center
                                        justify-center
                                        rounded-xl
                                        border
                                        border-dashed
                                        border-slate-200
                                        px-5
                                        py-8
                                        text-center
                                    "
                                >

                                    <!-- EMPTY ICON -->

                                    <div
                                        class="
                                            flex
                                            h-10
                                            w-10
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-slate-50
                                            text-slate-400
                                        "
                                    >
                                        <i
                                            data-lucide="history"
                                            class="h-5 w-5"
                                        ></i>
                                    </div>


                                    <!-- EMPTY TITLE -->

                                    <p
                                        class="
                                            mt-3
                                            text-sm
                                            font-medium
                                            text-slate-700
                                        "
                                    >
                                        No room activity
                                    </p>


                                    <!-- EMPTY DESCRIPTION -->

                                    <p
                                        class="
                                            mt-1
                                            max-w-[240px]
                                            text-xs
                                            leading-5
                                            text-slate-400
                                        "
                                    >
                                        Changes and events recorded for this room will appear
                                        here.
                                    </p>

                                </div>

                            @endforelse

                        </div>

                    </div>

                </div>
            </div>
        </div>
    @endforeach
</aside>

<style>
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
                archiveReason: "",

                equipmentId: equipmentId,

                form: null,

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
                        || (String(zone || '').trim() === 'Holding' ? 'Holding Area' : (zone || '—'));
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
                        `/maintenance/infrastructure/equipment/${this.equipmentId}`,
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
                        `/maintenance/infrastructure/equipment/${this.equipmentId}/transfer`,
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
                        `/maintenance/infrastructure/equipment/${this.equipmentId}`,
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
                },
            }),
        );
    });
</script>

@include('layouts.partials.equipment-category-detect')
