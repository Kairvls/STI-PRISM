@php
    $labelClass = $labelClass ?? 'w-[13.5rem] shrink-0 leading-snug';
    $type = $type ?? 'text';
@endphp
<div class="flex items-end gap-3 py-1.5">
    <span class="{{ $labelClass }}">{{ $label }}:</span>
    @if($editable)
        <input
            type="{{ $type }}"
            @if($type === 'number') step="0.01" @endif
            name="{{ $name }}"
            value="{{ $value }}"
            class="h-8 min-h-[2rem] flex-1 border-0 border-b border-black bg-transparent pb-0.5 outline-none"
        >
    @else
        <span class="min-h-[2rem] flex-1 border-b border-black pb-0.5 leading-8">
            @if($type === 'date' && $value)
                {{ \Carbon\Carbon::parse($value)->format('d/m/Y') }}
            @elseif($type === 'number' && $value !== '' && $value !== null)
                {{ number_format((float) $value, 2) }}
            @else
                {{ $value }}
            @endif
        </span>
    @endif
</div>
