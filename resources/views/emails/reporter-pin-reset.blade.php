<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset your PaAyo reporting PIN</title>
</head>
<body style="margin:0;padding:0;background:#f3f6ff;font-family:'Segoe UI',Arial,sans-serif;color:#1a1a2e;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6ff;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e8ecf4;">
                    <tr>
                        <td style="padding:28px 28px 8px;">
                            <div style="font-weight:800;letter-spacing:.08em;font-size:13px;color:#0025cc;">PAAYO</div>
                            <h1 style="margin:12px 0 8px;font-size:22px;line-height:1.3;">Reset your reporting PIN</h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#6b7280;">
                                Hi {{ $name }}, someone asked to reset the reporting PIN for employee ID
                                <strong style="color:#1a1a2e;">{{ $employeeId }}</strong>.
                            </p>
                            <p style="margin:0 0 22px;font-size:14px;line-height:1.6;color:#6b7280;">
                                Open the link below to choose a new 6-digit PIN. It works once and expires in {{ $minutes }} minutes, at {{ $expiresAt }} on {{ $expiresOn }}.
                            </p>
                            <a href="{{ $resetUrl }}"
                               style="display:inline-block;background:#0025cc;color:#fff;text-decoration:none;font-weight:700;font-size:14px;padding:12px 22px;border-radius:999px;">
                                Set a new PIN
                            </a>
                            <p style="margin:22px 0 0;font-size:12px;line-height:1.6;color:#9aa3b5;">
                                If you did not ask for this, ignore this email. Your current PIN still works.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
