@php
    $requestedName = \App\Support\RisWorkflow::requestedByPrintedName($ris ?? null);
    $requestedImage = \App\Support\RisWorkflow::requestedByDrawnSignature($ris ?? null);
    $lineClass = $lineClass ?? 'signature-line';
    $nameClass = $nameClass ?? 'signature-name';
    $imageClass = $imageClass ?? 'signature-image';
@endphp
<div class="{{ $lineClass }}">
    <span class="signature-name-stack">
        @if ($requestedImage !== '')
            <img src="{{ $requestedImage }}" alt="Requested by signature" class="{{ $imageClass }}" />
        @endif
        @if ($requestedName !== '')
            <span class="{{ $nameClass }}">{{ $requestedName }}</span>
        @endif
    </span>
</div>
