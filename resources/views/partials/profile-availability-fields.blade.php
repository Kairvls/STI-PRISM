{{-- Soft scheduled availability for routing (does not lock login). --}}
@php
    use App\Support\UserAvailability;

    $availabilitySupported = UserAvailability::supported();
    $snap = UserAvailability::snapshot($user);
    $awayStatus = old('user_away_status', $snap['status']);
    if (! in_array($awayStatus, UserAvailability::statuses(), true)) {
        $awayStatus = UserAvailability::AVAILABLE;
    }
    $awayStarts = old('user_away_starts_at', $snap['starts_at'] ?? '');
    $awayUntil = old('user_away_until', $snap['until'] ?? '');
    $awayNote = old('user_away_note', $snap['note'] ?? '');
    $locked = $snap['locked'];
    $canReturnEarly = $snap['can_return_early'];
@endphp

@if($availabilitySupported)
    <div
        class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50/80 p-4"
        x-data="{
            status: @js($awayStatus),
            locked: @js($locked),
            canReturnEarly: @js($canReturnEarly)
        }"
    >
        <div class="mb-3">
            <p class="text-sm font-semibold text-slate-800">Work availability</p>
            <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                Schedule when you leave and when you return. Assign-to lists mark you unavailable only
                during that window. You can still sign in and work.
            </p>
            @if($snap['label'] !== '')
                <p class="mt-2 text-xs font-medium text-amber-800">
                    Current: {{ $snap['label'] }}
                </p>
            @endif
            @if($locked && ! $canReturnEarly)
                <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                    This schedule is locked (within {{ UserAvailability::LOCK_HOURS }} hours of leave start).
                    Cancel and reschedule are disabled.
                </p>
            @elseif($canReturnEarly)
                <p class="mt-2 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-900">
                    Leave has started. You can mark Available for an early return, but you cannot change the dates.
                </p>
            @else
                <p class="mt-2 text-xs text-slate-500">
                    You can change or cancel until {{ UserAvailability::LOCK_HOURS }} hours before leave starts.
                    Teammates are notified when you schedule, update, or cancel — and again
                    {{ UserAvailability::REMINDER_DAYS }} days before leave begins.
                </p>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="user_away_status" class="mb-1.5 block text-xs font-semibold text-slate-600">
                    Status
                </label>
                <select
                    id="user_away_status"
                    name="user_away_status"
                    x-model="status"
                    @if($locked && ! $canReturnEarly) disabled @endif
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100 disabled:bg-slate-50 disabled:text-slate-400"
                >
                    <option value="{{ UserAvailability::AVAILABLE }}">Available</option>
                    <option value="{{ UserAvailability::LEAVE }}">On leave</option>
                    <option value="{{ UserAvailability::ABSENT }}">Absent</option>
                </select>
                @if($locked && ! $canReturnEarly)
                    <input type="hidden" name="user_away_status" value="{{ $awayStatus }}">
                @endif
                @error('user_away_status')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="hidden sm:block" aria-hidden="true"></div>

            <div x-show="status !== '{{ UserAvailability::AVAILABLE }}'" x-cloak>
                <label for="user_away_starts_at" class="mb-1.5 block text-xs font-semibold text-slate-600">
                    Leave from <span class="text-rose-500">*</span>
                </label>
                <input
                    id="user_away_starts_at"
                    type="datetime-local"
                    name="user_away_starts_at"
                    value="{{ $awayStarts }}"
                    @if($locked) disabled @endif
                    :required="status !== '{{ UserAvailability::AVAILABLE }}' && !locked"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100 disabled:bg-slate-50 disabled:text-slate-400"
                >
                @if($locked)
                    <input type="hidden" name="user_away_starts_at" value="{{ $awayStarts }}">
                @endif
                @error('user_away_starts_at')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="status !== '{{ UserAvailability::AVAILABLE }}'" x-cloak>
                <label for="user_away_until" class="mb-1.5 block text-xs font-semibold text-slate-600">
                    Back on <span class="text-rose-500">*</span>
                </label>
                <input
                    id="user_away_until"
                    type="datetime-local"
                    name="user_away_until"
                    value="{{ $awayUntil }}"
                    @if($locked) disabled @endif
                    :required="status !== '{{ UserAvailability::AVAILABLE }}' && !locked"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100 disabled:bg-slate-50 disabled:text-slate-400"
                >
                @if($locked)
                    <input type="hidden" name="user_away_until" value="{{ $awayUntil }}">
                @endif
                @error('user_away_until')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2" x-show="status !== '{{ UserAvailability::AVAILABLE }}'" x-cloak>
                <label for="user_away_note" class="mb-1.5 block text-xs font-semibold text-slate-600">
                    Note <span class="font-normal text-slate-400">(optional)</span>
                </label>
                <input
                    id="user_away_note"
                    type="text"
                    name="user_away_note"
                    value="{{ $awayNote }}"
                    maxlength="255"
                    placeholder="e.g. Official leave / covering for training"
                    @if($locked && ! $canReturnEarly) disabled @endif
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100 disabled:bg-slate-50"
                >
                @if($locked && ! $canReturnEarly)
                    <input type="hidden" name="user_away_note" value="{{ $awayNote }}">
                @endif
                @error('user_away_note')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
@endif
