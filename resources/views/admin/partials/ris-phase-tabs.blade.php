{{-- Shared RIS phase switcher: Pending review | Reviewed RIS | Awaiting Issued by --}}
@php
    use App\Support\AdminPortal;

    $phase = $phase ?? (request()->is('*digital-signatures/sign-ris*') ? 'sign' : 'accept');
    if ($phase === 'sign' && request()->query('filter') === 'for_cosign') {
        $phase = 'issued';
    }
    // Prefer page-local stats when present; fall back to sidebar attention counts.
    $pendingAcceptCount = (int) ($pendingAcceptCount ?? $pendingRis ?? $adminSidebarPendingRis ?? 0);
    $readyToSignCount = (int) ($readyToSignCount ?? $adminSidebarReviewedRis ?? 0);
    $awaitingIssuedCount = (int) ($awaitingIssuedCount ?? $adminSidebarAwaitingIssuedBy ?? 0);
    $acceptUrl = AdminPortal::route('procurement-review');
    $signUrl = AdminPortal::route('digital-signatures.sign-ris', ['filter' => 'for_decision']);
    $issuedUrl = AdminPortal::route('digital-signatures.sign-ris', ['filter' => 'for_cosign']);
@endphp

<nav
    class="mb-5 inline-flex flex-wrap items-center gap-1.5 rounded-[0.9rem] border border-slate-200 bg-white p-1.5 shadow-[0_1px_2px_rgba(15,23,42,0.03)]"
    aria-label="RIS phase"
>
    <a
        href="{{ $acceptUrl }}"
        class="inline-flex items-center gap-2 rounded-[0.65rem] px-3.5 py-2 text-[13px] font-semibold transition {{ $phase === 'accept' ? 'bg-[#0025cc] font-bold text-white' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}"
        title="{{ $pendingAcceptCount }} RIS waiting to be accepted"
    >
        <i data-lucide="inbox" class="h-3.5 w-3.5"></i>
        Pending review
        @if ($pendingAcceptCount > 0)
            <span class="min-w-[1.25rem] rounded-full px-1.5 py-0.5 text-center text-[11px] font-bold tabular-nums leading-none {{ $phase === 'accept' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $pendingAcceptCount }}</span>
        @endif
    </a>
    <a
        href="{{ $signUrl }}"
        class="inline-flex items-center gap-2 rounded-[0.65rem] px-3.5 py-2 text-[13px] font-semibold transition {{ $phase === 'sign' ? 'bg-[#0025cc] font-bold text-white' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}"
        title="{{ $readyToSignCount }} reviewed RIS waiting for a decision"
    >
        <i data-lucide="pen-line" class="h-3.5 w-3.5"></i>
        Reviewed RIS
        @if ($readyToSignCount > 0)
            <span class="min-w-[1.25rem] rounded-full px-1.5 py-0.5 text-center text-[11px] font-bold tabular-nums leading-none {{ $phase === 'sign' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $readyToSignCount }}</span>
        @endif
    </a>
    <a
        href="{{ $issuedUrl }}"
        class="inline-flex items-center gap-2 rounded-[0.65rem] px-3.5 py-2 text-[13px] font-semibold transition {{ $phase === 'issued' ? 'bg-[#0025cc] font-bold text-white' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}"
        title="{{ $awaitingIssuedCount }} President-approved RIS waiting for Issued by"
    >
        <i data-lucide="badge-check" class="h-3.5 w-3.5"></i>
        Awaiting Issued by
        @if ($awaitingIssuedCount > 0)
            <span class="min-w-[1.25rem] rounded-full px-1.5 py-0.5 text-center text-[11px] font-bold tabular-nums leading-none {{ $phase === 'issued' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $awaitingIssuedCount }}</span>
        @endif
    </a>
</nav>
