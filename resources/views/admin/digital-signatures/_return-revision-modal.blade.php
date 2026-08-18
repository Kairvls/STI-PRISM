{{-- Return President-rejected RIS for Minor Revision --}}
<div id="returnRevisionModal" class="fixed inset-0 hidden" style="z-index: 12000;">
    <div
        class="flex h-screen items-center justify-center bg-black/60 p-2 backdrop-blur-sm"
        onclick="if (event.target === this) closeReturnRevisionModal()"
    >
        <div
            class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl"
            onclick="event.stopPropagation()"
        >
            <div class="border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-bold text-gray-900">Return for Minor Revision</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Send this President-rejected RIS back to the Purchaser with revision remarks.
                </p>
            </div>
            <form id="returnRevisionForm" method="POST" action="">
                @csrf
                <div class="space-y-5 px-6 py-5">
                    <div>
                        <label for="return_revision_remarks" class="block text-sm font-medium text-gray-700">
                            Revision Remarks <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="return_revision_remarks"
                            name="remarks"
                            rows="5"
                            required
                            placeholder="Describe what the Purchaser must revise before resubmitting."
                            class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-400 focus:ring-2 focus:ring-gray-100"
                        ></textarea>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                        <p class="text-xs text-amber-800">
                            Approved by and Issued by signatures will be cleared. The Purchaser edits under Minor Revision, then resubmits to Admin.
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-6 py-4">
                    <button
                        type="button"
                        onclick="closeReturnRevisionModal()"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-amber-700"
                    >
                        Return to Purchaser
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.openReturnRevisionModal = function(risId) {
        var modal = document.getElementById('returnRevisionModal');
        var form = document.getElementById('returnRevisionForm');
        var textarea = document.getElementById('return_revision_remarks');
        if (!modal || !form || !textarea) return;
        form.action = '/admin/digital-signatures/ris/' + risId + '/return-revision';
        textarea.value = '';
        modal.classList.remove('hidden');
        setTimeout(function () { textarea.focus(); }, 200);
    };

    window.closeReturnRevisionModal = function() {
        var modal = document.getElementById('returnRevisionModal');
        if (modal) modal.classList.add('hidden');
    };
</script>
