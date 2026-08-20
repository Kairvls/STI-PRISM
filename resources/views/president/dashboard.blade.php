@extends('layouts.president-layout')

@section('title', 'President Dashboard')

@section('content')

{{-- ===================================== --}}
{{-- TOP HEADER (page title lives in topbar) --}}
{{-- ===================================== --}}
<div class="fade-in">
    <p class="text-sm text-gray-500">Welcome back, President. Here's your overview.</p>
</div>

{{-- ===================================== --}}
{{-- KPI SUMMARY CARDS --}}
{{-- ===================================== --}}
<div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <div class="pm-stat-card relative slide-up" style="animation-delay: 0.05s">
        <div class="pm-stat-icon bg-blue-50 text-blue-600">
            <i data-lucide="file-text"></i>
        </div>
        <div class="min-w-0 flex-1">
            <p class="pm-stat-label">All time records</p>
            <p class="pm-stat-value"><span class="count-up" data-target="{{ $totalRisCount ?? 0 }}">0</span> Total RIS</p>
        </div>
    </div>

    <div class="pm-stat-card relative slide-up" style="animation-delay: 0.1s">
        <div class="pm-stat-icon bg-blue-50 text-blue-600">
            <i data-lucide="clock-3"></i>
        </div>
        <div class="min-w-0 flex-1">
            <p class="pm-stat-label">Awaiting decision</p>
            <p class="pm-stat-value is-blue"><span class="count-up" data-target="{{ $pendingApprovalsCount ?? 0 }}">0</span> Pending</p>
        </div>
        <a href="/president/approvals" class="absolute inset-0 z-10 opacity-0"><span class="sr-only">View pending</span></a>
    </div>

    <div class="pm-stat-card relative slide-up" style="animation-delay: 0.15s">
        <div class="pm-stat-icon bg-blue-50 text-blue-600">
            <i data-lucide="circle-check-big"></i>
        </div>
        <div class="min-w-0 flex-1">
            <p class="pm-stat-label">Successfully approved</p>
            <p class="pm-stat-value is-blue"><span class="count-up" data-target="{{ $approvedDecisionsCount ?? 0 }}">0</span> Approved</p>
        </div>
        <a href="/president/reports/approved" class="absolute inset-0 z-10 opacity-0"><span class="sr-only">View approved</span></a>
    </div>

    <div class="pm-stat-card relative slide-up" style="animation-delay: 0.2s">
        <div class="pm-stat-icon bg-slate-100 text-slate-600">
            <i data-lucide="x-circle"></i>
        </div>
        <div class="min-w-0 flex-1">
            <p class="pm-stat-label">Declined requests</p>
            <p class="pm-stat-value"><span class="count-up" data-target="{{ $rejectedDecisionsCount ?? 0 }}">0</span> Rejected</p>
        </div>
        <a href="/president/reports/approved?filter=rejected" class="absolute inset-0 z-10 opacity-0"><span class="sr-only">View rejected</span></a>
    </div>
</div>

{{-- ===================================== --}}
{{-- CHARTS + TOP 3 RECENT RIS --}}
{{-- ===================================== --}}
@php
    $chartApprovedTotal = collect($monthlyStats ?? [])->sum('approved');
    $chartRejectedTotal = collect($monthlyStats ?? [])->sum('rejected');
    $chartDecisionTotal = $chartApprovedTotal + $chartRejectedTotal;
@endphp
<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <section class="pm-analytics-card lg:col-span-2 slide-up" style="animation-delay: 0.22s">
        <div class="pm-analytics-header">
            <div>
                <h2 class="pm-analytics-title">Decision Trend</h2>
                <p class="pm-analytics-subtitle">Last 6 months · approvals &amp; rejections</p>
            </div>
            <div class="pm-chart-total is-blue">
                {{ number_format($chartDecisionTotal) }}
                <span>decisions</span>
            </div>
        </div>

        <div class="pm-decision-chart-legend">
            <div class="pm-decision-chart-legend-item">
                <span class="pm-decision-chart-swatch is-approved"></span>
                Approved
            </div>
            <div class="pm-decision-chart-legend-item">
                <span class="pm-decision-chart-swatch is-rejected"></span>
                Rejected
            </div>
        </div>

        <div class="pm-decision-chart">
            <canvas id="dashboardChart"></canvas>
        </div>
    </section>

    <aside class="pm-card p-5 slide-up" style="animation-delay: 0.25s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Recent RIS</h2>
                <p class="mt-0.5 text-xs text-gray-400">Top 3 awaiting your decision</p>
            </div>
            <a href="/president/approvals" class="text-xs font-semibold text-blue-600 transition hover:text-blue-800" data-tip="Open approval queue">
                View all
            </a>
        </div>
        <div class="mt-4 space-y-2.5">
            @forelse ($recentRis as $ris)
                @php
                    $label = $ris->ris_form_number ?? ('RIS #' . $ris->ris_id);
                    $date = $ris->ris_created_at ? date('M d, Y', strtotime($ris->ris_created_at)) : '—';
                    $requester = $ris->ris_requested_by_signature ?: '—';
                    $amount = number_format((float) ($ris->total_amount ?? 0), 2);
                @endphp
                <div class="rounded-xl border border-blue-100 bg-white px-3 py-3 transition hover:border-blue-200 hover:bg-blue-50/40">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-gray-900">{{ $label }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-gray-500">{{ $requester }} · {{ $date }}</p>
                            <p class="mt-1 text-xs font-semibold text-blue-700">₱{{ $amount }}</p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="button" class="icon-btn" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument({{ $ris->ris_id }})">
                                <i data-lucide="printer" class="h-4 w-4"></i>
                            </button>
                            <a
                                href="/president/approvals?approve={{ $ris->ris_id }}"
                                class="inline-flex h-9 items-center rounded-xl bg-blue-600 px-3 text-[11px] font-medium text-white transition hover:bg-blue-700"
                                data-tip="Open and approve this RIS"
                            >
                                Approve
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-200 px-3 py-8 text-center">
                    <p class="text-xs font-medium text-gray-500">No pending RIS right now</p>
                    <p class="mt-1 text-[11px] text-gray-400">New forwarded requests will appear here</p>
                </div>
            @endforelse
        </div>
    </aside>
</div>

{{-- ===================================== --}}
{{-- ATTENTION + RECENTLY APPROVED --}}
{{-- ===================================== --}}
<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <section class="lg:col-span-2 pm-card p-5 slide-up" style="animation-delay: 0.28s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Needs Your Attention</h2>
                <p class="mt-0.5 text-xs text-gray-400">Actions that keep the approval workflow moving</p>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-3">
            <a href="/president/approvals" class="group rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-4 transition hover:border-blue-200 hover:bg-blue-50">
                <div class="flex items-center justify-between">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-blue-600 ring-1 ring-blue-100">
                        <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                    </div>
                    <span class="text-xl font-bold text-blue-700">{{ $pendingApprovalsCount ?? 0 }}</span>
                </div>
                <p class="mt-3 text-sm font-semibold text-gray-900">Pending review</p>
                <p class="mt-0.5 text-[11px] text-gray-500">RIS waiting for your decision</p>
            </a>
            <a href="/president/approvals" class="group rounded-xl border border-slate-200 bg-white px-4 py-4 transition hover:border-blue-200 hover:bg-blue-50/40">
                <div class="flex items-center justify-between">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                        <i data-lucide="bell" class="h-4 w-4"></i>
                    </div>
                    <span class="text-xl font-bold text-blue-700">{{ $awaitingNotifyCount ?? 0 }}</span>
                </div>
                <p class="mt-3 text-sm font-semibold text-gray-900">Ready to notify</p>
                <p class="mt-0.5 text-[11px] text-gray-500">Approved, Admin not yet notified</p>
            </a>
            <a href="/president/approvals/history" class="group rounded-xl border border-slate-200 bg-white px-4 py-4 transition hover:border-blue-200 hover:bg-blue-50/40">
                <div class="flex items-center justify-between">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                        <i data-lucide="history" class="h-4 w-4"></i>
                    </div>
                    <i data-lucide="chevron-right" class="h-4 w-4 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-blue-500"></i>
                </div>
                <p class="mt-3 text-sm font-semibold text-gray-900">Approval history</p>
                <p class="mt-0.5 text-[11px] text-gray-500">Review past decisions</p>
            </a>
        </div>
    </section>

    <aside class="pm-card p-5 slide-up" style="animation-delay: 0.3s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Recently Approved RIS</h2>
                <p class="mt-0.5 text-xs text-gray-400">Your latest approvals</p>
            </div>
            <a href="/president/reports/approved" class="text-xs font-semibold text-blue-600 transition hover:text-blue-800" data-tip="View all approved RIS">
                View all
            </a>
        </div>
        <div class="mt-4 space-y-1">
            @forelse ($recentlyApprovedRis ?? [] as $ris)
                @php
                    $label = $ris->ris_form_number ?? ('RIS #' . $ris->ris_id);
                    $date = $ris->ris_approved_by_date
                        ? date('M d, Y', strtotime($ris->ris_approved_by_date))
                        : '—';
                    $awaiting = !empty($ris->awaiting_notify);
                @endphp
                <div class="flex items-center gap-1">
                    <a
                        href="/president/approvals?preview={{ $ris->ris_id }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all duration-200 hover:bg-blue-50/60 min-w-0 flex-1"
                        data-tip="Open approved RIS"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <i data-lucide="badge-check" class="h-4 w-4"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-gray-900">{{ $label }}</p>
                            <p class="text-[11px] text-gray-500">{{ $date }}</p>
                        </div>
                        @if ($awaiting)
                            <span class="inline-flex items-center rounded-xl bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-100">
                                Notify Admin
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-xl bg-blue-600 px-2 py-0.5 text-[10px] font-semibold text-white">
                                Approved
                            </span>
                        @endif
                    </a>
                    <button type="button" class="icon-btn shrink-0" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument({{ $ris->ris_id }})">
                        <i data-lucide="printer" class="h-4 w-4"></i>
                    </button>
                </div>
            @empty
                <p class="px-3 py-4 text-center text-xs text-gray-400">No approved RIS yet</p>
            @endforelse
        </div>
    </aside>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes chartFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .fade-in { animation: fadeIn 0.4s ease-out forwards; }
    .slide-up { opacity: 0; animation: slideUp 0.5s ease-out forwards; }
    .count-up { display: inline-block; }

    /* Maintenance-style analytics chart card */
    .pm-analytics-card {
        min-width: 0;
        overflow: hidden;
        padding: 22px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .pm-analytics-card .pm-stat-value {
        font-size: inherit;
    }
    .pm-analytics-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 12px;
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
    .pm-chart-total.is-blue {
        color: #1d4ed8;
    }
    .pm-chart-total span {
        margin-left: 2px;
        font-size: 9px;
        font-weight: 500;
        color: #94a3b8;
    }
    .pm-decision-chart-legend {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 8px;
    }
    .pm-decision-chart-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 500;
        color: #64748b;
    }
    .pm-decision-chart-swatch {
        width: 10px;
        height: 3px;
        border-radius: 999px;
    }
    .pm-decision-chart-swatch.is-approved { background: #2563EB; }
    .pm-decision-chart-swatch.is-rejected { background: #94a3b8; }
    .pm-decision-chart {
        position: relative;
        width: 100%;
        height: 320px;
        animation: chartFadeIn 0.8s ease-out forwards;
    }
</style>

<script>
    window.printRisDocument = function (risId) {
        if (!risId) return;
        const win = window.open('/president/ris/' + risId + '/print', '_blank', 'noopener,noreferrer,width=1200,height=860');
        if (!win) return;
        const triggerPrint = function () {
            try { win.focus(); win.print(); } catch (e) {}
        };
        win.onload = triggerPrint;
        setTimeout(triggerPrint, 1200);
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }

        const counters = document.querySelectorAll('.count-up');
        counters.forEach(el => {
            const target = parseInt(el.dataset.target || el.textContent || '0', 10);
            if (target === 0) return;
            let current = 0;
            const step = Math.max(1, Math.floor(target / 30));
            const interval = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(interval);
                }
                el.textContent = current;
            }, 30);
        });

        const chartLabels = @json(array_column($monthlyStats ?? [], 'month_label'));
        const chartApproved = @json(array_column($monthlyStats ?? [], 'approved'));
        const chartRejected = @json(array_column($monthlyStats ?? [], 'rejected'));
        const canvas = document.getElementById('dashboardChart');

        if (!canvas || !chartLabels.length) {
            return;
        }

        const blueShadowPlugin = {
            id: 'presidentBlueShadowPlugin',
            beforeDatasetsDraw(chart) {
                const meta = chart.getDatasetMeta(0);
                if (!meta || meta.hidden || !meta.data.length || !meta.dataset) {
                    return;
                }

                const ctx = chart.ctx;
                const chartArea = chart.chartArea;
                const points = meta.data;
                const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
                gradient.addColorStop(0.35, 'rgba(37, 99, 235, 0.16)');
                gradient.addColorStop(0.7, 'rgba(37, 99, 235, 0.06)');
                gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');

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
            id: 'presidentDecisionHoverLine',
            afterDatasetsDraw(chart) {
                const activeElements = chart.tooltip?.getActiveElements();
                if (!activeElements?.length) {
                    return;
                }

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

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Approved',
                        data: chartApproved,
                        borderColor: '#2563EB',
                        backgroundColor: 'transparent',
                        borderWidth: 1.5,
                        fill: false,
                        tension: 0.42,
                        cubicInterpolationMode: 'monotone',
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHitRadius: 25,
                        pointHoverBackgroundColor: '#2563EB',
                        pointHoverBorderColor: 'white',
                        pointHoverBorderWidth: 2,
                    },
                    {
                        label: 'Rejected',
                        data: chartRejected,
                        borderColor: '#94a3b8',
                        backgroundColor: 'transparent',
                        borderWidth: 1.5,
                        fill: false,
                        tension: 0.42,
                        cubicInterpolationMode: 'monotone',
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHitRadius: 25,
                        pointHoverBackgroundColor: '#94a3b8',
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
                                const value = Math.round(Number(context.raw));
                                return context.dataset.label + '     ' + value;
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
                            precision: 0,
                            padding: 8,
                            color: '#94a3b8',
                            font: { family: 'Inter', size: 10 },
                        },
                    },
                },
            },
            plugins: [blueShadowPlugin, hoverLinePlugin],
        });
    });
</script>

@endsection
