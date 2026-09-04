{{-- Shared Direct Approve / Forward / Cosign modal shell + Return for Revision modal --}}

<div id="directApproveModal" class="fixed inset-0 z-[12000] hidden">
    <div
        class="absolute inset-0 flex items-center justify-center bg-slate-900/55 p-4 backdrop-blur-[2px]"
        onclick="if (event.target === this) closeDirectApproveModal()"
    >
        <div
            class="relative flex h-auto max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-200/80"
            onclick="event.stopPropagation()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="directApproveModalTitle"
        >
            <button
                type="button"
                onclick="closeDirectApproveModal()"
                class="absolute right-2.5 top-2.5 z-10 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                title="Close"
                aria-label="Close"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="shrink-0 border-b border-slate-100 px-5 pb-2.5 pr-11 pt-3">
                <h3 id="directApproveModalTitle" class="text-base font-semibold tracking-tight text-slate-900">
                    Approve Directly
                </h3>
                <p id="directApproveModalSubtitle" class="mt-0.5 text-sm leading-snug text-slate-500">
                    Sign Issued by on the RIS form, then confirm.
                </p>
            </div>

            <div id="directApproveModalBody" class="flex min-h-0 flex-1 flex-col overflow-hidden bg-slate-50/40">
                <div class="flex flex-1 items-center justify-center gap-3 py-16 text-sm text-slate-500">
                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-slate-200 border-t-slate-700"></div>
                    Loading RIS form...
                </div>
            </div>
        </div>
    </div>
</div>

<div id="amendModal" class="fixed inset-0 z-[12000] hidden">
    <div
        class="absolute inset-0 flex items-center justify-center bg-slate-900/55 p-4 backdrop-blur-[2px]"
        onclick="if (event.target === this) closeAmendModal()"
    >
        <div
            class="relative h-auto w-full max-w-lg rounded-2xl bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-200/80"
            onclick="event.stopPropagation()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="amendModalTitle"
        >
            <button
                type="button"
                onclick="closeAmendModal()"
                class="absolute right-2.5 top-2.5 z-10 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                title="Close"
                aria-label="Close"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="border-b border-slate-100 px-5 pb-2.5 pr-11 pt-3">
                <h3 id="amendModalTitle" class="text-base font-semibold tracking-tight text-slate-900">
                    Return for Revision
                </h3>
                <p class="mt-0.5 text-sm leading-snug text-slate-500">
                    No signature is required. This goes straight back to the Purchaser for revision.
                </p>
            </div>

            <form id="amendForm" method="POST" action="">
                @csrf
                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label for="amend_remarks" class="block text-sm font-medium text-slate-700">
                            Revision remarks <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="amend_remarks"
                            name="remarks"
                            rows="4"
                            required
                            placeholder="Describe what needs to be revised, e.g. incorrect quantities, missing documents, wrong unit cost."
                            class="mt-1.5 block w-full resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                        ></textarea>
                        <p class="mt-1.5 text-xs text-slate-400">
                            These remarks will be visible to the Purchaser when they revise this RIS.
                        </p>
                    </div>

                    <div class="flex gap-2.5 rounded-xl border border-amber-100 bg-amber-50/80 px-3.5 py-2.5">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"></path>
                        </svg>
                        <p class="text-xs leading-relaxed text-amber-900/80">
                            Confirming returns this RIS immediately. The Purchaser must address the remarks before resubmitting.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-3">
                    <button
                        type="button"
                        onclick="closeAmendModal()"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-amber-700"
                    >
                        Return to Purchaser
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        function mountAdminModalsToBody() {
            ['directApproveModal', 'amendModal'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el && el.parentElement !== document.body) {
                    document.body.appendChild(el);
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', mountAdminModalsToBody);
        } else {
            mountAdminModalsToBody();
        }
    })();

    window.openDirectApproveModal = function (risId, mode) {
        var modal = document.getElementById('directApproveModal');
        var body = document.getElementById('directApproveModalBody');
        var title = document.getElementById('directApproveModalTitle');
        var subtitle = document.getElementById('directApproveModalSubtitle');
        if (!modal || !body) return;

        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        var actionMode = 'direct';
        if (mode === 'forward') actionMode = 'forward';
        if (mode === 'cosign') actionMode = 'cosign';

        if (title) {
            title.textContent = actionMode === 'forward'
                ? 'Forward RIS to President'
                : (actionMode === 'cosign' ? 'Sign Issued by' : 'Approve Directly');
        }
        if (subtitle) {
            subtitle.textContent = actionMode === 'forward'
                ? 'Review the RIS form, then forward it to the President. Issued by is signed later on Sign RIS after approval.'
                : (actionMode === 'cosign'
                    ? 'Sign Issued by on the RIS form. Approved by is already filled by the President.'
                    : 'Fill Checked by and Issued by, add a reason (and optional proof), then confirm Admin Approval.');
        }

        body.innerHTML = '<div class="flex flex-1 items-center justify-center gap-3 py-16 text-sm text-slate-500"><div class="h-5 w-5 animate-spin rounded-full border-2 border-slate-200 border-t-slate-700"></div>Loading RIS form...</div>';
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        fetch('/admin/procurement-review/ris/' + risId + '/direct-approve-form?mode=' + encodeURIComponent(actionMode), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error(response.status === 403
                    ? 'This RIS is not available for that action.'
                    : 'Failed to load form');
            }
            return response.text();
        })
        .then(function (html) {
            body.innerHTML = html;
            var dateInput = document.getElementById('da_issued_by_date');
            if (dateInput) {
                dateInput.addEventListener('input', function () {
                    var digits = this.value.replace(/\D/g, '').slice(0, 8);
                    var parts = [];
                    if (digits.length > 0) parts.push(digits.slice(0, 2));
                    if (digits.length > 2) parts.push(digits.slice(2, 4));
                    if (digits.length > 4) parts.push(digits.slice(4, 8));
                    this.value = parts.join('/');
                });
            }
        })
        .catch(function (err) {
            var msg = (err && err.message) ? err.message : 'Failed to load RIS form. Please try again.';
            body.innerHTML = '<div class="px-6 py-16 text-center text-sm text-slate-600">' + msg + '</div>';
        });
    };

    window.closeDirectApproveModal = function () {
        var modal = document.getElementById('directApproveModal');
        var body = document.getElementById('directApproveModalBody');
        if (body) body.innerHTML = '';
        if (modal) modal.classList.add('hidden');
        var amendOpen = document.getElementById('amendModal');
        if (!amendOpen || amendOpen.classList.contains('hidden')) {
            document.body.style.overflow = '';
        }
    };

    window.openAmendModal = function (risId) {
        var modal = document.getElementById('amendModal');
        var form = document.getElementById('amendForm');
        var textarea = document.getElementById('amend_remarks');
        if (!modal || !form || !textarea) return;
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        form.action = '/admin/procurement-review/ris/' + risId + '/reject';
        textarea.value = '';
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(function () { textarea.focus(); }, 200);
    };

    window.closeAmendModal = function () {
        var modal = document.getElementById('amendModal');
        if (modal) modal.classList.add('hidden');
        var da = document.getElementById('directApproveModal');
        if (!da || da.classList.contains('hidden')) {
            document.body.style.overflow = '';
        }
    };
</script>
