<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reporter records · {{ $reporter['name'] }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; }
        .center { text-align: center; }
        .muted { color: #64748b; }
        .school { font-size: 13px; font-weight: bold; margin: 0; color: #0f172a; }
        h1 { font-size: 15px; margin: 10px 0 2px; letter-spacing: .04em; text-transform: uppercase; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; }
        .meta { margin-top: 16px; }
        .meta td { padding: 5px 0; vertical-align: bottom; }
        .meta .label { width: 9%; color: #475569; white-space: nowrap; padding-right: 8px; }
        .meta .value { width: 41%; font-weight: bold; border-bottom: 1px solid #cbd5e1; padding-right: 18px; }
        .meta .label-right { width: 11%; color: #475569; white-space: nowrap; padding-left: 16px; padding-right: 8px; }
        .meta .value-right { width: 39%; font-weight: bold; border-bottom: 1px solid #cbd5e1; }
        .items { margin-top: 14px; }
        .items th, .items td { border: 1px solid #94a3b8; padding: 5px 6px; text-align: left; vertical-align: top; }
        .items th { background: #e8eefc; font-size: 9px; text-transform: uppercase; letter-spacing: .03em; }
        .items .num { width: 4%; text-align: center; }
        .footer { margin-top: 18px; font-size: 8.5px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="center">
        <p class="school">STI College Ormoc</p>
        <p class="muted" style="margin: 2px 0 0;">Maintenance Office</p>
        <h1>Reporter Report Records</h1>
        <p class="muted" style="margin: 0;">Generated {{ $generatedAt->format('F d, Y h:i A') }}</p>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Reporter</td>
            <td class="value">{{ $reporter['name'] }}</td>
            <td class="label-right">Employee ID</td>
            <td class="value-right">{{ $reporter['employee_id'] }}</td>
        </tr>
        <tr>
            <td class="label">Type</td>
            <td class="value">{{ $reporter['type'] }}</td>
            <td class="label-right">Department</td>
            <td class="value-right">{{ $reporter['department'] }}</td>
        </tr>
        <tr>
            <td class="label">Email</td>
            <td class="value">{{ $reporter['email'] }}</td>
            <td class="label-right">Contact</td>
            <td class="value-right">{{ $reporter['contact'] }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td class="value">{{ $reporter['status'] }}</td>
            <td class="label-right">Reports listed</td>
            <td class="value-right">{{ count($reports) }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="num">#</th>
                <th style="width: 13%;">Report</th>
                <th style="width: 22%;">Issue</th>
                <th style="width: 15%;">Equipment</th>
                <th style="width: 13%;">Room</th>
                <th style="width: 10%;">Priority</th>
                <th style="width: 12%;">Status</th>
                <th style="width: 11%;">Submitted</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reports as $report)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td>{{ $report['ticket'] }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($report['issue'], 120) }}</td>
                    <td>{{ $report['equipment'] }}</td>
                    <td>{{ $report['room'] }}</td>
                    <td>{{ $report['priority'] }}</td>
                    <td>{{ $report['status'] }}</td>
                    <td>{{ $report['submitted_at'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center muted" style="padding: 14px;">No report records for this reporter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">
        This export lists the reporter profile and matching report records at the time generated · PaAyo
    </p>
</body>
</html>
