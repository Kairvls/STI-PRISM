<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Equipment Audit Pack #{{ $equipment['id'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 8px; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; letter-spacing: .03em; }
        .meta td { border: none; padding: 2px 0; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 999px; background: #e2e8f0; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Equipment Audit Pack</h1>
    <p class="muted">Generated {{ $generatedAt->format('Y-m-d H:i') }} · PaAyo Maintenance</p>

    <h2>Asset profile</h2>
    <table class="meta">
        <tr><td width="28%"><strong>Name</strong></td><td>{{ $equipment['name'] }}</td></tr>
        <tr><td><strong>Asset ID</strong></td><td>#{{ $equipment['id'] }}</td></tr>
        <tr><td><strong>Asset tag</strong></td><td>{{ $equipment['asset_tag'] ?: '—' }}</td></tr>
        <tr><td><strong>QR</strong></td><td>{{ $equipment['qr_code'] ?: '—' }}</td></tr>
        <tr><td><strong>Category</strong></td><td>{{ $equipment['category'] ?: '—' }}</td></tr>
        <tr><td><strong>Tracking</strong></td><td>{{ $equipment['tracking_mode'] ?: '—' }} · Qty {{ $equipment['quantity'] ?? 1 }}</td></tr>
        <tr><td><strong>Status</strong></td><td>{{ $equipment['inventory_status'] ?: '—' }} / {{ $equipment['condition_status'] ?: '—' }}</td></tr>
        <tr><td><strong>Current room</strong></td><td>{{ $equipment['room_name'] ?: '—' }}</td></tr>
        <tr><td><strong>Supplier</strong></td><td>{{ $equipment['supplier_name'] ?: '—' }}</td></tr>
        <tr><td><strong>Purchase cost</strong></td><td>{{ $equipment['purchase_cost'] !== null ? 'PHP '.number_format($equipment['purchase_cost'], 2) : '—' }}</td></tr>
        <tr><td><strong>Lot</strong></td><td>{{ $equipment['stock_lot_code'] ?: '—' }}</td></tr>
    </table>

    <h2>Procurement references</h2>
    <table class="meta">
        <tr><td width="28%"><strong>PO</strong></td><td>{{ $equipment['purchase_order_number'] ?: '—' }} {{ $equipment['purchase_order_date'] ? '('.$equipment['purchase_order_date'].')' : '' }}</td></tr>
        <tr><td><strong>ATP</strong></td><td>{{ $equipment['atp_number'] ?: '—' }}</td></tr>
        <tr><td><strong>RIS</strong></td><td>{{ $equipment['ris_number'] ?: '—' }}</td></tr>
        <tr><td><strong>RR</strong></td><td>{{ $equipment['receiving_report_number'] ?: '—' }} {{ $equipment['receiving_report_date'] ? '('.$equipment['receiving_report_date'].')' : '' }}</td></tr>
        <tr><td><strong>Received by</strong></td><td>{{ $equipment['received_by'] ?: '—' }}</td></tr>
        <tr><td><strong>Stocked by</strong></td><td>{{ $equipment['stocked_by_name'] ?: '—' }} {{ $equipment['acquired_date'] ? '· '.$equipment['acquired_date'] : '' }}</td></tr>
    </table>

    <h2>Lifecycle timeline</h2>
    @if (empty($events))
        <p class="muted">No timeline events recorded.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th width="18%">When</th>
                    <th width="14%">Type</th>
                    <th width="22%">Event</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $event)
                    <tr>
                        <td>{{ $event['occurred_at'] ?? '—' }}</td>
                        <td><span class="badge">{{ $event['type'] ?? '' }}</span></td>
                        <td>{{ $event['title'] ?? '' }}</td>
                        <td>{{ $event['description'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
