{{-- Modern minimalist confirm dialog for Purchaser forms (replaces native window.confirm) --}}
<script>
(function () {
    if (window.purConfirm) return;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function iconSvg(kind) {
        if (kind === 'danger' || kind === 'archive') {
            return '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>';
        }
        return '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>';
    }

    window.purConfirm = function (options) {
        options = options || {};
        var title = options.title || 'Please confirm';
        var text = options.text || options.message || 'Are you sure you want to continue?';
        var confirmText = options.confirmText || 'Confirm';
        var cancelText = options.cancelText || 'Cancel';
        var danger = !!options.danger;
        var kind = options.kind || (danger ? 'danger' : 'submit');

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

            overlay.innerHTML =
                '<div class="w-full max-w-[360px] overflow-hidden rounded-2xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.18)] ring-1 ring-slate-200/80" data-pur-confirm-card>'
                +   '<div class="px-5 pt-5 pb-1">'
                +     '<div class="flex items-start gap-3">'
                +       '<div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ' + iconWrap + '">'
                +         iconSvg(kind)
                +       '</div>'
                +       '<div class="min-w-0 pt-0.5">'
                +         '<h3 id="purConfirmTitle" class="text-[15px] font-semibold tracking-tight text-slate-900">' + escapeHtml(title) + '</h3>'
                +         '<p class="mt-1.5 text-sm leading-relaxed text-slate-500">' + escapeHtml(text) + '</p>'
                +       '</div>'
                +     '</div>'
                +   '</div>'
                +   '<div class="mt-5 flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-3.5">'
                +     '<button type="button" data-pur-cancel class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">' + escapeHtml(cancelText) + '</button>'
                +     '<button type="button" data-pur-ok class="rounded-lg px-3.5 py-2 text-sm font-semibold shadow-sm transition ' + confirmBtn + '">' + escapeHtml(confirmText) + '</button>'
                +   '</div>'
                + '</div>';

            function finish(ok) {
                document.removeEventListener('keydown', onKey);
                overlay.remove();
                resolve(!!ok);
            }

            function onKey(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    finish(false);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    finish(true);
                }
            }

            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) finish(false);
            });
            overlay.querySelector('[data-pur-cancel]').addEventListener('click', function () { finish(false); });
            overlay.querySelector('[data-pur-ok]').addEventListener('click', function () { finish(true); });
            document.addEventListener('keydown', onKey);

            document.body.appendChild(overlay);
            overlay.querySelector('[data-pur-ok]').focus();
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
        var kind = form.getAttribute('data-pur-confirm-kind') || (danger ? 'archive' : 'submit');

        window.purConfirm({
            title: title,
            text: message,
            confirmText: confirmText,
            danger: danger,
            kind: kind
        }).then(function (ok) {
            if (!ok) return;
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
