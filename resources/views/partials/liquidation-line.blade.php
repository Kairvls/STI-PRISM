<div class="flex items-end gap-2">
    <span class="w-48 shrink-0">{{ $label }}:</span>
    @if($editable)
        <input
            type="{{ $type ?? 'text' }}"
            @if(($type ?? '') === 'number') step="0.01" @endif
            name="{{ $name }}"
            value="{{ $value }}"
            class="h-7 flex-1 border-0 border-b border-black bg-transparent outline-none"
        >
    @else
        <span class="flex-1 border-b border-black min-h-[1.25rem]">
            @if(($type ?? '') === 'date' && $value)
                {{ \Carbon\Carbon::parse($value)->format('m/d/Y') }}
            @elseif(($type ?? '') === 'number' && $value !== '' && $value !== null)
                {{ number_format((float) $value, 2) }}
            @else
                {{ $value }}
            @endif
        </span>
    @endif
</div>
