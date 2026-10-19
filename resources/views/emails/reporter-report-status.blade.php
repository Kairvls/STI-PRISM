<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Report {{ $ticketCode }} status update</title>
</head>
<body style="margin:0;padding:0;background:#f3f6ff;font-family:'Segoe UI',Arial,sans-serif;color:#1a1a2e;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6ff;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e8ecf4;">
                    <tr>
                        <td style="padding:28px 28px 8px;">
                            <div style="font-weight:800;letter-spacing:.08em;font-size:13px;color:#0025cc;">PAAYO</div>
                            <h1 style="margin:12px 0 8px;font-size:22px;line-height:1.3;">
                                Report status updated
                            </h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#6b7280;">
                                Hi {{ $name }}, Maintenance updated report
                                <strong style="color:#1a1a2e;">{{ $ticketCode }}</strong>
                                filed under employee ID
                                <strong style="color:#1a1a2e;">{{ $employeeId }}</strong>.
                            </p>

                            <div style="margin:0 0 16px;padding:16px 18px;border-radius:14px;background:#f8fafc;border:1px solid #e8ecf4;">
                                <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#6b7280;">Status</p>
                                <p style="margin:0;font-size:16px;font-weight:700;color:#1a1a2e;">
                                    {{ $oldStatus }}
                                    <span style="color:#9aa3b5;font-weight:500;"> → </span>
                                    <span style="color:#0025cc;">{{ $newStatus }}</span>
                                </p>
                                <p style="margin:10px 0 0;font-size:13px;color:#6b7280;">
                                    Updated <strong style="color:#1a1a2e;">{{ $changedAt }}</strong>
                                    · Originally submitted {{ $submittedAt }}
                                </p>
                            </div>

                            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#6b7280;">Report summary</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 18px;">
                                @foreach ($items as $item)
                                    <tr>
                                        <td style="padding:10px 0;border-bottom:1px solid #eef2f7;font-size:14px;line-height:1.5;">
                                            <strong style="color:#1a1a2e;">{{ $item['name'] }}</strong>
                                            @if (!empty($item['room']))
                                                <span style="color:#9aa3b5;"> · {{ $item['room'] }}</span>
                                            @endif
                                            @if (!empty($item['issue']))
                                                <div style="margin-top:4px;color:#6b7280;">Issue: {{ $item['issue'] }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <p style="margin:0 0 22px;font-size:13px;line-height:1.6;color:#6b7280;">
                                If you do not recognize this report, contact Maintenance — someone may have used your employee ID and PIN.
                            </p>

                            <a href="{{ $appUrl }}"
                               style="display:inline-block;background:#0025cc;color:#fff;text-decoration:none;font-weight:700;font-size:14px;padding:12px 22px;border-radius:999px;">
                                Open PaAyo
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
