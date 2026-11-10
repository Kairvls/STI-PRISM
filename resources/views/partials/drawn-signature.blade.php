@php
    $value = trim((string) ($value ?? ''));
    $imgClass = $imgClass ?? 'signature-image pointer-events-none absolute left-1/2 top-1/2 z-[10] max-h-[38px] w-auto max-w-[92%] -translate-x-1/2 -translate-y-1/2 object-contain object-center';
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
    {{-- Stack sized by the printed name (not the full underline) so left:50% centers on the name. --}}
    <span class="signature-name-stack relative inline-flex max-w-full items-center justify-center">
        <img src="{{ $value }}" alt="Signature" class="{{ $imgClass }}" style="-webkit-print-color-adjust: exact; print-color-adjust: exact;">
        @if ($lineText !== '')
            <span class="signature-name relative z-[1] whitespace-nowrap text-center text-xs font-medium leading-5">{{ $lineText }}</span>
        @endif
    </span>
@else
    {{ $lineText }}
@endif
