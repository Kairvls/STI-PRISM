@auth
<div
    id="idleSessionWarningModal"
    class="fixed inset-0 z-[120] hidden items-center justify-center bg-[#0b1220]/75 p-4 backdrop-blur-[2px]"
    role="dialog"
    aria-modal="true"
    aria-labelledby="idleSessionWarningTitle"
>
    <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.25)]">
        <div class="border-b border-slate-100 px-6 py-5">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                Session security
            </p>
            <h2
                id="idleSessionWarningTitle"
                class="mt-1 text-xl font-semibold tracking-tight text-slate-950"
            >
                Still there?
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                No activity detected for 30 minutes. You will be signed out in
                <span id="idleSessionCountdown" class="font-semibold text-slate-900">60</span>
                seconds to protect this account.
            </p>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2 bg-slate-50/60 px-6 py-4">
            <button
                type="button"
                id="idleSessionLogoutNow"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
            >
                Sign out now
            </button>
            <button
                type="button"
                id="idleSessionStaySignedIn"
                class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#001fad]"
            >
                Stay signed in
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const IDLE_MS = 30 * 60 * 1000;
    const WARN_MS = 60 * 1000;
    const ACTIVITY_EVENTS = [
        'mousemove',
        'mousedown',
        'keydown',
        'scroll',
        'touchstart',
        'click',
        'wheel',
    ];

    const modal = document.getElementById('idleSessionWarningModal');
    const countdownEl = document.getElementById('idleSessionCountdown');
    const stayBtn = document.getElementById('idleSessionStaySignedIn');
    const logoutBtn = document.getElementById('idleSessionLogoutNow');

    if (!modal || !countdownEl || !stayBtn || !logoutBtn) {
        return;
    }

    let idleTimer = null;
    let warnTimer = null;
    let countdownTimer = null;
    let secondsLeft = 60;
    let warningActive = false;

    function logoutNow() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = @json(route('logout'));
        form.style.display = 'none';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
        form.appendChild(csrf);

        document.body.appendChild(form);
        form.submit();
    }

    function clearWarnTimers() {
        if (warnTimer) {
            clearTimeout(warnTimer);
            warnTimer = null;
        }
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    function hideWarning() {
        warningActive = false;
        clearWarnTimers();
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function showWarning() {
        if (warningActive) {
            return;
        }

        warningActive = true;
        secondsLeft = 60;
        countdownEl.textContent = String(secondsLeft);
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (window.lucide && typeof lucide.createIcons === 'function') {
            lucide.createIcons();
        }

        countdownTimer = setInterval(function () {
            secondsLeft -= 1;
            countdownEl.textContent = String(Math.max(secondsLeft, 0));
            if (secondsLeft <= 0) {
                clearWarnTimers();
                logoutNow();
            }
        }, 1000);

        warnTimer = setTimeout(logoutNow, WARN_MS);
    }

    function resetIdleTimer() {
        if (warningActive) {
            return;
        }

        if (idleTimer) {
            clearTimeout(idleTimer);
        }

        idleTimer = setTimeout(showWarning, IDLE_MS);
    }

    function onActivity() {
        if (warningActive) {
            return;
        }
        resetIdleTimer();
    }

    ACTIVITY_EVENTS.forEach(function (eventName) {
        document.addEventListener(eventName, onActivity, { passive: true });
    });

    stayBtn.addEventListener('click', function () {
        hideWarning();
        resetIdleTimer();
    });

    logoutBtn.addEventListener('click', logoutNow);

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && !warningActive) {
            resetIdleTimer();
        }
    });

    resetIdleTimer();
})();
</script>
@endauth
