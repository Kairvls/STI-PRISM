{{-- Enhances <select data-searchable="1"> with a filterable dropdown panel. --}}
<script>
    (function () {
        if (window.__prismSearchableSelectReady) {
            return;
        }
        window.__prismSearchableSelectReady = true;

        const ITEM_H = 40;
        const MAX_VISIBLE = 5;
        const SEARCH_H = 48;
        const ADD_H = 84;
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
            if (select.dataset.searchable !== '1') {
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
            const searchH = SEARCH_H + (select.dataset.addUrl ? ADD_H : 0);
            const count = Math.min(select.options.length, MAX_VISIBLE);
            const height = count * ITEM_H + 8 + searchH;
            const width = Math.max(rect.width, 160);
            panel.style.width = width + 'px';
            panel.style.maxHeight = (MAX_VISIBLE * ITEM_H + 8 + searchH) + 'px';
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

            const addUrl = select.dataset.addUrl || '';

            panel = document.createElement('div');
            panel.setAttribute('data-mp-select-panel', '1');
            panel.className = 'flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-950/15';
            panel.style.position = 'fixed';
            panel.style.zIndex = '2147483646';

            const searchWrap = document.createElement('div');
            searchWrap.className = 'shrink-0 border-b border-slate-100 p-2';
            const searchInput = document.createElement('input');
            searchInput.type = 'search';
            searchInput.placeholder = select.dataset.searchPlaceholder || 'Search…';
            searchInput.className = 'h-8 w-full rounded-lg border-0 bg-slate-50 px-3 text-sm text-slate-800 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-900/10';
            searchWrap.appendChild(searchInput);
            panel.appendChild(searchWrap);

            const list = document.createElement('div');
            list.className = 'min-h-0 flex-1 overflow-y-auto py-1';
            panel.appendChild(list);

            const rows = [];
            let currentGroup = null;

            const optionEntries = Array.from(select.options).map(function (option) {
                return {
                    option: option,
                    group: option.parentElement && option.parentElement.tagName === 'OPTGROUP'
                        ? option.parentElement
                        : null,
                };
            });

            // Keep placeholders (empty value) first; sort the rest A–Z within each group.
            const groupBuckets = new Map();
            optionEntries.forEach(function (entry) {
                const key = entry.group || '__root__';
                if (!groupBuckets.has(key)) {
                    groupBuckets.set(key, []);
                }
                groupBuckets.get(key).push(entry);
            });
            const sortedEntries = [];
            groupBuckets.forEach(function (bucket) {
                const placeholders = [];
                const choices = [];
                bucket.forEach(function (entry) {
                    if (String(entry.option.value || '') === '') {
                        placeholders.push(entry);
                    } else {
                        choices.push(entry);
                    }
                });
                choices.sort(function (a, b) {
                    return String(a.option.text || '').localeCompare(
                        String(b.option.text || ''),
                        undefined,
                        { sensitivity: 'base' }
                    );
                });
                placeholders.concat(choices).forEach(function (entry) {
                    sortedEntries.push(entry);
                });
            });

            sortedEntries.forEach(function (entry) {
                const option = entry.option;
                const group = entry.group;
                if (group && group !== currentGroup) {
                    const header = document.createElement('div');
                    header.className = 'px-3.5 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400';
                    header.textContent = group.label;
                    list.appendChild(header);
                    rows.push({ header: header, group: group });
                }
                currentGroup = group;

                const item = document.createElement('button');
                item.type = 'button';
                item.className =
                    'flex h-10 w-full items-center px-3.5 text-left text-sm transition hover:bg-slate-50 ' +
                    (option.disabled ? 'cursor-not-allowed text-slate-300 ' : '') +
                    (option.value === select.value && !option.disabled ? 'font-medium text-slate-900' : 'text-slate-600');
                const label = document.createElement('span');
                label.className = 'min-w-0 flex-1 truncate';
                label.textContent = option.text;
                item.appendChild(label);
                item.title = option.text.trim();
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
                    if (option.dataset.categoryId) {
                        applyLinkedCategory(select, option.dataset.categoryId);
                    }
                    select.dispatchEvent(new Event('input', { bubbles: true }));
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    closeMpSelect();
                });
                list.appendChild(item);
                rows.push({
                    item: item,
                    group: group,
                    text: (option.text + ' ' + (group ? group.label : '')).toLowerCase(),
                });
            });

            const empty = document.createElement('p');
            empty.className = 'hidden px-3.5 py-3 text-sm text-slate-400';
            empty.textContent = addUrl ? 'No matches. Add it below.' : 'No matches';
            list.appendChild(empty);

            const filter = function () {
                const terms = searchInput.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
                const visibleGroups = new Set();
                let visible = 0;

                rows.forEach(function (row) {
                    if (!row.item) return;
                    const match = terms.every(function (term) { return row.text.includes(term); });
                    row.item.classList.toggle('hidden', !match);
                    if (match) {
                        visible++;
                        if (row.group) visibleGroups.add(row.group);
                    }
                });
                rows.forEach(function (row) {
                    if (row.header) row.header.classList.toggle('hidden', !visibleGroups.has(row.group));
                });
                empty.classList.toggle('hidden', visible > 0);
            };

            searchInput.addEventListener('input', filter);
            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    const first = rows.find(function (row) {
                        return row.item && !row.item.disabled && !row.item.classList.contains('hidden');
                    });
                    if (first) first.item.click();
                } else if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    const first = rows.find(function (row) {
                        return row.item && !row.item.classList.contains('hidden');
                    });
                    if (first) first.item.focus();
                }
            });

            if (addUrl) {
                panel.appendChild(buildAddFooter(select, addUrl, searchInput));
            }

            document.body.appendChild(panel);
            placePanel(select);
            openSelect = select;
            searchInput.focus();

            panel.addEventListener('wheel', function (event) {
                event.stopPropagation();
            }, { passive: true });
        }

        function buildAddFooter(select, addUrl, searchInput) {
            const defaultPlaceholder = select.dataset.addPlaceholder || 'New name';
            const footer = document.createElement('div');
            footer.className = 'shrink-0 border-t border-slate-100 bg-slate-50 p-2';

            const title = document.createElement('p');
            title.className = 'mb-1 px-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400';
            title.textContent = select.dataset.addLabel || 'Add new';
            footer.appendChild(title);

            const row = document.createElement('div');
            row.className = 'flex items-center gap-1.5';
            const input = document.createElement('input');
            input.type = 'text';
            input.maxLength = 255;
            input.placeholder = defaultPlaceholder;
            input.className = 'h-8 min-w-0 flex-1 rounded-lg border-0 bg-white px-3 text-sm text-slate-800 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:ring-2 focus:ring-slate-900/10';
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = 'Add';
            button.className = 'h-8 shrink-0 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-blue-800 disabled:opacity-60';
            row.appendChild(input);
            row.appendChild(button);
            footer.appendChild(row);

            const error = document.createElement('p');
            error.className = 'mt-1 hidden px-0.5 text-[11px] text-rose-600';
            footer.appendChild(error);

            const showError = function (message) {
                error.textContent = message;
                error.classList.remove('hidden');
            };

            const submit = async function () {
                const name = (input.value || (searchInput ? searchInput.value : '')).trim();
                if (!name) {
                    showError('Type the name first.');
                    input.focus();
                    return;
                }
                const body = { name: name };
                const categorySelect = linkedCategorySelect(select);
                if (select.dataset.addRequiresCategory === '1') {
                    const categoryId = categorySelect ? String(categorySelect.value || '').trim() : '';
                    if (!categoryId) {
                        showError('Select a classification first.');
                        return;
                    }
                    body.category_id = categoryId;
                }
                error.classList.add('hidden');
                button.disabled = true;
                button.textContent = 'Adding…';
                try {
                    const res = await fetch(addUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(body),
                    });
                    const data = await res.json().catch(function () { return {}; });
                    if (!res.ok || !data.id) {
                        const first = data.errors ? Object.values(data.errors).flat()[0] : null;
                        throw new Error(first || data.message || 'Could not add it. Try again.');
                    }
                    const id = String(data.id);
                    const label = String(data.name || data.id);
                    const categoryId = String(data.category_id || (categorySelect && categorySelect.value) || '');
                    document.querySelectorAll('select[data-searchable="1"]').forEach(function (peer) {
                        if (peer === select) {
                            return;
                        }
                        if ((peer.dataset.addUrl || '') !== addUrl) {
                            return;
                        }
                        if (Array.from(peer.options).some(function (opt) { return opt.value === id; })) {
                            return;
                        }
                        const peerOpt = new Option(label, id);
                        if (categoryId) {
                            peerOpt.dataset.categoryId = categoryId;
                        }
                        peer.add(peerOpt);
                    });
                    let option = Array.from(select.options).find(function (opt) { return opt.value === id; });
                    if (!option) {
                        option = new Option(label, id);
                        if (categoryId) {
                            option.dataset.categoryId = categoryId;
                        }
                        select.add(option);
                    } else if (categoryId) {
                        option.dataset.categoryId = categoryId;
                    }
                    select.value = id;
                    if (categoryId) {
                        applyLinkedCategory(select, categoryId);
                    }
                    select.dispatchEvent(new Event('input', { bubbles: true }));
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    closeMpSelect();
                } catch (e) {
                    showError(e.message || 'Could not add it. Try again.');
                    button.disabled = false;
                    button.textContent = 'Add';
                }
            };

            button.addEventListener('click', submit);
            input.addEventListener('keydown', function (event) {
                event.stopPropagation();
                if (event.key === 'Enter') {
                    event.preventDefault();
                    submit();
                } else if (event.key === 'Escape') {
                    closeMpSelect();
                }
            });
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    input.placeholder = searchInput.value.trim() || defaultPlaceholder;
                });
            }

            return footer;
        }

        function linkedCategorySelect(select) {
            const id = select && select.dataset ? select.dataset.linkCategorySelect : '';
            return id ? document.getElementById(id) : null;
        }

        function applyLinkedCategory(select, categoryId) {
            const target = linkedCategorySelect(select);
            const next = String(categoryId || '');
            if (!target || !next) {
                return;
            }
            if (String(target.value || '') === next) {
                return;
            }
            target.dataset.autoCategory = '1';
            target.value = next;
            target.dispatchEvent(new Event('input', { bubbles: true }));
            target.dispatchEvent(new Event('change', { bubbles: true }));
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
            document.querySelectorAll('select[data-searchable="1"]').forEach(bindSelect);
        }

        window.mpSelect = window.mpSelect || {
            close: closeMpSelect,
            setValue: setMpSelectValue,
            scan: scan,
        };
        window.closeEqSelectPanel = window.closeEqSelectPanel || closeMpSelect;
        window.setEqSelectValue = window.setEqSelectValue || setMpSelectValue;

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
