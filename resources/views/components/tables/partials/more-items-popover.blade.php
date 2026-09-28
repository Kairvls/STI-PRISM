{{--
    "Monitor +1 more" label whose "+N more" pill lists every item on the ticket.
    Params: $label (e.g. "Monitor +1 more"), $items (report items), optional $textClass.
    The list opens on hover and toggles on click (touch screens).
--}}
@php
    $moreParts = \App\Support\ReportItems::splitMoreLabel($label ?? '');
    $moreItems = collect($items ?? []);
    $moreStatusPills = [
        'Pending' => 'bg-amber-50 text-amber-700',
        'Processing' => 'bg-sky-50 text-sky-700',
        'Resolved' => 'bg-emerald-50 text-emerald-700',
        'Rejected' => 'bg-rose-50 text-rose-700',
        'For Replacement' => 'bg-orange-50 text-orange-700',
    ];
@endphp

@once
<style>
    .report-more-panel {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        z-index: 60;
        min-width: 210px;
        max-width: 280px;
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

    /* Bridges the gap so the list stays open while the pointer moves onto it */
    .report-more-panel::before {
        content: "";
        position: absolute;
        top: -8px;
        left: 0;
        right: 0;
        height: 8px;
    }

    .report-more-pop:not(.is-dismissed):hover .report-more-panel,
    .report-more-pop.is-open .report-more-panel {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
</style>
<script>
    window.toggleReportMorePopover = function (button, event) {
        event.preventDefault();
        event.stopPropagation();
        const wrap = button.closest('[data-report-more]');
        if (!wrap) return;
        const willOpen = !wrap.classList.contains('is-open');
        document.querySelectorAll('[data-report-more].is-open').forEach((other) => {
            other.classList.remove('is-open');
            other.querySelector('[data-report-more-btn]')?.setAttribute('aria-expanded', 'false');
        });
        wrap.classList.toggle('is-open', willOpen);
        // Closing by click also stops hover from reopening it until the pointer leaves.
        wrap.classList.toggle('is-dismissed', !willOpen);
        button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        if (!wrap.dataset.leaveBound) {
            wrap.dataset.leaveBound = '1';
            wrap.addEventListener('mouseleave', () => wrap.classList.remove('is-dismissed'));
        }
    };

    // Capture phase: the ticket modal stops click bubbling inside its panel.
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-report-more]')) return;
        document.querySelectorAll('[data-report-more].is-open').forEach((wrap) => {
            wrap.classList.remove('is-open');
            wrap.querySelector('[data-report-more-btn]')?.setAttribute('aria-expanded', 'false');
        });
    }, true);
</script>
@endonce

<span class="inline-flex min-w-0 max-w-full items-center gap-1.5">
    <span class="truncate {{ $textClass ?? '' }}">{{ $moreParts['primary'] }}</span>
    @if ($moreParts['more'] > 0)
        @if ($moreItems->count() > 1)
            <span class="report-more-pop relative inline-flex shrink-0" data-report-more>
                <button
                    type="button"
                    data-report-more-btn
                    aria-expanded="false"
                    onclick="toggleReportMorePopover(this, event)"
                    class="inline-flex items-center rounded-md bg-[#0037C7]/10 px-1.5 py-0.5 text-[10px] font-bold leading-4 text-[#0037C7] ring-1 ring-[#0037C7]/20 transition hover:bg-[#0037C7] hover:text-white"
                >
                    +{{ $moreParts['more'] }} more
                </button>
                <span class="report-more-panel" role="tooltip">
                    <span class="block px-3 pb-1.5 pt-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        All equipment · {{ $moreItems->count() }}
                    </span>
                    @foreach ($moreItems as $moreItem)
                        @php
                            $moreItemStatus = (string) ($moreItem->report_item_status ?? 'Pending');
                            $moreItemDetail = collect([
                                ($moreItem->equipment_asset_tag ?? null) ?: ($moreItem->equipment_serial_number ?? null),
                                $moreItem->report_item_suggested_issue ?? null,
                            ])->filter()->implode(' · ');
                        @endphp
                        <span class="flex items-center justify-between gap-3 px-3 py-1.5">
                            <span class="min-w-0">
                                <span class="block truncate text-xs font-medium text-slate-700">
                                    {{ \App\Support\ReportItems::displayName($moreItem) }}
                                </span>
                                @if ($moreItemDetail !== '')
                                    <span class="block truncate text-[10.5px] text-slate-400" data-tooltip="{{ $moreItemDetail }}">{{ $moreItemDetail }}</span>
                                @endif
                            </span>
                            <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-medium {{ $moreStatusPills[$moreItemStatus] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $moreItemStatus }}
                            </span>
                        </span>
                    @endforeach
                </span>
            </span>
        @else
            <span class="inline-flex shrink-0 items-center rounded-md bg-[#0037C7]/10 px-1.5 py-0.5 text-[10px] font-bold leading-4 text-[#0037C7] ring-1 ring-[#0037C7]/20">
                +{{ $moreParts['more'] }} more
            </span>
        @endif
    @endif
</span>
