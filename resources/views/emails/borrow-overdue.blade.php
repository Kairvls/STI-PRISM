<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Borrow {{ $slipNo }} overdue</title>
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
                                Borrowed equipment is overdue
                            </h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#6b7280;">
                                Hi {{ $name }}, your borrow under employee ID
                                <strong style="color:#1a1a2e;">{{ $employeeId }}</strong>
                                has passed its expected return date and is now
                                <strong style="color:#1a1a2e;">overdue</strong>.
                            </p>

                            <div style="margin:0 0 16px;padding:16px 18px;border-radius:14px;background:#fff7ed;border:1px solid #ffedd5;">
                                <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9a3412;">Reference #</p>
                                <p style="margin:0;font-size:18px;font-weight:800;color:#0025cc;letter-spacing:.02em;">{{ $slipNo }}</p>
                                <p style="margin:12px 0 0;font-size:13px;color:#9a3412;">
                                    <strong style="color:#7c2d12;">Status:</strong> Overdue
                                </p>
                                <p style="margin:6px 0 0;font-size:13px;color:#9a3412;">
                                    <strong style="color:#7c2d12;">Borrow from:</strong> {{ $borrowFrom }}
                                </p>
                                <p style="margin:6px 0 0;font-size:13px;color:#9a3412;">
                                    <strong style="color:#7c2d12;">Was due:</strong> {{ $expectedReturn }}
                                </p>
                            </div>

                            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#6b7280;">Equipment</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 18px;">
                                @foreach ($items as $item)
                                    <tr>
                                        <td style="padding:10px 0;border-bottom:1px solid #eef2f7;font-size:14px;line-height:1.5;">
                                            <strong style="color:#1a1a2e;">{{ $item['name'] }}</strong>
                                            <span style="color:#9aa3b5;"> · ×{{ $item['qty'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <div style="margin:0 0 22px;padding:12px 14px;border-radius:12px;background:#f8fafc;border:1px solid #e8ecf4;font-size:13px;line-height:1.6;color:#475569;">
                                <strong style="color:#1a1a2e;">Next step:</strong> {{ $nextStep }}
                            </div>

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
