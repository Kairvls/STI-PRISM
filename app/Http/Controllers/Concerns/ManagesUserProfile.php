<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

trait ManagesUserProfile
{
    protected function showUserProfile(string $view): mixed
    {
        $user = Auth::user();

        return view($view, compact('user'));
    }

    protected function saveUserProfile(Request $request, string $redirectTo): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'user_full_name' => ['required', 'string', 'max:255'],
            'user_email_address' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users_table', 'user_email_address')->ignore($user->user_id, 'user_id'),
            ],
            'user_contact_number' => ['nullable', 'string', 'max:40'],
            'user_profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_profile_picture' => ['nullable', 'boolean'],
        ]);

        $user->fill([
            'user_full_name' => $validated['user_full_name'],
            'user_email_address' => $validated['user_email_address'],
            'user_contact_number' => $validated['user_contact_number'] ?? null,
        ]);

        if ($request->boolean('remove_profile_picture')) {
            $this->deleteStoredProfilePicture($user->user_profile_picture);
            $user->user_profile_picture = null;
        }

        if ($request->hasFile('user_profile_picture')) {
            $this->deleteStoredProfilePicture($user->user_profile_picture);
            $user->user_profile_picture = $request->file('user_profile_picture')
                ->store('profile-pictures', 'public');
        }

        $user->save();

        return redirect($redirectTo)->with('success', 'Profile information updated.');
    }

    protected function saveUserPassword(Request $request, string $redirectTo): RedirectResponse
    {
        // Staff sign-in is Microsoft 365 / Entra. Local password changes are not used.
        return redirect($redirectTo)->with(
            'error',
            'Passwords are managed in Microsoft 365. Use your Microsoft account security settings to change your password or MFA.'
        );
    }

    protected function deleteStoredProfilePicture(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '' || preg_match('#^https?://#i', $path) || str_starts_with($path, '/')) {
            return;
        }

        $relative = ltrim(preg_replace('#^storage/#', '', $path), '/');
        if ($relative !== '' && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }
}
