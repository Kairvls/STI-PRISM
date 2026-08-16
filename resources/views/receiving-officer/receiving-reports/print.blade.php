<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receiving Report {{ $row->receiving_report_form_number ?: ($row->ris_form_number ?: ($row->authority_purchase_form_number ?: '#'.$row->receiving_report_id)) }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 32px; }
        .bar { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .muted { color: #555; font-size: 12px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 8px 10px; font-size: 13px; text-align: left; }
        th { background: #f3f4f6; }
        .meta { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .meta td { border: none; padding: 4px 0; }
        .actions { margin-bottom: 20px; }
        .btn { display: inline-block; padding: 8px 14px; background: #0037c7; color: #fff; text-decoration: none; border-radius: 8px; font-size: 13px; border: 0; cursor: pointer; }
        .btn-ghost { background: #fff; color: #111; border: 1px solid #ccc; margin-left: 8px; }
        ul { margin: 8px 0 0; padding-left: 18px; font-size: 13px; }
        @media print { .actions { display: none; } body { margin: 12mm; } }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" class="btn" onclick="window.print()">Print</button>
        <a class="btn btn-ghost" href="/receiving/delivered-items">Back to delivered items</a>
    </div>

    @if(session('success'))
        <p class="muted">{{ session('success') }}</p>
    @endif

    <div class="bar">
        <div>
            <h1>Receiving Report</h1>
            <p class="muted">STI College Ormoc · Receiving Officer</p>
        </div>
        <div class="muted" style="text-align:right;">
            Printed {{ now()->format('M d, Y g:i A') }}
        </div>
    </div>

    <table class="meta">
        <tr><td width="180"><strong>RR No.</strong></td><td>{{ $row->receiving_report_form_number ?: 'RR-'.$row->receiving_report_id }}</td></tr>
        <tr><td><strong>ATP</strong></td><td>{{ $row->authority_purchase_form_number ?: '—' }}</td></tr>
        <tr><td><strong>RIS</strong></td><td>{{ $row->ris_form_number ?: '—' }}</td></tr>
        <tr><td><strong>Status</strong></td><td>{{ in_array($row->receiving_report_status, ['Accepted', 'Completed'], true) ? 'Accepted' : $row->receiving_report_status }}</td></tr>
        <tr><td><strong>Supplier</strong></td><td>{{ $row->supplier_name }}</td></tr>
        <tr><td><strong>PO / OR</strong></td><td>{{ $row->authority_purchase_reference_po_no ?: '—' }} / {{ $row->official_receipt ?: '—' }}</td></tr>
        <tr><td><strong>Received by</strong></td><td>{{ $officerName }}</td></tr>
        <tr><td><strong>Date</strong></td><td>{{ $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('F d, Y g:i A') : '—' }}</td></tr>
        @if(!empty($row->receiving_report_remarks))
            <tr><td><strong>Remarks</strong></td><td>{{ $row->receiving_report_remarks }}</td></tr>
        @endif
    </table>

    <h2 style="font-size:15px;margin:24px 0 0;">Line items</h2>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>{{ $item->receiving_report_item_article ?? $item->atp_description ?? '—' }}</td>
                    <td>{{ $item->receiving_report_item_quantity ?? $item->atp_quantity ?? '—' }}</td>
                    <td>{{ $item->receiving_report_item_unit ?? $item->atp_unit ?? '—' }}</td>
                    <td>₱{{ number_format((float) ($item->receiving_report_item_amount ?? $item->atp_amount ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No line items recorded.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if(count($checklist))
        <h2 style="font-size:15px;margin:24px 0 0;">Physical validation checklist</h2>
        <ul>
            @foreach($checklist as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endif
</body>
</html>
