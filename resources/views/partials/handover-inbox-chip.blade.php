@php
    $handoverInbox = \App\Support\DraftHandover::incomingCounts();
@endphp
@if($handoverInbox['total'] > 0)
    <a
        href="{{ \App\Support\DraftHandover::inboxUrl() }}"
        class="relative inline-flex h-9 items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-3 text-xs font-semibold text-[#0025cc] transition hover:bg-blue-100"
        data-tooltip="Open in the Purchaser portal to accept or decline"
        aria-label="{{ $handoverInbox['total'] }} draft(s) passed to you"
    >
        <i data-lucide="inbox" class="h-4 w-4"></i>
        <span class="hidden sm:inline">Drafts passed to you</span>
        <span class="flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-[#0025cc] px-1 text-[10px] font-bold leading-none text-white">
            {{ $handoverInbox['total'] > 99 ? '99+' : $handoverInbox['total'] }}
        </span>
    </a>
@endif
