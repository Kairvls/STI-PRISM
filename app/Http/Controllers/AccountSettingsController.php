<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function profile(): View
    {
        $user = Auth::user();
        $user->loadMissing('role');

        return view('maintenance-personnel.settings.profile', [
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'user_full_name' => ['required', 'string', 'max:255'],
            'user_email_address' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users_table', 'user_email_address')->ignore($user->user_id, 'user_id'),
            ],
            'user_contact_number' => ['nullable', 'string', 'max:32'],
            'user_username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users_table', 'user_username')->ignore($user->user_id, 'user_id'),
            ],
        ];

        if (Schema::hasColumn('users_table', 'user_profile_picture')) {
            $rules['user_profile_picture'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'];
            $rules['remove_profile_picture'] = ['nullable'];
        }

        $validated = $request->validate($rules);

        $user->user_full_name = trim($validated['user_full_name']);
        $user->user_email_address = trim($validated['user_email_address']);
        $user->user_contact_number = filled($validated['user_contact_number'] ?? null)
            ? trim($validated['user_contact_number'])
            : null;
        $user->user_username = trim($validated['user_username']);

        if (Schema::hasColumn('users_table', 'user_profile_picture')) {
            if ($request->hasFile('user_profile_picture') && $request->file('user_profile_picture')->isValid()) {
                $this->storeProfilePicture($user, $request->file('user_profile_picture'));
            } elseif ($request->input('remove_profile_picture') === '1') {
                $this->clearProfilePicture($user);
            }
        }

        $user->save();
        Auth::setUser($user->fresh());

        return redirect()
            ->route('maintenance.settings.profile')
            ->with('success', 'Profile settings updated successfully.');
    }

    public function updateProfilePicture(Request $request): JsonResponse
    {
        if (! Schema::hasColumn('users_table', 'user_profile_picture')) {
            return response()->json([
                'message' => 'Profile picture is not available yet. Please run migrations.',
            ], 422);
        }

        $request->validate([
            'user_profile_picture' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ]);

        $user = $request->user();
        $this->storeProfilePicture($user, $request->file('user_profile_picture'));
        $user->save();

        $fresh = $user->fresh();
        Auth::setUser($fresh);

        return response()->json([
            'message' => 'Profile picture saved.',
            'user_profile_picture' => $fresh->user_profile_picture,
            'profile_picture_url' => $this->versionedPictureUrl($fresh->profile_picture_url),
        ]);
    }

    public function removeProfilePicture(Request $request): JsonResponse
    {
        if (! Schema::hasColumn('users_table', 'user_profile_picture')) {
            return response()->json([
                'message' => 'Profile picture is not available yet.',
            ], 422);
        }

        $user = $request->user();
        $this->clearProfilePicture($user);
        $user->save();

        $fresh = $user->fresh();
        Auth::setUser($fresh);

        return response()->json([
            'message' => 'Profile picture removed.',
            'user_profile_picture' => null,
            'profile_picture_url' => null,
        ]);
    }

    public function security(): View
    {
        $user = Auth::user();
        $user->loadMissing('role');

        return view('maintenance-personnel.settings.security', [
            'user' => $user,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->user_password)) {
            return back()
                ->withErrors(['current_password' => 'The current password is incorrect.'])
                ->withInput($request->except(['current_password', 'password', 'password_confirmation']));
        }

        $user->user_password = Hash::make($validated['password']);
        $user->save();

        return back()->with('success', 'Password updated successfully.');
    }

    private function storeProfilePicture($user, $file): void
    {
        $this->deleteProfilePictureFile($user->user_profile_picture);

        $storedPath = $file->store('profile-pictures', 'public');
        $user->user_profile_picture = $storedPath;
    }

    private function clearProfilePicture($user): void
    {
        $this->deleteProfilePictureFile($user->user_profile_picture);
        $user->user_profile_picture = null;
    }

    private function versionedPictureUrl(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'v='.time();
    }

    private function deleteProfilePictureFile(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        if (
            str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
        ) {
            return;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        $normalized = preg_replace('#^storage/#', '', $normalized) ?: $normalized;

        if (Storage::disk('public')->exists($normalized)) {
            Storage::disk('public')->delete($normalized);
        }
    }
}
