{{--
  Row toolbar (delete mode, row count, add row) for an editable paper form table.
  Place inside a wrapper like:
    <div data-doc-rows data-doc-rows-name="items" data-doc-rows-min="1" data-doc-rows-max="50" data-doc-rows-default="8">
      <table> <tbody data-doc-rows-body> <tr data-doc-row>…</tr> </tbody> </table>
      <template data-doc-rows-template><tr data-doc-row>… name="items[__INDEX__][field]" …</tr></template>
      @include('partials.doc-rows-editor', ['count' => $rowCount, 'max' => 50, 'min' => 1])
    </div>
--}}
@php
    $count = (int) ($count ?? 0);
    $max = (int) ($max ?? 50);
    $min = (int) ($min ?? 1);
@endphp
<div class="doc-rows-toolbar print:hidden">
    <div class="doc-rows-mode-toggle" title="Turn on, then click a row to delete it">
        <span class="doc-rows-mode-off">Off</span>
        <button type="button" class="doc-rows-mode-switch" data-doc-rows-mode aria-pressed="false" aria-label="Toggle delete row mode"></button>
        <span class="doc-rows-mode-on">Delete</span>
    </div>
    <span class="doc-rows-count" data-doc-rows-count>{{ $count }} / {{ $max }} rows</span>
    <button
        type="button"
        class="doc-rows-add-btn"
        data-doc-rows-add
        title="{{ $count >= $max ? 'Maximum of '.$max.' rows' : 'Add item row' }}"
        @disabled($count >= $max)
    >+ Add Item</button>
</div>

@pushOnce('scripts')
<style>
    [data-doc-rows] tr[data-doc-row] > td {
        position: relative;
    }

    .doc-row-hit {
        position: absolute;
        inset: 0;
        z-index: 5;
        margin: 0;
        padding: 0;
        border: 0;
        background: rgba(15, 23, 42, 0.62);
        opacity: 0;
        pointer-events: none;
        cursor: pointer;
        transition: opacity 0.12s ease;
    }

    .doc-row-x {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 6;
        height: 100%;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.12s ease;
    }

    .doc-row-x svg {
        display: block;
        width: 100%;
        height: 100%;
        overflow: visible;
    }

    [data-doc-rows].is-delete-mode:not(.doc-rows-at-min) tr[data-doc-row]:not([data-doc-row-locked]):hover .doc-row-hit,
    [data-doc-rows].is-delete-mode:not(.doc-rows-at-min) tr[data-doc-row]:not([data-doc-row-locked]):hover .doc-row-x {
        opacity: 1;
    }

    [data-doc-rows].is-delete-mode:not(.doc-rows-at-min) tr[data-doc-row]:not([data-doc-row-locked]):hover .doc-row-hit {
        pointer-events: auto;
    }

    [data-doc-rows].is-delete-mode tr[data-doc-row]:focus-within .doc-row-hit,
    [data-doc-rows].is-delete-mode tr[data-doc-row]:focus-within .doc-row-x {
        opacity: 0;
        pointer-events: none;
    }

    .doc-rows-toolbar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        padding-top: 10px;
        font-size: 11px;
    }

    .doc-rows-count {
        font-weight: 600;
        color: #64748b;
    }

    .doc-rows-add-btn {
        border: 1px solid #d1d5db;
        border-radius: 7px;
        background: #fff;
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 600;
        color: #0f172a;
    }

    .doc-rows-add-btn:hover:not(:disabled) {
        background: #f8fafc;
    }

    .doc-rows-add-btn:disabled {
        cursor: not-allowed;
        opacity: 0.45;
    }

    .doc-rows-mode-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        user-select: none;
    }

    .doc-rows-mode-toggle span {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
    }

    [data-doc-rows]:not(.is-delete-mode) .doc-rows-mode-off,
    [data-doc-rows].is-delete-mode .doc-rows-mode-on {
        color: #0f172a;
    }

    .doc-rows-mode-switch {
        position: relative;
        width: 36px;
        height: 20px;
        border-radius: 999px;
        border: 1px solid #cbd5e1;
        background: #e2e8f0;
        padding: 0;
        cursor: pointer;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }

    .doc-rows-mode-switch::after {
        content: '';
        position: absolute;
        top: 1px;
        left: 1px;
        width: 16px;
        height: 16px;
        border-radius: 999px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.2);
        transition: transform 0.15s ease;
    }

    [data-doc-rows].is-delete-mode .doc-rows-mode-switch {
        background: #0025cc;
        border-color: #0025cc;
    }

    [data-doc-rows].is-delete-mode .doc-rows-mode-switch::after {
        transform: translateX(16px);
    }
</style>
<script>
(function () {
    if (window.docRows) return;

    var X_SVG = '<svg viewBox="0 0 100 40" preserveAspectRatio="none">'
        + '<line x1="0" y1="0" x2="100" y2="40" stroke="#ffffff" stroke-width="0.9" vector-effect="non-scaling-stroke"></line>'
        + '<line x1="0" y1="40" x2="100" y2="0" stroke="#ffffff" stroke-width="0.9" vector-effect="non-scaling-stroke"></line>'
        + '</svg>';

    function intAttr(root, name, fallback) {
        var value = parseInt(root.getAttribute(name), 10);
        return isNaN(value) ? fallback : value;
    }

    function limits(root) {
        return {
            min: Math.max(1, intAttr(root, 'data-doc-rows-min', 1)),
            max: intAttr(root, 'data-doc-rows-max', 50),
            fallback: intAttr(root, 'data-doc-rows-default', 1),
        };
    }

    function body(root) {
        return root.querySelector('[data-doc-rows-body]');
    }

    function rows(root) {
        var tbody = body(root);
        if (!tbody) return [];
        return Array.prototype.filter.call(tbody.children, function (el) {
            return el.hasAttribute('data-doc-row');
        });
    }

    function renumber(root) {
        var prefix = root.getAttribute('data-doc-rows-name') || 'items';
        var pattern = new RegExp('^' + prefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\[(?:\\d+|__INDEX__)\\]');
        rows(root).forEach(function (row, index) {
            row.querySelectorAll('[name]').forEach(function (el) {
                var name = el.getAttribute('name');
                if (pattern.test(name)) {
                    el.setAttribute('name', name.replace(pattern, prefix + '[' + index + ']'));
                }
            });
        });
    }

    function sync(root) {
        var count = rows(root).length;
        var l = limits(root);
        root.classList.toggle('doc-rows-at-min', count <= l.min);
        root.querySelectorAll('[data-doc-rows-count]').forEach(function (el) {
            el.textContent = count + ' / ' + l.max + ' rows';
        });
        root.querySelectorAll('[data-doc-rows-add]').forEach(function (btn) {
            btn.disabled = count >= l.max;
            btn.title = count >= l.max ? ('Maximum of ' + l.max + ' rows') : 'Add item row';
        });
    }

    function changed(root) {
        renumber(root);
        sync(root);
        root.dispatchEvent(new CustomEvent('doc-rows:change', { bubbles: true, detail: { count: rows(root).length } }));
    }

    function decorate(row) {
        if (!row.hasAttribute('data-doc-row-ready')) {
            row.setAttribute('data-doc-row-ready', '1');
            Array.prototype.forEach.call(row.children, function (td) {
                var hit = document.createElement('button');
                hit.type = 'button';
                hit.className = 'doc-row-hit';
                hit.tabIndex = -1;
                hit.setAttribute('data-doc-row-remove', '');
                hit.setAttribute('aria-label', 'Remove row');
                td.appendChild(hit);
            });
            if (row.firstElementChild) {
                var x = document.createElement('span');
                x.className = 'doc-row-x';
                x.setAttribute('aria-hidden', 'true');
                x.innerHTML = X_SVG;
                row.firstElementChild.appendChild(x);
            }
        }
        var mark = row.querySelector('.doc-row-x');
        if (mark) mark.style.width = row.offsetWidth + 'px';
    }

    function add(root) {
        var l = limits(root);
        var tbody = body(root);
        var template = root.querySelector('template[data-doc-rows-template]');
        if (!tbody || !template || rows(root).length >= l.max) return null;
        var row = template.content.firstElementChild.cloneNode(true);
        tbody.appendChild(row);
        if (root.classList.contains('is-delete-mode')) decorate(row);
        changed(root);
        return row;
    }

    function remove(root, row) {
        if (!row || row.hasAttribute('data-doc-row-locked')) return;
        if (rows(root).length <= limits(root).min) return;
        row.remove();
        changed(root);
    }

    /** Grow/shrink to max(default, needed) rows (trailing unlocked rows are dropped first). */
    function fit(root, needed) {
        var l = limits(root);
        var target = Math.min(l.max, Math.max(l.min, l.fallback, needed || 0));
        while (rows(root).length < target && add(root)) {}
        var list = rows(root);
        for (var i = list.length - 1; i >= 0 && rows(root).length > target; i--) {
            if (!list[i].hasAttribute('data-doc-row-locked')) list[i].remove();
        }
        changed(root);
        return rows(root);
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) return;
        var root = target.closest('[data-doc-rows]');
        if (!root) return;

        if (target.closest('[data-doc-rows-add]')) {
            event.preventDefault();
            var row = add(root);
            var first = row && row.querySelector('input:not([type=hidden]), select, textarea');
            if (first) first.focus({ preventScroll: false });
            return;
        }
        if (target.closest('[data-doc-rows-mode]')) {
            event.preventDefault();
            var on = !root.classList.contains('is-delete-mode');
            if (on) rows(root).forEach(decorate);
            root.classList.toggle('is-delete-mode', on);
            target.closest('[data-doc-rows-mode]').setAttribute('aria-pressed', on ? 'true' : 'false');
            return;
        }
        var hit = target.closest('[data-doc-row-remove]');
        if (hit && root.classList.contains('is-delete-mode')) {
            event.preventDefault();
            remove(root, hit.closest('[data-doc-row]'));
        }
    });

    document.addEventListener('mouseover', function (event) {
        var row = event.target && event.target.closest && event.target.closest('[data-doc-rows] tr[data-doc-row]');
        if (row) decorate(row);
    });

    window.docRows = {
        rows: rows,
        add: add,
        remove: remove,
        fit: fit,
        sync: sync,
    };
})();
</script>
@endPushOnce
