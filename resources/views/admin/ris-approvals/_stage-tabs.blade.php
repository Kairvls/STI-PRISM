{{-- Shared stage tabs for the RIS Approvals hub --}}
@php
    $attention = \App\Support\AdminAttentionSummary::counts();
    $incomingCount = (int) ($attention['pendingRis'] ?? 0);
    $issuedByCount = (int) ($attention['awaitingCosign'] ?? 0);
    $activeStage = $activeStage ?? 'incoming';
    $stages = [
        [
            'key' => 'incoming',
            'label' => 'Incoming',
            'href' => url('/admin/procurement-review'),
            'count' => $incomingCount,
            'title' => 'Review new RIS from the Purchaser',
        ],
        [
            'key' => 'issued_by',
            'label' => 'Awaiting Issued by',
            'href' => url('/admin/digital-signatures/sign-ris'),
            'count' => $issuedByCount,
            'title' => 'Sign Issued by on President-approved RIS',
        ],
        [
            'key' => 'history',
            'label' => 'History',
            'href' => url('/admin/digital-signatures/history'),
            'count' => null,
            'title' => 'Browse all RIS signature outcomes',
        ],
    ];
@endphp

<nav
    class="rounded-2xl border border-slate-200 bg-white p-1.5 shadow-[0_1px_2px_rgba(15,23,42,0.03)]"
    aria-label="RIS Approvals stages"
>
    <div class="grid grid-cols-1 gap-1 sm:grid-cols-3">
        @foreach ($stages as $stage)
            @php $isActive = $activeStage === $stage['key']; @endphp
            <a
                href="{{ $stage['href'] }}"
                title="{{ $stage['title'] }}"
                aria-current="{{ $isActive ? 'page' : 'false' }}"
                class="flex items-center justify-between gap-3 rounded-xl px-4 py-3 transition
                    {{ $isActive
                        ? 'bg-slate-900 text-white shadow-sm'
                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
            >
                <span class="text-sm font-semibold tracking-tight">
                    {{ $stage['label'] }}
                </span>

                @if (!is_null($stage['count']))
                    <span
                        class="inline-flex min-w-[1.75rem] items-center justify-center rounded-lg px-2 py-0.5 text-xs font-semibold
                            {{ $isActive
                                ? 'bg-white/15 text-white'
                                : ($stage['count'] > 0 ? 'bg-sky-50 text-sky-700' : 'bg-slate-100 text-slate-500') }}"
                    >
                        {{ $stage['count'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</nav>
