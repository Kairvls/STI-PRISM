{{-- Shared Approved/Checked + Issued + Received columns for RIS viewers --}}
@php
    $approvedLabel = \App\Support\RisWorkflow::approvedByColumnLabel($ris);
    $approvedRaw = trim((string) ($ris->ris_approved_by_signature ?? ''));
    $approvedImage = \App\Support\RisWorkflow::isDrawnSignature($approvedRaw) ? $approvedRaw : '';
    $approvedName = \App\Support\RisWorkflow::approvedByPrintedName($ris);

    $issuedRaw = trim((string) ($ris->ris_issued_by_signature ?? ''));
    $issuedImage = \App\Support\RisWorkflow::isDrawnSignature($issuedRaw) ? $issuedRaw : '';
    $issuedName = \App\Support\RisWorkflow::issuedByPrintedName($ris);

    $receivedRaw = trim((string) ($ris->ris_received_by_signature ?? ''));
    $receivedImage = \App\Support\RisWorkflow::isDrawnSignature($receivedRaw) ? $receivedRaw : '';
    $receivedName = ($receivedImage === '' && $receivedRaw !== '') ? $receivedRaw : '';
@endphp

<div class="ris-signature-column">
    <div class="ris-signature-label">{{ $approvedLabel }}</div>
    <div class="ris-signature-line ris-value-line">
        @if ($approvedImage !== '')
            <img src="{{ $approvedImage }}" alt="{{ rtrim($approvedLabel, ':') }} signature" class="signature-image">
            @if ($approvedName !== '')
                <span class="signature-name">{{ $approvedName }}</span>
            @endif
        @elseif ($approvedName !== '')
            <span class="signature-name">{{ $approvedName }}</span>
        @else
            {{ ' ' }}
        @endif
    </div>
    <div class="ris-date-label">Date:</div>
    <div class="ris-date-line ris-value-line">
        {{ $ris->ris_approved_by_date ? \Carbon\Carbon::parse($ris->ris_approved_by_date)->format('d/m/Y') : ' ' }}
    </div>
</div>

<div class="ris-signature-column">
    <div class="ris-signature-label">Issued by:</div>
    <div class="ris-signature-line ris-value-line">
        @if ($issuedImage !== '')
            <img src="{{ $issuedImage }}" alt="Issued by signature" class="signature-image">
            @if ($issuedName !== '')
                <span class="signature-name">{{ $issuedName }}</span>
            @endif
        @elseif ($issuedName !== '')
            <span class="signature-name">{{ $issuedName }}</span>
        @else
            {{ ' ' }}
        @endif
    </div>
    <div class="ris-date-label">Date:</div>
    <div class="ris-date-line ris-value-line">
        {{ $ris->ris_issued_by_date ? \Carbon\Carbon::parse($ris->ris_issued_by_date)->format('d/m/Y') : ' ' }}
    </div>
</div>

<div class="ris-signature-column">
    <div class="ris-signature-label">Received by:</div>
    <div class="ris-signature-line ris-value-line">
        @if ($receivedImage !== '')
            <img src="{{ $receivedImage }}" alt="Received by signature" class="signature-image">
            @if ($receivedName !== '')
                <span class="signature-name">{{ $receivedName }}</span>
            @endif
        @elseif ($receivedName !== '')
            <span class="signature-name">{{ $receivedName }}</span>
        @else
            {{ ' ' }}
        @endif
    </div>
    <div class="ris-date-label">Date:</div>
    <div class="ris-date-line ris-value-line">
        {{ $ris->ris_received_by_date ? \Carbon\Carbon::parse($ris->ris_received_by_date)->format('d/m/Y') : ' ' }}
    </div>
</div>
