@php
    $current = $current ?? null;
@endphp
<div class="acc-panel">
    <h3 class="acc-panel-title">Related documents</h3>
    <p class="acc-panel-sub">Transaction chain</p>
    <ol class="mt-3 space-y-1.5">
        @foreach (['ris' => 'RIS', 'atp' => 'ATP', 'rfc' => 'Request Check', 'funds' => 'Funds', 'rr' => 'Receiving Report', 'liq' => 'Liquidation'] as $key => $title)
            @php
                $node = $chain[$key] ?? null;
                $isCurrent = $current !== null && $current === $key;
            @endphp
            <li class="acc-chain-item {{ $isCurrent ? 'acc-chain-item--current' : '' }}">
                <div class="min-w-0">
                    <p>{{ $title }}</p>
                    <p title="{{ $node['label'] ?? '—' }}">{{ $node['label'] ?? '—' }}</p>
                </div>
                <div class="shrink-0 flex flex-col items-end gap-0.5">
                    @if ($isCurrent && !empty($node['url']))
                        <span class="text-[11px] font-medium text-slate-400 cursor-default select-none" title="You are viewing this document">Current</span>
                    @elseif (!empty($node['url']))
                        <a href="{{ $node['url'] }}" class="acc-link text-[11px]">Open</a>
                    @endif
                    @if (!empty($node['status']) && empty($node['url']))
                        <span class="text-[10px] text-slate-500">{{ $node['status'] }}</span>
                    @elseif (!empty($node['status']) && !empty($node['url']) && $key === 'ris')
                        <span class="text-[10px] text-slate-500">{{ $node['status'] }}</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</div>
