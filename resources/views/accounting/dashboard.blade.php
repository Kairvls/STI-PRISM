@extends('layouts.accounting-layout')

@section('title', 'Accounting Dashboard')

@section('content')
@include('accounting.partials.flash')

@php
    $chartMonths = $fundsReleasedChart['months'] ?? [];
    $chartYearTotal = (float) ($fundsReleasedChart['total'] ?? 0);
    $chartYearReleases = (int) ($fundsReleasedChart['releases'] ?? 0);
@endphp

<div class="acc-page acc-dash fade-in">
    <p class="text-sm text-gray-500">Overview of Accounting workload and financial status.</p>

    {{-- Metric cards --}}
    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <a href="/accounting/authority-to-purchase?status=incoming" class="pm-stat-card relative slide-up" style="animation-delay:.04s">
            <div class="pm-stat-icon bg-blue-50 text-blue-600">
                <i data-lucide="file-check"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="pm-stat-label">Pending ATP</p>
                <p class="pm-stat-value is-blue"><span>{{ $metrics['atp_pending'] }}</span> awaiting review</p>
            </div>
        </a>
        <a href="/accounting/request-check?status=incoming" class="pm-stat-card relative slide-up" style="animation-delay:.08s">
            <div class="pm-stat-icon bg-blue-50 text-blue-600">
                <i data-lucide="clipboard-list"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="pm-stat-label">Pending checks</p>
                <p class="pm-stat-value is-blue"><span>{{ $metrics['rfc_pending'] }}</span> pending review</p>
            </div>
        </a>
        <a href="/accounting/request-check?status=funds" class="pm-stat-card relative slide-up" style="animation-delay:.12s">
            <div class="pm-stat-icon bg-blue-50 text-blue-600">
                <i data-lucide="banknote"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="pm-stat-label">Funds to release</p>
                <p class="pm-stat-value is-blue"><span>{{ $metrics['funds_awaiting'] }}</span> ready</p>
            </div>
        </a>
        <a href="/accounting/liquidation-reports?status=incoming" class="pm-stat-card relative slide-up" style="animation-delay:.16s">
            <div class="pm-stat-icon bg-slate-100 text-slate-600">
                <i data-lucide="receipt"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="pm-stat-label">Pending liquid.</p>
                <p class="pm-stat-value"><span>{{ $metrics['liq_pending'] }}</span> pending review</p>
            </div>
        </a>
    </div>

    {{-- Deadlines (prominent) --}}
    @php
        $deadlineCards = [
            [
                'label' => 'Overdue',
                'value' => (int) ($deadlines['overdue'] ?? 0),
                'hint' => 'Past submission deadline',
                'href' => '/accounting/liquidation-reports?status=incoming&deadline=overdue',
                'tone' => 'rose',
                'icon' => 'alert-triangle',
            ],
            [
                'label' => 'Due today',
                'value' => (int) ($deadlines['due_today'] ?? 0),
                'hint' => 'Must be submitted today',
                'href' => '/accounting/liquidation-reports?status=incoming&deadline=due_today',
                'tone' => 'amber',
                'icon' => 'clock',
            ],
            [
                'label' => 'This week',
                'value' => (int) ($deadlines['this_week'] ?? 0),
                'hint' => 'Due within 7 days',
                'href' => '/accounting/liquidation-reports?status=incoming&deadline=this_week',
                'tone' => 'blue',
                'icon' => 'calendar',
            ],
        ];
    @endphp
    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach ($deadlineCards as $i => $card)
            <a href="{{ $card['href'] }}" class="acc-deadline-card slide-up is-{{ $card['tone'] }}" style="animation-delay:{{ 0.18 + ($i * 0.03) }}s">
                <div class="acc-deadline-icon">
                    <i data-lucide="{{ $card['icon'] }}"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <p class="acc-deadline-label">{{ $card['label'] }}</p>
                        <p class="acc-deadline-value">{{ $card['value'] }}</p>
                    </div>
                    <p class="acc-deadline-hint">{{ $card['hint'] }}</p>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Recent incoming (primary actionable feed) --}}
    <section class="pm-card acc-recent-incoming mt-3 slide-up overflow-hidden" style="animation-delay:.2s">
        <div class="acc-dash-section-head">
            <div>
                <h2 class="acc-dash-title">Recent incoming documents</h2>
                <p class="acc-dash-sub">Newest ATP, Request Checks, and Liquidations waiting for Accounting</p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-blue-500"></span>
                Live
            </span>
        </div>
        <div class="acc-table-wrap acc-dash-flush">
            <table class="acc-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Reference</th>
                        <th class="!text-right">Amount</th>
                        <th>Arrived</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="recentIncomingDocsBody" class="acc-animate">
                    @include('accounting._recent-incoming-docs-rows', ['recentIncomingDocs' => $recentIncomingDocs])
                </tbody>
            </table>
        </div>
    </section>

    {{-- Financial summary --}}
    <div class="acc-table-wrap mt-3 slide-up" style="animation-delay:.22s">
        <table class="acc-table acc-dash-summary">
            <thead>
                <tr>
                    <th colspan="3" class="!normal-case !tracking-normal !text-[13px] !font-bold !text-slate-900 !py-3">Financial summary</th>
                </tr>
                <tr>
                    <th class="text-center">Received</th>
                    <th class="text-center">Released</th>
                    <th class="text-center">Liquidated</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="acc-money text-center whitespace-nowrap text-blue-700">₱{{ number_format((float) $financialSummary['received'], 2) }}</td>
                    <td class="acc-money text-center whitespace-nowrap">₱{{ number_format((float) $financialSummary['released'], 2) }}</td>
                    <td class="acc-money text-center whitespace-nowrap">₱{{ number_format((float) $financialSummary['liquidated'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Chart (maintenance-style analytics card) --}}
    <section class="dashboard-analytics-card mt-3 slide-up" style="animation-delay:.22s">
        <div class="dashboard-analytics-header">
            <div class="min-w-0">
                <h2 class="dashboard-analytics-title">Funds released trend</h2>
                <p class="dashboard-analytics-subtitle">Monthly totals for {{ $chartYear }} · hover a month for details</p>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-3">
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500">
                    <span class="sr-only">Year</span>
                    <select
                        id="fundsChartYear"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-900 outline-none transition focus:border-blue-400"
                        aria-label="Select chart year"
                    >
                        @foreach ($chartYears as $y)
                            <option value="{{ $y }}" @selected((int) $y === (int) $chartYear)>{{ $y }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="activity-chart-total is-blue" id="fundsChartTotal">
                    ₱{{ number_format($chartYearTotal, 2) }}
                    <span>released</span>
                </div>
            </div>
        </div>

        <div class="dashboard-report-activity-chart acc-dash-chart">
            <canvas id="fundsReleasedChart"></canvas>
        </div>
    </section>

    {{-- Pending queue (full width — hugs content, no empty stretch) --}}
    <section class="pm-card acc-pending-card mt-3 slide-up overflow-hidden" style="animation-delay:.26s">
        <div class="acc-dash-section-head">
            <div>
                <h2 class="acc-dash-title">Pending document requests</h2>
                <p class="acc-dash-sub">ATP, Request Check, funds, and liquidation work</p>
            </div>
        </div>
        <div id="queueItems" class="acc-dash-queue">
            @include('accounting._queue-table', ['queue' => $queue])
        </div>
        <div id="queuePagination">
            @if ($queue->hasPages())
                <div class="acc-pagination acc-pagination--flush border-t border-slate-100">{{ $queue->links('pagination.president') }}</div>
            @endif
        </div>
    </section>

    {{-- Document status + Activity --}}
    <div class="mt-3 grid grid-cols-1 gap-3 xl:grid-cols-2 xl:items-stretch">
        <section class="pm-card acc-doc-status-card slide-up overflow-hidden" style="animation-delay:.3s">
            <div class="acc-dash-section-head">
                <div>
                    <h2 class="acc-dash-title">Document status</h2>
                    <p class="acc-dash-sub">Workload vs completed · click a row to open</p>
                </div>
            </div>
            @php
                $docStatusRows = [
                    [
                        'label' => 'ATP',
                        'pending' => (int) ($metrics['atp_pending'] ?? 0),
                        'revision' => (int) ($metrics['atp_revision'] ?? 0),
                        'approved' => (int) ($metrics['atp_approved'] ?? 0),
                        'extra' => null,
                        'href' => '/accounting/authority-to-purchase?status=incoming',
                    ],
                    [
                        'label' => 'Request Checks',
                        'pending' => (int) ($metrics['rfc_pending'] ?? 0),
                        'revision' => (int) ($metrics['rfc_revision'] ?? 0),
                        'approved' => (int) ($metrics['rfc_approved'] ?? 0),
                        'extra' => null,
                        'href' => '/accounting/request-check?status=incoming',
                    ],
                    [
                        'label' => 'Funds to release',
                        'pending' => (int) ($metrics['funds_awaiting'] ?? 0),
                        'revision' => 0,
                        'approved' => (int) ($metrics['funds_released'] ?? 0),
                        'extra' => 'Awaiting release',
                        'href' => '/accounting/request-check?status=funds',
                        'hide_revision' => true,
                    ],
                    [
                        'label' => 'Liquidations',
                        'pending' => (int) ($metrics['liq_pending'] ?? 0),
                        'revision' => (int) ($metrics['liq_revision'] ?? 0),
                        'approved' => (int) ($metrics['liq_approved'] ?? 0),
                        'extra' => null,
                        'href' => '/accounting/liquidation-reports?status=incoming',
                    ],
                ];
            @endphp
            <div class="acc-table-wrap acc-dash-flush acc-doc-status-wrap">
                <table class="acc-table acc-doc-status">
                    <colgroup>
                        <col style="width:34%">
                        <col style="width:14%">
                        <col style="width:14%">
                        <col style="width:14%">
                        <col style="width:24%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="acc-doc-col-label">Document</th>
                            <th class="acc-doc-col-num">Review</th>
                            <th class="acc-doc-col-num">Revise</th>
                            <th class="acc-doc-col-num">Done</th>
                            <th class="acc-doc-col-label">Completion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($docStatusRows as $row)
                            @php
                                $tracked = $row['pending'] + $row['revision'] + $row['approved'];
                                $pct = $tracked > 0 ? (int) round(($row['approved'] / $tracked) * 100) : 0;
                            @endphp
                            <tr class="acc-doc-status-row" onclick="window.location='{{ $row['href'] }}'">
                                <td class="acc-doc-col-label">
                                    <span class="font-semibold text-slate-800">{{ $row['label'] }}</span>
                                    @if (!empty($row['extra']))
                                        <span class="mt-0.5 block text-[10px] font-medium text-slate-400">
                                            {{ $row['extra'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="acc-doc-col-num font-semibold text-slate-800">{{ $row['pending'] }}</td>
                                <td class="acc-doc-col-num font-semibold {{ !empty($row['hide_revision']) ? 'text-slate-300' : ($row['revision'] > 0 ? 'text-amber-600' : 'text-slate-500') }}">
                                    {{ !empty($row['hide_revision']) ? '—' : $row['revision'] }}
                                </td>
                                <td class="acc-doc-col-num font-semibold text-blue-700">{{ $row['approved'] }}</td>
                                <td class="acc-doc-col-label">
                                    <div class="acc-doc-progress">
                                        <div class="acc-doc-progress-track">
                                            <span class="acc-doc-progress-bar" style="width: {{ $pct }}%"></span>
                                        </div>
                                        <span class="acc-doc-progress-pct">{{ $pct }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="pm-card slide-up overflow-hidden flex flex-col" style="animation-delay:.34s">
            <div class="acc-dash-section-head">
                <div>
                    <h2 class="acc-dash-title">Recent activity</h2>
                    <p class="acc-dash-sub">Accounting decisions</p>
                </div>
                <a href="/accounting/history" class="text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
            </div>
            <div class="acc-dash-activity px-4 pb-2" id="activityItems">
                @include('accounting._activity-items', ['recentActivity' => $recentActivity])
            </div>
            <div id="activityPagination">
                @if ($recentActivity->hasPages())
                    <div class="acc-pagination acc-pagination--flush border-t border-slate-100">{{ $recentActivity->links('pagination.president') }}</div>
                @endif
            </div>
        </aside>
    </div>
</div>

<script>
    (function () {
        function livePagination(containerId, apply) {
            const container = document.getElementById(containerId);
            if (!container) return;
            container.addEventListener('click', function (e) {
                const link = e.target.closest('a[href]');
                if (!link || !link.getAttribute('href') || link.getAttribute('href') === '#') return;
                e.preventDefault();
                const url = new URL(link.href, window.location.origin);
                fetch(url.pathname + url.search, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => apply(data, url))
                .catch(err => console.error(err));
            });
        }

        livePagination('queuePagination', function (data, url) {
            const items = document.getElementById('queueItems');
            const pag = document.getElementById('queuePagination');
            if (items && data.queue_html !== undefined) items.innerHTML = data.queue_html;
            if (pag && data.queue_pagination_html !== undefined) {
                pag.innerHTML = data.queue_pagination_html
                    ? '<div class="acc-pagination acc-pagination--flush border-t border-slate-100">' + data.queue_pagination_html + '</div>'
                    : '';
            }
            if (window.lucide) lucide.createIcons();
            window.history.replaceState({}, '', url.pathname + url.search);
        });

        livePagination('activityPagination', function (data, url) {
            const items = document.getElementById('activityItems');
            const pag = document.getElementById('activityPagination');
            if (items && data.activity_html !== undefined) items.innerHTML = data.activity_html;
            if (pag && data.activity_pagination_html !== undefined) {
                pag.innerHTML = data.activity_pagination_html
                    ? '<div class="acc-pagination acc-pagination--flush border-t border-slate-100">' + data.activity_pagination_html + '</div>'
                    : '';
            }
            if (window.lucide) lucide.createIcons();
            window.history.replaceState({}, '', url.pathname + url.search);
        });
    })();
</script>

<script>
    (function () {
        const tbody = document.getElementById('recentIncomingDocsBody');
        if (!tbody) return;

        function formatAgo(diffSec) {
            if (diffSec < 60) return 'now';
            const mins = Math.floor(diffSec / 60);
            if (mins === 1) return 'one minute ago';
            if (mins < 60) return mins + ' minutes ago';

            const hrs = Math.floor(mins / 60);
            if (hrs === 1) return 'one hour ago';
            if (hrs < 24) return hrs + ' hours ago';

            const days = Math.floor(hrs / 24);
            if (days === 1) return 'one day ago';
            return days + ' days ago';
        }

        function updateRelativeTimes() {
            const nowMs = Date.now();
            const spans = document.querySelectorAll('.acc-relative-time[data-arrived-at-ms]');
            spans.forEach(span => {
                const tsMs = Number(span.getAttribute('data-arrived-at-ms'));
                if (!tsMs) {
                    span.textContent = '—';
                    return;
                }
                const diffSec = Math.max(0, Math.floor((nowMs - tsMs) / 1000));
                span.textContent = formatAgo(diffSec);
            });
        }

        updateRelativeTimes();
        setInterval(updateRelativeTimes, 10000); // keeps "now -> one minute ago" live

        let fetching = false;
        async function fetchRecentIncomingDocs() {
            if (fetching) return;
            fetching = true;
            tbody.classList.add('is-loading');
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('partial', 'recent_incoming_docs');
                url.searchParams.set('t', String(Date.now())); // avoid caching
                const res = await fetch(url.pathname + url.search, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data && typeof data.recent_incoming_docs_html === 'string') {
                    tbody.innerHTML = data.recent_incoming_docs_html;
                    tbody.classList.remove('acc-animate');
                    void tbody.offsetWidth;
                    tbody.classList.add('acc-animate');
                    updateRelativeTimes();
                    if (window.lucide) lucide.createIcons();
                }
            } catch (err) {
                console.error(err);
            } finally {
                tbody.classList.remove('is-loading');
                fetching = false;
            }
        }

        // Poll periodically to keep "top 5 recent" truly live as new documents arrive.
        setInterval(fetchRecentIncomingDocs, 15000);
    })();
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
    @keyframes chartFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .acc-dash .acc-dash-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
    }
    .acc-dash .acc-dash-title {
        margin: 0;
        color: #0f172a;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.25;
    }
    .acc-dash .acc-dash-sub {
        margin: 2px 0 0;
        color: #94a3b8;
        font-size: 11px;
        line-height: 1.3;
    }
    .acc-dash .acc-dash-flush {
        border: 0;
        border-radius: 0;
        box-shadow: none;
        background: transparent;
    }
    .acc-dash .acc-dash-summary th,
    .acc-dash .acc-dash-summary td {
        text-align: center;
    }
    .acc-dash .acc-dash-summary .acc-money {
        text-align: center;
        font-size: 0.95rem;
    }
    /* Only Pending document requests hugs its rows (no stretched empty height). */
    .acc-dash .acc-dash-queue,
    .acc-dash .acc-dash-queue .acc-table-wrap {
        min-height: 0 !important;
        height: auto !important;
    }
    .acc-dash .acc-pending-card {
        height: auto !important;
        align-self: stretch;
        display: block;
        padding-bottom: 0;
    }
    .acc-dash .acc-pending-card #queuePagination:empty {
        display: none;
    }
    .acc-dash .acc-recent-incoming {
        border-color: #bfdbfe;
        box-shadow: 0 1px 3px rgba(37, 99, 235, 0.08);
    }
    .acc-dash .acc-dash-activity .acc-activity-item {
        padding: 0.55rem 0;
    }
    .acc-dash .acc-dash-chart {
        height: 280px;
    }

    /* Maintenance-style analytics card */
    .dashboard-analytics-card {
        min-width: 0;
        overflow: hidden;
        padding: 22px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .dashboard-analytics-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .dashboard-analytics-title {
        margin: 0;
        color: #0f172a;
        font-size: 16px;
        font-weight: 700;
    }
    .dashboard-analytics-subtitle {
        margin: 3px 0 0;
        color: #94a3b8;
        font-size: 10px;
    }
    .dashboard-report-activity-chart {
        position: relative;
        width: 100%;
        height: 280px;
        animation: chartFadeIn 0.8s ease-out forwards;
    }
    .activity-chart-total {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        white-space: nowrap;
    }
    .activity-chart-total.is-blue { color: #1d4ed8; }
    .activity-chart-total span {
        margin-left: 2px;
        font-size: 9px;
        font-weight: 500;
        color: #94a3b8;
    }

    /* Deadlines cards */
    .acc-deadline-card {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
        padding: 14px 16px;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        background: #fff;
        text-decoration: none;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    .acc-deadline-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    }
    .acc-deadline-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 40px;
    }
    .acc-deadline-icon svg { width: 18px; height: 18px; }
    .acc-deadline-label {
        margin: 0;
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .acc-deadline-value {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        line-height: 1;
        color: #0f172a;
    }
    .acc-deadline-hint {
        margin: 4px 0 0;
        font-size: 11px;
        color: #94a3b8;
    }
    .acc-deadline-card.is-rose {
        border-color: #fecdd3;
        background: linear-gradient(180deg, #fff1f2 0%, #ffffff 70%);
    }
    .acc-deadline-card.is-rose .acc-deadline-icon { background: #ffe4e6; color: #e11d48; }
    .acc-deadline-card.is-rose .acc-deadline-value { color: #be123c; }
    .acc-deadline-card.is-amber {
        border-color: #fde68a;
        background: linear-gradient(180deg, #fffbeb 0%, #ffffff 70%);
    }
    .acc-deadline-card.is-amber .acc-deadline-icon { background: #fef3c7; color: #d97706; }
    .acc-deadline-card.is-amber .acc-deadline-value { color: #b45309; }
    .acc-deadline-card.is-blue {
        border-color: #bfdbfe;
        background: linear-gradient(180deg, #eff6ff 0%, #ffffff 70%);
    }
    .acc-deadline-card.is-blue .acc-deadline-icon { background: #dbeafe; color: #2563eb; }
    .acc-deadline-card.is-blue .acc-deadline-value { color: #1d4ed8; }

    /* Document status table — fill card height */
    .acc-doc-status-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
    }
    .acc-doc-status-wrap {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .acc-doc-status {
        table-layout: fixed;
        width: 100%;
        height: 100%;
        flex: 1 1 auto;
    }
    .acc-doc-status tbody {
        height: 100%;
    }
    .acc-doc-status tbody tr {
        height: 25%;
    }
    .acc-doc-status th,
    .acc-doc-status td {
        vertical-align: middle;
    }
    .acc-doc-status .acc-doc-col-label {
        text-align: left !important;
    }
    .acc-doc-status .acc-doc-col-num {
        text-align: right !important;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .acc-doc-status .acc-money { text-align: right; }
    .acc-doc-status-row {
        cursor: pointer;
    }
    .acc-doc-status-row:hover td {
        background: #f8fafc;
    }
    .acc-doc-progress {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }
    .acc-doc-progress-track {
        flex: 1 1 auto;
        height: 6px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
        min-width: 0;
    }
    .acc-doc-progress-bar {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: #2563eb;
    }
    .acc-doc-progress-pct {
        flex: 0 0 auto;
        width: 2.25rem;
        text-align: right;
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        font-variant-numeric: tabular-nums;
    }

    .pm-analytics-card {
        min-width: 0;
        overflow: hidden;
        padding: 22px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .pm-analytics-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .pm-analytics-title {
        margin: 0;
        color: #0f172a;
        font-size: 16px;
        font-weight: 700;
    }
    .pm-analytics-subtitle {
        margin: 3px 0 0;
        color: #94a3b8;
        font-size: 10px;
    }
    .pm-chart-total {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        white-space: nowrap;
    }
    .pm-chart-total.is-blue { color: #1d4ed8; }
    .pm-chart-total span {
        margin-left: 2px;
        font-size: 9px;
        font-weight: 500;
        color: #94a3b8;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('fundsReleasedChart');
        const yearSelect = document.getElementById('fundsChartYear');
        const totalEl = document.getElementById('fundsChartTotal');
        const subtitleEl = document.querySelector('.dashboard-analytics-card .dashboard-analytics-subtitle');
        if (!canvas || typeof Chart === 'undefined') return;

        let chartLabels = @json(array_column($chartMonths, 'month_label'));
        let chartReleased = @json(array_column($chartMonths, 'released'));
        let chartCounts = @json(array_column($chartMonths, 'count'));
        let chartAverages = @json(array_column($chartMonths, 'average'));
        let fundsChart = null;

        function peso(value) {
            const n = Number(value || 0);
            return '₱' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function movingAverage(values, windowSize) {
            const size = Math.max(2, windowSize || 3);
            return values.map(function (_, index) {
                const start = Math.max(0, index - size + 1);
                const slice = values.slice(start, index + 1);
                const sum = slice.reduce(function (acc, n) { return acc + Number(n || 0); }, 0);
                return sum / slice.length;
            });
        }

        function updateHeader(year, total) {
            if (totalEl) {
                totalEl.innerHTML = peso(total) + ' <span>released</span>';
            }
            if (subtitleEl) {
                subtitleEl.textContent = 'Monthly totals for ' + year + ' · hover a month for details';
            }
        }

        const softBlueShadowPlugin = {
            id: 'accountingFundsSoftBlueShadow',
            beforeDatasetsDraw(chart) {
                const meta = chart.getDatasetMeta(0);
                if (!meta || meta.hidden || !meta.data.length || !meta.dataset) return;

                const ctx = chart.ctx;
                const chartArea = chart.chartArea;
                const points = meta.data;
                const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, 'rgba(114, 180, 220, 0.45)');
                gradient.addColorStop(0.35, 'rgba(114, 180, 220, 0.22)');
                gradient.addColorStop(0.7, 'rgba(114, 180, 220, 0.08)');
                gradient.addColorStop(1, 'rgba(114, 180, 220, 0)');

                ctx.save();
                ctx.beginPath();
                ctx.rect(chartArea.left, chartArea.top, chartArea.right - chartArea.left, chartArea.bottom - chartArea.top);
                ctx.clip();
                ctx.beginPath();
                meta.dataset.path(ctx);

                const lastPoint = points[points.length - 1];
                const firstPoint = points[0];
                const shadowDepth = 75;
                ctx.lineTo(lastPoint.x, Math.min(lastPoint.y + shadowDepth, chartArea.bottom));
                ctx.lineTo(firstPoint.x, Math.min(firstPoint.y + shadowDepth, chartArea.bottom));
                ctx.closePath();
                ctx.fillStyle = gradient;
                ctx.fill();
                ctx.restore();
            }
        };

        const hoverLinePlugin = {
            id: 'accountingFundsHoverLine',
            afterDatasetsDraw(chart) {
                const activeElements = chart.tooltip?.getActiveElements();
                if (!activeElements?.length) return;

                const activeElement = activeElements[0].element;
                const activeIndex = activeElements[0].index;
                const x = activeElement.x;
                const ctx = chart.ctx;
                const chartArea = chart.chartArea;

                ctx.save();
                ctx.beginPath();
                ctx.setLineDash([3, 3]);
                ctx.moveTo(x, chartArea.top);
                ctx.lineTo(x, chartArea.bottom);
                ctx.lineWidth = 1;
                ctx.strokeStyle = '#d7dce5';
                ctx.stroke();
                ctx.restore();

                const xScale = chart.scales.x;
                const labelX = xScale.getPixelForTick(activeIndex);
                const labelY = xScale.bottom + 17;
                const activeLabel = String(chartLabels[activeIndex] || '');

                ctx.save();
                ctx.font = '600 10px Inter, sans-serif';
                const textWidth = ctx.measureText(activeLabel).width;
                const boxWidth = textWidth + 14;
                const boxHeight = 22;
                ctx.fillStyle = '#f1f1f3';
                ctx.beginPath();
                if (typeof ctx.roundRect === 'function') {
                    ctx.roundRect(labelX - boxWidth / 2, labelY - boxHeight / 2, boxWidth, boxHeight, 6);
                } else {
                    ctx.rect(labelX - boxWidth / 2, labelY - boxHeight / 2, boxWidth, boxHeight);
                }
                ctx.fill();
                ctx.fillStyle = '#475569';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(activeLabel, labelX, labelY);
                ctx.restore();
            }
        };

        function buildChart() {
            if (fundsChart) {
                fundsChart.destroy();
            }

            const trendData = movingAverage(chartReleased, 3);

            fundsChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Funds released',
                            data: chartReleased,
                            borderColor: '#72b4dc',
                            backgroundColor: 'transparent',
                            borderWidth: 1.5,
                            fill: false,
                            tension: 0.42,
                            cubicInterpolationMode: 'monotone',
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            pointHitRadius: 25,
                            pointHoverBackgroundColor: '#72b4dc',
                            pointHoverBorderColor: 'white',
                            pointHoverBorderWidth: 2,
                        },
                        {
                            label: 'Trend',
                            data: trendData,
                            borderColor: '#e9b26f',
                            backgroundColor: 'transparent',
                            borderWidth: 1.5,
                            fill: false,
                            tension: 0.42,
                            cubicInterpolationMode: 'monotone',
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            pointHitRadius: 25,
                            pointHoverBackgroundColor: '#e9b26f',
                            pointHoverBorderColor: 'white',
                            pointHoverBorderWidth: 2,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    normalized: true,
                    interaction: { mode: 'index', intersect: false },
                    layout: { padding: { top: 10, right: 8, bottom: 18, left: 0 } },
                    animation: { duration: 350 },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            position: 'nearest',
                            backgroundColor: '#0f172a',
                            titleColor: 'white',
                            bodyColor: '#94a3b8',
                            borderWidth: 0,
                            padding: { top: 10, right: 12, bottom: 10, left: 12 },
                            cornerRadius: 7,
                            caretSize: 0,
                            displayColors: true,
                            usePointStyle: false,
                            boxWidth: 2,
                            boxHeight: 14,
                            boxPadding: 7,
                            titleSpacing: 4,
                            bodySpacing: 7,
                            titleMarginBottom: 7,
                            titleFont: { family: 'Inter', size: 11, weight: '600' },
                            bodyFont: { family: 'Inter', size: 10, weight: '400' },
                            callbacks: {
                                title(context) {
                                    return context[0].label;
                                },
                                label(context) {
                                    if (context.datasetIndex === 1) {
                                        return 'Trend     ' + peso(context.raw);
                                    }
                                    const i = context.dataIndex;
                                    const released = Number(chartReleased[i] || 0);
                                    const count = Number(chartCounts[i] || 0);
                                    const average = Number(chartAverages[i] || 0);
                                    return [
                                        'Total released     ' + peso(released),
                                        'Releases     ' + count,
                                        'Avg / release     ' + peso(average),
                                    ];
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            offset: false,
                            border: { display: false },
                            grid: { display: false },
                            ticks: {
                                autoSkip: false,
                                color: '#8c929c',
                                padding: 14,
                                maxRotation: 0,
                                minRotation: 0,
                                font: { family: 'Inter', size: 10, weight: '400' },
                            },
                        },
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: {
                                color: '#eef1f5',
                                drawTicks: false,
                            },
                            ticks: {
                                padding: 8,
                                color: '#94a3b8',
                                font: { family: 'Inter', size: 10 },
                                callback(value) {
                                    const n = Number(value);
                                    if (n >= 1000000) return '₱' + (n / 1000000).toFixed(1) + 'M';
                                    if (n >= 1000) return '₱' + (n / 1000).toFixed(n >= 10000 ? 0 : 1) + 'K';
                                    return '₱' + n;
                                },
                            },
                        },
                    },
                },
                plugins: [softBlueShadowPlugin, hoverLinePlugin],
            });
        }

        buildChart();

        if (yearSelect) {
            yearSelect.addEventListener('change', async function () {
                const year = yearSelect.value;
                const url = new URL(window.location.href);
                url.searchParams.set('year', year);
                url.searchParams.set('partial', 'funds_chart');
                url.searchParams.set('t', String(Date.now()));

                try {
                    const res = await fetch(url.pathname + url.search, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    const months = Array.isArray(data.months) ? data.months : [];
                    chartLabels = months.map(m => m.month_label);
                    chartReleased = months.map(m => Number(m.released || 0));
                    chartCounts = months.map(m => Number(m.count || 0));
                    chartAverages = months.map(m => Number(m.average || 0));
                    updateHeader(data.year || year, data.total || 0);
                    buildChart();

                    const clean = new URL(window.location.href);
                    clean.searchParams.set('year', year);
                    clean.searchParams.delete('partial');
                    clean.searchParams.delete('t');
                    window.history.replaceState({}, '', clean.pathname + clean.search);
                } catch (err) {
                    console.error(err);
                }
            });
        }
    });
</script>
@endsection
