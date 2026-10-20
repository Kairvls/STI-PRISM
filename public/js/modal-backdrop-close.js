/**
 * System-wide: close modals when clicking the empty backdrop (outside the panel).
 *
 * Exclusions (do not close on outside click):
 * - [data-no-backdrop-close] and descendants (chat/messaging system)
 * - #reportModal (landing page reporting modal, phase 2)
 * - SweetAlert2 containers
 */
(function () {
    'use strict';

    function isExcluded(el) {
        if (!(el instanceof Element)) return true;
        if (el.closest('[data-no-backdrop-close]')) return true;
        if (el.closest('#reportModal')) return true;
        if (el.closest('.swal2-container, .swal2-popup')) return true;
        return false;
    }

    function classNameOf(el) {
        if (!el || !el.className) return '';
        return typeof el.className === 'string' ? el.className : String(el.className.baseVal || '');
    }

    function hasInset0(el) {
        return /\binset-0\b/.test(classNameOf(el));
    }

    function isFixedOrAbsolute(el) {
        var style = window.getComputedStyle(el);
        return style.position === 'fixed' || style.position === 'absolute';
    }

    function isVisible(el) {
        if (!el || el.classList.contains('hidden')) return false;
        var style = window.getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
            return false;
        }
        var rect = el.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    }

    function coversViewport(el) {
        var rect = el.getBoundingClientRect();
        return rect.width >= window.innerWidth * 0.85 && rect.height >= window.innerHeight * 0.85;
    }

    /**
     * True when the click landed on the dimmed overlay itself (not the dialog panel).
     */
    function isBackdropTarget(el) {
        if (!(el instanceof HTMLElement)) return false;
        if (el === document.body || el === document.documentElement) return false;
        if (!isVisible(el) || !isFixedOrAbsolute(el)) return false;

        var cls = classNameOf(el);

        // Dedicated backdrop layers
        if (/\b(backdrop|overlay|modal-backdrop)\b/i.test(cls) || hasInset0(el)) {
            return coversViewport(el) || hasInset0(el);
        }

        // Single-node overlay: fixed inset-0 flex centering with a semi-transparent bg
        if (/\bfixed\b/.test(cls) && hasInset0(el) && coversViewport(el)) {
            return true;
        }

        return false;
    }

    function resolveOverlayRoot(el) {
        // Absolute backdrop inside a fixed modal root → close the root
        if (/\babsolute\b/.test(classNameOf(el)) && hasInset0(el) && el.parentElement) {
            var parent = el.parentElement;
            if (/\bfixed\b/.test(classNameOf(parent)) && hasInset0(parent)) {
                return parent;
            }
        }
        return el;
    }

    function setPathFalse(data, path) {
        if (!data || !path) return false;

        if (/^[a-zA-Z_$][\w$]*$/.test(path)) {
            if (path in data && data[path] === true) {
                data[path] = false;
                return true;
            }
            return false;
        }

        var m = path.match(/^([a-zA-Z_$][\w$]*)\.([a-zA-Z_$][\w$]*)$/);
        if (!m) return false;

        var obj = data[m[1]];
        if (!obj || typeof obj !== 'object') return false;

        if (m[1] === 'roomLayout' && m[2] === 'open') {
            // Edit mode: outside click must not close the layout modal
            if (obj.edit) {
                return true;
            }
            if (typeof data.requestCloseRoomLayout === 'function') {
                data.requestCloseRoomLayout();
                return true;
            }
        }

        if (obj[m[2]] === true) {
            obj[m[2]] = false;
            return true;
        }

        return false;
    }

    function callCloser(data) {
        if (!data) return false;
        var names = ['closeAll', 'close', 'hide', 'dismiss', 'requestClose'];
        for (var i = 0; i < names.length; i++) {
            if (typeof data[names[i]] === 'function') {
                try {
                    data[names[i]]();
                    return true;
                } catch (e) {
                    /* ignore */
                }
            }
        }
        return false;
    }

    function closeViaAlpine(overlay) {
        if (!window.Alpine || typeof Alpine.$data !== 'function') return false;

        var data;
        try {
            data = Alpine.$data(overlay);
        } catch (e) {
            data = null;
        }

        // Prefer the x-show expression on this overlay (avoids closing the wrong nested modal)
        var expr = overlay.getAttribute('x-show');
        if (expr && data) {
            var cleaned = expr.trim();

            // open / show / foo.open
            if (/^[a-zA-Z_$][\w$.]*$/.test(cleaned)) {
                if (setPathFalse(data, cleaned)) return true;
            }

            // viewOpen && selectedXxx === ...
            var andMatch = cleaned.match(/^([a-zA-Z_$][\w$.]*)\s*&&/);
            if (andMatch && setPathFalse(data, andMatch[1])) return true;

            // modal === 'create' → prefer closeAll/close helpers
            if (/===|!==/.test(cleaned) && callCloser(data)) return true;

            return false;
        }

        // No x-show: only toggle nearest simple open/show booleans
        var node = overlay;
        while (node && node !== document.body) {
            try {
                var d = Alpine.$data(node);
                if (d) {
                    if (setPathFalse(d, 'open') || setPathFalse(d, 'show')) return true;
                    if (callCloser(d)) return true;
                    break;
                }
            } catch (e2) {
                /* ignore */
            }
            node = node.parentElement;
        }

        return false;
    }

    function closeViaButton(overlay) {
        var root = resolveOverlayRoot(overlay);

        // Session / report idle warnings: outside click = stay (do not only hide)
        if (root.id === 'idleSessionWarningModal') {
            var stay = document.getElementById('idleSessionStaySignedIn');
            if (stay) {
                stay.click();
                return true;
            }
        }
        if (root.id === 'reportIdleWarningModal') {
            var reportStay = document.getElementById('reportIdleStay');
            if (reportStay) {
                reportStay.click();
                return true;
            }
        }

        var btn = root.querySelector(
            '[data-modal-close], [data-dismiss="modal"], button[aria-label="Close"], button[aria-label="close"]'
        );
        if (btn) {
            btn.click();
            return true;
        }
        return false;
    }

    function closeViaHidden(overlay) {
        var root = resolveOverlayRoot(overlay);
        if (root.classList.contains('hidden')) return false;

        // Alpine-driven overlays use x-show — do not force-hide those
        if (root.hasAttribute('x-show')) {
            return false;
        }

        root.classList.add('hidden');
        root.setAttribute('aria-hidden', 'true');
        return true;
    }

    function closeOverlay(overlay) {
        var root = resolveOverlayRoot(overlay);

        if (closeViaAlpine(root)) return;
        if (closeViaAlpine(overlay)) return;
        if (closeViaButton(root)) return;
        if (closeViaHidden(root)) return;

        // Last resort: Escape toward the overlay (many modals listen on window)
        try {
            document.dispatchEvent(
                new KeyboardEvent('keydown', {
                    key: 'Escape',
                    code: 'Escape',
                    keyCode: 27,
                    which: 27,
                    bubbles: true,
                    cancelable: true,
                })
            );
        } catch (e) {
            /* ignore */
        }
    }

    document.addEventListener(
        'click',
        function (event) {
            var target = event.target;
            if (!(target instanceof Element)) return;
            if (isExcluded(target)) return;
            if (!isBackdropTarget(target)) return;
            if (isExcluded(target)) return;

            // Click must be on the backdrop itself, not a child panel
            // (event.target === backdrop element)
            closeOverlay(target);
        },
        false
    );
})();
