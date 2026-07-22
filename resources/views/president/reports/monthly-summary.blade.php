@extends('layouts.president-layout')

@section('title', 'Reports & Summary')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Reports & Summary</h1>
        <p class="mt-1 text-sm text-gray-500">Weekly and monthly RIS statistics and trends.</p>
    </div>
</div>

{{-- ======================================== --}}
{{-- EXECUTIVE INSIGHTS --}}
{{-- ======================================== --}}
<div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
    <div class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 slide-up" style="animation-delay: 0.05s">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
                <i data-lucide="check-circle" class="h-4 w-4 text-emerald-700"></i>
            </div>
            <p class="text-xs font-medium text-emerald-700">Approval Rate</p>
        </div>
        <p class="mt-3 text-2xl font-semibold tracking-tight text-emerald-900">{{ $insights['approval_rate'] ?? 0 }}%</p>
    </div>

    <div class="rounded-xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-5 slide-up" style="animation-delay: 0.1s">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100">
                <i data-lucide="x-circle" class="h-4 w-4 text-rose-700"></i>
            </div>
            <p class="text-xs font-medium text-rose-700">Rejection Rate</p>
        </div>
        <p class="mt-3 text-2xl font-semibold tracking-tight text-rose-900">{{ $insights['rejection_rate'] ?? 0 }}%</p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5 slide-up" style="animation-delay: 0.15s">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100">
                <i data-lucide="wallet" class="h-4 w-4 text-gray-700"></i>
            </div>
            <p class="text-xs font-medium text-gray-600">Approved Value</p>
        </div>
        <p class="mt-3 text-2xl font-semibold tracking-tight text-gray-900">₱{{ number_format((float)($insights['approved_amount'] ?? 0), 2) }}</p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5 slide-up" style="animation-delay: 0.2s">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100">
                <i data-lucide="wallet" class="h-4 w-4 text-gray-700"></i>
            </div>
            <p class="text-xs font-medium text-gray-600">Rejected Value</p>
        </div>
        <p class="mt-3 text-2xl font-semibold tracking-tight text-gray-900">₱{{ number_format((float)($insights['rejected_amount'] ?? 0), 2) }}</p>
    </div>

    <div class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 slide-up" style="animation-delay: 0.25s">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
                <i data-lucide="trending-up" class="h-4 w-4 text-emerald-700"></i>
            </div>
            <p class="text-xs font-medium text-emerald-700">Top Approval Month</p>
        </div>
        <p class="mt-3 text-lg font-semibold tracking-tight text-emerald-900">{{ $insights['highest_approval_month'] ?? 'N/A' }}</p>
    </div>

    <div class="rounded-xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-5 slide-up" style="animation-delay: 0.3s">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100">
                <i data-lucide="trending-down" class="h-4 w-4 text-rose-700"></i>
            </div>
            <p class="text-xs font-medium text-rose-700">Top Rejection Month</p>
        </div>
        <p class="mt-3 text-lg font-semibold tracking-tight text-rose-900">{{ $insights['highest_rejection_month'] ?? 'N/A' }}</p>
    </div>
</div>

{{-- Tabs --}}
<div class="mt-6 flex flex-wrap items-center gap-3">
    <button type="button" id="tabWeekly" onclick="switchTab('weekly')" class="action-btn inline-flex h-11 items-center justify-center gap-2.5 rounded-lg px-5 text-sm font-semibold transition-all duration-200 {{ request('tab') !== 'monthly' ? 'bg-gray-900 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
        <i data-lucide="trending-up" class="h-4 w-4"></i>
        <span>Weekly Summary</span>
    </button>
    <button type="button" id="tabMonthly" onclick="switchTab('monthly')" class="action-btn inline-flex h-11 items-center justify-center gap-2.5 rounded-lg px-5 text-sm font-semibold transition-all duration-200 {{ request('tab') === 'monthly' ? 'bg-gray-900 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
        <i data-lucide="calendar-days" class="h-4 w-4"></i>
        <span>Monthly Summary</span>
    </button>
</div>

{{-- Weekly Tab --}}
<div id="weeklyTab" class="mt-6 {{ request('tab') === 'monthly' ? 'hidden' : '' }}">
    {{-- Weekly Stats Cards --}}
    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 card-hover slide-up" style="animation-delay: 0.05s">
            <p class="text-xs font-medium text-gray-500">Total RIS</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $totalRis ?? 0 }}">{{ $totalRis ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-5 card-hover slide-up" style="animation-delay: 0.1s">
            <p class="text-xs font-medium text-emerald-700">Approved</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-emerald-900 count-up" data-target="{{ $approvedDecisionsCount ?? 0 }}">{{ $approvedDecisionsCount ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50/60 p-5 card-hover slide-up" style="animation-delay: 0.15s">
            <p class="text-xs font-medium text-rose-700">Rejected</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-rose-900 count-up" data-target="{{ $rejectedDecisionsCount ?? 0 }}">{{ $rejectedDecisionsCount ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-5 card-hover slide-up" style="animation-delay: 0.2s">
            <p class="text-xs font-medium text-amber-700">Pending</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-amber-900 count-up" data-target="{{ $pendingApprovalsCount ?? 0 }}">{{ $pendingApprovalsCount ?? 0 }}</p>
        </div>
    </div>

    {{-- Weekly Chart --}}
    <div class="mt-6 rounded-xl border border-gray-200 bg-white slide-up" style="animation-delay: 0.25s">
        <div class="p-5 pb-3">
            <h2 class="text-sm font-semibold text-gray-900">Weekly Trend</h2>
            <p class="mt-1 text-xs text-gray-500">Last 4 weeks RIS decisions.</p>
        </div>
        <div class="px-5 pb-5">
            <div style="height: 340px;">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Weekly Table --}}
    <div class="mt-6 rounded-xl border border-gray-200 bg-white slide-up" style="animation-delay: 0.3s">
        <div class="p-5 pb-3">
            <h2 class="text-sm font-semibold text-gray-900">Weekly Breakdown</h2>
            <p class="mt-1 text-xs text-gray-500">Detailed performance metrics per week.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Period</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Total Received</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Approval Rate</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejection Rate</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved Value</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected Value</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Avg Processing</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Trend</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($weeklyStats ?? [] as $row)
                        <tr class="border-b border-gray-100 transition-all duration-200 hover:bg-gray-50/60">
                            <td class="px-4 py-4 text-sm font-semibold text-gray-700">{{ $row['label'] }}</td>
                            <td class="px-4 py-4 text-center text-sm font-semibold text-gray-900">{{ $row['total'] ?? ($row['approved'] + $row['rejected'] + $row['pending']) }}</td>
                            <td class="px-4 py-4 text-center text-sm text-emerald-700 font-semibold">{{ $row['approved'] }}</td>
                            <td class="px-4 py-4 text-center text-sm text-rose-700 font-semibold">{{ $row['rejected'] }}</td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">{{ $row['approval_rate'] }}%</span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800">{{ $row['rejection_rate'] }}%</span>
                            </td>
                            <td class="px-4 py-4 text-right text-sm text-gray-700">₱{{ number_format((float)($row['approved_amount'] ?? 0), 2) }}</td>
                            <td class="px-4 py-4 text-right text-sm text-gray-700">₱{{ number_format((float)($row['rejected_amount'] ?? 0), 2) }}</td>
                            <td class="px-4 py-4 text-center text-sm text-gray-600">{{ $row['avg_processing_time'] ?? 'N/A' }} {{ isset($row['avg_processing_time']) ? 'days' : '' }}</td>
                            <td class="px-4 py-4 text-center">
                                @php
                                    $trend = $row['trend'] ?? '➜ No Change';
                                    $trendColor = str_contains($trend, 'Improved') ? 'text-emerald-700 bg-emerald-50' : (str_contains($trend, 'Declined') ? 'text-rose-700 bg-rose-50' : 'text-gray-600 bg-gray-100');
                                @endphp
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $trendColor }}">
                                    {{ $trend }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-2 py-12 text-center">
                                <p class="text-sm font-semibold text-gray-800">No weekly data available.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Monthly Tab --}}
<div id="monthlyTab" class="mt-6 {{ request('tab') === 'monthly' ? '' : 'hidden' }}">
    {{-- Month & Year Picker --}}
    <div class="slide-up" style="animation-delay: 0.05s">
        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition-all duration-200 hover:border-gray-300 hover:shadow-md">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-50 text-gray-700 transition-colors duration-200">
                <i data-lucide="calendar-days" class="h-5 w-5"></i>
            </div>

            <div class="h-8 w-px bg-gray-200"></div>

            <div class="flex items-center gap-2">
                <select name="month" id="monthSelect" class="filter-select rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 outline-none transition-all duration-200 focus:border-gray-900 focus:ring-4 focus:ring-gray-100">
                    @foreach([1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'] as $num => $name)
                        <option value="{{ $num }}" {{ (request('month') ?? date('m')) == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <select name="year" id="yearSelect" class="filter-select rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 outline-none transition-all duration-200 focus:border-gray-900 focus:ring-4 focus:ring-gray-100">
                    @for ($y = date('Y'); $y >= date('Y') - 5; $y--)
                        <option value="{{ $y }}" {{ (request('year') ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="h-8 w-px bg-gray-200"></div>

            <button type="button" id="resetFilterBtn" class="action-btn inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-transparent bg-white px-3 text-sm font-medium text-gray-600 transition-all duration-200 hover:border-gray-200 hover:bg-gray-50 hover:text-gray-900 active:scale-95 {{ !request('month') && !request('year') ? 'opacity-40 cursor-not-allowed' : '' }}">
                <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                <span>Reset</span>
            </button>

            <div id="filterLoader" class="hidden ml-1">
                <div class="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900"></div>
            </div>
        </div>
    </div>

    {{-- Monthly Stats Cards --}}
    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 card-hover slide-up" style="animation-delay: 0.1s">
            <p class="text-xs font-medium text-gray-500">Total RIS</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $totalRis ?? 0 }}">{{ $totalRis ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-5 card-hover slide-up" style="animation-delay: 0.15s">
            <p class="text-xs font-medium text-emerald-700">Approved</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-emerald-900 count-up" data-target="{{ $approvedDecisionsCount ?? 0 }}">{{ $approvedDecisionsCount ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50/60 p-5 card-hover slide-up" style="animation-delay: 0.2s">
            <p class="text-xs font-medium text-rose-700">Rejected</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-rose-900 count-up" data-target="{{ $rejectedDecisionsCount ?? 0 }}">{{ $rejectedDecisionsCount ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-5 card-hover slide-up" style="animation-delay: 0.25s">
            <p class="text-xs font-medium text-amber-700">Pending</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-amber-900 count-up" data-target="{{ $pendingApprovalsCount ?? 0 }}">{{ $pendingApprovalsCount ?? 0 }}</p>
        </div>
    </div>

    {{-- Monthly Chart --}}
    <div class="mt-6 rounded-xl border border-gray-200 bg-white slide-up" style="animation-delay: 0.3s">
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
    <div class="mt-6 rounded-xl border border-gray-200 bg-white slide-up" style="animation-delay: 0.35s">
        <div class="p-5 pb-3">
            <h2 class="text-sm font-semibold text-gray-900">Monthly Breakdown</h2>
            <p class="mt-1 text-xs text-gray-500">Detailed performance metrics per month.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Period</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Total Received</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Approval Rate</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejection Rate</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved Value</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected Value</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Avg Processing</th>
                        <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Trend</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlyStats ?? [] as $row)
                        <tr class="border-b border-gray-100 transition-all duration-200 hover:bg-gray-50/60">
                            <td class="px-4 py-4 text-sm font-semibold text-gray-700">{{ $row['month_label'] }}</td>
                            <td class="px-4 py-4 text-center text-sm font-semibold text-gray-900">{{ $row['total'] ?? ($row['approved'] + $row['rejected'] + $row['pending']) }}</td>
                            <td class="px-4 py-4 text-center text-sm text-emerald-700 font-semibold">{{ $row['approved'] }}</td>
                            <td class="px-4 py-4 text-center text-sm text-rose-700 font-semibold">{{ $row['rejected'] }}</td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">{{ $row['approval_rate'] }}%</span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800">{{ $row['rejection_rate'] }}%</span>
                            </td>
                            <td class="px-4 py-4 text-right text-sm text-gray-700">₱{{ number_format((float)($row['approved_amount'] ?? 0), 2) }}</td>
                            <td class="px-4 py-4 text-right text-sm text-gray-700">₱{{ number_format((float)($row['rejected_amount'] ?? 0), 2) }}</td>
                            <td class="px-4 py-4 text-center text-sm text-gray-600">{{ $row['avg_processing_time'] ?? 'N/A' }} {{ isset($row['avg_processing_time']) ? 'days' : '' }}</td>
                            <td class="px-4 py-4 text-center">
                                @php
                                    $trend = $row['trend'] ?? '➜ No Change';
                                    $trendColor = str_contains($trend, 'Improved') ? 'text-emerald-700 bg-emerald-50' : (str_contains($trend, 'Declined') ? 'text-rose-700 bg-rose-50' : 'text-gray-600 bg-gray-100');
                                @endphp
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $trendColor }}">
                                    {{ $trend }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-2 py-12 text-center">
                                <p class="text-sm font-semibold text-gray-800">No monthly data available.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
            weeklyBtn.classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-200', 'hover:bg-gray-50');
            weeklyBtn.classList.add('bg-gray-900', 'text-white', 'shadow-sm');
            monthlyBtn.classList.remove('bg-gray-900', 'text-white', 'shadow-sm');
            monthlyBtn.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-200', 'hover:bg-gray-50');
        } else {
            weeklyTab.classList.add('hidden');
            monthlyTab.classList.remove('hidden');
            monthlyBtn.classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-200', 'hover:bg-gray-50');
            monthlyBtn.classList.add('bg-gray-900', 'text-white', 'shadow-sm');
            weeklyBtn.classList.remove('bg-gray-900', 'text-white', 'shadow-sm');
            weeklyBtn.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-200', 'hover:bg-gray-50');
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

        // Weekly Chart
        const weeklyCtx = document.getElementById('weeklyChart');
        if (weeklyCtx && weeklyData.length > 0) {
            new Chart(weeklyCtx, {
                type: 'line',
                data: {
                    labels: weeklyData.map(r => r.label),
                    datasets: [
                        {
                            label: 'Approved',
                            data: weeklyData.map(r => r.approved),
                            borderColor: '#10b981',
                            backgroundColor: (context) => {
                                const ctx = context.chart.ctx;
                                const gradient = ctx.createLinearGradient(0, 0, 0, 340);
                                gradient.addColorStop(0, 'rgba(16, 185, 129, 0.28)');
                                gradient.addColorStop(1, 'rgba(16, 185, 129, 0.04)');
                                return gradient;
                            },
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#10b981',
                            pointBorderWidth: 3,
                            pointRadius: 6,
                            pointHoverRadius: 8,
                            pointHoverBackgroundColor: '#10b981',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3,
                            borderWidth: 3,
                        },
                        {
                            label: 'Rejected',
                            data: weeklyData.map(r => r.rejected),
                            borderColor: '#f43f5e',
                            backgroundColor: (context) => {
                                const ctx = context.chart.ctx;
                                const gradient = ctx.createLinearGradient(0, 0, 0, 340);
                                gradient.addColorStop(0, 'rgba(244, 63, 94, 0.28)');
                                gradient.addColorStop(1, 'rgba(244, 63, 94, 0.04)');
                                return gradient;
                            },
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#f43f5e',
                            pointBorderWidth: 3,
                            pointRadius: 6,
                            pointHoverRadius: 8,
                            pointHoverBackgroundColor: '#f43f5e',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3,
                            borderWidth: 3,
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
                            backgroundColor: isFiltered ? 'rgba(16, 185, 129, 1)' : (context) => {
                                const ctx = context.chart.ctx;
                                const gradient = ctx.createLinearGradient(0, 0, 0, 340);
                                gradient.addColorStop(0, 'rgba(16, 185, 129, 0.28)');
                                gradient.addColorStop(1, 'rgba(16, 185, 129, 0.04)');
                                return gradient;
                            },
                            borderColor: '#10b981',
                            fill: isFiltered ? false : true,
                            tension: isFiltered ? 0 : 0.4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#10b981',
                            pointBorderWidth: 3,
                            pointRadius: isFiltered ? 0 : 6,
                            pointHoverRadius: isFiltered ? 0 : 8,
                            pointHoverBackgroundColor: '#10b981',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3,
                            borderWidth: 3,
                            borderRadius: isFiltered ? 10 : 0,
                        },
                        {
                            label: 'Rejected',
                            data: monthlyData.map(r => r.rejected),
                            backgroundColor: isFiltered ? 'rgba(244, 63, 94, 1)' : (context) => {
                                const ctx = context.chart.ctx;
                                const gradient = ctx.createLinearGradient(0, 0, 0, 340);
                                gradient.addColorStop(0, 'rgba(244, 63, 94, 0.28)');
                                gradient.addColorStop(1, 'rgba(244, 63, 94, 0.04)');
                                return gradient;
                            },
                            borderColor: '#f43f5e',
                            fill: isFiltered ? false : true,
                            tension: isFiltered ? 0 : 0.4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#f43f5e',
                            pointBorderWidth: 3,
                            pointRadius: isFiltered ? 0 : 6,
                            pointHoverRadius: isFiltered ? 0 : 8,
                            pointHoverBackgroundColor: '#f43f5e',
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
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card-hover:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
    }

    .action-btn {
        transition: all 0.2s ease;
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
</style>

@endsection
