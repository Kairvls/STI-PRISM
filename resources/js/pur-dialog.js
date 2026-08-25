/**
 * Shared focus trap / restore for purchaser (and other) dialog overlays.
 * Use with Alpine: x-effect + @keydown.tab on the dialog root.
 */
const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

const stateByRoot = new WeakMap();

function focusables(root) {
    if (!root) {
        return [];
    }

    return [...root.querySelectorAll(FOCUSABLE_SELECTOR)].filter((el) => {
        if (el.hasAttribute('disabled') || el.getAttribute('aria-hidden') === 'true') {
            return false;
        }

        const style = window.getComputedStyle(el);
        return style.display !== 'none' && style.visibility !== 'hidden';
    });
}

function activate(root) {
    if (!root) {
        return;
    }

    const prior = stateByRoot.get(root);
    if (!prior) {
        stateByRoot.set(root, { lastFocus: document.activeElement });
    }

    window.setTimeout(() => {
        const items = focusables(root);
        const preferred =
            root.querySelector('[data-pur-autofocus]') ||
            items.find((el) => el.getAttribute('aria-label') === 'Close') ||
            items[0];

        preferred?.focus?.();
    }, 50);
}

function deactivate(root) {
    if (!root) {
        return;
    }

    const prior = stateByRoot.get(root);
    stateByRoot.delete(root);

    const restore = prior?.lastFocus;
    if (restore && typeof restore.focus === 'function' && document.contains(restore)) {
        restore.focus();
    }
}

function trap(event, root) {
    if (!root || event.key !== 'Tab') {
        return;
    }

    const items = focusables(root);
    if (items.length === 0) {
        event.preventDefault();
        return;
    }

    const first = items[0];
    const last = items[items.length - 1];

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

/**
 * Alpine x-effect helper: sync trap when an open expression flips.
 * Example: x-effect="window.purDialog.sync(createOpen, $el)"
 */
function sync(isOpen, root) {
    if (isOpen) {
        activate(root);
    } else {
        deactivate(root);
    }
}

window.purDialog = {
    activate,
    deactivate,
    trap,
    sync,
    focusables,
};
