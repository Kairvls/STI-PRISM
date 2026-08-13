<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 18px;
        }
        h1 {
            font-size: 16px;
            margin: 0 0 4px;
        }
        .meta {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 7px;
            vertical-align: top;
            word-wrap: break-word;
        }
        th {
            background: #f3f4f6;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        td.amount {
            text-align: right;
            white-space: nowrap;
        }
        .empty {
            text-align: center;
            color: #6b7280;
            padding: 24px 0;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        Generated {{ $generatedAt }} &middot; {{ count($rows) }} record(s)
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Reference</th>
                <th style="width: 22%;">Purpose</th>
                <th style="width: 18%;">Equipment</th>
                <th style="width: 14%;">Requested By</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 14%;">Status</th>
                <th style="width: 10%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['reference'] }}</td>
                    <td>{{ $row['purpose'] }}</td>
                    <td>{{ $row['equipment'] }}</td>
                    <td>{{ $row['requested_by'] }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td class="amount">PHP {{ $row['amount'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">No RIS records found for this export.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
