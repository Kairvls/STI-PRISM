@php
    $labelClass = $labelClass ?? 'w-[13.5rem] shrink-0 leading-snug';
    $type = $type ?? 'text';
    $nameKey = (string) ($name ?? '');
    $textMax = preg_match('/(purpose|note|remark|description|particular)/i', $nameKey)
        ? \App\Support\InputLimits::NOTES
        : \App\Support\InputLimits::NAME;
    $attrs = trim((string) ($attrs ?? ''));
    $readonly = ! empty($readonly);
@endphp
<div class="flex items-end gap-3 py-1.5">
    <span class="{{ $labelClass }}">{{ $label }}: @if($editable && ($required ?? false))<span class="text-red-500">*</span>@endif</span>
    @if($editable)
        <div class="flex min-h-[2rem] flex-1 flex-col">
            <input
                type="{{ $type }}"
                @if($type === 'number') step="0.01" min="0" max="{{ \App\Support\InputLimits::COST }}" inputmode="decimal" data-money @endif
                @if(in_array($type, ['text', 'search', 'email', 'tel', ''], true)) maxlength="{{ $textMax }}" @endif
                name="{{ $name }}"
                value="{{ $value }}"
                @if($readonly) readonly tabindex="-1" @endif
                {!! $attrs !!}
                class="h-8 min-h-[2rem] w-full border-0 border-b border-black bg-transparent pb-0.5 outline-none {{ $readonly ? 'cursor-default text-slate-600' : '' }}"
            >
            @if(! empty($hint))
                <span class="pt-0.5 text-[10px] leading-tight text-slate-500">{{ $hint }}</span>
            @endif
        </div>
    @else
        <span class="min-h-[2rem] flex-1 border-b border-black pb-0.5 leading-8" {!! $attrs !!}>
            @if($type === 'date' && $value)
                {{ \Carbon\Carbon::parse($value)->format('m/d/Y') }}
            @elseif($type === 'number' && $value !== '' && $value !== null)
                {{ number_format((float) $value, 2) }}
            @else
                {{ $value }}
            @endif
        </span>
    @endif
</div>
