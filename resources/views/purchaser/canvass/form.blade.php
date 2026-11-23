@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', $mode === 'edit' ? 'Edit Purchase Canvass' : 'Purchase Canvass')
@section('page-subtitle', 'Submit one request with multiple items. Each item has its own supplier and brand quotations, and School Admin approves each item independently.')

@section('content')
@php
    $routePrefix = $pp ?? 'purchaser';
    $formAction = $mode === 'edit' && $canvass
        ? route($routePrefix.'.canvass.update', $canvass->canvass_id)
        : route($routePrefix.'.canvass.store');

    $bootData = [
        'itemsCatalog' => $items->map(fn ($i) => ['id' => (string) $i->item_id, 'name' => (string) $i->item_name])->values()->all(),
        'uoms' => $uoms->map(fn ($u) => ['id' => (string) $u->uom_id, 'name' => (string) $u->uom_name])->values()->all(),
        'brands' => $brands->map(fn ($b) => ['id' => (string) $b->brand_id, 'name' => (string) $b->brand_name])->values()->all(),
        'suppliers' => $suppliers->map(fn ($s) => [
            'id' => (string) $s->supplier_id,
            'name' => (string) ($s->option_label ?? $s->display_name),
            'baseName' => (string) $s->display_name,
            'type' => (string) ($s->supplier_store_type ?? ''),
            'blacklisted' => (bool) ($s->supplier_is_blacklisted ?? false),
        ])->values()->all(),
        'supplierWarnings' => $supplierWarnings,
        'initial' => $initialState,
        'existingAttachmentCount' => $attachments->count(),
        'existingAttachmentBytes' => (int) $attachments->sum('canvass_attachment_size'),
        'purchaserName' => (string) (Auth::user()->user_full_name ?? 'Purchaser'),
        'todayDisplay' => now()->format('m/d/Y'),
        'quickStoreUrls' => [
            'item' => route($routePrefix.'.items.quick-store'),
            'uom' => route($routePrefix.'.uom.quick-store'),
            'brand' => route($routePrefix.'.brands.quick-store'),
        ],
    ];
@endphp

<div
    x-data="canvassBuilder(@js($bootData))"
    x-init="init()"
    class="mx-auto max-w-5xl space-y-5 pb-10"
>
    {{-- Top back + mode pill --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a
                href="{{ route($routePrefix.'.canvass.index') }}"
                @click="handleBackClick($event, '{{ route($routePrefix.'.canvass.index') }}')"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                <span>Back to Canvasses</span>
            </a>
            @if ($mode === 'edit' && $canvass)
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    Editing {{ \App\Support\CanvassWorkflow::displayNumber($canvass) }}
                </span>
            @endif
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border px-5 py-4 text-sm" style="background: #fef2f2; border-color: #fecaca; color: #991b1b;">
            <p class="font-semibold" style="color: #991b1b;">Please fix the following before submitting:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs font-medium" style="color: #dc2626;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Client-side validation error banner (keeps all fields and files intact without reloading) --}}
    <div
        x-cloak
        x-show="clientErrors.length > 0"
        x-ref="clientErrorBanner"
        class="rounded-2xl border px-5 py-4 text-sm"
        style="background: #fef2f2; border-color: #fecaca; color: #991b1b;"
    >
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="font-semibold" style="color: #991b1b;">Please fix the following before submitting:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-xs font-medium" style="color: #dc2626;">
                    <template x-for="(err, eIdx) in clientErrors" :key="eIdx">
                        <li x-text="err"></li>
                    </template>
                </ul>
            </div>
            <button
                type="button"
                @click="clientErrors = []"
                class="shrink-0 rounded-lg px-2 py-1 text-xs font-semibold hover:opacity-80"
                style="color: #991b1b;"
                title="Dismiss"
            >
                ×
            </button>
        </div>
    </div>

    @if ($mode === 'edit' && $canvass && $canvass->canvass_status === \App\Support\CanvassWorkflow::STATUS_RETURNED)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <p class="font-semibold">Returned by School Administrator for revision</p>
            @if (! empty($canvass->canvass_review_note))
                <p class="mt-1 text-xs">{{ $canvass->canvass_review_note }}</p>
            @endif
        </div>
    @endif

    <form
        method="POST"
        action="{{ $formAction }}"
        enctype="multipart/form-data"
        x-ref="canvassForm"
        @submit="onFormSubmit($event)"
        class="space-y-5"
    >
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif
        <input type="hidden" name="save_action" x-model="saveAction">
        <input type="hidden" name="canvass_purchaser_signature" x-model="signatureData">
        <input type="hidden" name="canvass_procurement_request_id" x-model="procurementRequestId">

        {{-- Procurement Urgency Radio Cards --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <label class="mb-2.5 block text-xs font-semibold text-slate-800">
                Procurement urgency <span style="color: #dc2626;">*</span>
            </label>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label
                    class="flex cursor-pointer items-center gap-3.5 rounded-xl border px-4 py-3 transition"
                    :style="urgency === 'Non-Urgent'
                        ? 'border-color: #34d399; background-color: #ecfdf5;'
                        : 'border-color: #e2e8f0; background-color: #f8fafc;'"
                >
                    <input
                        type="radio"
                        name="canvass_urgency"
                        value="Non-Urgent"
                        x-model="urgency"
                        class="sr-only"
                    >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                        style="background-color: #f1f5f9; color: #475569;"
                    >
                        <i data-lucide="clock" class="h-4 w-4"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-slate-900">Non-Urgent</span>
                        <span class="block text-xs text-slate-500">Standard procurement timeline</span>
                    </span>
                </label>

                <label
                    class="flex cursor-pointer items-center gap-3.5 rounded-xl border px-4 py-3 transition"
                    :style="urgency === 'Urgent'
                        ? 'border-color: #f87171; background-color: #fef2f2;'
                        : 'border-color: #e2e8f0; background-color: #f8fafc;'"
                >
                    <input
                        type="radio"
                        name="canvass_urgency"
                        value="Urgent"
                        x-model="urgency"
                        class="sr-only"
                    >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                        style="background-color: #ffe4e6; color: #e11d48;"
                    >
                        <i data-lucide="triangle-alert" class="h-4 w-4"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-slate-900">Urgent</span>
                        <span class="block text-xs text-slate-500">Needs priority purchasing attention</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button
                type="button"
                @click="addItem()"
                :disabled="items.length >= 30"
                class="inline-flex items-center gap-1.5 rounded-xl border border-[#0025cc] bg-white px-4 py-2 text-xs font-semibold text-[#0025cc] shadow-sm transition hover:!bg-[#0025cc] hover:!text-white disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span>+ Add another item</span>
            </button>
        </div>

        {{-- ITEM CARDS --}}
        <template x-for="(item, iIdx) in items" :key="item._uid">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                {{-- Blue Item Header Bar (matches Image 1) --}}
                <div class="flex items-center justify-between rounded-t-2xl bg-[#0d6efd] px-5 py-3 text-white">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="text-sm font-semibold" x-text="'Item ' + (iIdx + 1)"></span>
                        <span
                            x-show="itemDisplayName(item)"
                            class="rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-medium"
                            x-text="itemDisplayName(item)"
                        ></span>
                        <span
                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                            :style="isItemReady(item) ? 'background:#16a34a;color:#fff;' : 'background:rgba(255,255,255,0.18);color:#fff;'"
                            x-text="isItemReady(item) ? '✓ Ready' : (validSupplierCount(item) + ' / ' + item.suppliers.length + ' suppliers ready')"
                        ></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="item.collapsed = !item.collapsed"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-white/90 transition hover:bg-white/15"
                            :title="item.collapsed ? 'Expand item' : 'Collapse item'"
                        >
                            <span class="text-base font-bold leading-none" x-text="item.collapsed ? '+' : '−'"></span>
                        </button>
                        <button
                            type="button"
                            x-show="items.length > 1"
                            @click="removeItem(iIdx)"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-white/90 transition hover:bg-white/15"
                            title="Remove item"
                        >
                            <span class="text-base font-bold leading-none">×</span>
                        </button>
                    </div>
                </div>

                <div x-show="!item.collapsed" class="space-y-6 p-5">
                    {{-- Return note if item was returned --}}
                    <template x-if="item.review_note">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs text-amber-900">
                            <span class="font-semibold">School Admin note:</span>
                            <span x-text="item.review_note"></span>
                        </div>
                    </template>

                    {{-- STEP 1: REQUESTED ITEM --}}
                    <div>
                        <div class="mb-3 flex items-center gap-2.5">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#0d6efd] text-xs font-bold text-white">1</span>
                            <h3 class="text-sm font-semibold text-slate-900">Requested item</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            {{-- ITEM SEARCHABLE + QUICK-ADD DROPDOWN --}}
                            <div
                                class="relative"
                                x-data="{ open: false, q: '', newName: '', busy: false, err: '' }"
                                @click.outside="open = false"
                                @keydown.escape.window="open = false"
                            >
                                <label class="mb-1.5 block text-xs font-medium text-slate-600">Item</label>
                                <input type="hidden" :name="`items[${iIdx}][item_id]`" :value="item.item_id">
                                <input type="hidden" :name="`items[${iIdx}][item_name]`" :value="item.item_name">
                                <button
                                    type="button"
                                    @click="open = !open; if (open) $nextTick(() => $refs.itemSearch?.focus())"
                                    class="flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-3 py-2 text-left text-sm outline-none transition focus:border-blue-500"
                                    :class="item.item_id ? 'text-slate-900' : 'text-slate-400'"
                                >
                                    <span class="truncate" x-text="itemDisplayName(item) || 'Select an item'"></span>
                                    <svg class="ml-2 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                                </button>

                                <div
                                    x-cloak
                                    x-show="open"
                                    class="absolute left-0 right-0 z-40 mt-1.5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                >
                                    <div class="border-b border-slate-100 p-2.5">
                                        <input
                                            type="text"
                                            x-ref="itemSearch"
                                            x-model="q"
                                            placeholder="Search items..."
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-blue-500"
                                        >
                                    </div>
                                    <div class="max-h-52 overflow-y-auto py-1">
                                        <template x-for="cat in filterList(itemsCatalog, q)" :key="cat.id">
                                            <button
                                                type="button"
                                                @click="item.item_id = cat.id; item.item_name = cat.name; open = false; q = ''"
                                                class="flex w-full items-center justify-between px-3.5 py-2 text-left text-sm transition hover:bg-blue-50"
                                                :class="String(item.item_id) === String(cat.id) ? 'bg-blue-50 font-semibold text-blue-700' : 'text-slate-800'"
                                            >
                                                <span class="truncate" x-text="cat.name"></span>
                                                <span x-show="String(item.item_id) === String(cat.id)" class="text-xs text-blue-600">✓</span>
                                            </button>
                                        </template>
                                        <p x-show="filterList(itemsCatalog, q).length === 0" class="px-3.5 py-3 text-center text-xs text-slate-400">No matching items</p>
                                    </div>
                                    <div class="border-t border-slate-100 bg-slate-50/70 p-2.5">
                                        <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Add new item</p>
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="text"
                                                x-model="newName"
                                                @keydown.enter.prevent="quickAdd('item', newName, (r) => { item.item_id = r.id; item.item_name = r.name; newName = ''; q = ''; open = false; }, (e) => err = e, (b) => busy = b)"
                                                placeholder="New item name"
                                                class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 outline-none focus:border-blue-500"
                                            >
                                            <button
                                                type="button"
                                                :disabled="busy || !newName.trim()"
                                                @click="quickAdd('item', newName, (r) => { item.item_id = r.id; item.item_name = r.name; newName = ''; q = ''; open = false; }, (e) => err = e, (b) => busy = b)"
                                                class="shrink-0 rounded-xl bg-[#0025cc] px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-[#001db3] disabled:opacity-50"
                                            >
                                                <span x-text="busy ? '...' : 'Add'"></span>
                                            </button>
                                        </div>
                                        <p x-show="err" x-text="err" class="mt-1 text-[11px] font-medium text-rose-600"></p>
                                    </div>
                                </div>
                            </div>

                            {{-- UOM SEARCHABLE + QUICK-ADD DROPDOWN --}}
                            <div
                                class="relative"
                                x-data="{ open: false, q: '', newName: '', busy: false, err: '' }"
                                @click.outside="open = false"
                                @keydown.escape.window="open = false"
                            >
                                <label class="mb-1.5 block text-xs font-medium text-slate-600">UOM</label>
                                <input type="hidden" :name="`items[${iIdx}][uom_id]`" :value="item.uom_id">
                                <button
                                    type="button"
                                    @click="open = !open; if (open) $nextTick(() => $refs.uomSearch?.focus())"
                                    class="flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-3 py-2 text-left text-sm outline-none transition focus:border-blue-500"
                                    :class="item.uom_id ? 'text-slate-900' : 'text-slate-400'"
                                >
                                    <span class="truncate" x-text="item.uom_id ? uomName(item.uom_id) : 'Select unit'"></span>
                                    <svg class="ml-2 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                                </button>

                                <div
                                    x-cloak
                                    x-show="open"
                                    class="absolute left-0 right-0 z-40 mt-1.5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                >
                                    <div class="border-b border-slate-100 p-2.5">
                                        <input
                                            type="text"
                                            x-ref="uomSearch"
                                            x-model="q"
                                            placeholder="Search units..."
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-blue-500"
                                        >
                                    </div>
                                    <div class="max-h-52 overflow-y-auto py-1">
                                        <template x-for="u in filterList(uoms, q)" :key="u.id">
                                            <button
                                                type="button"
                                                @click="item.uom_id = u.id; syncDefaultOfferUoms(item); open = false; q = ''"
                                                class="flex w-full items-center justify-between px-3.5 py-2 text-left text-sm transition hover:bg-blue-50"
                                                :class="String(item.uom_id) === String(u.id) ? 'bg-blue-50 font-semibold text-blue-700' : 'text-slate-800'"
                                            >
                                                <span class="truncate" x-text="u.name"></span>
                                                <span x-show="String(item.uom_id) === String(u.id)" class="text-xs text-blue-600">✓</span>
                                            </button>
                                        </template>
                                        <p x-show="filterList(uoms, q).length === 0" class="px-3.5 py-3 text-center text-xs text-slate-400">No matching units</p>
                                    </div>
                                    <div class="border-t border-slate-100 bg-slate-50/70 p-2.5">
                                        <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Add new unit</p>
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="text"
                                                x-model="newName"
                                                @keydown.enter.prevent="quickAdd('uom', newName, (r) => { item.uom_id = r.id; syncDefaultOfferUoms(item); newName = ''; q = ''; open = false; }, (e) => err = e, (b) => busy = b)"
                                                placeholder="New unit name"
                                                class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 outline-none focus:border-blue-500"
                                            >
                                            <button
                                                type="button"
                                                :disabled="busy || !newName.trim()"
                                                @click="quickAdd('uom', newName, (r) => { item.uom_id = r.id; syncDefaultOfferUoms(item); newName = ''; q = ''; open = false; }, (e) => err = e, (b) => busy = b)"
                                                class="shrink-0 rounded-xl bg-[#0025cc] px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-[#001db3] disabled:opacity-50"
                                            >
                                                <span x-text="busy ? '...' : 'Add'"></span>
                                            </button>
                                        </div>
                                        <p x-show="err" x-text="err" class="mt-1 text-[11px] font-medium text-rose-600"></p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-600">Requested quantity</label>
                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    :name="`items[${iIdx}][quantity]`"
                                    x-model="item.quantity"
                                    placeholder="e.g. 5"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-blue-500"
                                >
                            </div>
                        </div>
                    </div>

                    {{-- STEP 2: SUPPLIER QUOTATIONS --}}
                    <div>
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-start gap-2.5">
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#0d6efd] text-xs font-bold text-white">2</span>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900">Supplier quotations</h3>
                                    <p class="text-xs text-slate-500">Add 2–3 suppliers. Each supplier can offer multiple brands.</p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="if (item.suppliers.length < 3) addSupplier(item)"
                                :disabled="item.suppliers.length >= 3"
                                :aria-disabled="item.suppliers.length >= 3"
                                :title="item.suppliers.length >= 3 ? 'Maximum of 3 suppliers per item reached' : 'Add another supplier (up to 3)'"
                                class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-xs font-semibold transition"
                                :class="item.suppliers.length >= 3 ? 'cursor-not-allowed' : 'cursor-pointer hover:!bg-blue-50'"
                                :style="item.suppliers.length >= 3
                                    ? 'border-color:#e2e8f0;background:#f8fafc;color:#94a3b8;opacity:0.75;'
                                    : 'border-color:#3b82f6;background:#ffffff;color:#2563eb;'"
                            >
                                <span>+ Add supplier</span>
                            </button>
                        </div>

                        {{-- Supplier Carousel Tabs with Completion Indicators --}}
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <template x-for="(supTab, tIdx) in item.suppliers" :key="'tab-' + supTab._uid">
                                <button
                                    type="button"
                                    @click="item.activeSupplierIdx = tIdx"
                                    class="inline-flex items-center gap-2 rounded-xl border px-3 py-1.5 text-xs font-semibold transition"
                                    :style="item.activeSupplierIdx === tIdx
                                        ? 'border-color:#0025cc;background:#eff6ff;color:#0025cc;'
                                        : 'border-color:#e2e8f0;background:#ffffff;color:#475569;'"
                                >
                                    <span
                                        class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                                        :style="supplierStatus(item, supTab) === 'valid'
                                            ? 'background:#16a34a;color:#ffffff;'
                                            : (supplierStatus(item, supTab) === 'error'
                                                ? 'background:#dc2626;color:#ffffff;'
                                                : 'background:#e2e8f0;color:#64748b;')"
                                        x-text="supplierStatus(item, supTab) === 'valid' ? '✓' : (supplierStatus(item, supTab) === 'error' ? '!' : (tIdx + 1))"
                                    ></span>
                                    <span class="max-w-[160px] truncate" x-text="supTab.supplier_id ? ('Supplier ' + (tIdx + 1) + ' · ' + supplierName(supTab.supplier_id)) : ('Supplier ' + (tIdx + 1))"></span>
                                </button>
                            </template>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(sup, sIdx) in item.suppliers" :key="sup._uid">
                                <div x-show="item.activeSupplierIdx === sIdx" class="rounded-xl border border-slate-200 bg-white p-4">
                                    <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2">
                                        <span class="text-sm font-medium text-slate-800" x-text="'Supplier ' + (sIdx + 1)"></span>
                                        <button
                                            type="button"
                                            x-show="item.suppliers.length > 2"
                                            @click="removeSupplier(item, sIdx)"
                                            class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-semibold transition hover:!border-[#dc2626] hover:!bg-[#dc2626] hover:!text-white"
                                            style="background: #fef2f2; border-color: #fecaca; color: #dc2626;"
                                        >
                                            Remove supplier
                                        </button>
                                    </div>

                                    <div class="mb-4 flex flex-wrap items-center gap-3">
                                        <label class="w-32 text-xs font-medium text-slate-700">Supplier name:</label>
                                        <div
                                            class="relative w-full max-w-xs"
                                            x-data="{ open: false, q: '' }"
                                            @click.outside="open = false"
                                            @keydown.escape.window="open = false"
                                        >
                                            <input type="hidden" :name="`items[${iIdx}][suppliers][${sIdx}][supplier_id]`" :value="sup.supplier_id">
                                            <button
                                                type="button"
                                                @click="open = !open; if (open) $nextTick(() => $refs.supSearch?.focus())"
                                                class="flex w-full items-center justify-between rounded-md border border-slate-300 bg-white px-3 py-1.5 text-left text-sm outline-none transition focus:border-blue-500"
                                                :class="sup.supplier_id ? 'text-slate-900' : 'text-slate-600'"
                                            >
                                                <span class="truncate" x-text="sup.supplier_id ? supplierName(sup.supplier_id) : 'Select supplier'"></span>
                                                <svg class="ml-2 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                                            </button>

                                            <div
                                                x-cloak
                                                x-show="open"
                                                class="absolute left-0 right-0 z-40 mt-1.5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                            >
                                                <div class="border-b border-slate-100 p-2.5">
                                                    <input
                                                        type="text"
                                                        x-ref="supSearch"
                                                        x-model="q"
                                                        placeholder="Search..."
                                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-blue-500"
                                                    >
                                                </div>

                                                <div class="max-h-56 overflow-y-auto py-1">
                                                    <button
                                                        type="button"
                                                        @click="sup.supplier_id = ''; open = false; q = ''"
                                                        class="block w-full px-3.5 py-2 text-left text-sm text-slate-500 transition hover:bg-slate-50"
                                                    >
                                                        Select supplier
                                                    </button>
                                                    <template x-for="opt in filterList(suppliers, q)" :key="opt.id">
                                                        <button
                                                            type="button"
                                                            :disabled="isSupplierTaken(item, sIdx, opt.id)"
                                                            @click="sup.supplier_id = opt.id; open = false; q = ''"
                                                            class="flex w-full items-center justify-between px-3.5 py-2 text-left text-sm transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-45"
                                                            :class="String(sup.supplier_id) === String(opt.id) ? 'bg-blue-50 font-semibold text-blue-700' : 'text-slate-800'"
                                                        >
                                                            <span class="truncate" x-text="opt.name + (opt.blacklisted ? ' (Blacklisted)' : '')"></span>
                                                            <span x-show="String(sup.supplier_id) === String(opt.id)" class="ml-2 text-xs text-blue-600">✓</span>
                                                        </button>
                                                    </template>
                                                    <p x-show="filterList(suppliers, q).length === 0" class="px-3.5 py-3 text-center text-xs text-slate-400">No matching suppliers</p>
                                                </div>

                                                <div class="border-t border-slate-100 bg-white p-2.5">
                                                    <button
                                                        type="button"
                                                        @click="open = false; openAddSupplierModal(sup)"
                                                        class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#001db3]"
                                                    >
                                                        <span>+ Add supplier</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <template x-if="supplierWarning(sup.supplier_id)">
                                                <p class="mt-1 text-[11px] font-medium text-amber-700" x-text="supplierWarning(sup.supplier_id)"></p>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- BRAND OFFERS (1..10) --}}
                                    <div class="space-y-3">
                                        <template x-for="(offer, oIdx) in sup.offers" :key="offer._uid">
                                            <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-3.5">
                                                <div class="mb-2 flex items-center justify-between">
                                                    <span class="text-xs font-bold text-slate-800" x-text="'Brand offer ' + (oIdx + 1)"></span>
                                                    <button
                                                        type="button"
                                                        x-show="sup.offers.length > 1"
                                                        @click="removeOffer(sup, oIdx)"
                                                        class="text-xs font-medium hover:underline"
                                                        style="color: #dc2626;"
                                                    >
                                                        Remove
                                                    </button>
                                                </div>

                                                <div class="flex flex-wrap items-end gap-3">
                                                    {{-- BRAND SEARCHABLE + QUICK-ADD DROPDOWN --}}
                                                    <div
                                                        class="relative w-full sm:w-64"
                                                        x-data="{ open: false, q: '', newName: '', busy: false, err: '' }"
                                                        @click.outside="open = false"
                                                        @keydown.escape.window="open = false"
                                                    >
                                                        <label class="mb-1 block text-xs font-medium text-slate-700">Brand</label>
                                                        <input type="hidden" :name="`items[${iIdx}][suppliers][${sIdx}][offers][${oIdx}][brand_id]`" :value="offer.brand_id">
                                                        <button
                                                            type="button"
                                                            @click="open = !open; if (open) $nextTick(() => $refs.brandSearch?.focus())"
                                                            class="flex w-full items-center justify-between rounded-md border border-slate-300 bg-white px-3 py-1.5 text-left text-sm outline-none transition focus:border-blue-500"
                                                            :class="offer.brand_id ? 'text-slate-900' : 'text-slate-600'"
                                                        >
                                                            <span class="truncate" x-text="offer.brand_id ? brandName(offer.brand_id) : 'Select brand'"></span>
                                                            <svg class="ml-2 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                                                        </button>

                                                        <div
                                                            x-cloak
                                                            x-show="open"
                                                            class="absolute left-0 right-0 z-40 mt-1.5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                                        >
                                                            <div class="border-b border-slate-100 p-2.5">
                                                                <input
                                                                    type="text"
                                                                    x-ref="brandSearch"
                                                                    x-model="q"
                                                                    placeholder="Search brands..."
                                                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-blue-500"
                                                                >
                                                            </div>
                                                            <div class="max-h-52 overflow-y-auto py-1">
                                                                <template x-for="b in filterList(brands, q)" :key="b.id">
                                                                    <button
                                                                        type="button"
                                                                        @click="offer.brand_id = b.id; open = false; q = ''"
                                                                        class="flex w-full items-center justify-between px-3.5 py-2 text-left text-sm transition hover:bg-blue-50"
                                                                        :class="String(offer.brand_id) === String(b.id) ? 'bg-blue-50 font-semibold text-blue-700' : 'text-slate-800'"
                                                                    >
                                                                        <span class="truncate" x-text="b.name"></span>
                                                                        <span x-show="String(offer.brand_id) === String(b.id)" class="text-xs text-blue-600">✓</span>
                                                                    </button>
                                                                </template>
                                                                <p x-show="filterList(brands, q).length === 0" class="px-3.5 py-3 text-center text-xs text-slate-400">No matching brands</p>
                                                            </div>
                                                            <div class="border-t border-slate-100 bg-slate-50/70 p-2.5">
                                                                <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Add new brand</p>
                                                                <div class="flex items-center gap-2">
                                                                    <input
                                                                        type="text"
                                                                        x-model="newName"
                                                                        @keydown.enter.prevent="quickAdd('brand', newName, (r) => { offer.brand_id = r.id; newName = ''; q = ''; open = false; }, (e) => err = e, (b) => busy = b)"
                                                                        placeholder="New brand name"
                                                                        class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 outline-none focus:border-blue-500"
                                                                    >
                                                                    <button
                                                                        type="button"
                                                                        :disabled="busy || !newName.trim()"
                                                                        @click="quickAdd('brand', newName, (r) => { offer.brand_id = r.id; newName = ''; q = ''; open = false; }, (e) => err = e, (b) => busy = b)"
                                                                        class="shrink-0 rounded-xl bg-[#0025cc] px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-[#001db3] disabled:opacity-50"
                                                                    >
                                                                        <span x-text="busy ? '...' : 'Add'"></span>
                                                                    </button>
                                                                </div>
                                                                <p x-show="err" x-text="err" class="mt-1 text-[11px] font-medium text-rose-600"></p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- SOLD AS (UNIT) SEARCHABLE + QUICK-ADD DROPDOWN --}}
                                                    <div
                                                        class="relative w-full sm:w-36"
                                                        x-data="{ open: false, q: '', newName: '', busy: false, err: '' }"
                                                        @click.outside="open = false"
                                                        @keydown.escape.window="open = false"
                                                    >
                                                        <label class="mb-1 block text-xs font-medium text-slate-700">Sold as</label>
                                                        <input type="hidden" :name="`items[${iIdx}][suppliers][${sIdx}][offers][${oIdx}][offer_uom_id]`" :value="offer.offer_uom_id">
                                                        <button
                                                            type="button"
                                                            @click="open = !open; if (open) $nextTick(() => $refs.soldAsSearch?.focus())"
                                                            class="flex w-full items-center justify-between rounded-md border border-slate-300 bg-white px-3 py-1.5 text-left text-sm outline-none transition focus:border-blue-500"
                                                            :class="offer.offer_uom_id ? 'text-slate-900' : 'text-slate-600'"
                                                        >
                                                            <span class="truncate" x-text="offer.offer_uom_id ? uomName(offer.offer_uom_id) : 'Unit'"></span>
                                                            <svg class="ml-2 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                                                        </button>

                                                        <div
                                                            x-cloak
                                                            x-show="open"
                                                            class="absolute left-0 right-0 z-40 mt-1.5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                                        >
                                                            <div class="border-b border-slate-100 p-2.5">
                                                                <input
                                                                    type="text"
                                                                    x-ref="soldAsSearch"
                                                                    x-model="q"
                                                                    placeholder="Search units..."
                                                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-blue-500"
                                                                >
                                                            </div>
                                                            <div class="max-h-52 overflow-y-auto py-1">
                                                                <template x-for="u in filterList(uoms, q)" :key="u.id">
                                                                    <button
                                                                        type="button"
                                                                        @click="offer.offer_uom_id = u.id; if (String(offer.offer_uom_id) === String(item.uom_id)) offer.units_per_pack = '1'; open = false; q = ''"
                                                                        class="flex w-full items-center justify-between px-3.5 py-2 text-left text-sm transition hover:bg-blue-50"
                                                                        :class="String(offer.offer_uom_id) === String(u.id) ? 'bg-blue-50 font-semibold text-blue-700' : 'text-slate-800'"
                                                                    >
                                                                        <span class="truncate" x-text="u.name"></span>
                                                                        <span x-show="String(offer.offer_uom_id) === String(u.id)" class="text-xs text-blue-600">✓</span>
                                                                    </button>
                                                                </template>
                                                                <p x-show="filterList(uoms, q).length === 0" class="px-3.5 py-3 text-center text-xs text-slate-400">No matching units</p>
                                                            </div>
                                                            <div class="border-t border-slate-100 bg-slate-50/70 p-2.5">
                                                                <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Add new unit</p>
                                                                <div class="flex items-center gap-2">
                                                                    <input
                                                                        type="text"
                                                                        x-model="newName"
                                                                        @keydown.enter.prevent="quickAdd('uom', newName, (r) => { offer.offer_uom_id = r.id; if (String(offer.offer_uom_id) === String(item.uom_id)) offer.units_per_pack = '1'; newName = ''; q = ''; open = false; }, (e) => err = e, (b) => busy = b)"
                                                                        placeholder="New unit name"
                                                                        class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 outline-none focus:border-blue-500"
                                                                    >
                                                                    <button
                                                                        type="button"
                                                                        :disabled="busy || !newName.trim()"
                                                                        @click="quickAdd('uom', newName, (r) => { offer.offer_uom_id = r.id; if (String(offer.offer_uom_id) === String(item.uom_id)) offer.units_per_pack = '1'; newName = ''; q = ''; open = false; }, (e) => err = e, (b) => busy = b)"
                                                                        class="shrink-0 rounded-xl bg-[#0025cc] px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-[#001db3] disabled:opacity-50"
                                                                    >
                                                                        <span x-text="busy ? '...' : 'Add'"></span>
                                                                    </button>
                                                                </div>
                                                                <p x-show="err" x-text="err" class="mt-1 text-[11px] font-medium text-rose-600"></p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="w-full sm:w-48">
                                                        <label class="mb-1 block text-xs font-medium text-slate-700">Price per offer unit</label>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            :name="`items[${iIdx}][suppliers][${sIdx}][offers][${oIdx}][unit_price]`"
                                                            x-model="offer.unit_price"
                                                            placeholder="0.00"
                                                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 outline-none focus:border-blue-500"
                                                        >
                                                    </div>
                                                </div>

                                                {{-- Conditional units-per-pack conversion when Sold as differs from Item UOM --}}
                                                <div
                                                    x-show="item.uom_id && offer.offer_uom_id && offer.offer_uom_id !== item.uom_id"
                                                    class="mt-3 rounded-lg border border-blue-100 bg-blue-50/60 px-3 py-2.5"
                                                >
                                                    <div class="flex flex-wrap items-center gap-3">
                                                        <label class="text-xs font-medium text-blue-900">
                                                            Requested units (<span x-text="uomName(item.uom_id)"></span>) per 1 <span x-text="uomName(offer.offer_uom_id)"></span>:
                                                        </label>
                                                        <input
                                                            type="number"
                                                            min="0.01"
                                                            step="0.01"
                                                            :name="`items[${iIdx}][suppliers][${sIdx}][offers][${oIdx}][units_per_pack]`"
                                                            x-model="offer.units_per_pack"
                                                            class="w-28 rounded-lg border border-blue-200 bg-white px-2.5 py-1 text-sm text-slate-900 outline-none focus:border-blue-500"
                                                        >
                                                    </div>
                                                </div>
                                                <template x-if="!item.uom_id || !offer.offer_uom_id || offer.offer_uom_id === item.uom_id">
                                                    <input type="hidden" :name="`items[${iIdx}][suppliers][${sIdx}][offers][${oIdx}][units_per_pack]`" value="1">
                                                </template>

                                                {{-- Duplicate Brand + Sold-as warning --}}
                                                <p
                                                    x-show="isDuplicateOffer(sup, oIdx)"
                                                    class="mt-2 text-xs font-semibold"
                                                    style="color: #dc2626;"
                                                >
                                                    Duplicate brand and unit combination under this supplier.
                                                </p>

                                                {{-- Live estimate bar (matches Image 1) --}}
                                                <div class="mt-3 flex items-center justify-between rounded-md bg-white/80 px-3 py-2 text-xs">
                                                    <span class="text-slate-500">Estimated quantity and total</span>
                                                    <span class="font-bold text-slate-900" x-text="offerEstimateText(item, offer)"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between">
                                        <span class="text-xs text-slate-500" x-text="sup.offers.length + ' / 10 brand offers'"></span>
                                        <button
                                            type="button"
                                            @click="addOffer(item, sup)"
                                            :disabled="sup.offers.length >= 10"
                                            class="inline-flex items-center gap-1 rounded-md border border-slate-500 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:!border-[#0025cc] hover:!bg-[#0025cc] hover:!text-white disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-400 disabled:hover:!border-slate-200 disabled:hover:!bg-white disabled:hover:!text-slate-400"
                                        >
                                            <span>+ Add another brand</span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Supplier Carousel Prev / Next Controls --}}
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <button
                                type="button"
                                @click="if (item.activeSupplierIdx > 0) item.activeSupplierIdx--"
                                :disabled="item.activeSupplierIdx === 0"
                                class="rounded-lg border border-[#0025cc] bg-white px-3 py-1.5 text-xs font-semibold text-[#0025cc] transition hover:!bg-[#0025cc] hover:!text-white disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-400 disabled:hover:!bg-white disabled:hover:!text-slate-400"
                            >
                                ← Previous supplier
                            </button>
                            <span class="text-xs font-medium text-slate-600" x-text="'Supplier ' + (item.activeSupplierIdx + 1) + ' of ' + item.suppliers.length"></span>
                            <button
                                type="button"
                                @click="if (item.activeSupplierIdx < item.suppliers.length - 1) item.activeSupplierIdx++"
                                :disabled="item.activeSupplierIdx >= item.suppliers.length - 1"
                                class="rounded-lg bg-[#0025cc] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#001db3] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                            >
                                Next supplier →
                            </button>
                        </div>

                        <!--<p class="mt-3 text-[11px] leading-relaxed text-slate-500">
                            Add each supplier once, then add up to 10 brand offers under that supplier. A brand and unit combination can only be listed once per supplier. If an offer uses a different unit, enter the number of requested units per offer unit. Estimates round up to whole offer units.
                        </p>-->
                    </div>
                </div>
            </div>
        </template>

        {{-- CANVASS ATTACHMENTS CARD (with Purpose above Supporting files) --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Purpose <span style="color: #dc2626;">*</span></h3>

            <div class="mt-4">
                
                <textarea
                    name="canvass_purpose"
                    x-ref="purposeInput"
                    x-model="purpose"
                    @input="purposeError = ''"
                    rows="2"
                    maxlength="2000"
                    placeholder="e.g. Classroom equipment replacement"
                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-blue-500"
                    :style="purposeError ? 'border-color:#dc2626;' : ''"
                ></textarea>
                <p x-show="purposeError" x-text="purposeError" class="mt-1 text-xs font-semibold" style="color: #dc2626;"></p>
                <label class="mb-1.5 mt-1 block text-xs font-semibold text-slate-700">Note: <span class="font-normal text-slate-500">Carries into RIS</span></label>
            </div>

            <div class="mt-4">
                <div class="mb-1.5 flex items-center justify-between gap-2">
                    <label class="block text-xs font-semibold text-slate-700">Canvass Attachments <span class="font-normal text-slate-500">(Quotations)</span> <span style="color: #dc2626;">*</span></label>
                    <span
                        class="text-xs font-semibold"
                        :class="totalAttachmentCount() >= 5 ? 'text-blue-700' : 'text-slate-500'"
                        x-text="totalAttachmentCount() + ' / 5 files'"
                    ></span>
                </div>
                <input
                    type="file"
                    x-ref="attachmentsInput"
                    name="canvass_attachments[]"
                    multiple
                    :disabled="totalAttachmentCount() >= 5"
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,.bmp,.tiff,.avif,.heic"
                    @change="addAttachments($event)"
                    class="block w-full rounded-xl border border-slate-300 bg-white text-xs text-slate-700 file:mr-3 file:border-0 file:border-r file:border-slate-300 file:bg-slate-50 file:px-3.5 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                    :style="attachmentError ? 'border-color:#dc2626;' : ''"
                >
                <p class="mt-1.5 text-[11px] text-slate-500">
                    PDF, Word, or common image files (JPG, PNG, GIF, WEBP, BMP, TIFF, AVIF, HEIC) · At least 1 file required · Up to 5 files · 2 MB per file, 7 MB total.
                </p>
                <p x-show="attachmentError" x-text="attachmentError" class="mt-1.5 text-xs font-semibold" style="color: #dc2626;"></p>

                <template x-if="selectedAttachments.length > 0">
                    <ul class="mt-3 space-y-1.5">
                        <template x-for="(file, fIdx) in selectedAttachments" :key="file.name + '-' + file.size + '-' + fIdx">
                            <li class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs">
                                <span class="min-w-0 truncate font-medium text-slate-800" x-text="file.name"></span>
                                <div class="flex shrink-0 items-center gap-3">
                                    <span class="text-slate-400" x-text="formatFileSize(file.size)"></span>
                                    <button
                                        type="button"
                                        @click="removeSelectedAttachment(fIdx)"
                                        class="font-semibold hover:underline"
                                        style="color: #dc2626;"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>

            @if ($attachments->isNotEmpty())
                <div class="mt-4 space-y-1.5 border-t border-slate-100 pt-3">
                    <p class="text-xs font-semibold text-slate-700">Existing files ({{ $attachments->count() }})</p>
                    @foreach ($attachments as $att)
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs">
                            <a href="{{ route($routePrefix.'.canvass.attachments.download', $att->canvass_attachment_id) }}" class="truncate font-medium text-blue-700 hover:underline">
                                {{ $att->canvass_attachment_original_name }}
                            </a>
                            <span class="ml-3 shrink-0 text-slate-400">{{ number_format(($att->canvass_attachment_size ?? 0) / 1024, 1) }} KB</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- PURCHASER SIGNATURE CARD (matches Image 2) --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Purchaser signature</h3>
            <p class="mt-0.5 text-xs text-slate-500">Sign in the box or attach a signature image. A signature is required before submitting.</p>

            @if ($savedSignatures->isNotEmpty())
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-semibold text-slate-500">Saved signatures:</span>
                    @foreach ($savedSignatures as $sig)
                        <button
                            type="button"
                            @click="applySavedSignature(@js($sig->preview_url))"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700 transition hover:border-blue-400 hover:bg-blue-50"
                        >
                            <img src="{{ $sig->preview_url }}" alt="" class="h-5 w-auto max-w-[60px] object-contain">
                            <span>{{ $sig->user_signature_label ?: 'Saved' }}</span>
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="relative mt-3 overflow-hidden rounded-xl border-2 border-dashed border-slate-300 bg-white">
                <canvas
                    x-ref="sigCanvas"
                    width="860"
                    height="160"
                    @pointerdown="startDraw($event)"
                    @pointermove="moveDraw($event)"
                    @pointerup="endDraw()"
                    @pointerleave="endDraw()"
                    class="h-40 w-full cursor-crosshair touch-none"
                ></canvas>
                <div
                    x-show="!signatureData"
                    class="pointer-events-none absolute inset-0 flex items-center justify-center text-xs text-slate-400"
                >
                    Draw your signature here
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="clearSignature()"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Clear signature
                    </button>
                    <label class="inline-flex cursor-pointer items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">
                        <span>Attach signature image</span>
                        <input
                            type="file"
                            name="canvass_signature_file"
                            accept="image/png,image/jpeg,image/webp"
                            @change="loadSignatureFile($event)"
                            class="hidden"
                        >
                    </label>
                    <span x-show="signatureData" class="text-xs font-medium text-emerald-700">✓ Signature ready</span>
                </div>

                <button
                    type="button"
                    @click="openPreviewModal()"
                    class="rounded-lg border border-blue-500 bg-white px-3.5 py-1.5 text-xs font-semibold text-blue-600 transition hover:bg-blue-50"
                >
                    Preview requisition slip
                </button>
            </div>
        </div>

        {{-- BOTTOM ACTION BAR --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <span class="text-xs text-slate-500">All item lines are submitted together as one request; each line keeps its own supplier quotes and approval.</span>

            <div class="flex items-center gap-2.5">
                <button
                    type="button"
                    @click="submitForm('draft')"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Save draft
                </button>
                <button
                    type="button"
                    @click="submitForm('submit')"
                    class="rounded-xl bg-[#0d6efd] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                >
                    Submit all item quotations
                </button>
            </div>
        </div>
    </form>

    {{-- PREVIEW REQUISITION SLIP MODAL (matches Image 2) --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="previewModalOpen"
            x-transition.opacity
            @keydown.escape.window="previewModalOpen = false"
            @click.self="previewModalOpen = false"
            class="fixed inset-0 z-[250] flex items-center justify-center bg-black/60 p-4"
        >
            <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between px-6 pt-5 pb-2">
                <h3 class="text-xl font-medium text-slate-900">Requisition and Issue Slip Preview</h3>
                <button
                    type="button"
                    @click="previewModalOpen = false"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                    aria-label="Close preview"
                >
                    ✕
                </button>
            </div>

            <div class="overflow-y-auto px-6 pb-6 pt-1">
                <div class="text-center">
                    <div class="text-base font-bold text-slate-950">STI COLLEGE - ORMOC, INC.</div>
                    <div class="mt-0.5 text-xs font-medium tracking-wide text-slate-500 uppercase">REQUISITION AND ISSUE SLIP</div>
                </div>

                <div class="mt-2 text-right text-xs text-slate-500" x-text="'Preview · ' + previewTimestamp"></div>

                <div class="mt-3 space-y-4">
                    <template x-for="(card, cIdx) in previewItemCards()" :key="cIdx">
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-sm font-bold text-slate-900" x-text="'Item ' + (cIdx + 1) + ': ' + card.itemName"></div>
                            <div class="mt-0.5 text-xs text-slate-600" x-text="'Requested quantity: ' + card.requestedQtyText"></div>

                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 text-left text-slate-800">
                                            <th class="border border-slate-300 px-3 py-2 font-bold">Brand</th>
                                            <th class="border border-slate-300 px-3 py-2 font-bold">Supplier</th>
                                            <th class="border border-slate-300 px-3 py-2 font-bold">Sold as</th>
                                            <th class="border border-slate-300 px-3 py-2 font-bold">Price per offer unit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(offerRow, rIdx) in card.offers" :key="rIdx">
                                            <tr>
                                                <td class="border border-slate-200 px-3 py-2 text-slate-800" x-text="offerRow.brand"></td>
                                                <td class="border border-slate-200 px-3 py-2 text-slate-800" x-text="offerRow.supplier"></td>
                                                <td class="border border-slate-200 px-3 py-2 text-slate-700" x-text="offerRow.soldAs"></td>
                                                <td class="border border-slate-200 px-3 py-2 text-slate-900" x-text="offerRow.price"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="card.offers.length === 0">
                                            <td colspan="4" class="border border-slate-200 px-3 py-3 text-center text-slate-400">No supplier quotations entered yet.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-5 border-t border-slate-200 pt-4">
                    <div class="text-xs font-bold text-slate-900">Requested by signature</div>
                    <div class="mt-2">
                        <img x-show="signatureData" :src="signatureData" alt="Requested by signature" class="max-h-16 object-contain">
                        <p x-show="!signatureData" class="text-xs text-slate-400">No signature attached yet.</p>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </template>

    {{-- CONFIRM REMOVE ITEM MODAL --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="removeItemModalOpen"
            @keydown.escape.window="if (removeItemModalOpen) cancelRemoveItem()"
            class="fixed inset-0 z-[260] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[2px]"
            @click.self="cancelRemoveItem()"
        >
            <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start gap-3.5 px-6 pt-6 pb-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background: #fef2f2; color: #dc2626;">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base font-bold text-slate-900" x-text="'Remove ' + pendingRemoveItemLabel + '?'"></h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                            This item already has entered details or supplier quotations. Removing it will permanently discard those entries from this canvass.
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
                    <button
                        type="button"
                        @click="cancelRemoveItem()"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="confirmRemoveItem()"
                        class="rounded-lg px-4 py-2 text-xs font-semibold text-white transition hover:opacity-90"
                        style="background: #dc2626;"
                    >
                        Remove item
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- CONFIRM SUBMIT CANVASS MODAL --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="submitConfirmModalOpen"
            @keydown.escape.window="if (submitConfirmModalOpen && !isSubmitting) submitConfirmModalOpen = false"
            class="fixed inset-0 z-[265] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[2px]"
            @click.self="if (!isSubmitting) submitConfirmModalOpen = false"
        >
            <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 px-6 py-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background: #eff6ff; color: #0025cc;">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-bold text-slate-900">Confirm Purchase Canvass Submission</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Review the destination and summary below before sending.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 px-6 py-5">
                    {{-- Destination Indicator --}}
                    <div class="flex items-center justify-between gap-3 rounded-xl border px-4 py-3" style="background: #eff6ff; border-color: #bfdbfe;">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider" style="color: #1d4ed8;">Sending to</p>
                            <p class="mt-0.5 text-sm font-bold text-slate-900">School Administrator</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold" style="background: #dbeafe; color: #1e40af;">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: #2563eb;"></span>
                            Awaiting for review
                        </span>
                    </div>

                    {{-- Submission Summary --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 text-xs">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <span class="text-slate-500">Items included</span>
                                <p class="mt-0.5 font-bold text-slate-900" x-text="items.length + (items.length === 1 ? ' item' : ' items')"></p>
                            </div>
                            <div>
                                <span class="text-slate-500">Urgency</span>
                                <p class="mt-0.5 font-bold" :style="urgency === 'Urgent' ? 'color:#dc2626;' : 'color:#0f172a;'" x-text="urgency"></p>
                            </div>
                            <div>
                                <span class="text-slate-500">Quotation attachments</span>
                                <p class="mt-0.5 font-bold text-slate-900" x-text="totalAttachmentCount() + (totalAttachmentCount() === 1 ? ' file' : ' files')"></p>
                            </div>
                            <div>
                                <span class="text-slate-500">Purchaser signature</span>
                                <p class="mt-0.5 font-bold" style="color: #16a34a;">✓ Ready</p>
                            </div>
                        </div>
                        <div class="mt-3 border-t border-slate-200 pt-2.5">
                            <span class="text-slate-500">Purpose</span>
                            <p class="mt-0.5 line-clamp-2 font-medium text-slate-800" x-text="purpose"></p>
                        </div>
                    </div>

                    <p class="text-xs leading-relaxed text-slate-500">
                        Once submitted, this canvass will appear in the <strong>School Administrator's Canvass Review</strong> queue. Editing will be locked as soon as the School Administrator starts reviewing it.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
                    <button
                        type="button"
                        :disabled="isSubmitting"
                        @click="submitConfirmModalOpen = false"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="isSubmitting"
                        @click="confirmFinalSubmit()"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-semibold text-white transition hover:opacity-90 disabled:opacity-60"
                        style="background: #0025cc;"
                    >
                        <span x-text="isSubmitting ? 'Submitting...' : 'Confirm & Send to School Admin'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- CONFIRM LEAVE CANVASS MODAL --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="leaveModalOpen"
            @keydown.escape.window="if (leaveModalOpen) leaveModalOpen = false"
            class="fixed inset-0 z-[265] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[2px]"
            @click.self="leaveModalOpen = false"
        >
            <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start gap-3.5 px-6 pt-6 pb-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background: #fef2f2; color: #dc2626;">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base font-bold text-slate-900">Leave Purchase Canvass?</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                            You have entered item details or quotations that haven't been saved yet. Leaving now will discard your unsaved changes.
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
                    <button
                        type="button"
                        @click="leaveModalOpen = false"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Stay on form
                    </button>
                    <button
                        type="button"
                        @click="confirmLeave()"
                        class="rounded-lg px-4 py-2 text-xs font-semibold text-white transition hover:opacity-90"
                        style="background: #dc2626;"
                    >
                        Leave anyway
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- SHARED ADD SUPPLIER MODAL (AJAX submit keeps canvass form intact) --}}
    @include('purchaser.suppliers._add-modal', [
        'formAction' => route($routePrefix.'.suppliers.quick-store'),
        'modalZ' => 'z-[280]',
        'fromRis' => true,
        'ajaxSubmit' => true,
    ])
</div>

@push('scripts')
<script>
function canvassBuilder(boot) {
    let uidCounter = 1;
    const nextUid = () => 'cv-' + (uidCounter++);

    const normalizeOffer = (o, defaultUomId) => ({
        _uid: nextUid(),
        brand_id: String(o?.brand_id || ''),
        offer_uom_id: String(o?.offer_uom_id || defaultUomId || ''),
        units_per_pack: String(o?.units_per_pack || '1'),
        unit_price: o?.unit_price !== undefined && o?.unit_price !== null ? String(o.unit_price) : '',
    });

    const normalizeSupplier = (s, defaultUomId) => ({
        _uid: nextUid(),
        supplier_id: String(s?.supplier_id || ''),
        offers: Array.isArray(s?.offers) && s.offers.length
            ? s.offers.map(o => normalizeOffer(o, defaultUomId))
            : [normalizeOffer({}, defaultUomId)],
    });

    const normalizeItem = (it) => {
        const uomId = String(it?.uom_id || '');
        const sups = Array.isArray(it?.suppliers) && it.suppliers.length
            ? it.suppliers.map(s => normalizeSupplier(s, uomId))
            : [normalizeSupplier({}, uomId), normalizeSupplier({}, uomId)];
        return {
            _uid: nextUid(),
            item_id: String(it?.item_id || ''),
            item_name: String(it?.item_name || ''),
            uom_id: uomId,
            quantity: it?.quantity !== undefined && it?.quantity !== null ? String(it.quantity) : '',
            review_note: String(it?.review_note || ''),
            collapsed: Boolean(it?.collapsed || false),
            activeSupplierIdx: 0,
            suppliers: sups,
        };
    };

    return {
        itemsCatalog: boot.itemsCatalog || [],
        uoms: boot.uoms || [],
        brands: boot.brands || [],
        suppliers: boot.suppliers || [],
        supplierWarnings: boot.supplierWarnings || {},
        quickStoreUrls: boot.quickStoreUrls || {},

        filterList(list, query) {
            const q = String(query || '').trim().toLowerCase();
            if (!q) return list;
            return list.filter(x => String(x.name || '').toLowerCase().includes(q));
        },

        async quickAdd(kind, rawName, onSuccess, onError, setBusy) {
            const name = String(rawName || '').trim();
            if (!name) return;
            onError('');
            setBusy(true);
            const url = this.quickStoreUrls[kind];
            const fieldMap = { item: 'item_name', uom: 'uom_name', brand: 'brand_name' };
            const listMap = { item: 'itemsCatalog', uom: 'uoms', brand: 'brands' };
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ [fieldMap[kind]]: name }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.id) {
                    const firstErr = data?.errors ? Object.values(data.errors).flat()[0] : null;
                    onError(firstErr || data?.message || 'Could not add entry.');
                    return;
                }
                const entry = { id: String(data.id), name: String(data.label || name) };
                const targetList = this[listMap[kind]];
                if (!targetList.some(x => String(x.id) === entry.id)) {
                    targetList.push(entry);
                    targetList.sort((a, b) => a.name.localeCompare(b.name, undefined, { sensitivity: 'base' }));
                }
                onSuccess(entry);
            } catch (e) {
                onError('Network error while adding.');
            } finally {
                setBusy(false);
            }
        },
        procurementRequestId: String(boot.initial?.procurement_request_id || ''),
        purpose: String(boot.initial?.purpose || ''),
        urgency: String(boot.initial?.urgency || 'Non-Urgent'),
        signatureData: String(boot.initial?.purchaser_signature || ''),
        saveAction: 'submit',
        purposeError: '',
        attachmentError: '',
        clientErrors: [],
        selectedAttachments: [],
        existingAttachmentCount: Number(boot.existingAttachmentCount || 0),
        existingAttachmentBytes: Number(boot.existingAttachmentBytes || 0),
        previewModalOpen: false,
        previewTimestamp: '',
        removeItemModalOpen: false,
        pendingRemoveItemIdx: null,
        pendingRemoveItemLabel: '',
        submitConfirmModalOpen: false,
        isSubmitting: false,
        leaveModalOpen: false,
        pendingLeaveUrl: '',
        addSupplierModal: false,
        supplierType: 'Physical Store',
        supplierModalError: '',
        pendingSupplierSlot: null,
        purchaserName: boot.purchaserName || 'Purchaser',

        openAddSupplierModal(supSlot) {
            this.pendingSupplierSlot = supSlot || null;
            this.supplierModalError = '';
            this.supplierType = 'Physical Store';
            this.addSupplierModal = true;
        },

        async submitQuickSupplier(event) {
            const form = event.target;
            this.supplierModalError = '';
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.id) {
                    const firstErr = data?.errors ? Object.values(data.errors).flat()[0] : null;
                    this.supplierModalError = firstErr || data?.message || 'Could not save supplier.';
                    return;
                }
                const entry = {
                    id: String(data.id),
                    name: String(data.name || ('Supplier #' + data.id)),
                    baseName: String(data.name || ''),
                    type: String(data.type || 'Physical Store'),
                    blacklisted: false,
                };
                if (!this.suppliers.some(s => String(s.id) === entry.id)) {
                    this.suppliers.push(entry);
                    this.suppliers.sort((a, b) => a.name.localeCompare(b.name, undefined, { sensitivity: 'base' }));
                }
                if (this.pendingSupplierSlot) {
                    this.pendingSupplierSlot.supplier_id = entry.id;
                }
                form.reset();
                this.addSupplierModal = false;
            } catch (e) {
                this.supplierModalError = 'Network error while saving supplier.';
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        },
        todayDisplay: boot.todayDisplay || '',
        items: (boot.initial?.items || [{}]).map(normalizeItem),
        drawing: false,

        init() {
            this.$nextTick(() => {
                if (this.signatureData) {
                    this.drawImageOnCanvas(this.signatureData);
                }
            });
        },

        itemDisplayName(item) {
            if (item.item_id) {
                const found = this.itemsCatalog.find(c => c.id === String(item.item_id));
                if (found) return found.name;
            }
            return item.item_name || '';
        },

        syncItemName(item) {
            const found = this.itemsCatalog.find(c => c.id === String(item.item_id));
            item.item_name = found ? found.name : '';
        },

        syncDefaultOfferUoms(item) {
            item.suppliers.forEach(s => {
                s.offers.forEach(o => {
                    if (!o.offer_uom_id) o.offer_uom_id = item.uom_id;
                });
            });
        },

        uomName(id) {
            const u = this.uoms.find(x => x.id === String(id));
            return u ? u.name : 'unit';
        },

        brandName(id) {
            const b = this.brands.find(x => x.id === String(id));
            return b ? b.name : '—';
        },

        supplierName(id) {
            const s = this.suppliers.find(x => x.id === String(id));
            return s ? s.name : '—';
        },

        addItem() {
            if (this.items.length >= 30) return;
            this.items.push(normalizeItem({}));
        },

        formHasData() {
            if (String(this.purpose || '').trim() !== '') return true;
            if (this.selectedAttachments.length > 0) return true;
            return (this.items || []).some(it => this.itemHasData(it));
        },

        handleBackClick(event, url) {
            if (this.formHasData()) {
                event.preventDefault();
                this.pendingLeaveUrl = url;
                this.leaveModalOpen = true;
            }
        },

        confirmLeave() {
            const target = this.pendingLeaveUrl;
            this.leaveModalOpen = false;
            if (target) {
                window.location.href = target;
            }
        },

        itemHasData(item) {
            if (!item) return false;
            if (item.item_id || String(item.item_name || '').trim() !== '') return true;
            if (item.uom_id) return true;
            if (String(item.quantity || '').trim() !== '') return true;
            return (item.suppliers || []).some(s =>
                Boolean(s.supplier_id)
                || (s.offers || []).some(o =>
                    Boolean(o.brand_id)
                    || this.parseMoney(o.unit_price) > 0
                )
            );
        },

        removeItem(idx) {
            if (this.items.length <= 1) return;
            const item = this.items[idx];
            if (this.itemHasData(item)) {
                const name = this.itemDisplayName(item);
                this.pendingRemoveItemIdx = idx;
                this.pendingRemoveItemLabel = name ? ('Item ' + (idx + 1) + ' (' + name + ')') : ('Item ' + (idx + 1));
                this.removeItemModalOpen = true;
                return;
            }
            this.items.splice(idx, 1);
        },

        confirmRemoveItem() {
            if (this.pendingRemoveItemIdx !== null && this.items.length > 1) {
                this.items.splice(this.pendingRemoveItemIdx, 1);
            }
            this.cancelRemoveItem();
        },

        cancelRemoveItem() {
            this.removeItemModalOpen = false;
            this.pendingRemoveItemIdx = null;
            this.pendingRemoveItemLabel = '';
        },

        addSupplier(item) {
            if (item.suppliers.length >= 3) return;
            item.suppliers.push(normalizeSupplier({}, item.uom_id));
            item.activeSupplierIdx = item.suppliers.length - 1;
        },

        removeSupplier(item, sIdx) {
            if (item.suppliers.length <= 2) return;
            item.suppliers.splice(sIdx, 1);
            if (item.activeSupplierIdx >= item.suppliers.length) {
                item.activeSupplierIdx = Math.max(0, item.suppliers.length - 1);
            }
        },

        parseMoney(val) {
            const cleaned = String(val ?? '').replace(/[^0-9.]/g, '');
            const num = parseFloat(cleaned);
            return isNaN(num) ? 0 : num;
        },

        supplierStatus(item, sup) {
            if (!sup) return 'incomplete';
            const hasDup = sup.offers.some((o, idx) => this.isDuplicateOffer(sup, idx));
            if (hasDup) return 'error';
            if (!sup.supplier_id) return 'incomplete';
            const hasCompleteOffer = sup.offers.some(o =>
                Boolean(o.brand_id)
                && Boolean(o.offer_uom_id || item.uom_id)
                && this.parseMoney(o.unit_price) > 0
            );
            return hasCompleteOffer ? 'valid' : 'incomplete';
        },

        validSupplierCount(item) {
            return item.suppliers.filter(s => this.supplierStatus(item, s) === 'valid').length;
        },

        isItemReady(item) {
            return Boolean(item.item_id)
                && Boolean(item.uom_id)
                && (parseInt(item.quantity, 10) || 0) >= 1
                && this.validSupplierCount(item) >= 2;
        },

        addOffer(item, sup) {
            if (sup.offers.length >= 10) return;
            sup.offers.push(normalizeOffer({}, item.uom_id));
        },

        removeOffer(sup, oIdx) {
            if (sup.offers.length <= 1) return;
            sup.offers.splice(oIdx, 1);
        },

        isSupplierTaken(item, currentIdx, supplierId) {
            if (!supplierId) return false;
            return item.suppliers.some((s, idx) => idx !== currentIdx && String(s.supplier_id) === String(supplierId));
        },

        isDuplicateOffer(sup, currentIdx) {
            const cur = sup.offers[currentIdx];
            if (!cur || !cur.brand_id || !cur.offer_uom_id) return false;
            return sup.offers.some((o, idx) =>
                idx !== currentIdx
                && String(o.brand_id) === String(cur.brand_id)
                && String(o.offer_uom_id) === String(cur.offer_uom_id)
            );
        },

        supplierWarning(supplierId) {
            const w = this.supplierWarnings[String(supplierId)];
            if (!w) return '';
            if (w.blacklisted) return '⚠ Blacklisted supplier' + (w.reason ? ': ' + w.reason : '');
            if (w.risk) return '⚠ Delivery caution (' + w.issues + ' recorded issue(s))';
            return '';
        },

        computeOfferStats(item, offer) {
            const qty = Math.max(0, parseInt(item.quantity, 10) || 0);
            const sameUom = !item.uom_id || !offer.offer_uom_id || String(item.uom_id) === String(offer.offer_uom_id);
            const pack = sameUom ? 1 : Math.max(0.01, this.parseMoney(offer.units_per_pack) || 1);
            const price = Math.max(0, this.parseMoney(offer.unit_price));
            const offerUnits = qty > 0 ? Math.ceil(qty / pack) : 0;
            const total = offerUnits * price;
            return { offerUnits, total, effectiveUnitCost: price / pack };
        },

        offerEstimateText(item, offer) {
            const { offerUnits, total } = this.computeOfferStats(item, offer);
            return offerUnits + ' offer unit(s) · ₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        totalAttachmentCount() {
            return this.existingAttachmentCount + this.selectedAttachments.length;
        },

        formatFileSize(bytes) {
            const kb = Number(bytes || 0) / 1024;
            if (kb >= 1024) return (kb / 1024).toFixed(2) + ' MB';
            return kb.toFixed(1) + ' KB';
        },

        syncAttachmentsInput() {
            const input = this.$refs.attachmentsInput;
            if (!input || typeof DataTransfer === 'undefined') return;
            const dt = new DataTransfer();
            this.selectedAttachments.forEach(f => dt.items.add(f));
            input.files = dt.files;
        },

        addAttachments(event) {
            this.attachmentError = '';
            const incoming = Array.from(event.target.files || []);
            const maxFiles = 5;
            const maxPerFile = 2 * 1024 * 1024;
            const maxTotal = 7 * 1024 * 1024;

            let currentBytes = this.existingAttachmentBytes + this.selectedAttachments.reduce((sum, f) => sum + f.size, 0);

            for (const f of incoming) {
                const duplicate = this.selectedAttachments.some(
                    existing => existing.name === f.name && existing.size === f.size && existing.lastModified === f.lastModified
                );
                if (duplicate) continue;

                if (this.totalAttachmentCount() >= maxFiles) {
                    this.attachmentError = 'Maximum of 5 supporting files allowed.';
                    break;
                }
                if (f.size > maxPerFile) {
                    this.attachmentError = `"${f.name}" exceeds the 2 MB per-file limit.`;
                    continue;
                }
                if ((currentBytes + f.size) > maxTotal) {
                    this.attachmentError = 'Combined supporting files exceed the 7 MB total limit.';
                    break;
                }

                this.selectedAttachments.push(f);
                currentBytes += f.size;
            }

            this.syncAttachmentsInput();
        },

        removeSelectedAttachment(idx) {
            this.selectedAttachments.splice(idx, 1);
            this.attachmentError = '';
            this.syncAttachmentsInput();
        },

        // Signature canvas drawing
        canvasPos(e) {
            const canvas = this.$refs.sigCanvas;
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            return { x: (e.clientX - rect.left) * scaleX, y: (e.clientY - rect.top) * scaleY };
        },

        startDraw(e) {
            const canvas = this.$refs.sigCanvas;
            if (!canvas) return;
            canvas.setPointerCapture(e.pointerId);
            const ctx = canvas.getContext('2d');
            const pos = this.canvasPos(e);
            ctx.lineWidth = 2.2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#0f172a';
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
            this.drawing = true;
        },

        moveDraw(e) {
            if (!this.drawing) return;
            const canvas = this.$refs.sigCanvas;
            const ctx = canvas.getContext('2d');
            const pos = this.canvasPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        },

        endDraw() {
            if (!this.drawing) return;
            this.drawing = false;
            this.signatureData = this.$refs.sigCanvas.toDataURL('image/png');
        },

        clearSignature() {
            const canvas = this.$refs.sigCanvas;
            if (canvas) {
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            }
            this.signatureData = '';
        },

        drawImageOnCanvas(dataUrl) {
            const canvas = this.$refs.sigCanvas;
            if (!canvas || !dataUrl) return;
            const ctx = canvas.getContext('2d');
            const img = new Image();
            img.onload = () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                const ratio = Math.min((canvas.width - 20) / img.width, (canvas.height - 20) / img.height, 1);
                const w = img.width * ratio;
                const h = img.height * ratio;
                ctx.drawImage(img, (canvas.width - w) / 2, (canvas.height - h) / 2, w, h);
            };
            img.src = dataUrl;
        },

        applySavedSignature(dataUrl) {
            this.signatureData = dataUrl;
            this.drawImageOnCanvas(dataUrl);
        },

        loadSignatureFile(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = () => {
                if (typeof reader.result === 'string') {
                    this.signatureData = reader.result;
                    this.drawImageOnCanvas(reader.result);
                }
            };
            reader.readAsDataURL(file);
        },

        openPreviewModal() {
            this.previewTimestamp = new Date().toLocaleString('en-US');
            this.previewModalOpen = true;
        },

        previewItemCards() {
            return this.items.map(item => {
                const qtyText = item.quantity
                    ? (item.quantity + (item.uom_id ? ' ' + this.uomName(item.uom_id) : ''))
                    : '—';
                const offers = [];
                item.suppliers.forEach(s => {
                    const supLabel = s.supplier_id ? this.supplierName(s.supplier_id) : '';
                    s.offers.forEach(o => {
                        const priceNum = this.parseMoney(o.unit_price);
                        const hasAny = s.supplier_id || o.brand_id || (priceNum > 0);
                        if (!hasAny) return;
                        offers.push({
                            brand: o.brand_id ? this.brandName(o.brand_id) : '—',
                            supplier: supLabel || '—',
                            soldAs: o.offer_uom_id ? this.uomName(o.offer_uom_id) : (item.uom_id ? this.uomName(item.uom_id) : '—'),
                            price: priceNum > 0
                                ? ('₱' + priceNum.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
                                : '₱0.00',
                        });
                    });
                });
                return {
                    itemName: this.itemDisplayName(item) || 'Untitled item',
                    requestedQtyText: qtyText,
                    offers,
                };
            });
        },

        validateForSubmit() {
            const errs = [];
            let firstInvalidItemIdx = null;
            let firstInvalidSupIdx = null;

            const markInvalid = (iIdx, sIdx = null) => {
                if (firstInvalidItemIdx === null) {
                    firstInvalidItemIdx = iIdx;
                    firstInvalidSupIdx = sIdx;
                }
            };

            if (!this.items || this.items.length < 1) {
                errs.push('Add at least one item to the canvass.');
            } else {
                this.items.forEach((item, iIdx) => {
                    const label = 'Item ' + (iIdx + 1);
                    if (!item.item_id && !String(item.item_name || '').trim()) {
                        errs.push(label + ': Please select an item.');
                        markInvalid(iIdx);
                    }
                    if (!item.uom_id) {
                        errs.push(label + ': Please select a unit (UOM).');
                        markInvalid(iIdx);
                    }
                    const qty = parseInt(item.quantity, 10);
                    if (!qty || qty < 1) {
                        errs.push(label + ': Requested quantity must be at least 1.');
                        markInvalid(iIdx);
                    }

                    const seenSuppliers = new Set();
                    let validSupplierCount = 0;

                    (item.suppliers || []).forEach((sup, sIdx) => {
                        const supLabel = label + ', Supplier ' + (sIdx + 1);
                        if (!sup.supplier_id) {
                            errs.push(supLabel + ': Please select a supplier.');
                            markInvalid(iIdx, sIdx);
                            return;
                        }
                        if (seenSuppliers.has(String(sup.supplier_id))) {
                            errs.push(label + ': Each supplier can only be added once per item.');
                            markInvalid(iIdx, sIdx);
                            return;
                        }
                        seenSuppliers.add(String(sup.supplier_id));
                        validSupplierCount++;

                        const offers = sup.offers || [];
                        if (offers.length < 1) {
                            errs.push(supLabel + ': Add at least 1 brand offer.');
                            markInvalid(iIdx, sIdx);
                            return;
                        }

                        const seenPairs = new Set();
                        offers.forEach((offer, oIdx) => {
                            const offLabel = supLabel + ', Brand offer ' + (oIdx + 1);
                            const effectiveUom = offer.offer_uom_id || item.uom_id;
                            const price = this.parseMoney(offer.unit_price);
                            const pack = this.parseMoney(offer.units_per_pack);

                            if (!offer.brand_id) {
                                errs.push(offLabel + ': Select a brand.');
                                markInvalid(iIdx, sIdx);
                            }
                            if (!effectiveUom) {
                                errs.push(offLabel + ': Select a "Sold as" unit.');
                                markInvalid(iIdx, sIdx);
                            }
                            if (item.uom_id && effectiveUom && String(effectiveUom) !== String(item.uom_id) && pack <= 0) {
                                errs.push(offLabel + ': Units per offer unit must be greater than 0.');
                                markInvalid(iIdx, sIdx);
                            }
                            if (price <= 0) {
                                errs.push(offLabel + ': Price must be greater than ₱0.00.');
                                markInvalid(iIdx, sIdx);
                            }
                            if (offer.brand_id && effectiveUom) {
                                const key = String(offer.brand_id) + ':' + String(effectiveUom);
                                if (seenPairs.has(key)) {
                                    errs.push(supLabel + ': A brand and unit combination can only be listed once per supplier.');
                                    markInvalid(iIdx, sIdx);
                                } else {
                                    seenPairs.add(key);
                                }
                            }
                        });
                    });

                    if (validSupplierCount < 2) {
                        errs.push(label + ': Add at least 2 distinct suppliers before submitting.');
                        markInvalid(iIdx);
                    }
                });
            }

            if (!String(this.purpose || '').trim()) {
                this.purposeError = 'Purpose is required before submitting.';
                errs.push('Purpose is required before submitting.');
            }
            if (this.totalAttachmentCount() < 1) {
                this.attachmentError = 'Please attach at least 1 supporting quotation file before submitting.';
                errs.push('Please attach at least 1 supporting quotation file before submitting.');
            }
            if (!this.signatureData) {
                errs.push('Purchaser signature is required before submitting.');
            }

            if (errs.length > 0 && firstInvalidItemIdx !== null && this.items[firstInvalidItemIdx]) {
                this.items[firstInvalidItemIdx].collapsed = false;
                if (firstInvalidSupIdx !== null) {
                    this.items[firstInvalidItemIdx].activeSupplierIdx = firstInvalidSupIdx;
                }
            }

            return errs;
        },

        submitForm(action) {
            this.saveAction = action;
            this.purposeError = '';
            this.clientErrors = [];
            if (this.attachmentError && this.totalAttachmentCount() > 0) {
                this.clientErrors = [this.attachmentError];
                this.$nextTick(() => this.$refs.clientErrorBanner?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                return;
            }
            if (action === 'submit') {
                const errs = this.validateForSubmit();
                if (errs.length > 0) {
                    this.clientErrors = errs;
                    this.$nextTick(() => this.$refs.clientErrorBanner?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                    return;
                }
                this.submitConfirmModalOpen = true;
                return;
            }
            this.$nextTick(() => this.$refs.canvassForm.submit());
        },

        confirmFinalSubmit() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            this.saveAction = 'submit';
            this.$nextTick(() => this.$refs.canvassForm.submit());
        },

        onFormSubmit(event) {
            if (this.attachmentError) {
                event.preventDefault();
                alert(this.attachmentError);
            }
        },
    };
}
</script>
@endpush
@endsection
