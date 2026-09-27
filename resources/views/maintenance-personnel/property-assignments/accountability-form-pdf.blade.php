<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Property Accountability Form · {{ $person->custodian_full_name }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; }
        .center { text-align: center; }
        .muted { color: #64748b; }
        .school { font-size: 13px; font-weight: bold; margin: 0; }
        h1 { font-size: 15px; margin: 10px 0 2px; letter-spacing: .04em; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .meta .label { width: 18%; color: #475569; }
        .meta .value { width: 32%; font-weight: bold; border-bottom: 1px solid #cbd5e1; }
        .items { margin-top: 14px; }
        .items th, .items td { border: 1px solid #94a3b8; padding: 5px 6px; text-align: left; vertical-align: top; }
        .items th { background: #f1f5f9; font-size: 9px; text-transform: uppercase; letter-spacing: .03em; }
        .items .num { width: 4%; text-align: center; }
        .statement { margin-top: 14px; line-height: 1.5; text-align: justify; }
        .signatures { margin-top: 36px; }
        .signatures td { width: 33.33%; padding: 0 10px; vertical-align: bottom; text-align: center; }
        .sig-line { border-top: 1px solid #0f172a; padding-top: 3px; font-weight: bold; min-height: 14px; }
        .sig-role { color: #475569; font-size: 9px; }
        .footer { margin-top: 24px; font-size: 8.5px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="center">
        <p class="school">STI College Ormoc</p>
        <p class="muted" style="margin: 2px 0 0;">Maintenance and Property Office</p>
        <h1>Property Accountability Form</h1>
        <p class="muted" style="margin: 0;">Generated {{ $generatedAt->format('F d, Y h:i A') }}</p>
    </div>

    <table class="meta" style="margin-top: 14px;">
        <tr>
            <td class="label">Accountable person</td>
            <td class="value">{{ $person->custodian_full_name }}</td>
            <td class="label" style="padding-left: 14px;">Employee ID</td>
            <td class="value">{{ $person->custodian_employee_id ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Position</td>
            <td class="value">{{ $person->custodian_position ?: '—' }}</td>
            <td class="label" style="padding-left: 14px;">Department / office</td>
            <td class="value">{{ $person->custodian_department ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Home room</td>
            <td class="value">{{ $person->home_room_name ?: '—' }}</td>
            <td class="label" style="padding-left: 14px;">Items held</td>
            <td class="value">{{ $items->count() }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="num">#</th>
                <th style="width: 22%;">Item / description</th>
                <th style="width: 15%;">Asset tag</th>
                <th style="width: 15%;">Serial no.</th>
                <th style="width: 16%;">Room / desk</th>
                <th style="width: 9%;">Condition</th>
                <th style="width: 10%;">Doc. no.</th>
                <th style="width: 9%;">Date issued</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $item->equipment_name }}</strong>
                        @php $desc = implode(' · ', array_filter([$item->equipment_brand_name, $item->equipment_model])); @endphp
                        @if ($desc !== '')<br><span class="muted">{{ $desc }}</span>@endif
                    </td>
                    <td>{{ $item->equipment_asset_tag ?: '—' }}</td>
                    <td>{{ $item->equipment_serial_number ?: '—' }}</td>
                    <td>
                        {{ $item->current_room_name ?: '—' }}
                        @if ($item->workstation_slot_label)<br><span class="muted">{{ $item->workstation_slot_label }}</span>@endif
                    </td>
                    <td>{{ $item->equipment_condition_status ?: '—' }}</td>
                    <td>{{ $item->assignment_document_no ?: '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->assignment_issued_at)->format('m/d/Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center muted" style="padding: 14px;">No property is currently assigned to this person.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="statement">
        I acknowledge that I have received the property listed above and accept responsibility for its proper use,
        care, and safekeeping. I will promptly report any loss, damage, or malfunction to the Maintenance Office,
        and I will return all items listed here upon transfer of assignment, resignation, separation, or whenever
        the school requests it.
    </p>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">{{ $person->custodian_full_name }}</div>
                <div class="sig-role">Received by (accountable person)</div>
                <div class="sig-role" style="margin-top: 8px;">Date: ____________________</div>
            </td>
            <td>
                <div class="sig-line">{{ $preparedBy ?: ' ' }}</div>
                <div class="sig-role">Issued by (maintenance personnel)</div>
                <div class="sig-role" style="margin-top: 8px;">Date: ____________________</div>
            </td>
            <td>
                <div class="sig-line">&nbsp;</div>
                <div class="sig-role">Noted by (department head / administrator)</div>
                <div class="sig-role" style="margin-top: 8px;">Date: ____________________</div>
            </td>
        </tr>
    </table>

    <p class="footer">
        This form lists items with an active assignment on the date generated. Returns and transfers are recorded in the
        equipment's lifecycle history in PRISM.
    </p>
</body>
</html>
