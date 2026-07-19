@extends('layouts.president-layout')

@section('title', 'Reports & Summary')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Reports & Summary</h1>
        <p class="mt-1 text-sm text-gray-500">Weekly and monthly RIS statistics and trends.</p>
    </div>

    <div class="flex items-center gap-2">
    </div>
</div>

{{-- Stats Cards --}}
<div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <div class="rounded-xl border border-gray-200 bg-white p-5 card-hover slide-up" style="animation-delay: 0.05s">
        <p class="text-sm font-medium text-gray-500">Total RIS</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $totalRis ?? 0 }}">{{ $totalRis ?? 0 }}</p>
        <p class="mt-2 text-xs text-gray-500">All records</p>
    </div>

    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 card-hover slide-up" style="animation-delay: 0.1s">
        <p class="text-sm font-medium text-emerald-700">Approved</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-emerald-900 count-up" data-target="{{ $approvedDecisionsCount ?? 0 }}">{{ $approvedDecisionsCount ?? 0 }}</p>
        <p class="mt-2 text-xs text-emerald-600">Approved RIS</p>
    </div>

    <div class="rounded-xl border border-rose-200 bg-rose-50 p-5 card-hover slide-up" style="animation-delay: 0.15s">
        <p class="text-sm font-medium text-rose-700">Rejected</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-rose-900 count-up" data-target="{{ $rejectedDecisionsCount ?? 0 }}">{{ $rejectedDecisionsCount ?? 0 }}</p>
        <p class="mt-2 text-xs text-rose-600">Rejected RIS</p>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 card-hover slide-up" style="animation-delay: 0.2s">
        <p class="text-sm font-medium text-amber-700">Pending</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-amber-900 count-up" data-target="{{ $pendingApprovalsCount ?? 0 }}">{{ $pendingApprovalsCount ?? 0 }}</p>
        <p class="mt-2 text-xs text-amber-600">Pending RIS</p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5 card-hover slide-up" style="animation-delay: 0.25s">
        <p class="text-sm font-medium text-gray-500">Total Amount</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">₱{{ number_format((float)($totalAmount ?? 0), 2) }}</p>
        <p class="mt-2 text-xs text-gray-500">All RIS value</p>
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
    {{-- Weekly Chart --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.1s">
        <h2 class="text-sm font-semibold text-gray-900">Weekly Trend</h2>
        <p class="mt-1 text-xs text-gray-500">Last 4 weeks RIS decisions.</p>
        <div class="mt-4" style="height: 300px;">
            <canvas id="weeklyChart"></canvas>
        </div>
    </div>

    {{-- Weekly Table --}}
    <div class="mt-6 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.15s">
        <h2 class="text-sm font-semibold text-gray-900">Weekly Breakdown</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Week</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Pending</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($weeklyStats ?? [] as $row)
                        <tr class="border-b border-gray-100 transition-all duration-200 hover:bg-yellow-50/40">
                            <td class="px-3 py-4 text-sm font-semibold text-gray-700">{{ $row['label'] }}</td>
                            <td class="px-3 py-4 text-sm text-emerald-700 font-semibold">{{ $row['approved'] }}</td>
                            <td class="px-3 py-4 text-sm text-rose-700 font-semibold">{{ $row['rejected'] }}</td>
                            <td class="px-3 py-4 text-sm text-amber-700 font-semibold">{{ $row['pending'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-2 py-12 text-center">
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
    {{-- Month Selector --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.05s">
        <form id="monthFilterForm" method="GET" action="/president/reports/monthly-summary" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="tab" value="monthly" />
            <select name="month" id="monthSelect" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-amber-100 transition-all duration-200">
                @foreach([1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'] as $num => $name)
                    <option value="{{ $num }}" {{ (request('month') ?? date('m')) == $num ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            <select name="year" id="yearSelect" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-amber-100 transition-all duration-200">
                @for ($y = date('Y'); $y >= date('Y') - 5; $y--)
                    <option value="{{ $y }}" {{ (request('year') ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="action-btn inline-flex h-10 items-center justify-center rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white transition hover:bg-gray-800 active:scale-95">
                <i data-lucide="search" class="h-4 w-4"></i>
                <span class="ml-1.5">Apply</span>
            </button>
            <button type="button" id="resetFilterBtn" class="action-btn inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 active:scale-95 {{ !request('month') && !request('year') ? 'opacity-50 cursor-not-allowed' : '' }}">
                <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                <span class="ml-1.5">Reset</span>
            </button>
        </form>
    </div>

    {{-- Monthly Chart --}}
    <div class="mt-6 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.1s">
        <h2 class="text-sm font-semibold text-gray-900">{{ $filterMonth && $filterYear ? 'Monthly Trend' : 'Monthly Trend' }}</h2>
        <p class="mt-1 text-xs text-gray-500">{{ $filterMonth && $filterYear ? 'Selected month RIS decisions.' : 'Last 6 months RIS decisions.' }}</p>
        <div class="mt-4" style="height: 300px;">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    {{-- Monthly Table --}}
    <div class="mt-6 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.15s">
        <h2 class="text-sm font-semibold text-gray-900">Monthly Breakdown</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Month</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Pending</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlyStats ?? [] as $row)
                        <tr class="border-b border-gray-100 transition-all duration-200 hover:bg-yellow-50/40">
                            <td class="px-3 py-4 text-sm font-semibold text-gray-700">{{ $row['month_label'] }}</td>
                            <td class="px-3 py-4 text-sm text-emerald-700 font-semibold">{{ $row['approved'] }}</td>
                            <td class="px-3 py-4 text-sm text-rose-700 font-semibold">{{ $row['rejected'] }}</td>
                            <td class="px-3 py-4 text-sm text-amber-700 font-semibold">{{ $row['pending'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-2 py-12 text-center">
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
            weeklyBtn.classList.add('bg-gray-900', 'text-white');
            monthlyBtn.classList.remove('bg-gray-900', 'text-white');
            monthlyBtn.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-200', 'hover:bg-gray-50');
        } else {
            weeklyTab.classList.add('hidden');
            monthlyTab.classList.remove('hidden');
            monthlyBtn.classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-200', 'hover:bg-gray-50');
            monthlyBtn.classList.add('bg-gray-900', 'text-white');
            weeklyBtn.classList.remove('bg-gray-900', 'text-white');
            weeklyBtn.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-200', 'hover:bg-gray-50');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) { lucide.createIcons(); }

        const weeklyData = @json($weeklyStats ?? []);
        const monthlyData = @json($monthlyStats ?? []);
        const resetBtn = document.getElementById('resetFilterBtn');
        const monthSelect = document.getElementById('monthSelect');
        const yearSelect = document.getElementById('yearSelect');

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
                                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                                gradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
                                gradient.addColorStop(1, 'rgba(16, 185, 129, 0.02)');
                                return gradient;
                            },
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 3,
                            pointRadius: 5,
                            pointHoverRadius: 7,
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
                                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                                gradient.addColorStop(0, 'rgba(244, 63, 94, 0.2)');
                                gradient.addColorStop(1, 'rgba(244, 63, 94, 0.02)');
                                return gradient;
                            },
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#f43f5e',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 3,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#f43f5e',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3,
                            borderWidth: 3,
                        },
                        {
                            label: 'Pending',
                            data: weeklyData.map(r => r.pending),
                            borderColor: '#f59e0b',
                            backgroundColor: (context) => {
                                const ctx = context.chart.ctx;
                                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                                gradient.addColorStop(0, 'rgba(245, 158, 11, 0.2)');
                                gradient.addColorStop(1, 'rgba(245, 158, 11, 0.02)');
                                return gradient;
                            },
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#f59e0b',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 3,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#f59e0b',
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
                        duration: 1500,
                        easing: 'easeInOutQuart',
                        onComplete: () => {
                            // Smooth fade-in after animation
                            weeklyCtx.style.opacity = '1';
                        }
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
                                padding: 20,
                                font: {
                                    size: 12,
                                    weight: '500'
                                },
                                color: '#64748b'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
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
                                padding: 10
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
                                padding: 10
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
                            backgroundColor: isFiltered ? '#10b981' : (context) => {
                                const ctx = context.chart.ctx;
                                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                                gradient.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
                                gradient.addColorStop(1, 'rgba(16, 185, 129, 0.05)');
                                return gradient;
                            },
                            borderColor: '#10b981',
                            fill: isFiltered ? false : true,
                            tension: isFiltered ? 0 : 0.4,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 3,
                            pointRadius: isFiltered ? 0 : 5,
                            pointHoverRadius: isFiltered ? 0 : 7,
                            pointHoverBackgroundColor: '#10b981',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3,
                            borderWidth: 3,
                        },
                        {
                            label: 'Rejected',
                            data: monthlyData.map(r => r.rejected),
                            backgroundColor: isFiltered ? '#f43f5e' : (context) => {
                                const ctx = context.chart.ctx;
                                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                                gradient.addColorStop(0, 'rgba(244, 63, 94, 0.25)');
                                gradient.addColorStop(1, 'rgba(244, 63, 94, 0.05)');
                                return gradient;
                            },
                            borderColor: '#f43f5e',
                            fill: isFiltered ? false : true,
                            tension: isFiltered ? 0 : 0.4,
                            pointBackgroundColor: '#f43f5e',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 3,
                            pointRadius: isFiltered ? 0 : 5,
                            pointHoverRadius: isFiltered ? 0 : 7,
                            pointHoverBackgroundColor: '#f43f5e',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3,
                            borderWidth: 3,
                        },
                        {
                            label: 'Pending',
                            data: monthlyData.map(r => r.pending),
                            backgroundColor: isFiltered ? '#f59e0b' : (context) => {
                                const ctx = context.chart.ctx;
                                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                                gradient.addColorStop(0, 'rgba(245, 158, 11, 0.25)');
                                gradient.addColorStop(1, 'rgba(245, 158, 11, 0.05)');
                                return gradient;
                            },
                            borderColor: '#f59e0b',
                            fill: isFiltered ? false : true,
                            tension: isFiltered ? 0 : 0.4,
                            pointBackgroundColor: '#f59e0b',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 3,
                            pointRadius: isFiltered ? 0 : 5,
                            pointHoverRadius: isFiltered ? 0 : 7,
                            pointHoverBackgroundColor: '#f59e0b',
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
                        duration: 1500,
                        easing: 'easeInOutQuart',
                        onComplete: () => {
                            // Smooth fade-in after animation
                            monthlyCtx.style.opacity = '1';
                        }
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
                                padding: 20,
                                font: {
                                    size: 12,
                                    weight: '500'
                                },
                                color: '#64748b'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
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
                                padding: 10
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
                                padding: 10
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

        // Reset filter button functionality
        if (resetBtn && monthSelect && yearSelect) {
            // Update button state based on filter
            function updateResetButtonState() {
                const hasFilter = monthSelect.value || yearSelect.value;
                if (hasFilter) {
                    resetBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    resetBtn.disabled = false;
                } else {
                    resetBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    resetBtn.disabled = true;
                }
            }

            // Initialize button state
            updateResetButtonState();

            // Update state when selections change
            monthSelect.addEventListener('change', updateResetButtonState);
            yearSelect.addEventListener('change', updateResetButtonState);

            // Reset button click handler
            resetBtn.addEventListener('click', function() {
                // Clear selections
                monthSelect.value = '';
                yearSelect.value = '';

                // Update button state
                updateResetButtonState();

                // Submit form to reload page with all data
                const form = document.getElementById('monthFilterForm');
                if (form) {
                    // Remove any existing month/year parameters from URL
                    const url = new URL(window.location.href);
                    url.searchParams.delete('month');
                    url.searchParams.delete('year');
                    
                    // Redirect to clean URL
                    window.location.href = url.toString();
                }
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
</style>

@endsection