<div class="mx-auto max-w-2xl space-y-4 p-2">
    @if (session('success'))
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Shared unlock PIN</h2>
                <p class="mt-1 text-sm text-slate-500">Used in the infrastructure wizard when unlocking Step 1.</p>
                <p class="mt-2 text-xs text-slate-500">
                    PIN last updated:
                    <span class="font-semibold text-slate-700">
                        {{ $setting && $setting->campus_setup_pin_updated_at ? $setting->campus_setup_pin_updated_at->format('M d, Y h:i A') : 'Not set yet' }}
                    </span>
                </p>
            </div>
            <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                {{ $setting && $setting->campus_setup_pin_hash ? 'PIN configured' : 'No PIN set' }}
            </div>
        </div>

        <form method="POST" action="/admin/settings/campus-setup-pin" class="space-y-4">
            @csrf
            @if ($setting && $setting->campus_setup_pin_hash)
                <label class="block text-sm font-semibold text-slate-700">
                    Current PIN
                    <input type="password" name="current_campus_setup_pin" required minlength="4" maxlength="20" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-slate-400 focus:ring-slate-300" placeholder="Enter current PIN" autocomplete="current-password">
                </label>
            @endif
            <label class="block text-sm font-semibold text-slate-700">
                New PIN
                <input type="password" name="campus_setup_pin" required minlength="4" maxlength="20" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-slate-400 focus:ring-slate-300" placeholder="Enter a new shared PIN" autocomplete="new-password">
            </label>
            <label class="block text-sm font-semibold text-slate-700">
                Confirm New PIN
                <input type="password" name="campus_setup_pin_confirmation" required minlength="4" maxlength="20" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-slate-400 focus:ring-slate-300" placeholder="Re-enter the new PIN" autocomplete="new-password">
            </label>
            <button type="submit" class="admin-btn-primary">Save PIN</button>
        </form>
    </div>
</div>
