@extends ("layouts.app")

@section ("sidebar")
    @include ("layouts.maintenance-sidebar")

@endsection

@section ("topbar")
    @include ("layouts.maintenance-topbar")

@endsection

@push('scripts')
<script>
    (function () {
        const ITEM_H = 40;
        const MAX_VISIBLE = 5;
        let panel = null;
        let openSelect = null;
        let uid = 0;

        function closeMpSelect() {
            if (panel) {
                panel.remove();
                panel = null;
            }
            openSelect = null;
        }

        function shouldSkip(select) {
            if (!select || select.tagName !== 'SELECT') {
                return true;
            }
            if (select.multiple || Number(select.getAttribute('size') || 0) > 1) {
                return true;
            }
            if (select.dataset.nativeSelect === '1') {
                return true;
            }
            return false;
        }

        function bindSelect(select) {
            if (shouldSkip(select) || select.dataset.mpSelectBound === '1') {
                return;
            }
            if (!select.id) {
                select.id = 'mp-select-' + (++uid);
            }
            select.dataset.mpSelectBound = '1';

            select.addEventListener('mousedown', function (event) {
                if (event.button !== 0 || select.disabled) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                if (openSelect === select) {
                    closeMpSelect();
                    return;
                }
                openMpSelect(select);
            });

            select.addEventListener('keydown', function (event) {
                if (select.disabled) {
                    return;
                }
                if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown') {
                    event.preventDefault();
                    openMpSelect(select);
                }
            });
        }

        function placePanel(select) {
            if (!panel || !select) {
                return;
            }

            const rect = select.getBoundingClientRect();
            const count = Math.min(select.options.length, MAX_VISIBLE);
            const height = count * ITEM_H + 8;
            const width = Math.max(rect.width, 160);
            panel.style.width = width + 'px';
            panel.style.maxHeight = (MAX_VISIBLE * ITEM_H + 8) + 'px';
            panel.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8)) + 'px';

            const spaceBelow = window.innerHeight - rect.bottom - 8;
            const spaceAbove = rect.top - 8;
            const openUp = spaceBelow < height && spaceAbove > spaceBelow;

            if (openUp) {
                panel.style.top = Math.max(8, rect.top - height - 4) + 'px';
            } else {
                panel.style.top = (rect.bottom + 4) + 'px';
            }
        }

        function openMpSelect(select) {
            closeMpSelect();
            select.focus();

            panel = document.createElement('div');
            panel.setAttribute('data-mp-select-panel', '1');
            panel.className = 'overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-xl shadow-slate-950/15';
            panel.style.position = 'fixed';
            panel.style.zIndex = '2147483646';

            Array.from(select.options).forEach(function (option) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className =
                    'flex h-10 w-full items-center px-3.5 text-left text-sm transition hover:bg-slate-50 ' +
                    (option.disabled ? 'cursor-not-allowed text-slate-300 ' : '') +
                    (option.value === select.value && !option.disabled ? 'font-medium text-slate-900' : 'text-slate-600');
                item.textContent = option.text;
                item.disabled = option.disabled;
                item.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                });
                item.addEventListener('click', function () {
                    if (option.disabled) {
                        return;
                    }
                    select.value = option.value;
                    select.dispatchEvent(new Event('input', { bubbles: true }));
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    closeMpSelect();
                });
                panel.appendChild(item);
            });

            document.body.appendChild(panel);
            placePanel(select);
            openSelect = select;

            panel.addEventListener('wheel', function (event) {
                event.stopPropagation();
            }, { passive: true });
        }

        function setMpSelectValue(id, value) {
            const select = document.getElementById(id);
            if (!select) {
                return;
            }
            select.value = value;
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function scan() {
            document.querySelectorAll('select').forEach(bindSelect);
        }

        window.mpSelect = {
            close: closeMpSelect,
            setValue: setMpSelectValue,
            scan: scan,
        };
        window.closeEqSelectPanel = closeMpSelect;
        window.setEqSelectValue = setMpSelectValue;

        scan();
        new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });

        document.addEventListener('mousedown', function (event) {
            if (!panel) {
                return;
            }
            if (panel.contains(event.target) || event.target === openSelect) {
                return;
            }
            closeMpSelect();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMpSelect();
            }
        });

        window.addEventListener('resize', function () {
            if (openSelect) {
                placePanel(openSelect);
            }
        });

        window.addEventListener('scroll', function (event) {
            if (!openSelect || !panel) {
                return;
            }
            if (panel.contains(event.target)) {
                return;
            }
            placePanel(openSelect);
        }, true);
    })();
</script>
@endpush
