{{--
    Multi-item borrow slip cell: "Monitor +1 more" with hover/click tooltip of uniqueness.
    Params: $items (collection of borrow rows with equipment_name / equipment_asset_tag / status).
--}}
@php
    $slipItems = collect($items ?? [])->values();
    $primary = $slipItems->first();
    $primaryName = trim((string) ($primary->equipment_name ?? 'Equipment'));
    $moreCount = max(0, $slipItems->count() - 1);
    $slipNo = trim((string) ($primary->borrowing_transaction_no ?? ''));
    $eventName = trim((string) ($primary->borrowing_event_name ?? ''));
    $eventType = trim((string) ($primary->borrowing_event_type ?? ''));
    $assetTag = trim((string) ($primary->equipment_asset_tag ?? ''));
    $statusPills = [
        'Borrowed' => 'bg-sky-50 text-sky-700',
        'Returned' => 'bg-emerald-50 text-emerald-700',
        'Overdue' => 'bg-rose-50 text-rose-700',
    ];
@endphp

@once
<style>
    .borrow-more-panel {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        z-index: 70;
        min-width: 240px;
        max-width: 300px;
        padding: 4px 0 6px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-3px);
        transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s;
    }

    .borrow-more-panel::before {
        content: "";
        position: absolute;
        top: -8px;
        left: 0;
        right: 0;
        height: 8px;
    }

    .borrow-more-pop:not(.is-dismissed):hover .borrow-more-panel,
    .borrow-more-pop.is-open .borrow-more-panel {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
</style>
<script>
    window.toggleBorrowMorePopover = function (button, event) {
        event.preventDefault();
        event.stopPropagation();
        const wrap = button.closest('[data-borrow-more]');
        if (!wrap) return;
        const willOpen = !wrap.classList.contains('is-open');
        document.querySelectorAll('[data-borrow-more].is-open').forEach((other) => {
            other.classList.remove('is-open');
            other.querySelector('[data-borrow-more-btn]')?.setAttribute('aria-expanded', 'false');
        });
        wrap.classList.toggle('is-open', willOpen);
        wrap.classList.toggle('is-dismissed', !willOpen);
        button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        if (!wrap.dataset.leaveBound) {
            wrap.dataset.leaveBound = '1';
            wrap.addEventListener('mouseleave', () => wrap.classList.remove('is-dismissed'));
        }
    };

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-borrow-more]')) return;
        document.querySelectorAll('[data-borrow-more].is-open').forEach((wrap) => {
            wrap.classList.remove('is-open');
            wrap.querySelector('[data-borrow-more-btn]')?.setAttribute('aria-expanded', 'false');
        });
    }, true);
</script>
@endonce

<div class="flex items-center gap-3">
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400">
        <i data-lucide="package" class="h-4 w-4"></i>
    </div>

    <div class="min-w-0">
        <div class="flex min-w-0 max-w-[240px] items-center gap-1.5">
            <a
                href="{{ route('admin.borrowing.index', ['record' => (int) ($primary->borrowing_record_id ?? 0)]) }}"
                class="truncate text-sm font-semibold text-slate-800 hover:text-[#0025cc] hover:underline"
                title="{{ $primaryName }}"
            >{{ $primaryName }}</a>

            @if ($moreCount > 0)
                <span class="borrow-more-pop relative inline-flex shrink-0" data-borrow-more>
                    <button
                        type="button"
                        data-borrow-more-btn
                        aria-expanded="false"
                        onclick="toggleBorrowMorePopover(this, event)"
                        class="inline-flex items-center rounded-md bg-[#0037C7]/10 px-1.5 py-0.5 text-[10px] font-bold leading-4 text-[#0037C7] ring-1 ring-[#0037C7]/20 transition hover:bg-[#0037C7] hover:text-white"
                    >
                        +{{ $moreCount }} more
                    </button>
                    <span class="borrow-more-panel" role="tooltip">
                        <span class="block px-3 pb-1.5 pt-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            Borrowed equipment · {{ $slipItems->count() }}
                        </span>
                        @foreach ($slipItems as $item)
                            @php
                                $itemName = trim((string) ($item->equipment_name ?? 'Equipment'));
                                $itemTag = trim((string) ($item->equipment_asset_tag ?? ''));
                                $itemStatus = (string) ($item->borrowing_status ?? '');
                                $itemId = (int) ($item->borrowing_record_id ?? 0);
                            @endphp
                            <a
                                href="{{ route('admin.borrowing.index', ['record' => $itemId]) }}"
                                class="flex items-center justify-between gap-3 px-3 py-1.5 transition hover:bg-slate-50"
                            >
                                <span class="min-w-0">
                                    <span class="block truncate text-xs font-semibold text-slate-800">{{ $itemName }}</span>
                                    <span class="block truncate font-mono text-[10.5px] text-slate-400">
                                        {{ $itemTag !== '' ? $itemTag : 'No asset tag' }}
                                    </span>
                                </span>
                                @if ($itemStatus !== '')
                                    <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-medium {{ $statusPills[$itemStatus] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $itemStatus }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                        @if ($slipNo !== '')
                            <a
                                href="{{ route('admin.borrowing.index', ['slip' => $slipNo]) }}"
                                class="mt-1 block border-t border-slate-100 px-3 pt-2 text-[11px] font-semibold text-[#0025cc] hover:underline"
                            >
                                Open full slip →
                            </a>
                        @endif
                    </span>
                </span>
            @endif
        </div>

        @if ($slipNo !== '')
            <p class="mt-0.5 max-w-[240px] truncate">
                <a
                    href="{{ route('admin.borrowing.index', ['slip' => $slipNo]) }}"
                    class="font-mono text-xs font-medium tracking-wide text-slate-500 hover:text-[#0025cc] hover:underline"
                    title="Filter by slip {{ $slipNo }}"
                >{{ $slipNo }}</a>
                @if ($moreCount > 0)
                    <span class="text-xs text-slate-400"> · {{ $slipItems->count() }} items</span>
                @endif
            </p>
        @endif

        <p class="mt-0.5 max-w-[240px] truncate text-xs text-slate-400">
            @if ($moreCount > 0 && $slipNo === '')
                {{ $slipItems->count() }} items
            @else
                {{ $assetTag !== '' ? $assetTag : 'No asset tag' }}
            @endif
            @if (!empty($primary->borrowed_from_room_name))
                · from {{ $primary->borrowed_from_room_name }}
            @endif
        </p>

        @if ($eventName !== '')
            <p
                class="mt-0.5 max-w-[240px] truncate text-[11px] font-medium text-indigo-600"
                title="{{ $eventName }}{{ $eventType !== '' ? ' · '.$eventType : '' }}"
            >
                {{ $eventName }}
            </p>
        @endif
    </div>
</div>
