@extends('layouts.president-layout')

@section('title', 'Monthly Summary')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Monthly Summary</h1>
        <p class="mt-1 text-sm text-gray-500">Summary of approved and rejected RIS decisions.</p>
    </div>

    <div class="flex items-center gap-2">
    </div>
</div>

{{-- Stats Cards --}}
<div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-5 card-hover slide-up" style="animation-delay: 0.05s">
        <p class="text-sm font-medium text-gray-500">Approved (total)</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 count-up" id="approvedTotal">{{ $approvedDecisionsCount ?? 0 }}</p>
        <p class="mt-2 text-xs text-gray-500">All-time approved RIS records.</p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5 card-hover slide-up" style="animation-delay: 0.1s">
        <p class="text-sm font-medium text-gray-500">Rejected (total)</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 count-up" id="rejectedTotal">{{ $rejectedDecisionsCount ?? 0 }}</p>
        <p class="mt-2 text-xs text-gray-500">All-time rejected RIS records.</p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5 card-hover slide-up" style="animation-delay: 0.15s">
        <p class="text-sm font-medium text-gray-500">Pending (total)</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 count-up" id="pendingTotal">{{ $pendingApprovalsCount ?? 0 }}</p>
        <p class="mt-2 text-xs text-gray-500">Currently pending RIS approvals.</p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5 card-hover slide-up" style="animation-delay: 0.2s">
        <p class="text-sm font-medium text-gray-500">Total Records</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 count-up" id="grandTotal">{{ ($approvedDecisionsCount ?? 0) + ($rejectedDecisionsCount ?? 0) + ($pendingApprovalsCount ?? 0) }}</p>
        <p class="mt-2 text-xs text-gray-500">All RIS records.</p>
    </div>
</div>

{{-- Filter + Graph + Table --}}
<div class="mt-6 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.25s">
    {{-- Header + Filter --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Approved vs Rejected (by Month)</h2>
            <p class="mt-1 text-xs text-gray-500">Monthly breakdown of RIS decisions from July 2026 onward.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs font-medium text-gray-500">Filter:</span>
            <div class="flex rounded-lg border border-gray-200 bg-white overflow-hidden">
                <button type="button" class="summary-filter-btn px-4 py-2 text-xs font-semibold bg-gray-900 text-white transition-all duration-200 hover:bg-gray-800 active:scale-95" data-filter="all">All</button>
                <button type="button" class="summary-filter-btn px-4 py-2 text-xs font-semibold text-gray-700 transition-all duration-200 hover:bg-gray-100 active:scale-95" data-filter="approved">Approved</button>
                <button type="button" class="summary-filter-btn px-4 py-2 text-xs font-semibold text-gray-700 transition-all duration-200 hover:bg-gray-100 active:scale-95" data-filter="rejected">Rejected</button>
            </div>
        </div>
    </div>

    {{-- Line Graph --}}
    <div class="mt-6">
        <canvas id="monthlyChart" height="220" class="w-full"></canvas>
    </div>

    {{-- Table --}}
    <div class="mt-6 overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Month</th>
                    <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved</th>
                    <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected</th>
                </tr>
            </thead>
            <tbody id="summaryTableBody">
                @if(count($monthlyStats) > 0)
                    @foreach($monthlyStats as $row)
                        @php
                            $approved = (int)($row['approved'] ?? 0);
                            $rejected = (int)($row['rejected'] ?? 0);
                            $label = $row['month_label'] ?? ($row['month'] ?? '—');
                        @endphp
                        <tr class="border-b border-gray-100 table-row-hover transition-all duration-200 summary-row"
                            data-month="{{ $row['year_month'] ?? '' }}"
                            data-approved="{{ $approved }}"
                            data-rejected="{{ $rejected }}">
                            <td class="px-2 py-4 text-sm font-semibold text-gray-700">{{ $label }}</td>
                            <td class="px-2 py-4 text-sm text-emerald-700 font-semibold approved-cell">{{ $approved }}</td>
                            <td class="px-2 py-4 text-sm text-rose-700 font-semibold rejected-cell">{{ $rejected }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="3" class="px-2 py-12 text-center">
                            <p class="text-sm font-semibold text-gray-800">No decision records found.</p>
                            <p class="mt-1 text-xs text-gray-500">Monthly data will appear here once decisions are made.</p>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
    // =====================================================
    // DATA
    // =====================================================

    const monthlyData = @json($monthlyStats);

    const labels = monthlyData.map(r => r.month_label);
    const approvedData = monthlyData.map(r => r.approved);
    const rejectedData = monthlyData.map(r => r.rejected);

    // =====================================================
    // CHART
    // =====================================================

    const ctx = document.getElementById('monthlyChart').getContext('2d');

    let monthlyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Approved',
                    data: approvedData,
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.08)',
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#059669',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    borderWidth: 2.5,
                },
                {
                    label: 'Rejected',
                    data: rejectedData,
                    borderColor: '#e11d48',
                    backgroundColor: 'rgba(225, 29, 72, 0.08)',
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#e11d48',
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
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    backgroundColor: '#1f2937',
                    titleFont: { size: 13 },
                    bodyFont: { size: 12 },
                    padding: 10,
                    cornerRadius: 8,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { size: 11 }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: { size: 11 }
                    }
                }
            }
        }
    });

    // =====================================================
    // FILTER LOGIC
    // =====================================================

    function applySummaryFilter(filter) {
        const rows = document.querySelectorAll('.summary-row');

        let approvedSum = 0;
        let rejectedSum = 0;
        let pendingSum = parseInt(document.getElementById('pendingTotal')?.textContent || '0');

        rows.forEach(row => {
            const approved = parseInt(row.dataset.approved || '0');
            const rejected = parseInt(row.dataset.rejected || '0');

            if (filter === 'all') {
                row.style.display = '';
                approvedSum += approved;
                rejectedSum += rejected;
            } else if (filter === 'approved') {
                if (approved > 0) {
                    row.style.display = '';
                    approvedSum += approved;
                } else {
                    row.style.display = 'none';
                }
            } else if (filter === 'rejected') {
                if (rejected > 0) {
                    row.style.display = '';
                    rejectedSum += rejected;
                } else {
                    row.style.display = 'none';
                }
            }
        });

        // Update stat cards
        const approvedEl = document.getElementById('approvedTotal');
        const rejectedEl = document.getElementById('rejectedTotal');
        const grandEl = document.getElementById('grandTotal');

        if (filter === 'all') {
            approvedEl.textContent = approvedSum;
            rejectedEl.textContent = rejectedSum;
            grandEl.textContent = approvedSum + rejectedSum + pendingSum;
        } else if (filter === 'approved') {
            approvedEl.textContent = approvedSum;
            rejectedEl.textContent = 0;
            grandEl.textContent = approvedSum + pendingSum;
        } else if (filter === 'rejected') {
            approvedEl.textContent = 0;
            rejectedEl.textContent = rejectedSum;
            grandEl.textContent = rejectedSum + pendingSum;
        }

        // Update chart
        const filteredApproved = [];
        const filteredRejected = [];
        const filteredLabels = [];

        rows.forEach(row => {
            if (row.style.display !== 'none') {
                filteredLabels.push(row.querySelector('td:first-child')?.textContent?.trim() || '');
                filteredApproved.push(parseInt(row.dataset.approved || '0'));
                filteredRejected.push(parseInt(row.dataset.rejected || '0'));
            }
        });

        monthlyChart.data.labels = filteredLabels;
        monthlyChart.data.datasets[0].data = filteredApproved;
        monthlyChart.data.datasets[1].data = filteredRejected;
        monthlyChart.update();
    }

    // Init filter buttons
    document.querySelectorAll('.summary-filter-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.summary-filter-btn').forEach(b => {
                b.classList.remove('bg-gray-900', 'text-white');
                b.classList.add('text-gray-700');
            });
            this.classList.remove('text-gray-700');
            this.classList.add('bg-gray-900', 'text-white');

            applySummaryFilter(this.dataset.filter);
        });
    });

    // =====================================================
    // UI ANIMATIONS
    // =====================================================

    // Count-up animation
    document.querySelectorAll('.count-up').forEach(el => {
        const target = parseInt(el.textContent || '0');
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

    // Table row hover
    document.querySelectorAll('.table-row-hover').forEach(row => {
        row.addEventListener('mouseenter', function () {
            this.classList.add('bg-yellow-50/40');
        });
        row.addEventListener('mouseleave', function () {
            this.classList.remove('bg-yellow-50/40');
        });
    });
</script>

<style>
    /* ======================================
       ANIMATIONS
    ====================================== */

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

    .table-row-hover {
        transition: background-color 0.2s ease;
    }

    .summary-filter-btn {
        transition: all 0.2s ease;
    }

    .summary-filter-btn:active {
        transform: scale(0.95);
    }
</style>

@endsection