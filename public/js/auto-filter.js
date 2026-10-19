/**
 * Auto-submit GET filter forms when a select, checkbox/radio, or date control changes.
 * Opt-in via data-auto-filter on the <form>. Skip controls with data-no-auto-filter.
 */
(function () {
    function shouldAutoFilter(el) {
        if (!el || el.disabled || el.dataset.noAutoFilter !== undefined) return false;
        if (el.closest('[data-no-auto-filter]')) return false;
        // Avoid double-submit when the control already has an inline onchange handler.
        if (el.getAttribute('onchange')) return false;

        var tag = (el.tagName || '').toLowerCase();
        var type = (el.type || '').toLowerCase();

        if (tag === 'select') return true;
        if (type === 'checkbox' || type === 'radio') return true;
        if (type === 'date' || type === 'month' || type === 'week' || type === 'datetime-local') return true;

        return false;
    }

    function submitForm(form) {
        if (!form) return;
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }
        form.submit();
    }

    function bindForm(form) {
        if (!form || form.dataset.autoFilterBound === '1') return;
        form.dataset.autoFilterBound = '1';

        form.addEventListener('change', function (event) {
            var target = event.target;
            if (!shouldAutoFilter(target)) return;
            submitForm(form);
        });
    }

    function scan(root) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('form[data-auto-filter]').forEach(bindForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { scan(document); });
    } else {
        scan(document);
    }

    window.PrismAutoFilter = { scan: scan, bind: bindForm };
})();
