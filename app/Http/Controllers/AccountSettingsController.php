<?php

namespace App\Http\Controllers;

use App\Support\AvailabilityAnnouncer;
use App\Support\PhoneNumber;
use App\Support\UserAvailability;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function profile(): View
    {
        $user = Auth::user();
        $user->loadMissing('role');

        return view($this->profileView(), [
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'user_full_name' => ['required', 'string', 'max:255'],
            'user_contact_number' => PhoneNumber::rules(),
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

        if (UserAvailability::supported()) {
            $rules['user_away_status'] = ['required', Rule::in(UserAvailability::statuses())];
            $rules['user_away_starts_at'] = [
                'nullable',
                'date',
                Rule::requiredIf(fn () => in_array(
                    (string) $request->input('user_away_status'),
                    [UserAvailability::LEAVE, UserAvailability::ABSENT],
                    true
                )),
            ];
            $rules['user_away_until'] = [
                'nullable',
                'date',
                Rule::requiredIf(fn () => in_array(
                    (string) $request->input('user_away_status'),
                    [UserAvailability::LEAVE, UserAvailability::ABSENT],
                    true
                )),
                'after:user_away_starts_at',
            ];
            $rules['user_away_note'] = ['nullable', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

        $user->user_full_name = trim($validated['user_full_name']);
        // Office 365 email is admin-managed only (Users module). Ignore any posted value.
        $user->user_contact_number = PhoneNumber::normalizeForStorage($validated['user_contact_number'] ?? null);
        $user->user_username = trim($validated['user_username']);

        $availabilityMessage = null;
        if (UserAvailability::supported()) {
            $availabilityMessage = $this->applyAvailabilityUpdate($user, $validated);
        }

        if (Schema::hasColumn('users_table', 'user_profile_picture')) {
            if ($request->hasFile('user_profile_picture') && $request->file('user_profile_picture')->isValid()) {
                $this->storeProfilePicture($user, $request->file('user_profile_picture'));
            } elseif ($request->input('remove_profile_picture') === '1') {
                $this->clearProfilePicture($user);
            }
        }

        $user->save();
        Auth::setUser($user->fresh());

        $success = 'Profile settings updated successfully.';
        if ($availabilityMessage) {
            $success .= ' '.$availabilityMessage;
        }

        return redirect()
            ->route($this->profileRoute())
            ->with('success', $success);
    }

    /**
     * Apply leave/absence schedule with lock + announcements.
     *
     * @param  array<string, mixed>  $validated
     */
    private function applyAvailabilityUpdate($user, array $validated): ?string
    {
        $newStatus = (string) ($validated['user_away_status'] ?? UserAvailability::AVAILABLE);
        $hadPlan = UserAvailability::hasAwayPlan($user);
        $wasLocked = UserAvailability::isScheduleLocked($user);
        $canReturnEarly = UserAvailability::canReturnEarly($user);
        $previousLabel = UserAvailability::statusLabel(UserAvailability::statusOf($user));
        $previousStart = UserAvailability::startsAt($user)?->toDateTimeString();
        $previousEnd = UserAvailability::endsAt($user)?->toDateTimeString();

        if ($newStatus === UserAvailability::AVAILABLE) {
            if ($hadPlan && $wasLocked && ! $canReturnEarly) {
                throw ValidationException::withMessages([
                    'user_away_status' => 'Leave is locked within '
                        .UserAvailability::LOCK_HOURS
                        .' hours of the start time. You can no longer cancel this schedule.',
                ]);
            }

            $user->user_away_status = UserAvailability::AVAILABLE;
            if (Schema::hasColumn('users_table', 'user_away_starts_at')) {
                $user->user_away_starts_at = null;
            }
            $user->user_away_until = null;
            $user->user_away_note = null;
            if (Schema::hasColumn('users_table', 'user_away_reminder_sent_at')) {
                $user->user_away_reminder_sent_at = null;
            }

            if ($hadPlan) {
                AvailabilityAnnouncer::announceCancelled($user, strtolower($previousLabel));

                return $canReturnEarly
                    ? 'Marked available (early return).'
                    : 'Scheduled leave cancelled.';
            }

            return null;
        }

        $start = UserAvailability::parseDateTime($validated['user_away_starts_at'] ?? null);
        $end = UserAvailability::parseDateTime($validated['user_away_until'] ?? null);

        if (! $start || ! $end) {
            throw ValidationException::withMessages([
                'user_away_starts_at' => 'Set both leave-from and back-on date/time.',
            ]);
        }

        if ($end->lte($start)) {
            throw ValidationException::withMessages([
                'user_away_until' => 'Back-on must be after leave-from.',
            ]);
        }

        if ($end->lte(Carbon::now())) {
            throw ValidationException::withMessages([
                'user_away_until' => 'Back-on must be in the future.',
            ]);
        }

        // Changing an existing locked schedule (before/at start) is not allowed.
        if ($hadPlan && $wasLocked && ! $canReturnEarly) {
            $sameWindow = $previousStart === $start->toDateTimeString()
                && $previousEnd === $end->toDateTimeString()
                && UserAvailability::statusOf($user) === $newStatus;

            if (! $sameWindow) {
                throw ValidationException::withMessages([
                    'user_away_starts_at' => 'This schedule is locked within '
                        .UserAvailability::LOCK_HOURS
                        .' hours of leave start. You cannot reschedule or cancel it.',
                ]);
            }

            // Allow note-only updates while locked.
            $note = trim((string) ($validated['user_away_note'] ?? ''));
            $user->user_away_note = $note !== '' ? $note : null;

            return null;
        }

        // Once leave has started, dates stay fixed; only early return (Available) is the escape.
        if ($hadPlan && $canReturnEarly) {
            $sameWindow = $previousStart === $start->toDateTimeString()
                && $previousEnd === $end->toDateTimeString()
                && UserAvailability::statusOf($user) === $newStatus;

            if (! $sameWindow) {
                throw ValidationException::withMessages([
                    'user_away_starts_at' => 'Leave has already started. Set status to Available for an early return, or keep the current schedule.',
                ]);
            }

            $note = trim((string) ($validated['user_away_note'] ?? ''));
            $user->user_away_note = $note !== '' ? $note : null;

            return null;
        }

        $isNew = ! $hadPlan;
        $changedWindow = $previousStart !== $start->toDateTimeString()
            || $previousEnd !== $end->toDateTimeString()
            || UserAvailability::statusOf($user) !== $newStatus;

        $user->user_away_status = $newStatus;
        if (Schema::hasColumn('users_table', 'user_away_starts_at')) {
            $user->user_away_starts_at = $start;
        }
        $user->user_away_until = $end;
        $note = trim((string) ($validated['user_away_note'] ?? ''));
        $user->user_away_note = $note !== '' ? $note : null;
        if (Schema::hasColumn('users_table', 'user_away_reminder_sent_at') && $changedWindow) {
            $user->user_away_reminder_sent_at = null;
        }

        // Persist before announcing so messages read current values.
        $user->save();

        if ($isNew) {
            AvailabilityAnnouncer::announceScheduled($user);

            return 'Leave schedule saved. Teammates were notified.';
        }

        if ($changedWindow) {
            AvailabilityAnnouncer::announceUpdated($user);

            return 'Leave schedule updated. Teammates were notified.';
        }

        return null;
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

        return view($this->securityView(), [
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

    private function portal(): string
    {
        if (request()->routeIs('admin.*')) {
            return 'admin';
        }

        if (request()->routeIs('school-admin.*')) {
            return 'school-admin';
        }

        if (request()->routeIs('receiving.*')) {
            return 'receiving';
        }

        if (request()->routeIs('purchaser.*')) {
            return 'purchaser';
        }

        if (request()->routeIs('supply-officer.*') || request()->is('supply-officer/*')) {
            return 'supply-officer';
        }

        if (request()->routeIs('accounting.*') || request()->is('accounting/*')) {
            return 'accounting';
        }

        if (request()->routeIs('president.*') || request()->is('president/*')) {
            return 'president';
        }

        return 'maintenance';
    }

    private function profileView(): string
    {
        return match ($this->portal()) {
            'admin', 'school-admin' => 'admin.profile.index',
            'receiving' => 'receiving-officer.profile.index',
            'purchaser' => 'purchaser.profile.index',
            'supply-officer' => 'supply-officer.profile.index',
            'accounting' => 'accounting.profile.index',
            'president' => 'president.profile.index',
            default => 'maintenance-personnel.settings.profile',
        };
    }

    private function securityView(): string
    {
        return match ($this->portal()) {
            'admin', 'school-admin' => 'admin.security.index',
            'receiving' => 'receiving-officer.security.index',
            'purchaser' => 'purchaser.security.index',
            'supply-officer' => 'supply-officer.security.index',
            'accounting' => 'accounting.security.index',
            'president' => 'president.security.index',
            default => 'maintenance-personnel.settings.security',
        };
    }

    private function profileRoute(): string
    {
        return match ($this->portal()) {
            'admin' => 'admin.profile',
            'school-admin' => 'school-admin.profile',
            'receiving' => 'receiving.profile',
            'purchaser' => 'purchaser.profile',
            'supply-officer' => 'supply-officer.profile',
            'accounting' => 'accounting.profile',
            'president' => 'president.profile',
            default => 'maintenance.settings.profile',
        };
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
