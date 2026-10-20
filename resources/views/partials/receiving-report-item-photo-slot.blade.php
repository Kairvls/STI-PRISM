@php
    $itemId = (int) (is_array($row) ? ($row['item_id'] ?? 0) : ($row->receiving_report_item_id ?? 0));
    $isVerified = $itemId > 0 && in_array($itemId, $verifiedItemIds ?? [], true);
    $cellsEditable = ($editable ?? false) && !$isVerified;
    $qty = is_array($row) ? ($row['quantity'] ?? '') : ($row->receiving_report_item_quantity ?? '');
    $orderedQty = is_array($row) ? ($row['ordered_qty'] ?? '') : ($row->receiving_report_item_ordered_qty ?? '');
    $article = is_array($row) ? ($row['article'] ?? '') : ($row->receiving_report_item_article ?? '');
    $photoPath = is_array($row) ? ($row['photo_path'] ?? null) : ($row->receiving_report_item_photo_path ?? null);
    $photoName = is_array($row) ? ($row['photo_name'] ?? null) : ($row->receiving_report_item_photo_name ?? null);
    $rawUnitPhotos = is_array($row)
        ? ($row['unit_photos'] ?? [])
        : ($row->receiving_report_item_unit_photos ?? null);
    if (is_string($rawUnitPhotos)) {
        $rawUnitPhotos = json_decode($rawUnitPhotos, true) ?: [];
    }
    $unitPhotosByIndex = [];
    if (is_array($rawUnitPhotos)) {
        foreach ($rawUnitPhotos as $entry) {
            if (! is_array($entry) || empty($entry['path'])) {
                continue;
            }
            $uIndex = isset($entry['index']) ? (int) $entry['index'] : count($unitPhotosByIndex);
            if ($uIndex >= 0 && $uIndex < 50) {
                $unitPhotosByIndex[$uIndex] = [
                    'path' => (string) $entry['path'],
                    'name' => isset($entry['name']) ? (string) $entry['name'] : null,
                ];
            }
        }
    }
    $receivedCount = ($qty !== '' && $qty !== null) ? max(0, min(50, (int) $qty)) : 0;
    $hasArticle = trim((string) $article) !== '';
    $hasPhoto = filled($photoPath) || $unitPhotosByIndex !== [];
    $showSlot = $hasArticle || $hasPhoto;
    $labelParts = [];
    if ($hasArticle) {
        $labelParts[] = $article;
    } else {
        $labelParts[] = is_numeric($i) ? 'Item '.((int) $i + 1) : 'Item';
    }
    if ($orderedQty !== '' && $orderedQty !== null) {
        $labelParts[] = 'ordered '.$orderedQty;
    }
    if ($qty !== '' && $qty !== null) {
        $labelParts[] = 'received '.$qty;
    }
    $labelText = implode(' · ', $labelParts);
@endphp
<div
    data-rr-photo-slot
    data-rr-photo-index="{{ $i }}"
    class="overflow-hidden rounded-lg border border-slate-200 bg-white {{ $showSlot ? '' : 'hidden' }}"
    @if(!$showSlot) hidden @endif
>
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-3 py-2">
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-slate-800" data-rr-photo-label>{{ $labelText }}</p>
            <p class="mt-0.5 text-[10px] text-slate-400" data-rr-photo-hint>Bulk group shot and/or one photo per received unit</p>
        </div>
        @if($isVerified)
            <span class="shrink-0 text-[10px] font-medium text-emerald-700">Verified · locked</span>
        @endif
    </div>

    <div class="space-y-3 px-3 py-2.5">
        {{-- Bulk / group photo --}}
        <div data-rr-bulk-photo>
            <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">Bulk photo <span class="font-normal normal-case text-slate-400">· whole line / group shot</span></p>
            @if($cellsEditable)
                <div
                    class="overflow-hidden rounded-lg border border-slate-200 bg-white"
                    x-data="{
                        fileName: '',
                        clear: false,
                        hasExisting: @js((bool) $photoPath),
                        existingName: @js($photoName ?: 'Current photo'),
                        displayName() {
                            if (this.fileName) return this.fileName;
                            return this.hasExisting && !this.clear ? this.existingName : '';
                        },
                        showFile() {
                            return !!this.fileName || (this.hasExisting && !this.clear);
                        },
                        showUpload() {
                            return !this.showFile();
                        },
                        clearFile() {
                            this.fileName = '';
                            this.clear = this.hasExisting;
                            if (this.$refs.bulkPhoto) this.$refs.bulkPhoto.value = '';
                            this.$nextTick(() => window.lucide && window.lucide.createIcons());
                        },
                        onPick(e) {
                            const f = e.target.files && e.target.files[0];
                            this.fileName = f ? f.name : '';
                            if (this.fileName) this.clear = false;
                            this.$nextTick(() => window.lucide && window.lucide.createIcons());
                        },
                    }"
                >
                    <div class="space-y-1.5 px-2.5 py-2">
                        <p class="text-[10px] text-slate-400">1 file · optional group shot</p>
                        <input type="checkbox" name="items[{{ $i }}][clear_photo]" value="1" class="sr-only" x-model="clear" tabindex="-1" aria-hidden="true">
                        <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-1.5 py-1.5" x-show="showFile()" x-cloak>
                            @if($photoPath)
                                <a
                                    href="{{ asset('storage/'.$photoPath) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="shrink-0"
                                    x-show="hasExisting && !clear && !fileName"
                                >
                                    <img src="{{ asset('storage/'.$photoPath) }}" alt="Bulk photo" class="h-7 w-7 rounded object-cover ring-1 ring-slate-200">
                                </a>
                            @endif
                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white text-slate-500 ring-1 ring-slate-200/80"
                                x-show="fileName || !hasExisting || clear"
                            >
                                <i data-lucide="camera" class="h-3.5 w-3.5"></i>
                            </div>
                            <p class="min-w-0 flex-1 truncate text-xs font-medium text-slate-800" x-text="displayName()"></p>
                            <button type="button" class="shrink-0 text-[11px] font-medium text-slate-400 transition hover:text-slate-800" x-on:click="clearFile()">Remove</button>
                        </div>
                        <label
                            x-show="showUpload()"
                            x-cloak
                            class="group flex cursor-pointer items-center gap-2.5 rounded-lg border border-dashed border-slate-300 bg-slate-50/70 px-2.5 py-2 transition hover:border-slate-400 hover:bg-slate-50"
                        >
                            <input
                                type="file"
                                name="items[{{ $i }}][photo]"
                                accept=".jpg,.jpeg,.png,.webp,image/*"
                                class="sr-only"
                                x-ref="bulkPhoto"
                                x-on:change="onPick($event)"
                            >
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-dashed border-slate-300 bg-white text-slate-500">
                                <i data-lucide="camera" class="h-3.5 w-3.5"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-slate-800">Upload bulk photo</p>
                                <p class="truncate text-[10px] text-slate-500">One file only</p>
                            </div>
                        </label>
                    </div>
                </div>
            @elseif($photoPath)
                <div class="flex items-center gap-2.5">
                    <a href="{{ asset('storage/'.$photoPath) }}" target="_blank" rel="noopener noreferrer" class="shrink-0" title="{{ $photoName ?: 'View photo' }}">
                        <img src="{{ asset('storage/'.$photoPath) }}" alt="{{ $photoName ?: 'Bulk photo' }}" class="h-10 w-10 rounded-md object-cover ring-1 ring-slate-200">
                    </a>
                    <a href="{{ asset('storage/'.$photoPath) }}" target="_blank" rel="noopener noreferrer" class="min-w-0 truncate text-xs font-medium text-[#0025cc] hover:underline">{{ $photoName ?: 'View photo' }}</a>
                </div>
            @else
                <p class="text-xs text-slate-400">No bulk photo</p>
            @endif
        </div>

        {{-- Per-unit photos (count = received qty, capped at 50) — collapsed by default to save space --}}
        <div
            data-rr-unit-photos
            class="{{ $receivedCount > 0 || $unitPhotosByIndex !== [] ? '' : 'hidden' }}"
            x-data="{ open: @js($unitPhotosByIndex !== []) }"
        >
            <button
                type="button"
                class="mb-1.5 flex w-full items-center justify-between gap-2 rounded-md px-0.5 py-0.5 text-left transition hover:bg-slate-50"
                x-on:click="open = !open"
                x-bind:aria-expanded="open.toString()"
            >
                <span class="flex min-w-0 items-center gap-1.5">
                    <svg
                        class="h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                        x-bind:class="open ? 'rotate-90' : ''"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/>
                    </svg>
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Per-unit photos</span>
                </span>
                <span class="shrink-0 text-[10px] text-slate-400" data-rr-unit-count-label>
                    @if($receivedCount > 0)
                        {{ $receivedCount }} slot{{ $receivedCount === 1 ? '' : 's' }} from received qty
                    @endif
                </span>
            </button>
            <div x-show="open" x-cloak class="space-y-1">
            <div data-rr-unit-list class="grid gap-2 sm:grid-cols-2">
                @for($u = 0; $u < max($receivedCount, $unitPhotosByIndex === [] ? 0 : max(array_keys($unitPhotosByIndex)) + 1); $u++)
                    @if($u >= 50)
                        @break
                    @endif
                    @php
                        $unitPhoto = $unitPhotosByIndex[$u] ?? null;
                        $unitPath = $unitPhoto['path'] ?? null;
                        $unitName = $unitPhoto['name'] ?? null;
                        $unitVisible = $u < $receivedCount || filled($unitPath);
                    @endphp
                    <div
                        data-rr-unit-slot
                        data-rr-unit-index="{{ $u }}"
                        class="rounded-md border border-slate-200 bg-slate-50/50 p-2 {{ $unitVisible ? '' : 'hidden' }}"
                        @if(!$unitVisible) hidden @endif
                    >
                        <p class="mb-1.5 text-[10px] font-medium text-slate-600">Unit {{ $u + 1 }}</p>
                        @if($cellsEditable)
                            <div
                                class="overflow-hidden rounded border border-slate-200 bg-white"
                                x-data="{
                                    fileName: '',
                                    clear: false,
                                    hasExisting: @js((bool) $unitPath),
                                    existingName: @js($unitName ?: 'Photo'),
                                    displayName() {
                                        if (this.fileName) return this.fileName;
                                        return this.hasExisting && !this.clear ? this.existingName : '';
                                    },
                                    showFile() {
                                        return !!this.fileName || (this.hasExisting && !this.clear);
                                    },
                                    showUpload() {
                                        return !this.showFile();
                                    },
                                    clearFile() {
                                        this.fileName = '';
                                        this.clear = this.hasExisting;
                                        if (this.$refs.unitPhoto) this.$refs.unitPhoto.value = '';
                                        this.$nextTick(() => window.lucide && window.lucide.createIcons());
                                    },
                                    onPick(e) {
                                        const f = e.target.files && e.target.files[0];
                                        this.fileName = f ? f.name : '';
                                        if (this.fileName) this.clear = false;
                                        this.$nextTick(() => window.lucide && window.lucide.createIcons());
                                    },
                                }"
                            >
                                <input type="checkbox" name="items[{{ $i }}][clear_unit_photos][{{ $u }}]" value="{{ $u }}" class="sr-only" x-model="clear" tabindex="-1" aria-hidden="true">
                                <div class="flex items-center gap-1.5 px-2 py-1.5" x-show="showFile()" x-cloak>
                                    @if($unitPath)
                                        <a href="{{ asset('storage/'.$unitPath) }}" target="_blank" rel="noopener noreferrer" class="shrink-0" x-show="hasExisting && !clear && !fileName">
                                            <img src="{{ asset('storage/'.$unitPath) }}" alt="Unit {{ $u + 1 }}" class="h-7 w-7 rounded object-cover ring-1 ring-slate-200">
                                        </a>
                                    @endif
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-slate-50 text-slate-500" x-show="fileName || !hasExisting || clear">
                                        <i data-lucide="camera" class="h-3 w-3"></i>
                                    </div>
                                    <p class="min-w-0 flex-1 truncate text-[10px] font-medium text-slate-800" x-text="displayName()"></p>
                                    <button type="button" class="shrink-0 text-[10px] font-medium text-slate-400 hover:text-slate-800" x-on:click="clearFile()">Remove</button>
                                </div>
                                <label
                                    x-show="showUpload()"
                                    x-cloak
                                    class="group flex cursor-pointer items-center gap-2 px-2 py-1.5 transition hover:bg-slate-50"
                                >
                                    <input
                                        type="file"
                                        name="items[{{ $i }}][unit_photos][{{ $u }}]"
                                        accept=".jpg,.jpeg,.png,.webp,image/*"
                                        class="sr-only"
                                        x-ref="unitPhoto"
                                        x-on:change="onPick($event)"
                                    >
                                    <i data-lucide="camera" class="h-3.5 w-3.5 shrink-0 text-slate-500"></i>
                                    <span class="min-w-0 truncate text-[10px] font-medium text-slate-700">Add photo · 1 file</span>
                                </label>
                            </div>
                        @elseif($unitPath)
                            <a href="{{ asset('storage/'.$unitPath) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2">
                                <img src="{{ asset('storage/'.$unitPath) }}" alt="Unit {{ $u + 1 }}" class="h-8 w-8 rounded object-cover ring-1 ring-slate-200">
                                <span class="truncate text-[10px] font-medium text-[#0025cc]">{{ $unitName ?: 'View' }}</span>
                            </a>
                        @else
                            <p class="text-[10px] text-slate-400">No photo</p>
                        @endif
                    </div>
                @endfor
            </div>
            <template data-rr-unit-template>
                <div data-rr-unit-slot data-rr-unit-index="__UNIT__" class="rounded-md border border-slate-200 bg-slate-50/50 p-2">
                    <p class="mb-1.5 text-[10px] font-medium text-slate-600" data-rr-unit-title>Unit __UNIT_LABEL__</p>
                    <div
                        class="overflow-hidden rounded border border-slate-200 bg-white"
                        x-data="{
                            fileName: '',
                            clear: false,
                            hasExisting: false,
                            showFile() { return !!this.fileName; },
                            showUpload() { return !this.fileName; },
                            clearFile() {
                                this.fileName = '';
                                if (this.$refs.unitPhoto) this.$refs.unitPhoto.value = '';
                                this.$nextTick(() => window.lucide && window.lucide.createIcons());
                            },
                            onPick(e) {
                                const f = e.target.files && e.target.files[0];
                                this.fileName = f ? f.name : '';
                                this.$nextTick(() => window.lucide && window.lucide.createIcons());
                            },
                        }"
                    >
                        <div class="flex items-center gap-1.5 px-2 py-1.5" x-show="showFile()" x-cloak>
                            <p class="min-w-0 flex-1 truncate text-[10px] font-medium text-slate-800" x-text="fileName"></p>
                            <button type="button" class="shrink-0 text-[10px] font-medium text-slate-400 hover:text-slate-800" x-on:click="clearFile()">Remove</button>
                        </div>
                        <label
                            x-show="showUpload()"
                            x-cloak
                            class="group flex cursor-pointer items-center gap-2 px-2 py-1.5 transition hover:bg-slate-50"
                        >
                            <input
                                type="file"
                                name="items[{{ $i }}][unit_photos][__UNIT__]"
                                accept=".jpg,.jpeg,.png,.webp,image/*"
                                class="sr-only"
                                x-ref="unitPhoto"
                                x-on:change="onPick($event)"
                            >
                            <i data-lucide="camera" class="h-3.5 w-3.5 shrink-0 text-slate-500"></i>
                            <span class="min-w-0 truncate text-[10px] font-medium text-slate-700">Add photo · 1 file</span>
                        </label>
                    </div>
                </div>
            </template>
            <p data-rr-unit-empty class="mt-1 hidden text-[10px] text-slate-400">Enter received quantity to add per-unit photo slots.</p>
            </div>
        </div>
    </div>
</div>
