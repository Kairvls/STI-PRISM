<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Document Preview' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            min-height: 100vh;
            background: #e2e8f0;
            font-family: Arial, Helvetica, sans-serif;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 32px 16px 48px;
        }
        .document-viewer-shell {
            width: 100%;
            max-width: max-content;
            margin: 0 auto;
        }
        .document-viewer-shell > * {
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.12);
        }
        .document-viewer-shell .atp-print-sheet,
        .document-viewer-shell .rfc-print-sheet,
        .document-viewer-shell .rr-print-sheet,
        .document-viewer-shell .liq-print-sheet {
            max-width: none;
        }
    </style>
    @stack('head')
</head>
<body>
    <div class="document-viewer-shell">
        @yield('document')
    </div>
</body>
</html>
