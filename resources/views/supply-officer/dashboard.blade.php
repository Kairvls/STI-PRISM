@extends('layouts.supply-officer-layout')

@section('title', 'Dashboard')

@section('content')
@php
    use App\Support\RisWorkflow;
    use Carbon\Carbon;

    $formatDate = function ($value) {
        if (blank($value)) {
            return '—';
        }

        return Carbon::parse($value)->format('M j, Y');
    };

    $atpLabel = function ($atp) {
        $number = trim((string) ($atp->authority_purchase_form_number ?? ''));
        if ($number !== '') {
            return $number;
        }

        $ris = trim((string) ($atp->ris_form_number ?? ''));
        if ($ris === '') {
            $ris = trim((string) ($atp->ris_request_no ?? ''));
        }

        return $ris !== '' ? $ris : 'Draft';
    };

    $supplierNote = function ($ris) {
        $total = (int) ($ris->supplier_total ?? 0);
        $open = (int) ($ris->supplier_open ?? 0);
        if ($total < 2 || $open < 1) {
            return null;
        }

        return $open.' of '.$total.' suppliers still '.($open === 1 ? 'needs' : 'need').' an ATP';
    };

    $revisionNote = function ($atp) {
        $reason = trim((string) ($atp->authority_purchase_rejection_reason ?? ''));
        if ($reason === '') {
            return null;
        }

        return 'Sent back: '.$reason;
    };

    $sections = [
        [
            'title' => 'Make ATP',
            'count' => $incomingRisCount,
            'url' => route('supply-officer.incoming-ris'),
            'empty' => 'No incoming RIS',
            'rows' => $latestIncomingRis->map(function ($ris) use ($formatDate, $supplierNote) {
                return [
                    'title' => RisWorkflow::formNumber($ris),
                    'note' => $supplierNote($ris),
                    'meta' => trim(($ris->sent_by_name ?: 'Purchaser').' · '.$formatDate($ris->ris_sent_to_supply_at)),
                    'preview' => route('supply-officer.dashboard.preview-ris', $ris->ris_id),
                    'url' => route('supply-officer.atp.index', ['selected_ris' => $ris->ris_id]),
                    'action' => 'Create',
                ];
            }),
        ],
        [
            'title' => 'ATP drafts',
            'count' => $atpDraftCount,
            'url' => route('supply-officer.atp.index', ['status' => 'Draft']),
            'empty' => 'No drafts',
            'rows' => $latestAtpDrafts->map(function ($atp) use ($formatDate, $atpLabel, $revisionNote) {
                return [
                    'title' => $atpLabel($atp),
                    'note' => $revisionNote($atp),
                    'meta' => trim((blank($atp->authority_purchase_form_number ?? null) ? 'Draft · ' : '').$formatDate($atp->authority_purchase_updated_at ?: $atp->authority_purchase_created_at)),
                    'preview' => route('supply-officer.dashboard.preview-atp', $atp->authority_purchase_id),
                    'url' => route('supply-officer.atp.index', ['edit_atp' => $atp->authority_purchase_id]),
                    'action' => 'Open',
                ];
            }),
        ],
        [
            'title' => 'Pending approval',
            'count' => $atpPendingCount,
            'url' => route('supply-officer.atp.index', ['status' => 'Submitted']),
            'empty' => 'None waiting',
            'rows' => $latestAtpPending->map(function ($atp) use ($formatDate, $atpLabel) {
                return [
                    'title' => $atpLabel($atp),
                    'meta' => 'Sent '.$formatDate($atp->authority_purchase_submitted_at),
                    'preview' => route('supply-officer.dashboard.preview-atp', $atp->authority_purchase_id),
                    'url' => route('supply-officer.atp.index', ['view_atp' => $atp->authority_purchase_id]),
                    'action' => 'View',
                ];
            }),
        ],
        [
            'title' => 'Return to Purchaser',
            'count' => $atpToReleaseCount,
            'url' => route('supply-officer.atp.index', ['status' => 'Approved']),
            'empty' => 'None ready',
            'rows' => $latestAtpToRelease->map(function ($atp) use ($formatDate, $atpLabel) {
                return [
                    'title' => $atpLabel($atp),
                    'meta' => 'Approved '.$formatDate($atp->authority_purchase_updated_at),
                    'preview' => route('supply-officer.dashboard.preview-atp', $atp->authority_purchase_id),
                    'url' => route('supply-officer.atp.index', ['view_atp' => $atp->authority_purchase_id]),
                    'action' => 'Open',
                ];
            }),
        ],
    ];
@endphp

<div>
    <div class="grid gap-4 xl:grid-cols-2">
        @foreach ($sections as $section)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-5 py-3.5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <h2 class="truncate text-sm font-semibold text-slate-900">{{ $section['title'] }}</h2>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">
                                {{ number_format($section['count']) }}
                            </span>
                        </div>
                        <a href="{{ $section['url'] }}" class="shrink-0 text-xs font-semibold text-[#0025cc] hover:underline">
                            View all
                        </a>
                    </div>
                    @if ($section['count'] > $section['rows']->count())
                        <p class="mt-1 text-xs text-slate-500">{{ $section['rows']->count() }} latest</p>
                    @endif
                </div>

                <ul class="divide-y divide-slate-100">
                    @forelse ($section['rows'] as $row)
                        <li>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-3 px-5 py-3 text-left transition hover:bg-slate-50"
                                data-dashboard-preview
                                data-preview-url="{{ $row['preview'] }}"
                                data-continue-url="{{ $row['url'] }}"
                                data-continue-label="{{ $row['action'] }}"
                                data-preview-title="{{ $row['title'] }}"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $row['title'] }}</p>
                                    @if (!empty($row['note']))
                                        <p class="mt-0.5 truncate text-xs font-medium text-amber-800" title="{{ $row['note'] }}">{{ $row['note'] }}</p>
                                    @endif
                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $row['meta'] }}</p>
                                </div>
                                <span class="shrink-0 text-xs font-semibold text-slate-500">{{ $row['action'] }}</span>
                            </button>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-slate-400">
                            {{ $section['empty'] }}
                        </li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</div>

<div id="supplyDashboardPreview" class="fixed inset-0 z-[80] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="supplyDashboardPreviewTitle">
    <div class="my-auto w-full max-w-5xl overflow-hidden rounded-xl bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <h2 id="supplyDashboardPreviewTitle" class="truncate text-lg font-semibold text-slate-950"></h2>
            <button type="button" id="supplyDashboardPreviewClose" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <div id="supplyDashboardPreviewBody" class="max-h-[70vh] overflow-auto bg-slate-50"></div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4">
            <button type="button" id="supplyDashboardPreviewDismiss" class="text-sm font-medium text-slate-500 hover:text-slate-900">Close</button>
            <a id="supplyDashboardPreviewContinue" href="#" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">Open</a>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('supplyDashboardPreview');
        const title = document.getElementById('supplyDashboardPreviewTitle');
        const body = document.getElementById('supplyDashboardPreviewBody');
        const continueLink = document.getElementById('supplyDashboardPreviewContinue');
        if (!modal || !body) return;

        const skeleton = '<div class="space-y-3 p-5" aria-hidden="true">'
            + '<div class="h-8 w-1/3 animate-pulse rounded-md bg-slate-200"></div>'
            + '<div class="h-40 animate-pulse rounded-lg bg-slate-200"></div>'
            + '<div class="h-24 animate-pulse rounded-lg bg-slate-200"></div>'
            + '</div>';

        function closePreview() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            body.innerHTML = '';
            document.body.style.overflow = '';
        }

        async function openPreview(button) {
            title.textContent = button.dataset.previewTitle || 'Preview';
            continueLink.href = button.dataset.continueUrl || '#';
            continueLink.textContent = button.dataset.continueLabel || 'Open';
            body.innerHTML = skeleton;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            if (window.lucide) lucide.createIcons();

            try {
                const response = await fetch(button.dataset.previewUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                });
                if (!response.ok) throw new Error('Preview failed');
                body.innerHTML = await response.text();
                if (window.lucide) lucide.createIcons();
            } catch (error) {
                body.innerHTML = '<p class="px-5 py-10 text-center text-sm text-red-600">Could not load this form.</p>';
            }
        }

        document.querySelectorAll('[data-dashboard-preview]').forEach((button) => {
            button.addEventListener('click', () => openPreview(button));
        });

        document.getElementById('supplyDashboardPreviewClose')?.addEventListener('click', closePreview);
        document.getElementById('supplyDashboardPreviewDismiss')?.addEventListener('click', closePreview);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) closePreview();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closePreview();
        });
    })();
</script>
@endsection
