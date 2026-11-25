{{-- Shared Direct Approve / Forward / Cosign modal shell + Return for Revision modal --}}

<div id="directApproveModal" class="fixed inset-0 z-[12000] hidden">
    <div
        class="absolute inset-0 flex items-center justify-center bg-slate-900/55 p-4 backdrop-blur-[2px]"
        onclick="if (event.target === this && !window._adminDaFileDialogOpen) closeDirectApproveModal()"
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
                onclick="if (!window._adminDaFileDialogOpen) closeDirectApproveModal()"
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

            <div id="directApproveModalBody" class="flex min-h-0 flex-1 flex-col overflow-hidden bg-slate-50/40"></div>
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

            <form id="amendForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                <div class="max-h-[65vh] space-y-4 overflow-y-auto px-5 py-4">
                    <div>
                        @include('partials.assign-reviewer-field', [
                            'role' => \App\Support\WorkflowNotifier::ROLE_PURCHASER,
                            'label' => 'Purchaser',
                            'inputName' => 'assigned_purchaser_id',
                            'hint' => 'Only the selected Purchaser will receive this for revision.',
                        ])
                    </div>

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
                            These remarks and any images will be visible to the Purchaser when they revise this RIS.
                        </p>
                    </div>

                    @include('admin.partials.revision-image-picker', ['pickerId' => 'amendRevisionImages'])

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

{{-- Hard Reject (remarks + images) --}}
<div id="finalRejectModal" class="fixed inset-0 z-[12000] hidden">
    <div
        class="absolute inset-0 flex items-center justify-center bg-slate-900/55 p-4 backdrop-blur-[2px]"
        onclick="if (event.target === this) closeFinalRejectModal()"
    >
        <div
            class="relative h-auto w-full max-w-lg rounded-2xl bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-200/80"
            onclick="event.stopPropagation()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="finalRejectModalTitle"
        >
            <button
                type="button"
                onclick="closeFinalRejectModal()"
                class="absolute right-2.5 top-2.5 z-10 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                title="Close"
                aria-label="Close"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="border-b border-slate-100 px-5 pb-2.5 pr-11 pt-3">
                <h3 id="finalRejectModalTitle" class="text-base font-semibold tracking-tight text-slate-900">
                    Reject RIS
                </h3>
                <p class="mt-0.5 text-sm leading-snug text-slate-500">
                    This is a hard rejection. The Purchaser cannot resubmit this RIS into approval.
                </p>
            </div>

            <form id="finalRejectForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                <div class="max-h-[65vh] space-y-4 overflow-y-auto px-5 py-4">
                    <div>
                        <label for="final_reject_remarks" class="block text-sm font-medium text-slate-700">
                            Rejection remarks <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="final_reject_remarks"
                            name="remarks"
                            rows="4"
                            required
                            placeholder="Explain why this RIS is rejected."
                            class="mt-1.5 block w-full resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                        ></textarea>
                    </div>

                    @include('admin.partials.revision-image-picker', ['pickerId' => 'finalRejectImages'])

                    <div class="flex gap-2.5 rounded-xl border border-rose-100 bg-rose-50/80 px-3.5 py-2.5">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
                        </svg>
                        <p class="text-xs leading-relaxed text-rose-900/80">
                            Confirming marks this RIS as Rejected. Remarks and images are stored for the Purchaser to see.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-3">
                    <button
                        type="button"
                        onclick="closeFinalRejectModal()"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-rose-700"
                    >
                        Reject RIS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window._adminDaFileDialogOpen = false;

    (function () {
        function mountAdminModalsToBody() {
            ['directApproveModal', 'amendModal', 'finalRejectModal'].forEach(function (id) {
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

    window.markAdminDaFileDialog = function (isOpen) {
        window._adminDaFileDialogOpen = !!isOpen;
        if (isOpen) {
            window.setTimeout(function () {
                window._adminDaFileDialogOpen = false;
            }, 1500);
        }
    };

    window.openCoSignModal = function (risId) {
        window.openDirectApproveModal(risId, 'cosign');
    };

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
        var isForwardBack = mode === 'forward-back';
        if (mode === 'forward' || isForwardBack) actionMode = 'forward';
        if (mode === 'cosign') actionMode = 'cosign';

        if (title) {
            title.textContent = actionMode === 'forward'
                ? (isForwardBack ? 'Re-forward RIS & ATP to President' : 'Forward RIS & ATP to President')
                : (actionMode === 'cosign' ? 'Sign Issued by' : 'Approve Directly');
        }
        if (subtitle) {
            subtitle.hidden = actionMode === 'forward';
            subtitle.textContent = actionMode === 'forward'
                ? ''
                : (actionMode === 'cosign'
                    ? 'Sign Issued by on the RIS form. Approved by is already filled by the President.'
                    : 'Sign the RIS & ATP(s), add a reason (and optional proof), then approve and send to the Purchaser.');
        }

        var reqId = (window._adminDaFetchSeq = (window._adminDaFetchSeq || 0) + 1);
        document.body.style.cursor = 'wait';

        fetch(@js(\App\Support\AdminPortal::url('procurement-review/ris')) + '/' + risId + '/direct-approve-form?mode=' + encodeURIComponent(actionMode), {
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
            if (reqId !== window._adminDaFetchSeq) return;
            body.innerHTML = html;
            Array.prototype.slice.call(body.querySelectorAll('script')).forEach(function (oldScript) {
                var script = document.createElement('script');
                script.textContent = oldScript.textContent;
                oldScript.parentNode.replaceChild(script, oldScript);
            });
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        })
        .catch(function (err) {
            if (reqId !== window._adminDaFetchSeq) return;
            var msg = (err && err.message) ? err.message : 'Failed to load RIS form. Please try again.';
            body.innerHTML = '<div class="px-6 py-16 text-center text-sm text-slate-600">' + msg + '</div>';
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        })
        .finally(function () {
            if (reqId === window._adminDaFetchSeq) {
                document.body.style.cursor = '';
            }
        });
    };

    window.closeDirectApproveModal = function () {
        if (window._adminDaFileDialogOpen) return;
        ['adminDaSignPadModal', 'adminDaNameSigModal', 'adminDaDeleteSigModal', 'adminDaNotice', 'adminForwardConfirmModal'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && el.parentElement === document.body) {
                el.remove();
            }
        });
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
        form.action = @js(\App\Support\AdminPortal::url('procurement-review/ris')) + '/' + risId + '/reject';
        textarea.value = '';
        if (typeof window.resetRevisionImagePicker === 'function') {
            window.resetRevisionImagePicker('amendRevisionImages');
        }
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(function () { textarea.focus(); }, 200);
    };

    window.closeAmendModal = function () {
        var modal = document.getElementById('amendModal');
        if (modal) modal.classList.add('hidden');
        var da = document.getElementById('directApproveModal');
        var rejectOpen = document.getElementById('finalRejectModal');
        if ((!da || da.classList.contains('hidden')) && (!rejectOpen || rejectOpen.classList.contains('hidden'))) {
            document.body.style.overflow = '';
        }
    };

    window.openFinalRejectModal = function (risId) {
        var modal = document.getElementById('finalRejectModal');
        var form = document.getElementById('finalRejectForm');
        var textarea = document.getElementById('final_reject_remarks');
        if (!modal || !form || !textarea) return;
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        form.action = @js(\App\Support\AdminPortal::url('procurement-review/ris')) + '/' + risId + '/final-reject';
        textarea.value = '';
        if (typeof window.resetRevisionImagePicker === 'function') {
            window.resetRevisionImagePicker('finalRejectImages');
        }
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(function () { textarea.focus(); }, 200);
    };

    window.closeFinalRejectModal = function () {
        var modal = document.getElementById('finalRejectModal');
        if (modal) modal.classList.add('hidden');
        var da = document.getElementById('directApproveModal');
        var amendOpen = document.getElementById('amendModal');
        if ((!da || da.classList.contains('hidden')) && (!amendOpen || amendOpen.classList.contains('hidden'))) {
            document.body.style.overflow = '';
        }
    };

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        var nameModal = document.getElementById('adminDaNameSigModal');
        if (nameModal && !nameModal.classList.contains('hidden')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            nameModal.classList.add('hidden');
            return;
        }
        var deleteModal = document.getElementById('adminDaDeleteSigModal');
        if (deleteModal && !deleteModal.classList.contains('hidden')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            deleteModal.classList.add('hidden');
            return;
        }
        var pad = document.getElementById('adminDaSignPadModal');
        if (pad && !pad.classList.contains('hidden')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            pad.classList.add('hidden');
        }
    }, true);
</script>
@include('partials.signature-pad', ['renderPad' => false])
