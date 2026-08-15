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
        if (mode === 'amend') actionMode = 'amend';
        if (title) {
            title.textContent = actionMode === 'forward'
                ? 'Forward to President'
                : (actionMode === 'amend' ? 'Return for Amendment' : 'Admin Approval');
        }
        if (subtitle) {
            subtitle.textContent = actionMode === 'forward'
                ? 'Sign Issued by on the RIS form, then forward to the President.'
                : (actionMode === 'amend'
                    ? 'Sign Issued by on the RIS form, then enter amendment remarks.'
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
</script>
