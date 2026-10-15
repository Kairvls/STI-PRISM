{{-- borrowReadOnly hides the Return button; borrowProfileUrl (with __ID__) overrides the full profile link. --}}
@php
    $borrowReadOnly = $borrowReadOnly ?? false;
    $borrowProfileUrl = $borrowProfileUrl ?? null;
@endphp
    <!-- ===================================================== -->
    <!-- VIEW MODAL — slide-out details panel -->
    <!-- ===================================================== -->

    <div
        id="viewModal"
        class="fixed inset-0 z-[11000] hidden"
        onclick="if (event.target === this) closeViewModal()"
    >
        <div class="absolute inset-0 bg-gray-950/40"></div>

        <aside
            id="viewModalPanel"
            class="absolute inset-y-0 right-0 rounded-l-2xl flex h-full w-full max-w-md translate-x-full flex-col bg-white shadow-2xl transition-transform duration-300 ease-out sm:max-w-lg"
        >
            {{-- Header --}}
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">
                        Borrowing Details
                    </h2>
                    <p
                        id="viewSubtitle"
                        class="mt-1 text-sm text-slate-500"
                    >
                        Review borrowing record information.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="closeViewModal()"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            {{-- Scrollable body --}}
            <div class="min-h-0 flex-1 overflow-y-auto">
                {{-- Profile summary --}}
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-start gap-4">
                        <div
                            id="viewAvatar"
                            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-orange-100 text-lg font-semibold text-orange-700"
                        >
                            —
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3
                                    id="viewBorrowerName"
                                    class="truncate text-base font-semibold text-slate-950"
                                >
                                    —
                                </h3>
                                <span
                                    id="viewStatusBadge"
                                    class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600"
                                >
                                    —
                                </span>
                                <span
                                    id="viewDepartmentBadge"
                                    class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700"
                                >
                                    —
                                </span>
                            </div>

                            <div class="mt-3 space-y-2">
                                <div class="flex items-center gap-2 text-sm text-slate-600">
                                    <i data-lucide="monitor" class="h-3.5 w-3.5 shrink-0 text-sky-600"></i>
                                    <span id="viewEquipmentMeta" class="truncate">—</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-slate-600">
                                    <i data-lucide="map-pin" class="h-3.5 w-3.5 shrink-0 text-emerald-600"></i>
                                    <span id="viewDestinationMeta" class="truncate">—</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-slate-600">
                                    <i data-lucide="shield-check" class="h-3.5 w-3.5 shrink-0 text-violet-600"></i>
                                    <span class="truncate">
                                        Authorized by:
                                        <span id="viewAuthorizedMeta" class="font-medium text-slate-800">—</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="border-b border-slate-200 px-6">
                    <nav class="flex gap-6" aria-label="Borrowing details tabs">
                        <button
                            type="button"
                            data-view-tab="overview"
                            onclick="setViewTab('overview')"
                            class="view-tab relative -mb-px border-b-2 border-slate-950 py-3 text-sm font-semibold text-slate-950"
                        >
                            Overview
                        </button>
                        <button
                            type="button"
                            data-view-tab="equipment"
                            onclick="setViewTab('equipment')"
                            class="view-tab relative -mb-px border-b-2 border-transparent py-3 text-sm font-medium text-slate-500 transition hover:text-slate-800"
                        >
                            Equipment
                        </button>
                        <button
                            type="button"
                            data-view-tab="schedule"
                            onclick="setViewTab('schedule')"
                            class="view-tab relative -mb-px border-b-2 border-transparent py-3 text-sm font-medium text-slate-500 transition hover:text-slate-800"
                        >
                            Schedule
                        </button>
                        <button
                            type="button"
                            data-view-tab="notes"
                            onclick="setViewTab('notes')"
                            class="view-tab relative -mb-px border-b-2 border-transparent py-3 text-sm font-medium text-slate-500 transition hover:text-slate-800"
                        >
                            Notes
                        </button>
                    </nav>
                </div>

                {{-- Tab panels --}}
                <div class="px-6 py-5">
                    <div id="viewTabOverview" class="view-tab-panel space-y-4">
                        <h4 class="text-sm font-semibold text-slate-800">
                            Borrowing Information
                        </h4>
                        <div
                            id="viewOverviewRows"
                            class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white"
                        ></div>
                    </div>

                    <div id="viewTabEquipment" class="view-tab-panel hidden space-y-5">
                        <div id="viewEquipmentLoading" class="py-10 text-center text-sm text-slate-400">Loading equipment profile…</div>
                        <div id="viewEquipmentError" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
                        <div id="viewEquipmentContent" class="hidden space-y-5">
                            <div>
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <h4 class="text-sm font-semibold text-slate-800">Equipment profile</h4>
                                    <a id="viewEquipmentProfileLink" href="#" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs font-semibold text-[#0025cc] hover:underline">
                                        Open full profile <i data-lucide="external-link" class="h-3 w-3"></i>
                                    </a>
                                </div>
                                <div id="viewEquipmentRows" class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white"></div>
                            </div>
                            <div>
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <h4 class="text-sm font-semibold text-slate-800">Lifecycle timeline</h4>
                                    <span id="viewEquipmentTimelineNote" class="text-[11px] text-slate-400"></span>
                                </div>
                                <div id="viewEquipmentStages" class="mb-3 flex flex-wrap gap-2"></div>
                                <div id="viewEquipmentTimeline" class="space-y-0"></div>
                                <button id="viewEquipmentTimelineToggle" type="button" onclick="toggleBorrowTimeline()" class="mt-3 hidden items-center gap-1.5 text-sm font-semibold text-[#0025cc] hover:underline">
                                    <span id="viewEquipmentTimelineToggleIcon" class="inline-flex transition-transform">
                                        <i data-lucide="chevron-down" class="h-4 w-4"></i>
                                    </span>
                                    <span id="viewEquipmentTimelineToggleLabel">Show older events</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="viewTabSchedule" class="view-tab-panel hidden space-y-4">
                        <h4 class="text-sm font-semibold text-slate-800">
                            Schedule
                        </h4>
                        <div
                            id="viewScheduleRows"
                            class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white"
                        ></div>
                    </div>

                    <div id="viewTabNotes" class="view-tab-panel hidden space-y-4">
                        <h4 class="text-sm font-semibold text-slate-800">
                            Purpose & Remarks
                        </h4>
                        <div
                            id="viewNotesRows"
                            class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white"
                        ></div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button
                    type="button"
                    onclick="closeViewModal()"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Close
                </button>

                @unless ($borrowReadOnly)
                    <button
                        type="button"
                        id="viewReturnButton"
                        class="hidden inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="archive-restore" class="h-4 w-4"></i>
                        Return equipment
                    </button>
                @endunless
            </div>
        </aside>
    </div>

<script>
        function escapeHtml(value) {
            return String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#39;");
        }

        function getBorrowerInitials(name) {
            const parts = String(name || "")
                .trim()
                .split(/\s+/)
                .filter(Boolean);

            if (!parts.length) return "?";
            if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();

            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }

        function formatViewDate(value) {
            if (!value || value === "null" || value === "undefined") return "—";

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;

            return date.toLocaleDateString("en-US", {
                month: "short",
                day: "numeric",
                year: "numeric",
            });
        }

        function statusBadgeClass(status) {
            switch (status) {
                case "Borrowed":
                    return "bg-sky-50 text-sky-700";
                case "Returned":
                    return "bg-emerald-50 text-emerald-700";
                case "Overdue":
                    return "bg-rose-50 text-rose-700";
                default:
                    return "bg-slate-100 text-slate-600";
            }
        }

        function detailRow(label, valueHtml) {
            return `
                <div class="flex items-start justify-between gap-6 px-4 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">${label}</span>
                    <div class="min-w-0 text-right text-sm font-medium text-slate-900">
                        ${valueHtml}
                    </div>
                </div>
            `;
        }

        function setViewTab(tab) {
            const panels = {
                overview: document.getElementById("viewTabOverview"),
                equipment: document.getElementById("viewTabEquipment"),
                schedule: document.getElementById("viewTabSchedule"),
                notes: document.getElementById("viewTabNotes"),
            };

            Object.keys(panels).forEach(function (key) {
                panels[key].classList.toggle("hidden", key !== tab);
            });

            document.querySelectorAll(".view-tab").forEach(function (button) {
                const active = button.getAttribute("data-view-tab") === tab;
                button.classList.toggle("border-slate-950", active);
                button.classList.toggle("text-slate-950", active);
                button.classList.toggle("font-semibold", active);
                button.classList.toggle("border-transparent", !active);
                button.classList.toggle("text-slate-500", !active);
                button.classList.toggle("font-medium", !active);
            });
        }

        function viewBorrowing(record) {
            const recordId = record.id;
            const quantity = record.quantity;
            const borrowDate = record.borrowDate;
            const expectedReturn = record.expectedReturn;
            const actualReturn = record.actualReturn;
            const safeBorrower = record.borrower || "Unknown borrower";
            const safeEquipment = record.equipment || "—";
            const safeDepartment = record.department || "No department";
            const safeDestination = record.destination || "—";
            const safeAuthorized = record.authorized || "—";
            const safeStatus = record.status || "Unknown";
            const safePurpose = record.purpose || "—";
            const safeRemarks = record.remarks || "—";
            const isOut = safeStatus === "Borrowed" || safeStatus === "Overdue";

            document.getElementById("viewSubtitle").textContent =
                "Review " + safeBorrower + "'s borrowing record.";
            document.getElementById("viewBorrowerName").textContent = safeBorrower;
            document.getElementById("viewAvatar").textContent =
                getBorrowerInitials(safeBorrower);
            document.getElementById("viewEquipmentMeta").textContent = safeEquipment;
            document.getElementById("viewDestinationMeta").textContent =
                safeDestination;
            document.getElementById("viewAuthorizedMeta").textContent =
                safeAuthorized;

            const statusBadge = document.getElementById("viewStatusBadge");
            statusBadge.textContent = safeStatus;
            statusBadge.className =
                "inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold " +
                statusBadgeClass(safeStatus);

            document.getElementById("viewDepartmentBadge").textContent =
                safeDepartment;

            document.getElementById("viewOverviewRows").innerHTML = [
                detailRow("Equipment", escapeHtml(safeEquipment) + (record.assetTag ? '<span class="block text-xs font-normal text-slate-500">' + escapeHtml(record.assetTag) + "</span>" : "")),
                detailRow("Taken from", escapeHtml(record.fromRoom || "—")),
                detailRow(
                    "Location now",
                    isOut
                        ? '<span class="text-sky-700">Off campus, with the borrower</span>'
                        : escapeHtml(record.returnedTo ? "Returned to " + record.returnedTo : "Returned"),
                ),
                detailRow("Borrower", escapeHtml(safeBorrower)),
                detailRow("Department", escapeHtml(safeDepartment)),
                detailRow(
                    "Quantity",
                    '<span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2.5 py-1 text-sm font-semibold text-slate-700">' +
                        escapeHtml(quantity || "—") +
                        "</span>"
                ),
                detailRow("Destination", escapeHtml(safeDestination)),
                detailRow("Authorized by", escapeHtml(safeAuthorized)),
                detailRow(
                    "Status",
                    '<span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium ' +
                        statusBadgeClass(safeStatus) +
                        '">' +
                        escapeHtml(safeStatus) +
                        "</span>"
                ),
            ].join("");

            document.getElementById("viewScheduleRows").innerHTML = [
                detailRow(
                    "Borrow date",
                    '<span class="inline-flex items-center gap-1.5"><i data-lucide="calendar" class="h-3.5 w-3.5 text-sky-600"></i>' +
                        escapeHtml(formatViewDate(borrowDate)) +
                        "</span>"
                ),
                detailRow(
                    "Expected return",
                    '<span class="inline-flex items-center gap-1.5"><i data-lucide="clock" class="h-3.5 w-3.5 text-amber-600"></i>' +
                        escapeHtml(formatViewDate(expectedReturn)) +
                        "</span>"
                ),
                detailRow(
                    "Actual return",
                    actualReturn
                        ? '<span class="inline-flex items-center gap-1.5"><i data-lucide="check-circle-2" class="h-3.5 w-3.5 text-emerald-600"></i>' +
                            escapeHtml(formatViewDate(actualReturn)) +
                            "</span>"
                        : '<span class="' + (safeStatus === "Overdue" ? "font-semibold text-rose-600" : "text-slate-500") + '">Not returned yet</span>'
                ),
                detailRow("Condition when lent", escapeHtml(record.conditionOut || "—")),
                detailRow("Condition on return", escapeHtml(record.returnCondition || "—")),
                detailRow("Returned to", escapeHtml(record.returnedTo || "—")),
                detailRow("Received back by", escapeHtml(record.returnedBy || "—")),
            ].join("");

            document.getElementById("viewNotesRows").innerHTML = [
                detailRow(
                    "Purpose",
                    '<span class="whitespace-pre-wrap text-slate-700">' +
                        escapeHtml(safePurpose) +
                        "</span>"
                ),
                detailRow(
                    "Remarks",
                    '<span class="whitespace-pre-wrap text-slate-700">' +
                        escapeHtml(safeRemarks) +
                        "</span>"
                ),
                detailRow(
                    "Return remarks",
                    '<span class="whitespace-pre-wrap text-slate-700">' +
                        escapeHtml(record.returnRemarks || "—") +
                        "</span>"
                ),
            ].join("");

            loadBorrowEquipment(record.equipmentId);

            const returnButton = document.getElementById("viewReturnButton");
            if (returnButton) {
                returnButton.classList.toggle("hidden", !isOut);
                returnButton.onclick = isOut
                    ? function () {
                        closeViewModal();
                        openReturnModal(recordId, safeEquipment);
                    }
                    : null;
            }

            setViewTab("overview");

            const modal = document.getElementById("viewModal");
            const panel = document.getElementById("viewModalPanel");

            modal.classList.remove("hidden");
            document.body.style.overflow = "hidden";

            requestAnimationFrame(function () {
                panel.classList.remove("translate-x-full");
            });

            if (window.lucide) lucide.createIcons();
        }

        const BORROW_STAGE_CLASSES = {
            purchase: "bg-indigo-50 text-indigo-700",
            received: "bg-sky-50 text-sky-700",
            inventory: "bg-slate-100 text-slate-700",
            assigned: "bg-violet-50 text-violet-700",
            borrowed: "bg-cyan-50 text-cyan-700",
            repair: "bg-amber-50 text-amber-700",
            for_replacement: "bg-orange-50 text-orange-700",
            replacement: "bg-emerald-50 text-emerald-700",
            disposal: "bg-rose-50 text-rose-700",
        };
        const BORROW_PROFILE_URL = @json($borrowProfileUrl);
        const BORROW_TIMELINE_LIMIT = 10;
        let borrowTimeline = { events: [], stages: {}, counts: {}, stage: "all", expanded: false, request: 0 };

        function formatMoney(value) {
            if (value === null || value === undefined || value === "") return "—";
            return "₱" + Number(value).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function renderBorrowTimeline() {
            const { events, stages, counts, stage } = borrowTimeline;
            const chip = (key, label, count) => {
                const on = stage === key;
                return `<button type="button" onclick="filterBorrowTimeline('${key}')" class="rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 transition ${on ? "bg-[#0025cc] text-white ring-[#0025cc]" : "bg-slate-50 text-slate-600 ring-slate-200 hover:bg-slate-100"}">${escapeHtml(label)} · ${count}</button>`;
            };

            document.getElementById("viewEquipmentStages").innerHTML = events.length
                ? chip("all", "All", events.length) + Object.keys(stages)
                    .filter((key) => counts[key])
                    .map((key) => chip(key, stages[key], counts[key]))
                    .join("")
                : "";

            const matching = stage === "all" ? events : events.filter((event) => event.stage === stage);
            const expanded = borrowTimeline.expanded;
            const visible = expanded ? matching : matching.slice(0, BORROW_TIMELINE_LIMIT);
            const older = matching.length - BORROW_TIMELINE_LIMIT;

            document.getElementById("viewEquipmentTimelineNote").textContent =
                older > 0 && !expanded ? `Latest ${BORROW_TIMELINE_LIMIT} of ${matching.length}` : "";
            const toggle = document.getElementById("viewEquipmentTimelineToggle");
            toggle.classList.toggle("hidden", older <= 0);
            toggle.classList.toggle("inline-flex", older > 0);
            document.getElementById("viewEquipmentTimelineToggleIcon").classList.toggle("rotate-180", expanded);
            document.getElementById("viewEquipmentTimelineToggleLabel").textContent = expanded
                ? "Show fewer"
                : `Show ${older} older ${older === 1 ? "event" : "events"}`;

            document.getElementById("viewEquipmentTimeline").innerHTML = visible.length
                ? visible.map((event, index) => `
                    <div class="relative flex gap-3 ${index < visible.length - 1 ? "pb-4" : ""}">
                        ${index < visible.length - 1 ? '<span class="absolute left-[5px] top-4 bottom-0 w-px bg-slate-200"></span>' : ""}
                        <span class="relative z-10 mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ring-4 ring-white ${escapeHtml(event.meta?.dot || "bg-slate-400")}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <p class="text-sm font-semibold text-slate-900">${escapeHtml(event.title)}</p>
                                <span class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold ${BORROW_STAGE_CLASSES[event.stage] || "bg-slate-100 text-slate-700"}">${escapeHtml(event.stage_label || event.type_label)}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">${escapeHtml(formatViewDate(event.occurred_at))}</p>
                            ${event.description ? `<p class="mt-1 text-xs leading-5 text-slate-600">${escapeHtml(event.description)}</p>` : ""}
                        </div>
                    </div>`).join("")
                : '<p class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-400">No events for this stage.</p>';
        }

        function filterBorrowTimeline(stage) {
            borrowTimeline.stage = stage;
            borrowTimeline.expanded = false;
            renderBorrowTimeline();
        }

        function toggleBorrowTimeline() {
            const collapsing = borrowTimeline.expanded;
            borrowTimeline.expanded = !collapsing;
            renderBorrowTimeline();
            if (collapsing) {
                document.getElementById("viewEquipmentTimelineToggle").scrollIntoView({ block: "nearest", behavior: "smooth" });
            }
        }

        async function loadBorrowEquipment(equipmentId) {
            const loading = document.getElementById("viewEquipmentLoading");
            const error = document.getElementById("viewEquipmentError");
            const content = document.getElementById("viewEquipmentContent");
            const requestId = ++borrowTimeline.request;

            loading.classList.remove("hidden");
            error.classList.add("hidden");
            content.classList.add("hidden");

            if (!equipmentId) {
                loading.classList.add("hidden");
                error.textContent = "This record is not linked to an equipment item.";
                error.classList.remove("hidden");
                return;
            }

            try {
                const response = await fetch(`/maintenance/equipment/timeline/${equipmentId}`, {
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                });
                if (!response.ok) throw new Error("Could not load the equipment profile.");
                const data = await response.json();
                if (requestId !== borrowTimeline.request) return;

                const p = data.equipment || {};
                const brandModel = [p.brand, p.model].filter(Boolean).join(" ");
                document.getElementById("viewEquipmentRows").innerHTML = [
                    detailRow("Name", escapeHtml(p.name || "—")),
                    detailRow("Asset tag", escapeHtml(p.asset_tag || "—")),
                    detailRow("Category", escapeHtml(p.category || "—")),
                    detailRow("Brand / model", escapeHtml(brandModel || "—")),
                    detailRow("Serial no.", escapeHtml(p.serial_number || "—")),
                    detailRow("Tracking", escapeHtml([p.tracking_mode, p.quantity ? "Qty " + p.quantity : null].filter(Boolean).join(" · ") || "—")),
                    detailRow("Status", escapeHtml(p.inventory_status || "—")),
                    detailRow("Condition", escapeHtml(p.condition_status || "—")),
                    detailRow("Room on record", escapeHtml(p.room_name || "—")),
                    detailRow("Purchase date", escapeHtml(formatViewDate(p.purchase_date))),
                    detailRow("Purchase cost", escapeHtml(formatMoney(p.purchase_cost))),
                    detailRow("Supplier", escapeHtml(p.supplier_name || "—")),
                    detailRow("Received (RR)", escapeHtml(p.receiving_report_number ? "#" + p.receiving_report_number : "—")),
                    detailRow("Added to inventory", escapeHtml(formatViewDate(p.acquired_date || p.created_at))),
                    detailRow("Warranty until", escapeHtml(formatViewDate(p.warranty_expiration))),
                ].join("");
                document.getElementById("viewEquipmentProfileLink").href = BORROW_PROFILE_URL
                    ? BORROW_PROFILE_URL.replace("__ID__", encodeURIComponent(equipmentId))
                    : (p.view_url || `/maintenance/equipment/view/${equipmentId}`);

                borrowTimeline = {
                    events: data.events || [],
                    stages: data.stages || {},
                    counts: data.stage_counts || {},
                    stage: "all",
                    expanded: false,
                    request: borrowTimeline.request,
                };
                renderBorrowTimeline();

                loading.classList.add("hidden");
                content.classList.remove("hidden");
                if (window.lucide) lucide.createIcons();
            } catch (e) {
                if (requestId !== borrowTimeline.request) return;
                loading.classList.add("hidden");
                error.textContent = e.message || "Could not load the equipment profile.";
                error.classList.remove("hidden");
            }
        }

        function closeViewModal() {
            const modal = document.getElementById("viewModal");
            const panel = document.getElementById("viewModalPanel");

            panel.classList.add("translate-x-full");
            document.body.style.overflow = "";

            setTimeout(function () {
                modal.classList.add("hidden");
            }, 280);
        }
</script>
