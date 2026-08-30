@php
    $attention = \App\Support\AdminAttentionSummary::counts();
    $pendingRis = (int) ($attention['pendingRis'] ?? 0);
    $awaitingCosign = (int) ($attention['awaitingCosign'] ?? 0);
    $amendRis = (int) ($attention['amendRis'] ?? 0);
    $attentionTotal = (int) ($attention['attentionTotal'] ?? 0);
    $showDailyReminder = $attentionTotal > 0;
@endphp

@if ($showDailyReminder)
    <div
        id="adminDailyReminderModal"
        class="fixed inset-0 z-[70] hidden items-center justify-center bg-[#0b1220]/70 p-4 backdrop-blur-[2px]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="adminDailyReminderTitle"
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
                        id="adminDailyReminderTitle"
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
                    id="adminDailyReminderClose"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close reminder"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-6 py-5">
                @if ($pendingRis > 0)
                    <a
                        href="{{ url('/admin/procurement-review') }}"
                        class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-blue-200 hover:bg-blue-50/50"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 transition group-hover:bg-blue-100">
                            <i data-lucide="clipboard-check" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $pendingRis }} pending RIS needing review
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                Purchaser-submitted RIS waiting for Admin review.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-blue-400"></i>
                    </a>
                @endif

                @if ($awaitingCosign > 0)
                    <a
                        href="{{ url('/admin/digital-signatures/sign-ris') }}"
                        class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-amber-200 hover:bg-amber-50/50"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-100 transition group-hover:bg-amber-100">
                            <i data-lucide="pen-line" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $awaitingCosign }} RIS awaiting Admin cosign
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                President-approved RIS that still need Admin signature.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-amber-400"></i>
                    </a>
                @endif

                @if ($amendRis > 0)
                    <a
                        href="{{ url('/admin/procurement-review?filter=all') }}"
                        class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200 transition group-hover:bg-slate-200">
                            <i data-lucide="file-warning" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $amendRis }} amendments / returned
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                RIS marked for revision or rejected and still in the pipeline.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-slate-500"></i>
                    </a>
                @endif
            </div>

            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-6 py-4">
                <button
                    type="button"
                    id="adminDailyReminderDismiss"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                >
                    Remind me tomorrow
                </button>

                @if ($pendingRis > 0)
                    <a
                        href="{{ url('/admin/procurement-review') }}"
                        class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#001fad]"
                    >
                        Review RIS
                    </a>
                @elseif ($awaitingCosign > 0)
                    <a
                        href="{{ url('/admin/digital-signatures/sign-ris') }}"
                        class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#001fad]"
                    >
                        Sign RIS
                    </a>
                @elseif ($amendRis > 0)
                    <a
                        href="{{ url('/admin/procurement-review?filter=all') }}"
                        class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        View all
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif

<script>
(function () {
    const modal = document.getElementById('adminDailyReminderModal');
    if (!modal) {
        window.openAdminDailyReminder = function () {};
        window.dismissAdminDailyReminder = function () {};
        return;
    }

    const sessionKey = @json('adminDailyReminder:session:' . (session('attention_popup_token') ?: 'default'));
    const dateKey = @json('adminDailyReminder:date:v1:' . (Auth::id() ?: 'guest'));
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

    window.openAdminDailyReminder = function () {
        showModal();
        try {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) {
                dropdown.classList.add('hidden');
            }
        } catch (e) {}
    };

    window.dismissAdminDailyReminder = dismissForSession;

    const dismissButton = document.getElementById('adminDailyReminderDismiss');
    const closeButton = document.getElementById('adminDailyReminderClose');

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
