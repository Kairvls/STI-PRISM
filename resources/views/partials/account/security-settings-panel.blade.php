{{--
  Security settings UI (form shell; save not wired yet).
  Expects: $user
--}}
@php
    $user = $user ?? auth()->user();
@endphp

<div class="space-y-4">
    <div class="rounded-[18px] border border-gray-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
        <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
            <h2 class="text-sm font-semibold text-gray-900">Change password</h2>
            <p class="mt-1 text-xs text-gray-500">
                Use a strong password you do not reuse elsewhere. Password updates will be enabled in a later update.
            </p>
        </div>

        <form class="space-y-4 px-5 py-5 sm:px-6" onsubmit="return false;" aria-label="Change password">
            <label class="block max-w-md">
                <span class="text-xs font-semibold text-slate-600">Current password</span>
                <input
                    type="password"
                    name="current_password"
                    autocomplete="current-password"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                    placeholder="••••••••"
                >
            </label>

            <label class="block max-w-md">
                <span class="text-xs font-semibold text-slate-600">New password</span>
                <input
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                    placeholder="At least 8 characters"
                >
            </label>

            <label class="block max-w-md">
                <span class="text-xs font-semibold text-slate-600">Confirm new password</span>
                <input
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                    placeholder="Repeat new password"
                >
            </label>

            <div class="flex items-center justify-end border-t border-gray-100 pt-4">
                <button
                    type="button"
                    disabled
                    class="rounded-lg bg-slate-900/40 px-4 py-2.5 text-sm font-medium text-white cursor-not-allowed"
                    title="Saving is not available yet"
                >
                    Update password
                </button>
            </div>
        </form>
    </div>

    <div class="rounded-[18px] border border-gray-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
        <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
            <h2 class="text-sm font-semibold text-gray-900">Signed-in sessions</h2>
            <p class="mt-1 text-xs text-gray-500">
                Review where your account is active. Session management will be enabled later.
            </p>
        </div>

        <div class="px-5 py-5 sm:px-6">
            <div class="flex items-start justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-900">This device</p>
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{ request()->ip() ?: 'Unknown IP' }} · Current browser session
                    </p>
                </div>
                <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                    Active
                </span>
            </div>

            <div class="mt-4 flex justify-end">
                <button
                    type="button"
                    disabled
                    class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-medium text-gray-400 cursor-not-allowed"
                    title="Not available yet"
                >
                    Sign out other sessions
                </button>
            </div>
        </div>
    </div>
</div>
