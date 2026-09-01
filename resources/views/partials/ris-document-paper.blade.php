@php
    $presidentSigned = trim((string) ($ris->ris_approved_by_signature ?? '')) !== '';
    $adminIssued = trim((string) ($ris->ris_issued_by_signature ?? '')) !== '';
@endphp

<div class="ris-document">
    @if (!empty($isScreenPreview) ? $presidentSigned : ($presidentSigned && $adminIssued))
        @include('partials.ris-approval-watermark', ['watermarkLabel' => 'APPROVED'])
    @endif

    <section class="header">
        <div class="school">STI COLLEGE- ORMOC, INC.</div>
        <div class="title">REQUISITION AND ISSUE SLIP</div>
        <div class="number">
            No.
            <span class="line">{{ $ris->ris_form_number ?? $ris->ris_id }}</span>
        </div>
    </section>

    <table class="ris-table">
        <thead>
            <tr>
                <th rowspan="2" class="item-col">ITEM</th>
                <th rowspan="2">SUPPLIER</th>
                <th colspan="2">QUANTITY</th>
                <th rowspan="2" class="cost-col">UNIT COST</th>
                <th rowspan="2" class="amount-col">AMOUNT</th>
            </tr>
            <tr>
                <th class="qty-col">REQUESTED</th>
                <th class="qty-col">ISSUED</th>
            </tr>
        </thead>
        <tbody>
            @for($row = 0; $row < 10; $row++)
                @php($item = $risItems[$row] ?? null)
                <tr>
                    <td>
                        {{ $item?->ris_item_name_description ?? '' }}
                        @if(!empty($item?->uom_name)) ({{ $item->uom_name }})@endif
                    </td>
                    <td>{{ $item?->supplier_display_name ?? '' }}</td>
                    <td style="text-align: center;">{{ $item?->ris_quantity_requested ?? '' }}</td>
                    <td style="text-align: center;">{{ $item?->ris_quantity_issued ?? '' }}</td>
                    <td style="text-align: right;">{{ $item && $item->ris_unit_cost !== null ? number_format($item->ris_unit_cost, 2) : '' }}</td>
                    <td style="text-align: right;">{{ $item && $item->ris_total_amount !== null ? number_format($item->ris_total_amount, 2) : '' }}</td>
                </tr>
            @endfor
        </tbody>
    </table>

    <section class="purpose">
        <div>PURPOSE</div>
        <div class="purpose-lines">{{ $ris->ris_purpose_description }}</div>
    </section>

    <section class="signatures">
        <div class="signature-box">
            <p>Requested by:</p>
            <div class="signature-line">{{ $ris->ris_requested_by_signature }}</div>
            <div class="date-row"><span>Date:</span><div class="signature-line">{{ $ris->ris_requested_by_date }}</div></div>
        </div>
        <div class="signature-box">
            <p>Approved by:</p>
            <div class="signature-line">
                @if (!empty($ris->ris_approved_by_signature) && strpos($ris->ris_approved_by_signature, 'data:image') === 0)
                    <img src="{{ $ris->ris_approved_by_signature }}" alt="Approved by signature" class="signature-image" />
                    <span class="signature-name">{{ $presidentName ?? ($ris->ris_approved_by_name ?? 'President') }}</span>
                @elseif (!empty(trim((string) ($ris->ris_approved_by_signature ?? ''))))
                    <span class="signature-name">{{ $ris->ris_approved_by_signature }}</span>
                @else
                    <span class="signature-name">{{ $ris->ris_approved_by_name ?? '' }}</span>
                @endif
            </div>
            <div class="date-row"><span>Date:</span><div class="signature-line">{{ $ris->ris_approved_by_date }}</div></div>
        </div>
        <div class="signature-box">
            <p>Issued by:</p>
            <div class="signature-line"></div>
            <div class="signature-name-wrapper">
                <div class="signature-name">{{ $ris->ris_issued_by_name ?? '' }}</div>
                <div class="signature-position">{{ $ris->ris_issued_by_position ?? '' }}</div>
                @if (!empty($ris->ris_issued_by_signature) && strpos($ris->ris_issued_by_signature, 'data:image/png;base64,') === 0)
                    <img src="{{ $ris->ris_issued_by_signature }}" alt="Issued by signature" class="signature-image" />
                @endif
            </div>
            <div class="date-row"><span>Date:</span><div class="signature-line">{{ $ris->ris_issued_by_date }}</div></div>
        </div>
        <div class="signature-box">
            <p>Received by:</p>
            <div class="signature-line">{{ $ris->ris_received_by_signature }}</div>
            <div class="date-row"><span>Date:</span><div class="signature-line">{{ $ris->ris_received_by_date }}</div></div>
        </div>
    </section>
</div>
