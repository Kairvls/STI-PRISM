{{-- Modern minimalist confirm dialog for Purchaser forms (replaces native window.confirm) --}}
@php
    use App\Support\ReviewerAssignment;
    use App\Support\WorkflowNotifier;

    $purReviewerOptions = [
        WorkflowNotifier::ROLE_ADMIN => ReviewerAssignment::options(WorkflowNotifier::ROLE_ADMIN),
        WorkflowNotifier::ROLE_ACCOUNTING => ReviewerAssignment::options(WorkflowNotifier::ROLE_ACCOUNTING),
        WorkflowNotifier::ROLE_RECEIVING => ReviewerAssignment::options(WorkflowNotifier::ROLE_RECEIVING),
    ];
@endphp
<script>
(function () {
    window.purReviewerOptions = @json($purReviewerOptions);

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function iconSvg(kind) {
        if (kind === 'danger') {
            return '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
        }
        if (kind === 'archive') {
            return '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>';
        }
        return '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>';
    }

    function reviewerRoleLabel(role) {
        if (role === 'Admin') return 'Administrator';
        if (role === 'Receiving Officer') return 'Receiving Officer';
        if (role === 'Accounting') return 'Accounting';
        return 'reviewer';
    }

    function ensureHiddenInput(form, name, value) {
        var input = form.querySelector('input[name="' + name + '"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            form.appendChild(input);
        }
        input.value = value;
    }

    window.purConfirm = function (options) {
        options = options || {};
        var title = options.title || 'Please confirm';
        var text = options.text || options.message || 'Are you sure you want to continue?';
        var confirmText = options.confirmText || 'Confirm';
        var cancelText = options.cancelText || 'Cancel';
        var danger = !!options.danger;
        var kind = options.kind || (danger ? 'danger' : 'submit');
        var reviewerRole = options.reviewerRole || null;
        var reviewers = reviewerRole
            ? ((window.purReviewerOptions && window.purReviewerOptions[reviewerRole]) || [])
            : [];
        var needsReviewer = !!reviewerRole;

        return new Promise(function (resolve) {
            document.getElementById('purConfirmOverlay')?.remove();

            var overlay = document.createElement('div');
            overlay.id = 'purConfirmOverlay';
            overlay.className = 'fixed inset-0 z-[12000] flex items-center justify-center bg-black/50 p-4';
            overlay.setAttribute('role', 'dialog');
            overlay.setAttribute('aria-modal', 'true');
            overlay.setAttribute('aria-labelledby', 'purConfirmTitle');

            var iconWrap = danger
                ? 'bg-rose-50 text-rose-600'
                : 'bg-[#0025cc]/10 text-[#0025cc]';
            var confirmBtn = danger
                ? 'bg-rose-600 text-white hover:bg-rose-700'
                : 'bg-[#0025cc] text-white hover:bg-blue-800';

            var reviewerHtml = '';
            if (needsReviewer) {
                var opts = '<option value=\"\">Select ' + escapeHtml(reviewerRoleLabel(reviewerRole)) + '…</option>';
                reviewers.forEach(function (row) {
                    opts += '<option value=\"' + escapeHtml(row.id) + '\">' + escapeHtml(row.name) + '</option>';
                });
                reviewerHtml =
                    '<div class=\"mt-4 block w-full\">'
                    +   '<label for=\"purConfirmReviewer\" class=\"block text-xs font-medium text-slate-600\">Assign to <span class=\"text-red-500\">*</span></label>'
                    +   '<select id=\"purConfirmReviewer\" data-pur-reviewer class=\"mt-1.5 block h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-slate-300\">'
                    +     opts
                    +   '</select>'
                    +   (reviewers.length === 0
                        ? '<p class=\"mt-1.5 text-xs text-rose-600\">No ' + escapeHtml(reviewerRoleLabel(reviewerRole)) + ' users are available.</p>'
                        : '<p class=\"mt-1.5 text-xs text-slate-400\">Only the selected person will receive this for review.</p>')
                    + '</div>';
            }

            overlay.innerHTML =
                '<div class="w-full max-w-[400px] overflow-hidden rounded-2xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.18)] ring-1 ring-slate-200/80" data-pur-confirm-card>'
                +   '<div class="px-5 pt-5 pb-1">'
                +     '<div class="flex items-start gap-3">'
                +       '<div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ' + iconWrap + '">'
                +         iconSvg(kind)
                +       '</div>'
                +       '<div class="min-w-0 flex-1 pt-0.5">'
                +         '<h3 id="purConfirmTitle" class="text-[15px] font-semibold tracking-tight text-slate-900">' + escapeHtml(title) + '</h3>'
                +         '<p class="mt-1.5 text-sm leading-relaxed text-slate-500">' + escapeHtml(text) + '</p>'
                +       '</div>'
                +     '</div>'
                +     reviewerHtml
                +     '<p data-pur-reviewer-error class="mt-2 hidden text-xs text-rose-600">Please select a reviewer.</p>'
                +   '</div>'
                +   '<div class="mt-5 flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-3.5">'
                +     '<button type="button" data-pur-cancel class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">' + escapeHtml(cancelText) + '</button>'
                +     '<button type="button" data-pur-ok class="rounded-lg px-3.5 py-2 text-sm font-semibold shadow-sm transition ' + confirmBtn + '">' + escapeHtml(confirmText) + '</button>'
                +   '</div>'
                + '</div>';

            function finish(result) {
                document.removeEventListener('keydown', onKey);
                overlay.remove();
                resolve(result);
            }

            function tryConfirm() {
                if (!needsReviewer) {
                    finish(true);
                    return;
                }
                var select = overlay.querySelector('[data-pur-reviewer]');
                var err = overlay.querySelector('[data-pur-reviewer-error]');
                var reviewerId = select ? String(select.value || '').trim() : '';
                if (!reviewerId) {
                    if (err) err.classList.remove('hidden');
                    if (select) select.focus();
                    return;
                }
                if (err) err.classList.add('hidden');
                finish({ ok: true, reviewerId: reviewerId });
            }

            function onKey(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    finish(false);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    tryConfirm();
                }
            }

            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) finish(false);
            });
            overlay.querySelector('[data-pur-cancel]').addEventListener('click', function () { finish(false); });
            overlay.querySelector('[data-pur-ok]').addEventListener('click', tryConfirm);
            document.addEventListener('keydown', onKey);

            document.body.appendChild(overlay);
            var focusEl = overlay.querySelector('[data-pur-reviewer]') || overlay.querySelector('[data-pur-ok]');
            if (focusEl) focusEl.focus();
        });
    };

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || form.tagName !== 'FORM') return;
        if (!form.hasAttribute('data-pur-confirm')) return;
        if (form.dataset.purConfirmAccepted === '1') {
            delete form.dataset.purConfirmAccepted;
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        var message = form.getAttribute('data-pur-confirm') || 'Are you sure you want to continue?';
        var title = form.getAttribute('data-pur-confirm-title') || 'Please confirm';
        var confirmText = form.getAttribute('data-pur-confirm-ok') || 'Confirm';
        var danger = form.getAttribute('data-pur-confirm-danger') === '1';
        var kind = form.getAttribute('data-pur-confirm-kind') || (danger ? 'danger' : 'submit');
        var reviewerRole = form.getAttribute('data-pur-confirm-reviewer-role') || null;

        window.purConfirm({
            title: title,
            text: message,
            confirmText: confirmText,
            danger: danger,
            kind: kind,
            reviewerRole: reviewerRole
        }).then(function (result) {
            if (!result) return;
            if (result && result.reviewerId) {
                ensureHiddenInput(form, 'assigned_reviewer_id', result.reviewerId);
            }
            form.dataset.purConfirmAccepted = '1';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }, true);
})();
</script>
