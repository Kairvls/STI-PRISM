{{--
  Assign-to reviewer field.
  When only one person holds the role (School Administrator / President), show them
  as locked text + hidden input — no person select.
  Unavailable (leave/absent) people stay selectable but are labeled and not preferred.
--}}
@php
    $role = $role ?? \App\Support\WorkflowNotifier::ROLE_SCHOOL_ADMIN;
    $label = $label ?? $role;
    $inputName = $inputName ?? 'assigned_reviewer_id';
    $reviewers = \App\Support\ReviewerAssignment::options($role);
    $autoId = \App\Support\ReviewerAssignment::preferredId($reviewers);
    $sole = count($reviewers) === 1;
    $soleReviewer = $sole ? ($reviewers[0] ?? null) : null;
    $soleId = (int) ($soleReviewer['id'] ?? 0);
    $soleName = (string) ($soleReviewer['name'] ?? '');
    $soleAway = ! empty($soleReviewer['unavailable']);
    $hint = $hint ?? ('Only the selected '.$label.' will receive this for review.');
    $required = $required ?? true;
    $hasAvailable = collect($reviewers)->contains(fn ($r) => empty($r['unavailable']));
@endphp

@if($sole && $soleId > 0)
    <input type="hidden" name="{{ $inputName }}" value="{{ $soleId }}">
    <div class="rounded-lg border {{ $soleAway ? 'border-amber-200 bg-amber-50/70' : 'border-slate-200 bg-slate-50' }} px-3.5 py-3">
        <p class="text-xs font-medium text-slate-600">{{ $label }}</p>
        <p class="mt-0.5 text-sm font-semibold text-slate-900">{{ $soleName }}</p>
        <p class="mt-1 text-[11px] leading-relaxed {{ $soleAway ? 'text-amber-700' : 'text-slate-400' }}">
            @if($soleAway)
                This {{ $label }} is currently marked unavailable. You can still submit — consider following up if needed.
            @else
                Only one {{ $label }} is in the system, so this is sent to them automatically.
            @endif
        </p>
    </div>
@else
    <label class="mb-1.5 block text-xs font-medium text-gray-600">
        Assign to {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <select
        name="{{ $inputName }}"
        @if($required) required @endif
        class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-800 outline-none transition focus:border-gray-300"
    >
        <option value="">Select {{ $label }}...</option>
        @foreach($reviewers as $reviewer)
            @php
                $isUnavailable = ! empty($reviewer['unavailable']);
                // Keep selectable when everyone is away, or when this is the only option path.
                $disableOption = $isUnavailable && $hasAvailable;
            @endphp
            <option
                value="{{ $reviewer['id'] }}"
                @selected($autoId !== null && (int) $reviewer['id'] === (int) $autoId)
                @disabled($disableOption)
            >{{ $reviewer['name'] }}</option>
        @endforeach
    </select>
    <p class="mt-1.5 text-xs text-gray-400">
        {{ $hint }}
        @if($hasAvailable && collect($reviewers)->contains(fn ($r) => ! empty($r['unavailable'])))
            People on leave or absent are greyed out.
        @endif
    </p>
@endif
