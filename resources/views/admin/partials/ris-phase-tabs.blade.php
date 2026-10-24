{{-- Shared RIS phase switcher: Pending Accept | Ready to Sign --}}
@php
    use App\Support\AdminPortal;

    $phase = $phase ?? (request()->is('*digital-signatures/sign-ris*') ? 'sign' : 'accept');
    $pendingAcceptCount = (int) ($adminSidebarPendingRis ?? 0);
    $readyToSignCount = (int) ($adminSidebarAwaitingCosign ?? 0);
    $acceptUrl = AdminPortal::route('procurement-review', ['filter' => 'pending']);
    $signUrl = AdminPortal::route('digital-signatures.sign-ris', ['filter' => 'for_decision']);
@endphp

<nav
    class="mb-5 inline-flex flex-wrap items-center gap-1.5 rounded-[0.9rem] border border-slate-200 bg-white p-1.5 shadow-[0_1px_2px_rgba(15,23,42,0.03)]"
    aria-label="RIS phase"
>
    <a
        href="{{ $acceptUrl }}"
        class="inline-flex items-center gap-2 rounded-[0.65rem] px-3.5 py-2 text-[13px] font-semibold transition {{ $phase === 'accept' ? 'bg-[#0025cc] font-bold text-white' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}"
    >
        <i data-lucide="inbox" class="h-3.5 w-3.5"></i>
        Pending Accept
        @if ($pendingAcceptCount > 0)
            <span class="rounded-full px-1.5 py-0.5 text-[11px] tabular-nums {{ $phase === 'accept' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $pendingAcceptCount }}</span>
        @endif
    </a>
    <a
        href="{{ $signUrl }}"
        class="inline-flex items-center gap-2 rounded-[0.65rem] px-3.5 py-2 text-[13px] font-semibold transition {{ $phase === 'sign' ? 'bg-[#0025cc] font-bold text-white' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}"
    >
        <i data-lucide="pen-line" class="h-3.5 w-3.5"></i>
        Ready to Sign
        @if ($readyToSignCount > 0)
            <span class="rounded-full px-1.5 py-0.5 text-[11px] tabular-nums {{ $phase === 'sign' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $readyToSignCount }}</span>
        @endif
    </a>
</nav>
