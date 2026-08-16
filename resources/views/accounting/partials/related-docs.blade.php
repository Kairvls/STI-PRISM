<div class="acc-panel">
    <h3 class="acc-panel-title">Related documents</h3>
    <p class="acc-panel-sub">Transaction chain</p>
    <ol class="mt-3 space-y-1.5">
        @foreach (['ris' => 'RIS', 'atp' => 'ATP', 'rfc' => 'Request Check', 'funds' => 'Funds', 'rr' => 'Receiving Report', 'liq' => 'Liquidation'] as $key => $title)
            @php $node = $chain[$key] ?? null; @endphp
            <li class="acc-chain-item">
                <div class="min-w-0">
                    <p>{{ $title }}</p>
                    <p title="{{ $node['label'] ?? '—' }}">{{ $node['label'] ?? '—' }}</p>
                </div>
                @if (!empty($node['url']))
                    <a href="{{ $node['url'] }}" class="acc-link shrink-0 text-[11px]">Open</a>
                @elseif (!empty($node['status']))
                    <span class="shrink-0 text-[10px] text-slate-500">{{ $node['status'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</div>
