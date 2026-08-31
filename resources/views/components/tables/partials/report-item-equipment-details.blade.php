@php
    $compact = (bool) ($compact ?? false);
    $equipmentId = (int) ($item->report_item_equipment_id ?? 0);
    $isListed = $equipmentId > 0;
    $cardId = 'eq-card-'.($item->report_item_id ?? $equipmentId).'-'.($compact ? 'c' : 'f');
    $formatDate = function ($value) {
        if (empty($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('M d, Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };
    $na = function ($value) {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : '—';
    };

    $trackingMode = $item->equipment_tracking_mode ?? 'Individual';
    $zone = $item->equipment_placement_zone ?? $item->equipment_current_location ?? null;
    $qtyMode = trim((string) ($item->equipment_quantity ?? '1')).' · '.$trackingMode;

    $summaryRows = [
        ['label' => 'Brand', 'value' => $item->equipment_brand_name ?? null],
        ['label' => 'Model', 'value' => $item->equipment_model ?? null],
        ['label' => 'Serial', 'value' => $item->equipment_serial_number ?? null],
        ['label' => 'Asset tag', 'value' => $item->equipment_asset_tag ?? null],
        ['label' => 'Category', 'value' => $item->equipment_category_name ?? null],
        ['label' => 'Condition', 'value' => $item->equipment_condition_status ?? null],
        ['label' => 'Inventory', 'value' => $item->equipment_inventory_status ?? null],
        ['label' => 'Warranty', 'value' => $formatDate($item->equipment_warranty_expiration ?? null)],
    ];

    if (! $compact) {
        $summaryRows[] = ['label' => 'Qty', 'value' => $item->equipment_quantity ?? null];
        if (isset($item->equipment_is_borrowable)) {
            $summaryRows[] = [
                'label' => 'Borrowable',
                'value' => ((int) $item->equipment_is_borrowable) === 1 ? 'Yes' : 'No',
            ];
        }
    }

    $filledSummaryRows = collect($summaryRows)->filter(
        fn ($row) => trim((string) ($row['value'] ?? '')) !== ''
    )->values();

    $fullProfileRows = [
        ['label' => 'Brand', 'value' => $na($item->equipment_brand_name ?? null)],
        ['label' => 'Model', 'value' => $na($item->equipment_model ?? null)],
        ['label' => 'Serial number', 'value' => $na($item->equipment_serial_number ?? null)],
        ['label' => 'Asset tag', 'value' => $na($item->equipment_asset_tag ?? null), 'wide' => true],
        ['label' => 'Category', 'value' => $na($item->equipment_category_name ?? null)],
        ['label' => 'Qty / Mode', 'value' => $na($qtyMode)],
        ['label' => 'Condition', 'value' => $na($item->equipment_condition_status ?? null)],
        ['label' => 'Inventory status', 'value' => $na($item->equipment_inventory_status ?? null)],
        ['label' => 'Warranty expiration', 'value' => $na($formatDate($item->equipment_warranty_expiration ?? null))],
        ['label' => 'Received', 'value' => $na($formatDate($item->equipment_acquired_date ?? null))],
        ['label' => 'Room', 'value' => $na($item->room_name ?? null)],
        ['label' => 'Zone', 'value' => $na($zone)],
        [
            'label' => 'Borrowable',
            'value' => isset($item->equipment_is_borrowable)
                ? (((int) $item->equipment_is_borrowable) === 1 ? 'Yes' : 'No')
                : '—',
        ],
    ];

    $purchased = $formatDate($item->equipment_purchase_date ?? null);
    if ($purchased) {
        $fullProfileRows[] = ['label' => 'Purchased', 'value' => $purchased];
    }

    if (isset($item->equipment_purchase_cost) && $item->equipment_purchase_cost !== null && $item->equipment_purchase_cost !== '') {
        $fullProfileRows[] = [
            'label' => 'Purchase cost',
            'value' => is_numeric($item->equipment_purchase_cost)
                ? '₱'.number_format((float) $item->equipment_purchase_cost, 2)
                : (string) $item->equipment_purchase_cost,
        ];
    }

    $isStorageRoom = \App\Support\RoomCategories::isStorageType($item->room_type ?? null);
    $fullProfileRows[] = [
        'label' => 'Placement',
        'value' => $isStorageRoom ? 'Stock' : 'Deployed',
    ];
@endphp

@if (! $isListed)
    <p class="{{ $compact ? 'mt-1 text-[11px] text-slate-400' : 'mt-2 text-xs text-slate-500' }}">
        Manual entry — no inventory record linked.
    </p>
@elseif ($compact)
    @if ($filledSummaryRows->isEmpty())
        <p class="mt-1 text-[11px] text-slate-400">No extra equipment details on file.</p>
    @else
        <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1.5">
            @foreach ($filledSummaryRows as $row)
                <div class="min-w-0">
                    <dt class="text-[10px] uppercase tracking-wide text-slate-400">{{ $row['label'] }}</dt>
                    <dd class="truncate text-[11px] font-medium text-slate-700" title="{{ $na($row['value']) }}">
                        {{ $na($row['value']) }}
                    </dd>
                </div>
            @endforeach
        </dl>
    @endif
@else
    <div
        class="rf-eq-detail-card mt-3"
        data-eq-card="{{ $cardId }}"
        data-equipment-id="{{ $equipmentId }}"
    >
        <div class="rf-eq-tab-carousel mb-3 flex items-center gap-1.5" data-eq-tab-carousel="{{ $cardId }}">
            <button
                type="button"
                class="rf-eq-tab-scroll rf-eq-tab-scroll-left flex h-7 w-7 shrink-0 items-center justify-center self-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 disabled:pointer-events-none"
                onclick="scrollReportEquipmentTabs('{{ $cardId }}', -1)"
                aria-label="Scroll tabs left"
                disabled
            >
                <i data-lucide="chevron-left" class="h-4 w-4"></i>
            </button>
            <div
                class="rf-eq-tab-track flex min-w-0 flex-1 gap-1.5 overflow-x-auto scroll-smooth pb-0.5"
                data-eq-tab-track="{{ $cardId }}"
                onscroll="updateReportEquipmentTabArrows('{{ $cardId }}')"
            >
                <button
                    type="button"
                    data-eq-tab="summary"
                    onclick="switchReportEquipmentPanel('{{ $cardId }}', 'summary')"
                    class="rf-eq-tab inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-900 bg-slate-900 px-2.5 py-1 text-[11px] font-medium text-white"
                >
                    Details
                </button>
                <button
                    type="button"
                    data-eq-tab="profile"
                    onclick="switchReportEquipmentPanel('{{ $cardId }}', 'profile')"
                    class="rf-eq-tab inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50"
                >
                    <i data-lucide="id-card" class="h-3 w-3"></i>
                    Full profile
                </button>
                <button
                    type="button"
                    data-eq-tab="lifecycle"
                    onclick="switchReportEquipmentPanel('{{ $cardId }}', 'lifecycle')"
                    class="rf-eq-tab inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50"
                >
                    <i data-lucide="git-branch" class="h-3 w-3"></i>
                    Lifecycle
                </button>
                <button
                    type="button"
                    data-eq-tab="activity"
                    onclick="switchReportEquipmentPanel('{{ $cardId }}', 'activity')"
                    class="rf-eq-tab inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50"
                >
                    <i data-lucide="activity" class="h-3 w-3"></i>
                    Activity
                </button>
                <button
                    type="button"
                    data-eq-tab="maintenance"
                    onclick="switchReportEquipmentPanel('{{ $cardId }}', 'maintenance')"
                    class="rf-eq-tab inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50"
                >
                    <i data-lucide="history" class="h-3 w-3"></i>
                    Maintenance
                </button>
                <button
                    type="button"
                    data-eq-tab="transfers"
                    onclick="switchReportEquipmentPanel('{{ $cardId }}', 'transfers')"
                    class="rf-eq-tab inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50"
                >
                    <i data-lucide="arrow-left-right" class="h-3 w-3"></i>
                    Transfers
                </button>
            </div>
            <button
                type="button"
                class="rf-eq-tab-scroll rf-eq-tab-scroll-right flex h-7 w-7 shrink-0 items-center justify-center self-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 disabled:pointer-events-none"
                onclick="scrollReportEquipmentTabs('{{ $cardId }}', 1)"
                aria-label="Scroll tabs right"
            >
                <i data-lucide="chevron-right" class="h-4 w-4"></i>
            </button>
        </div>

        <div data-eq-panel="summary">
            @if ($filledSummaryRows->isEmpty())
                <p class="text-xs text-slate-500">No extra equipment details on file.</p>
            @else
                <dl class="grid grid-cols-2 gap-x-3 gap-y-2">
                    @foreach ($filledSummaryRows as $row)
                        <div class="min-w-0 {{ $row['label'] === 'Asset tag' ? 'col-span-2' : '' }}">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                {{ $row['label'] }}
                            </dt>
                            <dd
                                class="{{ $row['label'] === 'Asset tag' ? 'break-all' : 'truncate' }} text-xs font-medium text-slate-800"
                                title="{{ $na($row['value']) }}"
                            >
                                {{ $na($row['value']) }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>

        <div data-eq-panel="profile" class="hidden">
            @if (!empty($item->equipment_image))
                <button
                    type="button"
                    onclick="window.open('{{ asset('storage/'.$item->equipment_image) }}', '_blank')"
                    class="mb-3 block w-full overflow-hidden rounded-lg border border-slate-200"
                >
                    <img
                        src="{{ asset('storage/'.$item->equipment_image) }}"
                        alt="{{ \App\Support\ReportItems::displayName($item) }}"
                        class="max-h-40 w-full object-cover"
                    />
                </button>
            @endif
            <dl class="grid grid-cols-2 gap-x-3 gap-y-2">
                @foreach ($fullProfileRows as $row)
                    <div class="min-w-0 {{ !empty($row['wide']) ? 'col-span-2' : '' }}">
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                            {{ $row['label'] }}
                        </dt>
                        <dd
                            class="{{ !empty($row['wide']) ? 'break-all' : 'truncate' }} text-xs font-medium text-slate-800"
                            title="{{ $row['value'] }}"
                        >
                            {{ $row['value'] }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div
            data-eq-panel="lifecycle"
            class="hidden"
            data-eq-loaded="0"
            data-eq-panel-type="lifecycle"
        >
            <p class="text-xs text-slate-500">Loading lifecycle…</p>
        </div>

        <div
            data-eq-panel="activity"
            class="hidden"
            data-eq-loaded="0"
            data-eq-panel-type="activity"
        >
            <p class="text-xs text-slate-500">Loading activity…</p>
        </div>

        <div
            data-eq-panel="maintenance"
            class="hidden"
            data-eq-loaded="0"
            data-eq-url="/maintenance/equipment/history/{{ $equipmentId }}"
            data-eq-panel-type="maintenance"
        >
            <p class="text-xs text-slate-500">Loading maintenance history…</p>
        </div>

        <div
            data-eq-panel="transfers"
            class="hidden"
            data-eq-loaded="0"
            data-eq-url="/maintenance/equipment/transfers/{{ $equipmentId }}"
            data-eq-panel-type="transfers"
        >
            <p class="text-xs text-slate-500">Loading transfer history…</p>
        </div>
    </div>
@endif

@once
<style>
    .rf-eq-tab-track {
        -ms-overflow-style: none;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }

    .rf-eq-tab-track::-webkit-scrollbar {
        display: none;
    }

    .rf-eq-tab-scroll:disabled {
        cursor: not-allowed;
        opacity: 0.45;
        background: #f8fafc;
        color: #94a3b8;
        border-color: #e2e8f0;
        box-shadow: none;
    }
</style>
<script>
    if (typeof window.updateReportEquipmentTabArrows !== "function") {
        window.updateReportEquipmentTabArrows = function (cardId) {
            const carousel = document.querySelector('[data-eq-tab-carousel="' + cardId + '"]');
            const track = document.querySelector('[data-eq-tab-track="' + cardId + '"]');
            if (!carousel || !track) return;

            const leftBtn = carousel.querySelector(".rf-eq-tab-scroll-left");
            const rightBtn = carousel.querySelector(".rf-eq-tab-scroll-right");
            const maxScroll = track.scrollWidth - track.clientWidth;
            const atStart = track.scrollLeft <= 4;
            const atEnd = track.scrollLeft >= maxScroll - 4;

            if (leftBtn) leftBtn.disabled = atStart || maxScroll <= 0;
            if (rightBtn) rightBtn.disabled = atEnd || maxScroll <= 0;
        };

        window.scrollReportEquipmentTabs = function (cardId, direction) {
            const track = document.querySelector('[data-eq-tab-track="' + cardId + '"]');
            if (!track) return;

            track.scrollBy({
                left: direction * 140,
                behavior: "smooth",
            });

            window.setTimeout(function () {
                window.updateReportEquipmentTabArrows(cardId);
            }, 220);
        };

        window.initReportEquipmentTabCarousels = function () {
            document.querySelectorAll("[data-eq-tab-carousel]").forEach(function (carousel) {
                const cardId = carousel.getAttribute("data-eq-tab-carousel");
                if (cardId) window.updateReportEquipmentTabArrows(cardId);
            });
            if (typeof lucide !== "undefined") lucide.createIcons();
        };

        document.addEventListener("DOMContentLoaded", window.initReportEquipmentTabCarousels);
        document.addEventListener("report-modal-opened", window.initReportEquipmentTabCarousels);
    }

    if (typeof window.formatReportEquipmentDate !== "function") {
        window.formatReportEquipmentDate = function (value) {
            if (!value) return "—";
            const date = new Date(String(value).replace(" ", "T"));
            if (Number.isNaN(date.getTime())) return String(value);
            return date.toLocaleDateString(undefined, {
                year: "numeric",
                month: "short",
                day: "numeric",
            });
        };
    }

    if (typeof window.switchReportEquipmentPanel !== "function") {
        window.switchReportEquipmentPanel = async function (cardId, panel) {
            const card = document.querySelector('[data-eq-card="' + cardId + '"]');
            if (!card) return;

            const activeTab =
                "rf-eq-tab inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-900 bg-slate-900 px-2.5 py-1 text-[11px] font-medium text-white";
            const idleTab =
                "rf-eq-tab inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50";

            let activeButton = null;
            card.querySelectorAll("[data-eq-tab]").forEach((btn) => {
                const isActive = btn.getAttribute("data-eq-tab") === panel;
                btn.className = isActive ? activeTab : idleTab;
                if (isActive) activeButton = btn;
            });

            if (activeButton) {
                activeButton.scrollIntoView({
                    behavior: "smooth",
                    block: "nearest",
                    inline: "center",
                });
                window.setTimeout(function () {
                    window.updateReportEquipmentTabArrows(cardId);
                }, 220);
            }

            card.querySelectorAll("[data-eq-panel]").forEach((el) => {
                el.classList.toggle("hidden", el.getAttribute("data-eq-panel") !== panel);
            });

            if (typeof lucide !== "undefined") lucide.createIcons();

            if (panel === "lifecycle" || panel === "activity") {
                await window.loadReportEquipmentLifecyclePanel(card, panel);
            } else if (panel === "maintenance" || panel === "transfers") {
                await window.loadReportEquipmentPanel(card, panel);
            }
        };

        window.loadReportEquipmentLifecyclePanel = async function (card, panel) {
            const body = card.querySelector('[data-eq-panel="' + panel + '"]');
            if (!body || body.getAttribute("data-eq-loaded") === "1") return;

            const equipmentId = card.getAttribute("data-equipment-id");
            if (!equipmentId) return;

            body.innerHTML =
                '<p class="text-xs text-slate-500">Loading ' +
                (panel === "lifecycle" ? "lifecycle" : "activity") +
                "…</p>";

            try {
                const response = await fetch("/maintenance/equipment/lifecycle/" + equipmentId, {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    credentials: "same-origin",
                });

                const data = await response.json().catch(() => null);

                if (!response.ok || !data) {
                    body.innerHTML = '<p class="text-xs text-rose-600">Could not load lifecycle.</p>';
                    return;
                }

                if (panel === "lifecycle") {
                    const rows = [
                        ["Put in room", window.formatReportEquipmentDate(data.deployed_at)],
                        ["Last moved", window.formatReportEquipmentDate(data.last_moved_at)],
                        ["Last maintenance", window.formatReportEquipmentDate(data.last_maintenance_at)],
                    ];

                    if (data.disposed_at) {
                        rows.push(["Disposed", window.formatReportEquipmentDate(data.disposed_at)]);
                    }
                    if (data.disposal_reason) {
                        rows.push(["Disposal reason", data.disposal_reason]);
                    }

                    body.innerHTML =
                        '<dl class="divide-y divide-slate-100 overflow-hidden rounded-lg border border-slate-200 bg-white">' +
                        rows
                            .map(function (row) {
                                return (
                                    '<div class="flex items-center justify-between gap-3 px-3 py-2.5">' +
                                    '<dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">' +
                                    row[0] +
                                    "</dt>" +
                                    '<dd class="text-right text-xs font-medium text-slate-800">' +
                                    String(row[1] || "—").replace(/</g, "&lt;") +
                                    "</dd>" +
                                    "</div>"
                                );
                            })
                            .join("") +
                        "</dl>";
                } else {
                    const transfers = Array.isArray(data.transfers) ? data.transfers.slice(0, 6) : [];
                    const maintenance = Array.isArray(data.maintenance) ? data.maintenance.slice(0, 6) : [];

                    if (!transfers.length && !maintenance.length) {
                        body.innerHTML = '<p class="text-xs text-slate-500">No recent activity.</p>';
                        body.setAttribute("data-eq-loaded", "1");
                        return;
                    }

                    let html = "";

                    if (transfers.length) {
                        html +=
                            '<div class="mb-3 overflow-hidden rounded-lg border border-slate-200 bg-white">' +
                            '<p class="border-b border-slate-100 px-3 py-2 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Recent moves</p>';
                        transfers.forEach(function (move, index) {
                            html +=
                                '<div class="' +
                                (index ? "border-t border-slate-100 " : "") +
                                'px-3 py-2.5">' +
                                '<p class="text-xs font-medium text-slate-800">' +
                                String(move.from_room_name || "Unassigned").replace(/</g, "&lt;") +
                                ' <span class="text-slate-400">→</span> ' +
                                String(move.to_room_name || "—").replace(/</g, "&lt;") +
                                "</p>" +
                                '<p class="mt-1 text-[10px] text-slate-400">' +
                                window.formatReportEquipmentDate(move.created_at) +
                                "</p>" +
                                "</div>";
                        });
                        html += "</div>";
                    }

                    if (maintenance.length) {
                        html +=
                            '<div class="overflow-hidden rounded-lg border border-slate-200 bg-white">' +
                            '<p class="border-b border-slate-100 px-3 py-2 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Maintenance</p>';
                        maintenance.forEach(function (row, index) {
                            html +=
                                '<div class="' +
                                (index ? "border-t border-slate-100 " : "") +
                                'px-3 py-2.5">' +
                                '<p class="truncate text-xs font-medium text-slate-800">' +
                                String(row.findings || row.status || "Maintenance").replace(/</g, "&lt;") +
                                "</p>" +
                                '<p class="mt-1 text-[10px] text-slate-400">' +
                                window.formatReportEquipmentDate(row.at) +
                                "</p>" +
                                "</div>";
                        });
                        html += "</div>";
                    }

                    body.innerHTML = html;
                }

                body.setAttribute("data-eq-loaded", "1");
            } catch (error) {
                body.innerHTML = '<p class="text-xs text-rose-600">Could not load lifecycle.</p>';
            }
        };

        window.loadReportEquipmentPanel = async function (card, panel) {
            const body = card.querySelector('[data-eq-panel="' + panel + '"]');
            if (!body || body.getAttribute("data-eq-loaded") === "1") return;

            const url = body.getAttribute("data-eq-url");
            if (!url) return;

            body.innerHTML =
                '<p class="text-xs text-slate-500">Loading ' +
                (panel === "maintenance" ? "maintenance" : "transfer") +
                " history…</p>";

            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    credentials: "same-origin",
                });
                const data = await response.json().catch(() => []);

                if (!response.ok || !Array.isArray(data)) {
                    body.innerHTML =
                        '<p class="text-xs text-rose-600">Could not load history.</p>';
                    return;
                }

                if (data.length === 0) {
                    body.innerHTML =
                        '<p class="text-xs text-slate-500">No ' +
                        (panel === "maintenance" ? "maintenance" : "transfer") +
                        " records yet.</p>";
                    body.setAttribute("data-eq-loaded", "1");
                    return;
                }

                if (panel === "maintenance") {
                    body.innerHTML = data
                        .map(function (row) {
                            const status = row.equipment_maintenance_status || "Record";
                            const findings =
                                row.equipment_maintenance_findings ||
                                row.equipment_maintenance_repair_action ||
                                row.equipment_maintenance_replacement_remarks ||
                                "No notes";
                            const when =
                                row.equipment_maintenance_completed_at ||
                                row.equipment_maintenance_created_at ||
                                "";
                            const whenLabel = when
                                ? new Date(when).toLocaleString(undefined, {
                                      year: "numeric",
                                      month: "short",
                                      day: "2-digit",
                                      hour: "numeric",
                                      minute: "2-digit",
                                  })
                                : "—";
                            return (
                                '<div class="mb-2 rounded-lg border border-slate-200 bg-white px-3 py-2 last:mb-0">' +
                                '<div class="flex items-start justify-between gap-2">' +
                                '<p class="text-xs font-semibold text-slate-800">' +
                                String(status).replace(/</g, "&lt;") +
                                "</p>" +
                                '<p class="shrink-0 text-[10px] text-slate-400">' +
                                whenLabel +
                                "</p>" +
                                "</div>" +
                                '<p class="mt-1 text-[11px] leading-5 text-slate-600">' +
                                String(findings).replace(/</g, "&lt;") +
                                "</p>" +
                                "</div>"
                            );
                        })
                        .join("");
                } else {
                    body.innerHTML = data
                        .map(function (row) {
                            const fromRoom = row.from_room_name || "Unknown";
                            const toRoom = row.to_room_name || "Unknown";
                            const remarks = row.remarks || "No remarks";
                            const when = row.created_at || "";
                            const whenLabel = when
                                ? new Date(when).toLocaleString(undefined, {
                                      year: "numeric",
                                      month: "short",
                                      day: "2-digit",
                                      hour: "numeric",
                                      minute: "2-digit",
                                  })
                                : "—";
                            return (
                                '<div class="mb-2 rounded-lg border border-slate-200 bg-white px-3 py-2 last:mb-0">' +
                                '<p class="text-xs font-semibold text-slate-800">' +
                                String(fromRoom).replace(/</g, "&lt;") +
                                ' <span class="font-normal text-slate-400">→</span> ' +
                                String(toRoom).replace(/</g, "&lt;") +
                                "</p>" +
                                '<p class="mt-1 text-[11px] leading-5 text-slate-600">' +
                                String(remarks).replace(/</g, "&lt;") +
                                "</p>" +
                                '<p class="mt-1 text-[10px] text-slate-400">' +
                                whenLabel +
                                "</p>" +
                                "</div>"
                            );
                        })
                        .join("");
                }

                body.setAttribute("data-eq-loaded", "1");
            } catch (error) {
                body.innerHTML =
                    '<p class="text-xs text-rose-600">Could not load history.</p>';
            }
        };
    }
</script>
@endonce
