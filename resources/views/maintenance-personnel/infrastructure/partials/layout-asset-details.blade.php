{{-- Shared Asset Details body for Room Interior Layout (loose + comlab). --}}
{{-- Expects Alpine: selectedLayoutAsset(), roomLayout.lifecycle, formatLayoutDate() --}}
<div class="min-h-0 flex-1 space-y-0 overflow-y-auto px-4 py-2 text-sm">
    <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
        <span class="text-slate-400">Name</span>
        <span class="max-w-[60%] truncate text-right font-medium text-slate-800" x-text="selectedLayoutAsset()?.name || '—'"></span>
    </div>
    <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
        <span class="text-slate-400">Asset tag</span>
        <span class="max-w-[60%] truncate text-right font-medium text-slate-800" x-text="selectedLayoutAsset()?.asset_tag || '—'"></span>
    </div>
    <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
        <span class="text-slate-400">Brand</span>
        <span class="font-medium text-slate-800" x-text="selectedLayoutAsset()?.brand || '—'"></span>
    </div>
    <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
        <span class="text-slate-400">Model</span>
        <span class="font-medium text-slate-800" x-text="selectedLayoutAsset()?.model || '—'"></span>
    </div>
    <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
        <span class="text-slate-400">Serial</span>
        <span class="max-w-[60%] truncate text-right font-medium text-slate-800" x-text="selectedLayoutAsset()?.serial_number || '—'"></span>
    </div>
    <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
        <span class="text-slate-400">Category</span>
        <span class="max-w-[60%] truncate text-right font-medium text-slate-800" x-text="selectedLayoutAsset()?.category_name || '—'"></span>
    </div>
    <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
        <span class="text-slate-400">Qty / Mode</span>
        <span class="font-medium text-slate-800">
            <span x-text="selectedLayoutAsset()?.quantity ?? 1"></span>
            <span class="text-slate-400">·</span>
            <span x-text="selectedLayoutAsset()?.tracking_mode || 'Individual'"></span>
        </span>
    </div>
    <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
        <span class="text-slate-400">Condition</span>
        <span
            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold"
            :class="
                (selectedLayoutAsset()?.condition || '') === 'Good'
                    ? 'bg-emerald-50 text-emerald-700'
                    : (selectedLayoutAsset()?.condition || '') === 'Damaged'
                        ? 'bg-rose-50 text-rose-700'
                        : 'bg-slate-100 text-slate-600'
            "
            x-text="selectedLayoutAsset()?.condition || '—'"
        ></span>
    </div>
    <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
        <span class="text-slate-400">Status</span>
        <span class="font-medium text-slate-800" x-text="selectedLayoutAsset()?.inventory_status || '—'"></span>
    </div>
    <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
        <span class="text-slate-400">Warranty</span>
        <span class="font-medium text-slate-800" x-text="formatLayoutDate(selectedLayoutAsset()?.warranty_expiration)"></span>
    </div>
    <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
        <span class="text-slate-400">Received</span>
        <span class="font-medium text-slate-800" x-text="formatLayoutDate(selectedLayoutAsset()?.acquired_date)"></span>
    </div>
    <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
        <span class="text-slate-400">Location</span>
        <span class="font-medium text-slate-800" x-text="roomLayout.name || '—'"></span>
    </div>
    <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
        <span class="text-slate-400">Zone</span>
        <span class="font-medium text-slate-800" x-text="selectedLayoutAsset()?.placement_zone || selectedLayoutAsset()?.location || '—'"></span>
    </div>

    <div class="border-b border-slate-50 py-3">
        <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">Lifecycle</p>
        <template x-if="roomLayout.lifecycle.loading">
            <p class="text-xs text-slate-400">Loading history…</p>
        </template>
        <template x-if="!roomLayout.lifecycle.loading && roomLayout.lifecycle.data">
            <div class="space-y-2.5">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-400">Put in room</span>
                    <span class="text-right font-medium text-slate-800" x-text="formatLayoutDate(roomLayout.lifecycle.data.deployed_at)"></span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-400">Last moved</span>
                    <span class="text-right font-medium text-slate-800" x-text="formatLayoutDate(roomLayout.lifecycle.data.last_moved_at)"></span>
                </div>
                <div class="flex items-center justify-between gap-3" x-show="roomLayout.lifecycle.data.disposed_at">
                    <span class="text-slate-400">Disposed</span>
                    <span class="text-right font-medium text-slate-800" x-text="formatLayoutDate(roomLayout.lifecycle.data.disposed_at)"></span>
                </div>
                <div class="flex items-center justify-between gap-3" x-show="roomLayout.lifecycle.data.disposal_reason">
                    <span class="text-slate-400">Disposal reason</span>
                    <span class="max-w-[55%] truncate text-right font-medium text-slate-800" x-text="roomLayout.lifecycle.data.disposal_reason || '—'"></span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-400">Last maintenance</span>
                    <span class="text-right font-medium text-slate-800" x-text="formatLayoutDate(roomLayout.lifecycle.data.last_maintenance_at)"></span>
                </div>

                <template x-if="(roomLayout.lifecycle.data.transfers || []).length">
                    <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                        <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Recent moves</p>
                        <template x-for="(move, idx) in (roomLayout.lifecycle.data.transfers || []).slice(0, 4)" :key="'move-' + idx">
                            <div class="border-t border-slate-200/70 py-1.5 first:border-t-0 first:pt-0">
                                <p class="text-xs font-medium text-slate-700">
                                    <span x-text="move.from_room_name || 'Unassigned'"></span>
                                    →
                                    <span x-text="move.to_room_name || '—'"></span>
                                </p>
                                <p class="text-[11px] text-slate-400" x-text="formatLayoutDate(move.created_at)"></p>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="(roomLayout.lifecycle.data.maintenance || []).length">
                    <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                        <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Recent maintenance</p>
                        <template x-for="(row, idx) in (roomLayout.lifecycle.data.maintenance || []).slice(0, 3)" :key="'maint-' + idx">
                            <div class="border-t border-slate-200/70 py-1.5 first:border-t-0 first:pt-0">
                                <p class="truncate text-xs font-medium text-slate-700" x-text="row.findings || row.status || 'Maintenance'"></p>
                                <p class="text-[11px] text-slate-400" x-text="formatLayoutDate(row.at)"></p>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </template>
        <template x-if="!roomLayout.lifecycle.loading && !roomLayout.lifecycle.data">
            <p class="text-xs text-slate-400">Lifecycle history unavailable.</p>
        </template>
    </div>

    <div class="py-3">
        <a
            :href="selectedLayoutAsset()?.view_url || ('/maintenance/equipment/view/' + selectedLayoutAsset()?.id)"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
            <i data-lucide="external-link" class="h-4 w-4"></i>
            Open full profile
        </a>
    </div>
</div>
