<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set a new PIN | PaAyo</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico?v=1') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --blue: #0025cc;
            --blue-dark: #001ca3;
            --ink: #1a1a2e;
            --muted: #6b7280;
            --line: #e8ecf4;
            --soft: #f3f6ff;
        }
        html, body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--soft);
            color: var(--ink);
        }
        .field {
            width: 100%;
            height: 52px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: #fff;
            padding: 0 14px;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: .5em;
            text-align: center;
            outline: none;
        }
        .field:focus { border-color: #9db0ff; box-shadow: 0 0 0 4px rgba(0, 37, 204, .08); }
        .field::placeholder { color: #c5cad6; letter-spacing: .5em; }
        .btn-blue {
            background: var(--blue);
            color: #fff;
            font-weight: 700;
            border: 0;
            border-radius: 999px;
            padding: 12px 22px;
            cursor: pointer;
        }
        .btn-blue:hover { background: var(--blue-dark); }
    </style>
</head>
<body class="min-h-screen px-4 py-10">
    <div class="max-w-[460px] mx-auto">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 no-underline mb-6" style="color:var(--ink);">
            <span class="font-extrabold tracking-tight text-lg">Pa<span style="color:var(--blue);">Ayo</span></span>
        </a>

        <div class="rounded-3xl bg-white p-6 md:p-8" style="border:1px solid var(--line); box-shadow:0 24px 60px rgba(15,23,42,.08);">
            <p class="text-xs font-bold tracking-[0.16em] uppercase mb-2" style="color:var(--blue);">Reporting PIN</p>
            <h1 class="text-2xl font-extrabold mb-2">Set a new PIN</h1>
            <p class="text-sm leading-relaxed mb-6" style="color:var(--muted);">
                {{ $reporterName }} &middot; <span class="font-semibold" style="color:var(--ink);">{{ $employeeId }}</span><br>
                Choose 6 digits you can remember. It has to be a new PIN, not the one you use now. Avoid patterns like 123456 or 111111.
                This link expires at {{ \Illuminate\Support\Carbon::parse($expiresAt)->timezone(config('app.timezone'))->format('g:i A') }} on {{ \Illuminate\Support\Carbon::parse($expiresAt)->timezone(config('app.timezone'))->format('M j, Y') }}.
            </p>

            @if ($errors->any())
                <div class="mb-5 rounded-xl px-4 py-3 text-sm" style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('reporter.pin.reset.complete', $token) }}" class="space-y-4" id="pinResetForm">
                @csrf

                <div>
                    <label for="pin" class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color:var(--muted);">New PIN</label>
                    <input id="pin" name="pin" type="password" inputmode="numeric" autocomplete="new-password"
                        maxlength="6" pattern="[0-9]{6}" placeholder="••••••" required class="field">
                </div>

                <div>
                    <label for="pin_confirmation" class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color:var(--muted);">Type it again</label>
                    <input id="pin_confirmation" name="pin_confirmation" type="password" inputmode="numeric" autocomplete="new-password"
                        maxlength="6" pattern="[0-9]{6}" placeholder="••••••" required class="field">
                </div>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <a href="{{ url('/') }}" class="text-sm font-semibold no-underline" style="color:var(--muted);">Back to home</a>
                    <button type="submit" class="btn-blue text-sm">Save new PIN</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('#pinResetForm input[inputmode="numeric"]').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = input.value.replace(/\D/g, '').slice(0, 6);
            });
        });
    </script>
</body>
</html>
