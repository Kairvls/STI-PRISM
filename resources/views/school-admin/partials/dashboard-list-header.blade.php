@php
    $shown = is_countable($rows ?? null) ? count($rows) : 0;
    $total = (int) ($total ?? 0);
@endphp
<div class="border-b border-gray-100 px-5 py-4">
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="flex min-w-0 items-center gap-2">
                <h3 class="truncate text-sm font-semibold text-gray-950">{{ $title }}</h3>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-gray-500">
                    {{ number_format($total) }}
                </span>
            </div>
            @if (!empty($hint))
                <p class="mt-0.5 text-xs text-gray-400">{{ $hint }}</p>
            @endif
        </div>
        <a href="{{ $url }}" class="shrink-0 text-xs font-semibold text-gray-400 transition hover:text-[#0025cc]">
            View all
        </a>
    </div>
    @if ($total > $shown && $shown > 0)
        <p class="mt-1 text-xs text-gray-500">{{ number_format($shown) }} latest</p>
    @endif
</div>
