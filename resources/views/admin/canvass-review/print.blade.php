<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authority to Purchase &amp; RIS — {{ \App\Support\CanvassWorkflow::displayNumber($canvass) }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 24px; background: #f8fafc; }
        .no-print { margin-bottom: 18px; display: flex; justify-content: flex-end; gap: 8px; }
        .btn { padding: 8px 14px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; cursor: pointer; font-weight: 600; font-size: 13px; }
        .btn-primary { background: #0d6efd; border-color: #0d6efd; color: #fff; }
        .sheet { background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 28px; margin-bottom: 24px; page-break-after: always; }
        .sheet:last-of-type { page-break-after: auto; }
        .hdr { text-align: center; }
        .hdr h1 { margin: 0; font-size: 17px; }
        .hdr h2 { margin: 4px 0 0; font-size: 12px; color: #475569; letter-spacing: .04em; text-transform: uppercase; }
        .hdr .sup { margin-top: 6px; font-size: 13px; font-weight: 700; }
        .meta { display: flex; justify-content: space-between; margin-top: 16px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 9px; text-align: left; }
        th { background: #f1f5f9; font-weight: 700; }
        .sigs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; margin-top: 32px; font-size: 12px; }
        .sig-line { height: 50px; border-bottom: 1px solid #334155; display: flex; align-items: flex-end; justify-content: center; padding-bottom: 4px; }
        .sig-line img { max-height: 44px; max-width: 100%; object-fit: contain; }
        .sig-lbl { margin-top: 4px; font-weight: 600; color: #334155; }
        .sig-date { margin-top: 6px; color: #64748b; }
        @media print {
            body { margin: 0; background: #fff; }
            .no-print { display: none; }
            .sheet { border: 0; border-radius: 0; padding: 16px; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn" onclick="window.close()">Close</button>
        <button class="btn btn-primary" onclick="window.print()">Print</button>
    </div>

    @php
        $displayNo = \App\Support\CanvassWorkflow::displayNumber($canvass);
        $submittedAt = $canvass->canvass_submitted_at ? \Carbon\Carbon::parse($canvass->canvass_submitted_at)->format('Y-m-d H:i:s') : '—';
        $bySupplier = [];
        foreach ($items as $it) {
            foreach ($it->comparison_offers as $o) {
                if (! $o->is_selected) continue;
                $sid = (int) $o->supplier_id;
                $bySupplier[$sid]['name'] = $o->supplier_display_name;
                $bySupplier[$sid]['lines'][] = [
                    'item' => $it->canvass_item_name ?: $it->catalog_item_name,
                    'requested' => $it->canvass_item_quantity.' '.($it->uom_name ?: 'Pc'),
                    'brand' => $o->brand_name ?: '—',
                    'qty' => (int) ($o->approved_offer_units ?? $o->computed_offer_units).' '.($o->offer_uom_name ?: 'Pc'),
                    'unitCost' => (float) $o->offer_unit_price,
                    'amount' => (float) ($o->approved_amount ?? $o->computed_total),
                    'status' => $it->canvass_item_status,
                ];
            }
        }
    @endphp

    @foreach ($bySupplier as $supGroup)
        <div class="sheet">
            <div class="hdr">
                <h1>STI COLLEGE - ORMOC, INC.</h1>
                <h2>AUTHORITY TO PURCHASE</h2>
                <div class="sup">Supplier: {{ $supGroup['name'] }}</div>
            </div>
            <div class="meta">
                <div>
                    <div><strong>Request No.:</strong> {{ $displayNo }}</div>
                    <div><strong>Date submitted:</strong> {{ $submittedAt }}</div>
                </div>
                <div><strong>Status:</strong> {{ $canvass->canvass_status }}</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Requested</th>
                        <th>Approved brand</th>
                        <th>Approved qty</th>
                        <th>Unit cost</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($supGroup['lines'] as $line)
                        <tr>
                            <td>{{ $line['item'] }}</td>
                            <td>{{ $line['requested'] }}</td>
                            <td>{{ $line['brand'] }}</td>
                            <td>{{ $line['qty'] }}</td>
                            <td>₱{{ number_format($line['unitCost'], 2) }}</td>
                            <td>₱{{ number_format($line['amount'], 2) }}</td>
                            <td>{{ $line['status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="sigs">
                <div>
                    <div class="sig-line">
                        @if (! empty($canvass->canvass_purchaser_signature))
                            <img src="{{ $canvass->canvass_purchaser_signature }}" alt="Requested by">
                        @endif
                    </div>
                    <div class="sig-lbl">Requested by</div>
                    <div class="sig-date">Date: ______________________</div>
                </div>
                <div>
                    <div class="sig-line">
                        @if (! empty($canvass->canvass_admin_signature))
                            <img src="{{ $canvass->canvass_admin_signature }}" alt="Authorized by">
                        @endif
                    </div>
                    <div class="sig-lbl">Authorized by</div>
                    <div class="sig-date">Date: ______________________</div>
                </div>
                <div>
                    <div class="sig-line"></div>
                    <div class="sig-lbl">Received by (Supplier)</div>
                    <div class="sig-date">Date: ______________________</div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Shared Requisition and Issue Slip --}}
    <div class="sheet">
        <div class="hdr">
            <h1>STI COLLEGE - ORMOC, INC.</h1>
            <h2>REQUISITION AND ISSUE SLIP</h2>
        </div>
        <div class="meta">
            <div><strong>Request No.:</strong> {{ $displayNo }}</div>
            <div><strong>Status:</strong> {{ $canvass->canvass_status }}</div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Requested item</th>
                    <th>Quantity</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $it)
                    <tr>
                        <td>{{ $it->canvass_item_name ?: $it->catalog_item_name }}</td>
                        <td>{{ $it->canvass_item_quantity }} {{ $it->uom_name ?: 'Pc' }}</td>
                        <td>{{ $it->canvass_item_status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="sigs">
            <div>
                <div class="sig-line">
                    @if (! empty($canvass->canvass_purchaser_signature))
                        <img src="{{ $canvass->canvass_purchaser_signature }}" alt="Requested by">
                    @endif
                </div>
                <div class="sig-lbl">Requested by</div>
                <div class="sig-date">Date: ______________________</div>
            </div>
            <div>
                <div class="sig-line">
                    @if (! empty($canvass->canvass_admin_signature))
                        <img src="{{ $canvass->canvass_admin_signature }}" alt="Approved by">
                    @endif
                </div>
                <div class="sig-lbl">Approved by</div>
                <div class="sig-date">Date: ______________________</div>
            </div>
            <div>
                <div class="sig-line">
                    @if (! empty($canvass->canvass_admin_signature) && $canvass->canvass_status === 'Approved')
                        <img src="{{ $canvass->canvass_admin_signature }}" alt="Issued by">
                    @endif
                </div>
                <div class="sig-lbl">Issued by</div>
                <div class="sig-date">Date: ______________________</div>
            </div>
        </div>
    </div>
</body>
</html>
