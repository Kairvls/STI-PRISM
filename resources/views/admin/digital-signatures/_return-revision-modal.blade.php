{{-- Return President-rejected RIS for Minor Revision — owner by default, optional reassign --}}
@php
    $purchaserOptions = \App\Support\ReviewerAssignment::options(\App\Support\WorkflowNotifier::ROLE_PURCHASER);
@endphp
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
                <h3 class="text-lg font-bold text-gray-900">Return for Revision</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Defaults to the Purchaser who submitted this RIS. Reassign only if they are unavailable.
                </p>
            </div>
            <form id="returnRevisionForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="owner_purchaser_id" id="return_revision_owner_id" value="">
                <input type="hidden" name="assigned_purchaser_id" id="return_revision_purchaser_id" value="">
                <div class="max-h-[65vh] space-y-5 overflow-y-auto px-6 py-5">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-3">
                        <p class="text-xs font-medium text-slate-600">Purchaser (owner)</p>
                        <p id="return_revision_purchaser_name" class="mt-0.5 text-sm font-semibold text-slate-900">—</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-400">
                            This person submitted the RIS and normally handles the revision.
                        </p>
                    </div>

                    <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-slate-200 bg-white px-3.5 py-3">
                        <input
                            type="checkbox"
                            id="return_revision_reassign"
                            name="reassign_purchaser"
                            value="1"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]"
                            onchange="window.toggleReturnRevisionReassign(this.checked)"
                        >
                        <span>
                            <span class="block text-sm font-medium text-slate-800">Reassign to another Purchaser</span>
                            <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-400">
                                Use when the owner is absent or on leave. Requires a short reason.
                            </span>
                        </span>
                    </label>

                    <div id="return_revision_reassign_panel" class="hidden space-y-4">
                        <div>
                            <label for="return_revision_reassign_select" class="mb-1.5 block text-xs font-medium text-gray-600">
                                Covering Purchaser <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="return_revision_reassign_select"
                                class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-800 outline-none transition focus:border-gray-300"
                            >
                                <option value="">Select Purchaser...</option>
                                @php
                                    $hasAvailablePurchaser = collect($purchaserOptions)->contains(fn ($r) => empty($r['unavailable']));
                                @endphp
                                @foreach ($purchaserOptions as $reviewer)
                                    <option
                                        value="{{ $reviewer['id'] }}"
                                        @disabled(! empty($reviewer['unavailable']) && $hasAvailablePurchaser)
                                    >{{ $reviewer['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="return_revision_reassign_reason" class="mb-1.5 block text-xs font-medium text-gray-600">
                                Why reassign? <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="return_revision_reassign_reason"
                                name="reassign_reason"
                                maxlength="255"
                                placeholder="e.g. Owner on leave until Friday"
                                class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-800 outline-none transition focus:border-gray-300"
                            >
                        </div>
                    </div>

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
                    @include('admin.partials.revision-image-picker', ['pickerId' => 'returnRevisionImages'])
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-700">
                            Approved by and Issued by are cleared. The Purchaser then edits under For revision (President) and resubmits.
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
                        class="rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fa8]"
                    >
                        Return to Purchaser
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.toggleReturnRevisionReassign = function (enabled) {
        var panel = document.getElementById('return_revision_reassign_panel');
        var select = document.getElementById('return_revision_reassign_select');
        var reason = document.getElementById('return_revision_reassign_reason');
        var assigned = document.getElementById('return_revision_purchaser_id');
        var owner = document.getElementById('return_revision_owner_id');
        if (!panel) return;

        panel.classList.toggle('hidden', !enabled);
        if (select) {
            select.required = !!enabled;
            if (!enabled) select.value = '';
        }
        if (reason) {
            reason.required = !!enabled;
            if (!enabled) reason.value = '';
        }
        if (assigned && owner && !enabled) {
            assigned.value = owner.value || '';
        }
    };

    window.syncReturnRevisionAssignee = function () {
        var reassign = document.getElementById('return_revision_reassign');
        var select = document.getElementById('return_revision_reassign_select');
        var assigned = document.getElementById('return_revision_purchaser_id');
        var owner = document.getElementById('return_revision_owner_id');
        if (!assigned) return;
        if (reassign && reassign.checked && select && select.value) {
            assigned.value = select.value;
        } else if (owner) {
            assigned.value = owner.value || '';
        }
    };

    window.openReturnRevisionModal = function(risId, purchaserId, purchaserName) {
        var modal = document.getElementById('returnRevisionModal');
        var form = document.getElementById('returnRevisionForm');
        var textarea = document.getElementById('return_revision_remarks');
        var ownerIdInput = document.getElementById('return_revision_owner_id');
        var assignedInput = document.getElementById('return_revision_purchaser_id');
        var ownerNameEl = document.getElementById('return_revision_purchaser_name');
        var reassign = document.getElementById('return_revision_reassign');
        if (!modal || !form || !textarea) return;

        form.action = @js(\App\Support\AdminPortal::url('digital-signatures/ris')) + '/' + risId + '/return-revision';
        textarea.value = '';
        var ownerId = purchaserId ? String(purchaserId) : '';
        if (ownerIdInput) ownerIdInput.value = ownerId;
        if (assignedInput) assignedInput.value = ownerId;
        if (ownerNameEl) {
            ownerNameEl.textContent = (purchaserName && String(purchaserName).trim() !== '')
                ? String(purchaserName).trim()
                : 'Purchaser who submitted this RIS';
        }
        if (reassign) {
            reassign.checked = false;
            window.toggleReturnRevisionReassign(false);
        }
        var select = document.getElementById('return_revision_reassign_select');
        if (select) {
            Array.prototype.forEach.call(select.options, function (opt) {
                if (!opt.value) return;
                opt.disabled = ownerId !== '' && String(opt.value) === ownerId;
                opt.hidden = opt.disabled;
            });
            select.value = '';
        }
        if (typeof window.resetRevisionImagePicker === 'function') {
            window.resetRevisionImagePicker('returnRevisionImages');
        }
        modal.classList.remove('hidden');
        setTimeout(function () { textarea.focus(); }, 200);
    };

    window.closeReturnRevisionModal = function() {
        var modal = document.getElementById('returnRevisionModal');
        if (modal) modal.classList.add('hidden');
    };

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('returnRevisionForm');
        var select = document.getElementById('return_revision_reassign_select');
        if (select) {
            select.addEventListener('change', window.syncReturnRevisionAssignee);
        }
        if (form) {
            form.addEventListener('submit', function () {
                window.syncReturnRevisionAssignee();
            });
        }
    });
</script>
