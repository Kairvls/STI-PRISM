{{--
    Read-only right-side ticket drawers (same UI as Maintenance All Reports).
    Expected: $reports (iterable). Optional: $ticketLinkBase (null = no timeline ticket links).
--}}
@php
    if ($reports instanceof \Illuminate\Contracts\Pagination\Paginator) {
        $drawerReports = collect($reports->items());
    } else {
        $drawerReports = collect($reports ?? []);
    }
    if (! array_key_exists('ticketLinkBase', get_defined_vars())) {
        $ticketLinkBase = url('/maintenance/reports');
    }
@endphp

@foreach ($drawerReports as $report)
    @php
        $statusMap = [
            'Pending' => 'bg-amber-50 text-amber-700',
            'Processing' => 'bg-sky-50 text-sky-700',
            'For Replacement' => 'bg-violet-50 text-violet-700',
            'Resolved' => 'bg-emerald-50 text-emerald-700',
            'Rejected' => 'bg-rose-50 text-rose-700',
            'Closed / Cancelled' => 'bg-slate-200 text-slate-700',
        ];
        $statusPill = $statusMap[$report->report_current_status] ?? 'bg-slate-100 text-slate-600';
        $severityLevel = \App\Support\ReportSeverity::forReport($report);
        $priorityPill = \App\Support\ReportSeverity::meta($severityLevel)['pill'];
        $historyCount = collect($report->report_timeline ?? $report->equipment_report_history ?? [])->count();
        $viewItems = collect($report->report_items ?? []);
        $showItemPhotos = $viewItems->count() > 1
            && $viewItems->contains(fn ($item) => ! empty($item->report_item_uploaded_image));
        $perItemDetails = \App\Support\ReportItems::hasPerItemDetails($viewItems);
        $itemIssueList = $viewItems->count() > 1 ? \App\Support\ReportItems::issueList($viewItems, $report) : [];
        $equipmentLabel = $report->equipment_display
            ?? $report->equipment_name
            ?? ($report->report_unlisted_equipment_name ?? 'Unlisted');
        $reporterInitials = collect(explode(' ', trim($report->reporter_full_name ?? 'R')))
            ->filter()
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');
        $assignedName = $report->assigned_personnel_name ?? $report->assigned_purchaser_name ?? 'Unassigned';
        $detailsUrl = \App\Support\ReportDetailsReturn::detailsUrl((int) $report->report_id);
    @endphp

    <div
        id="view-modal-{{ $report->report_id }}"
        class="fixed inset-0 z-[80] hidden overflow-hidden"
        onclick="closeReportModal('view-modal-{{ $report->report_id }}')"
    >
        <div class="flex h-full justify-end bg-[#0b1220]/70">
            <div
                class="flex h-full w-full max-w-lg flex-col bg-white shadow-2xl sm:rounded-l-2xl"
                onclick="event.stopPropagation()"
            >
                <div class="flex items-start justify-between gap-4 px-6 pt-6">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-slate-800">Ticket details</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Review {{ $equipmentLabel }} in {{ \App\Support\ReportItems::locationLabel($report, 'an unassigned room') }}.
                        </p>
                    </div>
                    <button
                        type="button"
                        onclick="closeReportModal('view-modal-{{ $report->report_id }}')"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <div class="flex items-start gap-4 px-6 pt-6">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-700">
                        {{ $reporterInitials ?: 'R' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-base font-semibold text-slate-900">
                                {{ $report->reporter_full_name ?? 'Unknown reporter' }}
                            </p>
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $statusPill }}">
                                {{ $report->report_current_status }}
                            </span>
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $priorityPill }}">
                                {{ $severityLevel }} priority
                            </span>
                        </div>
                        <div class="mt-2 space-y-1.5 text-sm text-slate-500">
                            <p class="flex items-center gap-2">
                                <i data-lucide="wrench" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                @include('components.tables.partials.more-items-popover', [
                                    'label' => $equipmentLabel,
                                    'items' => $viewItems,
                                ])
                            </p>
                            <p class="flex items-center gap-2">
                                <i data-lucide="map-pin" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                <span class="truncate" title="{{ implode(', ', $report->item_location_names ?? []) }}">{{ \App\Support\ReportItems::locationLabel($report) }}</span>
                            </p>
                            <p class="flex items-center gap-2">
                                <i data-lucide="hash" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                <span>{{ $report->reporter_employee_id ?? $report->report_reporter_employee_id ?? '—' }} · {{ \App\Support\ReportGrouping::ticketCode($report) }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-6 border-b border-slate-200 px-6">
                    <button
                        type="button"
                        id="report-tab-details-{{ $report->report_id }}"
                        onclick="switchReportViewTab({{ $report->report_id }}, 'details')"
                        class="border-b-2 border-[#0037C7] px-1 pb-3 text-sm font-medium text-slate-900"
                    >
                        Overview
                    </button>
                    <button
                        type="button"
                        id="report-tab-history-{{ $report->report_id }}"
                        onclick="switchReportViewTab({{ $report->report_id }}, 'history')"
                        class="border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-slate-400 hover:text-slate-600"
                    >
                        Timeline{{ $historyCount ? ' · '.$historyCount : '' }}
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <div id="report-panel-details-{{ $report->report_id }}">
                        @if ($viewItems->count() > 0)
                            <div
                                id="report-equipment-items-{{ $report->report_id }}"
                                class="rounded-2xl transition ring-0 ring-transparent"
                            >
                            <p class="mb-3 text-sm font-medium text-slate-600">
                                Equipment items{{ $viewItems->count() > 1 ? ' · '.$viewItems->count() : '' }}
                            </p>
                            <ul class="mb-5 space-y-3">
                                @foreach ($viewItems as $item)
                                    @php
                                        $itemStatus = (string) ($item->report_item_status ?? 'Pending');
                                        $itemStatusPill = $statusMap[$itemStatus] ?? 'bg-slate-100 text-slate-600';
                                    @endphp
                                    <li class="rounded-xl border px-4 py-3 {{ ($item->repeat_state ?? null) === 'not_actioned' ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50/80' }}">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="truncate text-base font-semibold text-slate-900">
                                                    {{ \App\Support\ReportItems::displayName($item) }}
                                                </p>
                                                <p class="mt-0.5 text-sm text-slate-500">
                                                    Issue: {{ \App\Support\ReportItems::itemIssue($item) ?? ($report->report_suggested_issue ?? 'None given') }}
                                                </p>
                                                @if ($perItemDetails && ! empty(trim((string) ($item->report_item_problem_description ?? ''))))
                                                    <p class="mt-1 whitespace-pre-wrap text-sm italic leading-6 text-slate-700">“{{ trim($item->report_item_problem_description) }}”</p>
                                                @endif
                                                @if ((int) ($report->extra_location_count ?? 0) > 0 && ! empty($item->room_name))
                                                    <p class="mt-0.5 flex items-center gap-1 text-sm font-medium text-[#0037C7]">
                                                        <i data-lucide="map-pin" class="h-3.5 w-3.5 shrink-0"></i>
                                                        {{ $item->room_name }}
                                                    </p>
                                                @endif
                                            </div>
                                            <span class="inline-flex shrink-0 rounded-md px-2 py-0.5 text-[13px] font-medium {{ $itemStatusPill }}">
                                                {{ $itemStatus }}
                                            </span>
                                        </div>
                                        @include('components.tables.partials.report-item-repeat-badge', ['item' => $item, 'large' => true])
                                        @include('components.tables.partials.report-item-checks', ['item' => $item])
                                        @include('components.tables.partials.report-item-equipment-details', [
                                            'item' => $item,
                                            'compact' => false,
                                        ])
                                        @if ($showItemPhotos)
                                            @if (! empty($item->report_item_uploaded_image))
                                                <button
                                                    type="button"
                                                    onclick="window.open('{{ asset('storage/'.$item->report_item_uploaded_image) }}', '_blank')"
                                                    class="mt-2 block overflow-hidden rounded-lg border border-slate-200"
                                                    title="Open photo"
                                                >
                                                    <img
                                                        src="{{ asset('storage/'.$item->report_item_uploaded_image) }}"
                                                        alt="Photo of {{ \App\Support\ReportItems::displayName($item) }}"
                                                        class="h-20 w-28 object-cover"
                                                    />
                                                </button>
                                            @else
                                                <p class="mt-2 text-sm text-slate-400">No photo</p>
                                            @endif
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            </div>
                        @endif

                        <p class="mb-3 text-sm font-medium text-slate-600">Report information</p>
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            @if (count($itemIssueList) > 1)
                                <div class="border-b border-slate-100 px-4 py-3">
                                    <p class="text-sm text-slate-500">Issues</p>
                                    <ul class="mt-1.5 space-y-1">
                                        @foreach ($itemIssueList as $entry)
                                            <li class="flex items-baseline justify-between gap-4 text-sm">
                                                <span class="min-w-0 truncate text-slate-500" title="{{ $entry['name'] }}">{{ $entry['name'] }}</span>
                                                <span class="shrink-0 text-right font-medium text-slate-800">{{ $entry['issue'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3">
                                    <span class="text-sm text-slate-500">Issue</span>
                                    <span class="text-right text-sm font-medium text-slate-800">{{ $report->issue_display ?? $report->report_suggested_issue ?? 'None given' }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3">
                                <span class="text-sm text-slate-500">Assigned</span>
                                <span class="text-right text-sm font-medium text-slate-800">{{ $assignedName }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3">
                                <span class="text-sm text-slate-500">Filed</span>
                                <span class="text-right text-sm font-medium text-slate-800">
                                    {{ \Carbon\Carbon::parse($report->report_submitted_at)->format('M d, Y h:i A') }}
                                </span>
                            </div>
                            @if (! empty($report->report_preferred_action_date))
                                <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3">
                                    <span class="text-sm text-slate-500">Preferred date</span>
                                    <span class="text-right text-sm font-medium text-slate-800">
                                        {{ \Carbon\Carbon::parse($report->report_preferred_action_date)->format('M d, Y') }}
                                    </span>
                                </div>
                            @endif
                            <div class="px-4 py-3">
                                <p class="text-sm text-slate-500">What happened</p>
                                @if ($perItemDetails && $viewItems->count() > 1)
                                    @php
                                        $describedItems = $viewItems->filter(fn ($item) => trim((string) ($item->report_item_problem_description ?? '')) !== '');
                                    @endphp
                                    <ul class="mt-1.5 space-y-2">
                                        @foreach ($describedItems as $item)
                                            <li class="text-sm leading-6 text-slate-800">
                                                <span class="font-medium text-slate-600">{{ \App\Support\ReportItems::displayName($item) }}:</span>
                                                <span class="whitespace-pre-wrap">{{ trim($item->report_item_problem_description) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="mt-1.5 whitespace-pre-wrap text-sm leading-6 {{ $report->report_problem_description ? 'text-slate-800' : 'text-slate-400' }}">{{ $report->report_problem_description ?? 'No extra details from reporter.' }}</p>
                                @endif
                            </div>
                        </div>

                        @if ($report->report_uploaded_image && ! $showItemPhotos)
                            <button
                                type="button"
                                onclick="window.open('{{ asset('storage/'.$report->report_uploaded_image) }}', '_blank')"
                                class="mt-4 block w-full overflow-hidden rounded-xl border border-slate-200"
                            >
                                <img
                                    src="{{ asset('storage/'.$report->report_uploaded_image) }}"
                                    alt="Report photo"
                                    class="max-h-48 w-full object-cover"
                                />
                            </button>
                        @endif

                        @if ($report->report_current_status === 'Resolved')
                            <div class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                                <p class="text-sm font-medium text-emerald-800">Resolution</p>
                                <p class="mt-1 text-sm leading-6 text-emerald-900/80">{{ $report->report_resolution_notes ?: 'No notes.' }}</p>
                            </div>
                        @endif
                        @if ($report->report_current_status === 'Rejected')
                            <div class="mt-4 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3">
                                <p class="text-sm font-medium text-rose-800">Rejected</p>
                                <p class="mt-1 text-sm leading-6 text-rose-900/80">{{ $report->report_rejection_notes ?: 'No notes.' }}</p>
                            </div>
                        @endif
                        @if ($report->report_current_status === 'For Replacement')
                            <div class="mt-4 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3">
                                <p class="text-sm font-medium text-amber-800">Replacement</p>
                                <p class="mt-1 text-sm leading-6 text-amber-900/80">{{ $report->report_replacement_notes ?: 'No notes.' }}</p>
                            </div>
                        @endif
                    </div>

                    <div id="report-panel-history-{{ $report->report_id }}" class="hidden">
                        <p class="mb-3 text-sm font-medium text-slate-600">Ticket timeline</p>
                        <p class="mb-4 text-xs leading-5 text-slate-500">
                            Status updates for equipment on this ticket, plus every other time those same assets were reported, fixed, or sent for replacement.
                        </p>
                        @include('components.tables.partials.equipment-report-history', [
                            'report' => $report,
                            'ticketLinkBase' => $ticketLinkBase,
                        ])
                    </div>
                </div>

                <div class="border-t border-slate-200 px-6 py-4">
                    <a
                        href="{{ $detailsUrl }}"
                        class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad]"
                    >
                        <i data-lucide="external-link" class="h-4 w-4"></i>
                        Open report details
                    </a>
                </div>
            </div>
        </div>
    </div>
@endforeach

@once
<script>
    if (typeof window.openReportModal !== 'function') {
        window.openReportModal = function (modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('hidden');
            if (window.lucide) {
                window.lucide.createIcons();
            }
            if (typeof window.initReportEquipmentTabCarousels === 'function') {
                window.requestAnimationFrame(function () {
                    window.initReportEquipmentTabCarousels();
                });
            }
        };
    }

    if (typeof window.closeReportModal !== 'function') {
        window.closeReportModal = function (modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('hidden');
        };
    }

    if (typeof window.switchReportViewTab !== 'function') {
        window.switchReportViewTab = function (reportId, tab) {
            const detailsPanel = document.getElementById(`report-panel-details-${reportId}`);
            const historyPanel = document.getElementById(`report-panel-history-${reportId}`);
            const detailsTab = document.getElementById(`report-tab-details-${reportId}`);
            const historyTab = document.getElementById(`report-tab-history-${reportId}`);

            if (!detailsPanel || !historyPanel || !detailsTab || !historyTab) {
                return;
            }

            const showHistory = tab === 'history';
            detailsPanel.classList.toggle('hidden', showHistory);
            historyPanel.classList.toggle('hidden', !showHistory);

            const historyCountBox = document.getElementById(`report-rail-history-count-${reportId}`);
            if (historyCountBox) {
                historyCountBox.classList.toggle('hidden', !showHistory);
            }

            const activeTab =
                'border-b-2 border-[#0037C7] px-1 pb-3 text-sm font-medium text-slate-900';
            const idleTab =
                'border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-slate-400 hover:text-slate-600';

            detailsTab.className = showHistory ? idleTab : activeTab;
            historyTab.className = showHistory ? activeTab : idleTab;
        };
    }

    if (typeof window.openReportHistory !== 'function') {
        window.openReportHistory = function (reportId) {
            window.openReportModal(`view-modal-${reportId}`);
            window.switchReportViewTab(reportId, 'history');
        };
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[id^="view-modal-"]:not(.hidden)').forEach(function (modal) {
            modal.classList.add('hidden');
        });
    });
</script>
@endonce
