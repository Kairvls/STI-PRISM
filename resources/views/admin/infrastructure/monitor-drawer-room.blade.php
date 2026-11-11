{{-- Single-room inspector panel (lazy-loaded). Expects: $room, $categories, $transferDestinationRooms --}}
        @php
            $transferDestinationRooms = collect($transferDestinationRooms ?? []);
            $transferDestRoomsForRoom = $transferDestinationRooms
                ->filter(fn ($destination) => (int) ($destination['id'] ?? 0) !== (int) $room->room_id)
                ->values()
                ->all();

            $transferSourceFallback = collect($room->equipment ?? [])->map(function ($equipment) {
                $name = $equipment->equipment_name ?: ($equipment->equipment_asset_tag ?: 'Equipment');
                $tag = $equipment->equipment_asset_tag ?: null;
                $serial = $equipment->equipment_serial_number ?: null;
                $brand = $equipment->equipment_brand_name ?: null;
                $model = $equipment->equipment_model ?: null;
                $condition = $equipment->equipment_condition_status ?: null;
                $category = optional($equipment->category)->equipment_category_name
                    ?? ($equipment->equipment_category_name ?? null);
                $zone = $equipment->equipment_placement_zone ?: ($equipment->equipment_current_location ?: null);

                $unique = $tag ?: $serial;
                if (! $unique) {
                    $nameLower = mb_strtolower((string) $name);
                    $unique = collect([$brand, $model, $condition, $category, $zone])
                        ->filter(fn ($part) => filled($part) && mb_strtolower((string) $part) !== $nameLower)
                        ->implode(' · ') ?: null;
                }

                $label = ($unique && strcasecmp((string) $unique, (string) $name) !== 0)
                    ? $name.' · '.$unique
                    : $name;

                return [
                    'id' => (int) $equipment->equipment_id,
                    'name' => $name,
                    'asset_tag' => $tag,
                    'serial_number' => $serial,
                    'brand' => $brand,
                    'model' => $model,
                    'condition' => $condition,
                    'category_name' => $category,
                    'placement_zone' => $equipment->equipment_placement_zone ?: null,
                    'location' => $equipment->equipment_current_location ?: null,
                    'unique' => $unique,
                    'label' => $label,
                ];
            })->values()->all();
        @endphp
        <div
            x-show="selectedRoom === {{ $room->room_id }}"
            x-cloak
            x-data="{

                tab:'overview',

                placeFromStorageModal:false,

                placeableStock:[],

                placeableStockLoading:false,

                placeableStockError:'',

                placeSelectedIds:[],

                placeQuery:'',

                placingFromStorage:false,

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

                destinationDepartment:0,

                transferMode:'single',

                transferAllRoom:'',

                transferAllDepartment:0,

                transferTargets:{},

                transferSelectedIds:[],

                transferApplyRoom:'',

                transferApplyDepartment:0,

                transferDestRooms:@js($transferDestRoomsForRoom),

                transferSourceFallback:@js($transferSourceFallback),

                transferRoomQuery:'',

                transferOpenRoomIds:[],

                transferError:'',

                roomSaving:false,

                roomImageUrl:@js(
                    filled($room->room_image ?? null)
                        ? asset('storage/'.$room->room_image)
                        : null
                ),

                roomImageLoading:@js(filled($room->room_image ?? null)),

                roomImageUploading:false,

                roomImageError:'',

                roomForm:{
                    name:@js($room->room_name),
                    type:@js($room->room_type)
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
                        || (String(zone || '').trim() === 'Holding' ? 'Holding Area' : (zone || 'â€”'));
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

                    serial:'',

                    acqSource:'',
                    acqSupplier:'',
                    acqSupplierName:'',
                    acqReference:'',
                    acqNotes:'',
                    purchaseDate:'',
                    purchaseCost:'',
                    receivedDate:'',
                    usefulLifeYears:'',
                    custodianId:'',
                    replacesId:''

                },

                categoryManual:false,

                blankAddForm(){
                    const now = new Date();
                    const today = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
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
                        serial:'',
                        acqSource:'',
                        acqSupplier:'',
                        acqSupplierName:'',
                        acqReference:'',
                        acqNotes:'',
                        purchaseDate:'',
                        purchaseCost:'',
                        receivedDate:today,
                        usefulLifeYears:'',
                        custodianId:'',
                        replacesId:''
                    };
                },

                canAssignAddPerson(){
                    return @js(! \App\Support\RoomCategories::isStorageType($room->room_type ?? null))
                        && this.addForm.tracking === 'Individual'
                        && this.addForm.condition !== 'Disposed';
                },

                async openPlaceFromStorage(){
                    this.placeableStockError = '';
                    this.placeSelectedIds = [];
                    this.placeQuery = '';
                    this.placeableStockLoading = true;
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                    try {
                        const response = await fetch(
                            `/admin/infrastructure/rooms/{{ $room->room_id }}/placeable-stock`,
                            { headers: { Accept: 'application/json' } }
                        );
                        if (!response.ok) {
                            throw new Error('Could not load storage stock.');
                        }
                        const payload = await response.json();
                        this.placeableStock = Array.isArray(payload.items) ? payload.items : [];
                        if (payload.message && this.placeableStock.length === 0) {
                            this.placeableStockError = payload.message;
                        }
                    } catch (e) {
                        this.placeableStock = [];
                        this.placeableStockError = e?.message || 'Could not load storage stock.';
                    } finally {
                        this.placeableStockLoading = false;
                        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                    }
                },

                closePlaceFromStorage(){
                    this.placeFromStorageModal = false;
                    this.placeSelectedIds = [];
                    this.placeQuery = '';
                    this.placeableStockError = '';
                    this.placingFromStorage = false;
                    document.body.style.overflow = '';
                },

                filteredPlaceableStock(){
                    const q = String(this.placeQuery || '').trim().toLowerCase();
                    const items = Array.isArray(this.placeableStock) ? this.placeableStock : [];
                    if (!q) {
                        return items;
                    }
                    return items.filter((item) => {
                        const hay = [
                            item.name,
                            item.asset_tag,
                            item.serial_number,
                            item.category_name,
                            item.storage_room_name,
                            item.receiving_report_number,
                            item.brand,
                            item.model,
                        ]
                            .filter(Boolean)
                            .join(' ')
                            .toLowerCase();
                        return hay.includes(q);
                    });
                },

                togglePlaceStockId(id){
                    const equipmentId = Number(id);
                    if (this.placeSelectedIds.includes(equipmentId)) {
                        this.placeSelectedIds = this.placeSelectedIds.filter((value) => value !== equipmentId);
                    } else {
                        this.placeSelectedIds = [...this.placeSelectedIds, equipmentId];
                    }
                },

                selectAllPlaceableStock(){
                    this.placeSelectedIds = this.filteredPlaceableStock().map((item) => Number(item.id));
                },

                async confirmPlaceFromStorage(){
                    if (this.placingFromStorage || this.placeSelectedIds.length === 0) {
                        return;
                    }
                    this.placingFromStorage = true;
                    this.placeableStockError = '';
                    try {
                        const response = await fetch(
                            `/admin/infrastructure/rooms/{{ $room->room_id }}/place-from-storage`,
                            {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                                },
                                body: JSON.stringify({
                                    equipment_ids: this.placeSelectedIds,
                                }),
                            }
                        );
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const firstError = payload?.errors
                                ? Object.values(payload.errors).flat()[0]
                                : null;
                            throw new Error(firstError || payload?.message || 'Could not place equipment.');
                        }
                        this.closePlaceFromStorage();
                        if (window.infrastructure?.refreshRoomEquipment) {
                            await window.infrastructure.refreshRoomEquipment({{ $room->room_id }});
                        }
                        if (window.infrastructure) {
                            window.infrastructure.toast = payload.message || 'Assets placed from storage.';
                        }
                    } catch (e) {
                        this.placeableStockError = e?.message || 'Could not place equipment.';
                    } finally {
                        this.placingFromStorage = false;
                    }
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

                                    equipment_position_y:position.y,

                                    equipment_acquisition_source: this.addForm.acqSource || null,

                                    acquisition_supplier: this.addForm.acqSupplier || null,

                                    equipment_supplier_name: this.addForm.acqSupplier === @js(\App\Support\EquipmentAcquisition::OTHER_SUPPLIER)
                                        ? (this.addForm.acqSupplierName || null)
                                        : null,

                                    equipment_reference_number: this.addForm.acqReference || null,

                                    equipment_acquisition_notes: this.addForm.acqNotes || null,

                                    equipment_purchase_date: this.addForm.purchaseDate || null,

                                    equipment_purchase_cost: this.addForm.purchaseCost !== '' ? this.addForm.purchaseCost : null,

                                    equipment_acquired_date: this.addForm.receivedDate || null,

                                    equipment_useful_life_years: this.addForm.usefulLifeYears || null,

                                    custodian_id: this.canAssignAddPerson() ? (this.addForm.custodianId || null) : null,

                                    replaces_equipment_id: this.addForm.replacesId || null

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

                            '/admin/infrastructure/equipment',

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
                                        else if (key.includes('custodian')) fieldErrors.custodian = msg;
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
                        this.placeFromStorageModal = false;
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
                            `/admin/infrastructure/rooms/{{ $room->room_id }}`,
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

                                    room_type:this.roomForm.type

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
                                type: payload?.room?.type ?? this.roomForm.type,
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
                    this.destinationDepartment = 0;
                    this.transferAllRoom = '';
                    this.transferAllDepartment = 0;
                    this.transferTargets = {};
                    this.transferSelectedIds = [];
                    this.transferApplyRoom = '';
                    this.transferApplyDepartment = 0;
                    this.transferRoomQuery = '';
                    this.transferOpenRoomIds = [];
                    this.transferError = '';
                    this.initTransferTargets();
                },

                transferPickerIsMulti(field){
                    return field === 'apply';
                },

                shownTransferDestRooms(){
                    const q = String(this.transferRoomQuery || '').trim().toLowerCase();
                    const rooms = Array.isArray(this.transferDestRooms) ? this.transferDestRooms : [];
                    if (!q) return rooms;
                    return rooms.filter((room) => {
                        const hay = [room.name, room.type, ...(room.departments || []).map((d) => d.name)]
                            .join(' ')
                            .toLowerCase();
                        return hay.includes(q);
                    });
                },

                isTransferRoomOpen(room){
                    return this.transferOpenRoomIds.includes(Number(room.id));
                },

                toggleTransferRoomOpen(room){
                    const id = Number(room.id);
                    if (this.transferOpenRoomIds.includes(id)) {
                        this.transferOpenRoomIds = this.transferOpenRoomIds.filter((item) => item !== id);
                    } else {
                        this.transferOpenRoomIds = [...this.transferOpenRoomIds, id];
                    }
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                transferPickFor(field){
                    if (field === 'all') {
                        return {
                            roomId: Number(this.transferAllRoom || 0),
                            departmentId: Number(this.transferAllDepartment || 0),
                        };
                    }
                    if (field === 'apply') {
                        return {
                            roomId: Number(this.transferApplyRoom || 0),
                            departmentId: Number(this.transferApplyDepartment || 0),
                        };
                    }
                    return {
                        roomId: Number(this.destinationRoom || 0),
                        departmentId: Number(this.destinationDepartment || 0),
                    };
                },

                // Destinations currently used by at least one equipment row (source of truth).
                transferDestinationsInUse(){
                    const keys = [];
                    Object.values(this.transferTargets || {}).forEach((value) => {
                        const parsed = this.parseTransferTarget(value);
                        if (!parsed.roomId) return;
                        const encoded = this.encodeTransferTarget(parsed.roomId, parsed.departmentId);
                        if (!keys.includes(encoded)) {
                            keys.push(encoded);
                        }
                    });
                    return keys;
                },

                selectTransferDestination(field, room, dept){
                    const roomId = Number(room.id);
                    const departmentId = Number(dept?.id || 0);
                    const encoded = this.encodeTransferTarget(roomId, departmentId);

                    if (field === 'all') {
                        this.transferAllRoom = roomId;
                        this.transferAllDepartment = departmentId;
                    } else if (field === 'apply') {
                        this.transferApplyRoom = roomId;
                        this.transferApplyDepartment = departmentId;
                        if (this.transferDestinationsInUse().includes(encoded)) {
                            // Uncheck: clear only rows using this destination.
                            this.clearTransferDestinationFromEquipment(encoded);
                        } else {
                            // Check: fill Keep here / skip rows only (never overwrite).
                            this.fillUnassignedTransferDestinations(encoded, false);
                        }
                    } else {
                        this.destinationRoom = roomId;
                        this.destinationDepartment = departmentId;
                    }

                    if (!this.isTransferRoomOpen(room)) {
                        this.transferOpenRoomIds = [...this.transferOpenRoomIds, roomId];
                    }
                    this.transferError = '';
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                equipmentHasTransferDestination(equipmentId){
                    const parsed = this.parseTransferTarget(this.transferTargets?.[equipmentId]);
                    return Boolean(parsed.roomId);
                },

                clearTransferDestinationFromEquipment(encoded){
                    const next = { ...this.transferTargets };
                    Object.keys(next).forEach((equipmentId) => {
                        if (next[equipmentId] === encoded) {
                            next[equipmentId] = '';
                        }
                    });
                    this.transferTargets = next;
                },

                // Fill empty rows with one destination. onlySelected=true limits to checked rows.
                fillUnassignedTransferDestinations(encoded, onlySelected){
                    if (!encoded) return 0;

                    const allIds = this.transferSourceEquipment().map((item) => item.id);
                    let candidateIds = allIds;
                    if (onlySelected) {
                        if (!this.transferSelectedIds.length) {
                            return 0;
                        }
                        candidateIds = allIds.filter((id) =>
                            this.transferSelectedIds.includes(Number(id))
                        );
                    }

                    const unassignedIds = candidateIds.filter(
                        (id) => !this.equipmentHasTransferDestination(id)
                    );
                    if (!unassignedIds.length) {
                        return 0;
                    }

                    const next = { ...this.transferTargets };
                    unassignedIds.forEach((id) => {
                        next[id] = encoded;
                    });
                    this.transferTargets = next;

                    const mergedSelected = new Set([
                        ...(this.transferSelectedIds || []),
                        ...unassignedIds.map((id) => Number(id)),
                    ]);
                    this.transferSelectedIds = [...mergedSelected];
                    return unassignedIds.length;
                },

                isTransferDestinationSelected(field, roomId, deptId){
                    if (this.transferPickerIsMulti(field)) {
                        const encoded = this.encodeTransferTarget(roomId, deptId);
                        return this.transferDestinationsInUse().includes(encoded);
                    }
                    const pick = this.transferPickFor(field);
                    return pick.roomId === Number(roomId) && pick.departmentId === Number(deptId);
                },

                isTransferRoomEntireSelected(field, room){
                    return this.isTransferDestinationSelected(field, room.id, 0);
                },

                transferRoomSelectedCount(field, room){
                    const roomId = Number(room.id);
                    if (this.transferPickerIsMulti(field)) {
                        return this.transferDestinationsInUse().filter((key) => {
                            return this.parseTransferTarget(key).roomId === roomId;
                        }).length;
                    }
                    const pick = this.transferPickFor(field);
                    return pick.roomId === roomId ? 1 : 0;
                },

                transferRoomSelectedLabel(field, room){
                    const count = this.transferRoomSelectedCount(field, room);
                    if (!count) {
                        return 'None selected';
                    }
                    if (this.transferPickerIsMulti(field)) {
                        return count + ' selected';
                    }
                    const pick = this.transferPickFor(field);
                    const dept = (room.departments || []).find((item) => Number(item.id) === pick.departmentId);
                    return dept?.name || (pick.departmentId === 0 ? 'Entire room' : 'Selected');
                },

                encodeTransferTarget(roomId, departmentId){
                    return String(Number(roomId || 0)) + ':' + String(Number(departmentId || 0));
                },

                parseTransferTarget(value){
                    if (!value) {
                        return { roomId: 0, departmentId: 0 };
                    }
                    if (typeof value === 'object') {
                        return {
                            roomId: Number(value.roomId || value.room_id || 0),
                            departmentId: Number(value.departmentId || value.department_id || 0),
                        };
                    }
                    const raw = String(value);
                    if (raw.includes(':')) {
                        const [roomId, departmentId] = raw.split(':');
                        return { roomId: Number(roomId || 0), departmentId: Number(departmentId || 0) };
                    }
                    return { roomId: Number(raw || 0), departmentId: 0 };
                },

                transferTargetLabel(value){
                    const parsed = this.parseTransferTarget(value);
                    if (!parsed.roomId) return '';
                    const room = (this.transferDestRooms || []).find((item) => Number(item.id) === parsed.roomId);
                    if (!room) return 'Room #' + parsed.roomId;
                    if (!parsed.departmentId) return room.name + ' · Entire room';
                    const dept = (room.departments || []).find((item) => Number(item.id) === parsed.departmentId);
                    return room.name + ' · ' + (dept?.name || ('Dept #' + parsed.departmentId));
                },

                transferEquipmentUnique(item){
                    const clean = (value) => String(value || '').trim();
                    const tag = clean(item?.asset_tag);
                    const serial = clean(item?.serial_number);
                    if (tag) return tag;
                    if (serial) return serial;

                    const details = [
                        clean(item?.brand),
                        clean(item?.model),
                        clean(item?.condition),
                        clean(item?.category_name),
                        clean(item?.placement_zone || item?.location),
                    ].filter(Boolean);

                    // Drop values that only repeat the equipment name.
                    const name = clean(item?.name).toLowerCase();
                    const uniqueDetails = details.filter((part) => part.toLowerCase() !== name);

                    return uniqueDetails.join(' · ');
                },

                transferEquipmentLabel(item){
                    const base = String(item?.name || item?.asset_tag || 'Equipment').trim();
                    const unique = this.transferEquipmentUnique(item);
                    if (unique && unique.toLowerCase() !== base.toLowerCase()) {
                        return base + ' · ' + unique;
                    }
                    return base;
                },

                orderedTransferDepartments(field, room){
                    const departments = Array.isArray(room?.departments) ? [...room.departments] : [];
                    const roomId = Number(room?.id);
                    const selectedIds = new Set();

                    if (this.transferPickerIsMulti(field)) {
                        this.transferDestinationsInUse().forEach((key) => {
                            const parsed = this.parseTransferTarget(key);
                            if (parsed.roomId === roomId) {
                                selectedIds.add(Number(parsed.departmentId));
                            }
                        });
                    } else {
                        const pick = this.transferPickFor(field);
                        if (pick.roomId === roomId) {
                            selectedIds.add(Number(pick.departmentId));
                        }
                    }

                    if (!selectedIds.size) {
                        return departments;
                    }

                    const selected = [];
                    const entire = [];
                    const rest = [];
                    departments.forEach((dept) => {
                        const id = Number(dept.id);
                        if (selectedIds.has(id)) {
                            selected.push(dept);
                        } else if (id === 0) {
                            entire.push(dept);
                        } else {
                            rest.push(dept);
                        }
                    });

                    // In-use destinations first, then Entire room, then the rest.
                    return [...selected, ...entire, ...rest];
                },

                onCustomTransferTargetChange(equipmentId, value){
                    this.transferTargets = { ...this.transferTargets, [equipmentId]: value };
                    const parsed = this.parseTransferTarget(value);
                    if (parsed.roomId) {
                        this.transferApplyRoom = parsed.roomId;
                        this.transferApplyDepartment = parsed.departmentId;
                        if (!this.transferOpenRoomIds.includes(parsed.roomId)) {
                            this.transferOpenRoomIds = [...this.transferOpenRoomIds, parsed.roomId];
                        }
                        if (!this.transferSelectedIds.includes(Number(equipmentId))) {
                            this.transferSelectedIds = [...this.transferSelectedIds, Number(equipmentId)];
                        }
                    }
                    this.transferError = '';
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                transferSourceEquipment(){
                    const room = window.infrastructure?.roomCatalog?.find(
                        (item) => item.id === {{ $room->room_id }}
                    );
                    if (room && Array.isArray(room.equipment) && room.equipment.length) {
                        return room.equipment.map((item) => {
                            const mapped = {
                                id: Number(item.id),
                                name: item.name || item.asset_tag || 'Equipment',
                                asset_tag: item.asset_tag || null,
                                serial_number: item.serial_number || null,
                                brand: item.brand || null,
                                model: item.model || null,
                                condition: item.condition || null,
                                category_name: item.category_name || null,
                                placement_zone: item.placement_zone || null,
                                location: item.location || null,
                            };
                            mapped.unique = this.transferEquipmentUnique(mapped);
                            mapped.label = this.transferEquipmentLabel(mapped);
                            return mapped;
                        });
                    }
                    return this.transferSourceFallback || [];
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
                        this.transferError = 'Click a destination room / department first, then Apply to selected.';
                        return;
                    }
                    if (!this.transferSelectedIds.length) {
                        this.transferError = 'Check at least one equipment row first.';
                        return;
                    }
                    const encoded = this.encodeTransferTarget(
                        this.transferApplyRoom,
                        this.transferApplyDepartment
                    );
                    const filled = this.fillUnassignedTransferDestinations(encoded, true);
                    if (!filled) {
                        this.transferError = 'Checked rows already have destinations. Clear a row to Keep here / skip first.';
                        return;
                    }
                    this.transferError = '';
                },

                setAllCustomDestinations(roomId, departmentId = 0){
                    if (!roomId) return;
                    const encoded = this.encodeTransferTarget(roomId, departmentId);
                    const next = {};
                    this.transferSourceEquipment().forEach((item) => {
                        next[item.id] = encoded;
                    });
                    this.transferTargets = next;
                    this.transferError = '';
                },

                buildTransferPayload(){
                    if (this.transferMode === 'single') {
                        if (!this.selectedEquipment || !this.destinationRoom) {
                            this.transferError = 'Select equipment and a destination room / department.';
                            return null;
                        }
                        return [{
                            equipment_id: Number(this.selectedEquipment),
                            room_id: Number(this.destinationRoom),
                            department_id: Number(this.destinationDepartment || 0) || null,
                        }];
                    }

                    if (this.transferMode === 'all') {
                        if (!this.transferAllRoom) {
                            this.transferError = 'Select a destination room / department for all equipment.';
                            return null;
                        }
                        const items = this.transferSourceEquipment();
                        if (!items.length) {
                            this.transferError = 'This room has no equipment to transfer.';
                            return null;
                        }
                        const departmentId = Number(this.transferAllDepartment || 0) || null;
                        return items.map((item) => ({
                            equipment_id: item.id,
                            room_id: Number(this.transferAllRoom),
                            department_id: departmentId,
                        }));
                    }

                    const transfers = this.transferSourceEquipment()
                        .map((item) => {
                            const parsed = this.parseTransferTarget(this.transferTargets[item.id]);
                            return {
                                equipment_id: item.id,
                                room_id: parsed.roomId,
                                department_id: parsed.departmentId || null,
                            };
                        })
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
                                `/admin/infrastructure/equipment/${transfers[0].equipment_id}/transfer`,
                                {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    },
                                    body: JSON.stringify({
                                        room_id: transfers[0].room_id,
                                        department_id: transfers[0].department_id,
                                    }),
                                },
                            );
                        } else {
                            response = await fetch(
                                `/admin/infrastructure/rooms/{{ $room->room_id }}/transfer-equipment`,
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

                pickRoomImage(){
                    if (this.roomImageUploading) return;
                    this.$refs.roomImageInput?.click();
                },

                async onRoomImageSelected(event){
                    const file = event?.target?.files?.[0];
                    if (!file) return;

                    this.roomImageError = '';
                    this.roomImageUploading = true;

                    try {
                        const formData = new FormData();
                        formData.append('room_image', file);

                        const response = await fetch(
                            `/admin/infrastructure/rooms/{{ $room->room_id }}/image`,
                            {
                                method: 'POST',
                                headers: {
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                                },
                                body: formData,
                            }
                        );

                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const firstError = payload?.errors
                                ? Object.values(payload.errors).flat()[0]
                                : null;
                            throw new Error(firstError || payload?.message || 'Could not upload room photo.');
                        }

                        const nextUrl = payload?.room?.image_url || null;
                        this.roomImageLoading = !!nextUrl;
                        this.roomImageUrl = nextUrl;
                        window.infrastructure?.invalidateRoomInspectorCache?.({{ $room->room_id }});
                        if (window.infrastructure) {
                            window.infrastructure.toast = payload.message || 'Room photo updated.';
                            setTimeout(() => {
                                if (window.infrastructure?.toast === (payload.message || 'Room photo updated.')) {
                                    window.infrastructure.toast = '';
                                }
                            }, 3000);
                        }
                        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                    } catch (e) {
                        this.roomImageError = e?.message || 'Could not upload room photo.';
                    } finally {
                        this.roomImageUploading = false;
                        if (event?.target) event.target.value = '';
                    }
                },

                async removeRoomImage(){
                    if (this.roomImageUploading || !this.roomImageUrl) return;
                    if (!confirm('Remove this room photo?')) return;

                    this.roomImageError = '';
                    this.roomImageUploading = true;

                    try {
                        const response = await fetch(
                            `/admin/infrastructure/rooms/{{ $room->room_id }}/image`,
                            {
                                method: 'DELETE',
                                headers: {
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                                },
                            }
                        );

                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(payload?.message || 'Could not remove room photo.');
                        }

                        this.roomImageUrl = null;
                        this.roomImageLoading = false;
                        window.infrastructure?.invalidateRoomInspectorCache?.({{ $room->room_id }});
                        if (window.infrastructure) {
                            window.infrastructure.toast = payload.message || 'Room photo removed.';
                            setTimeout(() => {
                                if (window.infrastructure?.toast === (payload.message || 'Room photo removed.')) {
                                    window.infrastructure.toast = '';
                                }
                            }, 3000);
                        }
                        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                    } catch (e) {
                        this.roomImageError = e?.message || 'Could not remove room photo.';
                    } finally {
                        this.roomImageUploading = false;
                    }
                },

            }"
            class="flex h-full min-h-0 flex-col overflow-hidden"
        >
            {{-- Room details: Room header (white, clean) --}}
            <div class="shrink-0 border-b border-slate-100">

                {{-- Room photo preview (upload / edit) --}}
                <div class="px-5 pt-4">
                    <input
                        type="file"
                        class="hidden"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        x-ref="roomImageInput"
                        @change="onRoomImageSelected($event)"
                    />
                    <div
                        class="group relative overflow-hidden rounded-2xl bg-slate-50 ring-1 ring-slate-200"
                        :class="roomImageUrl ? 'aspect-[4/3]' : 'aspect-[16/10]'"
                    >
                        <template x-if="roomImageUrl">
                            <img
                                :src="roomImageUrl"
                                alt="Room photo"
                                class="h-full w-full object-cover"
                                :class="roomImageLoading ? 'opacity-0' : 'opacity-100'"
                                @load="roomImageLoading = false"
                                x-on:error="roomImageLoading = false"
                            />
                        </template>
                        <template x-if="!roomImageUrl">
                            <button
                                type="button"
                                @click="pickRoomImage()"
                                :disabled="roomImageUploading"
                                class="flex h-full w-full flex-col items-center justify-center gap-2 px-4 py-6 text-center transition hover:bg-slate-100/80 disabled:cursor-wait disabled:opacity-70"
                            >
                                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-[#005EA6] shadow-sm ring-1 ring-slate-200">
                                    <i data-lucide="image-plus" class="h-5 w-5"></i>
                                </span>
                                <span class="text-sm font-semibold text-slate-800">Add room photo</span>
                                <span class="text-[11px] leading-4 text-slate-500">JPG, PNG, or WebP · up to 5 MB</span>
                            </button>
                        </template>

                        <div
                            x-show="roomImageUrl && roomImageLoading && !roomImageUploading"
                            x-cloak
                            class="absolute inset-0 z-[1] flex items-center justify-center bg-slate-50"
                        >
                            <p class="text-sm font-semibold text-slate-500">Loading…</p>
                        </div>

                        <div
                            x-show="roomImageUrl && !roomImageLoading"
                            x-cloak
                            class="absolute inset-x-0 bottom-0 z-[2] flex items-center justify-between gap-2 bg-gradient-to-t from-[#0b1220]/75 via-[#0b1220]/35 to-transparent px-3 pb-3 pt-10 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100"
                        >
                            <button
                                type="button"
                                @click="pickRoomImage()"
                                :disabled="roomImageUploading"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-white/95 px-2.5 py-1.5 text-[11px] font-semibold text-slate-800 shadow-sm hover:bg-white disabled:opacity-60"
                            >
                                <i data-lucide="pencil" class="h-3 w-3"></i>
                                <span x-text="roomImageUploading ? 'Uploading…' : 'Replace'"></span>
                            </button>
                            <button
                                type="button"
                                @click="removeRoomImage()"
                                :disabled="roomImageUploading"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-white/95 px-2.5 py-1.5 text-[11px] font-semibold text-rose-700 shadow-sm hover:bg-white disabled:opacity-60"
                            >
                                <i data-lucide="trash-2" class="h-3 w-3"></i>
                                Remove
                            </button>
                        </div>

                        <div
                            x-show="roomImageUploading"
                            x-cloak
                            class="absolute inset-0 flex items-center justify-center bg-white/55 backdrop-blur-[1px]"
                        >
                            <span class="rounded-full bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200">
                                Uploading…
                            </span>
                        </div>
                    </div>
                    <p
                        x-show="roomImageError"
                        x-cloak
                        class="mt-2 text-[11px] font-medium text-rose-600"
                        x-text="roomImageError"
                    ></p>
                </div>

                {{-- Title row --}}
                <div class="px-5 py-4 text-center">
                    <h2 class="text-base font-black leading-snug text-slate-950 break-words" x-text="roomForm.name || 'Not Specified'"></h2>
                    <p class="mt-0.5 text-xs font-medium text-slate-400 break-words" x-text="roomForm.type || 'No Room Type'"></p>
                </div>
            </div>

            {{-- Room details: Icon tab bar --}}
            <div class="flex shrink-0 items-stretch border-b border-slate-100">
                @foreach ([
                    ['key'=>'overview',   'icon'=>'layout-dashboard', 'label'=>'Overview'],
                    ['key'=>'equipment',  'icon'=>'box',              'label'=>'Equipments'],
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
                class="drawer-scroll relative min-h-0 flex-1 overflow-y-auto overflow-x-hidden overscroll-contain bg-white p-5"
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

                    {{-- Status + key metrics (minimal) --}}
                    <section class="space-y-5">
                        <div>
                            <p class="text-[11px] font-medium tracking-[0.14em] text-slate-400">Status</p>
                            <div class="mt-2 flex items-baseline gap-2.5">
                                <span
                                    class="mt-1.5 h-2 w-2 shrink-0 self-center rounded-full"
                                    :class="currentRoom?.monitoring?.room_information?.status === 'Critical'
                                        ? 'bg-rose-500'
                                        : currentRoom?.monitoring?.room_information?.status === 'Under Maintenance'
                                            ? 'bg-amber-400'
                                            : 'bg-emerald-500'"
                                ></span>
                                <p
                                    class="text-lg font-semibold leading-none tracking-tight text-slate-950"
                                    x-text="currentRoom?.monitoring?.room_information?.status || 'Normal'"
                                ></p>
                            </div>
                            <template x-if="currentRoom?.monitoring?.room_information?.status === 'Under Maintenance'">
                                <div class="mt-2.5 flex flex-wrap gap-1.5">
                                    <template
                                        x-for="scope in (currentRoom?.maintenanceScopes
                                            || currentRoom?.monitoring?.room_information?.maintenance_scopes
                                            || ['Entire room'])"
                                        :key="'ov-'+scope"
                                    >
                                        <span
                                            class="text-[11px] font-medium text-amber-700"
                                            x-text="scope"
                                        ></span>
                                    </template>
                                </div>
                            </template>
                            <template x-if="currentRoom?.monitoring?.room_information?.status === 'Critical'">
                                <p class="mt-2 text-[12px] font-medium text-rose-600">
                                    <span
                                        x-text="Number(currentRoom?.criticalReportCount
                                            || currentRoom?.monitoring?.room_information?.critical_report_count
                                            || 0)"
                                    ></span>
                                    critical report(s) this period
                                </p>
                            </template>
                            <p class="mt-2 text-[12px] text-slate-400">
                                Updated
                                <span
                                    x-text="currentRoom.monitoring.room_information.last_updated
                                        ? timeAgo(currentRoom.monitoring.room_information.last_updated)
                                        : 'Unknown'"
                                ></span>
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-[11px] font-medium tracking-[0.14em] text-slate-400">Equipments</p>
                                <p class="mt-1.5 text-lg font-semibold leading-none tracking-tight tabular-nums text-slate-950">{{ $room->monitoring["equipment_quantity"] }}</p>
                                <p class="mt-1.5 text-[12px] text-slate-400">registered</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-medium tracking-[0.14em] text-slate-400">Reports</p>
                                <p class="mt-1.5 text-lg font-semibold leading-none tracking-tight tabular-nums text-slate-950">{{ $room->monitoring["active_reports"] }}</p>
                                <p class="mt-1.5 text-[12px] text-slate-400">unresolved</p>
                            </div>
                        </div>
                    </section>

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
                                        x-text="(currentRoom?.roomInspectionNextDate
                                            || currentRoom?.monitoring?.room_information?.room_inspection_next_date
                                            || currentRoom?.monitoring?.room_information?.next_maintenance)
                                            ? formatDate(currentRoom?.roomInspectionNextDate
                                                || currentRoom?.monitoring?.room_information?.room_inspection_next_date
                                                || currentRoom.monitoring.room_information.next_maintenance)
                                            : 'No schedule'"
                                    ></p>
                                    <template x-if="(currentRoom?.maintenanceScopes
                                        || currentRoom?.monitoring?.room_information?.maintenance_scopes
                                        || []).length">
                                        <p
                                            class="mt-1 text-[11px] text-slate-500"
                                            x-text="'Scope: ' + (currentRoom?.maintenanceScopes
                                                || currentRoom?.monitoring?.room_information?.maintenance_scopes
                                                || []).join(', ')"
                                        ></p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Equipment health (minimal) --}}
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
                        $healthBreakdown = [
                            ['label' => 'Good', 'count' => $eqGood, 'pct' => $pctGood, 'tone' => 'bg-emerald-500'],
                            ['label' => 'Maintenance', 'count' => $eqMaint, 'pct' => $pctMaint, 'tone' => 'bg-sky-400'],
                            ['label' => 'Damaged', 'count' => $eqDamaged, 'pct' => $pctDamaged, 'tone' => 'bg-slate-500'],
                            ['label' => 'Disposed', 'count' => $eqDisposed, 'pct' => $pctDisposed, 'tone' => 'bg-slate-300'],
                        ];
                    @endphp
                    <section class="space-y-4">
                        <div class="flex items-end justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-medium tracking-[0.14em] text-slate-400">Condition</p>
                                <p class="mt-1 text-[15px] font-semibold tracking-tight text-slate-950">Equipment health</p>
                            </div>
                            <p class="pb-0.5 text-[12px] tabular-nums text-slate-400">{{ (int) $room->monitoring['equipment_quantity'] }} total</p>
                        </div>

                        <div class="flex h-1.5 overflow-hidden rounded-full bg-slate-100">
                            @foreach ($healthBreakdown as $row)
                                @if ($row['count'] > 0)
                                    <div class="{{ $row['tone'] }}" style="width: {{ $row['pct'] }}%"></div>
                                @endif
                            @endforeach
                        </div>

                        <div class="space-y-3">
                            @foreach ($healthBreakdown as $row)
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $row['tone'] }}"></span>
                                        <span class="text-[13px] text-slate-600">{{ $row['label'] }}</span>
                                    </div>
                                    <span class="text-[13px] font-semibold tabular-nums text-slate-950">{{ $row['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    {{-- Quick actions (History lives in the History tab above â€” avoids leaving this page) --}}
                    <section>
                        <p class="mb-3 text-[11px] font-medium tracking-[0.18em] text-slate-400 uppercase">Quick actions</p>
                        <div class="grid grid-cols-3 overflow-hidden rounded-xl border border-slate-200">
                            <button
                                type="button"
                                @click="placeFromStorageModal = true"
                                class="flex flex-col items-center gap-1.5 px-1 py-3.5 text-center transition hover:bg-slate-50"
                            >
                                <i data-lucide="package-plus" class="h-4 w-4 text-[#005EA6]"></i>
                                <span class="text-[11px] font-medium leading-4 text-slate-800">Add Equipment</span>
                            </button>
                            <button
                                type="button"
                                @click="editRoomModal = true; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                                class="flex flex-col items-center gap-1.5 border-l border-slate-200 px-1 py-3.5 text-center transition hover:bg-slate-50"
                            >
                                <i data-lucide="pencil" class="h-4 w-4 text-slate-700"></i>
                                <span class="text-[11px] font-medium text-slate-800">Edit Room</span>
                            </button>
                            <button
                                type="button"
                                @click="openTransferModal()"
                                class="flex flex-col items-center gap-1.5 border-l border-slate-200 px-1 py-3.5 text-center transition hover:bg-slate-50"
                            >
                                <i data-lucide="arrow-right-left" class="h-4 w-4 text-slate-700"></i>
                                <span
                                    class="text-[11px] font-medium leading-4 text-slate-800"
                                    title="Transfer Equipment"
                                >Transfer Eqp...</span>
                            </button>
                        </div>
                    </section>
                </div>


                @include('admin.infrastructure.partials.place-from-storage-modal')


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


                                </div>


                                <!-- CLOSE BUTTON -->

                                <button
                                    type="button"
                                    @click="editRoomModal = false"
                                    aria-label="Close"
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
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="h-4 w-4"
                                        aria-hidden="true"
                                    >
                                        <path d="M18 6 6 18" />
                                        <path d="m6 6 12 12" />
                                    </svg>
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
                                            Room Name <span class="text-red-500">*</span>
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
                                    <!-- OPERATIONAL STATUS (layout-derived, read-only) -->
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

                                        <div
                                            class="
                                                flex
                                                h-11
                                                w-full
                                                items-center
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-slate-50
                                                px-3.5
                                                text-sm
                                                font-medium
                                                text-slate-800
                                            "
                                            x-text="currentRoom?.status || currentRoom?.monitoring?.room_information?.status || 'Normal'"
                                        ></div>

                                        <template x-if="(currentRoom?.status || currentRoom?.monitoring?.room_information?.status) === 'Under Maintenance'">
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                <template
                                                    x-for="scope in (currentRoom?.maintenanceScopes
                                                        || currentRoom?.monitoring?.room_information?.maintenance_scopes
                                                        || ['Entire room'])"
                                                    :key="scope"
                                                >
                                                    <span
                                                        class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800 ring-1 ring-amber-100"
                                                        x-text="scope"
                                                    ></span>
                                                </template>
                                            </div>
                                        </template>

                                        <template x-if="(currentRoom?.status || currentRoom?.monitoring?.room_information?.status) === 'Critical'">
                                            <p class="mt-2 text-[11px] leading-4 text-slate-500">
                                                <span
                                                    x-text="Number(currentRoom?.criticalReportCount
                                                        || currentRoom?.monitoring?.room_information?.critical_report_count
                                                        || 0)"
                                                ></span>
                                                critical report(s) in the status period.
                                            </p>
                                        </template>

                                        <p class="mt-1.5 text-[11px] leading-4 text-slate-400">
                                            Driven by open critical reports and active room inspections. Not editable here.
                                        </p>

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

                                            <option value="Storage / Stockroom">
                                                Storage / Stockroom
                                            </option>

                                        </select>

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
                            :class="transferMode === 'custom' ? 'max-w-3xl' : 'max-w-xl sm:max-w-2xl'"
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

                                <div x-show="transferMode === 'single'" class="space-y-5">
                                    <div>
                                        <label class="mb-2 block text-xs font-medium text-slate-600">Equipment <span class="text-red-500">*</span></label>
                                        <select
                                            x-model="selectedEquipment"
                                            class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3.5 text-sm text-slate-700 outline-none transition hover:border-slate-300 focus:border-[#005EA6] focus:ring-2 focus:ring-blue-100"
                                        >
                                            <option value="">Select Equipment</option>
                                            <template x-for="item in transferSourceEquipment()" :key="'single-eq-' + item.id">
                                                <option :value="item.id" x-text="item.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-xs font-medium text-slate-600">Destination room &amp; department <span class="text-red-500">*</span></label>
                                        <p class="mb-2 text-[11px] text-slate-500">Expand a room, then pick Entire room or a specific department.</p>
                                        @include('admin.infrastructure.partials.transfer-destination-picker', ['field' => 'destination'])
                                    </div>
                                </div>

                                <div x-show="transferMode === 'all'" x-cloak class="space-y-4">
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                        Transfer
                                        <span class="font-semibold text-slate-900" x-text="transferSourceEquipment().length"></span>
                                        <span x-text="transferSourceEquipment().length === 1 ? ' item' : ' items'"></span>
                                        from <span class="font-semibold text-slate-900">{{ $room->room_name }}</span> to one destination room / department.
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-xs font-medium text-slate-600">Destination room &amp; department <span class="text-red-500">*</span></label>
                                        @include('admin.infrastructure.partials.transfer-destination-picker', ['field' => 'all'])
                                    </div>
                                </div>

                                <div x-show="transferMode === 'custom'" x-cloak class="space-y-4">
                                    <p class="text-sm text-slate-600">
                                        Click one department at a time to fill empty rows. Already assigned rows stay put. Top checkboxes mirror destinations in use. Uncheck a department to clear only those rows.
                                    </p>

                                    <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <label class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Destination room &amp; department</label>
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    type="button"
                                                    @click="applyDestinationToSelected()"
                                                    class="h-9 rounded-lg bg-[#0025cc] px-3 text-xs font-medium text-white hover:bg-[#001fad]"
                                                >Apply to selected</button>
                                                <button
                                                    type="button"
                                                    @click="selectAllTransferEquipment()"
                                                    class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                                >Select all</button>
                                                <button
                                                    type="button"
                                                    @click="clearTransferSelection()"
                                                    class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                                >Clear</button>
                                            </div>
                                        </div>
                                        @include('admin.infrastructure.partials.transfer-destination-picker', ['field' => 'apply'])
                                    </div>

                                    <div class="overflow-hidden rounded-xl ring-1 ring-slate-200">
                                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                            <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                                <tr>
                                                    <th class="w-10 px-3 py-2.5"></th>
                                                    <th class="px-3 py-2.5">Equipment</th>
                                                    <th class="px-3 py-2.5">Destination</th>
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
                                                        <td class="px-3 py-2.5">
                                                            <div class="font-medium text-slate-800" x-text="item.name"></div>
                                                            <div
                                                                class="mt-0.5 text-[11px] text-slate-500"
                                                                x-show="item.unique"
                                                                x-text="item.unique"
                                                            ></div>
                                                        </td>
                                                        <td class="px-3 py-2.5">
                                                            <select
                                                                class="h-9 w-full min-w-[12rem] rounded-md border border-slate-200 px-2 text-sm"
                                                                :value="transferTargets[item.id] || ''"
                                                                @change="onCustomTransferTargetChange(item.id, $event.target.value)"
                                                            >
                                                                <option value="">Keep here / skip</option>
                                                                <template x-for="dest in transferDestRooms" :key="'opt-room-' + item.id + '-' + dest.id">
                                                                    <template x-for="dept in (dest.departments || [])" :key="'opt-dept-' + item.id + '-' + dest.id + '-' + dept.id">
                                                                        <option
                                                                            :value="encodeTransferTarget(dest.id, dept.id)"
                                                                            x-text="dest.name + ' · ' + dept.name"
                                                                        ></option>
                                                                    </template>
                                                                </template>
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
                                                ? 'All equipment in this room will move to the selected room and department.'
                                                : (transferMode === 'custom'
                                                    ? 'Only items with a destination will be moved. Unassigned items stay here.'
                                                    : 'The selected equipment will move to the chosen room and department.')
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
                                        <span x-text="roomSaving ? 'Transferringâ€¦' : (transferMode === 'single' ? 'Transfer Asset' : 'Transfer selected')"></span>
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
                        filterOpen: false,
                        filterOptions: [
                            { value: 'all', label: 'All conditions' },
                            { value: 'good', label: 'Good' },
                            { value: 'maintenance', label: 'Under Maintenance' },
                            { value: 'defective', label: 'Defective' },
                            { value: 'damaged', label: 'Damaged' },
                            { value: 'disposed', label: 'Disposed' },
                        ],
                        filterLabel() {
                            return this.filterOptions.find((option) => option.value === this.filter)?.label || 'All conditions';
                        },
                        setFilter(value) {
                            this.filter = value;
                            this.filterOpen = false;
                        },
                    }"
                    class="space-y-5"
                >
                    <!-- SEARCH & FILTER -->
                    <div class="space-y-3">
                        <div class="flex items-end justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[11px] font-medium tracking-[0.14em] text-slate-400">Equipments</p>
                                <p class="mt-1 text-[15px] font-semibold tracking-tight text-slate-950">Equipment list</p>
                            </div>

                            <div
                                class="relative shrink-0"
                                @click.outside="filterOpen = false"
                            >
                                <button
                                    type="button"
                                    @click="filterOpen = !filterOpen; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-50 hover:text-slate-700"
                                    :class="filter !== 'all' ? 'bg-[#005EA6]/10 text-[#005EA6] hover:bg-[#005EA6]/15 hover:text-[#005EA6]' : ''"
                                    :aria-label="'Filter by condition: ' + filterLabel()"
                                    :title="filterLabel()"
                                >
                                    <i data-lucide="list-filter" class="h-4 w-4"></i>
                                </button>

                                <div
                                    x-show="filterOpen"
                                    x-cloak
                                    x-transition.origin.top.right
                                    class="absolute right-0 top-full z-50 mt-1.5 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl shadow-slate-950/10"
                                >
                                    <p class="px-3.5 pb-1 pt-2 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Condition</p>
                                    <template x-for="option in filterOptions" :key="'eq-filter-' + option.value">
                                        <button
                                            type="button"
                                            @click="setFilter(option.value)"
                                            class="flex w-full items-center justify-between gap-3 px-3.5 py-2.5 text-left text-sm transition hover:bg-slate-50"
                                            :class="filter === option.value ? 'font-semibold text-slate-950' : 'text-slate-600'"
                                        >
                                            <span x-text="option.label"></span>
                                            <i
                                                data-lucide="check"
                                                class="h-3.5 w-3.5 text-[#005EA6]"
                                                x-show="filter === option.value"
                                            ></i>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="relative w-full">
                            <i
                                data-lucide="search"
                                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            ></i>
                            <input
                                x-model="search"
                                type="search"
                                placeholder="Search equipment…"
                                class="h-10 w-full rounded-full border border-slate-200 bg-white py-2 pl-10 pr-4 text-sm text-slate-900 outline-none placeholder:text-slate-400 transition focus:border-[#005EA6] focus:ring-2 focus:ring-blue-100"
                            />
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
                                    (filter === 'defective' &&
                                        (item.condition || '') === 'Defective') ||
                                    (filter === 'damaged' &&
                                        (item.condition || '') === 'Damaged') ||
                                    (filter === 'disposed' &&
                                        (item.condition || '') === 'Disposed'))
                            "
                            class="border-b border-slate-100 py-3.5 first:pt-0 last:border-b-0"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="h-1.5 w-1.5 shrink-0 rounded-full"
                                            :class="
                                                (item.condition || '') === 'Good'
                                                    ? 'bg-emerald-500'
                                                    : (item.condition || '') === 'Under Maintenance'
                                                        ? 'bg-amber-400'
                                                        : (item.condition || '') === 'Damaged'
                                                            ? 'bg-rose-500'
                                                            : (item.condition || '') === 'Disposed'
                                                                ? 'bg-slate-400'
                                                                : 'bg-slate-300'
                                            "
                                        ></span>
                                        <h3
                                            class="truncate text-[13px] font-semibold tracking-tight text-slate-950"
                                            x-text="item.name"
                                        ></h3>
                                    </div>
                                    <p
                                        class="mt-1 truncate text-[12px] text-slate-400"
                                        x-text="item.category || 'Uncategorized'"
                                    ></p>
                                    <p
                                        class="mt-1 truncate text-[12px] text-slate-500"
                                        x-text="item.location || item.placement_zone || 'No location'"
                                    ></p>
                                </div>

                                <div class="flex shrink-0 flex-col items-end gap-1">
                                    <span
                                        class="text-[12px] font-medium"
                                        :class="
                                            (item.condition || '') === 'Good'
                                                ? 'text-emerald-600'
                                                : (item.condition || '') === 'Under Maintenance'
                                                    ? 'text-amber-600'
                                                    : (item.condition || '') === 'Damaged'
                                                        ? 'text-rose-600'
                                                        : (item.condition || '') === 'Disposed'
                                                            ? 'text-slate-500'
                                                            : 'text-slate-400'
                                        "
                                        x-text="item.condition || 'Unknown'"
                                    ></span>
                                    <template x-if="item.quantity">
                                        <span class="text-[11px] tabular-nums text-slate-400">
                                            Qty <span x-text="item.quantity"></span>
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
                            $defective = $item->equipment_condition_status === "Defective";
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

                                            filter === 'defective'

                                            &&

                                            '{{ $item->equipment_condition_status }}'

                                            === 'Defective'

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
                            class="relative overflow-visible border-b border-slate-100 py-3.5 first:pt-0 last:border-b-0"
                        >
                            <!-- Equipment Alpine Component -->
                            <div
                                x-data="equipmentCard(

                                    {{ $item->equipment_id }},

                                    {{ $room->room_id }}

                                )"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="h-1.5 w-1.5 shrink-0 rounded-full
                                                {{
                                                    $healthy
                                                        ? 'bg-emerald-500'
                                                        : ($maintenance
                                                            ? 'bg-amber-400'
                                                            : ($defective
                                                                ? 'bg-orange-500'
                                                                : ($damaged
                                                                    ? 'bg-rose-500'
                                                                    : ($disposed
                                                                        ? 'bg-slate-400'
                                                                        : 'bg-slate-300'))))
                                                }}"
                                            ></span>
                                            <h3 class="truncate text-[13px] font-semibold tracking-tight text-slate-950">
                                                {{ $item->equipment_name }}
                                            </h3>
                                        </div>

                                        @php
                                            $itemUniqueness = \App\Support\EquipmentIdentity::label($item) ?: 'No asset tag / serial';
                                        @endphp
                                        <div class="group/uniqueness relative mt-1 min-w-0">
                                            <p class="truncate font-mono text-[11px] text-slate-500 cursor-default">
                                                {{ $itemUniqueness }}
                                            </p>
                                            <div
                                                role="tooltip"
                                                class="
                                                    pointer-events-none
                                                    absolute
                                                    left-0
                                                    top-full
                                                    z-[60]
                                                    mt-1.5
                                                    hidden
                                                    max-w-[min(22rem,calc(100vw-3rem))]
                                                    rounded-lg
                                                    bg-[#0f172a]
                                                    px-3
                                                    py-2.5
                                                    text-[12px]
                                                    font-medium
                                                    leading-snug
                                                    text-slate-100
                                                    shadow-[0_12px_28px_rgba(15,23,42,0.35)]
                                                    group-hover/uniqueness:block
                                                "
                                            >
                                                {{ $itemUniqueness }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <span
                                            class="text-[12px] font-medium
                                            {{
                                                $healthy
                                                    ? 'text-emerald-600'
                                                    : ($maintenance
                                                        ? 'text-amber-600'
                                                        : ($defective
                                                            ? 'text-orange-600'
                                                            : ($damaged
                                                                ? 'text-rose-600'
                                                                : ($disposed
                                                                    ? 'text-slate-500'
                                                                    : 'text-slate-400'))))
                                            }}"
                                        >
                                            {{ $item->equipment_condition_status }}
                                        </span>

                                        <div
                                            x-data="{ menu: false }"
                                            :class="menu
                                                ? 'relative z-40'
                                                : 'relative z-10'"
                                            class="relative"
                                        >
                                            <div class="relative z-30">
                                                <button
                                                    type="button"
                                                    @click="menu = !menu"
                                                    class="rounded-md p-1.5 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700"
                                                    aria-label="Equipment actions"
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
                                                            transferRoomLabel = '';
                                                            transferRoomSearch = '';
                                                            transferRoomOpen = false;
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

                                <p class="mt-1.5 text-[12px] text-slate-400">
                                    {{
                                        $item->equipment_placement_zone ?:
                                            ($item->equipment_current_location ?:
                                                'Placement not plotted')
                                    }}
                                </p>

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

                                    <div class="space-y-5 p-5">
                                        @php
                                            $detailNa = static function ($value, string $fallback = 'â€”') {
                                                if ($value === null || $value === '') {
                                                    return $fallback;
                                                }

                                                return $value;
                                            };
                                            $detailDate = static function ($value) use ($detailNa) {
                                                if ($value === null || $value === '') {
                                                    return $detailNa(null);
                                                }

                                                try {
                                                    return \Illuminate\Support\Carbon::parse($value)->format('M j, Y');
                                                } catch (\Throwable $e) {
                                                    return (string) $value;
                                                }
                                            };
                                            $detailCategory = optional($item->category)->equipment_category_name;
                                            $detailSupplier = null;
                                            if ($item->supplier) {
                                                $detailSupplier = optional($item->supplier->physical)->company_name
                                                    ?? optional($item->supplier->online)->shop_name;
                                            }
                                            $detailPlacement = $item->equipment_placement_zone
                                                ?: $item->equipment_current_location;
                                            $detailHasQr = filled($item->equipment_qr_code ?? null);
                                            $detailHasImage = filled($item->equipment_image ?? null);
                                        @endphp

                                        {{-- Identity --}}
                                        <div>
                                            <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Identity</p>
                                            <div class="grid grid-cols-2 gap-x-5 gap-y-4">
                                                <div class="col-span-2 min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Equipment name</p>
                                                    <p class="mt-1 break-words text-sm font-medium text-slate-800">{{ $detailNa($item->equipment_name) }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Classification</p>
                                                    <p class="mt-1 truncate text-sm font-medium text-slate-800">{{ $detailNa($detailCategory) }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Tracking</p>
                                                    <p class="mt-1 truncate text-sm font-medium text-slate-800">{{ $detailNa($item->equipment_tracking_mode ?: 'Individual') }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Brand</p>
                                                    <p class="mt-1 truncate text-sm font-medium text-slate-800">{{ $detailNa($item->equipment_brand_name) }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Model</p>
                                                    <p class="mt-1 truncate text-sm font-medium text-slate-800">{{ $detailNa($item->equipment_model) }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Asset tag</p>
                                                    <p class="mt-1 break-all font-mono text-sm font-medium text-slate-800">{{ $detailNa($item->equipment_asset_tag, 'Not Assigned') }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Serial number</p>
                                                    <p class="mt-1 break-all text-sm font-medium text-slate-800">{{ $detailNa($item->equipment_serial_number, 'Unavailable') }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Quantity</p>
                                                    <p class="mt-1 text-sm font-medium text-slate-800">{{ $detailNa($item->equipment_quantity ?? 1) }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Borrowable</p>
                                                    <p class="mt-1 text-sm font-medium text-slate-800">{{ !empty($item->equipment_is_borrowable) ? 'Yes' : 'No' }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Status --}}
                                        <div class="border-t border-slate-100 pt-5">
                                            <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Status</p>
                                            <div class="grid grid-cols-2 gap-x-5 gap-y-4">
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Condition</p>
                                                    <p class="mt-1 truncate text-sm font-medium text-slate-800">{{ $detailNa($item->equipment_condition_status) }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Inventory status</p>
                                                    <p class="mt-1 truncate text-sm font-medium text-slate-800">{{ $detailNa($item->equipment_inventory_status) }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Location --}}
                                        <div class="border-t border-slate-100 pt-5">
                                            <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Location</p>
                                            <div class="grid grid-cols-2 gap-x-5 gap-y-4">
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Placement zone</p>
                                                    <p class="mt-1 truncate text-sm font-medium text-slate-800">{{ $detailNa($detailPlacement, 'Not plotted') }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Current location</p>
                                                    <p class="mt-1 truncate text-sm font-medium text-slate-800">{{ $detailNa($item->equipment_current_location, 'â€”') }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Layout position</p>
                                                    <p class="mt-1 text-sm font-medium text-slate-800">
                                                        @if ($item->equipment_position_x !== null || $item->equipment_position_y !== null)
                                                            X {{ (int) ($item->equipment_position_x ?? 0) }}% · Y {{ (int) ($item->equipment_position_y ?? 0) }}%
                                                        @else
                                                            â€”
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Room</p>
                                                    <p class="mt-1 truncate text-sm font-medium text-slate-800">{{ $detailNa($room->room_name) }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Acquisition --}}
                                        <div class="border-t border-slate-100 pt-5">
                                            <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Acquisition</p>
                                            <div class="grid grid-cols-2 gap-x-5 gap-y-4">
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Purchase date</p>
                                                    <p class="mt-1 text-sm font-medium text-slate-800">{{ $detailDate($item->equipment_purchase_date) }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Acquired date</p>
                                                    <p class="mt-1 text-sm font-medium text-slate-800">{{ $detailDate($item->equipment_acquired_date) }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Purchase cost</p>
                                                    <p class="mt-1 text-sm font-medium text-slate-800">
                                                        @if ($item->equipment_purchase_cost !== null && $item->equipment_purchase_cost !== '')
                                                            â‚±{{ number_format((float) $item->equipment_purchase_cost, 2) }}
                                                        @else
                                                            â€”
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Warranty</p>
                                                    <p class="mt-1 text-sm font-medium text-slate-800">{{ $detailDate($item->equipment_warranty_expiration) }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Useful life</p>
                                                    <p class="mt-1 text-sm font-medium text-slate-800">
                                                        @if (!empty($item->equipment_useful_life_years))
                                                            {{ (int) $item->equipment_useful_life_years }} years
                                                        @else
                                                            â€” <span class="text-[10px] font-normal text-slate-400">(default 5)</span>
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Supplier</p>
                                                    <p class="mt-1 truncate text-sm font-medium text-slate-800">{{ $detailNa($detailSupplier, 'Not Assigned') }}</p>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Record created</p>
                                                    <p class="mt-1 text-sm font-medium text-slate-800">{{ $detailDate($item->equipment_created_at) }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Photo --}}
                                        @if ($detailHasImage)
                                            <div class="border-t border-slate-100 pt-5">
                                                <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Photo</p>
                                                <img
                                                    src="{{ asset('storage/'.$item->equipment_image) }}"
                                                    alt="{{ $item->equipment_name }}"
                                                    class="h-28 w-full rounded-lg object-cover ring-1 ring-slate-200"
                                                >
                                            </div>
                                        @endif

                                        {{-- QR --}}
                                        <div class="border-t border-slate-100 pt-5">
                                            <div class="flex items-center justify-between gap-4">
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <i data-lucide="qr-code" class="h-4 w-4 text-slate-400"></i>
                                                        <p class="text-sm font-medium text-slate-800">QR Code</p>
                                                    </div>
                                                    @if ($detailHasQr)
                                                        <p class="mt-1 break-all font-mono text-[11px] text-slate-500">{{ $item->equipment_qr_code }}</p>
                                                    @else
                                                        <p class="mt-1 max-w-[220px] text-xs leading-5 text-slate-500">
                                                            No QR code assigned to this equipment yet.
                                                        </p>
                                                    @endif
                                                </div>

                                                @if ($detailHasQr)
                                                    <img
                                                        src="{{ url('/maintenance/equipment/qr-image/'.$item->equipment_qr_code) }}"
                                                        alt="QR for {{ $item->equipment_name }}"
                                                        class="h-20 w-20 shrink-0 rounded-lg border border-slate-200 bg-white object-contain p-1"
                                                    >
                                                @else
                                                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50">
                                                        <i data-lucide="qr-code" class="h-7 w-7 text-slate-300"></i>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div
                                    x-show="panel === 'edit'"
                                    x-cloak
                                    x-transition.opacity
                                    @click.self="panel = ''"
                                    class="fixed inset-0 z-[10000] flex items-center justify-center bg-[#0b1220]/70 p-4 sm:p-6"
                                >
                                    <div
                                        class="flex max-h-[calc(90dvh-2rem)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                                        @click.stop
                                    >

                                    <!-- ===================================================== -->
                                    <!-- PANEL HEADER -->
                                    <!-- ===================================================== -->

                                    <div
                                        class="
                                            flex
                                            shrink-0
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

                                    <div class="eq-modal-scroll min-h-0 flex-1 space-y-5 overflow-y-auto p-5">


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
                                                Equipment Name <span class="text-red-500">*</span>
                                            </label>


                                            <input
                                                x-model="form.name"
                                                type="text"
                                                maxlength="{{ \App\Support\InputLimits::NAME }}"
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
                                                Classification
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
                                                    Select Classification
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
                                                    Quantity <span class="text-red-500">*</span>
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
                                                Condition <span class="text-red-500">*</span>
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

                                                <option>Good</option>
                                                <option>Under Maintenance</option>
                                                <option>Defective</option>
                                                <option>Damaged</option>
                                                <option>Disposed</option>

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

                                </div>

                                <div
                                    x-show="panel === 'transfer'"
                                    x-cloak
                                    x-transition.opacity
                                    @click.self="panel = ''"
                                    class="fixed inset-0 z-[10000] flex items-center justify-center bg-[#0b1220]/70 p-4 sm:p-6"
                                >
                                    <div
                                        class="flex max-h-[calc(90dvh-2rem)] w-full max-w-md flex-col overflow-visible rounded-2xl border border-slate-200 bg-white shadow-2xl"
                                        @click.stop
                                    >

                                    <!-- ===================================================== -->
                                    <!-- PANEL HEADER -->
                                    <!-- ===================================================== -->

                                    <div
                                        class="
                                            flex
                                            shrink-0
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

                                    <div class="relative z-10 p-5">


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
                                                Destination Room <span class="text-red-500">*</span>
                                            </label>


                                            <div
                                                class="relative"
                                                @click.outside="transferRoomOpen = false"
                                            >

                                                <div
                                                    class="
                                                        pointer-events-none
                                                        absolute
                                                        left-3
                                                        top-1/2
                                                        z-10
                                                        -translate-y-1/2
                                                        text-slate-400
                                                    "
                                                >
                                                    <i
                                                        data-lucide="map-pin"
                                                        class="h-3.5 w-3.5"
                                                    ></i>
                                                </div>

                                                <button
                                                    type="button"
                                                    @click="transferRoomOpen = !transferRoomOpen; if (transferRoomOpen) { transferRoomSearch = ''; $nextTick(() => { if (window.lucide) lucide.createIcons(); $refs.transferRoomSearch?.focus(); }); }"
                                                    class="
                                                        flex
                                                        h-10
                                                        w-full
                                                        items-center
                                                        rounded-lg
                                                        border
                                                        border-slate-200
                                                        bg-white
                                                        pl-8
                                                        pr-10
                                                        text-left
                                                        text-sm
                                                        text-slate-700
                                                        outline-none
                                                        transition

                                                        hover:border-slate-300

                                                        focus:border-[#005EA6]
                                                        focus:ring-2
                                                        focus:ring-blue-100
                                                    "
                                                    :class="transferRoomOpen ? 'border-[#005EA6] ring-2 ring-blue-100' : ''"
                                                >
                                                    <span
                                                        class="truncate"
                                                        :class="transferRoomLabel ? 'text-slate-800' : 'text-slate-400'"
                                                        x-text="transferRoomLabel || 'Select destination room'"
                                                    ></span>

                                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                                        <i data-lucide="chevron-down" class="h-4 w-4 transition" :class="transferRoomOpen ? 'rotate-180' : ''"></i>
                                                    </span>
                                                </button>

                                                <div
                                                    x-show="transferRoomOpen"
                                                    x-cloak
                                                    x-transition.origin.top
                                                    class="absolute left-0 right-0 top-full z-[120] mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl"
                                                >
                                                    <div class="border-b border-slate-100 p-2">
                                                        <div class="relative">
                                                            <i
                                                                data-lucide="search"
                                                                class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
                                                            ></i>
                                                            <input
                                                                x-ref="transferRoomSearch"
                                                                type="text"
                                                                x-model="transferRoomSearch"
                                                                @keydown.escape.stop="transferRoomOpen = false"
                                                                placeholder="Search rooms..."
                                                                class="
                                                                    h-9
                                                                    w-full
                                                                    rounded-lg
                                                                    border
                                                                    border-slate-200
                                                                    bg-slate-50
                                                                    pl-8
                                                                    pr-3
                                                                    text-sm
                                                                    text-slate-800
                                                                    outline-none
                                                                    transition

                                                                    placeholder:text-slate-400

                                                                    focus:border-[#005EA6]
                                                                    focus:bg-white
                                                                    focus:ring-2
                                                                    focus:ring-blue-100
                                                                "
                                                            >
                                                        </div>
                                                    </div>

                                                    <div class="max-h-48 overflow-y-auto overscroll-contain py-1">
                                                        <button
                                                            type="button"
                                                            @click="transferRoom = ''; transferRoomLabel = ''; transferRoomOpen = false"
                                                            class="flex w-full px-3 py-2 text-left text-sm text-slate-400 transition hover:bg-slate-50"
                                                        >
                                                            Select destination room
                                                        </button>

                                                        @foreach ($transferDestinationRooms as $destination)
                                                            @if ((int) ($destination['id'] ?? 0) !== (int) $room->room_id)
                                                                @php
                                                                    $destinationDeptNames = collect($destination['departments'] ?? [])
                                                                        ->filter(fn ($d) => is_array($d) && (int) ($d['id'] ?? 0) > 0)
                                                                        ->map(fn ($d) => $d['name'] ?? null)
                                                                        ->filter()
                                                                        ->values()
                                                                        ->all();
                                                                    $destinationLabel = (string) ($destination['name'] ?? 'Room')
                                                                        .($destinationDeptNames !== []
                                                                            ? ' · '.implode(', ', $destinationDeptNames)
                                                                            : ' · No department');
                                                                @endphp
                                                                <button
                                                                    type="button"
                                                                    x-show="matchesTransferRoomSearch(@js($destinationLabel))"
                                                                    @click="selectTransferRoom(@js((string) $destination['id']), @js($destinationLabel))"
                                                                    class="flex w-full px-3 py-2 text-left text-sm transition hover:bg-blue-50 hover:text-[#005EA6]"
                                                                    :class="String(transferRoom) === @js((string) $destination['id']) ? 'bg-blue-50 font-medium text-[#005EA6]' : 'text-slate-700'"
                                                                >
                                                                    <span class="truncate">{{ $destinationLabel }}</span>
                                                                </button>
                                                            @endif
                                                        @endforeach

                                                        <p
                                                            x-show="transferRoomSearch.trim() !== '' && !hasTransferRoomMatches()"
                                                            class="px-3 py-3 text-center text-xs text-slate-400"
                                                        >
                                                            No rooms match your search.
                                                        </p>
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

                                </div>

                                <div
                                    x-show="panel === 'archive'"
                                    x-cloak
                                    x-transition.opacity
                                    @click.self="panel = ''"
                                    class="fixed inset-0 z-[10000] flex items-center justify-center bg-[#0b1220]/70 p-4 sm:p-6"
                                >
                                    <div
                                        class="flex max-h-[calc(90dvh-2rem)] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                                        @click.stop
                                    >

                                    <!-- ===================================================== -->
                                    <!-- PANEL HEADER -->
                                    <!-- ===================================================== -->

                                    <div
                                        class="
                                            flex
                                            shrink-0
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

                                    <div class="eq-modal-scroll min-h-0 flex-1 overflow-y-auto p-5">


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
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-5
                                                    py-2
                                                    text-sm
                                                    font-semibold
                                                    text-[#007a3f]
                                                    transition

                                                    hover:bg-slate-50
                                                "
                                            >
                                                Archive
                                            </button>

                                        </div>

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
                                Common problems
                            </h3>

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
                                            @if (! empty($problem->last_reported_at))
                                                Last reported {{ \Illuminate\Support\Carbon::parse($problem->last_reported_at)->format('M j, Y') }}
                                            @else
                                                Common problem in this room
                                            @endif
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
                                        {{ $problem->occurrences }}Ã—
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
                                        No common problems have been recorded for
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
                    <!-- ROOM INSPECTIONS -->
                    <!-- ===================================================== -->

                    <div class="mb-8">

                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Room inspections
                                </h3>
                                <p class="mt-0.5 text-[11px] text-slate-400">
                                    Entire room or by department
                                </p>
                            </div>
                            <div
                                class="
                                    flex
                                    h-8
                                    w-8
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-amber-50
                                    text-amber-600
                                "
                            >
                                <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                            </div>
                        </div>

                        <div class="mt-4 space-y-0 border-y border-slate-200">
                            @php
                                $roomInspections = $room->maintenance_schedules
                                    ?? data_get($room->monitoring, 'room_information.maintenance_schedules', []);
                            @endphp
                            @forelse ($roomInspections as $inspection)
                                @php
                                    $insp = is_array($inspection) ? $inspection : (array) $inspection;
                                    $inspDate = $insp['next_date'] ?? null;
                                    $inspScope = $insp['scope'] ?? 'Entire room';
                                    $inspStatus = $insp['status'] ?? '';
                                    $inspTitle = $insp['title'] ?? 'Room inspection';
                                @endphp
                                <article class="flex items-start gap-3 border-b border-slate-100 py-4 first:pt-0 last:border-b-0 last:pb-0">
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
                                            border-amber-200
                                            bg-amber-50
                                        "
                                    >
                                        @if ($inspDate)
                                            <span class="text-base font-semibold leading-none text-slate-900">
                                                {{ \Carbon\Carbon::parse($inspDate)->format('d') }}
                                            </span>
                                            <span class="mt-0.5 text-[10px] font-medium uppercase tracking-wide text-slate-500">
                                                {{ \Carbon\Carbon::parse($inspDate)->format('M') }}
                                            </span>
                                        @else
                                            <span class="text-[10px] font-medium text-slate-500">â€”</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-slate-900">{{ $inspTitle }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            Scope:
                                            <span class="font-medium text-slate-700">{{ $inspScope }}</span>
                                        </p>
                                        @if ($inspStatus !== '')
                                            <span
                                                class="mt-1.5 inline-flex rounded-md px-2 py-0.5 text-[10px] font-semibold
                                                    {{ strtolower($inspStatus) === 'overdue' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600' }}"
                                            >
                                                {{ $inspStatus }}
                                            </span>
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <div class="py-6 text-center">
                                    <p class="text-sm font-medium text-slate-700">No active room inspections</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        No Active or Overdue room inspection schedules for this period.
                                    </p>
                                </div>
                            @endforelse
                        </div>

                    </div>

                    <!-- ===================================================== -->
                    <!-- EQUIPMENT MAINTENANCE -->
                    <!-- ===================================================== -->

                    <div>

                        <!-- SECTION HEADER -->

                        <div class="flex items-center justify-between">

                            <div>

                                <h3 class="text-sm font-semibold text-slate-900">
                                    Equipment maintenance
                                </h3>
                                <p class="mt-0.5 text-[11px] text-slate-400">
                                    By equipment uniqueness
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



                                        <!-- EQUIPMENT + UNIQUENESS -->
                                        @php
                                            $scheduleUniqueness = \App\Support\EquipmentIdentity::label($schedule);
                                        @endphp

                                        <div class="mt-1.5 min-w-0">
                                            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                                <i
                                                    data-lucide="wrench"
                                                    class="h-3 w-3 shrink-0 text-slate-400"
                                                ></i>
                                                <span class="truncate">
                                                    {{ $schedule->equipment_name }}
                                                </span>
                                            </div>
                                            @php
                                                $scheduleUniquenessText = $scheduleUniqueness !== '' ? $scheduleUniqueness : 'No asset tag / serial';
                                            @endphp
                                            <div class="group/uniqueness relative mt-1 min-w-0">
                                                <p class="truncate font-mono text-[11px] text-slate-600 cursor-default">
                                                    {{ $scheduleUniquenessText }}
                                                </p>
                                                <div
                                                    role="tooltip"
                                                    class="pointer-events-none absolute left-0 top-full z-[60] mt-1.5 hidden max-w-[min(22rem,calc(100vw-3rem))] rounded-lg bg-[#0f172a] px-3 py-2.5 text-[12px] font-medium leading-snug text-slate-100 shadow-[0_12px_28px_rgba(15,23,42,0.35)] group-hover/uniqueness:block"
                                                >
                                                    {{ $scheduleUniquenessText }}
                                                </div>
                                            </div>
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

                            @forelse (($room->monitoring['history'] ?? []) as $history)

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
