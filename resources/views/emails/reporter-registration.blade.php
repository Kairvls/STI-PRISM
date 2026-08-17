<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Complete your PaAyo registration</title>
</head>
<body style="margin:0;padding:0;background:#f3f6ff;font-family:'Segoe UI',Arial,sans-serif;color:#1a1a2e;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6ff;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e8ecf4;">
                    <tr>
                        <td style="padding:28px 28px 8px;">
                            <div style="font-weight:800;letter-spacing:.08em;font-size:13px;color:#0025cc;">PAAYO</div>
                            <h1 style="margin:12px 0 8px;font-size:22px;line-height:1.3;">Add your reporter details</h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#6b7280;">
                                Faculty and staff at STI College Ormoc fill this once so maintenance can identify your reports. This is not a system account.
                            </p>
                            <p style="margin:0 0 22px;font-size:14px;line-height:1.6;color:#6b7280;">
                                This link is tied to <strong style="color:#1a1a2e;">{{ $email }}</strong> and expires in 24 hours.
                            </p>
                            <a href="{{ $registerUrl }}"
                               style="display:inline-block;background:#0025cc;color:#fff;text-decoration:none;font-weight:700;font-size:14px;padding:12px 22px;border-radius:999px;">
                                Open reporter form
                            </a>
                            <p style="margin:22px 0 0;font-size:12px;line-height:1.6;color:#9aa3b5;">
                                If you did not request this, you can ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
