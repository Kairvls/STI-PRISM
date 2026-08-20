@extends('layouts.accounting-layout')

@section('title', 'Accounting Dashboard')

@section('content')
@include('accounting.partials.flash')

<div class="acc-page fade-in">
    <div>
        <p class="text-sm text-gray-500">Documents that need Accounting action.</p>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <a href="/accounting/authority-to-purchase" class="pm-stat-card relative slide-up" style="animation-delay:.04s">
            <div class="pm-stat-icon bg-blue-50 text-blue-600">
                <i data-lucide="file-check"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="pm-stat-label">ATP pending</p>
                <p class="pm-stat-value is-blue"><span>{{ $metrics['atp_pending'] }}</span> Awaiting review</p>
            </div>
        </a>
        <a href="/accounting/request-check" class="pm-stat-card relative slide-up" style="animation-delay:.08s">
            <div class="pm-stat-icon bg-blue-50 text-blue-600">
                <i data-lucide="clipboard-list"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="pm-stat-label">Request Checks</p>
                <p class="pm-stat-value is-blue"><span>{{ $metrics['rfc_pending'] }}</span> Pending review</p>
            </div>
        </a>
        <a href="/accounting/request-check?status=funds" class="pm-stat-card relative slide-up" style="animation-delay:.12s">
            <div class="pm-stat-icon bg-blue-50 text-blue-600">
                <i data-lucide="banknote"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="pm-stat-label">Funds to release</p>
                <p class="pm-stat-value is-blue"><span>{{ $metrics['funds_awaiting'] }}</span> Ready</p>
            </div>
        </a>
        <a href="/accounting/liquidation-reports" class="pm-stat-card relative slide-up" style="animation-delay:.16s">
            <div class="pm-stat-icon bg-slate-100 text-slate-600">
                <i data-lucide="receipt"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="pm-stat-label">Liquidations</p>
                <p class="pm-stat-value"><span>{{ $metrics['liq_pending'] }}</span> Pending review</p>
            </div>
        </a>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="pm-kpi-card slide-up" style="animation-delay:.18s">
            <p class="text-xs font-semibold text-gray-500">Approved ATP</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ $metrics['atp_approved'] }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay:.2s">
            <p class="text-xs font-semibold text-gray-500">Approved Request Checks</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ $metrics['rfc_approved'] }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay:.22s">
            <p class="text-xs font-semibold text-gray-500">Approved liquidations</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">{{ $metrics['liq_approved'] }}</p>
        </div>
        <div class="pm-kpi-card slide-up" style="animation-delay:.24s">
            <p class="text-xs font-semibold text-gray-500">Out for revision</p>
            <p class="mt-2 text-2xl font-bold text-slate-700">{{ $metrics['needs_revision'] }}</p>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <section class="pm-card lg:col-span-2 slide-up overflow-hidden" style="animation-delay:.26s">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Needs your attention</h2>
                    <p class="mt-0.5 text-xs text-gray-400">ATP, Request Check, funds, and liquidation work</p>
                </div>
            </div>
            <div>
                @forelse ($queue as $item)
                    <a href="{{ $item->url }}" class="acc-feed-item">
                        <span class="acc-feed-type">{{ $item->type }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $item->ref }}</p>
                            <p class="truncate text-[11px] text-slate-500">{{ $item->related }} · {{ $item->who }}</p>
                        </div>
                        <span class="acc-feed-money">{{ $item->amount !== null ? '₱'.number_format((float)$item->amount, 2) : '—' }}</span>
                        @include('accounting.partials.status-badge', ['status' => $item->status])
                        <span class="acc-feed-action">{{ $item->action }}</span>
                    </a>
                @empty
                    <div class="p-6"><div class="acc-empty">Nothing waiting for Accounting right now.</div></div>
                @endforelse
            </div>
        </section>

        <aside class="pm-card p-5 slide-up" style="animation-delay:.3s">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Recent activity</h2>
                    <p class="mt-0.5 text-xs text-gray-400">Accounting decisions</p>
                </div>
                <a href="/accounting/history" class="text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
            </div>
            <div class="mt-2">
                @forelse ($recentActivity as $event)
                    <div class="acc-activity-item">
                        <p class="text-xs font-semibold text-slate-900">{{ $event->approval_log_approval_status }} · {{ $event->approval_log_reference_type }}</p>
                        <p class="text-[11px] text-slate-500">{{ $event->user_full_name ?? 'Accounting' }} · {{ $event->approval_log_approved_at ? \Carbon\Carbon::parse($event->approval_log_approved_at)->format('M d, g:i A') : '' }}</p>
                    </div>
                @empty
                    <p class="py-4 text-center text-xs text-slate-400">No recent Accounting actions.</p>
                @endforelse
            </div>
        </aside>
    </div>
</div>
@endsection
