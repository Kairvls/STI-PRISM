@extends('layouts.accounting-layout')

@section('title', 'Accounting Dashboard')

@section('content')
@include('accounting.partials.flash')

<div class="acc-page fade-in">
    <div class="acc-page-header">
        <div>
            <p class="acc-page-kicker">Accounting</p>
            <h1 class="acc-page-title">Dashboard</h1>
            <p class="acc-page-subtitle">Documents that need Accounting action.</p>
        </div>
    </div>

    <div class="acc-stat-grid">
        <a href="/accounting/authority-to-purchase" class="acc-stat-card slide-up" style="animation-delay:.04s">
            <p class="acc-stat-label is-warn">ATP pending</p>
            <p class="acc-stat-value">{{ $metrics['atp_pending'] }}</p>
            <p class="acc-stat-hint">Awaiting review</p>
            <div class="acc-stat-icon is-warn"><i data-lucide="file-check"></i></div>
        </a>
        <a href="/accounting/request-check" class="acc-stat-card slide-up" style="animation-delay:.08s">
            <p class="acc-stat-label is-warn">Request Checks</p>
            <p class="acc-stat-value">{{ $metrics['rfc_pending'] }}</p>
            <p class="acc-stat-hint">Pending review</p>
            <div class="acc-stat-icon is-warn"><i data-lucide="clipboard-list"></i></div>
        </a>
        <a href="/accounting/request-check?status=funds" class="acc-stat-card slide-up" style="animation-delay:.12s">
            <p class="acc-stat-label is-ok">Funds to release</p>
            <p class="acc-stat-value">{{ $metrics['funds_awaiting'] }}</p>
            <p class="acc-stat-hint">Ready for collection</p>
            <div class="acc-stat-icon is-ok"><i data-lucide="banknote"></i></div>
        </a>
        <a href="/accounting/liquidation-reports" class="acc-stat-card slide-up" style="animation-delay:.16s">
            <p class="acc-stat-label is-warn">Liquidations</p>
            <p class="acc-stat-value">{{ $metrics['liq_pending'] }}</p>
            <p class="acc-stat-hint">Pending review</p>
            <div class="acc-stat-icon is-warn"><i data-lucide="receipt"></i></div>
        </a>
    </div>

    <div class="acc-mini-grid">
        <div class="acc-mini-card slide-up" style="animation-delay:.18s">
            <p>Approved ATP</p>
            <p>{{ $metrics['atp_approved'] }}</p>
        </div>
        <div class="acc-mini-card slide-up" style="animation-delay:.2s">
            <p>Approved Request Checks</p>
            <p>{{ $metrics['rfc_approved'] }}</p>
        </div>
        <div class="acc-mini-card slide-up" style="animation-delay:.22s">
            <p>Approved liquidations</p>
            <p>{{ $metrics['liq_approved'] }}</p>
        </div>
        <div class="acc-mini-card slide-up" style="animation-delay:.24s">
            <p class="!text-sky-700">Out for revision</p>
            <p>{{ $metrics['needs_revision'] }}</p>
        </div>
    </div>

    <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-3">
        <section class="acc-card lg:col-span-2 slide-up" style="animation-delay:.26s">
            <div class="flex items-center justify-between border-b border-slate-100 px-3.5 py-2.5">
                <div>
                    <h2 class="acc-panel-title">Needs your attention</h2>
                    <p class="acc-panel-sub">ATP, Request Check, funds, and liquidation work</p>
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
                    <div class="p-4"><div class="acc-empty">Nothing waiting for Accounting right now.</div></div>
                @endforelse
            </div>
        </section>

        <aside class="acc-panel slide-up" style="animation-delay:.3s">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h2 class="acc-panel-title">Recent activity</h2>
                    <p class="acc-panel-sub">Accounting decisions</p>
                </div>
                <a href="/accounting/history" class="acc-link text-[11px]">View all</a>
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
