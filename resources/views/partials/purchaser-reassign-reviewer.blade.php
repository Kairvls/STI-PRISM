{{--
  Purchaser-only change-reviewer control.
  Props: $type (atp|ris|rfc|rr|liq|po), $id, $currentReviewerId (optional), $title (optional)
--}}
@php
    $type = $type ?? '';
    $id = (int) ($id ?? 0);
    $catalog = \App\Support\ReviewerAssignment::reassignCatalog();
    $cfg = $catalog[$type] ?? null;
    $reviewers = $cfg ? \App\Support\ReviewerAssignment::options($cfg['role']) : [];
    $roleLabel = match ($cfg['role'] ?? '') {
        \App\Support\WorkflowNotifier::ROLE_ADMIN => 'Administrator',
        \App\Support\WorkflowNotifier::ROLE_RECEIVING => 'Receiving Officer',
        default => 'Accounting',
    };
    $title = $title ?? ('Change '.$roleLabel);
    $currentReviewerId = (int) ($currentReviewerId ?? 0);
    $pp = $pp ?? 'purchaser';
@endphp

@if($cfg && $id > 0 && count($reviewers) > 0)
    <form
        method="POST"
        action="{{ route($pp.'.reviewer.reassign', ['type' => $type, 'id' => $id]) }}"
        data-pur-confirm="Reassign this {{ $cfg['label'] }} to another {{ $roleLabel }}?"
        data-pur-confirm-title="{{ $title }}"
        data-pur-confirm-ok="Reassign"
        data-pur-confirm-reviewer-role="{{ $cfg['role'] }}"
        class="inline"
    >
        @csrf
        <button
            type="submit"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
            title="{{ $title }}"
            aria-label="{{ $title }}"
        >
            <i data-lucide="user-cog" class="h-4 w-4"></i>
        </button>
    </form>
@endif
