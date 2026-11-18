/**
 * System-wide input max limits.
 * Applies maxlength/max to text-like and number fields.
 * Money fields: max 7 digits before decimal + 2 after (9999999.99),
 * ₱ prefix adornment, and auto .00 formatting on blur.
 * Names: 30 chars. Purpose / description: 70 chars. Qty / counts: 7 digits.
 * Skips fields with data-no-max-limit.
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

    var DEFAULT_NAME = 30;
    var DEFAULT_PURPOSE = 400;
    var DEFAULT_NOTES = 500;
    var DEFAULT_NUMBER_MAX = 9999999;
    var QTY_DIGITS = 7;
    var MONEY_MAX = 9999999.99;
    var MONEY_WHOLE_DIGITS = 7;
    var MONEY_DECIMAL_DIGITS = 2;

    var NAME_RULES = [
        // Purpose / short descriptions first (before generic "name" / "note").
        { re: /(purpose|particular|description|desc\b)/i, max: DEFAULT_PURPOSE },
        // Longer admin remarks keep a higher cap.
        { re: /(remark|note|reason|comment|message|revision)/i, max: DEFAULT_NOTES },
        { re: /(employee_id|employeeid|pin\b)/i, max: 50 },
        { re: /(phone|mobile|contact_number|tel)/i, max: 30 },
        { re: /email/i, max: 255 },
        { re: /(asset_tag|serial)/i, max: 255 },
        {
            re: /(quantity|qty|count|months|days|interval|pieces|units)/i,
            numberMax: DEFAULT_NUMBER_MAX,
            qty: true,
        },
        {
            re: /(cost|price|amount|purchase_cost|unit_cost|downpayment|refund|budget|fund|residual|salvage)/i,
            numberMax: MONEY_MAX,
            money: true,
        },
        // Person / product / equipment / supplier / brand / model / room names.
        {
            re: /(full_?name|person_name|payee|requested_by|received_by|authorized_by|contact_person|custodian|equipment_name|product_name|item_name|article|company_name|shop_name|supplier|brand|model|room_name|building|category_name|check_name|operating_hours)/i,
            max: DEFAULT_NAME,
        },
        { re: /(^|[_-])name($|[_-])/i, max: DEFAULT_NAME },
    ];

    function ensureMoneyStyles() {
        if (document.getElementById('prism-money-styles')) return;
        var style = document.createElement('style');
        style.id = 'prism-money-styles';
        style.textContent = [
            '.prism-money-wrap{position:relative;display:block;width:100%;min-width:0;}',
            '.prism-money-wrap.prism-money-wrap--inline{display:inline-block;width:auto;}',
            '.prism-money-prefix{position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);pointer-events:none;color:#64748b;font-weight:600;font-size:0.875rem;line-height:1;z-index:1;}',
            '.prism-money-wrap.prism-money-wrap--compact .prism-money-prefix{left:0.2rem;font-size:0.75rem;}',
            '.prism-money-input{padding-left:1.75rem !important;}',
            '.prism-money-wrap.prism-money-wrap--compact .prism-money-input{padding-left:1rem !important;}',
            '.prism-money-wrap.prism-money-wrap--right .prism-money-prefix{left:auto;right:0.75rem;}',
            '.prism-money-wrap.prism-money-wrap--right .prism-money-input{padding-left:0.75rem !important;padding-right:1.75rem !important;}',
            '.prism-money-wrap.prism-money-wrap--compact.prism-money-wrap--right .prism-money-prefix{right:0.2rem;}',
            '.prism-money-wrap.prism-money-wrap--compact.prism-money-wrap--right .prism-money-input{padding-right:1rem !important;}',
        ].join('');
        document.head.appendChild(style);
    }

    function fieldKey(el) {
        return [el.getAttribute('name') || '', el.id || '', el.getAttribute('placeholder') || '', el.getAttribute('aria-label') || '', el.getAttribute('data-money') || ''].join(' ');
    }

    function matchRule(el) {
        if (el.hasAttribute('data-money') || el.hasAttribute('data-money-input')) {
            return { numberMax: MONEY_MAX, money: true };
        }
        if (el.hasAttribute('data-qty') || el.hasAttribute('data-qty-input')) {
            return { numberMax: DEFAULT_NUMBER_MAX, qty: true };
        }
        var key = fieldKey(el);
        for (var i = 0; i < NAME_RULES.length; i++) {
            if (NAME_RULES[i].re.test(key)) {
                return NAME_RULES[i];
            }
        }
        return null;
    }

    function isMoneyField(el, rule) {
        if (!el) return false;
        if (el.hasAttribute('data-money') || el.hasAttribute('data-money-input') || el.hasAttribute('data-dv-amount-input')) {
            return true;
        }
        return !!(rule && rule.money);
    }

    function isQtyField(el, rule) {
        if (!el) return false;
        if (el.hasAttribute('data-qty') || el.hasAttribute('data-qty-input')) return true;
        return !!(rule && rule.qty);
    }

    function clampMoneyString(raw) {
        var cleaned = String(raw == null ? '' : raw).replace(/,/g, '').replace(/[^\d.]/g, '');
        if (cleaned === '') return '';

        var parts = cleaned.split('.');
        var whole = (parts[0] || '').slice(0, MONEY_WHOLE_DIGITS);
        var hasDot = parts.length > 1;
        var decimals = hasDot ? parts.slice(1).join('').slice(0, MONEY_DECIMAL_DIGITS) : '';

        if (!hasDot) return whole;
        return whole + '.' + decimals;
    }

    function clampMoneyNumber(raw) {
        var clamped = clampMoneyString(raw);
        if (clamped === '' || clamped === '.') return '';
        var num = Number(clamped);
        if (!isFinite(num)) return '';
        if (num > MONEY_MAX) return String(MONEY_MAX);
        if (num < 0) return '0';
        return clamped;
    }

    function formatMoneyDecimals(raw) {
        var clamped = clampMoneyNumber(raw);
        if (clamped === '') return '';
        var num = Number(clamped);
        if (!isFinite(num)) return '';
        return num.toFixed(MONEY_DECIMAL_DIGITS);
    }

    function clampQtyString(raw) {
        var digits = String(raw == null ? '' : raw).replace(/[^\d]/g, '');
        if (digits === '') return '';
        // Drop leading zeros but keep a single 0.
        digits = digits.replace(/^0+(?=\d)/, '');
        return digits.slice(0, QTY_DIGITS);
    }

    function clampQtyNumber(raw) {
        var clamped = clampQtyString(raw);
        if (clamped === '') return '';
        var num = Number(clamped);
        if (!isFinite(num)) return '';
        if (num > DEFAULT_NUMBER_MAX) return String(DEFAULT_NUMBER_MAX);
        return String(num);
    }

    function isCompactMoneyInput(el) {
        var cls = ' ' + (el.className || '') + ' ';
        return /border-0|bg-transparent|ris-cell-input/.test(cls)
            || el.closest('table') != null
            || el.hasAttribute('data-money-compact');
    }

    function isRightAlignedMoneyInput(el) {
        var cls = ' ' + (el.className || '') + ' ';
        return /text-right/.test(cls) || el.hasAttribute('data-money-right');
    }

    function ensurePesoPrefix(el) {
        if (!el || el.__prismPesoWrapped) return;
        if (el.hasAttribute('data-no-peso-prefix')) {
            el.__prismPesoWrapped = true;
            return;
        }
        if (el.hasAttribute('data-dv-amount-input')) {
            el.__prismPesoWrapped = true;
            return;
        }
        if (el.closest('.prism-money-wrap')) {
            el.__prismPesoWrapped = true;
            return;
        }
        if (!el.parentNode) return;

        ensureMoneyStyles();

        var wrap = document.createElement('div');
        wrap.className = 'prism-money-wrap';
        if (isCompactMoneyInput(el)) wrap.classList.add('prism-money-wrap--compact');
        if (isRightAlignedMoneyInput(el)) wrap.classList.add('prism-money-wrap--right');

        var cls = ' ' + (el.className || '') + ' ';
        if (/\bflex-1\b/.test(cls)) wrap.classList.add('flex-1', 'min-w-0');
        if (/\bw-full\b/.test(cls)) wrap.style.width = '100%';
        if (/\bh-10\b/.test(cls) || /\bh-9\b/.test(cls) || /\bh-8\b/.test(cls)) {
            wrap.style.display = 'block';
        }

        var parent = el.parentNode;
        parent.insertBefore(wrap, el);
        wrap.appendChild(el);

        var prefix = document.createElement('span');
        prefix.className = 'prism-money-prefix';
        prefix.setAttribute('aria-hidden', 'true');
        prefix.textContent = '₱';
        wrap.insertBefore(prefix, el);

        el.classList.add('prism-money-input');
        el.__prismPesoWrapped = true;
    }

    function syncValue(el, next) {
        if (next === el.value) return;
        el.value = next;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function onMoneyInput(e) {
        var el = e.target;
        if (!el || el.hasAttribute('data-no-max-limit')) return;
        if (el.hasAttribute('readonly') || el.disabled) return;

        var before = el.value;
        var next = clampMoneyString(before);
        if (next === before) return;

        var start = el.selectionStart;
        el.value = next;
        try {
            if (typeof start === 'number') {
                var delta = before.length - next.length;
                var pos = Math.max(0, start - (delta > 0 ? delta : 0));
                el.setSelectionRange(pos, pos);
            }
        } catch (err) {}
    }

    function onMoneyBlur(e) {
        var el = e.target;
        if (!el || el.hasAttribute('data-no-max-limit') || el.hasAttribute('readonly') || el.disabled) return;
        if (el.hasAttribute('data-dv-amount-input')) return;
        var next = formatMoneyDecimals(el.value);
        syncValue(el, next);
    }

    function onQtyInput(e) {
        var el = e.target;
        if (!el || el.hasAttribute('data-no-max-limit')) return;
        if (el.hasAttribute('readonly') || el.disabled) return;

        var before = el.value;
        var next = clampQtyString(before);
        if (next === before) return;

        var start = el.selectionStart;
        el.value = next;
        try {
            if (typeof start === 'number') {
                var delta = before.length - next.length;
                var pos = Math.max(0, start - (delta > 0 ? delta : 0));
                el.setSelectionRange(pos, pos);
            }
        } catch (err) {}
        // Keep Alpine x-model.number in sync.
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function bindMoneyGuard(el) {
        if (!el || el.__prismMoneyBound) return;
        el.__prismMoneyBound = true;
        el.addEventListener('input', onMoneyInput, true);
        el.addEventListener('blur', onMoneyBlur);
        ensurePesoPrefix(el);

        if (String(el.value || '').trim() !== '' && !el.hasAttribute('data-dv-amount-input')) {
            var formatted = formatMoneyDecimals(el.value);
            if (formatted && formatted !== el.value) {
                el.value = formatted;
            }
        }
    }

    function bindQtyGuard(el) {
        if (!el || el.__prismQtyBound) return;
        el.__prismQtyBound = true;
        el.addEventListener('input', onQtyInput, true);
        if (String(el.value || '').trim() !== '') {
            var next = clampQtyNumber(el.value);
            if (next !== '' && next !== el.value) {
                el.value = next;
            }
        }
    }

    function setMaxLength(el, max) {
        if (!max || max < 1) return;
        var current = el.getAttribute('maxlength');
        if (!current || Number(current) > max) {
            el.setAttribute('maxlength', String(max));
        }
    }

    function setNumberMax(el, max) {
        if (max == null) return;
        var current = el.getAttribute('max');
        if (!current || Number(current) > Number(max)) {
            el.setAttribute('max', String(max));
        }
    }

    function applyTo(el) {
        if (!el || el.nodeType !== 1) {
            return;
        }

        if (el.hasAttribute('data-no-max-limit')) {
            return;
        }

        var tag = el.tagName;
        var rule = matchRule(el);

        if (tag === 'TEXTAREA') {
            var textMax = (rule && rule.max) || DEFAULT_NOTES;
            // Purpose/description textareas get 70; unknown textareas keep NOTES unless already tighter.
            if (rule && rule.max) {
                setMaxLength(el, rule.max);
            } else if (!el.hasAttribute('maxlength')) {
                el.setAttribute('maxlength', String(DEFAULT_NOTES));
            } else {
                // Tighten oversized hardcoded purpose-like limits when name matches later rules.
                setMaxLength(el, textMax);
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

        var money = isMoneyField(el, rule);
        var qty = isQtyField(el, rule);

        if (type === 'number' || ((money || qty) && (type === 'text' || type === ''))) {
            if (money) {
                setNumberMax(el, MONEY_MAX);
                if (!el.hasAttribute('min')) {
                    el.setAttribute('min', '0');
                }
                if (type === 'number' && !el.hasAttribute('step')) {
                    el.setAttribute('step', '0.01');
                }
                if (!el.getAttribute('inputmode')) {
                    el.setAttribute('inputmode', 'decimal');
                }
                if (!el.getAttribute('placeholder')) {
                    el.setAttribute('placeholder', '0.00');
                }
                bindMoneyGuard(el);
            } else if (qty) {
                setNumberMax(el, (rule && rule.numberMax) || DEFAULT_NUMBER_MAX);
                if (!el.hasAttribute('min')) {
                    el.setAttribute('min', '0');
                }
                if (!el.getAttribute('inputmode')) {
                    el.setAttribute('inputmode', 'numeric');
                }
                bindQtyGuard(el);
            } else if (type === 'number') {
                setNumberMax(el, (rule && rule.numberMax) || DEFAULT_NUMBER_MAX);
                bindQtyGuard(el);
            }
            return;
        }

        if (!TEXT_TYPES[type] && type !== '') {
            return;
        }

        if (money) {
            el.setAttribute('inputmode', el.getAttribute('inputmode') || 'decimal');
            if (!el.getAttribute('placeholder')) {
                el.setAttribute('placeholder', '0.00');
            }
            bindMoneyGuard(el);
        }

        if (rule && rule.max) {
            setMaxLength(el, rule.max);
        } else if (!el.hasAttribute('maxlength')) {
            el.setAttribute('maxlength', String(DEFAULT_NAME));
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
        ensureMoneyStyles();
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
            attributeFilter: ['type', 'name', 'id', 'data-money', 'data-qty'],
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.PrismInputLimits = {
        scan: scan,
        applyTo: applyTo,
        clampMoney: clampMoneyNumber,
        formatMoney: formatMoneyDecimals,
        clampQty: clampQtyNumber,
        MONEY_MAX: MONEY_MAX,
        MONEY_WHOLE_DIGITS: MONEY_WHOLE_DIGITS,
        MONEY_DECIMAL_DIGITS: MONEY_DECIMAL_DIGITS,
        QTY_DIGITS: QTY_DIGITS,
        NAME_MAX: DEFAULT_NAME,
        PURPOSE_MAX: DEFAULT_PURPOSE,
    };
})();
