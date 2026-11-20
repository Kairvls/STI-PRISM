{{--
  Security settings for Office 365 / Microsoft Entra sign-in.
  Expects: $user
--}}
@php
    $user = $user ?? auth()->user();
    $email = (string) ($user->user_email_address ?? '');
@endphp

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-6 py-5">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                <i data-lucide="shield-check" class="h-4 w-4"></i>
            </div>
            <div>
                <h3 class="text-base font-semibold text-slate-950">Microsoft 365 sign-in</h3>
                <p class="mt-1 text-sm text-slate-500">
                    PaAyo uses your Office 365 account. Password and MFA are managed in Microsoft, not in this app.
                </p>
            </div>
        </div>
    </div>

    <div class="space-y-4 px-6 py-6">
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm text-slate-700">
            To change your password or update multi-factor authentication, use your
            <span class="font-semibold text-slate-900">Microsoft account</span>
            or ask your IT administrator in
            <span class="font-semibold text-slate-900">Azure / Entra ID</span>.
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a
                href="https://account.microsoft.com/security"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800"
            >
                <i data-lucide="external-link" class="h-4 w-4"></i>
                Open Microsoft security
            </a>
            <a
                href="https://mysignins.microsoft.com/security-info"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i data-lucide="key-round" class="h-4 w-4"></i>
                Security info &amp; MFA
            </a>
        </div>
    </div>
</div>

<div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="px-6 py-5">
        <h3 class="text-sm font-semibold text-slate-800">Account tips</h3>
        <div class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200">
            <div class="flex items-start justify-between gap-6 px-4 py-3.5 text-sm">
                <span class="text-slate-500">Office 365 email</span>
                <span class="text-right font-medium text-slate-900">{{ $email !== '' ? $email : '—' }}</span>
            </div>
            <div class="flex items-start justify-between gap-6 px-4 py-3.5 text-sm">
                <span class="text-slate-500">Sign-in method</span>
                <span class="text-right font-medium text-slate-900">Microsoft 365 (SSO)</span>
            </div>
            <div class="flex items-start justify-between gap-6 px-4 py-3.5 text-sm">
                <span class="text-slate-500">Recommendation</span>
                <span class="text-right text-slate-700">Never share your Microsoft password or MFA codes.</span>
            </div>
        </div>
    </div>
</div>
