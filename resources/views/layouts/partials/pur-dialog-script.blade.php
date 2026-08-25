{{-- Shared purchaser dialog focus trap (works even if Vite assets are stale). --}}
<script>
(function () {
    if (window.purDialog) {
        return;
    }

    var FOCUSABLE_SELECTOR = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])'
    ].join(',');

    var stateByRoot = typeof WeakMap !== 'undefined' ? new WeakMap() : null;
    var fallbackState = null;

    function focusables(root) {
        if (!root) {
            return [];
        }

        return Array.prototype.slice.call(root.querySelectorAll(FOCUSABLE_SELECTOR)).filter(function (el) {
            if (el.hasAttribute('disabled') || el.getAttribute('aria-hidden') === 'true') {
                return false;
            }

            var style = window.getComputedStyle(el);
            return style.display !== 'none' && style.visibility !== 'hidden';
        });
    }

    function activate(root) {
        if (!root) {
            return;
        }

        var payload = { lastFocus: document.activeElement };
        if (stateByRoot) {
            if (!stateByRoot.get(root)) {
                stateByRoot.set(root, payload);
            }
        } else {
            fallbackState = payload;
        }

        window.setTimeout(function () {
            var items = focusables(root);
            var preferred =
                root.querySelector('[data-pur-autofocus]') ||
                items.find(function (el) {
                    return el.getAttribute('aria-label') === 'Close';
                }) ||
                items[0];

            if (preferred && preferred.focus) {
                preferred.focus();
            }
        }, 50);
    }

    function deactivate(root) {
        if (!root) {
            return;
        }

        var prior = stateByRoot ? stateByRoot.get(root) : fallbackState;
        if (stateByRoot) {
            stateByRoot.delete(root);
        } else {
            fallbackState = null;
        }

        if (prior && prior.lastFocus && typeof prior.lastFocus.focus === 'function' && document.contains(prior.lastFocus)) {
            prior.lastFocus.focus();
        }
    }

    function trap(event, root) {
        if (!root || event.key !== 'Tab') {
            return;
        }

        var items = focusables(root);
        if (items.length === 0) {
            event.preventDefault();
            return;
        }

        var first = items[0];
        var last = items[items.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
            return;
        }

        if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    function sync(isOpen, root) {
        if (isOpen) {
            activate(root);
        } else {
            deactivate(root);
        }
    }

    window.purDialog = {
        activate: activate,
        deactivate: deactivate,
        trap: trap,
        sync: sync,
        focusables: focusables
    };
})();
</script>
