@extends('layouts.admin-layout')

@section('title', 'Administrator Dashboard')

@section('content')
@php
    $peso = fn ($v) => '₱'.number_format((float) $v, 2);
    $showing = function (int $total, int $shown): string {
        if ($shown <= 0) {
            return '';
        }

        return $total > $shown
            ? $shown.' latest'
            : 'Showing '.$shown.' of '.$total;
    };

    $urgentTotal = (int) ($overview['urgent_reports'] ?? 0);
    $overdueSchedules = (int) ($overview['overdue_schedules'] ?? 0);
    $overdueBorrows = (int) ($overview['overdue_borrows'] ?? 0);
    $openBackOrders = (int) ($receivingSummary['open'] ?? 0);

    $urgentRows = collect($urgentReportsList ?? [])->take(5);
    $borrowRows = collect($overdueBorrowsPreview ?? [])->take(5);
    $scheduleRows = collect($upcomingMaintenanceSchedules ?? [])->take(5);
    $inspectionRows = collect($semesterInspectionDue ?? [])->take(5);
    $backOrderRows = collect($receivingSummary['rows'] ?? [])->take(5);
    $backOrderTotal = (int) ($receivingSummary['total'] ?? 0);
    $warrantyRows = collect($warrantyWatch['rows'] ?? [])->take(5);
    $warrantyTotal = (int) ($warrantyWatch['total'] ?? $warrantyRows->count());
    $disposalRows = collect($disposalWatch['rows'] ?? [])->take(5);
    $disposalTotal = (int) ($disposalWatch['total'] ?? $disposalRows->count());

    $selectedYear = (int) ($budgetProposalYear ?? now()->year);
    $usage = ($budgetUsage ?? [])[$selectedYear] ?? null;

    $daysTag = function (?\Carbon\Carbon $date) {
        if (! $date) {
            return null;
        }
        $days = (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);

        return $days < 0 ? abs($days).'d overdue' : ($days === 0 ? 'Due today' : 'In '.$days.'d');
    };
@endphp

<span class="admin-keep-colors hidden" aria-hidden="true"></span>
<div class="ad-dash mx-3 space-y-4 sm:mx-1 lg:-mx-1">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach([
            ['Urgent reports', $urgentTotal, route('admin.operations.reports', ['filter' => 'urgent'])],
            ['Overdue schedules', $overdueSchedules, route('admin.operations.schedules', ['filter' => 'overdue'])],
            ['Overdue borrows', $overdueBorrows, route('admin.borrowing.index', ['status' => 'Overdue'])],
            ['Open back orders', $openBackOrders, route('admin.back-orders.index')],
        ] as [$label, $value, $href])
            <a href="{{ $href }}" class="ad-panel px-5 py-4 transition hover:bg-slate-50">
                <span class="block text-xs font-medium text-slate-500">{{ $label }}</span>
                <span class="mt-1 block text-2xl font-semibold tabular-nums tracking-tight {{ $value > 0 ? 'text-slate-900' : 'text-slate-300' }}">{{ number_format($value) }}</span>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <section class="ad-panel p-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900">Urgent reports</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($urgentTotal) }}</span>
                </div>
                <a href="{{ route('admin.operations.reports', ['filter' => 'urgent']) }}" class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-800">View all</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse($urgentRows as $report)
                    <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50" data-dashboard-preview data-preview-url="{{ route('admin.dashboard.preview', ['type' => 'report', 'id' => $report->report_id]) }}" data-continue-url="{{ route('admin.operations.reports', ['filter' => 'urgent', 'q' => $report->equipment_name]) }}" data-continue-label="Open" data-preview-title="{{ $report->equipment_name ?: 'Urgent report' }}">
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-semibold text-gray-900">{{ $report->equipment_name ?: 'Unlisted equipment' }}</span>
                            @if($unique = \App\Support\EquipmentIdentity::label($report))
                                <span class="mt-0.5 block truncate font-mono text-[11px] text-slate-600">{{ $unique }}</span>
                            @endif
                            <span class="block truncate text-[11px] text-gray-500">{{ $report->room_name ?: 'No room' }} · {{ $report->report_current_status }}</span>
                        </span>
                    </button>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-gray-500">None</p>
                @endforelse
            </div>
            @if($urgentRows->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ $showing($urgentTotal, $urgentRows->count()) }}</p>
            @endif
        </section>

        <section class="ad-panel p-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900">Overdue borrows</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($overdueBorrows) }}</span>
                </div>
                <a href="{{ route('admin.borrowing.index', ['status' => 'Overdue']) }}" class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-800">View all</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse($borrowRows as $row)
                    <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50" data-dashboard-preview data-preview-url="{{ route('admin.dashboard.preview', ['type' => 'borrow', 'id' => $row->borrowing_record_id]) }}" data-continue-url="{{ route('admin.borrowing.index', ['status' => 'Overdue', 'search' => $row->equipment_name ?: $row->borrowing_borrower_name]) }}" data-continue-label="Open" data-preview-title="{{ $row->equipment_name ?: 'Borrow' }}">
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-semibold text-gray-900">{{ $row->equipment_name ?: 'Equipment' }}</span>
                            @if($unique = \App\Support\EquipmentIdentity::label($row))
                                <span class="mt-0.5 block truncate font-mono text-[11px] text-slate-600">{{ $unique }}</span>
                            @endif
                            <span class="block truncate text-[11px] text-gray-500">{{ $row->borrowing_borrower_name ?: '—' }}@if($row->borrowing_expected_return_date) · Due {{ \Carbon\Carbon::parse($row->borrowing_expected_return_date)->format('M j, Y') }}@endif</span>
                        </span>
                    </button>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-gray-500">None</p>
                @endforelse
            </div>
            @if($borrowRows->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ $showing($overdueBorrows, $borrowRows->count()) }}</p>
            @endif
        </section>

        <section class="ad-panel p-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900">Schedules</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($overdueSchedules) }}</span>
                </div>
                <a href="{{ route('admin.operations.schedules', ['filter' => 'overdue']) }}" class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-800">View all</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse($scheduleRows as $schedule)
                    @php
                        $nextDate = !empty($schedule->maintenance_schedule_next_date) ? \Carbon\Carbon::parse($schedule->maintenance_schedule_next_date) : null;
                        $when = $daysTag($nextDate) ?: ($schedule->maintenance_schedule_status ?: 'Scheduled');
                    @endphp
                    <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50" data-dashboard-preview data-preview-url="{{ route('admin.dashboard.preview', ['type' => 'schedule', 'id' => $schedule->maintenance_schedule_id]) }}" data-continue-url="{{ route('admin.operations.schedules', ['record' => $schedule->maintenance_schedule_id]) }}" data-continue-label="Open" data-preview-title="{{ $schedule->equipment_name ?: ($schedule->maintenance_schedule_title ?: 'Schedule') }}">
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-semibold text-gray-900">{{ $schedule->equipment_name ?: ($schedule->maintenance_schedule_title ?: 'Equipment') }}</span>
                            @if($unique = \App\Support\EquipmentIdentity::label($schedule))
                                <span class="mt-0.5 block truncate font-mono text-[11px] text-slate-600">{{ $unique }}</span>
                            @endif
                            <span class="block truncate text-[11px] text-gray-500">{{ $schedule->room_name ?: 'No room' }} · {{ $when }}</span>
                        </span>
                    </button>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-gray-500">None</p>
                @endforelse
            </div>
            @if($scheduleRows->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ $showing(max($overdueSchedules, $scheduleRows->count()), $scheduleRows->count()) }}</p>
            @endif
        </section>

        <section class="ad-panel p-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900">Back orders</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($openBackOrders) }}</span>
                </div>
                <a href="{{ route('admin.back-orders.index') }}" class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-800">View all</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse($backOrderRows as $rr)
                    <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50" data-dashboard-preview data-preview-url="{{ route('admin.operations.document', ['type' => 'rr', 'id' => $rr->id]) }}" data-continue-url="{{ route('admin.back-orders.index', ['rr' => $rr->id]) }}" data-continue-label="Open" data-preview-title="{{ $rr->number }}">
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-semibold text-gray-900">{{ $rr->number }}</span>
                            <span class="block truncate text-[11px] text-gray-500">{{ $rr->supplier ?: 'Supplier not set' }} · {{ $rr->open > 0 ? $rr->open.' open' : 'Delivered' }}</span>
                        </span>
                    </button>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-gray-500">None</p>
                @endforelse
            </div>
            @if($backOrderRows->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ $showing(max($backOrderTotal, $backOrderRows->count()), $backOrderRows->count()) }}</p>
            @endif
        </section>

        <section class="ad-panel p-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900">Warranties ending</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($warrantyTotal) }}</span>
                </div>
                <a href="{{ route('admin.operations.equipment') }}" class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-800">View all</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse($warrantyRows as $item)
                    <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50" data-dashboard-preview data-preview-url="{{ route('admin.dashboard.preview', ['type' => 'equipment', 'id' => $item->id]) }}" data-continue-url="{{ route('admin.operations.equipment.show', $item->id) }}" data-continue-label="Open" data-preview-title="{{ $item->name }}">
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-semibold text-gray-900">{{ $item->name }}</span>
                            @if($unique = \App\Support\EquipmentIdentity::label($item))
                                <span class="mt-0.5 block truncate font-mono text-[11px] text-slate-600">{{ $unique }}</span>
                            @endif
                            <span class="block truncate text-[11px] text-gray-500">Ends {{ $item->endsAt->format('M j, Y') }}</span>
                        </span>
                        <span class="shrink-0 text-[11px] font-semibold {{ $item->daysLeft <= 7 ? 'text-amber-700' : 'text-slate-500' }}">{{ $item->daysLeft === 0 ? 'Today' : $item->daysLeft.'d left' }}</span>
                    </button>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-gray-500">None</p>
                @endforelse
            </div>
            @if($warrantyRows->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ $showing($warrantyTotal, $warrantyRows->count()) }}</p>
            @endif
        </section>

        <section class="ad-panel p-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900">For disposal</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($disposalTotal) }}</span>
                </div>
                <a href="{{ route('admin.operations.equipment', ['filter' => 'replacement']) }}" class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-800">View all</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse($disposalRows as $item)
                    <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50" data-dashboard-preview data-preview-url="{{ route('admin.dashboard.preview', ['type' => 'equipment', 'id' => $item->id]) }}" data-continue-url="{{ route('admin.operations.equipment.show', $item->id) }}" data-continue-label="Open" data-preview-title="{{ $item->name }}">
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-semibold text-gray-900">{{ $item->name }}</span>
                            @if($unique = \App\Support\EquipmentIdentity::label($item))
                                <span class="mt-0.5 block truncate font-mono text-[11px] text-slate-600">{{ $unique }}</span>
                            @endif
                            <span class="block truncate text-[11px] text-gray-500">{{ $item->meta ?: 'For replacement' }}</span>
                        </span>
                    </button>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-gray-500">None</p>
                @endforelse
            </div>
            @if($disposalRows->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ $showing($disposalTotal, $disposalRows->count()) }}</p>
            @endif
        </section>

        @if($inspectionRows->isNotEmpty())
            <section class="ad-panel p-5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <h2 class="text-sm font-bold text-gray-900">Semester inspections</h2>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($inspectionRows->count()) }}</span>
                    </div>
                    <a href="{{ route('admin.operations.schedules') }}" class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-800">View all</a>
                </div>
                <div class="mt-4 divide-y divide-slate-100">
                    @foreach($inspectionRows as $campaign)
                        @php $when = $daysTag(\Carbon\Carbon::parse($campaign->campaign_due_date)); @endphp
                        <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50" data-dashboard-preview data-preview-url="{{ route('admin.dashboard.preview', ['type' => 'inspection', 'id' => $campaign->campaign_id]) }}" data-continue-url="{{ route('admin.semester-inspections.show', $campaign->campaign_id) }}" data-continue-label="Open" data-preview-title="{{ $campaign->campaign_title }}">
                            <span class="min-w-0">
                                <span class="block truncate text-xs font-semibold text-gray-900">{{ $campaign->campaign_title }}</span>
                                <span class="block truncate text-[11px] text-gray-500">{{ $campaign->campaign_semester }}@if(!empty($campaign->campaign_academic_year)) · {{ $campaign->campaign_academic_year }}@endif @if($when) · {{ $when }}@endif</span>
                            </span>
                        </button>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-slate-500">{{ $showing($inspectionRows->count(), $inspectionRows->count()) }}</p>
            </section>
        @endif

        <section class="ad-panel p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-bold text-gray-900">Budget</h2>
                <form method="GET" action="{{ route('admin.dashboard') }}">
                    <label for="budget_year" class="sr-only">Budget year</label>
                    <select id="budget_year" name="budget_year" onchange="this.form.submit()"
                            class="rounded-md border border-slate-200 bg-white py-1 pl-2.5 pr-7 text-xs font-medium text-slate-700 focus:border-slate-300 focus:outline-none focus:ring-0">
                        @foreach(($budgetProposalYears ?? collect([(int) now()->year])) as $yearOption)
                            <option value="{{ $yearOption }}" @selected($selectedYear === (int) $yearOption)>{{ $yearOption }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <dl class="mt-4 divide-y divide-slate-100">
                <div class="flex items-center justify-between gap-3 py-3">
                    <dt class="text-xs text-slate-500">Proposed</dt>
                    <dd class="text-sm font-semibold tabular-nums text-slate-900">{{ $peso($budgetProposalTotal ?? 0) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 py-3">
                    <dt class="text-xs text-slate-500">Approved</dt>
                    <dd class="text-sm font-semibold tabular-nums text-slate-900">{{ $peso($usage['approved'] ?? 0) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 py-3">
                    <dt class="text-xs text-slate-500">Paid</dt>
                    <dd class="text-sm font-semibold tabular-nums text-slate-900">{{ $peso($usage['released'] ?? 0) }}</dd>
                </div>
            </dl>
        </section>
    </div>
</div>

<style>
.ad-panel { border-radius: 1rem; border: 1px solid #e2e8f0; background: #fff; }
</style>

<div id="adminDashboardPreview" class="fixed inset-0 z-[12000] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="adminDashboardPreviewTitle">
    <div class="my-4 flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" data-preview-card>
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
            <h2 id="adminDashboardPreviewTitle" class="truncate text-sm font-semibold text-slate-950">Form</h2>
            <button type="button" data-preview-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
        <div id="adminDashboardPreviewStage" class="relative overflow-hidden bg-[#dbeafe]" style="height: 280px">
            <iframe id="adminDashboardPreviewFrame" title="Form preview" class="block w-full border-0 bg-[#dbeafe]" src="about:blank"></iframe>
            <div id="adminDashboardPreviewSkeleton" class="absolute inset-0 z-10 hidden overflow-auto bg-[#dbeafe] py-6">
                @include('partials.skeleton', ['skeletonType' => 'document', 'skeletonLabel' => 'Loading form'])
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-4 py-3">
            <button type="button" data-preview-close class="text-sm font-medium text-slate-500 hover:text-slate-900">Close</button>
            <a id="adminDashboardPreviewContinue" href="#" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">Open</a>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('adminDashboardPreview');
        const stage = document.getElementById('adminDashboardPreviewStage');
        const frame = document.getElementById('adminDashboardPreviewFrame');
        const skeleton = document.getElementById('adminDashboardPreviewSkeleton');
        const title = document.getElementById('adminDashboardPreviewTitle');
        const continueLink = document.getElementById('adminDashboardPreviewContinue');
        if (!modal || !stage || !frame || !skeleton || !title || !continueLink) return;
        document.body.appendChild(modal);

        function showSkeleton() {
            skeleton.classList.remove('hidden');
            stage.style.height = '280px';
        }

        function hideSkeleton() {
            skeleton.classList.add('hidden');
        }

        function fitFrame() {
            const doc = frame.contentDocument;
            if (!doc || !doc.body || !doc.head) return;
            let fit = doc.getElementById('admin-preview-fit');
            if (!fit) {
                fit = doc.createElement('style');
                fit.id = 'admin-preview-fit';
                fit.textContent = 'html,body{min-height:0!important;height:auto!important;background:#dbeafe!important;padding:16px 12px!important;overflow:hidden!important;}';
                doc.head.appendChild(fit);
            }
            const height = Math.ceil(Math.max(doc.documentElement.scrollHeight, doc.body.scrollHeight));
            const max = Math.round(window.innerHeight * 0.72);
            frame.style.height = height + 'px';
            stage.style.height = Math.min(height, max) + 'px';
            stage.style.overflowY = height > max ? 'auto' : 'hidden';
        }

        frame.addEventListener('load', function () {
            const src = frame.getAttribute('src') || '';
            if (!src || src === 'about:blank') return;
            fitFrame();
            hideSkeleton();
            window.setTimeout(fitFrame, 60);
        });

        function closePreview() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            hideSkeleton();
            frame.style.height = '';
            stage.style.height = '280px';
            stage.style.overflowY = 'hidden';
            frame.src = 'about:blank';
            document.body.style.overflow = '';
        }

        function openPreview(trigger) {
            const previewUrl = trigger.getAttribute('data-preview-url') || '';
            const continueUrl = trigger.getAttribute('data-continue-url') || '';
            if (!previewUrl) {
                if (continueUrl) window.location = continueUrl;
                return;
            }
            title.textContent = trigger.getAttribute('data-preview-title') || 'Form';
            continueLink.href = continueUrl || '#';
            continueLink.textContent = trigger.getAttribute('data-continue-label') || 'Open';
            showSkeleton();
            const joiner = previewUrl.indexOf('?') === -1 ? '?' : '&';
            frame.src = previewUrl + joiner + 'ts=' + Date.now();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-dashboard-preview]');
            if (!trigger) return;
            const inner = event.target.closest('a, button');
            if (inner && inner !== trigger) return;
            openPreview(trigger);
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal || event.target.closest('[data-preview-close]')) closePreview();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closePreview();
        });
    })();
</script>
@endsection
