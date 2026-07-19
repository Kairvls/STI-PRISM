@extends('layouts.president-layout')

@section('title', 'President Dashboard')

@section('content')

{{-- Header --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between fade-in">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Monitor RIS approvals and decisions</p>
    </div>
    <a href="/president/approvals" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gray-900 px-5 text-sm font-semibold text-white transition hover:bg-gray-800 active:scale-95 shadow-sm">
        <i data-lucide="clipboard-check" class="h-4 w-4"></i>
        Review Approvals
    </a>
</div>

{{-- Stats Cards --}}
<div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    {{-- Total RIS --}}
    <div class="group rounded-xl border border-gray-200 bg-white p-6 transition-all duration-200 hover:border-gray-300 hover:shadow-md card-hover slide-up" style="animation-delay: 0.05s">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total RIS</p>
                <p class="mt-2 text-4xl font-bold text-gray-900 count-up" data-target="{{ $totalRisCount ?? 0 }}">{{ $totalRisCount ?? 0 }}</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-50 text-slate-700">
                <i data-lucide="file-text" class="h-6 w-6"></i>
            </div>
        </div>
    </div>

    {{-- Pending Approvals --}}
    <a href="/president/approvals" class="group rounded-xl border border-gray-200 bg-white p-6 transition-all duration-200 hover:border-amber-300 hover:shadow-md card-hover slide-up" style="animation-delay: 0.1s">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Pending</p>
                <p class="mt-2 text-4xl font-bold text-gray-900 count-up" data-target="{{ $pendingApprovalsCount ?? 0 }}">{{ $pendingApprovalsCount ?? 0 }}</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                <i data-lucide="clock-3" class="h-6 w-6"></i>
            </div>
        </div>
    </a>

    {{-- Approved Decisions --}}
    <a href="/president/reports/approved" class="group rounded-xl border border-gray-200 bg-white p-6 transition-all duration-200 hover:border-emerald-300 hover:shadow-md card-hover slide-up" style="animation-delay: 0.15s">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Approved</p>
                <p class="mt-2 text-4xl font-bold text-gray-900 count-up" data-target="{{ $approvedDecisionsCount ?? 0 }}">{{ $approvedDecisionsCount ?? 0 }}</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                <i data-lucide="circle-check-big" class="h-6 w-6"></i>
            </div>
        </div>
    </a>

    {{-- Rejected Decisions --}}
    <a href="/president/reports/approved?filter=rejected" class="group rounded-xl border border-gray-200 bg-white p-6 transition-all duration-200 hover:border-rose-300 hover:shadow-md card-hover slide-up" style="animation-delay: 0.2s">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-700">Rejected</p>
                <p class="mt-2 text-4xl font-bold text-gray-900 count-up" data-target="{{ $rejectedDecisionsCount ?? 0 }}">{{ $rejectedDecisionsCount ?? 0 }}</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-rose-50 text-rose-700">
                <i data-lucide="x-circle" class="h-6 w-6"></i>
            </div>
        </div>
    </a>
</div>

{{-- Quick Actions & Recent Activity --}}
<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
    {{-- Quick Actions --}}
    <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6 slide-up" style="animation-delay: 0.25s">
        <h2 class="text-base font-bold text-gray-900">Quick Actions</h2>
        <p class="mt-1 text-xs text-gray-500">Common tasks and shortcuts</p>
        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <a href="/president/approvals" class="group flex items-center gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 transition hover:border-gray-300 hover:bg-white hover:shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-gray-700">
                    <i data-lucide="clipboard-check" class="h-5 w-5"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">RIS Review</p>
                    <p class="text-xs text-gray-500">Pending approvals</p>
                </div>
                <i data-lucide="arrow-right" class="h-4 w-4 text-gray-400 transition group-hover:translate-x-0.5"></i>
            </a>

            <a href="/president/approvals/history" class="group flex items-center gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 transition hover:border-gray-300 hover:bg-white hover:shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-gray-700">
                    <i data-lucide="history" class="h-5 w-5"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">Approval History</p>
                    <p class="text-xs text-gray-500">Past decisions</p>
                </div>
                <i data-lucide="arrow-right" class="h-4 w-4 text-gray-400 transition group-hover:translate-x-0.5"></i>
            </a>

            <a href="/president/reports/approved" class="group flex items-center gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 transition hover:border-gray-300 hover:bg-white hover:shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-gray-700">
                    <i data-lucide="badge-check" class="h-5 w-5"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">Decision Reports</p>
                    <p class="text-xs text-gray-500">View all decisions</p>
                </div>
                <i data-lucide="arrow-right" class="h-4 w-4 text-gray-400 transition group-hover:translate-x-0.5"></i>
            </a>

            <a href="/president/reports/monthly-summary" class="group flex items-center gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 transition hover:border-gray-300 hover:bg-white hover:shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-gray-700">
                    <i data-lucide="bar-chart-3" class="h-5 w-5"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">Reports & Summary</p>
                    <p class="text-xs text-gray-500">Analytics & trends</p>
                </div>
                <i data-lucide="arrow-right" class="h-4 w-4 text-gray-400 transition group-hover:translate-x-0.5"></i>
            </a>
        </div>
    </section>

    {{-- Recent Activity --}}
    <aside class="rounded-xl border border-gray-200 bg-white p-6 slide-up" style="animation-delay: 0.3s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900">Recent Activity</h2>
                <p class="mt-1 text-xs text-gray-500">Latest RIS updates</p>
            </div>
        </div>
        <div class="mt-4 flex flex-col gap-2.5">
            @forelse ($recentRis as $ris)
                @php
                    $statusLower = strtolower($ris->ris_status ?? '');
                    $icon = $statusLower === 'approved' ? 'circle-check-big' : ($statusLower === 'rejected' ? 'x-circle' : 'clock-3');
                    $color = $statusLower === 'approved' ? 'text-emerald-600 bg-emerald-50' : ($statusLower === 'rejected' ? 'text-rose-600 bg-rose-50' : 'text-amber-600 bg-amber-50');
                    $date = $ris->ris_created_at ? date('M d, Y', strtotime($ris->ris_created_at)) : '—';
                    $label = $ris->ris_form_number ?? ('RIS #' . $ris->ris_id);
                @endphp
                <a href="/president/approvals" class="flex items-center gap-3 rounded-lg p-2.5 transition hover:bg-gray-50">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $color }}">
                        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-900 truncate">{{ $label }}</p>
                        <p class="text-[11px] text-gray-500">{{ $date }}</p>
                    </div>
                </a>
            @empty
                <p class="text-xs text-gray-500">No recent activity</p>
            @endforelse
        </div>
        <a href="/president/notifications" class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition hover:text-gray-700 active:scale-95">
            View all notifications
            <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
        </a>
    </aside>
</div>

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
        transition: all 0.2s ease;
    }

    .card-hover:hover {
        transform: translateY(-2px);
    }

    .count-up {
        display: inline-block;
    }
</style>

<script>
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
    });
</script>

@endsection