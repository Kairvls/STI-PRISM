{{-- Teacher online borrow modal (PIN-verified). Catalog loaded live. --}}
<div id="teacherBorrowModal"
     data-no-backdrop-close
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-3 sm:p-4 overscroll-none"
     style="background: rgba(11, 18, 32, 0.7);"
     role="dialog"
     aria-modal="true"
     aria-labelledby="teacherBorrowTitle">
    <div class="teacher-borrow-wrap relative flex max-h-[min(920px,92vh)] w-full max-w-[720px] flex-col overflow-hidden rounded-2xl bg-white shadow-[0_24px_80px_rgba(15,23,42,0.28)]">
        <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Online borrowing</p>
                <h2 id="teacherBorrowTitle" class="mt-0.5 text-lg font-semibold tracking-tight text-slate-950 sm:text-xl">
                    Borrow equipment
                </h2>
                <p id="teacherBorrowIdentity" class="mt-1 truncate text-sm text-slate-500">
                    Verify your employee ID to continue.
                </p>
            </div>
            <div class="relative z-30 flex shrink-0 items-center gap-2">
                <div id="teacherBorrowCautionWrap" class="relative hidden">
                    <button type="button"
                            id="teacherBorrowCautionBtn"
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
                            aria-expanded="false"
                            aria-controls="teacherBorrowCautionPanel"
                            aria-label="Equipment you still have">
                        <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                    </button>
                    <div id="teacherBorrowCautionPanel"
                         class="absolute right-0 top-[calc(100%+0.45rem)] hidden max-h-[min(24rem,60vh)] w-80 overflow-y-auto rounded-xl border border-slate-200 bg-white p-3 shadow-xl"
                         role="dialog"
                         aria-label="Equipment you still have"></div>
                </div>
                <button type="button"
                        onclick="requestCloseTeacherBorrowModal()"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
                        aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        </div>

        <form id="teacherBorrowForm" class="flex min-h-0 flex-1 flex-col" novalidate>
            @csrf
            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4 sm:px-6 sm:py-5">
                {{-- Equipment cart --}}
                <div class="space-y-3 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            Equipment cart <span class="text-red-500">*</span>
                        </p>
                        <p id="teacherBorrowCartBadge" class="rounded-lg bg-white px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200/80">
                            No items yet
                        </p>
                    </div>

                    <div class="relative">
                        <label for="teacherBorrowSearch" class="mb-1.5 block text-sm text-slate-600">Find equipment</label>
                        <p class="mb-1.5 text-xs text-slate-400">Only equipment still in inventory. Deployed, under maintenance, damaged, and defective equipment cannot be borrowed. Identical stock is listed once as a bulk item — add it, then set how many to borrow.</p>
                        <div class="flex gap-2">
                            <div class="relative min-w-0 flex-1">
                                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                                <input
                                    type="text"
                                    id="teacherBorrowSearch"
                                    placeholder="Search by name, room, or asset tag"
                                    autocomplete="off"
                                    class="h-11 w-full rounded-xl border-0 bg-white pl-10 pr-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                >
                            </div>
                            <button
                                type="button"
                                id="teacherBorrowAddBtn"
                                class="inline-flex h-11 shrink-0 items-center rounded-xl bg-[#0025cc] px-4 text-sm font-medium text-white transition hover:bg-[#001fad]"
                            >
                                Add
                            </button>
                        </div>
                        <div id="teacherBorrowSearchResults"
                             class="absolute left-0 right-0 top-[calc(100%+0.35rem)] z-40 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                            <div id="teacherBorrowSearchList" class="max-h-64 overflow-y-auto py-1"></div>
                        </div>
                        <p id="teacherBorrowSelectedHint" class="mt-2 hidden text-xs text-slate-500"></p>
                        <p id="teacherBorrowPickerError" class="mt-2 hidden text-xs font-medium text-rose-600"></p>
                    </div>

                    <div id="teacherBorrowCart" class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                        <div id="teacherBorrowCartEmpty" class="px-4 py-8 text-center">
                            <p class="text-sm font-medium text-slate-700">No equipment added</p>
                            <p class="mt-1 text-xs text-slate-400">Add items here before submitting the borrow.</p>
                        </div>
                        <div id="teacherBorrowCartLines" class="hidden divide-y divide-slate-100"></div>
                    </div>
                    <p id="teacherBorrowCartError" class="hidden text-xs font-medium text-rose-600"></p>
                </div>

                {{-- Location --}}
                <div class="space-y-3 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <div>
                        <label for="teacherBorrowLocation" class="mb-1.5 block text-sm text-slate-600">
                            Location <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="teacherBorrowLocation"
                            type="text"
                            required
                            maxlength="150"
                            placeholder="Where the equipment will be used"
                            class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                        >
                    </div>
                </div>

                {{-- Schedule --}}
                <div class="space-y-3 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Schedule</p>
                        <p class="mt-1 text-sm text-slate-500">One borrow / return window (dates) for the whole cart.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="teacherBorrowDate" class="mb-1.5 block text-sm text-slate-600">
                                Borrow from <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="teacherBorrowDate"
                                type="date"
                                required
                                class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:ring-2 focus:ring-slate-900/10"
                            >
                        </div>
                        <div>
                            <label for="teacherBorrowReturn" class="mb-1.5 block text-sm text-slate-600">
                                Expected return by <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="teacherBorrowReturn"
                                type="date"
                                required
                                class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:ring-2 focus:ring-slate-900/10"
                            >
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="space-y-3 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Notes</p>
                        <p class="mt-1 text-sm text-slate-500">Why the equipment is needed.</p>
                    </div>
                    <div>
                        <label for="teacherBorrowPurpose" class="mb-1.5 block text-sm text-slate-600">
                            Purpose <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="teacherBorrowPurpose"
                            rows="2"
                            required
                            maxlength="2000"
                            placeholder="Describe why the equipment is being borrowed"
                            class="w-full resize-none rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                        ></textarea>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 flex-col gap-2 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p id="teacherBorrowFormError" class="hidden text-xs font-medium text-rose-600 sm:max-w-sm"></p>
                <div class="flex items-center justify-end gap-2 sm:ml-auto">
                    <button
                        type="button"
                        onclick="requestCloseTeacherBorrowModal()"
                        class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        id="teacherBorrowSubmitBtn"
                        class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fa8] disabled:opacity-60"
                    >
                        Submit borrow request
                    </button>
                </div>
            </div>
        </form>

        <div id="teacherBorrowStatusPanel" class="hidden min-h-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-5 py-4 sm:px-6 sm:py-5">
                <p id="teacherBorrowStatusLoading" class="hidden text-sm text-slate-500">Loading your requests…</p>
                <p id="teacherBorrowStatusError" class="hidden rounded-xl bg-red-50 px-3.5 py-3 text-sm text-red-700 ring-1 ring-red-100"></p>
                <p id="teacherBorrowStatusEmpty" class="hidden rounded-xl bg-slate-50 px-3.5 py-4 text-sm text-slate-600 ring-1 ring-slate-200/80">
                    No online borrow requests found for your account yet.
                </p>
                <div id="teacherBorrowStatusList" class="space-y-3"></div>
            </div>
            <div class="flex shrink-0 items-center justify-between gap-3 border-t border-slate-100 px-5 py-3.5 sm:px-6">
                <button type="button"
                        id="teacherBorrowStatusRefresh"
                        class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Refresh
                </button>
                <button type="button"
                        id="teacherBorrowStatusBack"
                        class="inline-flex h-10 items-center rounded-xl bg-[#0025cc] px-4 text-sm font-medium text-white transition hover:bg-[#001fad]">
                    Back to borrow
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('teacherBorrowModal');
    const form = document.getElementById('teacherBorrowForm');
    if (!modal || !form) return;

    const csrf = () => form.querySelector('input[name="_token"]')?.value || '';
    const identityEl = document.getElementById('teacherBorrowIdentity');
    const titleEl = document.getElementById('teacherBorrowTitle');
    const statusPanel = document.getElementById('teacherBorrowStatusPanel');
    const statusLoading = document.getElementById('teacherBorrowStatusLoading');
    const statusError = document.getElementById('teacherBorrowStatusError');
    const statusEmpty = document.getElementById('teacherBorrowStatusEmpty');
    const statusList = document.getElementById('teacherBorrowStatusList');
    const statusRefresh = document.getElementById('teacherBorrowStatusRefresh');
    const statusBack = document.getElementById('teacherBorrowStatusBack');
    const searchInput = document.getElementById('teacherBorrowSearch');
    const addBtn = document.getElementById('teacherBorrowAddBtn');
    const resultsWrap = document.getElementById('teacherBorrowSearchResults');
    const resultsList = document.getElementById('teacherBorrowSearchList');
    const selectedHint = document.getElementById('teacherBorrowSelectedHint');
    const pickerError = document.getElementById('teacherBorrowPickerError');
    const cartBadge = document.getElementById('teacherBorrowCartBadge');
    const cartEmpty = document.getElementById('teacherBorrowCartEmpty');
    const cartLines = document.getElementById('teacherBorrowCartLines');
    const cartError = document.getElementById('teacherBorrowCartError');
    const formError = document.getElementById('teacherBorrowFormError');
    const submitBtn = document.getElementById('teacherBorrowSubmitBtn');
    const dateInput = document.getElementById('teacherBorrowDate');
    const returnInput = document.getElementById('teacherBorrowReturn');
    const purposeInput = document.getElementById('teacherBorrowPurpose');
    const locationInput = document.getElementById('teacherBorrowLocation');
    const cautionWrap = document.getElementById('teacherBorrowCautionWrap');
    const cautionBtn = document.getElementById('teacherBorrowCautionBtn');
    const cautionPanel = document.getElementById('teacherBorrowCautionPanel');

    let catalog = [];
    let selected = null;
    let cart = [];
    let borrower = null;
    let highlight = 0;
    let loading = false;
    let viewMode = 'form';
    let statusLoadingFlag = false;
    let defaultBorrowDate = '';
    let defaultReturnDate = '';

    function refreshIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    function showText(el, text, show) {
        if (!el) return;
        el.textContent = text || '';
        el.classList.toggle('hidden', !show || !text);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function meta(item) {
        const bits = [];
        if (item.room) bits.push(item.room);
        if (item.assetTag) bits.push(item.assetTag);
        bits.push((item.tracking || 'Individual') + ' · ' + item.available + ' available');
        return bits.join(' · ');
    }

    function filtered() {
        const q = String(searchInput.value || '').trim().toLowerCase();
        return catalog
            .filter((item) => item.available > 0)
            .filter((item) => {
                if (!q) return true;
                return [item.name, item.room, item.assetTag].join(' ').toLowerCase().includes(q);
            })
            .slice(0, 40);
    }

    function renderResults() {
        const rows = filtered();
        if (!rows.length) {
            resultsList.innerHTML = '<p class="px-3 py-4 text-sm text-slate-400">No matching equipment.</p>';
            return;
        }
        resultsList.innerHTML = rows.map((item, index) => `
            <button type="button"
                data-equip-id="${item.id}"
                class="flex w-full items-start gap-3 px-3 py-2.5 text-left transition ${index === highlight ? 'bg-[#0025cc]/5' : 'hover:bg-slate-50'}">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                    <i data-lucide="package" class="h-4 w-4"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-medium text-slate-900">${escapeHtml(item.name)}</span>
                    <span class="mt-0.5 block truncate text-xs text-slate-500">${escapeHtml(meta(item))}</span>
                </span>
                <span class="shrink-0 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">${item.available} avail</span>
            </button>
        `).join('');
        refreshIcons();
    }

    function choose(item) {
        selected = item;
        searchInput.value = item.name;
        resultsWrap.classList.add('hidden');
        showText(pickerError, '', false);
        showText(selectedHint, 'Selected: ' + item.name + ' · up to ' + item.available + ' available', true);
    }

    function renderCart() {
        const totalQty = cart.reduce((sum, line) => sum + (Number(line.quantity) || 0), 0);
        cartBadge.textContent = cart.length
            ? (cart.length + ' line' + (cart.length === 1 ? '' : 's') + ' · ' + totalQty + ' pcs')
            : 'No items yet';

        if (!cart.length) {
            cartEmpty.classList.remove('hidden');
            cartLines.classList.add('hidden');
            cartLines.innerHTML = '';
            return;
        }

        cartEmpty.classList.add('hidden');
        cartLines.classList.remove('hidden');
        cartLines.innerHTML = cart.map((line, index) => `
            <div class="flex flex-wrap items-center gap-3 px-4 py-3" data-cart-index="${index}">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-900">${escapeHtml(line.name)}</p>
                    <p class="mt-0.5 truncate text-xs text-slate-500">${escapeHtml(meta(line))}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" inputmode="numeric" autocomplete="off" value="${line.quantity}"
                        data-cart-qty="${index}"
                        class="h-9 w-20 rounded-lg border border-slate-200 px-2 text-sm">
                    <span class="text-xs text-slate-400">/${line.available}</span>
                    <button type="button" data-cart-remove="${index}"
                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-red-700 transition hover:bg-slate-50"
                        aria-label="Remove item">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>
        `).join('');
        refreshIcons();
    }

    function addSelected() {
        showText(pickerError, '', false);
        if (!selected) {
            showText(pickerError, 'Search and select an equipment item first.', true);
            return;
        }
        const qty = 1;
        if (qty > selected.available) {
            showText(pickerError, 'Only ' + selected.available + ' available for this item.', true);
            return;
        }
        const existing = cart.find((line) => line.id === selected.id);
        if (existing) {
            const next = existing.quantity + qty;
            if (next > existing.available) {
                showText(pickerError, 'Cart would exceed available quantity (' + existing.available + ').', true);
                return;
            }
            existing.quantity = next;
        } else {
            cart.push({
                id: selected.id,
                name: selected.name,
                room: selected.room,
                assetTag: selected.assetTag,
                tracking: selected.tracking,
                available: selected.available,
                quantity: qty,
            });
        }
        selected = null;
        searchInput.value = '';
        showText(selectedHint, '', false);
        showText(cartError, '', false);
        renderCart();
    }

    function setIdentity(profile) {
        borrower = profile || null;
        if (!borrower) {
            identityEl.textContent = 'Verify your employee ID to continue.';
            return;
        }
        const bits = [borrower.name, borrower.employee_id, borrower.department].filter(Boolean);
        identityEl.textContent = 'Borrowing as ' + bits.join(' · ');
    }

    function closeCautionPanel() {
        if (!cautionPanel || !cautionBtn) return;
        cautionPanel.classList.add('hidden');
        cautionBtn.setAttribute('aria-expanded', 'false');
    }

    function cautionList(title, hint, items) {
        if (!items.length) return '';
        const lines = items.map(function (item) {
            const qty = Number(item.qty) > 1 ? ' ×' + Number(item.qty) : '';
            return '<li class="flex items-start justify-between gap-3 py-1.5">' +
                '<span class="min-w-0 text-sm text-slate-800">' + escapeHtml(item.name) + escapeHtml(qty) + '</span>' +
                '<span class="shrink-0 text-right text-xs text-slate-500">' + escapeHtml(item.days_label || '') + '</span>' +
                '</li>';
        }).join('');

        return '<div class="mt-3">' +
            '<p class="text-xs font-semibold text-slate-800">' + title + '</p>' +
            '<p class="mt-0.5 text-[11px] leading-4 text-slate-400">' + hint + '</p>' +
            '<ul class="mt-1 divide-y divide-slate-100">' + lines + '</ul>' +
            '</div>';
    }

    function setCaution(caution) {
        if (!cautionWrap || !cautionBtn || !cautionPanel) return;
        const overdueItems = Array.isArray(caution?.overdue_items) ? caution.overdue_items : [];
        const borrowedItems = Array.isArray(caution?.borrowed_items) ? caution.borrowed_items : [];
        closeCautionPanel();

        if (!overdueItems.length && !borrowedItems.length) {
            cautionWrap.classList.add('hidden');
            cautionPanel.innerHTML = '';
            return;
        }

        cautionWrap.classList.remove('hidden');
        cautionPanel.innerHTML =
            '<p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Equipment you still have</p>' +
            '<p class="mt-1 text-xs leading-5 text-slate-500">You can still submit a new request.</p>' +
            cautionList('Overdue', 'Past the return date.', overdueItems) +
            cautionList('Still borrowed', 'With you, and not yet due. The count is how long you have had it.', borrowedItems);
        refreshIcons();
    }

    if (cautionBtn && cautionPanel && cautionWrap) {
        cautionBtn.addEventListener('click', function (event) {
            event.stopPropagation();
            const willOpen = cautionPanel.classList.contains('hidden');
            cautionPanel.classList.toggle('hidden', !willOpen);
            cautionBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
        document.addEventListener('click', function (event) {
            if (cautionWrap.classList.contains('hidden')) return;
            if (cautionWrap.contains(event.target)) return;
            closeCautionPanel();
        });
    }

    function statusTone(status) {
        switch (status) {
            case 'Pending': return 'bg-amber-50 text-amber-800 ring-amber-100';
            case 'Borrowed': return 'bg-emerald-50 text-emerald-800 ring-emerald-100';
            case 'Overdue': return 'bg-orange-50 text-orange-800 ring-orange-100';
            case 'Denied': return 'bg-red-50 text-red-700 ring-red-100';
            case 'Returned': return 'bg-slate-100 text-slate-700 ring-slate-200';
            default: return 'bg-slate-50 text-slate-700 ring-slate-200';
        }
    }

    function renderStatusSlips(slips) {
        statusList.innerHTML = '';
        if (!Array.isArray(slips) || !slips.length) {
            statusEmpty.classList.remove('hidden');
            return;
        }
        statusEmpty.classList.add('hidden');

        slips.forEach((slip) => {
            const card = document.createElement('article');
            card.className = 'rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80';
            const items = Array.isArray(slip.items) ? slip.items : [];
            const itemHtml = items.map((item) => `
                <li class="flex items-start justify-between gap-3 text-sm text-slate-700">
                    <span class="min-w-0">${escapeHtml(item.name)}</span>
                    <span class="shrink-0 text-slate-500">×${Number(item.qty) || 1}</span>
                </li>
            `).join('');
            const denial = slip.status === 'Denied' && slip.denial_reason
                ? `<p class="mt-2 text-sm text-red-700"><span class="font-medium">Reason:</span> ${escapeHtml(slip.denial_reason)}</p>`
                : '';

            card.innerHTML = `
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Slip</p>
                        <p class="mt-0.5 text-base font-semibold tracking-tight text-[#0025cc]">${escapeHtml(slip.slip_no)}</p>
                    </div>
                    <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium ring-1 ${statusTone(slip.status)}">
                        ${escapeHtml(slip.status_label || slip.status || 'Unknown')}
                    </span>
                </div>
                <div class="mt-3 grid gap-1.5 text-sm text-slate-600 sm:grid-cols-2">
                    <p><span class="font-medium text-slate-800">Borrow from:</span> ${escapeHtml(slip.borrow_from || '—')}</p>
                    <p><span class="font-medium text-slate-800">Return by:</span> ${escapeHtml(slip.expected_return || '—')}</p>
                </div>
                ${denial}
                <ul class="mt-3 space-y-1.5 border-t border-slate-200/80 pt-3">${itemHtml}</ul>
            `;
            statusList.appendChild(card);
        });
    }

    async function loadStatus() {
        if (statusLoadingFlag) return;
        statusLoadingFlag = true;
        showText(statusError, '', false);
        statusEmpty.classList.add('hidden');
        statusLoading.classList.remove('hidden');
        if (statusRefresh) statusRefresh.disabled = true;

        try {
            const response = await fetch('/reporter/borrow/status', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const data = await response.json().catch(() => ({}));
            if (response.status === 401 || data.pin_required) {
                throw new Error(data.message || 'Your verification expired. Enter your employee ID and PIN again.');
            }
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Could not load borrow status.');
            }
            if (data.borrower) setIdentity(data.borrower);
            renderStatusSlips(data.slips || []);
        } catch (err) {
            statusList.innerHTML = '';
            showText(statusError, err.message || 'Could not load borrow status.', true);
        } finally {
            statusLoadingFlag = false;
            statusLoading.classList.add('hidden');
            if (statusRefresh) statusRefresh.disabled = false;
            refreshIcons();
        }
    }

    function showBorrowView(mode) {
        viewMode = mode === 'status' ? 'status' : 'form';
        const showingStatus = viewMode === 'status';

        form.classList.toggle('hidden', showingStatus);
        statusPanel.classList.toggle('hidden', !showingStatus);
        statusPanel.classList.toggle('flex', showingStatus);

        if (titleEl) {
            titleEl.textContent = showingStatus ? 'Check borrow status' : 'Borrow equipment';
        }

        if (showingStatus) {
            loadStatus();
        }
        refreshIcons();
    }

    function toLocalDateValue(date) {
        const pad = (n) => String(n).padStart(2, '0');
        return date.getFullYear()
            + '-' + pad(date.getMonth() + 1)
            + '-' + pad(date.getDate());
    }

    function resetFormFields() {
        catalog = [];
        selected = null;
        cart = [];
        highlight = 0;
        searchInput.value = '';
        purposeInput.value = '';
        if (locationInput) locationInput.value = '';
        showText(pickerError, '', false);
        showText(selectedHint, '', false);
        showText(cartError, '', false);
        showText(formError, '', false);
        showText(statusError, '', false);
        statusEmpty.classList.add('hidden');
        statusList.innerHTML = '';
        setCaution(null);
        resultsWrap.classList.add('hidden');
        renderCart();
        showBorrowView('form');

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const later = new Date(today.getTime() + (1 * 24 * 60 * 60 * 1000));
        dateInput.value = toLocalDateValue(today);
        returnInput.value = toLocalDateValue(later);
        dateInput.min = toLocalDateValue(today);
        syncReturnMin();
        defaultBorrowDate = dateInput.value;
        defaultReturnDate = returnInput.value;
    }

    function formHasInput() {
        if (cart.length > 0) return true;
        if (selected) return true;
        if (String(searchInput.value || '').trim() !== '') return true;
        if (String(purposeInput.value || '').trim() !== '') return true;
        if (locationInput && String(locationInput.value || '').trim() !== '') return true;
        if (String(dateInput.value || '') !== defaultBorrowDate) return true;
        if (String(returnInput.value || '') !== defaultReturnDate) return true;
        return false;
    }

    function syncReturnMin() {
        const borrowDate = dateInput.value || '';
        if (!borrowDate) {
            returnInput.removeAttribute('min');
            return;
        }
        returnInput.min = borrowDate;
        if (returnInput.value && returnInput.value < borrowDate) {
            returnInput.value = borrowDate;
        }
    }

    async function loadBootstrap() {
        loading = true;
        submitBtn.disabled = true;
        identityEl.textContent = 'Loading your borrow form…';
        try {
            const response = await fetch('/reporter/borrow/bootstrap', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Could not load borrow form.');
            }
            catalog = Array.isArray(data.equipment) ? data.equipment : [];
            setIdentity(data.borrower);
            setCaution(data.caution || null);
            if (!catalog.length) {
                showText(formError, 'No equipment is available to borrow right now.', true);
            }
        } catch (err) {
            setIdentity(null);
            setCaution(null);
            showText(formError, err.message || 'Could not load borrow form.', true);
            throw err;
        } finally {
            loading = false;
            submitBtn.disabled = false;
            refreshIcons();
        }
    }

    window.openTeacherBorrowForm = async function () {
        resetFormFields();
        if (typeof window.showModal === 'function') {
            window.showModal(modal);
        } else {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden', 'modal-open');
        }
        refreshIcons();
        try {
            await loadBootstrap();
        } catch (_) {
            // Keep modal open so the teacher can cancel / retry after re-verify.
        }
        if (typeof window.startTeacherBorrowIdleWatch === 'function') {
            window.startTeacherBorrowIdleWatch();
        }
    };

    if (statusBack) {
        statusBack.addEventListener('click', function () {
            showBorrowView('form');
        });
    }
    if (statusRefresh) {
        statusRefresh.addEventListener('click', function () {
            loadStatus();
        });
    }

    window.requestCloseTeacherBorrowModal = async function () {
        if (formHasInput()) {
            let discard = false;
            if (window.Swal) {
                const result = await Swal.fire({
                    icon: 'warning',
                    title: 'Discard changes?',
                    text: 'The borrow details you entered will be lost.',
                    showCancelButton: true,
                    confirmButtonText: 'Discard',
                    cancelButtonText: 'Keep editing',
                    confirmButtonColor: '#0025cc',
                    reverseButtons: true,
                    focusCancel: true,
                });
                discard = !!result.isConfirmed;
            } else {
                discard = window.confirm('Discard changes?');
            }
            if (!discard) return;
        }
        window.closeTeacherBorrowModal();
    };

    window.closeTeacherBorrowModal = function (options) {
        options = options || {};
        if (typeof window.stopTeacherBorrowIdleWatch === 'function') {
            window.stopTeacherBorrowIdleWatch();
        }
        if (!options.keepSession) {
            fetch('/reporter/exit', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf(),
                },
                credentials: 'same-origin',
                body: '{}',
            }).catch(() => {});
        }
        if (typeof window.hideModal === 'function') {
            window.hideModal(modal);
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden', 'modal-open');
        }
        resetFormFields();
        setIdentity(null);
        window.__pinGateIntent = 'report';
    };

    window.onTeacherBorrowVerified = function (payload) {
        // Close report/gate modal without ending the PIN session.
        if (typeof window.stopReportModalIdleWatch === 'function') {
            window.stopReportModalIdleWatch();
        }
        if (typeof window.clearReportPhase2Mark === 'function') {
            window.clearReportPhase2Mark();
        }
        if (typeof window.hideModal === 'function') {
            const reportModal = document.getElementById('reportModal');
            if (reportModal) {
                reportModal.classList.remove('is-expanded');
                window.hideModal(reportModal);
            }
        }
        if (typeof window.reporterGateResetSilent === 'function') {
            window.reporterGateResetSilent();
        }
        window.openTeacherBorrowForm();
    };

    searchInput.addEventListener('focus', () => {
        resultsWrap.classList.remove('hidden');
        renderResults();
    });
    searchInput.addEventListener('input', () => {
        selected = null;
        showText(selectedHint, '', false);
        highlight = 0;
        resultsWrap.classList.remove('hidden');
        renderResults();
    });
    searchInput.addEventListener('keydown', (e) => {
        const rows = filtered();
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!rows.length) return;
            highlight = (highlight + 1) % rows.length;
            renderResults();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (!rows.length) return;
            highlight = (highlight - 1 + rows.length) % rows.length;
            renderResults();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (rows[highlight]) choose(rows[highlight]);
        } else if (e.key === 'Escape') {
            resultsWrap.classList.add('hidden');
        }
    });

    resultsList.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-equip-id]');
        if (!btn) return;
        const id = Number(btn.getAttribute('data-equip-id'));
        const item = catalog.find((row) => row.id === id);
        if (item) choose(item);
    });

    document.addEventListener('click', (e) => {
        if (!resultsWrap.contains(e.target) && e.target !== searchInput) {
            resultsWrap.classList.add('hidden');
        }
    });

    addBtn.addEventListener('click', addSelected);

    function readCartQty(input, line, finalize) {
        const max = Math.max(1, Number(line.available) || 1);
        const digits = String(input.value || '').replace(/\D/g, '');
        if (digits === '') {
            if (finalize) {
                line.quantity = 1;
                input.value = '1';
            }
            return;
        }
        let qty = parseInt(digits, 10);
        if (qty > max) qty = max;
        if (finalize && qty < 1) qty = 1;
        line.quantity = qty;
        input.value = String(qty);
    }

    cartLines.addEventListener('input', (e) => {
        const input = e.target.closest('[data-cart-qty]');
        if (!input) return;
        const line = cart[Number(input.getAttribute('data-cart-qty'))];
        if (!line) return;
        readCartQty(input, line, false);
    });

    cartLines.addEventListener('focusout', (e) => {
        const input = e.target.closest('[data-cart-qty]');
        if (!input) return;
        const line = cart[Number(input.getAttribute('data-cart-qty'))];
        if (!line) return;
        readCartQty(input, line, true);
    });

    cartLines.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-cart-remove]');
        if (!btn) return;
        const index = Number(btn.getAttribute('data-cart-remove'));
        cart.splice(index, 1);
        renderCart();
    });

    dateInput.addEventListener('change', syncReturnMin);
    dateInput.addEventListener('input', syncReturnMin);

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        showText(formError, '', false);
        showText(cartError, '', false);

        if (!cart.length) {
            showText(cartError, 'Add at least one equipment item to the cart.', true);
            showText(formError, 'Add at least one equipment item to the cart.', true);
            return;
        }
        cart.forEach((line) => {
            const max = Math.max(1, Number(line.available) || 1);
            let qty = parseInt(String(line.quantity ?? '').replace(/\D/g, ''), 10);
            if (!Number.isFinite(qty) || qty < 1) qty = 1;
            if (qty > max) qty = max;
            line.quantity = qty;
        });
        renderCart();
        if (!dateInput.value) {
            showText(formError, 'Enter the borrow date.', true);
            dateInput.focus();
            return;
        }
        if (!returnInput.value) {
            showText(formError, 'Enter the expected return date.', true);
            returnInput.focus();
            return;
        }
        const borrowAt = new Date(dateInput.value + 'T00:00:00');
        const returnAt = new Date(returnInput.value + 'T00:00:00');
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (Number.isNaN(borrowAt.getTime()) || Number.isNaN(returnAt.getTime())) {
            showText(formError, 'Enter a valid borrow / return date.', true);
            return;
        }
        if (borrowAt < today) {
            showText(formError, 'Borrow date cannot be in the past.', true);
            dateInput.focus();
            return;
        }
        if (returnAt < borrowAt) {
            showText(formError, 'Expected return must be on or after the borrow date.', true);
            returnInput.focus();
            return;
        }
        const maxReturn = new Date(borrowAt.getTime() + (30 * 24 * 60 * 60 * 1000));
        if (returnAt > maxReturn) {
            showText(formError, 'Expected return cannot be more than 30 days after the borrow date.', true);
            returnInput.focus();
            return;
        }
        if (locationInput && !String(locationInput.value || '').trim()) {
            showText(formError, 'Enter where the equipment will be used.', true);
            locationInput.focus();
            return;
        }
        if (!String(purposeInput.value || '').trim()) {
            showText(formError, 'Enter the purpose of this borrow.', true);
            purposeInput.focus();
            return;
        }

        submitBtn.disabled = true;
        const originalLabel = submitBtn.textContent;
        submitBtn.textContent = 'Submitting…';

        try {
            const response = await fetch('/reporter/borrow', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    borrowing_date: dateInput.value,
                    borrowing_expected_return_date: returnInput.value,
                    borrowing_event_location: locationInput ? String(locationInput.value || '').trim() : '',
                    borrowing_purpose: String(purposeInput.value || '').trim(),
                    items: cart.map((line) => ({
                        equipment_id: line.id,
                        quantity: line.quantity,
                    })),
                }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                if (data.pin_required) {
                    window.closeTeacherBorrowModal({ keepSession: true });
                    if (typeof window.openBorrowModal === 'function') {
                        window.openBorrowModal();
                    }
                }
                throw new Error(data.message || 'Could not submit the borrow request.');
            }

            window.closeTeacherBorrowModal({ keepSession: true });

            const formatWhen = (value) => {
                if (!value) return '—';
                const raw = String(value).trim();
                const d = new Date(/^\d{4}-\d{2}-\d{2}$/.test(raw) ? raw + 'T00:00:00' : raw.replace(' ', 'T'));
                if (Number.isNaN(d.getTime())) return raw;
                return d.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                });
            };

            const slip = data.slip_no || '';
            const nextStep = data.next_step
                || 'Check your employee or school email for your borrow slip. Show that slip when you claim the equipment.';
            const html = `
                <div style="text-align:left;font-size:14px;line-height:1.55;color:#334155">
                    <p style="margin:0 0 12px">${escapeHtml(data.message || 'Your borrow request is recorded.')}</p>
                    ${slip ? `<p style="margin:0 0 10px"><strong style="color:#0f172a">Slip</strong><br><span style="font-size:18px;font-weight:700;color:#0025cc;letter-spacing:0.02em">${escapeHtml(slip)}</span></p>` : ''}
                    <p style="margin:0 0 6px"><strong style="color:#0f172a">Borrow from</strong><br>${escapeHtml(formatWhen(data.borrow_from))}</p>
                    <p style="margin:0 0 12px"><strong style="color:#0f172a">Return by</strong><br>${escapeHtml(formatWhen(data.expected_return))}</p>
                    <p style="margin:0;padding:10px 12px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;color:#475569">
                        <strong style="color:#0f172a">Next step:</strong> ${escapeHtml(nextStep)}
                    </p>
                </div>
            `;

            if (window.Swal) {
                await Swal.fire({
                    icon: 'success',
                    title: data.status === 'Pending' ? 'Request submitted' : 'Borrow recorded',
                    html,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#0025cc',
                    width: 440,
                });
            } else {
                alert((data.message || 'Request submitted.') + (slip ? (' Slip ' + slip + '.') : '') + ' ' + nextStep);
            }
        } catch (err) {
            showText(formError, err.message || 'Could not submit the borrow request.', true);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalLabel;
        }
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            // Intentionally no backdrop close (shared terminal safety).
        }
    });
})();
</script>
