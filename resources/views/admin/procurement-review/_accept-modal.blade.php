{{-- Accept procurement request confirmation modal (single or bulk) --}}

<div id="acceptRisModal" class="fixed inset-0 z-[12000] hidden">
    <div
        class="absolute inset-0 flex items-center justify-center bg-slate-900/55 p-4 backdrop-blur-[2px]"
        onclick="if (event.target === this) closeAcceptRisModal()"
    >
        <div
            class="relative h-auto w-full max-w-lg rounded-2xl bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-200/80"
            onclick="event.stopPropagation()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="acceptRisModalTitle"
        >
            <button
                type="button"
                onclick="closeAcceptRisModal()"
                class="absolute right-2.5 top-2.5 z-10 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                title="Close"
                aria-label="Close"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="border-b border-slate-100 px-5 pb-2.5 pr-11 pt-3">
                <h3 id="acceptRisModalTitle" class="text-base font-semibold tracking-tight text-slate-900">
                    Accept procurement request?
                </h3>
                <p id="acceptRisModalSubtitle" class="mt-0.5 text-sm leading-snug text-slate-500">
                    This moves the RIS to Sign RIS for Forward, Approve Directly, or Return.
                </p>
            </div>

            <form id="acceptRisForm" method="POST" action="">
                @csrf
                <div id="acceptRisBulkIds" class="hidden"></div>

                <div class="space-y-4 px-5 py-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            Reference
                        </p>
                        <p id="acceptRisRef" class="mt-1 text-sm font-semibold text-slate-900">
                            —
                        </p>
                        <p id="acceptRisDetail" class="mt-0.5 text-xs text-slate-500">
                            —
                        </p>
                    </div>

                    <div class="flex gap-2.5 rounded-xl border border-sky-100 bg-sky-50/80 px-3.5 py-2.5">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"></path>
                        </svg>
                        <p class="text-xs leading-relaxed text-sky-900/80">
                            Accepting does not approve the purchase. You will decide the path on Sign RIS next.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-3">
                    <button
                        type="button"
                        onclick="closeAcceptRisModal()"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        id="acceptRisSubmitBtn"
                        class="rounded-xl bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800"
                    >
                        Accept &amp; continue
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var bulkAcceptUrl = @js(route('admin.procurement-review.ris.accept-bulk'));

    function prepareAcceptModalShell() {
        var modal = document.getElementById('acceptRisModal');
        if (!modal) return null;

        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        return modal;
    }

    function fillAcceptModalCopy(title, subtitle, refLabel, detailLabel, submitLabel) {
        var titleEl = document.getElementById('acceptRisModalTitle');
        var subtitleEl = document.getElementById('acceptRisModalSubtitle');
        var refEl = document.getElementById('acceptRisRef');
        var detailEl = document.getElementById('acceptRisDetail');
        var submitBtn = document.getElementById('acceptRisSubmitBtn');

        if (titleEl) titleEl.textContent = title;
        if (subtitleEl) subtitleEl.textContent = subtitle;
        if (refEl) refEl.textContent = refLabel;
        if (detailEl) detailEl.textContent = detailLabel;
        if (submitBtn) submitBtn.textContent = submitLabel;
    }

    window.openAcceptRisModal = function (risId, refLabel, detailLabel) {
        var modal = prepareAcceptModalShell();
        var form = document.getElementById('acceptRisForm');
        var bulkIds = document.getElementById('acceptRisBulkIds');
        if (!modal || !form) return;

        if (bulkIds) bulkIds.innerHTML = '';

        form.action = '/admin/procurement-review/ris/' + encodeURIComponent(risId) + '/accept';
        fillAcceptModalCopy(
            'Accept procurement request?',
            'This moves the RIS to Sign RIS for Forward, Approve Directly, or Return.',
            refLabel || ('RIS-' + risId),
            detailLabel || 'Ready to accept for Sign RIS.',
            'Accept & continue'
        );

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        var submitBtn = document.getElementById('acceptRisSubmitBtn');
        if (submitBtn) {
            setTimeout(function () { submitBtn.focus(); }, 150);
        }
    };

    window.openBulkAcceptRisModal = function (ids) {
        var modal = prepareAcceptModalShell();
        var form = document.getElementById('acceptRisForm');
        var bulkIds = document.getElementById('acceptRisBulkIds');
        if (!modal || !form || !bulkIds) return;

        var selected = Array.isArray(ids)
            ? ids.map(function (id) { return String(id); }).filter(Boolean)
            : [];

        if (!selected.length) return;

        bulkIds.innerHTML = '';
        selected.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ris_ids[]';
            input.value = id;
            bulkIds.appendChild(input);
        });

        form.action = bulkAcceptUrl;

        var count = selected.length;
        fillAcceptModalCopy(
            count === 1 ? 'Accept procurement request?' : 'Accept ' + count + ' procurement requests?',
            'Selected requests will move to Sign RIS for Forward, Approve Directly, or Return.',
            count === 1 ? ('1 request selected') : (count + ' requests selected'),
            'Ready to accept for Sign RIS.',
            count === 1 ? 'Accept & continue' : ('Accept ' + count + ' & continue')
        );

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        var submitBtn = document.getElementById('acceptRisSubmitBtn');
        if (submitBtn) {
            setTimeout(function () { submitBtn.focus(); }, 150);
        }
    };

    window.closeAcceptRisModal = function () {
        var modal = document.getElementById('acceptRisModal');
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        var modal = document.getElementById('acceptRisModal');
        if (modal && !modal.classList.contains('hidden')) {
            closeAcceptRisModal();
        }
    });
})();
</script>
