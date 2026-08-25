@php
    $settingsUser = $user ?? auth()->user();
    $initial = strtoupper(substr($settingsUser->user_full_name ?? 'U', 0, 1));
    $roleName = $settingsUser->role->role_name ?? 'Maintenance Personnel';
    $isProfile = request()->is('maintenance/settings/profile*');
    $isSecurity = request()->is('maintenance/settings/security*');
    $profilePictureUrl = $settingsUser->profile_picture_url ?? null;
@endphp

<div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-900 text-lg font-semibold text-white">
                @if ($profilePictureUrl)
                    <img
                        src="{{ $profilePictureUrl }}"
                        alt="{{ $settingsUser->user_full_name }}"
                        class="h-full w-full object-cover"
                    >
                @else
                    {{ $initial }}
                @endif
            </div>

            <div class="min-w-0">
                <h2 class="truncate text-lg font-semibold text-slate-950">
                    {{ $settingsUser->user_full_name }}
                </h2>
                <p class="mt-0.5 truncate text-sm text-slate-500">
                    {{ $settingsUser->user_email_address }}
                </p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">
                        {{ $roleName }}
                    </span>
                    @if (filled($settingsUser->user_employee_id))
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                            ID: {{ $settingsUser->user_employee_id }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="flex gap-6 border-b border-slate-200 px-6">
        <a
            href="{{ url('/maintenance/settings/profile') }}"
            class="relative -mb-px border-b-2 py-3 text-sm transition {{ $isProfile ? 'border-slate-950 font-semibold text-slate-950' : 'border-transparent font-medium text-slate-500 hover:text-slate-800' }}"
        >
            Profile
        </a>
        <a
            href="{{ url('/maintenance/settings/security') }}"
            class="relative -mb-px border-b-2 py-3 text-sm transition {{ $isSecurity ? 'border-slate-950 font-semibold text-slate-950' : 'border-transparent font-medium text-slate-500 hover:text-slate-800' }}"
        >
            Security
        </a>
    </div>
</div>

@if (session('success'))
    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <p class="font-semibold">Please check the form.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
