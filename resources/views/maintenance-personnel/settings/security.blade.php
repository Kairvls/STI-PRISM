@extends('layouts.maintenance-layout')

@section('title', 'Security Settings')

@section('content')
<div class="mx-auto max-w-3xl">
    @include('maintenance-personnel.settings._header')

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-700">
                    <i data-lucide="shield-check" class="h-4 w-4"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-950">Change password</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Use a strong password you do not reuse on other accounts.
                    </p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ url('/maintenance/settings/security/password') }}" class="px-6 py-6">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <div>
                    <label for="current_password" class="mb-2 block text-sm font-semibold text-slate-700">
                        Current password
                    </label>
                    <input
                        id="current_password"
                        name="current_password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">
                        New password
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >
                </div>

                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-700">
                        Confirm new password
                    </label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <button
                    type="submit"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800"
                >
                    <i data-lucide="key-round" class="h-4 w-4"></i>
                    Update password
                </button>
            </div>
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="px-6 py-5">
            <h3 class="text-sm font-semibold text-slate-800">Account tips</h3>
            <div class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200">
                <div class="flex items-start justify-between gap-6 px-4 py-3.5 text-sm">
                    <span class="text-slate-500">Signed in as</span>
                    <span class="text-right font-medium text-slate-900">{{ $user->user_username }}</span>
                </div>
                <div class="flex items-start justify-between gap-6 px-4 py-3.5 text-sm">
                    <span class="text-slate-500">Email</span>
                    <span class="text-right font-medium text-slate-900">{{ $user->user_email_address }}</span>
                </div>
                <div class="flex items-start justify-between gap-6 px-4 py-3.5 text-sm">
                    <span class="text-slate-500">Recommendation</span>
                    <span class="text-right text-slate-700">Change your password regularly and never share it.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endsection
