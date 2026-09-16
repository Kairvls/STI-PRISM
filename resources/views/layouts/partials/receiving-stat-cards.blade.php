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
            class="receiving-filter-card group rounded-2xl border bg-white p-5 text-left shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition hover:border-slate-300 hover:shadow-sm
                {{ $current === $card['filter'] ? 'border-[#0025cc]/60 ring-2 ring-[#0025cc]/15' : 'border-slate-200' }}"
        >
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                @if ($current === $card['filter'])
                    <span data-ro-active-dot class="mt-0.5 h-2 w-2 shrink-0 rounded-full bg-[#0025cc]"></span>
                @endif
            </div>
            <p class="mt-4 text-3xl font-semibold tracking-tight {{ $card['color'] ?? 'text-slate-950' }}">{{ $card['count'] }}</p>
        </button>
    @endforeach
</div>
@endif
