@extends('layouts.accounting-layout')

@section('title', 'Profile settings')

@section('content')
@include('accounting.partials.flash')

@php
    $roleName = $user->role->role_name ?? 'Accounting';
    $avatarUrl = $user->profilePictureUrl();
@endphp

<div class="acc-page fade-in space-y-4">
    <p class="text-sm text-slate-500">Manage your Accounting account details, photo, and password.</p>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <aside class="pm-card p-5 xl:col-span-4">
            <div class="flex items-center gap-3">
                @include('partials.user-avatar', ['avatarUser' => $user, 'avatarSize' => 'lg'])
                <div class="min-w-0">
                    <p class="truncate text-base font-semibold text-slate-900">{{ $user->user_full_name }}</p>
                    <p class="mt-0.5 truncate text-sm text-slate-500">{{ $user->user_email_address }}</p>
                </div>
            </div>

            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">Role</dt>
                    <dd class="font-semibold text-slate-800">{{ $roleName }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">Employee ID</dt>
                    <dd class="font-semibold text-slate-800">{{ $user->user_employee_id ?: '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">Username</dt>
                    <dd class="font-semibold text-slate-800">{{ $user->user_username ?: '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3">
                    <dt class="text-slate-500">Contact</dt>
                    <dd class="font-semibold text-slate-800">{{ $user->user_contact_number ?: '—' }}</dd>
                </div>
            </dl>
        </aside>

        <div class="space-y-4 xl:col-span-8">
            <section class="pm-card p-5">
                <div class="mb-4">
                    <h2 class="text-sm font-bold text-slate-900">Profile information</h2>
                    <p class="mt-1 text-xs text-slate-500">Update your photo, name, email, username, and contact number.</p>
                </div>

                <form method="POST" action="{{ route('accounting.profile.update') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PATCH')

                    <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div id="accAvatarPreview" class="shrink-0">
                                @include('partials.user-avatar', ['avatarUser' => $user, 'avatarSize' => 'lg'])
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-800">Profile picture</p>
                                <p class="mt-1 text-xs text-slate-500">JPG, PNG, or WEBP up to 2MB.</p>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <label class="acc-btn acc-btn-ghost cursor-pointer">
                                        <span>Upload photo</span>
                                        <input id="accProfilePictureInput" type="file" name="user_profile_picture" accept="image/jpeg,image/png,image/webp" class="sr-only">
                                    </label>
                                    @if ($avatarUrl)
                                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-rose-600">
                                            <input type="checkbox" name="remove_profile_picture" value="1" class="rounded border-slate-300">
                                            Remove current photo
                                        </label>
                                    @endif
                                </div>
                                @error('user_profile_picture')
                                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600" for="user_full_name">Full name</label>
                        <input id="user_full_name" name="user_full_name" type="text" value="{{ old('user_full_name', $user->user_full_name) }}" required class="acc-search w-full max-w-none">
                        @error('user_full_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600" for="user_email_address">Email address</label>
                        <input id="user_email_address" name="user_email_address" type="email" value="{{ old('user_email_address', $user->user_email_address) }}" required class="acc-search w-full max-w-none">
                        @error('user_email_address') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600" for="user_contact_number">Contact number</label>
                        <input id="user_contact_number" name="user_contact_number" type="text" value="{{ old('user_contact_number', $user->user_contact_number) }}" class="acc-search w-full max-w-none">
                        @error('user_contact_number') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600" for="user_username">Username</label>
                        <input id="user_username" name="user_username" type="text" value="{{ old('user_username', $user->user_username) }}" required class="acc-search w-full max-w-none">
                        @error('user_username') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600" for="user_employee_id">Employee ID</label>
                        <input id="user_employee_id" type="text" value="{{ $user->user_employee_id }}" disabled class="acc-search w-full max-w-none opacity-70">
                        <p class="mt-1 text-[11px] text-slate-400">Managed by Admin. Contact Admin to change this.</p>
                    </div>

                    <div class="sm:col-span-2 flex items-center gap-3 pt-1">
                        <button type="submit" class="acc-btn acc-btn-primary">Save profile</button>
                    </div>
                </form>
            </section>

            <section class="pm-card p-5">
                <div class="mb-4">
                    <h2 class="text-sm font-bold text-slate-900">Update password</h2>
                    <p class="mt-1 text-xs text-slate-500">Use a long, unique password for your Accounting account.</p>
                </div>

                <form method="POST" action="{{ route('accounting.profile.password') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600" for="current_password">Current password</label>
                        <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="acc-search w-full max-w-none">
                        @error('current_password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600" for="password">New password</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password" class="acc-search w-full max-w-none">
                        @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600" for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="acc-search w-full max-w-none">
                    </div>

                    <div class="sm:col-span-2 flex items-center gap-3 pt-1">
                        <button type="submit" class="acc-btn acc-btn-primary">Update password</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>

<script>
    (function () {
        const input = document.getElementById('accProfilePictureInput');
        const preview = document.getElementById('accAvatarPreview');
        if (!input || !preview) return;
        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            preview.innerHTML = '<img src="' + url + '" alt="Preview" class="h-20 w-20 shrink-0 rounded-full object-cover bg-slate-200">';
        });
    })();
</script>
@endsection
