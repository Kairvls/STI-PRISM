<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reporter Audit Pack #{{ $reporter['id'] }}</title>
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
    <h1>Reporter Audit Pack</h1>
    <p class="muted">Generated {{ $generatedAt->format('Y-m-d H:i') }} · PaAyo</p>

    <h2>Reporter profile</h2>
    <table class="meta">
        <tr><td width="28%"><strong>Name</strong></td><td>{{ $reporter['name'] }}</td></tr>
        <tr><td><strong>Employee ID</strong></td><td>{{ $reporter['employee_id'] }}</td></tr>
        <tr><td><strong>Type</strong></td><td>{{ $reporter['type'] }}</td></tr>
        <tr><td><strong>Department</strong></td><td>{{ $reporter['department'] }}</td></tr>
        <tr><td><strong>Email</strong></td><td>{{ $reporter['email'] }}</td></tr>
        <tr><td><strong>Contact</strong></td><td>{{ $reporter['contact'] }}</td></tr>
        <tr><td><strong>Status</strong></td><td><span class="badge">{{ $reporter['status'] }}</span></td></tr>
    </table>

    <h2>Report records ({{ count($reports) }})</h2>
    @if (empty($reports))
        <p class="muted">No report records for this reporter.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th width="14%">Report</th>
                    <th width="24%">Issue</th>
                    <th width="14%">Equipment</th>
                    <th width="12%">Room</th>
                    <th width="10%">Priority</th>
                    <th width="12%">Status</th>
                    <th width="14%">Submitted</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reports as $report)
                    <tr>
                        <td>{{ $report['ticket'] }}</td>
                        <td>{{ $report['issue'] }}</td>
                        <td>{{ $report['equipment'] }}</td>
                        <td>{{ $report['room'] }}</td>
                        <td>{{ $report['priority'] }}</td>
                        <td>{{ $report['status'] }}</td>
                        <td>{{ $report['submitted_at'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
