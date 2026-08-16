<div class="rounded-xl border border-gray-200 bg-white p-5">
    <h3 class="text-sm font-bold text-gray-900">Related documents</h3>
    <p class="mt-0.5 text-xs text-gray-400">Transaction chain</p>
    <ol class="mt-4 space-y-2">
        @foreach (['ris' => 'RIS', 'atp' => 'ATP', 'rfc' => 'Request Check', 'funds' => 'Funds', 'rr' => 'Receiving Report', 'liq' => 'Liquidation'] as $key => $title)
            @php $node = $chain[$key] ?? null; @endphp
            <li class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $title }}</p>
                    <p class="truncate text-sm font-medium text-gray-900">{{ $node['label'] ?? '—' }}</p>
                </div>
                @if (!empty($node['url']))
                    <a href="{{ $node['url'] }}" class="shrink-0 text-xs font-semibold text-gray-900 hover:text-amber-600">Open</a>
                @elseif (!empty($node['status']))
                    <span class="shrink-0 text-[11px] text-gray-500">{{ $node['status'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</div>
