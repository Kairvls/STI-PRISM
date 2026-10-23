{{-- Shared Request No. (ticket) for one procurement transaction --}}
@php
    $requestNo = trim((string) ($requestNo ?? ''));
    $size = $size ?? 'sm'; // sm | md
    $showHint = (bool) ($showHint ?? false);
    $inline = (bool) ($inline ?? false);
    $showLabel = (bool) ($showLabel ?? true);
    $link = (bool) ($link ?? true);
    $href = null;
    if ($link && $requestNo !== '') {
        try {
            $href = \App\Support\RequestExplorer::showUrl($requestNo);
        } catch (\Throwable) {
            $href = null;
        }
    }
    $badgeClass = 'rounded-md border border-indigo-200 bg-indigo-50 px-1.5 py-0.5 font-semibold tabular-nums text-indigo-800 transition';
    if ($href) {
        $badgeClass .= ' hover:border-indigo-300 hover:bg-indigo-100 hover:underline';
    }
@endphp
@if($requestNo !== '')
    @if($inline)
        <span class="inline-flex items-center gap-1.5 {{ $size === 'md' ? 'text-sm' : 'text-xs' }}" title="{{ \App\Support\ProcurementRequestNumber::uiHint() }}">
            @if($showLabel)
                <span class="font-medium text-slate-500">Request No.</span>
            @endif
            @if($href)
                <a href="{{ $href }}" class="{{ $badgeClass }}">{{ $requestNo }}</a>
            @else
                <span class="{{ $badgeClass }}">{{ $requestNo }}</span>
            @endif
        </span>
    @else
        <div class="{{ $size === 'md' ? 'text-sm' : 'text-xs' }}" title="{{ \App\Support\ProcurementRequestNumber::uiHint() }}">
            @if($showLabel)
                <span class="font-medium text-slate-500">Request No.</span>
            @endif
            @if($href)
                <a href="{{ $href }}" class="{{ $showLabel ? 'ml-1 ' : '' }}{{ $badgeClass }}">{{ $requestNo }}</a>
            @else
                <span class="{{ $showLabel ? 'ml-1 ' : '' }}{{ $badgeClass }}">{{ $requestNo }}</span>
            @endif
            @if($showHint)
                <p class="mt-1 text-[11px] leading-4 text-slate-400">Same ticket follows Canvas + RIS, ATP, RFC, DV, RR, and Liquidation for this purchase. Click to open the pipeline.</p>
            @endif
        </div>
    @endif
@endif
