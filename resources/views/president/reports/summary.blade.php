@extends('layouts.president-layout')

@section('title', 'Reports & Summary')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Reports & Summary</h1>
        <p class="mt-1 text-sm leading-6 text-gray-500">Weekly and monthly procurement statistics.</p>
    </div>
</div>

{{-- Tabs --}}
<div class="mt-6 flex items-center gap-2">
    <button type="button" id="tabWeekly" onclick="switchTab('weekly')" class="action-btn inline-flex h-10 items-center justify-center rounded-xl px-4 text-sm font-medium transition {{ $tab === 'weekly' ? 'bg-slate-900 text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
        <i data-lucide="calendar-week" class="h-4 w-4"></i>
        <span class="ml-1.5">Weekly Summary</span>
    </button>
    <button type="button" id="tabMonthly" onclick="switchTab('monthly')" class="action-btn inline-flex h-10 items-center justify-center rounded-xl px-4 text-sm font-medium transition {{ $tab === 'monthly' ? 'bg-slate-900 text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
        <i data-lucide="calendar-days" class="h-4 w-4"></i>
        <span class="ml-1.5">Monthly Summary</span>
    </button>
</div>

{{-- Weekly Tab --}}
<div id="weeklyTab" class="mt-6 {{ $tab === 'weekly' ? '' : 'hidden' }}">
    {{-- Date Range --}}
    <div class="pm-card p-5 slide-up" style="animation-delay: 0.05s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">This Week</h2>
                <p class="mt-1 text-xs text-gray-500">{{ date('F d, Y', strtotime($startOfWeek ?? now()->startOfWeek())) }} — {{ date('F d, Y', strtotime($endOfWeek ?? now()->endOfWeek())) }}</p>
            </div>
        </div>
    </div>

    {{-- Weekly Stats Cards --}}
    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.1s">
            <p class="text-sm font-medium text-gray-500">Total RIS</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $totalRis ?? 0 }}">{{ $totalRis ?? 0 }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.15s">
            <p class="text-sm font-medium text-gray-500">Approved</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 count-up" data-target="{{ $approved ?? 0 }}">{{ $approved ?? 0 }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.2s">
            <p class="text-sm font-medium text-gray-500">Rejected</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-700 count-up" data-target="{{ $rejected ?? 0 }}">{{ $rejected ?? 0 }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.25s">
            <p class="text-sm font-medium text-gray-500">Pending</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-700 count-up" data-target="{{ $pending ?? 0 }}">{{ $pending ?? 0 }}</p>
        </div>
    </div>

    {{-- Weekly Chart --}}
    <div class="mt-6 pm-card p-5 slide-up" style="animation-delay: 0.35s">
        <h2 class="text-sm font-semibold text-gray-900">Weekly Trend</h2>
        <div class="mt-4" style="height: 300px;">
            <canvas id="weeklyChart"></canvas>
        </div>
    </div>

    {{-- Weekly Table --}}
    <div class="mt-6 pm-card p-5 slide-up" style="animation-delay: 0.4s">
        <h2 class="text-sm font-semibold text-gray-900">Daily Breakdown</h2>
        <div class="mt-4 overflow-x-auto">
            <table id="summaryWeeklyTable" class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Day</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Pending</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tableData ?? [] as $row)
                        <tr class="border-b border-gray-100 transition-all duration-200 hover:bg-slate-50">
                            <td class="px-3 py-4 text-sm font-semibold text-gray-700">{{ $row['day'] }}</td>
                            <td class="px-3 py-4 text-sm text-blue-700 font-semibold">{{ $row['approved'] }}</td>
                            <td class="px-3 py-4 text-sm text-slate-700 font-semibold">{{ $row['rejected'] }}</td>
                            <td class="px-3 py-4 text-sm text-slate-700 font-semibold">{{ $row['pending'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('president.partials.table-word-export', [
            'target' => '#summaryWeeklyTable',
            'filename' => 'president-weekly-daily-breakdown',
        ])
    </div>
</div>

{{-- Monthly Tab --}}
<div id="monthlyTab" class="mt-6 {{ $tab === 'monthly' ? '' : 'hidden' }}">
    {{-- Month Selector --}}
    <div class="pm-card p-5 slide-up" style="animation-delay: 0.05s">
        <form method="GET" action="/president/reports/summary" class="flex items-center gap-2">
            <input type="hidden" name="tab" value="monthly" />
            <select name="month" class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-slate-200 transition-all duration-200">
                @foreach(($monthNames ?? []) as $num => $name)
                    <option value="{{ $num }}" {{ ($month ?? date('m')) == $num ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            <select name="year" class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-slate-200 transition-all duration-200">
                @for ($y = date('Y'); $y >= date('Y') - 5; $y--)
                    <option value="{{ $y }}" {{ ($year ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="action-btn inline-flex h-10 items-center justify-center rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 active:scale-95">
                <i data-lucide="search" class="h-4 w-4"></i>
                <span class="ml-1.5">Apply</span>
            </button>
        </form>
    </div>

    {{-- Monthly Stats Cards --}}
    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.1s">
            <p class="text-sm font-medium text-gray-500">Total RIS</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $totalRis ?? 0 }}">{{ $totalRis ?? 0 }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.15s">
            <p class="text-sm font-medium text-gray-500">Approved</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 count-up" data-target="{{ $approved ?? 0 }}">{{ $approved ?? 0 }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.2s">
            <p class="text-sm font-medium text-gray-500">Rejected</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-700 count-up" data-target="{{ $rejected ?? 0 }}">{{ $rejected ?? 0 }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay: 0.25s">
            <p class="text-sm font-medium text-gray-500">Pending</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-700 count-up" data-target="{{ $pending ?? 0 }}">{{ $pending ?? 0 }}</p>
        </div>
    </div>

    {{-- Monthly Chart --}}
    <div class="mt-6 pm-card p-5 slide-up" style="animation-delay: 0.35s">
        <h2 class="text-sm font-semibold text-gray-900">Monthly Trend</h2>
        <div class="mt-4" style="height: 300px;">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    {{-- Monthly Table --}}
    <div class="mt-6 pm-card p-5 slide-up" style="animation-delay: 0.4s">
        <h2 class="text-sm font-semibold text-gray-900">Daily Breakdown</h2>
        <div class="mt-4 overflow-x-auto">
            <table id="summaryMonthlyTable" class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Day</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Pending</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tableData ?? [] as $row)
                        <tr class="border-b border-gray-100 transition-all duration-200 hover:bg-slate-50">
                            <td class="px-3 py-4 text-sm font-semibold text-gray-700">{{ $row['day'] }}</td>
                            <td class="px-3 py-4 text-sm text-blue-700 font-semibold">{{ $row['approved'] }}</td>
                            <td class="px-3 py-4 text-sm text-slate-700 font-semibold">{{ $row['rejected'] }}</td>
                            <td class="px-3 py-4 text-sm text-slate-700 font-semibold">{{ $row['pending'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('president.partials.table-word-export', [
            'target' => '#summaryMonthlyTable',
            'filename' => 'president-monthly-daily-breakdown',
        ])
    </div>
</div>

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
            weeklyBtn.classList.add('bg-slate-900', 'text-white');
            monthlyBtn.classList.remove('bg-slate-900', 'text-white');
            monthlyBtn.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-200', 'hover:bg-gray-50');
        } else {
            weeklyTab.classList.add('hidden');
            monthlyTab.classList.remove('hidden');
            monthlyBtn.classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-200', 'hover:bg-gray-50');
            monthlyBtn.classList.add('bg-slate-900', 'text-white');
            weeklyBtn.classList.remove('bg-slate-900', 'text-white');
            weeklyBtn.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-200', 'hover:bg-gray-50');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) { lucide.createIcons(); }

        const weeklyCtx = document.getElementById('weeklyChart');
        if (weeklyCtx) {
            new Chart(weeklyCtx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels ?? []),
                    datasets: [
                        {
                            label: 'Approved',
                            data: @json($chartApproved ?? []),
                            borderColor: '#2563EB',
                            backgroundColor: 'rgba(5, 150, 105, 0.08)',
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#2563EB',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            borderWidth: 2.5,
                        },
                        {
                            label: 'Rejected',
                            data: @json($chartRejected ?? []),
                            borderColor: '#64748B',
                            backgroundColor: 'rgba(225, 29, 72, 0.08)',
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#64748B',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            borderWidth: 2.5,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 1000, easing: 'easeInOutQuart' },
                    plugins: {
                        legend: { display: true, position: 'top', labels: { usePointStyle: true, boxWidth: 8, font: { size: 12 } } },
                        tooltip: { backgroundColor: '#1f2937', titleFont: { size: 13 }, bodyFont: { size: 12 }, padding: 10, cornerRadius: 8 }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: 'rgba(0,0,0,0.05)' } },
                        x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                    }
                }
            });
        }

        const monthlyCtx = document.getElementById('monthlyChart');
        if (monthlyCtx) {
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels ?? []),
                    datasets: [
                        {
                            label: 'Approved',
                            data: @json($chartApproved ?? []),
                            borderColor: '#2563EB',
                            backgroundColor: 'rgba(5, 150, 105, 0.08)',
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#2563EB',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            borderWidth: 2.5,
                        },
                        {
                            label: 'Rejected',
                            data: @json($chartRejected ?? []),
                            borderColor: '#64748B',
                            backgroundColor: 'rgba(225, 29, 72, 0.08)',
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#64748B',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            borderWidth: 2.5,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 1000, easing: 'easeInOutQuart' },
                    plugins: {
                        legend: { display: true, position: 'top', labels: { usePointStyle: true, boxWidth: 8, font: { size: 12 } } },
                        tooltip: { backgroundColor: '#1f2937', titleFont: { size: 13 }, bodyFont: { size: 12 }, padding: 10, cornerRadius: 8 }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: 'rgba(0,0,0,0.05)' } },
                        x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                    }
                }
            });
        }

        const counters = document.querySelectorAll('.count-up');
        counters.forEach(el => {
            const target = parseInt(el.dataset.target || el.textContent || '0', 10);
            if (target === 0) return;
            let current = 0;
            const step = Math.max(1, Math.floor(target / 30));
            const interval = setInterval(() => {
                current += step;
                if (current >= target) { current = target; clearInterval(interval); }
                el.textContent = current;
            }, 30);
        });
    });
</script>

<style>
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
    .fade-in { animation: fadeIn 0.4s ease-out forwards; }
    .slide-up { opacity: 0; animation: slideUp 0.5s ease-out forwards; }
    .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .card-hover:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06); }
    .count-up { display: inline-block; }
</style>

@endsection
