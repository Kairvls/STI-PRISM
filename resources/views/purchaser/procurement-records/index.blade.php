@extends('layouts.purchaser-layout')

@section('page-title', 'Compiled Records')
@section('page-subtitle', 'Submit completed workflow document checklists to Accounting.')

@section('content')
@php
    use App\Support\ProcurementPaymentPath;
    use App\Support\ProcurementRecordCompiler;
@endphp

@include('partials.procurement-records-ui')

<div class="pr-module space-y-5">
    <div class="pr-hero">
        <p class="pr-hero-kicker">Purchasing Workflow</p>
        <h1 class="pr-hero-title">Compiled Records</h1>
        <p class="pr-hero-sub">
            When a procurement workflow is complete, submit a checklist of linked documents
            (RIS, ATP, funding request, RR, and Liquidation for Cash Advance) to Accounting for records.
        </p>
    </div>

    @php $hasSubmitted = $packages instanceof \Illuminate\Pagination\LengthAwarePaginator && $packages->count(); @endphp

    <div class="pr-grid {{ $hasSubmitted ? 'pr-grid--split' : '' }}">
    @if($hasSubmitted)
        <section class="pr-section">
            <div class="pr-surface h-full">
                <div class="pr-section-head">
                    <h2 class="pr-section-title">Submitted packages</h2>
                    <p class="pr-section-sub">Records already sent to Accounting for review.</p>
                </div>
                <div class="pr-list">
                    @foreach($packages as $pkg)
                        @php
                            $status = str_replace('_', ' ', ucfirst($pkg->package_status));
                            $statusClass = match ($pkg->package_status) {
                                'submitted_to_accounting' => 'pr-badge--amber',
                                'forwarded_to_president' => 'pr-badge--green',
                                default => '',
                            };
                        @endphp
                        <div class="pr-list-row pr-list-row--static">
                            <div class="pr-list-main">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="pr-list-ref">{{ $pkg->authority_purchase_form_number ?? '—' }}</span>
                                    <span class="pr-badge {{ $statusClass }}">{{ $status }}</span>
                                </div>
                                <div class="pr-list-meta">
                                    <span>
                                        <i data-lucide="clipboard-list" class="h-3 w-3"></i>
                                        RIS {{ $pkg->ris_form_number ?? '—' }}
                                    </span>
                                    <span>
                                        <i data-lucide="git-branch" class="h-3 w-3"></i>
                                        {{ ProcurementPaymentPath::label($pkg->package_payment_path) }}
                                    </span>
                                    <span>
                                        <i data-lucide="calendar" class="h-3 w-3"></i>
                                        {{ optional(\Carbon\Carbon::parse($pkg->package_submitted_to_accounting_at))->format('M d, Y') ?? '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($packages->hasPages())
                    <div class="border-t border-slate-100 px-5 py-3">{{ $packages->links() }}</div>
                @endif
            </div>
        </section>
    @endif

    <section class="pr-section">
        <div class="pr-surface h-full">
            <div class="pr-section-head">
                <h2 class="pr-section-title">Ready to compile</h2>
                <p class="pr-section-sub">Approved ATPs with a chosen payment path.</p>
            </div>

            @if($eligibleAtps->isNotEmpty())
                <div class="pr-compile-list">
                    @foreach($eligibleAtps as $atp)
                        @php
                            $atpId = (int) $atp->authority_purchase_id;
                            $checklist = $checklists[$atpId] ?? [];
                            $items = $checklist['items'] ?? [];
                            $complete = (bool) ($checklist['complete'] ?? false);
                            $alreadySubmitted = in_array($atpId, $existingAtpIds ?? [], true);
                            $readyCount = collect($items)->where('ready', true)->count();
                            $totalCount = count($items);
                            $progress = $totalCount > 0 ? round(($readyCount / $totalCount) * 100) : 0;
                        @endphp
                        <div class="pr-compile-card" id="atp-record-{{ $atpId }}">
                            <div class="pr-compile-head">
                                <div>
                                    <p class="pr-compile-ref">{{ $atp->authority_purchase_form_number }}</p>
                                    <p class="pr-compile-meta">
                                        RIS {{ $atp->ris_form_number ?? '—' }}
                                        · {{ ProcurementPaymentPath::label($atp->authority_purchase_payment_path) }}
                                    </p>
                                </div>

                                @if($complete && !$alreadySubmitted)
                                    <form method="POST" action="{{ route('purchaser.procurement-records.store') }}" onsubmit="return confirm('Submit compiled record to Accounting?')">
                                        @csrf
                                        <input type="hidden" name="package_authority_purchase_id" value="{{ $atpId }}">
                                        <button type="submit" class="pr-submit-btn">
                                            <i data-lucide="send"></i>
                                            Submit to Accounting
                                        </button>
                                    </form>
                                @elseif($alreadySubmitted)
                                    <span class="pr-badge pr-badge--green">Already submitted</span>
                                @else
                                    <span class="pr-badge pr-badge--amber">Workflow incomplete</span>
                                @endif
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3">
                                <div class="pr-progress flex-1" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="pr-progress-bar" style="width: {{ $progress }}%"></div>
                                </div>
                                <span class="pr-checklist-count">{{ $readyCount }}/{{ $totalCount }}</span>
                            </div>

                            <div class="pr-inline-checklist">
                                @foreach($items as $item)
                                    @php
                                        $itemKey = $item['key'] ?? '';
                                        $url = ProcurementRecordCompiler::purchaserUrlForItem($item);
                                        $supportingLinks = $itemKey === 'supporting_docs'
                                            ? ProcurementRecordCompiler::supportingDocLinksForAtp($atpId)
                                            : [];
                                    @endphp
                                    @if($itemKey === 'supporting_docs' && $supportingLinks !== [])
                                        <div class="space-y-1.5">
                                            <span class="pr-inline-check {{ !empty($item['ready']) ? 'is-ready' : '' }}">
                                                <span class="pr-check-dot" aria-hidden="true">
                                                    <i data-lucide="{{ !empty($item['ready']) ? 'check' : 'minus' }}"></i>
                                                </span>
                                                {{ $item['label'] }}
                                            </span>
                                            <div class="ml-6 flex flex-wrap gap-1.5">
                                                @foreach($supportingLinks as $link)
                                                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="pr-doc-btn">
                                                        <i data-lucide="paperclip"></i>
                                                        {{ $link['label'] }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @elseif($url)
                                        <a href="{{ $url }}" class="pr-inline-check {{ !empty($item['ready']) ? 'is-ready' : '' }}">
                                            <span class="pr-check-dot" aria-hidden="true">
                                                <i data-lucide="{{ !empty($item['ready']) ? 'check' : 'minus' }}"></i>
                                            </span>
                                            {{ $item['label'] }}
                                        </a>
                                    @else
                                        <span class="pr-inline-check {{ !empty($item['ready']) ? 'is-ready' : '' }}">
                                            <span class="pr-check-dot" aria-hidden="true">
                                                <i data-lucide="{{ !empty($item['ready']) ? 'check' : 'minus' }}"></i>
                                            </span>
                                            {{ $item['label'] }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="pr-empty">
                    <div class="pr-empty-icon">
                        <i data-lucide="folder-open"></i>
                    </div>
                    <p class="pr-empty-title">No ATPs ready yet</p>
                    <p class="pr-empty-sub">Approved ATPs with a payment path will appear here.</p>
                </div>
            @endif
        </div>
    </section>
    </div>
</div>
@endsection
