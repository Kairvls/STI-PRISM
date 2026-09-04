{{-- President Approve RIS modal shell --}}

<div id="presidentApproveModal" class="fixed inset-0 z-[12000] hidden">
    <div
        class="absolute inset-0 flex items-center justify-center bg-slate-900/55 p-4 backdrop-blur-[2px]"
        onclick="if (event.target === this && !window._presidentPaFileDialogOpen) closePresidentApproveModal()"
    >
        <div
            class="relative flex h-auto max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-200/80"
            onclick="event.stopPropagation()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="presidentApproveModalTitle"
        >
            <button
                type="button"
                onclick="if (!window._presidentPaFileDialogOpen) closePresidentApproveModal()"
                class="absolute right-2.5 top-2.5 z-10 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                title="Close"
                aria-label="Close"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <div class="shrink-0 border-b border-slate-100 px-5 pb-2.5 pr-11 pt-3">
                <h3 id="presidentApproveModalTitle" class="text-base font-semibold tracking-tight text-slate-900">
                    Approve RIS
                </h3>
                <p id="presidentApproveModalSubtitle" class="mt-0.5 text-sm leading-snug text-slate-500">
                    Sign Approved by on the RIS form, then confirm.
                </p>
            </div>

            <div id="presidentApproveModalBody" class="flex min-h-0 flex-1 flex-col overflow-hidden bg-slate-50/40">
                <div class="flex flex-1 items-center justify-center gap-3 py-16 text-sm text-slate-500">
                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-slate-200 border-t-slate-700"></div>
                    Loading RIS form...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window._presidentPaFileDialogOpen = false;

    (function () {
        function mountPresidentApproveModalToBody() {
            var el = document.getElementById('presidentApproveModal');
            if (el && el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', mountPresidentApproveModalToBody);
        } else {
            mountPresidentApproveModalToBody();
        }
    })();

    window.markPresidentPaFileDialog = function (isOpen) {
        window._presidentPaFileDialogOpen = !!isOpen;
        if (isOpen) {
            window.setTimeout(function () {
                window._presidentPaFileDialogOpen = false;
            }, 1500);
        }
    };

    window.openPresidentApproveModal = function (risId) {
        var modal = document.getElementById('presidentApproveModal');
        var body = document.getElementById('presidentApproveModalBody');
        var subtitle = document.getElementById('presidentApproveModalSubtitle');
        if (!modal || !body) return;

        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        if (subtitle) {
            subtitle.textContent = 'RIS #' + risId + ' — pick a saved signature, draw, or upload. It overlays Approved by.';
        }

        body.innerHTML = '<div class="flex flex-1 items-center justify-center gap-3 py-16 text-sm text-slate-500"><div class="h-5 w-5 animate-spin rounded-full border-2 border-slate-200 border-t-slate-700"></div>Loading RIS form...</div>';
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        fetch(@json(url('/president/approvals/ris')) + '/' + risId + '/approve-form', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error(response.status === 403
                    ? 'This RIS is not available for President approval.'
                    : 'Failed to load form');
            }
            return response.text();
        })
        .then(function (html) {
            body.innerHTML = html;
            Array.prototype.slice.call(body.querySelectorAll('script')).forEach(function (oldScript) {
                var script = document.createElement('script');
                script.textContent = oldScript.textContent;
                oldScript.parentNode.replaceChild(script, oldScript);
            });
        })
        .catch(function (err) {
            var msg = (err && err.message) ? err.message : 'Failed to load RIS form. Please try again.';
            body.innerHTML = '<div class="px-6 py-16 text-center text-sm text-slate-600">' + msg + '</div>';
        });
    };

    window.closePresidentApproveModal = function () {
        if (window._presidentPaFileDialogOpen) return;
        ['paSignPadModal', 'paNameSigModal', 'paDeleteSigModal', 'paNotice'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && el.parentElement === document.body) {
                el.remove();
            }
        });
        var modal = document.getElementById('presidentApproveModal');
        var body = document.getElementById('presidentApproveModalBody');
        if (body) body.innerHTML = '';
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        var nameModal = document.getElementById('paNameSigModal');
        if (nameModal && !nameModal.classList.contains('hidden')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            nameModal.classList.add('hidden');
            return;
        }
        var deleteModal = document.getElementById('paDeleteSigModal');
        if (deleteModal && !deleteModal.classList.contains('hidden')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            deleteModal.classList.add('hidden');
            return;
        }
        var pad = document.getElementById('paSignPadModal');
        if (pad && !pad.classList.contains('hidden')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            pad.classList.add('hidden');
            return;
        }
        var modal = document.getElementById('presidentApproveModal');
        if (modal && !modal.classList.contains('hidden') && !window._presidentPaFileDialogOpen) {
            event.preventDefault();
            closePresidentApproveModal();
        }
    }, true);
</script>
@include('partials.signature-pad', ['renderPad' => false])
