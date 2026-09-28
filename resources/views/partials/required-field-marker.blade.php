{{-- Adds a red * to the label of every required input/select/textarea, including ones shown or toggled later. --}}
{{-- Opt out on a control or label with data-no-req-star. --}}
<style>
    .req-star {
        color: #ef4444;
        font-weight: 600;
        margin-left: 0.2em;
        text-transform: none;
    }
</style>
<script>
(function () {
    if (window.__reqStarInit) return;
    window.__reqStarInit = true;

    var REQUIRED = 'input[required]:not([type="hidden"]), select[required], textarea[required], [aria-required="true"]';
    var CONTROLS = 'input:not([type="hidden"]), select, textarea';

    function findLabel(el) {
        if (el.id) {
            var byFor = document.querySelector('label[for="' + CSS.escape(el.id) + '"]');
            if (byFor) return byFor;
        }

        var type = (el.getAttribute('type') || '').toLowerCase();
        if (type === 'radio' || type === 'checkbox') {
            var fieldset = el.closest('fieldset');
            return fieldset ? fieldset.querySelector(':scope > legend') : null;
        }

        var wrap = el.closest('label');
        if (wrap) return wrap;

        var node = el;
        for (var depth = 0; depth < 4 && node.parentElement; depth++) {
            for (var sib = node.previousElementSibling; sib; sib = sib.previousElementSibling) {
                if (sib.tagName === 'LABEL') {
                    if (sib.htmlFor && sib.htmlFor !== el.id) return null;
                    if (sib.querySelector(CONTROLS)) return null;
                    return sib;
                }
                if (sib.matches(CONTROLS) || sib.querySelector(CONTROLS)) return null;
            }
            node = node.parentElement;
            if (node.tagName === 'FORM' || node.tagName === 'BODY' || node.tagName === 'TD' || node.tagName === 'TR') break;
        }

        return null;
    }

    function hasManualStar(label) {
        var clone = label.cloneNode(true);
        clone.querySelectorAll('.req-star, select, textarea').forEach(function (n) { n.remove(); });
        return clone.textContent.indexOf('*') !== -1;
    }

    function starAnchor(label, control) {
        var walker = document.createTreeWalker(label, NodeFilter.SHOW_TEXT, {
            acceptNode: function (n) {
                if (!n.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
                if (n.parentElement.closest('select, textarea, option, button, .req-star')) return NodeFilter.FILTER_REJECT;
                if (label.contains(control) && (control.compareDocumentPosition(n) & Node.DOCUMENT_POSITION_FOLLOWING)) return NodeFilter.FILTER_REJECT;
                return NodeFilter.FILTER_ACCEPT;
            },
        });
        var last = null;
        while (walker.nextNode()) last = walker.currentNode;
        return last;
    }

    function scan() {
        var wanted = new Map();
        document.querySelectorAll(REQUIRED).forEach(function (el) {
            if (el.disabled || el.closest('[data-no-req-star]')) return;
            var label = findLabel(el);
            if (!label || label.hasAttribute('data-no-req-star') || wanted.has(label)) return;
            wanted.set(label, el);
        });

        document.querySelectorAll('.req-star[data-req-auto]').forEach(function (star) {
            var label = star.closest('label, legend');
            if (!label || !wanted.has(label)) star.remove();
        });

        wanted.forEach(function (control, label) {
            if (label.querySelector('.req-star[data-req-auto]') || hasManualStar(label)) return;
            var anchor = starAnchor(label, control);
            if (!anchor) return;
            var star = document.createElement('span');
            star.className = 'req-star';
            star.setAttribute('data-req-auto', '');
            star.setAttribute('aria-hidden', 'true');
            star.textContent = '*';
            anchor.parentNode.insertBefore(star, anchor.nextSibling);
        });
    }

    var timer = null;
    function schedule() {
        if (timer) return;
        timer = setTimeout(function () { timer = null; scan(); }, 120);
    }

    function start() {
        scan();
        new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var m = mutations[i];
                if (m.type === 'attributes') { schedule(); return; }
                for (var j = 0; j < m.addedNodes.length; j++) {
                    var n = m.addedNodes[j];
                    if (n.nodeType === 1 && !(n.classList && n.classList.contains('req-star'))) { schedule(); return; }
                }
            }
        }).observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['required', 'aria-required', 'disabled'],
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>
