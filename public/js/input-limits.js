/**
 * System-wide input max limits.
 * Applies maxlength/max to text-like and number fields that lack one.
 * Skips fields with data-no-max-limit or an existing maxlength/max.
 */
(function () {
    'use strict';

    var TEXT_TYPES = {
        text: true,
        search: true,
        email: true,
        tel: true,
        url: true,
        password: true,
        '': true,
    };

    var SKIP_TYPES = {
        hidden: true,
        file: true,
        checkbox: true,
        radio: true,
        submit: true,
        button: true,
        reset: true,
        image: true,
        range: true,
        color: true,
        date: true,
        'datetime-local': true,
        time: true,
        month: true,
        week: true,
    };

    var DEFAULT_TEXT = 255;
    var DEFAULT_NOTES = 500;
    var DEFAULT_NUMBER_MAX = 999999;

    var NAME_RULES = [
        { re: /(remark|note|reason|description|comment|message|particular)/i, max: 500 },
        { re: /(employee_id|employeeid|pin\b)/i, max: 50 },
        { re: /(phone|mobile|contact_number|tel)/i, max: 30 },
        { re: /email/i, max: 255 },
        { re: /(asset_tag|serial)/i, max: 255 },
        { re: /(quantity|qty)/i, numberMax: 500 },
        { re: /(cost|price|amount|purchase_cost)/i, numberMax: 999999999.99 },
    ];

    function fieldKey(el) {
        return [el.getAttribute('name') || '', el.id || '', el.getAttribute('placeholder') || ''].join(' ');
    }

    function matchRule(el) {
        var key = fieldKey(el);
        for (var i = 0; i < NAME_RULES.length; i++) {
            if (NAME_RULES[i].re.test(key)) {
                return NAME_RULES[i];
            }
        }
        return null;
    }

    function applyTo(el) {
        if (!el || el.nodeType !== 1) {
            return;
        }

        if (el.hasAttribute('data-no-max-limit')) {
            return;
        }

        var tag = el.tagName;
        if (tag === 'TEXTAREA') {
            if (!el.hasAttribute('maxlength')) {
                var noteRule = matchRule(el);
                el.setAttribute('maxlength', String((noteRule && noteRule.max) || DEFAULT_NOTES));
            }
            return;
        }

        if (tag !== 'INPUT') {
            return;
        }

        var type = (el.getAttribute('type') || '').toLowerCase();
        if (SKIP_TYPES[type]) {
            return;
        }

        if (type === 'number') {
            if (!el.hasAttribute('max')) {
                var numRule = matchRule(el);
                el.setAttribute('max', String((numRule && numRule.numberMax) || DEFAULT_NUMBER_MAX));
            }
            return;
        }

        if (!TEXT_TYPES[type] && type !== '') {
            return;
        }

        if (!el.hasAttribute('maxlength')) {
            var rule = matchRule(el);
            var max = DEFAULT_TEXT;
            if (rule && rule.max) {
                max = rule.max;
            } else if (rule && rule.numberMax) {
                max = DEFAULT_TEXT;
            }
            el.setAttribute('maxlength', String(max));
        }
    }

    function scan(root) {
        var scope = root && root.querySelectorAll ? root : document;
        var nodes = scope.querySelectorAll('input, textarea');
        for (var i = 0; i < nodes.length; i++) {
            applyTo(nodes[i]);
        }
        if (root && root.matches && (root.matches('input') || root.matches('textarea'))) {
            applyTo(root);
        }
    }

    function boot() {
        scan(document);

        if (typeof MutationObserver === 'undefined') {
            return;
        }

        var observer = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var m = mutations[i];
                if (m.type === 'childList') {
                    for (var j = 0; j < m.addedNodes.length; j++) {
                        var node = m.addedNodes[j];
                        if (node.nodeType === 1) {
                            scan(node);
                        }
                    }
                } else if (m.type === 'attributes' && m.target) {
                    applyTo(m.target);
                }
            }
        });

        observer.observe(document.documentElement, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['type', 'name', 'id'],
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.PrismInputLimits = { scan: scan, applyTo: applyTo };
})();
