{{-- Read-only President remarks for a Sign RIS record --}}
<div id="presidentRemarksModal" class="fixed inset-0 hidden" style="z-index: 12000;" role="dialog" aria-modal="true" aria-labelledby="presidentRemarksTitle">
    <div
        class="flex h-screen items-center justify-center bg-black/60 p-2 backdrop-blur-sm"
        onclick="if (event.target === this) closePresidentRemarksModal()"
    >
        <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4">
                <div class="min-w-0">
                    <h3 id="presidentRemarksTitle" class="text-lg font-bold text-gray-900">Remarks from the President</h3>
                    <p class="mt-1 truncate text-sm text-gray-500" data-president-remarks-ref></p>
                </div>
                <span class="mt-1 inline-flex shrink-0 items-center rounded-md border px-2 py-0.5 text-[11px] font-semibold" data-president-remarks-decision></span>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div class="max-h-[50vh] overflow-y-auto whitespace-pre-line break-words rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-relaxed text-slate-800" data-president-remarks-text></div>
                <p class="text-xs text-slate-500" data-president-remarks-meta></p>
            </div>

            <div class="flex items-center justify-end border-t border-gray-100 px-6 py-4">
                <button
                    type="button"
                    onclick="closePresidentRemarksModal()"
                    class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.openPresidentRemarksModal = function (button) {
        var modal = document.getElementById('presidentRemarksModal');
        if (!modal || !button) return;

        var data = button.dataset;
        var rejected = data.decision === 'Rejected';
        var text = modal.querySelector('[data-president-remarks-text]');
        var badge = modal.querySelector('[data-president-remarks-decision]');

        modal.querySelector('[data-president-remarks-ref]').textContent = data.ref || '';
        badge.textContent = rejected ? 'Rejected by the President' : 'Approved by the President';
        badge.className = 'mt-1 inline-flex shrink-0 items-center rounded-md border px-2 py-0.5 text-[11px] font-semibold '
            + (rejected ? 'border-slate-500 bg-slate-800 text-slate-100' : 'border-emerald-200 bg-emerald-50 text-emerald-700');

        text.textContent = data.remarks || 'The President did not leave any remarks.';
        text.classList.toggle('italic', !data.remarks);
        text.classList.toggle('text-slate-400', !data.remarks);
        text.classList.toggle('text-slate-800', !!data.remarks);

        modal.querySelector('[data-president-remarks-meta]').textContent =
            (rejected ? 'Rejected by ' : 'Approved by ') + (data.by || 'President') + (data.at ? ' · ' + data.at : '');

        modal.classList.remove('hidden');
    };

    window.closePresidentRemarksModal = function () {
        var modal = document.getElementById('presidentRemarksModal');
        if (modal) modal.classList.add('hidden');
    };

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') window.closePresidentRemarksModal();
    });
</script>
