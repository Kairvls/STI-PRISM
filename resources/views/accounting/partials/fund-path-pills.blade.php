@if(!empty($parts))
    <p class="mt-1.5 flex flex-wrap gap-1.5">
        @foreach($parts as $part)
            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-[#15803d]">
                {{ $part['label'] }}
                <span class="font-medium tabular-nums">₱{{ number_format((float) ($part['amount'] ?? 0), 2) }}</span>
            </span>
        @endforeach
    </p>
@endif
