<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin sign in — PaAyo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">PaAyo</p>
            <h1 class="mb-2 text-2xl font-bold text-slate-900">Admin sign in</h1>
            <p class="mb-6 text-sm text-slate-600">
                Office 365 only (email, password, and MFA). Only Administrator accounts can continue.
            </p>

            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-left text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-left text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <a
                href="{{ route('auth.microsoft.redirect', ['admin' => 1]) }}"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-50"
            >
                <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" class="h-4 w-4" alt="">
                Log in with Office 365
            </a>

            <p class="mt-5 text-[11px] leading-relaxed text-slate-400">
                This page is for system administrators. Staff should use the main sign-in on the home page.
            </p>
        </div>
    </div>
</body>
</html>
