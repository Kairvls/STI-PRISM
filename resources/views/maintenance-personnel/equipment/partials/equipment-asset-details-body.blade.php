{{-- Right drawer body for All Equipment / Inventory asset details. --}}
<div class="flex min-h-0 flex-1 flex-col overflow-hidden">
    <div class="shrink-0 border-b border-slate-100 px-6 py-5">
        <div class="flex items-start gap-4">
            <button
                type="button"
                id="modal_image_wrap"
                onclick="openEquipmentPhotoViewer(document.getElementById('modal_image')?.src, document.getElementById('eqAssetModal_profile_name')?.textContent)"
                class="hidden group relative h-14 w-14 shrink-0 overflow-hidden rounded-full bg-slate-100 ring-2 ring-white"
                aria-label="View equipment photo fullscreen"
            >
                <img id="modal_image" src="" alt="" class="h-full w-full object-cover">
                <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 transition group-hover:bg-slate-950/40">
                    <i data-lucide="expand" class="h-3.5 w-3.5 text-white opacity-0 transition group-hover:opacity-100"></i>
                </span>
            </button>
            <div
                id="modal_layout_icon_wrap"
                class="hidden h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 ring-2 ring-white"
            >
                <span
                    id="modal_layout_icon"
                    class="inline-flex h-7 w-7 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                ></span>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 id="eqAssetModal_profile_name" class="truncate text-base font-semibold text-slate-900"></h3>
                    <span id="eqAssetModal_status_badge" class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"></span>
                </div>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    <span id="eqAssetModal_category_badge" class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600"></span>
                    <span id="eqAssetModal_placement_badge" class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"></span>
                    <span id="eqAssetModal_condition_badge" class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"></span>
                </div>
            </div>
        </div>

        <div class="mt-4 space-y-2.5">
            <div class="flex items-center gap-2.5 text-sm text-slate-600">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                    <i data-lucide="tag" class="h-3.5 w-3.5"></i>
                </span>
                <span id="eqAssetModal_meta_tag" class="min-w-0 flex-1 truncate font-medium text-slate-800">—</span>
                <button
                    type="button"
                    id="eqAssetModal_copy_tag"
                    onclick="copyEquipmentAssetTag()"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Copy asset tag"
                >
                    <i data-lucide="copy" class="h-3.5 w-3.5"></i>
                </button>
            </div>
            <div class="flex items-center gap-2.5 text-sm text-slate-600">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                    <i data-lucide="barcode" class="h-3.5 w-3.5"></i>
                </span>
                <span id="eqAssetModal_meta_serial" class="min-w-0 truncate font-medium text-slate-800">—</span>
            </div>
            <div class="flex items-center gap-2.5 text-sm text-slate-600">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                    <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>
                </span>
                <span id="eqAssetModal_meta_room" class="min-w-0 truncate font-medium text-slate-800">—</span>
            </div>
        </div>
    </div>

    <div class="shrink-0 border-b border-slate-100 px-6">
        <div class="flex gap-6" role="tablist" aria-label="Asset detail sections">
            <button
                type="button"
                id="eqAssetModal_tab_overview"
                onclick="switchEquipmentModalTab('overview')"
                class="eq-asset-tab border-b-2 border-[#0025cc] pb-3 pt-4 text-sm font-semibold text-[#0025cc]"
                role="tab"
                aria-selected="true"
            >
                Overview
            </button>
            <button
                type="button"
                id="eqAssetModal_tab_lifecycle"
                onclick="switchEquipmentModalTab('lifecycle')"
                class="eq-asset-tab border-b-2 border-transparent pb-3 pt-4 text-sm font-medium text-slate-500 hover:text-slate-800"
                role="tab"
                aria-selected="false"
            >
                Lifecycle
            </button>
            <button
                type="button"
                id="eqAssetModal_tab_activity"
                onclick="switchEquipmentModalTab('activity')"
                class="eq-asset-tab border-b-2 border-transparent pb-3 pt-4 text-sm font-medium text-slate-500 hover:text-slate-800"
                role="tab"
                aria-selected="false"
            >
                Activity
            </button>
        </div>
    </div>

    <div class="eq-drawer-scroll min-h-0 flex-1 overflow-y-auto px-6 py-5">
        <div id="eqAssetModal_panel_overview" class="eq-asset-panel space-y-5">
            <div>
                <h4 class="text-sm font-semibold text-slate-900">Asset information</h4>
                <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3.5">
                        <span class="text-sm text-slate-500">Brand</span>
                        <span id="eqAssetModal_brand" class="text-right text-sm font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3.5">
                        <span class="text-sm text-slate-500">Model</span>
                        <span id="eqAssetModal_model" class="text-right text-sm font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3.5">
                        <span class="text-sm text-slate-500">Category</span>
                        <span id="eqAssetModal_category" class="text-right text-sm font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3.5">
                        <span class="text-sm text-slate-500">Qty / Mode</span>
                        <span class="text-right text-sm font-medium text-slate-800">
                            <span id="eqAssetModal_quantity">1</span>
                            <span class="text-slate-300">·</span>
                            <span id="eqAssetModal_tracking_mode">Individual</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3.5">
                        <span class="text-sm text-slate-500">Condition</span>
                        <span id="eqAssetModal_condition" class="text-right text-sm font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3.5">
                        <span class="text-sm text-slate-500">Status</span>
                        <span id="eqAssetModal_status" class="text-right text-sm font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3.5">
                        <span class="text-sm text-slate-500">Warranty</span>
                        <span class="inline-flex items-center gap-1.5 text-right text-sm font-medium text-slate-800">
                            <i data-lucide="calendar" class="h-3.5 w-3.5 text-slate-400"></i>
                            <span id="eqAssetModal_warranty">—</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-4 py-3.5">
                        <span class="text-sm text-slate-500">Received</span>
                        <span class="inline-flex items-center gap-1.5 text-right text-sm font-medium text-slate-800">
                            <i data-lucide="calendar-clock" class="h-3.5 w-3.5 text-slate-400"></i>
                            <span id="eqAssetModal_acquired_date">—</span>
                        </span>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-slate-900">Location</h4>
                <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3.5">
                        <span class="text-sm text-slate-500">Room</span>
                        <span id="eqAssetModal_room" class="text-right text-sm font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-4 py-3.5">
                        <span class="text-sm text-slate-500">Zone</span>
                        <span id="eqAssetModal_zone" class="text-right text-sm font-medium text-slate-800">—</span>
                    </div>
                </div>
            </div>
        </div>

        <div id="eqAssetModal_panel_lifecycle" class="eq-asset-panel hidden space-y-4">
            <h4 class="text-sm font-semibold text-slate-900">Lifecycle</h4>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div id="eqAssetModal_lifecycle_loading" class="hidden px-4 py-6 text-center text-sm text-slate-400">Loading history…</div>
                <div id="eqAssetModal_lifecycle_content" class="hidden divide-y divide-slate-100"></div>
                <p id="eqAssetModal_lifecycle_empty" class="hidden px-4 py-6 text-center text-sm text-slate-400">Lifecycle history unavailable.</p>
            </div>
        </div>

        <div id="eqAssetModal_panel_activity" class="eq-asset-panel hidden space-y-4">
            <h4 class="text-sm font-semibold text-slate-900">Recent activity</h4>
            <div id="eqAssetModal_activity_loading" class="hidden rounded-xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-400">Loading activity…</div>
            <div id="eqAssetModal_activity_content" class="hidden space-y-4"></div>
            <p id="eqAssetModal_activity_empty" class="hidden rounded-xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-400">No recent activity.</p>
        </div>
    </div>

    <div class="shrink-0 border-t border-slate-100 px-6 py-4">
        <a
            id="eqAssetModal_profile_link"
            href="#"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800"
        >
            <i data-lucide="external-link" class="h-4 w-4"></i>
            Open full profile
        </a>
    </div>
</div>
