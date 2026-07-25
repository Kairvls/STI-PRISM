@extends('layouts.president-layout')

@section('title', 'President Dashboard')

@section('content')

{{-- ===================================== --}}
{{-- TOP HEADER --}}
{{-- ===================================== --}}
<div class="fade-in">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h1>
    <p class="mt-0.5 text-sm text-gray-500">Welcome back, President. Here's your overview.</p>
</div>

{{-- ===================================== --}}
{{-- KPI SUMMARY CARDS --}}
{{-- ===================================== --}}
<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    {{-- Total RIS --}}
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

    {{-- Pending --}}
    <div class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 transition-all duration-300 hover:border-amber-200 hover:shadow-lg card-hover slide-up" style="animation-delay: 0.1s">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Pending</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 count-up" data-target="{{ $pendingApprovalsCount ?? 0 }}">0</p>
                <p class="mt-1 text-[11px] text-amber-500">Awaiting decision</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                <i data-lucide="clock-3" class="h-5 w-5"></i>
            </div>
        </div>
        <a href="/president/approvals" class="absolute inset-0 z-10 opacity-0"><span class="sr-only">View pending</span></a>
    </div>

    {{-- Approved --}}
    <div class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 transition-all duration-300 hover:border-emerald-200 hover:shadow-lg card-hover slide-up" style="animation-delay: 0.15s">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Approved</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 count-up" data-target="{{ $approvedDecisionsCount ?? 0 }}">0</p>
                <p class="mt-1 text-[11px] text-emerald-500">Successfully approved</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                <i data-lucide="circle-check-big" class="h-5 w-5"></i>
            </div>
        </div>
        <a href="/president/reports/approved" class="absolute inset-0 z-10 opacity-0"><span class="sr-only">View approved</span></a>
    </div>

    {{-- Rejected --}}
    <div class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 transition-all duration-300 hover:border-rose-200 hover:shadow-lg card-hover slide-up" style="animation-delay: 0.2s">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-700">Rejected</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 count-up" data-target="{{ $rejectedDecisionsCount ?? 0 }}">0</p>
                <p class="mt-1 text-[11px] text-rose-500">Declined requests</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-rose-50 text-rose-600 ring-1 ring-rose-100">
                <i data-lucide="x-circle" class="h-5 w-5"></i>
            </div>
        </div>
        <a href="/president/reports/approved?filter=rejected" class="absolute inset-0 z-10 opacity-0"><span class="sr-only">View rejected</span></a>
    </div>
</div>

{{-- ===================================== --}}
{{-- CHARTS + RECENT ACTIVITY ROW --}}
{{-- ===================================== --}}
<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
    {{-- Monthly Trend Chart --}}
    <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.22s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Monthly Trend</h2>
                <p class="mt-0.5 text-xs text-gray-400">Approved vs Rejected over the last 6 months</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    <span class="text-[11px] font-medium text-gray-500">Approved</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                    <span class="text-[11px] font-medium text-gray-500">Rejected</span>
                </div>
            </div>
        </div>
        <div class="mt-4" style="height: 240px; position: relative;">
            <canvas id="dashboardChart"></canvas>
        </div>
    </div>

    {{-- Recent Activity --}}
    <aside class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.25s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Recent Activity</h2>
                <p class="mt-0.5 text-xs text-gray-400">Latest RIS updates</p>
            </div>
            <a href="/president/approvals/history" class="text-xs font-semibold text-gray-900 transition hover:text-amber-600">
                View all
            </a>
        </div>
        <div class="mt-4 space-y-1">
            @forelse ($recentRis as $ris)
                @php
                    $statusLower = strtolower($ris->ris_status ?? '');
                    $icon = $statusLower === 'approved' ? 'circle-check-big' : ($statusLower === 'rejected' ? 'x-circle' : 'clock-3');
                    $color = $statusLower === 'approved' ? 'text-emerald-600 bg-emerald-50 ring-emerald-100' : ($statusLower === 'rejected' ? 'text-rose-600 bg-rose-50 ring-rose-100' : 'text-amber-600 bg-amber-50 ring-amber-100');
                    $date = $ris->ris_created_at ? date('M d, Y', strtotime($ris->ris_created_at)) : '—';
                    $label = $ris->ris_form_number ?? ('RIS #' . $ris->ris_id);
                @endphp
                <a href="/president/approvals" class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition-all duration-200 hover:bg-gray-50">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $color }} ring-1">
                        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-900 truncate">{{ $label }}</p>
                        <p class="text-[11px] text-gray-500">{{ $date }}</p>
                    </div>
                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold {{ $statusLower === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($statusLower === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">
                        {{ $ris->ris_status ?? 'Pending' }}
                    </span>
                </a>
            @empty
                <p class="px-3 py-4 text-center text-xs text-gray-400">No recent activity</p>
            @endforelse
        </div>
    </aside>
</div>

{{-- ===================================== --}}
{{-- QUICK ACTIONS + STATUS BREAKDOWN --}}
{{-- ===================================== --}}
<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    {{-- Quick Actions --}}
    <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.28s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Quick Actions</h2>
                <p class="mt-0.5 text-xs text-gray-400">Navigate to key sections</p>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
            <a href="/president/approvals" class="group flex items-center gap-3.5 rounded-lg border border-gray-100 bg-gray-50/50 px-4 py-3.5 transition-all duration-200 hover:border-gray-200 hover:bg-white hover:shadow-sm active:scale-[0.98]">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-gray-700 ring-1 ring-gray-200">
                    <i data-lucide="clipboard-check" class="h-5 w-5"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">RIS Review</p>
                    <p class="text-[11px] text-gray-500">Pending approvals</p>
                </div>
                <i data-lucide="chevron-right" class="h-4 w-4 shrink-0 text-gray-300 transition-all duration-200 group-hover:text-gray-500 group-hover:translate-x-0.5"></i>
            </a>
            <a href="/president/approvals/history" class="group flex items-center gap-3.5 rounded-lg border border-gray-100 bg-gray-50/50 px-4 py-3.5 transition-all duration-200 hover:border-gray-200 hover:bg-white hover:shadow-sm active:scale-[0.98]">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-gray-700 ring-1 ring-gray-200">
                    <i data-lucide="history" class="h-5 w-5"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">Approval History</p>
                    <p class="text-[11px] text-gray-500">Past decisions</p>
                </div>
                <i data-lucide="chevron-right" class="h-4 w-4 shrink-0 text-gray-300 transition-all duration-200 group-hover:text-gray-500 group-hover:translate-x-0.5"></i>
            </a>
            <a href="/president/reports/approved" class="group flex items-center gap-3.5 rounded-lg border border-gray-100 bg-gray-50/50 px-4 py-3.5 transition-all duration-200 hover:border-gray-200 hover:bg-white hover:shadow-sm active:scale-[0.98]">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-gray-700 ring-1 ring-gray-200">
                    <i data-lucide="badge-check" class="h-5 w-5"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">Decision Reports</p>
                    <p class="text-[11px] text-gray-500">View all decisions</p>
                </div>
                <i data-lucide="chevron-right" class="h-4 w-4 shrink-0 text-gray-300 transition-all duration-200 group-hover:text-gray-500 group-hover:translate-x-0.5"></i>
            </a>
            <a href="/president/reports/summary" class="group flex items-center gap-3.5 rounded-lg border border-gray-100 bg-gray-50/50 px-4 py-3.5 transition-all duration-200 hover:border-gray-200 hover:bg-white hover:shadow-sm active:scale-[0.98]">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-gray-700 ring-1 ring-gray-200">
                    <i data-lucide="bar-chart-3" class="h-5 w-5"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">Reports & Summary</p>
                    <p class="text-[11px] text-gray-500">Analytics & trends</p>
                </div>
                <i data-lucide="chevron-right" class="h-4 w-4 shrink-0 text-gray-300 transition-all duration-200 group-hover:text-gray-500 group-hover:translate-x-0.5"></i>
            </a>
        </div>
    </section>

    {{-- Status Breakdown --}}
    <aside class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.3s">
        <div>
            <h2 class="text-sm font-bold text-gray-900">Status Overview</h2>
            <p class="mt-0.5 text-xs text-gray-400">Breakdown of all RIS records</p>
        </div>
        <div class="mt-5 space-y-4">
            @php
                $total = max(($totalRisCount ?? 1), 1);
                $approvedPct = round((($approvedDecisionsCount ?? 0) / $total) * 100);
                $pendingPct = round((($pendingApprovalsCount ?? 0) / $total) * 100);
                $rejectedPct = round((($rejectedDecisionsCount ?? 0) / $total) * 100);
            @endphp
            {{-- Approved --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-medium text-gray-700">Approved</span>
                    </div>
                    <span class="text-xs font-semibold text-gray-900">{{ $approvedDecisionsCount ?? 0 }}</span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-100">
                    <div class="h-2 rounded-full bg-emerald-500 transition-all duration-1000" style="width: {{ $approvedPct }}%"></div>
                </div>
            </div>
            {{-- Pending --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                        <span class="text-xs font-medium text-gray-700">Pending</span>
                    </div>
                    <span class="text-xs font-semibold text-gray-900">{{ $pendingApprovalsCount ?? 0 }}</span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-100">
                    <div class="h-2 rounded-full bg-amber-400 transition-all duration-1000" style="width: {{ $pendingPct }}%"></div>
                </div>
            </div>
            {{-- Rejected --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                        <span class="text-xs font-medium text-gray-700">Rejected</span>
                    </div>
                    <span class="text-xs font-semibold text-gray-900">{{ $rejectedDecisionsCount ?? 0 }}</span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-100">
                    <div class="h-2 rounded-full bg-rose-400 transition-all duration-1000" style="width: {{ $rejectedPct }}%"></div>
                </div>
            </div>
        </div>
        {{-- Mini summary --}}
        <div class="mt-5 flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
            <span class="text-xs font-medium text-gray-500">Total records</span>
            <span class="text-sm font-bold text-gray-900">{{ $totalRisCount ?? 0 }}</span>
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

    .fade-in {
        animation: fadeIn 0.4s ease-out forwards;
    }

    .slide-up {
        opacity: 0;
        animation: slideUp 0.5s ease-out forwards;
    }

    .card-hover {
        transition: all 0.25s ease;
    }

    .card-hover:hover {
        transform: translateY(-3px);
    }

    .count-up {
        display: inline-block;
    }

    #dashboardChart {
        animation: chartFadeIn 0.8s ease-out forwards;
        opacity: 0;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }

        // =====================================================
        // COUNT-UP ANIMATION
        // =====================================================
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

        // =====================================================
        // MONTHLY TREND CHART
        // =====================================================
        const chartLabels = @json(array_column($monthlyStats ?? [], 'month_label'));
        const chartApproved = @json(array_column($monthlyStats ?? [], 'approved'));
        const chartRejected = @json(array_column($monthlyStats ?? [], 'rejected'));

        const ctx = document.getElementById('dashboardChart');
        if (ctx && chartLabels.length > 0) {
            const approvedGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 240);
            approvedGradient.addColorStop(0, 'rgba(5, 150, 105, 0.28)');
            approvedGradient.addColorStop(0.4, 'rgba(5, 150, 105, 0.08)');
            approvedGradient.addColorStop(1, 'rgba(5, 150, 105, 0.01)');

            const rejectedGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 240);
            rejectedGradient.addColorStop(0, 'rgba(225, 29, 72, 0.18)');
            rejectedGradient.addColorStop(0.4, 'rgba(225, 29, 72, 0.06)');
            rejectedGradient.addColorStop(1, 'rgba(225, 29, 72, 0.01)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Approved',
                            data: chartApproved,
                            borderColor: '#059669',
                            backgroundColor: approvedGradient,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#059669',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2.5,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#059669',
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 3,
                            borderWidth: 2.5,
                        },
                        {
                            label: 'Rejected',
                            data: chartRejected,
                            borderColor: '#fb7185',
                            backgroundColor: rejectedGradient,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#fb7185',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2.5,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#fb7185',
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 3,
                            borderWidth: 2.5,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1200,
                        easing: 'easeInOutQuart',
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            titleColor: '#f3f4f6',
                            titleFont: { size: 12, weight: '600' },
                            bodyColor: '#e5e7eb',
                            bodyFont: { size: 11 },
                            padding: 12,
                            cornerRadius: 8,
                            boxPadding: 6,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
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
                                font: { size: 11 },
                                color: '#9ca3af',
                                padding: 8,
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.04)',
                                drawBorder: false,
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                font: { size: 11, weight: '500' },
                                color: '#6b7280',
                                maxRotation: 0,
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    elements: {
                        point: {
                            hover: {
                                scale: 1.5,
                            }
                        }
                    },
                    hover: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            });
        }
    });
</script>

@endsection