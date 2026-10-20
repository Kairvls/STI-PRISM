@php
    $labelClass = $labelClass ?? 'w-[13.5rem] shrink-0 leading-snug';
    $type = $type ?? 'text';
    $nameKey = (string) ($name ?? '');
    $textMax = preg_match('/(purpose|note|remark|description|particular)/i', $nameKey)
        ? \App\Support\InputLimits::NOTES
        : \App\Support\InputLimits::NAME;
@endphp
<div class="flex items-end gap-3 py-1.5">
    <span class="{{ $labelClass }}">{{ $label }}: @if($editable && ($required ?? false))<span class="text-red-500">*</span>@endif</span>
    @if($editable)
        <input
            type="{{ $type }}"
            @if($type === 'number') step="0.01" min="0" max="{{ \App\Support\InputLimits::COST }}" @endif
            @if(in_array($type, ['text', 'search', 'email', 'tel', ''], true)) maxlength="{{ $textMax }}" @endif
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
