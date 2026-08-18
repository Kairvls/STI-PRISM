@php
    $value = trim((string) ($value ?? ''));
    $imgClass = $imgClass ?? 'mx-auto h-12 w-auto max-w-full object-contain';
@endphp
@if(\App\Support\RisWorkflow::isDrawnSignature($value))
    <img src="{{ $value }}" alt="Signature" class="{{ $imgClass }}" style="-webkit-print-color-adjust: exact; print-color-adjust: exact;">
@else
    {{ $value !== '' ? $value : ($empty ?? '') }}
@endif
