@php
    $urgentReportsNeedingAction = (int) ($attentionUrgentReports ?? 0);
    $nonUrgentReportsNeedingAction = (int) ($attentionNonUrgentReports ?? 0);
    $overdueMaintenance = (int) ($attentionOverdueSchedules ?? 0);
    $overdueBorrowings = (int) ($attentionOverdueBorrows ?? 0);
    $attentionTotal = (int) ($attentionTotal ?? (
        $urgentReportsNeedingAction
        + $nonUrgentReportsNeedingAction
        + $overdueMaintenance
        + $overdueBorrowings
    ));
    $showDailyReminder = $attentionTotal > 0;
@endphp

@if ($showDailyReminder)
    <div
        id="dailyPriorityReminderModal"
        class="fixed inset-0 z-[70] hidden items-center justify-center bg-[#0b1220]/70 p-4 backdrop-blur-[2px]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="dailyPriorityReminderTitle"
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
                        id="dailyPriorityReminderTitle"
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
                    id="dailyPriorityReminderClose"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close reminder"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-6 py-5">
                @if ($urgentReportsNeedingAction > 0)
                    <a
                        href="{{ url('/maintenance/reports/urgent') }}"
                        class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-rose-200 hover:bg-rose-50/50"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-100 transition group-hover:bg-rose-100">
                            <i data-lucide="triangle-alert" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $urgentReportsNeedingAction }} urgent {{ \Illuminate\Support\Str::plural('report', $urgentReportsNeedingAction) }} need action
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                Overdue or still pending — not yet actioned by maintenance.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-rose-400"></i>
                    </a>
                @endif

                @if ($nonUrgentReportsNeedingAction > 0)
                    <a
                        href="{{ url('/maintenance/reports/incoming?urgency=Non-Urgent') }}"
                        class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-sky-200 hover:bg-sky-50/50"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-100 transition group-hover:bg-sky-100">
                            <i data-lucide="clipboard-list" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $nonUrgentReportsNeedingAction }} non-urgent {{ \Illuminate\Support\Str::plural('report', $nonUrgentReportsNeedingAction) }} need action
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                Pending for 5 days or more, or the requested date has arrived.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-sky-400"></i>
                    </a>
                @endif

                @if ($overdueMaintenance > 0)
                    <a
                        href="{{ url('/maintenance/schedules') }}"
                        class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-orange-200 hover:bg-orange-50/50"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100 transition group-hover:bg-orange-100">
                            <i data-lucide="calendar-x-2" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $overdueMaintenance }} overdue {{ \Illuminate\Support\Str::plural('schedule', $overdueMaintenance) }}
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                Maintenance is past the scheduled date and still open.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-orange-400"></i>
                    </a>
                @endif

                @if ($overdueBorrowings > 0)
                    <a
                        href="{{ url('/maintenance/borrowing?status=Overdue') }}"
                        class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-amber-200 hover:bg-amber-50/50"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100 transition group-hover:bg-amber-100">
                            <i data-lucide="clipboard-x" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $overdueBorrowings }} overdue {{ \Illuminate\Support\Str::plural('borrow', $overdueBorrowings) }}
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                Equipment was due back and has not been returned.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-amber-400"></i>
                    </a>
                @endif
            </div>

            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-6 py-4">
                <button
                    type="button"
                    id="dailyPriorityReminderDismiss"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                >
                    Remind me tomorrow
                </button>

                @if ($urgentReportsNeedingAction > 0)
                    <a
                        href="{{ url('/maintenance/reports/urgent') }}"
                        class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#001fad]"
                    >
                        View urgent reports
                    </a>
                @elseif ($nonUrgentReportsNeedingAction > 0)
                    <a
                        href="{{ url('/maintenance/reports/incoming?urgency=Non-Urgent') }}"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        View non-urgent reports
                    </a>
                @elseif ($overdueMaintenance > 0)
                    <a
                        href="{{ url('/maintenance/schedules') }}"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        View schedules
                    </a>
                @elseif ($overdueBorrowings > 0)
                    <a
                        href="{{ url('/maintenance/borrowing?status=Overdue') }}"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        View overdue borrows
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif

<script>
(function () {
    const modal = document.getElementById('dailyPriorityReminderModal');
    if (!modal) {
        window.openMaintenanceDailyReminder = function () {};
        return;
    }

    const sessionKey = @json('mpDailyReminder:session:' . (session('attention_popup_token') ?: 'default'));
    const dateKey = @json('mpDailyReminder:date:v1:' . (Auth::id() ?: 'guest'));
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

        // Every new login / browser session
        if (!sessionSeen) {
            return true;
        }

        // Same long session past midnight (once per new calendar day)
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

    window.openMaintenanceDailyReminder = function () {
        showModal();
        try {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) {
                dropdown.classList.add('hidden');
            }
        } catch (e) {}
    };

    window.dismissMaintenanceDailyReminder = dismissForSession;

    const dismissButton = document.getElementById('dailyPriorityReminderDismiss');
    const closeButton = document.getElementById('dailyPriorityReminderClose');

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
