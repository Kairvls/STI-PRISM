@extends('layouts.president-layout')

@section('title', 'President Dashboard')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">

    <div>

        <p class="text-sm font-medium text-gray-500">

            President Overview

        </p>

        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-gray-900">

            President Dashboard

        </h1>

        <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">

            Review approval requests, monitor decisions, and stay updated with the latest notifications.

        </p>

    </div>

    <div class="flex items-center gap-2">

        <a href="/president/approvals" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white transition hover:bg-gray-800 active:scale-95">

            <i data-lucide="clipboard-check" class="h-4 w-4"></i>

            Review Approvals

        </a>

    </div>

</div>

<div class="mt-7 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

    {{-- Total RIS --}}

    <div class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm card-hover slide-up" style="animation-delay: 0.05s">

        <div class="flex items-start justify-between gap-4">

            <div>

                <p class="text-sm font-medium text-gray-500">Total RIS</p>

                <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $totalRisCount ?? 0 }}">{{ $totalRisCount ?? 0 }}</p>

                <p class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">

                    <span>All records</span>

                </p>

            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-50 text-slate-700">

                <i data-lucide="file-text" class="h-5 w-5"></i>

            </div>

        </div>

    </div>

    {{-- Pending approvals --}}

    <a href="/president/approvals" class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm card-hover slide-up" style="animation-delay: 0.1s">

        <div class="flex items-start justify-between gap-4">

            <div>

                <p class="text-sm font-medium text-gray-500">Pending Approvals</p>

                <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $pendingApprovalsCount ?? 0 }}">{{ $pendingApprovalsCount ?? 0 }}</p>

                <p class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">

                    <span>Needs your decision</span>

                    <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>

                </p>

            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-700">

                <i data-lucide="clock-3" class="h-5 w-5"></i>

            </div>

        </div>

    </a>

    {{-- Approved decisions --}}

    <a href="/president/reports/approved" class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm card-hover slide-up" style="animation-delay: 0.15s">

        <div class="flex items-start justify-between gap-4">

            <div>

                <p class="text-sm font-medium text-gray-500">Approved Decisions</p>

                <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $approvedDecisionsCount ?? 0 }}">{{ $approvedDecisionsCount ?? 0 }}</p>

                <p class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">

                    <span>Successful approvals</span>

                    <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>

                </p>

            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">

                <i data-lucide="circle-check-big" class="h-5 w-5"></i>

            </div>

        </div>

    </a>

    {{-- Rejected decisions --}}

    <a href="/president/reports/rejected" class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm card-hover slide-up" style="animation-delay: 0.2s">

        <div class="flex items-start justify-between gap-4">

            <div>

                <p class="text-sm font-medium text-gray-500">Rejected Decisions</p>

                <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $rejectedDecisionsCount ?? 0 }}">{{ $rejectedDecisionsCount ?? 0 }}</p>

                <p class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">

                    <span>Review rejection reasons</span>

                    <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>

                </p>

            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-50 text-rose-700">

                <i data-lucide="x-circle" class="h-5 w-5"></i>

            </div>

        </div>

    </a>

</div>

<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- President Procurement Packet --}}

    <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6 slide-up" style="animation-delay: 0.25s">

        <div class="flex items-start justify-between gap-4">

            <div>

                <h2 class="text-sm font-semibold text-gray-900">President Procurement Packet</h2>

                <p class="mt-1 text-sm text-gray-500">RIS and procurement documents you need to review before signing decisions.</p>

            </div>

        </div>

        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">

            {{-- RIS --}}

            <a href="/president/approvals" class="group rounded-lg bg-gray-50 p-4 transition hover:bg-gray-100">

                <p class="text-xs font-semibold text-gray-900">RIS Review</p>

                <p class="mt-1 text-xs text-gray-500">Review Request Information Sheets forwarded for presidential approval.</p>

                <span class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition">

                    Review RIS

                    <i data-lucide="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5"></i>

                </span>

            </a>

            {{-- Approval History --}}

            <a href="/president/approvals/history" class="group rounded-lg bg-gray-50 p-4 transition hover:bg-gray-100">

                <p class="text-xs font-semibold text-gray-900">Approval History</p>

                <p class="mt-1 text-xs text-gray-500">Timeline of past RIS and procurement decisions made by the President.</p>

                <span class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition">

                    View History

                    <i data-lucide="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5"></i>

                </span>

            </a>

            {{-- Monthly Summary --}}

            <a href="/president/reports/monthly-summary" class="group rounded-lg bg-gray-50 p-4 transition hover:bg-gray-100">

                <p class="text-xs font-semibold text-gray-900">Monthly Summary</p>

                <p class="mt-1 text-xs text-gray-500">Aggregated approved vs rejected decisions by month.</p>

                <span class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition">

                    View Summary

                    <i data-lucide="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5"></i>

                </span>

            </a>

            {{-- Decision Reports --}}

            <a href="/president/reports/approved" class="group rounded-lg bg-gray-50 p-4 transition hover:bg-gray-100">

                <p class="text-xs font-semibold text-gray-900">Decision Reports</p>

                <p class="mt-1 text-xs text-gray-500">Detailed list of all approved and rejected procurement outcomes.</p>

                <span class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition">

                    View Reports

                    <i data-lucide="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5"></i>

                </span>

            </a>

        </div>

    </section>

    {{-- Decision Summary + Recent Activity --}}

    <aside class="rounded-xl border border-gray-200 bg-white p-6 slide-up" style="animation-delay: 0.3s">

        <h2 class="text-sm font-semibold text-gray-900">Decision Summary</h2>

        <p class="mt-1 text-sm text-gray-500">Quick access to all approved and rejected procurement decisions.</p>

        <div class="mt-4 flex items-center gap-2">

            <a href="/president/reports/approved" class="inline-flex h-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100 active:scale-95">

                Approved

            </a>

            <a href="/president/reports/rejected" class="inline-flex h-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3 text-xs font-semibold text-rose-800 transition hover:bg-rose-100 active:scale-95">

                Rejected

            </a>

        </div>

        <div class="mt-5 rounded-lg bg-gray-50 p-4">

            <p class="text-xs font-semibold text-gray-900">Monthly decision reports</p>

            <p class="mt-1 text-xs text-gray-500">Review aggregated decision trends for better oversight.</p>

            <a href="/president/reports/monthly-summary" class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition hover:text-gray-700 active:scale-95">

                View Monthly Summary

                <i data-lucide="arrow-right" class="h-4 w-4"></i>

            </a>

        </div>

        <div class="mt-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-5">

            <p class="text-xs font-semibold text-gray-900">Recent Activity</p>

            <div class="mt-3 flex flex-col gap-3">
                @forelse ($recentRis as $ris)
                    @php
                        $statusLower = strtolower($ris->ris_status ?? '');
                        $icon = $statusLower === 'approved' ? 'circle-check-big' : ($statusLower === 'rejected' ? 'x-circle' : 'clock-3');
                        $color = $statusLower === 'approved' ? 'text-emerald-600 bg-emerald-50' : ($statusLower === 'rejected' ? 'text-rose-600 bg-rose-50' : 'text-amber-600 bg-amber-50');
                        $date = $ris->ris_created_at ? date('M d, Y', strtotime($ris->ris_created_at)) : '—';
                        $label = $ris->ris_form_number ?? ('RIS #' . $ris->ris_id);
                    @endphp
                    <a href="/president/approvals" class="flex items-start gap-3 rounded-lg p-2 transition hover:bg-white hover:shadow-sm">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $color }}">
                            <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-gray-900 truncate">{{ $label }}</p>
                            <p class="text-[11px] text-gray-500">{{ $date }}</p>
                        </div>
                    </a>
                @empty
                    <p class="text-xs text-gray-500">No recent activity.</p>
                @endforelse
            </div>

            <a href="/president/notifications" class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition hover:text-gray-700 active:scale-95">

                Go to Notifications

                <i data-lucide="arrow-right" class="h-4 w-4"></i>

            </a>

        </div>

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
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .card-hover:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
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
