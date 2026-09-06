@php
    $value = trim((string) ($value ?? ''));
    $imgClass = $imgClass ?? 'pointer-events-none absolute bottom-1 left-1/2 max-h-12 w-auto max-w-[92%] -translate-x-1/2 object-contain';
    $printedName = trim((string) ($printedName ?? ''));
    $isDrawn = \App\Support\RisWorkflow::isDrawnSignature($value);
    $lineText = $isDrawn
        ? ($printedName !== '' ? $printedName : ($emptyName ?? ''))
        : ($value !== '' ? $value : ($empty ?? ''));
@endphp
@if ($isDrawn)
    <span class="relative inline-flex min-h-[1.75rem] w-full items-end justify-center">
        <img src="{{ $value }}" alt="Signature" class="{{ $imgClass }}" style="-webkit-print-color-adjust: exact; print-color-adjust: exact;">
        @if ($lineText !== '')
            <span class="relative z-[1] text-center text-xs font-medium">{{ $lineText }}</span>
        @endif
    </span>
@else
    {{ $lineText }}
@endif
