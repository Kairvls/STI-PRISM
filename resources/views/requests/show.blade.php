@extends($layout ?? 'layouts.purchaser-layout')

@section('title', 'Request '.$requestNo)
@section('page-title', 'Request '.$requestNo)
@section('page-subtitle', 'Pipeline and documents under this Request No.')

@section('content')
@php
    $explorer = $explorer ?? null;
    $stageOrder = $stageOrder ?? \App\Support\RequestExplorer::STAGE_ORDER;
    $stageLabels = $stageLabels ?? \App\Support\RequestExplorer::STAGE_LABELS;
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Request No.</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ $requestNo }}</h1>
            @php
                $pathHint = is_array($explorer) ? ($explorer['payment_path'] ?? null) : null;
                $subtitle = match (true) {
                    \App\Support\ProcurementPaymentPath::requiresLiquidation($pathHint)
                        => 'Cash Advance: Canvas + RIS → ATP → funding request → RR → Liquidation Report.',
                    \App\Support\ProcurementPaymentPath::endsAtReceivingReport($pathHint)
                        => \App\Support\ProcurementPaymentPath::label($pathHint).': Canvas + RIS → ATP → funding request → DV → Receiving Report.',
                    default
                        => 'Direct Payment / Cash Installment end at Receiving Report; Cash Advance ends at Liquidation Report.',
                };
            @endphp
            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
        </div>
        <a
            href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}"
            class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
        >Back</a>
    </div>

    @if(! $explorer)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-6 text-sm text-amber-900">
            No procurement documents were found for <strong class="tabular-nums">{{ $requestNo }}</strong>.
            Check the Request No. or open it from a form list that already shows this ticket.
        </div>
    @else
        @php
            $stages = $explorer['stages'] ?? [];
            $stageOrder = $explorer['stage_order']
                ?? \App\Support\RequestExplorer::visibleStageKeys($stages, $explorer['payment_path'] ?? null);
            $current = $explorer['current_stage'] ?? 'ris';
            $currentIndex = array_search($current, $stageOrder, true);
            if ($currentIndex === false) {
                $currentIndex = 0;
            }
        @endphp

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Now at</p>
                    <p class="mt-1 text-lg font-semibold text-slate-950">{{ $stageLabels[$current] ?? strtoupper($current) }}</p>
                    @if(! empty($explorer['current_hint']))
                        <p class="mt-1 text-sm text-slate-500">{{ $explorer['current_hint'] }}</p>
                    @endif
                </div>
                <div class="text-right text-sm text-slate-500">
                    @if(! empty($explorer['payment_path_label']))
                        <p>Payment: <span class="font-medium text-slate-700">{{ $explorer['payment_path_label'] }}</span></p>
                    @endif
                    @if(! empty($explorer['funds']['label']))
                        <p class="mt-1">Funds: <span class="font-medium text-slate-700">{{ $explorer['funds']['label'] }}</span></p>
                    @endif
                </div>
            </div>

            <div class="mt-6 overflow-x-auto">
                <div class="flex min-w-[640px] items-start gap-0">
                    @foreach($stageOrder as $index => $key)
                        @php
                            $stage = $stages[$key] ?? null;
                            $exists = ! empty($stage['exists']);
                            $isCurrent = $key === $current;
                            $isPast = $index < $currentIndex;
                        @endphp
                        <div class="flex flex-1 flex-col items-center">
                            <div class="flex w-full items-center">
                                @if($index > 0)
                                    <div class="h-0.5 flex-1 {{ $isPast || $isCurrent ? 'bg-[#0025cc]' : 'bg-gray-200' }}"></div>
                                @else
                                    <div class="flex-1"></div>
                                @endif
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold
                                        {{ $isCurrent ? 'bg-[#0025cc] text-white ring-4 ring-blue-100' : ($exists ? 'bg-[#0025cc]/10 text-[#0025cc]' : 'bg-gray-100 text-gray-400') }}"
                                    title="{{ $stageLabels[$key] ?? strtoupper($key) }}"
                                >
                                    {{ strtoupper($key) }}
                                </div>
                                @if($index < count($stageOrder) - 1)
                                    <div class="h-0.5 flex-1 {{ $isPast ? 'bg-[#0025cc]' : 'bg-gray-200' }}"></div>
                                @else
                                    <div class="flex-1"></div>
                                @endif
                            </div>
                            <p class="mt-2 text-center text-[11px] font-medium {{ $isCurrent ? 'text-[#0025cc]' : 'text-slate-500' }}">
                                {{ $stageLabels[$key] ?? strtoupper($key) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Documents in this request</h2>
                <p class="mt-0.5 text-xs text-slate-500">Open a form to view details in your portal.</p>
            </div>
            <ul class="divide-y divide-gray-100">
                @foreach($stageOrder as $key)
                    @php $stage = $stages[$key] ?? null; @endphp
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">{{ $stageLabels[$key] ?? strtoupper($key) }}</p>
                            @if(! empty($stage['exists']))
                                <p class="mt-0.5 text-sm text-slate-600">
                                    <span class="font-medium tabular-nums">{{ $stage['label'] }}</span>
                                    @if(! empty($stage['hint']))
                                        <span class="text-slate-400"> · {{ $stage['hint'] }}</span>
                                    @endif
                                </p>
                            @else
                                <p class="mt-0.5 text-sm text-slate-400">Not started yet</p>
                            @endif
                        </div>
                        @if(! empty($stage['exists']) && ! empty($stage['view_url']))
                            <a
                                href="{{ $stage['view_url'] }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-[#001db3]"
                            >Open</a>
                        @elseif(! empty($stage['exists']))
                            <span class="text-xs text-slate-400">No open link in this portal</span>
                        @else
                            <span class="inline-flex h-9 items-center rounded-lg border border-dashed border-gray-200 px-3 text-xs text-gray-400">Pending</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        @if(! empty($explorer['atp_siblings']))
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-950">Other ATPs on this RIS</h2>
                <p class="mt-0.5 text-xs text-slate-500">Multi-supplier RIS creates one ATP per supplier.</p>
                <ul class="mt-4 space-y-2">
                    @foreach($explorer['atp_siblings'] as $sibling)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-100 px-3 py-2.5 text-sm">
                            <div>
                                <span class="font-medium text-slate-800">{{ $sibling['label'] }}</span>
                                @if(! empty($sibling['hint']))
                                    <span class="text-slate-400"> · {{ $sibling['hint'] }}</span>
                                @endif
                            </div>
                            @if(! empty($sibling['view_url']))
                                <a
                                    href="{{ $sibling['view_url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-medium text-[#0025cc] hover:underline"
                                >Open</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</div>
@endsection
