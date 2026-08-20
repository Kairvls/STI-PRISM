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
<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 transition-all duration-300 hover:border-gray-300 hover:shadow-lg card-hover slide-up" style="animation-delay: 0.05s">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total RIS</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 count-up" data-target="{{ $totalRisCount ?? 0 }}">0</p>
                <p class="mt-1 text-[11px] text-gray-400">All time records</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-50 text-slate-600 ring-1 ring-slate-100">
                <i data-lucide="file-text" class="h-5 w-5"></i>
            </div>
        </div>
    </div>

    <div class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 transition-all duration-300 hover:border-slate-200 hover:shadow-lg card-hover slide-up" style="animation-delay: 0.1s">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-700">Pending</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 count-up" data-target="{{ $pendingApprovalsCount ?? 0 }}">0</p>
                <p class="mt-1 text-[11px] text-slate-500">Awaiting decision</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 text-slate-600 ring-1 ring-slate-200">
                <i data-lucide="clock-3" class="h-5 w-5"></i>
            </div>
        </div>
        <a href="/president/approvals" class="absolute inset-0 z-10 opacity-0"><span class="sr-only">View pending</span></a>
    </div>

    <div class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 transition-all duration-300 hover:border-blue-200 hover:shadow-lg card-hover slide-up" style="animation-delay: 0.15s">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">Approved</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 count-up" data-target="{{ $approvedDecisionsCount ?? 0 }}">0</p>
                <p class="mt-1 text-[11px] text-blue-500">Successfully approved</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                <i data-lucide="circle-check-big" class="h-5 w-5"></i>
            </div>
        </div>
        <a href="/president/reports/approved" class="absolute inset-0 z-10 opacity-0"><span class="sr-only">View approved</span></a>
    </div>

    <div class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 transition-all duration-300 hover:border-slate-200 hover:shadow-lg card-hover slide-up" style="animation-delay: 0.2s">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-700">Rejected</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 count-up" data-target="{{ $rejectedDecisionsCount ?? 0 }}">0</p>
                <p class="mt-1 text-[11px] text-slate-500">Declined requests</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 text-slate-600 ring-1 ring-slate-200">
                <i data-lucide="x-circle" class="h-5 w-5"></i>
            </div>
        </div>
        <a href="/president/reports/approved?filter=rejected" class="absolute inset-0 z-10 opacity-0"><span class="sr-only">View rejected</span></a>
    </div>
</div>

{{-- ===================================== --}}
{{-- CHARTS + TOP 3 RECENT RIS --}}
{{-- ===================================== --}}
<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.22s">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Decision Trend</h2>
                <p class="mt-0.5 text-xs text-gray-400">Last 6 months · approvals &amp; rejections</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-sm bg-blue-500"></span>
                    <span class="text-[11px] font-medium text-gray-500">Approved</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-sm bg-slate-400"></span>
                    <span class="text-[11px] font-medium text-gray-500">Rejected</span>
                </div>
            </div>
        </div>
        <div class="mt-4" style="height: 260px; position: relative;">
            <canvas id="dashboardChart"></canvas>
        </div>
        @php
            $chartApprovedTotal = collect($monthlyStats ?? [])->sum('approved');
            $chartRejectedTotal = collect($monthlyStats ?? [])->sum('rejected');
            $chartDecisionTotal = max($chartApprovedTotal + $chartRejectedTotal, 1);
            $approvalRate = round(($chartApprovedTotal / $chartDecisionTotal) * 100);
        @endphp
        <div class="mt-4 grid grid-cols-3 gap-3 border-t border-gray-100 pt-4">
            <div class="rounded-lg bg-blue-50 px-3 py-2.5">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-blue-700">Approved</p>
                <p class="mt-0.5 text-lg font-bold text-blue-800">{{ $chartApprovedTotal }}</p>
            </div>
            <div class="rounded-lg bg-slate-100 px-3 py-2.5">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-700">Rejected</p>
                <p class="mt-0.5 text-lg font-bold text-slate-800">{{ $chartRejectedTotal }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-600">Approval rate</p>
                <p class="mt-0.5 text-lg font-bold text-slate-800">{{ $approvalRate }}%</p>
            </div>
        </div>
    </div>

    <aside class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.25s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Recent RIS</h2>
                <p class="mt-0.5 text-xs text-gray-400">Top 3 awaiting your decision</p>
            </div>
            <a href="/president/approvals" class="text-xs font-semibold text-gray-900 transition hover:text-slate-600" data-tip="Open approval queue">
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
                <div class="rounded-lg border border-gray-100 bg-gray-50/60 px-3 py-3 transition hover:border-slate-200 hover:bg-slate-50">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-gray-900">{{ $label }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-gray-500">{{ $requester }} · {{ $date }}</p>
                            <p class="mt-1 text-xs font-semibold text-gray-800">₱{{ $amount }}</p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="button" class="icon-btn" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument({{ $ris->ris_id }})">
                                <i data-lucide="printer" class="h-4 w-4"></i>
                            </button>
                            <a
                                href="/president/approvals?approve={{ $ris->ris_id }}"
                                class="inline-flex items-center rounded-md bg-blue-600 px-2.5 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-blue-700"
                                data-tip="Open and approve this RIS"
                            >
                                Approve
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-gray-200 px-3 py-8 text-center">
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
    <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.28s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Needs Your Attention</h2>
                <p class="mt-0.5 text-xs text-gray-400">Actions that keep the approval workflow moving</p>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-3">
            <a href="/president/approvals" class="group rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 transition hover:border-slate-200 hover:bg-slate-100">
                <div class="flex items-center justify-between">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-slate-200">
                        <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                    </div>
                    <span class="text-xl font-bold text-slate-800">{{ $pendingApprovalsCount ?? 0 }}</span>
                </div>
                <p class="mt-3 text-sm font-semibold text-gray-900">Pending review</p>
                <p class="mt-0.5 text-[11px] text-gray-500">RIS waiting for your decision</p>
            </a>
            <a href="/president/approvals" class="group rounded-lg border border-sky-100 bg-sky-50/50 px-4 py-4 transition hover:border-sky-200 hover:bg-sky-50">
                <div class="flex items-center justify-between">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-sky-600 ring-1 ring-sky-100">
                        <i data-lucide="bell" class="h-4 w-4"></i>
                    </div>
                    <span class="text-xl font-bold text-sky-800">{{ $awaitingNotifyCount ?? 0 }}</span>
                </div>
                <p class="mt-3 text-sm font-semibold text-gray-900">Ready to notify</p>
                <p class="mt-0.5 text-[11px] text-gray-500">Approved, Admin not yet notified</p>
            </a>
            <a href="/president/approvals/history" class="group rounded-lg border border-gray-100 bg-gray-50/60 px-4 py-4 transition hover:border-gray-200 hover:bg-white hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-gray-600 ring-1 ring-gray-200">
                        <i data-lucide="history" class="h-4 w-4"></i>
                    </div>
                    <i data-lucide="chevron-right" class="h-4 w-4 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-gray-500"></i>
                </div>
                <p class="mt-3 text-sm font-semibold text-gray-900">Approval history</p>
                <p class="mt-0.5 text-[11px] text-gray-500">Review past decisions</p>
            </a>
        </div>
    </section>

    <aside class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.3s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Recently Approved RIS</h2>
                <p class="mt-0.5 text-xs text-gray-400">Your latest approvals</p>
            </div>
            <a href="/president/reports/approved" class="text-xs font-semibold text-gray-900 transition hover:text-blue-600" data-tip="View all approved RIS">
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
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition-all duration-200 hover:bg-gray-50 min-w-0 flex-1"
                        data-tip="Open approved RIS"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                            <i data-lucide="badge-check" class="h-4 w-4"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-gray-900">{{ $label }}</p>
                            <p class="text-[11px] text-gray-500">{{ $date }}</p>
                        </div>
                        @if ($awaiting)
                            <span class="inline-flex items-center rounded-md bg-sky-50 px-2 py-0.5 text-[10px] font-semibold text-sky-700">
                                Notify Admin
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
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
    .card-hover { transition: all 0.25s ease; }
    .card-hover:hover { transform: translateY(-3px); }
    .count-up { display: inline-block; }
    #dashboardChart { animation: chartFadeIn 0.8s ease-out forwards; opacity: 0; }
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

        const ctx = document.getElementById('dashboardChart');
        if (ctx && chartLabels.length > 0) {
            const chartCtx = ctx.getContext('2d');
            const approvedFill = chartCtx.createLinearGradient(0, 0, 0, 260);
            approvedFill.addColorStop(0, 'rgba(37, 99, 235, 0.22)');
            approvedFill.addColorStop(1, 'rgba(37, 99, 235, 0.02)');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            type: 'line',
                            label: 'Approved',
                            data: chartApproved,
                            borderColor: '#2563EB',
                            backgroundColor: approvedFill,
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2.5,
                            pointRadius: 3.5,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#2563EB',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            order: 1,
                            yAxisID: 'y',
                        },
                        {
                            type: 'bar',
                            label: 'Rejected',
                            data: chartRejected,
                            backgroundColor: 'rgba(148, 163, 184, 0.55)',
                            hoverBackgroundColor: 'rgba(71, 85, 105, 0.75)',
                            borderRadius: 6,
                            borderSkipped: false,
                            barPercentage: 0.45,
                            categoryPercentage: 0.6,
                            order: 2,
                            yAxisID: 'y',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 900, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#f8fafc',
                            titleFont: { size: 12, weight: '600' },
                            bodyColor: '#e2e8f0',
                            bodyFont: { size: 11 },
                            padding: 12,
                            cornerRadius: 10,
                            boxPadding: 6,
                            displayColors: true,
                            callbacks: {
                                label: function (context) {
                                    return ' ' + context.dataset.label + ': ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                precision: 0,
                                font: { size: 11 },
                                color: '#94a3b8',
                                padding: 8,
                            },
                            grid: {
                                color: 'rgba(15, 23, 42, 0.05)',
                                drawBorder: false,
                            },
                            border: { display: false },
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 11, weight: '500' },
                                color: '#64748b',
                                maxRotation: 0,
                            },
                            border: { display: false },
                        }
                    },
                    interaction: { intersect: false, mode: 'index' }
                }
            });
        }
    });
</script>

@endsection
