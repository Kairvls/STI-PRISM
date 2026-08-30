@php
    $attention = \App\Support\PurchaserAttentionSummary::counts();
    $pendingReplacementRequests = (int) ($attention['pendingReplacementRequests'] ?? 0);
    $availableUrgentReports = (int) ($attention['availableUrgentReports'] ?? 0);
    $risReadyForAtp = (int) ($attention['risReadyForAtp'] ?? 0);
    $atpReadyForRfc = (int) ($attention['atpReadyForRfc'] ?? 0);
    $rfcReadyForRr = (int) ($attention['rfcReadyForRr'] ?? 0);
    $rrReadyForLiq = (int) ($attention['rrReadyForLiq'] ?? 0);
    $attentionTotal = (int) ($attention['attentionTotal'] ?? 0);
    $showDailyReminder = $attentionTotal > 0;
@endphp

@if ($showDailyReminder)
    <div
        id="purchaserDailyReminderModal"
        class="fixed inset-0 z-[70] hidden items-center justify-center bg-[#0b1220]/70 p-4 backdrop-blur-[2px]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="purchaserDailyReminderTitle"
        data-attention-total="{{ $attentionTotal }}"
    >
        <div
            class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.22)]"
            onclick="event.stopPropagation()"
        >
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                        Daily reminder
                    </p>
                    <h2
                        id="purchaserDailyReminderTitle"
                        class="mt-1 text-xl font-semibold tracking-tight text-slate-950"
                    >
                        Attention needed today
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Shows on every login, after midnight while you stay signed in, or anytime from the bell menu.
                    </p>
                </div>
                <button
                    type="button"
                    id="purchaserDailyReminderClose"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close reminder"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-6 py-5">
                @if ($pendingReplacementRequests > 0)
                    <a
                        href="{{ route('purchaser.procurement.replacement-requests', ['status' => 'Pending']) }}"
                        class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-amber-200 hover:bg-amber-50/50"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-100 transition group-hover:bg-amber-100">
                            <i data-lucide="inbox" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $pendingReplacementRequests }} pending {{ \Illuminate\Support\Str::plural('replacement request', $pendingReplacementRequests) }}
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                Waiting for purchaser review.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-amber-400"></i>
                    </a>
                @endif

                @if ($availableUrgentReports > 0)
                    <a
                        href="{{ route('purchaser.reports.urgent') }}"
                        class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-rose-200 hover:bg-rose-50/50"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-100 transition group-hover:bg-rose-100">
                            <i data-lucide="triangle-alert" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $availableUrgentReports }} urgent {{ \Illuminate\Support\Str::plural('report', $availableUrgentReports) }} available
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                Unclaimed urgent reports that may need purchasing action.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-rose-400"></i>
                    </a>
                @endif

                @if ($risReadyForAtp > 0)
                    <a
                        href="{{ route('purchaser.ris.index', ['status' => 'Approved']) }}"
                        class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-blue-200 hover:bg-blue-50/50"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700 ring-1 ring-blue-100 transition group-hover:bg-blue-100">
                            <i data-lucide="file-check-2" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $risReadyForAtp }} approved {{ \Illuminate\Support\Str::plural('RIS', $risReadyForAtp) }} ready for ATP
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                Create Authority to Purchase from approved RIS.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-blue-400"></i>
                    </a>
                @endif

                @if ($atpReadyForRfc > 0)
                    <a
                        href="{{ route('purchaser.rfc.index', ['create' => 1]) }}"
                        class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-indigo-200 hover:bg-indigo-50/50"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100 transition group-hover:bg-indigo-100">
                            <i data-lucide="file-plus-2" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $atpReadyForRfc }} {{ \Illuminate\Support\Str::plural('ATP', $atpReadyForRfc) }} ready for RFC
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                Create Request for Check from approved ATP.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-indigo-400"></i>
                    </a>
                @endif

                @if ($rfcReadyForRr > 0)
                    <a
                        href="{{ route('purchaser.rfc.index', ['status' => 'Approved']) }}"
                        class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-sky-200 hover:bg-sky-50/50"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-700 ring-1 ring-sky-100 transition group-hover:bg-sky-100">
                            <i data-lucide="banknote" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $rfcReadyForRr }} {{ \Illuminate\Support\Str::plural('RFC', $rfcReadyForRr) }} ready for Receiving Report
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                Funds released — create Receiving Report next.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-sky-400"></i>
                    </a>
                @endif

                @if ($rrReadyForLiq > 0)
                    <a
                        href="{{ route('purchaser.rr.index', ['status' => 'Completed']) }}"
                        class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-violet-200 hover:bg-violet-50/50"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700 ring-1 ring-violet-100 transition group-hover:bg-violet-100">
                            <i data-lucide="receipt" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $rrReadyForLiq }} {{ \Illuminate\Support\Str::plural('Receiving Report', $rrReadyForLiq) }} ready for liquidation
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                Create Liquidation from completed Receiving Report.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-violet-400"></i>
                    </a>
                @endif
            </div>

            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-6 py-4">
                <button
                    type="button"
                    id="purchaserDailyReminderDismiss"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                >
                    Remind me tomorrow
                </button>

                @if ($pendingReplacementRequests > 0)
                    <a
                        href="{{ route('purchaser.procurement.replacement-requests', ['status' => 'Pending']) }}"
                        class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#001fad]"
                    >
                        View pending requests
                    </a>
                @elseif ($availableUrgentReports > 0)
                    <a
                        href="{{ route('purchaser.reports.urgent') }}"
                        class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#001fad]"
                    >
                        View urgent reports
                    </a>
                @elseif ($risReadyForAtp > 0)
                    <a
                        href="{{ route('purchaser.ris.index', ['status' => 'Approved']) }}"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        Open approved RIS
                    </a>
                @elseif ($atpReadyForRfc > 0)
                    <a
                        href="{{ route('purchaser.rfc.index', ['create' => 1]) }}"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        Create RFC
                    </a>
                @elseif ($rfcReadyForRr > 0)
                    <a
                        href="{{ route('purchaser.rfc.index', ['status' => 'Approved']) }}"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        Open approved RFC
                    </a>
                @elseif ($rrReadyForLiq > 0)
                    <a
                        href="{{ route('purchaser.rr.index', ['status' => 'Completed']) }}"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        Open completed RR
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif

<script>
(function () {
    const modal = document.getElementById('purchaserDailyReminderModal');
    if (!modal) {
        window.openPurchaserDailyReminder = function () {};
        return;
    }

    const sessionKey = @json('purchaserDailyReminder:session:' . (session('attention_popup_token') ?: 'default'));
    const dateKey = @json('purchaserDailyReminder:date:v1:' . (Auth::id() ?: 'guest'));
    const todayKey = () => new Date().toISOString().slice(0, 10);
    const dayShownKey = () => sessionKey + ':day:' + todayKey();

    function isVisible() {
        return modal.classList.contains('flex') && !modal.classList.contains('hidden');
    }

    function showModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide && typeof lucide.createIcons === 'function') {
            lucide.createIcons();
        }
    }

    function hideModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function markSessionSeen() {
        try {
            sessionStorage.setItem(sessionKey, '1');
            sessionStorage.setItem(dayShownKey(), '1');
        } catch (e) {}
    }

    function acknowledgeToday() {
        try {
            localStorage.setItem(dateKey, todayKey());
        } catch (e) {}
        markSessionSeen();
    }

    function shouldAutoShow() {
        let sessionSeen = false;
        let shownForToday = false;
        let lastDate = null;

        try {
            sessionSeen = sessionStorage.getItem(sessionKey) === '1';
            shownForToday = sessionStorage.getItem(dayShownKey()) === '1';
            lastDate = localStorage.getItem(dateKey);
        } catch (e) {
            return true;
        }

        if (!sessionSeen) {
            return true;
        }

        if ((!lastDate || lastDate < todayKey()) && !shownForToday) {
            return true;
        }

        return false;
    }

    function dismissForSession() {
        acknowledgeToday();
        hideModal();
    }

    function snoozeUntilTomorrow() {
        acknowledgeToday();
        hideModal();
    }

    window.openPurchaserDailyReminder = function () {
        showModal();
        try {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) {
                dropdown.classList.add('hidden');
            }
        } catch (e) {}
    };

    window.dismissPurchaserDailyReminder = dismissForSession;

    const dismissButton = document.getElementById('purchaserDailyReminderDismiss');
    const closeButton = document.getElementById('purchaserDailyReminderClose');

    if (dismissButton) {
        dismissButton.addEventListener('click', snoozeUntilTomorrow);
    }

    if (closeButton) {
        closeButton.addEventListener('click', dismissForSession);
    }

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            dismissForSession();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && isVisible()) {
            dismissForSession();
        }
    });

    function maybeAutoShow() {
        if (isVisible()) {
            return;
        }
        if (shouldAutoShow()) {
            showModal();
            markSessionSeen();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', maybeAutoShow);
    } else {
        maybeAutoShow();
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            maybeAutoShow();
        }
    });

    window.addEventListener('focus', maybeAutoShow);
    setInterval(maybeAutoShow, 5 * 60 * 1000);
})();
</script>
