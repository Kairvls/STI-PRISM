@extends('layouts.receiving-layout')

@section('title', 'Dashboard')

@section('content')
@php
    $d = $dashboard;
    $queueRows = collect($d['queue']['rows'] ?? [])->take(5);
    $queueTotal = (int) ($d['queue']['total'] ?? 0);
    $replacements = $d['board']['receiving'] ?? ['count' => 0, 'tickets' => []];
    $replacementRows = collect($replacements['tickets'] ?? [])->take(5);
    $replacementTotal = (int) ($replacements['count'] ?? 0);
    $incompleteRows = collect($d['incomplete'] ?? [])->take(5);
    $incompleteTotal = (int) ($d['incompleteCount'] ?? $incompleteRows->count());

    $backOrderRows = collect();
    foreach ($d['board'] ?? [] as $status => $col) {
        if ($status === 'receiving') {
            continue;
        }
        foreach ($col['tickets'] ?? [] as $ticket) {
            $ticket->status_label = $col['label'] ?? '';
            $backOrderRows->push($ticket);
        }
    }
    $backOrderRows = $backOrderRows->take(5);
    $backOrderTotal = max(0, (int) ($d['snapshot']['total'] ?? 0) - $replacementTotal);
    $returnedRows = collect($d['returned']['rows'] ?? [])->take(5);
    $returnedTotal = (int) ($d['returned']['total'] ?? 0);
    $deliveredRows = collect($d['delivered']['rows'] ?? [])->take(5);
    $deliveredTotal = (int) ($d['delivered']['total'] ?? 0);

    $rrPreview = fn (int $id) => $id > 0 ? route('receiving.dashboard.preview-rr', $id) : '';
    $rfcPreview = fn (int $id) => $id > 0 ? route('receiving.dashboard.preview-rfc', $id) : '';
    $ticketPreview = function ($ticket) use ($rrPreview, $rfcPreview): string {
        $rootId = (int) ($ticket->root_id ?? 0);
        if ($rootId > 0) {
            return $rrPreview($rootId);
        }

        return $rfcPreview((int) ($ticket->rfc_id ?? 0));
    };
    $line = fn (...$parts) => collect($parts)->filter(fn ($part) => $part !== null && $part !== '')->implode(' · ');
    $showing = function (int $total, int $shown): string {
        if ($shown <= 0) {
            return '';
        }

        return $total > $shown
            ? $shown.' latest'
            : 'Showing '.$shown.' of '.$total;
    };
@endphp

<style>
    .rod-panel { border-radius: 1rem; border: 1px solid #e2e8f0; background: #fff; }
</style>

<div class="mx-auto max-w-[1400px] space-y-4">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <section class="rod-panel p-5 lg:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900">To count</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($queueTotal) }}</span>
                </div>
                <a href="{{ route('receiving.rr.index', ['focus' => 'queue']) }}" class="shrink-0 text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($queueRows as $rr)
                    <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-blue-50/40" data-dashboard-preview data-preview-url="{{ $rrPreview((int) $rr->id) }}" data-continue-url="{{ route('receiving.rr.index', ['focus' => 'queue', 'search' => $rr->number]) }}" data-continue-label="Open" data-preview-title="{{ $rr->number }}">
                        <span class="min-w-0">
                            <span class="flex items-center gap-2">
                                <span class="truncate text-xs font-semibold text-gray-900">{{ $rr->number }}</span>
                                @if ($rr->is_urgent)
                                    <span class="shrink-0 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">Urgent</span>
                                @endif
                            </span>
                            <span class="block truncate text-[11px] text-gray-500">{{ $line($rr->kind ?? '', $rr->supplier, $rr->submitted_at ? $rr->submitted_at->format('M d, Y') : '—') }}</span>
                        </span>
                        <span class="shrink-0 text-[11px] font-semibold text-blue-600">Open</span>
                    </button>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-gray-500">Nothing to count</p>
                @endforelse
            </div>
            @if ($queueRows->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ $showing($queueTotal, $queueRows->count()) }}</p>
            @endif
        </section>

        <aside class="rod-panel p-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900">Replacements</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($replacementTotal) }}</span>
                </div>
                <a href="{{ route('receiving.back-orders.index', ['status' => 'receiving']) }}" class="shrink-0 text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
            </div>
            <div class="mt-4 space-y-2">
                @forelse ($replacementRows as $ticket)
                    <button type="button" class="block w-full rounded-xl px-3 py-2.5 text-left transition hover:bg-blue-50/60" data-dashboard-preview data-preview-url="{{ $ticketPreview($ticket) }}" data-continue-url="{{ route('receiving.back-orders.index', array_filter(['status' => 'receiving', 'rr' => $ticket->root_id ?: null])) }}" data-continue-label="Open" data-preview-title="{{ $ticket->article }}">
                        <p class="truncate text-xs font-semibold text-gray-900">{{ $ticket->article }}</p>
                        <p class="truncate text-[11px] text-gray-500">{{ $line($ticket->kind ?? '', $ticket->qty.' '.$ticket->unit, $ticket->number) }}</p>
                    </button>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-gray-500">None</p>
                @endforelse
            </div>
            @if ($replacementRows->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ $showing($replacementTotal, $replacementRows->count()) }}</p>
            @endif
        </aside>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <section class="rod-panel p-5 lg:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900">Incomplete reports</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($incompleteTotal) }}</span>
                </div>
                <a href="{{ route('receiving.rr.index', ['status' => 'completed']) }}" class="shrink-0 text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($incompleteRows as $rr)
                    <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-blue-50/40" data-dashboard-preview data-preview-url="{{ $rrPreview((int) $rr->id) }}" data-continue-url="{{ route('receiving.back-orders.index', ['rr' => $rr->id, 'status' => 'all']) }}" data-continue-label="Open" data-preview-title="{{ $rr->number }}">
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-semibold text-gray-900">{{ $rr->number }}</span>
                            <span class="block truncate text-[11px] text-gray-500">{{ $line($rr->kind ?? '', $rr->supplier, $rr->total > 0 ? $rr->settled.'/'.$rr->total.' settled' : null) }}</span>
                        </span>
                        <span class="shrink-0 text-[11px] font-semibold text-blue-600">Open</span>
                    </button>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-gray-500">None</p>
                @endforelse
            </div>
            @if ($incompleteRows->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ $showing($incompleteTotal, $incompleteRows->count()) }}</p>
            @endif
        </section>

        <aside class="rod-panel p-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900">Back orders</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($backOrderTotal) }}</span>
                </div>
                <a href="{{ route('receiving.back-orders.index') }}" class="shrink-0 text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
            </div>
            <div class="mt-4 space-y-2">
                @forelse ($backOrderRows as $ticket)
                    <button type="button" class="block w-full rounded-xl px-3 py-2.5 text-left transition hover:bg-blue-50/60" data-dashboard-preview data-preview-url="{{ $ticketPreview($ticket) }}" data-continue-url="{{ route('receiving.back-orders.index', ['status' => 'all']) }}" data-continue-label="Open" data-preview-title="{{ $ticket->article }}">
                        <p class="truncate text-xs font-semibold text-gray-900">{{ $ticket->article }}</p>
                        <p class="truncate text-[11px] text-gray-500">{{ $line($ticket->kind ?? '', $ticket->status_label, $ticket->qty.' '.$ticket->unit) }}</p>
                    </button>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-gray-500">None</p>
                @endforelse
            </div>
            @if ($backOrderRows->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ $showing($backOrderTotal, $backOrderRows->count()) }}</p>
            @endif
        </aside>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <section class="rod-panel p-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900">Returned</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($returnedTotal) }}</span>
                </div>
                <a href="{{ route('receiving.rr.index', ['focus' => 'returned']) }}" class="shrink-0 text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($returnedRows as $rr)
                    <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-blue-50/40" data-dashboard-preview data-preview-url="{{ $rrPreview((int) $rr->id) }}" data-continue-url="{{ route('receiving.rr.index', ['rr' => $rr->id]) }}" data-continue-label="Open" data-preview-title="{{ $rr->number }}">
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-semibold text-gray-900">{{ $rr->number }}</span>
                            <span class="block truncate text-[11px] text-gray-500">{{ $line($rr->kind ?? '', $rr->supplier, $rr->at ? $rr->at->format('M d, Y') : '—') }}</span>
                        </span>
                        <span class="shrink-0 text-[11px] font-semibold text-blue-600">Open</span>
                    </button>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-gray-500">None</p>
                @endforelse
            </div>
            @if ($returnedRows->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ $showing($returnedTotal, $returnedRows->count()) }}</p>
            @endif
        </section>

        <section class="rod-panel p-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900">Delivered</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($deliveredTotal) }}</span>
                </div>
                <a href="{{ url('/receiving/delivered-items') }}" class="shrink-0 text-xs font-semibold text-blue-600 transition hover:text-blue-800">View all</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($deliveredRows as $rr)
                    <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-blue-50/40" data-dashboard-preview data-preview-url="{{ $rrPreview((int) $rr->id) }}" data-continue-url="{{ route('receiving.rr.index', ['rr' => $rr->id]) }}" data-continue-label="Open" data-preview-title="{{ $rr->number }}">
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-semibold text-gray-900">{{ $rr->number }}</span>
                            <span class="block truncate text-[11px] text-gray-500">{{ $line($rr->kind ?? '', $rr->supplier, $rr->at ? $rr->at->format('M d, Y') : '—') }}</span>
                        </span>
                        <span class="shrink-0 text-[11px] font-semibold text-blue-600">Open</span>
                    </button>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-gray-500">None</p>
                @endforelse
            </div>
            @if ($deliveredRows->isNotEmpty())
                <p class="mt-3 text-xs text-slate-500">{{ $showing($deliveredTotal, $deliveredRows->count()) }}</p>
            @endif
        </section>
    </div>
</div>

<div id="receivingDashboardPreview" class="fixed inset-0 z-[12000] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="receivingDashboardPreviewTitle">
    <div class="my-4 flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
            <h2 id="receivingDashboardPreviewTitle" class="truncate text-sm font-semibold text-slate-950">Form</h2>
            <button type="button" data-preview-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <div class="relative h-[70vh] bg-slate-100">
            <iframe id="receivingDashboardPreviewFrame" title="Form preview" class="h-full w-full border-0 bg-white" src="about:blank"></iframe>
            <div id="receivingDashboardPreviewSkeleton" class="absolute inset-0 z-10 hidden overflow-auto bg-slate-100 py-6">
                @include('partials.skeleton', ['skeletonType' => 'document', 'skeletonLabel' => 'Loading form'])
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-4 py-3">
            <button type="button" data-preview-close class="text-sm font-medium text-slate-500 hover:text-slate-900">Close</button>
            <a id="receivingDashboardPreviewContinue" href="#" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">Open</a>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('receivingDashboardPreview');
        const frame = document.getElementById('receivingDashboardPreviewFrame');
        const skeleton = document.getElementById('receivingDashboardPreviewSkeleton');
        const title = document.getElementById('receivingDashboardPreviewTitle');
        const continueLink = document.getElementById('receivingDashboardPreviewContinue');
        if (!modal || !frame || !skeleton || !title || !continueLink) return;
        if (modal.parentElement !== document.body) document.body.appendChild(modal);

        function showSkeleton() { skeleton.classList.remove('hidden'); }
        function hideSkeleton() { skeleton.classList.add('hidden'); }

        frame.addEventListener('load', function () {
            const src = frame.getAttribute('src') || '';
            if (!src || src === 'about:blank') return;
            hideSkeleton();
        });

        function closePreview() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            hideSkeleton();
            frame.src = 'about:blank';
            document.body.style.overflow = '';
        }

        function openPreview(trigger) {
            const previewUrl = trigger.getAttribute('data-preview-url') || '';
            const continueUrl = trigger.getAttribute('data-continue-url') || '';
            if (!previewUrl) {
                if (continueUrl) window.location = continueUrl;
                return;
            }
            title.textContent = trigger.getAttribute('data-preview-title') || 'Form';
            continueLink.href = continueUrl || '#';
            continueLink.textContent = trigger.getAttribute('data-continue-label') || 'Open';
            showSkeleton();
            const joiner = previewUrl.indexOf('?') === -1 ? '?' : '&';
            frame.src = previewUrl + joiner + 'ts=' + Date.now();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            if (window.lucide) lucide.createIcons();
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-dashboard-preview]');
            if (!trigger) return;
            const inner = event.target.closest('a, button');
            if (inner && inner !== trigger) return;
            openPreview(trigger);
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal || event.target.closest('[data-preview-close]')) closePreview();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closePreview();
        });
    })();
</script>
@endsection
