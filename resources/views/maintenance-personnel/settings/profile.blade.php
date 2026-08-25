@extends('layouts.maintenance-layout')

@section('title', 'Profile Settings')

@section('content')
@php
    $initial = strtoupper(substr($user->user_full_name ?? 'U', 0, 1));
    $roleName = $user->role->role_name ?? 'Maintenance Personnel';
    $profilePictureUrl = $user->profile_picture_url
        ? $user->profile_picture_url.'?v='.time()
        : null;
@endphp

<div class="mx-auto max-w-3xl">
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div
        id="profilePictureFlash"
        class="mb-6 hidden rounded-xl border px-4 py-3 text-sm"
        role="status"
    ></div>

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

    <form
        id="profileSettingsForm"
        method="POST"
        action="{{ url('/maintenance/settings/profile') }}"
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >
        @csrf

        <div class="border-b border-slate-200 px-6 py-5">
            <div class="flex items-center gap-4">
                <div class="relative shrink-0">
                    <button
                        type="button"
                        id="profileAvatarTrigger"
                        class="group relative flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-slate-900 text-xl font-semibold text-white ring-2 ring-white shadow-sm"
                        aria-label="Change profile picture"
                    >
                        <span
                            id="profileAvatarInitial"
                            class="{{ $profilePictureUrl ? 'hidden' : '' }}"
                        >{{ $initial }}</span>

                        <img
                            id="profileAvatarPreview"
                            src="{{ $profilePictureUrl }}"
                            alt="{{ $user->user_full_name }}"
                            class="h-full w-full object-cover {{ $profilePictureUrl ? '' : 'hidden' }}"
                        >

                        <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 transition group-hover:bg-slate-950/45">
                            <i data-lucide="camera" class="h-5 w-5 text-white opacity-0 transition group-hover:opacity-100"></i>
                        </span>

                        <span
                            id="profileAvatarSaving"
                            class="absolute inset-0 hidden items-center justify-center bg-slate-950/55 text-[10px] font-semibold uppercase tracking-wide text-white"
                        >
                            Saving…
                        </span>
                    </button>

                    <input
                        id="user_profile_picture"
                        type="file"
                        accept="image/jpeg,image/jpg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                        class="hidden"
                    >

                    <button
                        type="button"
                        id="removeProfilePictureBtn"
                        class="absolute -bottom-1 -right-1 inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 bg-white text-rose-600 shadow-sm transition hover:bg-rose-50 {{ $profilePictureUrl ? '' : 'hidden' }}"
                        aria-label="Remove profile picture"
                        title="Remove photo"
                    >
                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                    </button>
                </div>

                <div class="min-w-0">
                    <h2 class="truncate text-lg font-semibold text-slate-950">
                        {{ $user->user_full_name }}
                    </h2>
                    <p class="mt-0.5 truncate text-sm text-slate-500">
                        {{ $user->user_email_address }}
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">
                            {{ $roleName }}
                        </span>
                        @if (filled($user->user_employee_id))
                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                                ID: {{ $user->user_employee_id }}
                            </span>
                        @endif
                        <span class="text-[11px] text-slate-400">
                            Photo saves instantly and stays after refresh.
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-6 border-b border-slate-200 px-6">
            <a
                href="{{ url('/maintenance/settings/profile') }}"
                class="relative -mb-px border-b-2 border-slate-950 py-3 text-sm font-semibold text-slate-950"
            >
                Profile
            </a>
            <a
                href="{{ url('/maintenance/settings/security') }}"
                class="relative -mb-px border-b-2 border-transparent py-3 text-sm font-medium text-slate-500 transition hover:text-slate-800"
            >
                Security
            </a>
        </div>

        <div class="border-b border-slate-200 px-6 py-5">
            <h3 class="text-base font-semibold text-slate-950">Profile information</h3>
            <p class="mt-1 text-sm text-slate-500">
                Update your name, contact details, and username used across PRISM.
            </p>
        </div>

        <div class="space-y-5 px-6 py-6">
            <div>
                <label for="user_full_name" class="mb-2 block text-sm font-semibold text-slate-700">
                    Full name
                </label>
                <input
                    id="user_full_name"
                    name="user_full_name"
                    type="text"
                    value="{{ old('user_full_name', $user->user_full_name) }}"
                    required
                    maxlength="255"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                >
            </div>

            <div>
                <label for="user_email_address" class="mb-2 block text-sm font-semibold text-slate-700">
                    Email address
                </label>
                <input
                    id="user_email_address"
                    name="user_email_address"
                    type="email"
                    value="{{ old('user_email_address', $user->user_email_address) }}"
                    required
                    maxlength="255"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                >
            </div>

            <div>
                <label for="user_contact_number" class="mb-2 block text-sm font-semibold text-slate-700">
                    Contact number
                </label>
                <input
                    id="user_contact_number"
                    name="user_contact_number"
                    type="text"
                    value="{{ old('user_contact_number', $user->user_contact_number) }}"
                    maxlength="32"
                    placeholder="Optional"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                >
            </div>

            <div>
                <label for="user_username" class="mb-2 block text-sm font-semibold text-slate-700">
                    Username
                </label>
                <input
                    id="user_username"
                    name="user_username"
                    type="text"
                    value="{{ old('user_username', $user->user_username) }}"
                    required
                    maxlength="100"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                >
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="flex items-center justify-between gap-4 text-sm">
                    <span class="text-slate-500">Employee ID</span>
                    <span class="font-medium text-slate-900">
                        {{ $user->user_employee_id ?: '—' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
            <button
                type="submit"
                class="inline-flex h-10 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800"
            >
                Save profile
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) window.lucide.createIcons();

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const trigger = document.getElementById('profileAvatarTrigger');
        const input = document.getElementById('user_profile_picture');
        const preview = document.getElementById('profileAvatarPreview');
        const initial = document.getElementById('profileAvatarInitial');
        const removeBtn = document.getElementById('removeProfilePictureBtn');
        const savingOverlay = document.getElementById('profileAvatarSaving');
        const flash = document.getElementById('profilePictureFlash');
        const initialLetter = initial ? initial.textContent.trim() : 'U';
        let busy = false;

        function showFlash(message, ok) {
            if (!flash) return;
            flash.textContent = message;
            flash.classList.remove('hidden', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-800', 'border-rose-200', 'bg-rose-50', 'text-rose-800');
            if (ok) {
                flash.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-800');
            } else {
                flash.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-800');
            }
        }

        function setSaving(on) {
            busy = on;
            if (!savingOverlay) return;
            savingOverlay.classList.toggle('hidden', !on);
            savingOverlay.classList.toggle('flex', on);
        }

        function applyPicture(url) {
            if (preview && url) {
                preview.src = url;
                preview.classList.remove('hidden');
            }
            if (initial) initial.classList.add('hidden');
            if (removeBtn) removeBtn.classList.remove('hidden');

            document.querySelectorAll('[data-user-avatar]').forEach(function (el) {
                el.innerHTML = '<img src="' + url + '" alt="" class="h-full w-full object-cover">';
            });
        }

        function clearPicture() {
            if (preview) {
                preview.removeAttribute('src');
                preview.classList.add('hidden');
            }
            if (initial) initial.classList.remove('hidden');
            if (removeBtn) removeBtn.classList.add('hidden');
            if (input) input.value = '';

            document.querySelectorAll('[data-user-avatar]').forEach(function (el) {
                el.textContent = initialLetter;
            });
        }

        if (trigger && input) {
            trigger.addEventListener('click', function () {
                if (!busy) input.click();
            });
        }

        if (input) {
            input.addEventListener('change', async function () {
                const file = input.files && input.files[0];
                if (!file || busy) return;

                setSaving(true);

                // Instant local preview while uploading
                const localUrl = URL.createObjectURL(file);
                if (preview) {
                    preview.src = localUrl;
                    preview.classList.remove('hidden');
                }
                if (initial) initial.classList.add('hidden');

                const body = new FormData();
                body.append('user_profile_picture', file);

                try {
                    const response = await fetch('{{ url('/maintenance/settings/profile/picture') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body
                    });

                    const data = await response.json().catch(function () { return {}; });

                    if (!response.ok) {
                        const firstError = data.errors
                            ? Object.values(data.errors).flat()[0]
                            : null;
                        throw new Error(firstError || data.message || 'Unable to save profile picture.');
                    }

                    applyPicture(data.profile_picture_url);
                    showFlash(data.message || 'Profile picture saved.', true);
                } catch (error) {
                    clearPicture();
                    showFlash(error.message || 'Unable to save profile picture.', false);
                } finally {
                    URL.revokeObjectURL(localUrl);
                    setSaving(false);
                    input.value = '';
                }
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', async function () {
                if (busy) return;
                setSaving(true);

                try {
                    const response = await fetch('{{ url('/maintenance/settings/profile/picture') }}', {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json().catch(function () { return {}; });

                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to remove profile picture.');
                    }

                    clearPicture();
                    showFlash(data.message || 'Profile picture removed.', true);
                } catch (error) {
                    showFlash(error.message || 'Unable to remove profile picture.', false);
                } finally {
                    setSaving(false);
                }
            });
        }
    });
</script>
@endsection
