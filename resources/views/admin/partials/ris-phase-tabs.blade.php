{{-- Shared RIS phase switcher: Pending Accept | Ready to Sign --}}
@php
    use App\Support\AdminPortal;

    $phase = $phase ?? (request()->is('*digital-signatures/sign-ris*') ? 'sign' : 'accept');
    $pendingAcceptCount = (int) ($adminSidebarPendingRis ?? 0);
    $readyToSignCount = (int) ($adminSidebarAwaitingCosign ?? 0);
    $acceptUrl = AdminPortal::route('procurement-review', ['filter' => 'pending']);
    $signUrl = AdminPortal::route('digital-signatures.sign-ris', ['filter' => 'for_decision']);
@endphp

<div class="mb-5 flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3">
    <a
        href="{{ $acceptUrl }}"
        class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition {{ $phase === 'accept' ? 'bg-[#0025cc] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900' }}"
    >
        Pending Accept
        @if ($pendingAcceptCount > 0)
            <span class="rounded-full {{ $phase === 'accept' ? 'bg-white/20 text-white' : 'bg-white text-slate-600' }} px-1.5 py-0.5 text-[11px] tabular-nums">{{ $pendingAcceptCount }}</span>
        @endif
    </a>
    <a
        href="{{ $signUrl }}"
        class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition {{ $phase === 'sign' ? 'bg-[#0025cc] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900' }}"
    >
        Ready to Sign
        @if ($readyToSignCount > 0)
            <span class="rounded-full {{ $phase === 'sign' ? 'bg-white/20 text-white' : 'bg-white text-slate-600' }} px-1.5 py-0.5 text-[11px] tabular-nums">{{ $readyToSignCount }}</span>
        @endif
    </a>
    <p class="ml-auto hidden text-xs text-slate-400 sm:block">
        @if ($phase === 'accept')
            Accept submissions, then decide on Ready to Sign (Direct Approve or Pass to President).
        @else
            Direct Approve, Pass to President, or complete Issued by after President approval.
        @endif
    </p>
</div>
