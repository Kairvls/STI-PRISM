@extends('layouts.president-layout')

@section('title', 'Reports & Summary')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <p class="text-sm text-gray-500">Weekly and monthly RIS statistics and trends.</p>
    </div>
</div>

{{-- ======================================== --}}
{{-- EXECUTIVE INSIGHTS --}}
{{-- ======================================== --}}
    <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-5">
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.05s">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100">
                    <i data-lucide="check-circle" class="h-4 w-4 text-slate-700"></i>
                </div>
                <p class="text-xs font-medium text-slate-600">Decision Rate</p>
            </div>
            <div class="mt-3 space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-600">Approved</span>
                    <span class="text-sm font-semibold text-slate-900">{{ $insights['approval_rate'] ?? 0 }}%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-600">Rejected</span>
                    <span class="text-sm font-semibold text-slate-900">{{ $insights['rejection_rate'] ?? 0 }}%</span>
                </div>
            </div>
        </div>

        <div class="pm-kpi-card slide-up" style="animation-delay: 0.15s">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100">
                <i data-lucide="wallet" class="h-4 w-4 text-slate-700"></i>
            </div>
            <p class="text-xs font-medium text-slate-600">Approved Value</p>
        </div>
        <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">₱{{ number_format((float)($insights['approved_amount'] ?? 0), 2) }}</p>
    </div>

    <div class="pm-kpi-card slide-up" style="animation-delay: 0.2s">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100">
                <i data-lucide="wallet" class="h-4 w-4 text-slate-700"></i>
            </div>
            <p class="text-xs font-medium text-slate-600">Rejected Value</p>
        </div>
        <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">₱{{ number_format((float)($insights['rejected_amount'] ?? 0), 2) }}</p>
    </div>

    <div class="pm-kpi-card slide-up" style="animation-delay: 0.25s">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100">
                <i data-lucide="trending-up" class="h-4 w-4 text-slate-700"></i>
            </div>
            <p class="text-xs font-medium text-slate-600">Top Approval Month</p>
        </div>
        <p class="mt-3 text-lg font-semibold tracking-tight text-slate-900">{{ $insights['highest_approval_month'] ?? 'N/A' }}</p>
    </div>

    <div class="pm-kpi-card slide-up" style="animation-delay: 0.3s">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100">
                <i data-lucide="trending-down" class="h-4 w-4 text-slate-700"></i>
            </div>
            <p class="text-xs font-medium text-slate-600">Top Rejection Month</p>
        </div>
        <p class="mt-3 text-lg font-semibold tracking-tight text-slate-900">{{ $insights['highest_rejection_month'] ?? 'N/A' }}</p>
    </div>
</div>

{{-- Tabs --}}
<div class="mt-6 flex flex-wrap items-center gap-2">
    <button type="button" id="tabWeekly" onclick="switchTab('weekly')" class="action-btn inline-flex h-10 items-center justify-center gap-2 rounded-xl px-4 text-sm font-medium transition {{ request('tab') !== 'monthly' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 hover:text-slate-900' }}">
        <i data-lucide="trending-up" class="h-4 w-4"></i>
        <span>Weekly Summary</span>
    </button>
    <button type="button" id="tabMonthly" onclick="switchTab('monthly')" class="action-btn inline-flex h-10 items-center justify-center gap-2 rounded-xl px-4 text-sm font-medium transition {{ request('tab') === 'monthly' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 hover:text-slate-900' }}">
        <i data-lucide="calendar-days" class="h-4 w-4"></i>
        <span>Monthly Summary</span>
    </button>
</div>

{{-- Weekly Tab --}}
<div id="weeklyTab" class="mt-6 {{ request('tab') === 'monthly' ? 'hidden' : '' }}">
    {{-- Weekly Stats Cards --}}
    <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.05s">
            <p class="text-xs font-medium text-slate-500">Total RIS</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 count-up" data-target="{{ $totalRis ?? 0 }}">{{ $totalRis ?? 0 }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.1s">
            <p class="text-xs font-medium text-slate-500">Approved</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 count-up" data-target="{{ $approvedDecisionsCount ?? 0 }}">{{ $approvedDecisionsCount ?? 0 }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.15s">
            <p class="text-xs font-medium text-slate-500">Rejected</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 count-up" data-target="{{ $rejectedDecisionsCount ?? 0 }}">{{ $rejectedDecisionsCount ?? 0 }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.2s">
            <p class="text-xs font-medium text-slate-500">Pending</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 count-up" data-target="{{ $pendingApprovalsCount ?? 0 }}">{{ $pendingApprovalsCount ?? 0 }}</p>
        </div>
    </div>

    {{-- Weekly Chart --}}
    @php
        $weeklyApprovedTotal = collect($weeklyStats ?? [])->sum('approved');
        $weeklyRejectedTotal = collect($weeklyStats ?? [])->sum('rejected');
        $weeklyDecisionTotal = $weeklyApprovedTotal + $weeklyRejectedTotal;
    @endphp
    <section class="pm-analytics-card mt-6 slide-up" style="animation-delay: 0.25s">
        <div class="pm-analytics-header">
            <div>
                <h2 class="pm-analytics-title">Weekly Trend</h2>
                <p class="pm-analytics-subtitle">Last 4 weeks · approvals &amp; rejections</p>
            </div>
            <div class="pm-chart-total">
                {{ number_format($weeklyDecisionTotal) }}
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
            <canvas id="weeklyChart"></canvas>
        </div>
    </section>

    {{-- Weekly Table --}}
    <div class="mt-6 pm-card slide-up" style="animation-delay: 0.3s">
        <div class="p-5 pb-3">
            <h2 class="text-sm font-semibold text-gray-900">Weekly Breakdown</h2>
            <p class="mt-1 text-xs text-gray-500">Detailed performance metrics per week.</p>
        </div>
        <div class="overflow-x-auto">
            <table id="weeklyBreakdownTable" class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Period</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Total Received</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved Value</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected Value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($weeklyStats ?? [] as $row)
                        <tr class="border-b border-gray-100 transition-all duration-200 hover:bg-gray-50/60">
                            <td class="px-4 py-4 text-sm font-semibold text-gray-700">{{ $row['label'] }}</td>
                            <td class="px-4 py-4 text-center text-sm font-semibold text-gray-900">{{ $row['total'] ?? ($row['approved'] + $row['rejected'] + $row['pending']) }}</td>
                            <td class="px-4 py-4 text-center text-sm text-blue-700 font-semibold">{{ $row['approved'] }}</td>
                            <td class="px-4 py-4 text-center text-sm text-slate-700 font-semibold">{{ $row['rejected'] }}</td>
                            <td class="px-4 py-4 text-right tabular-nums text-gray-700">₱{{ number_format((float)($row['approved_amount'] ?? 0), 2) }}</td>
                            <td class="px-4 py-4 text-right tabular-nums text-gray-700">₱{{ number_format((float)($row['rejected_amount'] ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-2 py-12 text-center">
                                <p class="text-sm font-semibold text-gray-800">No weekly data available.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('president.partials.table-word-export', [
            'target' => '#weeklyBreakdownTable',
            'filename' => 'president-weekly-breakdown',
        ])
    </div>
</div>

{{-- Monthly Tab --}}
<div id="monthlyTab" class="mt-6 {{ request('tab') === 'monthly' ? '' : 'hidden' }}">
    {{-- Month & Year Picker --}}
    <div class="slide-up" style="animation-delay: 0.05s">
        <div class="flex flex-wrap items-center gap-3 rounded-[22px] border border-slate-200 bg-white p-3 shadow-[0_1px_3px_rgba(15,23,42,0.04)]">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-700">
                <i data-lucide="calendar-days" class="h-5 w-5"></i>
            </div>

            <div class="h-8 w-px bg-slate-200"></div>

            <div class="flex items-center gap-2">
                <select name="month" id="monthSelect" class="filter-select rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-slate-400">
                    @foreach([1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'] as $num => $name)
                        <option value="{{ $num }}" {{ (request('month') ?? date('m')) == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <select name="year" id="yearSelect" class="filter-select rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-slate-400">
                    @for ($y = date('Y'); $y >= date('Y') - 5; $y--)
                        <option value="{{ $y }}" {{ (request('year') ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="h-8 w-px bg-slate-200"></div>

            <button type="button" id="resetFilterBtn" class="action-btn inline-flex h-10 items-center justify-center gap-1.5 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 active:scale-95 {{ !request('month') && !request('year') ? 'opacity-40 cursor-not-allowed' : '' }}">
                <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                <span>Reset</span>
            </button>

            <div id="filterLoader" class="hidden ml-1">
                <div class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-slate-900"></div>
            </div>
        </div>
    </div>

    {{-- Monthly Stats Cards --}}
    <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.1s">
            <p class="text-xs font-medium text-slate-500">Total RIS</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 count-up" data-target="{{ $totalRis ?? 0 }}">{{ $totalRis ?? 0 }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.15s">
            <p class="text-xs font-medium text-slate-500">Approved</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 count-up" data-target="{{ $approvedDecisionsCount ?? 0 }}">{{ $approvedDecisionsCount ?? 0 }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.2s">
            <p class="text-xs font-medium text-slate-500">Rejected</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 count-up" data-target="{{ $rejectedDecisionsCount ?? 0 }}">{{ $rejectedDecisionsCount ?? 0 }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.25s">
            <p class="text-xs font-medium text-slate-500">Pending</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 count-up" data-target="{{ $pendingApprovalsCount ?? 0 }}">{{ $pendingApprovalsCount ?? 0 }}</p>
        </div>
    </div>

    {{-- Monthly Chart --}}
    <div class="mt-6 pm-card slide-up" style="animation-delay: 0.3s">
        <div class="p-5 pb-3">
            <h2 class="text-sm font-semibold text-gray-900">Monthly Analytics</h2>
            <p class="mt-1 text-xs text-gray-500">{{ $filterMonth && $filterYear ? 'Approved vs rejected RIS for the selected month.' : 'Approved vs rejected RIS over the last 6 months.' }}</p>
        </div>
        <div class="px-5 pb-5">
            <div style="height: 340px;">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Monthly Table --}}
    <div class="mt-6 pm-card slide-up" style="animation-delay: 0.35s">
        <div class="p-5 pb-3">
            <h2 class="text-sm font-semibold text-gray-900">Monthly Breakdown</h2>
            <p class="mt-1 text-xs text-gray-500">Detailed performance metrics per month.</p>
        </div>
        <div class="overflow-x-auto">
            <table id="monthlyBreakdownTable" class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Period</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Total Received</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved Value</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected Value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlyStats ?? [] as $row)
                        <tr class="border-b border-gray-100 transition-all duration-200 hover:bg-gray-50/60">
                            <td class="px-4 py-4 text-sm font-semibold text-gray-700">{{ $row['month_label'] }}</td>
                            <td class="px-4 py-4 text-center text-sm font-semibold text-gray-900">{{ $row['total'] ?? ($row['approved'] + $row['rejected'] + $row['pending']) }}</td>
                            <td class="px-4 py-4 text-center text-sm text-blue-700 font-semibold">{{ $row['approved'] }}</td>
                            <td class="px-4 py-4 text-center text-sm text-slate-700 font-semibold">{{ $row['rejected'] }}</td>
                            <td class="px-4 py-4 text-right tabular-nums text-gray-700">₱{{ number_format((float)($row['approved_amount'] ?? 0), 2) }}</td>
                            <td class="px-4 py-4 text-right tabular-nums text-gray-700">₱{{ number_format((float)($row['rejected_amount'] ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-2 py-12 text-center">
                                <p class="text-sm font-semibold text-gray-800">No monthly data available.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('president.partials.table-word-export', [
            'target' => '#monthlyBreakdownTable',
            'filename' => 'president-monthly-breakdown',
        ])
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
    function switchTab(tab) {
        const weeklyTab = document.getElementById('weeklyTab');
        const monthlyTab = document.getElementById('monthlyTab');
        const weeklyBtn = document.getElementById('tabWeekly');
        const monthlyBtn = document.getElementById('tabMonthly');

        if (tab === 'weekly') {
            weeklyTab.classList.remove('hidden');
            monthlyTab.classList.add('hidden');
            weeklyBtn.classList.remove('bg-white', 'text-slate-600', 'border', 'border-slate-200', 'hover:bg-slate-50', 'hover:text-slate-900');
            weeklyBtn.classList.add('bg-slate-900', 'text-white');
            monthlyBtn.classList.remove('bg-slate-900', 'text-white');
            monthlyBtn.classList.add('bg-white', 'text-slate-600', 'border', 'border-slate-200', 'hover:bg-slate-50', 'hover:text-slate-900');
        } else {
            weeklyTab.classList.add('hidden');
            monthlyTab.classList.remove('hidden');
            monthlyBtn.classList.remove('bg-white', 'text-slate-600', 'border', 'border-slate-200', 'hover:bg-slate-50', 'hover:text-slate-900');
            monthlyBtn.classList.add('bg-slate-900', 'text-white');
            weeklyBtn.classList.remove('bg-slate-900', 'text-white');
            weeklyBtn.classList.add('bg-white', 'text-slate-600', 'border', 'border-slate-200', 'hover:bg-slate-50', 'hover:text-slate-900');
        }
    }

    function showLoader(show) {
        const loader = document.getElementById('filterLoader');
        if (loader) {
            loader.classList.toggle('hidden', !show);
        }
    }

    function submitMonthFilter() {
        showLoader(true);
        const month = document.getElementById('monthSelect').value;
        const year = document.getElementById('yearSelect').value;
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'monthly');
        if (month) url.searchParams.set('month', month);
        else url.searchParams.delete('month');
        if (year) url.searchParams.set('year', year);
        else url.searchParams.delete('year');
        window.location.href = url.toString();
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) { lucide.createIcons(); }

        const monthlyData = @json($monthlyStats ?? []);
        const weeklyData = @json($weeklyStats ?? []);

        // Weekly Chart (same design as President dashboard Decision Trend)
        const weeklyCtx = document.getElementById('weeklyChart');
        if (weeklyCtx && weeklyData.length > 0) {
            const weeklyLabels = weeklyData.map(r => r.label);
            const weeklyApproved = weeklyData.map(r => r.approved);
            const weeklyRejected = weeklyData.map(r => r.rejected);

            const weeklyBlueShadowPlugin = {
                id: 'weeklyBlueShadowPlugin',
                beforeDatasetsDraw(chart) {
                    const meta = chart.getDatasetMeta(0);
                    if (!meta || meta.hidden || !meta.data.length || !meta.dataset) {
                        return;
                    }

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

            const weeklyHoverLinePlugin = {
                id: 'weeklyDecisionHoverLine',
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
                    const activeLabel = String(weeklyLabels[activeIndex] || '');

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

            new Chart(weeklyCtx, {
                type: 'line',
                data: {
                    labels: weeklyLabels,
                    datasets: [
                        {
                            label: 'Approved',
                            data: weeklyApproved,
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
                            label: 'Rejected',
                            data: weeklyRejected,
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
                        }
                    ]
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
                plugins: [weeklyBlueShadowPlugin, weeklyHoverLinePlugin],
            });
        }

        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart');
        if (monthlyCtx && monthlyData.length > 0) {
            const isFiltered = @json($filterMonth && $filterYear);
            new Chart(monthlyCtx, {
                type: isFiltered ? 'bar' : 'line',
                data: {
                    labels: monthlyData.map(r => r.month_label),
                    datasets: [
                        {
                            label: 'Approved',
                            data: monthlyData.map(r => r.approved),
                            backgroundColor: isFiltered ? 'rgba(37, 99, 235, 1)' : (context) => {
                                const ctx = context.chart.ctx;
                                const gradient = ctx.createLinearGradient(0, 0, 0, 340);
                                gradient.addColorStop(0, 'rgba(37, 99, 235, 0.28)');
                                gradient.addColorStop(1, 'rgba(37, 99, 235, 0.04)');
                                return gradient;
                            },
                            borderColor: '#2563EB',
                            fill: isFiltered ? false : true,
                            tension: isFiltered ? 0 : 0.4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#2563EB',
                            pointBorderWidth: 3,
                            pointRadius: isFiltered ? 0 : 6,
                            pointHoverRadius: isFiltered ? 0 : 8,
                            pointHoverBackgroundColor: '#2563EB',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3,
                            borderWidth: 3,
                            borderRadius: isFiltered ? 10 : 0,
                        },
                        {
                            label: 'Rejected',
                            data: monthlyData.map(r => r.rejected),
                            backgroundColor: isFiltered ? 'rgba(71, 85, 105, 1)' : (context) => {
                                const ctx = context.chart.ctx;
                                const gradient = ctx.createLinearGradient(0, 0, 0, 340);
                                gradient.addColorStop(0, 'rgba(100, 116, 139, 0.28)');
                                gradient.addColorStop(1, 'rgba(100, 116, 139, 0.04)');
                                return gradient;
                            },
                            borderColor: '#64748B',
                            fill: isFiltered ? false : true,
                            tension: isFiltered ? 0 : 0.4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#64748B',
                            pointBorderWidth: 3,
                            pointRadius: isFiltered ? 0 : 6,
                            pointHoverRadius: isFiltered ? 0 : 8,
                            pointHoverBackgroundColor: '#64748B',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3,
                            borderWidth: 3,
                            borderRadius: isFiltered ? 10 : 0,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1600,
                        easing: 'easeInOutQuart',
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                boxHeight: 8,
                                padding: 24,
                                font: {
                                    size: 12,
                                    weight: '600'
                                },
                                color: '#475569'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            titleColor: '#f8fafc',
                            bodyColor: '#e2e8f0',
                            titleFont: {
                                size: 13,
                                weight: '600'
                            },
                            bodyFont: {
                                size: 12
                            },
                            padding: 14,
                            cornerRadius: 10,
                            displayColors: true,
                            boxWidth: 10,
                            boxHeight: 10,
                            boxPadding: 4,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + ' RIS';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: {
                                    size: 11,
                                    weight: '500'
                                },
                                color: '#64748b',
                                padding: 12
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.04)',
                                drawBorder: false
                            },
                            border: {
                                display: false
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                    weight: '500'
                                },
                                color: '#64748b',
                                padding: 12
                            },
                            border: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Count-up animation
        document.querySelectorAll('.count-up').forEach(el => {
            const target = parseInt(el.dataset.target || el.textContent || '0');
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

        // Month & Year picker auto-submit
        const monthSelect = document.getElementById('monthSelect');
        const yearSelect = document.getElementById('yearSelect');
        const resetBtn = document.getElementById('resetFilterBtn');

        function updateResetState() {
            if (resetBtn) {
                const hasFilter = monthSelect && (monthSelect.value || yearSelect.value);
                resetBtn.classList.toggle('opacity-50', !hasFilter);
                resetBtn.classList.toggle('cursor-not-allowed', !hasFilter);
                resetBtn.disabled = !hasFilter;
            }
        }

        if (monthSelect && yearSelect) {
            updateResetState();
            monthSelect.addEventListener('change', () => {
                updateResetState();
                submitMonthFilter();
            });
            yearSelect.addEventListener('change', () => {
                updateResetState();
                submitMonthFilter();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                if (monthSelect) monthSelect.value = '';
                if (yearSelect) yearSelect.value = '';
                updateResetState();
                const url = new URL(window.location.href);
                url.searchParams.delete('month');
                url.searchParams.delete('year');
                url.searchParams.set('tab', 'monthly');
                window.location.href = url.toString();
            });
        }
    });
</script>

<style>
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(16px); }
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
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .card-hover:hover {
        transform: translateY(-1px);
        border-color: #d1d5db;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
    }

    .action-btn {
        transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease;
    }

    .action-btn:active {
        transform: scale(0.95);
    }

    .filter-select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 1rem;
        padding-right: 2.5rem;
        min-width: 140px;
    }

    .filter-select:focus {
        border-color: #171717;
        box-shadow: 0 0 0 4px rgba(23, 23, 23, 0.06);
    }

    .filter-select:hover {
        border-color: #d1d5db;
    }

    /* Same analytics chart chrome as President dashboard */
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
    .pm-decision-chart-swatch.is-approved { background: #72b4dc; }
    .pm-decision-chart-swatch.is-rejected { background: #94a3b8; }
    .pm-decision-chart {
        position: relative;
        width: 100%;
        height: 320px;
    }
</style>

@endsection
