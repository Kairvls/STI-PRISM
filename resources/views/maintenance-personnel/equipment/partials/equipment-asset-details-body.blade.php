{{-- Right drawer body for All Equipment / Inventory asset details. --}}
<div class="flex min-h-0 flex-1 flex-col overflow-hidden">
    {{-- COMPACT SUMMARY BLOCK --}}
    <div class="shrink-0 border-b border-slate-100 px-5 py-3.5">
        <div class="flex items-center gap-3">
            <button
                type="button"
                id="modal_image_wrap"
                onclick="openEquipmentPhotoViewer(document.getElementById('modal_image')?.src, document.getElementById('eqAssetModal_profile_name')?.textContent)"
                class="hidden group relative h-11 w-11 shrink-0 overflow-hidden rounded-full bg-slate-100 ring-1 ring-slate-200"
                aria-label="View equipment photo fullscreen"
            >
                <img id="modal_image" src="" alt="" class="h-full w-full object-cover">
                <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 transition group-hover:bg-slate-950/40">
                    <i data-lucide="expand" class="h-3.5 w-3.5 text-white opacity-0 transition group-hover:opacity-100"></i>
                </span>
            </button>
            <div
                id="modal_layout_icon_wrap"
                class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-50 ring-1 ring-slate-200/80"
                title="No image yet"
            >
                <span id="modal_layout_icon" class="inline-flex h-5 w-5 items-center justify-center [&_svg]:h-full [&_svg]:w-full"></span>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-1.5">
                    <h3 id="eqAssetModal_profile_name" class="truncate text-sm font-semibold text-slate-900"></h3>
                    <span id="eqAssetModal_status_badge" class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"></span>
                </div>
                <div class="mt-1 flex flex-wrap gap-1">
                    <span id="eqAssetModal_category_badge" class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600"></span>
                    <span id="eqAssetModal_placement_badge" class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"></span>
                    <span id="eqAssetModal_condition_badge" class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"></span>
                </div>
            </div>
        </div>

        <div class="mt-3 space-y-1.5">
            <div class="flex items-center gap-2 text-xs text-slate-600">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-500">
                    <i data-lucide="tag" class="h-3.5 w-3.5"></i>
                </span>
                <div class="group/assettag relative min-w-0 flex-1">
                    <span
                        id="eqAssetModal_meta_tag"
                        class="block cursor-default truncate font-mono text-xs font-medium text-slate-800"
                        title=""
                    >—</span>
                    <details id="eqAssetModal_tag_details" class="group/tags hidden">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 rounded-md border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-800 [&::-webkit-details-marker]:hidden">
                            <span id="eqAssetModal_tag_summary" class="truncate">—</span>
                            <i data-lucide="chevron-down" class="h-3.5 w-3.5 shrink-0 text-slate-400 transition group-open/tags:rotate-180"></i>
                        </summary>
                        <ul id="eqAssetModal_tag_list" class="mt-1 max-h-32 space-y-1 overflow-y-auto overscroll-contain rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] leading-4 text-slate-700"></ul>
                    </details>
                    <div
                        id="eqAssetModal_meta_tag_tip"
                        role="tooltip"
                        class="pointer-events-none absolute left-0 top-full z-[60] mt-1.5 hidden max-w-[min(22rem,calc(100vw-3rem))] whitespace-normal break-all rounded-lg bg-[#0f172a] px-2.5 py-2 text-[11px] font-medium leading-snug text-slate-100 shadow-[0_12px_28px_rgba(15,23,42,0.35)] group-hover/assettag:block"
                    ></div>
                </div>
                <button
                    type="button"
                    id="eqAssetModal_copy_tag"
                    onclick="copyEquipmentAssetTag()"
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Copy asset tag"
                    title="Copy asset tag"
                >
                    <i data-lucide="copy" class="h-3.5 w-3.5"></i>
                </button>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-600">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-500">
                    <i data-lucide="barcode" class="h-3.5 w-3.5"></i>
                </span>
                <span id="eqAssetModal_meta_serial" class="min-w-0 truncate font-medium text-slate-800">—</span>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-600">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-500">
                    <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>
                </span>
                <span id="eqAssetModal_meta_room" class="min-w-0 truncate font-medium text-slate-800">—</span>
            </div>
        </div>
    </div>

    {{-- COMPACT TABS --}}
    <div class="shrink-0 border-b border-slate-100 px-5">
        <div class="flex gap-5" role="tablist" aria-label="Asset detail sections">
            <button
                type="button"
                id="eqAssetModal_tab_overview"
                onclick="switchEquipmentModalTab('overview')"
                class="eq-asset-tab border-b-2 border-[#0025cc] py-2.5 text-xs font-semibold text-[#0025cc] transition"
                role="tab"
                aria-selected="true"
            >
                Overview
            </button>
            <button
                type="button"
                id="eqAssetModal_tab_lifecycle"
                onclick="switchEquipmentModalTab('lifecycle')"
                class="eq-asset-tab border-b-2 border-transparent py-2.5 text-xs font-medium text-slate-500 transition hover:text-slate-800"
                role="tab"
                aria-selected="false"
            >
                Lifecycle
            </button>
            <button
                type="button"
                id="eqAssetModal_tab_activity"
                onclick="switchEquipmentModalTab('activity')"
                class="eq-asset-tab border-b-2 border-transparent py-2.5 text-xs font-medium text-slate-500 transition hover:text-slate-800"
                role="tab"
                aria-selected="false"
            >
                Activity
            </button>
        </div>
    </div>

    {{-- SCROLLABLE DRAWER BODY --}}
    <div class="eq-drawer-scroll min-h-0 flex-1 overflow-y-auto px-5 py-3.5">
        <div id="eqAssetModal_panel_overview" class="eq-asset-panel space-y-3.5">
            <div>
                <h4 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Asset information</h4>
                <div class="mt-2 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Brand</span>
                        <span id="eqAssetModal_brand" class="truncate text-right text-xs font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Model</span>
                        <span id="eqAssetModal_model" class="truncate text-right text-xs font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Serial</span>
                        <span id="eqAssetModal_serial" class="max-w-[60%] truncate text-right text-xs font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Classification</span>
                        <span id="eqAssetModal_category" class="truncate text-right text-xs font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Qty / Mode</span>
                        <span class="text-right text-xs font-medium text-slate-800">
                            <span id="eqAssetModal_quantity">1</span>
                            <span class="text-slate-300">·</span>
                            <span id="eqAssetModal_tracking_mode">Individual</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Condition</span>
                        <span id="eqAssetModal_condition" class="text-right text-xs font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Status</span>
                        <span id="eqAssetModal_status" class="text-right text-xs font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Purchased</span>
                        <span class="inline-flex items-center gap-1.5 text-right text-xs font-medium text-slate-800">
                            <i data-lucide="calendar" class="h-3.5 w-3.5 text-slate-400"></i>
                            <span id="eqAssetModal_purchase_date">—</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Purchase cost</span>
                        <span id="eqAssetModal_purchase_cost" class="text-right text-xs font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Useful life</span>
                        <span id="eqAssetModal_useful_life" class="text-right text-xs font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Warranty</span>
                        <span class="inline-flex max-w-[60%] items-center gap-1.5 text-right text-xs font-medium text-slate-800">
                            <i data-lucide="shield" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                            <span id="eqAssetModal_warranty">—</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Added / received</span>
                        <span class="inline-flex items-center gap-1.5 text-right text-xs font-medium text-slate-800">
                            <i data-lucide="calendar-clock" class="h-3.5 w-3.5 text-slate-400"></i>
                            <span id="eqAssetModal_acquired_date">—</span>
                        </span>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Procurement</h4>
                <div class="mt-2 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Supplier</span>
                        <span id="eqAssetModal_supplier" class="max-w-[60%] truncate text-right text-xs font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Receiving report</span>
                        <span id="eqAssetModal_rr" class="max-w-[60%] truncate text-right text-xs font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">ATP / RIS</span>
                        <span id="eqAssetModal_atp_ris" class="max-w-[60%] truncate text-right text-xs font-medium text-slate-800">—</span>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Location</h4>
                <div class="mt-2 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Room</span>
                        <span id="eqAssetModal_room" class="truncate text-right text-xs font-medium text-slate-800">—</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3.5 py-2">
                        <span class="shrink-0 text-xs text-slate-500">Zone</span>
                        <span id="eqAssetModal_zone" class="truncate text-right text-xs font-medium text-slate-800">—</span>
                    </div>
                </div>
            </div>
        </div>

        <div id="eqAssetModal_panel_lifecycle" class="eq-asset-panel hidden space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h4 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Lifecycle timeline</h4>
                <span id="eqAssetModal_lifecycle_note" class="text-[11px] text-slate-400"></span>
            </div>
            <div id="eqAssetModal_lifecycle_loading" class="hidden rounded-xl border border-slate-200 bg-white px-3.5 py-5 text-center text-xs text-slate-400">Loading history…</div>
            <div id="eqAssetModal_lifecycle_content" class="hidden space-y-3">
                <div id="eqAssetModal_lifecycle_stages" class="flex flex-wrap gap-1.5"></div>
                <div id="eqAssetModal_lifecycle_timeline"></div>
                <button id="eqAssetModal_lifecycle_more" type="button" onclick="toggleEquipmentLifecycle()" class="hidden items-center gap-1.5 text-xs font-semibold text-[#0025cc] hover:underline">
                    <span id="eqAssetModal_lifecycle_more_icon" class="inline-flex transition-transform">
                        <i data-lucide="chevron-down" class="h-3.5 w-3.5"></i>
                    </span>
                    <span id="eqAssetModal_lifecycle_more_label">Show older events</span>
                </button>
            </div>
            <p id="eqAssetModal_lifecycle_empty" class="hidden rounded-xl border border-slate-200 bg-white px-3.5 py-5 text-center text-xs text-slate-400">Lifecycle history unavailable.</p>
        </div>

        <div id="eqAssetModal_panel_activity" class="eq-asset-panel hidden space-y-3">
            <h4 class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Recent activity</h4>
            <div id="eqAssetModal_activity_loading" class="hidden rounded-xl border border-slate-200 bg-white px-3.5 py-5 text-center text-xs text-slate-400">Loading activity…</div>
            <div id="eqAssetModal_activity_content" class="hidden space-y-3"></div>
            <p id="eqAssetModal_activity_empty" class="hidden rounded-xl border border-slate-200 bg-white px-3.5 py-5 text-center text-xs text-slate-400">No recent activity.</p>
        </div>
    </div>

    {{-- COMPACT FOOTER ACTION BAR --}}
    <div class="shrink-0 border-t border-slate-200/80 bg-slate-50/70 px-5 py-2.5">
        <div class="flex items-center justify-end gap-2">
            <button
                type="button"
                onclick="closeEquipmentModal()"
                class="inline-flex h-8 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
            >
                Close
            </button>
            <a
                id="eqAssetModal_profile_link"
                href="#"
                class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-[#0025cc] px-3.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800"
            >
                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                <span>Open full profile</span>
            </a>
            <div id="eqAssetModal_unit_chooser" class="relative hidden">
                <button
                    type="button"
                    id="eqAssetModal_unit_chooser_toggle"
                    onclick="toggleEquipmentUnitChooser()"
                    class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-[#0025cc] px-3.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800"
                    aria-expanded="false"
                    aria-controls="eqAssetModal_unit_chooser_list"
                >
                    <i data-lucide="list" class="h-3.5 w-3.5"></i>
                    <span>Choose a unit</span>
                    <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition" data-unit-chevron></i>
                </button>
                <ul
                    id="eqAssetModal_unit_chooser_list"
                    class="absolute bottom-full right-0 z-20 mb-1.5 hidden max-h-40 w-60 space-y-1 overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg shadow-slate-950/10"
                ></ul>
            </div>
        </div>
    </div>
</div>
