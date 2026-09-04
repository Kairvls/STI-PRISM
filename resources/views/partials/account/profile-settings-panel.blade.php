{{--
  Profile settings UI (display + form shell; save not wired yet).
  Expects: $user, optional $roleLabel
--}}
@php
    $user = $user ?? auth()->user();
    $roleLabel = $roleLabel ?? 'User';
    $initial = strtoupper(substr((string) ($user->user_full_name ?? 'U'), 0, 1));
@endphp

<div class="rounded-[18px] border border-gray-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
    <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
        <h2 class="text-sm font-semibold text-gray-900">Profile information</h2>
        <p class="mt-1 text-xs text-gray-500">
            Your name and contact details as shown across PRISM. Saving will be enabled in a later update.
        </p>
    </div>

    <div class="px-5 py-5 sm:px-6">
        <div class="mb-6 flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-slate-900 text-lg font-semibold text-white">
                {{ $initial }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-900">{{ $user->user_full_name ?? '—' }}</p>
                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $roleLabel }}</p>
                <p class="mt-0.5 truncate text-xs text-slate-400">{{ $user->user_email_address ?? '—' }}</p>
            </div>
            <button
                type="button"
                disabled
                title="Photo upload coming soon"
                class="ml-auto rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-400 cursor-not-allowed"
            >
                Change photo
            </button>
        </div>

        <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" onsubmit="return false;" aria-label="Profile settings">
            <label class="block sm:col-span-2">
                <span class="text-xs font-semibold text-slate-600">Full name</span>
                <input
                    type="text"
                    name="user_full_name"
                    value="{{ old('user_full_name', $user->user_full_name ?? '') }}"
                    autocomplete="name"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                >
            </label>

            <label class="block">
                <span class="text-xs font-semibold text-slate-600">Username</span>
                <input
                    type="text"
                    name="user_username"
                    value="{{ old('user_username', $user->user_username ?? '') }}"
                    autocomplete="username"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                >
            </label>

            <label class="block">
                <span class="text-xs font-semibold text-slate-600">Employee ID</span>
                <input
                    type="text"
                    name="user_employee_id"
                    value="{{ old('user_employee_id', $user->user_employee_id ?? '') }}"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                >
            </label>

            <label class="block">
                <span class="text-xs font-semibold text-slate-600">Email address</span>
                <input
                    type="email"
                    name="user_email_address"
                    value="{{ old('user_email_address', $user->user_email_address ?? '') }}"
                    autocomplete="email"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                >
            </label>

            <label class="block">
                <span class="text-xs font-semibold text-slate-600">Contact number</span>
                @include('partials.phone-input', [
                    'name' => 'user_contact_number',
                    'value' => old('user_contact_number', $user->user_contact_number ?? ''),
                    'id' => 'account-panel-user-contact-number',
                    'inputClass' => 'mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100',
                ])
            </label>

            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4 sm:col-span-2">
                <button
                    type="button"
                    disabled
                    class="rounded-lg bg-slate-900/40 px-4 py-2.5 text-sm font-medium text-white cursor-not-allowed"
                    title="Saving is not available yet"
                >
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>
