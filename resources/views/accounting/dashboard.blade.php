@extends('layouts.accounting-layout')

@section('title', 'Accounting Dashboard')

@section('content')
@include('accounting.partials.flash')

<div class="fade-in">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h1>
    <p class="mt-0.5 text-sm text-gray-500">Documents that need Accounting action.</p>
</div>

<div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
    <a href="/accounting/authority-to-purchase" class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 card-hover slide-up" style="animation-delay:.05s">
        <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">ATP pending</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $metrics['atp_pending'] }}</p>
        <p class="mt-1 text-[11px] text-amber-500">Awaiting review</p>
        <div class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-lg bg-amber-50 text-amber-600 ring-1 ring-amber-100">
            <i data-lucide="file-check" class="h-5 w-5"></i>
        </div>
    </a>
    <a href="/accounting/request-check" class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 card-hover slide-up" style="animation-delay:.1s">
        <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Request Checks</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $metrics['rfc_pending'] }}</p>
        <p class="mt-1 text-[11px] text-amber-500">Pending review</p>
        <div class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-lg bg-amber-50 text-amber-600 ring-1 ring-amber-100">
            <i data-lucide="clipboard-list" class="h-5 w-5"></i>
        </div>
    </a>
    <a href="/accounting/request-check?status=funds" class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 card-hover slide-up" style="animation-delay:.15s">
        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Funds to release</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $metrics['funds_awaiting'] }}</p>
        <p class="mt-1 text-[11px] text-emerald-500">Ready for collection</p>
        <div class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
            <i data-lucide="banknote" class="h-5 w-5"></i>
        </div>
    </a>
    <a href="/accounting/liquidation-reports" class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 card-hover slide-up" style="animation-delay:.2s">
        <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Liquidations</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $metrics['liq_pending'] }}</p>
        <p class="mt-1 text-[11px] text-amber-500">Pending review</p>
        <div class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-lg bg-amber-50 text-amber-600 ring-1 ring-amber-100">
            <i data-lucide="receipt" class="h-5 w-5"></i>
        </div>
    </a>
</div>

<div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 slide-up" style="animation-delay:.22s">
        <p class="text-xs font-medium text-gray-500">Approved ATP</p>
        <p class="mt-1 text-xl font-bold text-gray-900">{{ $metrics['atp_approved'] }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 slide-up" style="animation-delay:.24s">
        <p class="text-xs font-medium text-gray-500">Approved Request Checks</p>
        <p class="mt-1 text-xl font-bold text-gray-900">{{ $metrics['rfc_approved'] }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 slide-up" style="animation-delay:.26s">
        <p class="text-xs font-medium text-gray-500">Approved liquidations</p>
        <p class="mt-1 text-xl font-bold text-gray-900">{{ $metrics['liq_approved'] }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 slide-up" style="animation-delay:.28s">
        <p class="text-xs font-medium text-sky-700">Out for revision</p>
        <p class="mt-1 text-xl font-bold text-gray-900">{{ $metrics['needs_revision'] }}</p>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <section class="lg:col-span-2 overflow-hidden rounded-xl border border-gray-200 bg-white slide-up" style="animation-delay:.3s">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Needs your attention</h2>
                <p class="text-xs text-gray-400">ATP, Request Check, funds, and liquidation work</p>
            </div>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($queue as $item)
                <a href="{{ $item->url }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50">
                    <span class="w-24 shrink-0 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $item->type }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-gray-900">{{ $item->ref }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $item->related }} · {{ $item->who }}</p>
                    </div>
                    <span class="w-24 text-right text-sm font-medium text-gray-900">{{ $item->amount !== null ? '₱'.number_format((float)$item->amount, 2) : '—' }}</span>
                    @include('accounting.partials.status-badge', ['status' => $item->status])
                    <span class="w-24 text-right text-xs font-semibold text-gray-900">{{ $item->action }}</span>
                </a>
            @empty
                <div class="acc-empty m-5 rounded-lg p-10 text-center text-sm text-gray-500">Nothing waiting for Accounting right now.</div>
            @endforelse
        </div>
    </section>

    <aside class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay:.34s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Recent activity</h2>
                <p class="text-xs text-gray-400">Accounting decisions</p>
            </div>
            <a href="/accounting/history" class="text-xs font-semibold text-gray-900 hover:text-amber-600">View all</a>
        </div>
        <div class="mt-4 space-y-3">
            @forelse ($recentActivity as $event)
                <div class="border-b border-gray-50 pb-3 last:border-0">
                    <p class="text-sm font-semibold text-gray-900">{{ $event->approval_log_approval_status }} · {{ $event->approval_log_reference_type }}</p>
                    <p class="text-xs text-gray-500">{{ $event->user_full_name ?? 'Accounting' }} · {{ $event->approval_log_approved_at ? \Carbon\Carbon::parse($event->approval_log_approved_at)->format('M d, g:i A') : '' }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500">No recent Accounting actions.</p>
            @endforelse
        </div>
    </aside>
</div>
@endsection
