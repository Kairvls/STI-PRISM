<!DOCTYPE html>
<html>
<head>
    <title>Sign in — PaAyo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex items-center justify-center h-screen px-4">
        <div class="bg-white p-8 rounded-xl shadow w-full max-w-sm text-center">
            <h2 class="text-2xl font-bold mb-3">
                Sign in
            </h2>
            <p class="text-sm text-gray-600 mb-6">
                Use your STI Office 365 account (email, password, and MFA). You’ll open your primary role first; extra roles are available from the portal switcher.
            </p>

            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-left text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <a href="{{ route('auth.microsoft.redirect') }}"
               class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-900 hover:bg-gray-50">
                <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" class="w-4 h-4" alt="Microsoft">
                Log in with Office 365
            </a>

            <a href="{{ url('/') }}" class="mt-4 inline-block text-sm text-blue-700 hover:underline">
                Back to home
            </a>
        </div>
    </div>
</body>
</html>
