{{--
  Tiled diagonal APPROVED watermark for on-screen RIS preview only.
  Hidden when printing (@media print).
  Optional: $watermarkLabel (default: APPROVED)
--}}
@php
    $watermarkLabel = strtoupper((string) ($watermarkLabel ?? 'APPROVED'));
    $wmRows = 16;
    $wmCols = 10;
@endphp

@once
<style>
    .approval-watermark {
        position: absolute;
        inset: 0;
        z-index: 40;
        overflow: hidden;
        pointer-events: none;
        user-select: none;
        -webkit-user-select: none;
    }
    .approval-watermark-pattern {
        position: absolute;
        left: 50%;
        top: 50%;
        width: 260%;
        height: 260%;
        display: flex;
        flex-direction: column;
        justify-content: space-evenly;
        gap: 1.75rem;
        transform: translate(-50%, -50%) rotate(-32deg);
    }
    .approval-watermark-row {
        display: flex;
        flex-wrap: nowrap;
        justify-content: space-evenly;
        gap: 2rem;
        white-space: nowrap;
    }
    .approval-watermark-row:nth-child(even) {
        transform: translateX(5.5rem);
    }
    .approval-watermark-row span {
        flex-shrink: 0;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 28px;
        font-weight: 800;
        letter-spacing: 0.14em;
        line-height: 1;
        color: rgba(15, 23, 42, 0.14);
        text-transform: uppercase;
    }
    html.screen-preview .approval-watermark {
        animation: approvalWatermarkIn 0.55s ease both;
    }
    @keyframes approvalWatermarkIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @media print {
        .approval-watermark {
            display: none !important;
        }
    }
</style>
@endonce

<div class="approval-watermark" aria-hidden="true">
    <div class="approval-watermark-pattern">
        @for ($r = 0; $r < $wmRows; $r++)
            <div class="approval-watermark-row">
                @for ($c = 0; $c < $wmCols; $c++)
                    <span>{{ $watermarkLabel }}</span>
                @endfor
            </div>
        @endfor
    </div>
</div>
