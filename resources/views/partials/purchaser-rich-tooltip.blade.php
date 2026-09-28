{{-- Floating tooltip with a title and description for elements with data-pur-tip-title / data-pur-tip. --}}
<style>
    #purRichTip {
        position: fixed;
        z-index: 11000;
        max-width: 260px;
        padding: 10px 12px;
        border-radius: 12px;
        background: #0f172a;
        color: #e2e8f0;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.28);
        pointer-events: none;
        opacity: 0;
        transform: translateY(4px);
        transition: opacity .14s ease, transform .14s ease;
    }
    #purRichTip.is-visible { opacity: 1; transform: translateY(0); }
    #purRichTip.is-below { transform: translateY(-4px); }
    #purRichTip.is-below.is-visible { transform: translateY(0); }
    #purRichTip [data-tip-title] {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #fff;
    }
    #purRichTip [data-tip-dot] { width: 6px; height: 6px; border-radius: 9999px; background: var(--pur-tip-accent, #60a5fa); }
    #purRichTip [data-tip-body] { margin-top: 4px; font-size: 11.5px; line-height: 1.45; color: #cbd5e1; }
    #purRichTip [data-tip-note] {
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px solid rgba(148, 163, 184, 0.22);
        font-size: 11px;
        color: #94a3b8;
    }
    #purRichTip::after {
        content: '';
        position: absolute;
        left: var(--pur-tip-arrow-x, 50%);
        width: 10px;
        height: 10px;
        background: #0f172a;
        transform: translateX(-50%) rotate(45deg);
    }
    #purRichTip.is-above::after { bottom: -4px; }
    #purRichTip.is-below::after { top: -4px; }
</style>
<script>
(function () {
    if (window.__purRichTipInit) return;
    window.__purRichTipInit = true;

    var tip = null;
    var active = null;

    function ensureTip() {
        if (tip) return tip;
        tip = document.createElement('div');
        tip.id = 'purRichTip';
        tip.setAttribute('role', 'tooltip');
        tip.hidden = true;
        tip.innerHTML = '<div data-tip-title><span data-tip-dot></span><span data-tip-title-text></span></div>'
            + '<div data-tip-body></div><div data-tip-note></div>';
        document.body.appendChild(tip);
        return tip;
    }

    function hide() {
        active = null;
        if (!tip) return;
        tip.classList.remove('is-visible', 'is-above', 'is-below');
        tip.hidden = true;
    }

    function show(el) {
        var t = ensureTip();
        active = el;
        var note = el.getAttribute('data-pur-tip-note') || '';
        t.querySelector('[data-tip-title-text]').textContent = el.getAttribute('data-pur-tip-title') || '';
        t.querySelector('[data-tip-body]').textContent = el.getAttribute('data-pur-tip') || '';
        var noteEl = t.querySelector('[data-tip-note]');
        noteEl.textContent = note;
        noteEl.style.display = note ? '' : 'none';
        t.style.setProperty('--pur-tip-accent', el.getAttribute('data-pur-tip-accent') || '#60a5fa');

        t.hidden = false;
        t.classList.remove('is-visible', 'is-above', 'is-below');
        t.style.left = '0px';
        t.style.top = '0px';

        var rect = el.getBoundingClientRect();
        var tipRect = t.getBoundingClientRect();
        var gap = 10;
        var pad = 8;
        var left = rect.left + rect.width / 2 - tipRect.width / 2;
        left = Math.max(pad, Math.min(left, window.innerWidth - tipRect.width - pad));

        var top = rect.top - tipRect.height - gap;
        if (top < pad) {
            top = rect.bottom + gap;
            t.classList.add('is-below');
        } else {
            t.classList.add('is-above');
        }

        var arrowX = rect.left + rect.width / 2 - left;
        t.style.setProperty('--pur-tip-arrow-x', Math.max(12, Math.min(arrowX, tipRect.width - 12)) + 'px');
        t.style.left = Math.round(left) + 'px';
        t.style.top = Math.round(top) + 'px';
        requestAnimationFrame(function () { t.classList.add('is-visible'); });
    }

    document.addEventListener('pointerover', function (e) {
        var el = e.target.closest && e.target.closest('[data-pur-tip-title]');
        if (!el || el === active) return;
        show(el);
    });

    document.addEventListener('pointerout', function (e) {
        if (!active) return;
        var next = e.relatedTarget;
        if (next && active.contains(next)) return;
        hide();
    });

    document.addEventListener('focusin', function (e) {
        var el = e.target.closest && e.target.closest('[data-pur-tip-title]');
        if (el) show(el);
    });
    document.addEventListener('focusout', hide);
    document.addEventListener('click', hide, true);
    window.addEventListener('scroll', hide, true);
    window.addEventListener('resize', hide);
})();
</script>
