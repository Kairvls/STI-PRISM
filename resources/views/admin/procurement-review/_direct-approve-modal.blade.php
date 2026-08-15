<div id="directApproveModal" class="fixed inset-0 hidden" style="z-index: 12000;">
    <div
        class="flex h-screen items-center justify-center bg-black/60 p-2 backdrop-blur-sm md:p-4"
        onclick="if (event.target === this) closeDirectApproveModal()"
    >
        <div
            class="relative flex max-h-[95vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
            onclick="event.stopPropagation()"
        >
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <div>
                    <h3 id="directApproveModalTitle" class="text-lg font-semibold text-gray-900">Admin Approval</h3>
                    <p id="directApproveModalSubtitle" class="mt-1 text-sm text-gray-500">Sign Issued by on the RIS form, then confirm.</p>
                </div>
                <button
                    type="button"
                    onclick="closeDirectApproveModal()"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                    title="Close"
                >
                    Close
                </button>
            </div>
            <div id="directApproveModalBody" class="flex min-h-0 flex-1 flex-col overflow-hidden bg-white">
                <div class="flex flex-1 items-center justify-center gap-3 py-16 text-sm text-gray-500">
                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-gray-300 border-t-slate-800"></div>
                    Loading RIS form...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.openDirectApproveModal = function(risId, mode) {
        var modal = document.getElementById('directApproveModal');
        var body = document.getElementById('directApproveModalBody');
        var title = document.getElementById('directApproveModalTitle');
        var subtitle = document.getElementById('directApproveModalSubtitle');
        if (!modal || !body) return;

        var actionMode = 'direct';
        if (mode === 'forward') actionMode = 'forward';
        if (mode === 'cosign') actionMode = 'cosign';
        if (title) {
            title.textContent = actionMode === 'forward'
                ? 'Forward to President'
                : (actionMode === 'cosign' ? 'Sign Issued by' : 'Admin Approval');
        }
        if (subtitle) {
            subtitle.textContent = actionMode === 'forward'
                ? 'Review the RIS form, then forward it to the President. Issued by is signed later on Sign RIS.'
                : (actionMode === 'cosign'
                    ? 'Sign Issued by on the RIS form. Approved by is already filled by the President.'
                    : 'Sign Issued by on the RIS form, then confirm Admin Approval.');
        }

        body.innerHTML = '<div class="flex flex-1 items-center justify-center gap-3 py-16 text-sm text-gray-500"><div class="h-5 w-5 animate-spin rounded-full border-2 border-gray-300 border-t-slate-800"></div>Loading RIS form...</div>';
        modal.classList.remove('hidden');

        fetch('/admin/procurement-review/ris/' + risId + '/direct-approve-form?mode=' + encodeURIComponent(actionMode), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(function(response) {
            if (!response.ok) throw new Error('Failed to load form');
            return response.text();
        })
        .then(function(html) {
            body.innerHTML = html;
            var dateInput = document.getElementById('da_issued_by_date');
            if (dateInput) {
                dateInput.addEventListener('input', function() {
                    var digits = this.value.replace(/\D/g, '').slice(0, 8);
                    var parts = [];
                    if (digits.length > 0) parts.push(digits.slice(0, 2));
                    if (digits.length > 2) parts.push(digits.slice(2, 4));
                    if (digits.length > 4) parts.push(digits.slice(4, 8));
                    this.value = parts.join('/');
                });
            }
        })
        .catch(function() {
            body.innerHTML = '<div class="px-6 py-16 text-center text-sm text-rose-600">Failed to load RIS form. Please try again.</div>';
        });
    };

    window.closeDirectApproveModal = function() {
        var modal = document.getElementById('directApproveModal');
        var body = document.getElementById('directApproveModalBody');
        if (body) body.innerHTML = '';
        if (modal) modal.classList.add('hidden');
    };

    window.openAmendModal = function(risId) {
        var modal = document.getElementById('amendModal');
        var form = document.getElementById('amendForm');
        var textarea = document.getElementById('amend_remarks');
        if (!modal || !form || !textarea) return;
        form.action = '/admin/procurement-review/ris/' + risId + '/reject';
        textarea.value = '';
        modal.classList.remove('hidden');
        setTimeout(function () { textarea.focus(); }, 200);
    };

    window.closeAmendModal = function() {
        var modal = document.getElementById('amendModal');
        if (modal) modal.classList.add('hidden');
    };
</script>

<div id="amendModal" class="fixed inset-0 hidden" style="z-index: 12000;">
    <div
        class="flex h-screen items-center justify-center bg-black/60 p-2 backdrop-blur-sm"
        onclick="if (event.target === this) closeAmendModal()"
    >
        <div
            class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl"
            onclick="event.stopPropagation()"
        >
            <div class="border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-bold text-gray-900">Return for Amendment</h3>
                <p class="mt-1 text-sm text-gray-500">
                    No signature is required. This goes straight back to the Purchaser for revision.
                </p>
            </div>
            <form id="amendForm" method="POST" action="">
                @csrf
                <div class="space-y-5 px-6 py-5">
                    <div>
                        <label for="amend_remarks" class="block text-sm font-medium text-gray-700">
                            Amendment Remarks <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="amend_remarks"
                            name="remarks"
                            rows="5"
                            required
                            placeholder="Describe what needs to be revised, e.g. incorrect quantities, missing documents, wrong unit cost."
                            class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-400 focus:ring-2 focus:ring-gray-100"
                        ></textarea>
                        <p class="mt-1.5 text-xs text-gray-400">
                            These remarks will be visible to the Purchaser when they revise this RIS.
                        </p>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                        <p class="text-xs text-amber-800">
                            Confirming returns this RIS immediately. The Purchaser must address the remarks before resubmitting.
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-6 py-4">
                    <button
                        type="button"
                        onclick="closeAmendModal()"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700"
                    >
                        Return to Purchaser
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
