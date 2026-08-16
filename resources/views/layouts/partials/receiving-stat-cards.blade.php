@php
    $cards = $cards ?? [];
    $current = $current ?? 'all';
@endphp

@if(count($cards))
<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    @foreach ($cards as $card)
        <button
            type="button"
            data-filter="{{ $card['filter'] }}"
            title="{{ $card['title'] ?? $card['label'] }}"
            aria-pressed="{{ $current === $card['filter'] ? 'true' : 'false' }}"
            class="receiving-filter-card rounded-[18px] border bg-white px-5 py-5 text-left shadow-[0_1px_2px_rgba(15,23,42,0.03)] transition
                {{ $current === $card['filter'] ? 'border-slate-900/20 ring-2 ring-slate-900/10' : 'border-gray-200 hover:border-gray-300' }}"
        >
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $card['label'] }}</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold {{ $card['color'] ?? 'text-slate-900' }}">{{ $card['count'] }}</p>
        </button>
    @endforeach
</div>
@endif
