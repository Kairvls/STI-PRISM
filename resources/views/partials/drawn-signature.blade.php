@php
    $value = trim((string) ($value ?? ''));
    $imgClass = $imgClass ?? 'signature-image pointer-events-none absolute bottom-2 left-1/2 z-[2] max-h-10 w-auto max-w-[92%] -translate-x-1/2 object-contain';
    $printedName = trim((string) ($printedName ?? ''));
    $isDrawn = \App\Support\RisWorkflow::isDrawnSignature($value);
    $lineText = $isDrawn
        ? ($printedName !== '' ? $printedName : ($emptyName ?? ''))
        : ($value !== '' ? $value : ($empty ?? ''));
@endphp
@once
    @include('partials.ris-signature-overlay-styles')
@endonce
@if ($isDrawn)
    <span class="relative inline-flex min-h-[2.5rem] w-full items-end justify-center">
        <img src="{{ $value }}" alt="Signature" class="{{ $imgClass }}" style="-webkit-print-color-adjust: exact; print-color-adjust: exact;">
        @if ($lineText !== '')
            <span class="signature-name relative z-[1] text-center text-xs font-medium leading-5">{{ $lineText }}</span>
        @endif
    </span>
@else
    {{ $lineText }}
@endif
