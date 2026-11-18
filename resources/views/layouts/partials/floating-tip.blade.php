{{-- Global floating tooltip: [data-tip] + auto tips for truncated .truncate text --}}
<style>
#pm-floating-tip {
    position: fixed;
    z-index: 100000;
    max-width: min(280px, calc(100vw - 16px));
    padding: 6px 10px;
    border-radius: 12px;
    border: 1px solid rgba(226, 232, 240, .8);
    background: #ffffff;
    color: #334155;
    font-size: 11px;
    font-weight: 500;
    line-height: 1.35;
    text-align: center;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .14);
    pointer-events: none;
    opacity: 0;
    transform: translateY(2px);
    transition: opacity .12s ease, transform .12s ease;
    white-space: normal;
    word-break: break-word;
}
#pm-floating-tip.is-wide {
    max-width: min(420px, calc(100vw - 16px));
    text-align: left;
}
#pm-floating-tip.is-visible {
    opacity: 1;
    transform: translateY(0);
}
#pm-floating-tip.is-below::after,
#pm-floating-tip.is-above::after {
    content: none;
}
</style>
<script>
(function () {
    if (window.__pmFloatingTipInit) return;
    window.__pmFloatingTipInit = true;

    var tip = document.createElement('div');
    tip.id = 'pm-floating-tip';
    tip.setAttribute('role', 'tooltip');
    tip.hidden = true;
    document.body.appendChild(tip);

    var activeEl = null;
    var truncateSel = '.truncate, .line-clamp-1, .line-clamp-2, [data-truncate-tip]';

    function hideTip() {
        activeEl = null;
        tip.classList.remove('is-visible', 'is-above', 'is-below', 'is-wide');
        tip.hidden = true;
        tip.textContent = '';
    }

    function isTruncated(el) {
        if (!el) return false;
        return el.scrollWidth > el.clientWidth + 1 || el.scrollHeight > el.clientHeight + 1;
    }

    function tipTextFor(el) {
        if (!el) return '';
        var explicit = (el.getAttribute('data-tip') || '').trim();
        if (explicit) return explicit;

        var full = (el.getAttribute('data-full-tip') || el.getAttribute('data-truncate-tip') || '').trim();
        if (full) return full;

        var title = (el.getAttribute('title') || '').trim();
        if (title) return title;

        return (el.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function resolveTipTarget(start) {
        if (!start || !start.closest) return null;

        var trunc = start.closest(truncateSel);
        if (trunc && document.body.contains(trunc)) {
            var full = (
                trunc.getAttribute('data-truncate-tip')
                || trunc.getAttribute('data-tip')
                || trunc.getAttribute('title')
                || ''
            ).trim();
            var visible = (trunc.textContent || '').replace(/\s+/g, ' ').trim();
            var cut = isTruncated(trunc) || (full !== '' && full !== visible);
            if (cut) {
                var truncText = full || visible;
                if (truncText) return { el: trunc, text: truncText };
            }
        }

        var withDataTip = start.closest('[data-tip]');
        if (
            withDataTip
            && document.body.contains(withDataTip)
            && !withDataTip.matches(truncateSel)
            && !withDataTip.hasAttribute('data-truncate-tip')
        ) {
            var tipOnly = (withDataTip.getAttribute('data-tip') || '').trim();
            if (tipOnly) return { el: withDataTip, text: tipOnly };
        }

        return null;
    }

    function placeTip(el, text) {
        if (!el || !text) {
            hideTip();
            return;
        }

        tip.textContent = text;
        tip.hidden = false;
        tip.classList.toggle('is-wide', text.length > 42);
        tip.classList.remove('is-visible', 'is-above', 'is-below');
        tip.style.left = '0px';
        tip.style.top = '0px';

        var rect = el.getBoundingClientRect();
        var tipRect = tip.getBoundingClientRect();
        var gap = 8;
        var vw = window.innerWidth;
        var vh = window.innerHeight;

        var left = rect.left + rect.width / 2 - tipRect.width / 2;
        left = Math.max(8, Math.min(left, vw - tipRect.width - 8));

        var spaceAbove = rect.top;
        var spaceBelow = vh - rect.bottom;
        var placeAttr = (el.getAttribute('data-tip-place') || '').trim().toLowerCase();
        var preferAbove = placeAttr === 'above' || placeAttr === 'top';
        var preferBelow = !preferAbove && (
            placeAttr === 'below'
            || placeAttr === 'bottom'
            || !!el.closest('.pm-queue-table, .recent-row, .panel-head, .awaiting-indicator, .pur-table, table')
            || spaceAbove < tipRect.height + gap + 4
        );

        var top;
        if (preferAbove && spaceAbove >= tipRect.height + gap) {
            top = rect.top - tipRect.height - gap;
            tip.classList.add('is-above');
        } else if (preferBelow && spaceBelow >= tipRect.height + gap) {
            top = rect.bottom + gap;
            tip.classList.add('is-below');
        } else if (spaceAbove >= tipRect.height + gap) {
            top = rect.top - tipRect.height - gap;
            tip.classList.add('is-above');
        } else if (spaceBelow >= spaceAbove) {
            top = Math.min(vh - tipRect.height - 8, Math.max(8, rect.bottom + gap));
            tip.classList.add('is-below');
        } else {
            top = Math.max(8, rect.top - tipRect.height - gap);
            tip.classList.add('is-above');
        }

        tip.style.left = left + 'px';
        tip.style.top = top + 'px';
        requestAnimationFrame(function () {
            tip.classList.add('is-visible');
        });
    }

    function showTip(el, text) {
        activeEl = el;
        placeTip(el, text);
    }

    document.addEventListener('mouseover', function (e) {
        var resolved = resolveTipTarget(e.target);
        if (!resolved) return;
        if (resolved.el === activeEl) return;
        showTip(resolved.el, resolved.text);
    });

    document.addEventListener('mouseout', function (e) {
        if (!activeEl) return;
        var next = e.relatedTarget;
        if (next && activeEl.contains(next)) return;
        if (next && next.closest && next.closest(truncateSel + ', [data-tip]') === activeEl) return;
        hideTip();
    });

    document.addEventListener('focusin', function (e) {
        var resolved = resolveTipTarget(e.target);
        if (resolved) showTip(resolved.el, resolved.text);
    });

    document.addEventListener('focusout', function () {
        hideTip();
    });

    window.addEventListener('scroll', function () {
        if (!activeEl) return;
        placeTip(activeEl, tipTextFor(activeEl));
    }, true);

    window.addEventListener('resize', function () {
        if (!activeEl) return;
        placeTip(activeEl, tipTextFor(activeEl));
    });
})();
</script>
