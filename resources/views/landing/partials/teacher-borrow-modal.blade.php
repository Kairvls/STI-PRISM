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
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Equipment borrowing</p>
                <h2 id="teacherBorrowTitle" class="mt-0.5 text-lg font-semibold tracking-tight text-slate-950 sm:text-xl">
                    Request equipment
                </h2>
                <p id="teacherBorrowIdentity" class="mt-1 truncate text-sm text-slate-600">
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
                {{-- Items to borrow --}}
                <div class="space-y-3 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            Items to borrow <span class="text-red-500" aria-hidden="true">*</span>
                        </p>
                        <p id="teacherBorrowCartBadge"
                           aria-live="polite"
                           class="rounded-lg bg-white px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200/80">
                            No items selected
                        </p>
                    </div>

                    <div class="relative">
                        <label for="teacherBorrowSearch" class="mb-1 block text-sm font-medium text-slate-700">Add equipment</label>
                        <p id="teacherBorrowSearchHint" class="mb-2 text-xs text-slate-500">All available equipment (in storage and deployed) is shown. Select an item to add it to your request.</p>
                        <div class="relative">
                            <i data-lucide="search" aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="text"
                                id="teacherBorrowSearch"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-expanded="false"
                                aria-controls="teacherBorrowSearchList"
                                aria-describedby="teacherBorrowSearchHint"
                                placeholder="Search by equipment name or room"
                                autocomplete="off"
                                class="h-11 w-full rounded-xl border-0 bg-white pl-10 pr-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/90 placeholder:text-slate-400 transition focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                            >
                        </div>
                        <div id="teacherBorrowSearchResults"
                             class="absolute left-0 right-0 top-[calc(100%+0.35rem)] z-40 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                            <div id="teacherBorrowSearchList"
                                 role="listbox"
                                 aria-label="Available equipment"
                                 class="max-h-64 overflow-y-auto py-1"></div>
                        </div>
                        <p id="teacherBorrowLiveStatus" class="sr-only" aria-live="polite" aria-atomic="true"></p>
                        <p id="teacherBorrowPickerError" role="alert" class="mt-2 hidden text-xs font-medium text-rose-600"></p>
                    </div>

                    <div id="teacherBorrowCart" class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                        <div id="teacherBorrowCartEmpty" class="px-4 py-7 text-center">
                            <p class="text-sm font-medium text-slate-700">No items selected yet</p>
                            <p class="mt-1 text-xs text-slate-500">Search above and click an item to add it to your request.</p>
                        </div>
                        <div id="teacherBorrowCartLines" class="hidden divide-y divide-slate-100"></div>
                    </div>
                    <p id="teacherBorrowCartError" role="alert" class="hidden text-xs font-medium text-rose-600"></p>
                </div>


                {{-- Schedule --}}
                @php
                    $officeTimeSlots = [];
                    for ($h = 9; $h <= 17; $h++) {
                        foreach ([0, 30] as $m) {
                            if ($h === 17 && $m > 0) {
                                continue;
                            }
                            $val = sprintf('%02d:%02d', $h, $m);
                            $label = \Carbon\Carbon::createFromTime($h, $m)->format('g:i A');
                            $officeTimeSlots[$val] = $label;
                        }
                    }
                @endphp
                <div class="space-y-3 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Schedule</p>
                        <p class="mt-1 text-sm text-slate-600">Equipment claiming and returns are available from <span class="font-semibold text-slate-800">9:00 AM to 5:00 PM</span>.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="teacherBorrowDate" class="mb-1.5 block text-sm font-medium text-slate-700">
                                Claim / borrow time <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <div class="grid grid-cols-[minmax(0,1fr)_7.75rem] gap-2">
                                <input
                                    id="teacherBorrowDate"
                                    type="date"
                                    required
                                    aria-label="Claim date"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200/90 transition focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                                >
                                <select
                                    id="teacherBorrowTime"
                                    required
                                    aria-label="Claim time (9:00 AM to 5:00 PM)"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-2.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/90 transition focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                                >
                                    @foreach ($officeTimeSlots as $slotVal => $slotLabel)
                                        <option value="{{ $slotVal }}">{{ $slotLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">When you will claim the borrowed items (9:00 AM – 5:00 PM).</p>
                        </div>
                        <div>
                            <label for="teacherBorrowReturn" class="mb-1.5 block text-sm font-medium text-slate-700">
                                Expected return by <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <div class="grid grid-cols-[minmax(0,1fr)_7.75rem] gap-2">
                                <input
                                    id="teacherBorrowReturn"
                                    type="date"
                                    required
                                    aria-label="Expected return date"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200/90 transition focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                                >
                                <select
                                    id="teacherBorrowReturnTime"
                                    required
                                    aria-label="Expected return time (9:00 AM to 5:00 PM)"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-2.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/90 transition focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                                >
                                    @foreach ($officeTimeSlots as $slotVal => $slotLabel)
                                        <option value="{{ $slotVal }}">{{ $slotLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">When items will be returned (9:00 AM – 5:00 PM).</p>
                        </div>
                    </div>
                </div>

                {{-- Location --}}
                <div class="space-y-3 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <div>
                        <label for="teacherBorrowLocation" class="mb-1.5 block text-sm font-medium text-slate-700">
                            Location <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input
                            id="teacherBorrowLocation"
                            type="text"
                            required
                            maxlength="150"
                            placeholder="Where the equipment will be used"
                            class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/90 placeholder:text-slate-400 transition focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                        >
                    </div>
                </div>

                {{-- Notes --}}
                <div class="space-y-3 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Notes</p>
                        <p class="mt-1 text-sm text-slate-600">Why the equipment is needed.</p>
                    </div>
                    <div>
                        <label for="teacherBorrowPurpose" class="mb-1.5 block text-sm font-medium text-slate-700">
                            Purpose <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <textarea
                            id="teacherBorrowPurpose"
                            rows="2"
                            required
                            maxlength="2000"
                            placeholder="Describe why the equipment is being borrowed"
                            class="w-full resize-none rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none ring-1 ring-slate-200/90 placeholder:text-slate-400 transition focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                        ></textarea>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 flex-col gap-2 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p id="teacherBorrowFormError" role="alert" class="hidden text-xs font-medium text-rose-600 sm:max-w-sm"></p>
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
    const resultsWrap = document.getElementById('teacherBorrowSearchResults');
    const resultsList = document.getElementById('teacherBorrowSearchList');
    const liveStatus = document.getElementById('teacherBorrowLiveStatus');
    const pickerError = document.getElementById('teacherBorrowPickerError');
    const cartBadge = document.getElementById('teacherBorrowCartBadge');
    const cartEmpty = document.getElementById('teacherBorrowCartEmpty');
    const cartLines = document.getElementById('teacherBorrowCartLines');
    const cartError = document.getElementById('teacherBorrowCartError');
    const formError = document.getElementById('teacherBorrowFormError');
    const submitBtn = document.getElementById('teacherBorrowSubmitBtn');
    const dateInput = document.getElementById('teacherBorrowDate');
    const timeInput = document.getElementById('teacherBorrowTime');
    const returnInput = document.getElementById('teacherBorrowReturn');
    const returnTimeInput = document.getElementById('teacherBorrowReturnTime');
    const purposeInput = document.getElementById('teacherBorrowPurpose');
    const locationInput = document.getElementById('teacherBorrowLocation');
    const cautionWrap = document.getElementById('teacherBorrowCautionWrap');
    const cautionBtn = document.getElementById('teacherBorrowCautionBtn');
    const cautionPanel = document.getElementById('teacherBorrowCautionPanel');

    let catalog = [];
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

    function announce(message) {
        if (!liveStatus) return;
        liveStatus.textContent = '';
        requestAnimationFrame(() => {
            liveStatus.textContent = message || '';
        });
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
        bits.push(item.available + ' available');
        const pending = Number(item.pending || 0);
        if (pending > 0) {
            bits.push(pending + ' pending');
        }
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

    function openResults() {
        resultsWrap.classList.remove('hidden');
        searchInput.setAttribute('aria-expanded', 'true');
        renderResults();
    }

    function closeResults() {
        resultsWrap.classList.add('hidden');
        searchInput.setAttribute('aria-expanded', 'false');
        searchInput.removeAttribute('aria-activedescendant');
    }

    function updateCartBadge() {
        const totalQty = cart.reduce((sum, line) => sum + (Number(line.quantity) || 0), 0);
        if (!cart.length) {
            cartBadge.textContent = 'No items selected';
            return;
        }
        const itemLabel = cart.length + ' item' + (cart.length === 1 ? '' : 's');
        cartBadge.textContent = totalQty > cart.length
            ? (itemLabel + ' · ' + totalQty + ' total')
            : itemLabel;
    }

    function renderResults() {
        const rows = filtered();
        if (!rows.length) {
            highlight = 0;
            searchInput.removeAttribute('aria-activedescendant');
            resultsList.innerHTML = '<p class="px-3.5 py-4 text-sm text-slate-500">No matching equipment available.</p>';
            return;
        }
        if (highlight >= rows.length) highlight = 0;

        resultsList.innerHTML = rows.map((item, index) => {
            const inCart = cart.find((line) => line.id === item.id);
            const atMax = !!(inCart && inCart.quantity >= item.available);
            const active = index === highlight;
            const optId = 'teacherBorrowOpt-' + index;
            const pill = atMax
                ? '<span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Max added</span>'
                : inCart
                    ? `<span class="shrink-0 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">In list (${inCart.quantity}/${item.available}) · +1</span>`
                    : '<span class="shrink-0 rounded-md bg-[#0025cc]/10 px-2 py-0.5 text-[11px] font-semibold text-[#0025cc]">+ Add</span>';

            return `
                <button type="button"
                    id="${optId}"
                    role="option"
                    aria-selected="${active ? 'true' : 'false'}"
                    aria-disabled="${atMax ? 'true' : 'false'}"
                    data-equip-id="${item.id}"
                    class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition ${atMax ? 'opacity-60' : ''} ${active ? 'bg-[#0025cc]/5' : 'hover:bg-slate-50'}">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <i data-lucide="package" aria-hidden="true" class="h-4 w-4"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-slate-900">${escapeHtml(item.name)}</span>
                        <span class="mt-0.5 block truncate text-xs text-slate-500">${escapeHtml(meta(item))}</span>
                    </span>
                    ${pill}
                </button>
            `;
        }).join('');

        const activeOptId = 'teacherBorrowOpt-' + highlight;
        searchInput.setAttribute('aria-activedescendant', activeOptId);
        const activeEl = document.getElementById(activeOptId);
        if (activeEl) {
            activeEl.scrollIntoView({ block: 'nearest' });
        }
        refreshIcons();
    }

    function addItem(item) {
        showText(pickerError, '', false);
        if (!item || item.available < 1) {
            showText(pickerError, 'This item is not available right now.', true);
            return;
        }
        const existing = cart.find((line) => line.id === item.id);
        if (existing) {
            if (existing.quantity >= existing.available) {
                showText(pickerError, 'All available units of ' + existing.name + ' (' + existing.available + ') are already added.', true);
                closeResults();
                return;
            }
            existing.quantity += 1;
            announce('Increased ' + existing.name + ' to ' + existing.quantity + ' of ' + existing.available + '.');
        } else {
            cart.push({
                id: item.id,
                name: item.name,
                room: item.room,
                assetTag: item.assetTag,
                tracking: item.tracking,
                available: item.available,
                pending: Number(item.pending || 0),
                quantity: 1,
            });
            announce('Added ' + item.name + ' to your request.');
        }
        searchInput.value = '';
        highlight = 0;
        closeResults();
        showText(cartError, '', false);
        renderCart();
    }

    function renderCart() {
        updateCartBadge();

        if (!cart.length) {
            cartEmpty.classList.remove('hidden');
            cartLines.classList.add('hidden');
            cartLines.innerHTML = '';
            return;
        }

        cartEmpty.classList.add('hidden');
        cartLines.classList.remove('hidden');
        cartLines.innerHTML = cart.map((line, index) => {
            const safeName = escapeHtml(line.name);
            const qtyControl = line.available <= 1
                ? '<span class="inline-flex h-9 items-center rounded-lg bg-slate-100 px-3 text-xs font-medium text-slate-600">Qty 1</span>'
                : `
                    <div class="inline-flex items-center rounded-lg border border-slate-200 bg-white" role="group" aria-label="Quantity for ${safeName}">
                        <button type="button"
                            data-cart-dec="${index}"
                            ${line.quantity <= 1 ? 'disabled' : ''}
                            aria-label="Decrease quantity for ${safeName}"
                            class="flex h-9 w-8 items-center justify-center rounded-l-lg text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus-visible:ring-2 focus-visible:ring-[#0025cc]/40 disabled:cursor-not-allowed disabled:opacity-40">
                            <i data-lucide="minus" aria-hidden="true" class="h-3.5 w-3.5"></i>
                        </button>
                        <input type="text"
                            inputmode="numeric"
                            autocomplete="off"
                            value="${line.quantity}"
                            data-cart-qty="${index}"
                            aria-label="Quantity for ${safeName} (max ${line.available})"
                            class="h-9 w-11 border-x border-slate-200 bg-white text-center text-sm font-medium text-slate-900 outline-none focus-visible:ring-2 focus-visible:ring-[#0025cc]/40">
                        <button type="button"
                            data-cart-inc="${index}"
                            ${line.quantity >= line.available ? 'disabled' : ''}
                            aria-label="Increase quantity for ${safeName}"
                            class="flex h-9 w-8 items-center justify-center rounded-r-lg text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus-visible:ring-2 focus-visible:ring-[#0025cc]/40 disabled:cursor-not-allowed disabled:opacity-40">
                            <i data-lucide="plus" aria-hidden="true" class="h-3.5 w-3.5"></i>
                        </button>
                    </div>
                    <span class="text-xs font-medium text-slate-500">of ${line.available}</span>
                `;

            return `
                <div class="flex flex-wrap items-center gap-3 px-4 py-3" data-cart-index="${index}">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900">${safeName}</p>
                        <p class="mt-0.5 truncate text-xs text-slate-500">${escapeHtml(meta(line))}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        ${qtyControl}
                        <button type="button"
                            data-cart-remove="${index}"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-rose-700 transition hover:border-rose-200 hover:bg-rose-50 focus-visible:ring-2 focus-visible:ring-[#0025cc]/40"
                            aria-label="Remove ${safeName}">
                            <i data-lucide="trash-2" aria-hidden="true" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
        refreshIcons();
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
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Reference #</p>
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
            titleEl.textContent = showingStatus ? 'Check borrow status' : 'Request equipment';
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

    function toLocalTimeValue(date) {
        const pad = (n) => String(n).padStart(2, '0');
        return pad(date.getHours()) + ':' + pad(date.getMinutes());
    }

    function clampOfficeTime(hm) {
        const val = String(hm || '').trim();
        if (!/^\d{2}:\d{2}$/.test(val)) return '09:00';
        if (val <= '09:00') return '09:00';
        if (val >= '17:00') return '17:00';
        const [hStr, mStr] = val.split(':');
        let h = Number(hStr);
        let m = Number(mStr);
        if (m === 0) return String(h).padStart(2, '0') + ':00';
        if (m <= 30) return String(h).padStart(2, '0') + ':30';
        h += 1;
        if (h >= 17) return '17:00';
        return String(h).padStart(2, '0') + ':00';
    }

    function isOfficeTime(hm) {
        const val = String(hm || '').trim();
        return /^\d{2}:\d{2}$/.test(val) && val >= '09:00' && val <= '17:00';
    }

    function combinedBorrowFrom() {
        if (!dateInput.value) return '';
        return dateInput.value + 'T' + (timeInput?.value || '09:00');
    }

    function combinedReturnBy() {
        if (!returnInput.value) return '';
        return returnInput.value + 'T' + (returnTimeInput?.value || '17:00');
    }

    function resetFormFields() {
        catalog = [];
        cart = [];
        highlight = 0;
        searchInput.value = '';
        purposeInput.value = '';
        if (locationInput) locationInput.value = '';
        showText(pickerError, '', false);
        showText(cartError, '', false);
        showText(formError, '', false);
        showText(statusError, '', false);
        if (liveStatus) liveStatus.textContent = '';
        statusEmpty.classList.add('hidden');
        statusList.innerHTML = '';
        setCaution(null);
        closeResults();
        renderCart();
        showBorrowView('form');

        const now = new Date();
        now.setSeconds(0, 0);
        const later = new Date(now.getTime() + (1 * 24 * 60 * 60 * 1000));
        dateInput.value = toLocalDateValue(now);
        if (timeInput) timeInput.value = clampOfficeTime(toLocalTimeValue(now));
        returnInput.value = toLocalDateValue(later);
        if (returnTimeInput) returnTimeInput.value = '17:00';
        dateInput.min = toLocalDateValue(now);
        syncReturnMin();
        defaultBorrowDate = combinedBorrowFrom();
        defaultReturnDate = combinedReturnBy();
    }

    function formHasInput() {
        if (cart.length > 0) return true;
        if (String(searchInput.value || '').trim() !== '') return true;
        if (String(purposeInput.value || '').trim() !== '') return true;
        if (locationInput && String(locationInput.value || '').trim() !== '') return true;
        if (combinedBorrowFrom() !== defaultBorrowDate) return true;
        if (combinedReturnBy() !== defaultReturnDate) return true;
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
        if (returnInput.value === borrowDate && timeInput?.value && returnTimeInput?.value && returnTimeInput.value < timeInput.value) {
            returnTimeInput.value = timeInput.value;
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
        openResults();
    });
    searchInput.addEventListener('input', () => {
        showText(pickerError, '', false);
        highlight = 0;
        openResults();
    });
    searchInput.addEventListener('keydown', (e) => {
        const isOpen = !resultsWrap.classList.contains('hidden');
        const rows = filtered();
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!isOpen) {
                openResults();
                return;
            }
            if (!rows.length) return;
            highlight = (highlight + 1) % rows.length;
            renderResults();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (!isOpen) {
                openResults();
                return;
            }
            if (!rows.length) return;
            highlight = (highlight - 1 + rows.length) % rows.length;
            renderResults();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (isOpen && rows[highlight]) {
                addItem(rows[highlight]);
            }
        } else if (e.key === 'Escape' && isOpen) {
            e.preventDefault();
            e.stopPropagation();
            closeResults();
        }
    });

    resultsList.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-equip-id]');
        if (!btn) return;
        const id = Number(btn.getAttribute('data-equip-id'));
        const item = catalog.find((row) => row.id === id);
        if (item) addItem(item);
    });

    document.addEventListener('click', (e) => {
        if (!resultsWrap.contains(e.target) && e.target !== searchInput) {
            closeResults();
        }
    });

    function syncLineButtons(rowEl, line) {
        if (!rowEl || !line) return;
        const decBtn = rowEl.querySelector('[data-cart-dec]');
        const incBtn = rowEl.querySelector('[data-cart-inc]');
        if (decBtn) decBtn.disabled = Number(line.quantity) <= 1;
        if (incBtn) incBtn.disabled = Number(line.quantity) >= Number(line.available);
    }

    function readCartQty(input, line, finalize) {
        const max = Math.max(1, Number(line.available) || 1);
        const digits = String(input.value || '').replace(/\D/g, '');
        if (digits === '') {
            if (finalize) {
                line.quantity = 1;
                input.value = '1';
                updateCartBadge();
                syncLineButtons(input.closest('[data-cart-index]'), line);
            }
            return;
        }
        let qty = parseInt(digits, 10);
        if (qty > max) qty = max;
        if (finalize && qty < 1) qty = 1;
        line.quantity = qty < 1 ? 1 : qty;
        input.value = String(line.quantity);
        updateCartBadge();
        syncLineButtons(input.closest('[data-cart-index]'), line);
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
        const decBtn = e.target.closest('[data-cart-dec]');
        if (decBtn) {
            const index = Number(decBtn.getAttribute('data-cart-dec'));
            const line = cart[index];
            if (line && line.quantity > 1) {
                line.quantity -= 1;
                renderCart();
                announce(line.name + ' quantity set to ' + line.quantity + '.');
            }
            return;
        }
        const incBtn = e.target.closest('[data-cart-inc]');
        if (incBtn) {
            const index = Number(incBtn.getAttribute('data-cart-inc'));
            const line = cart[index];
            if (line && line.quantity < line.available) {
                line.quantity += 1;
                renderCart();
                announce(line.name + ' quantity set to ' + line.quantity + '.');
            }
            return;
        }
        const removeBtn = e.target.closest('[data-cart-remove]');
        if (!removeBtn) return;
        const index = Number(removeBtn.getAttribute('data-cart-remove'));
        const removed = cart[index];
        cart.splice(index, 1);
        renderCart();
        if (removed) {
            announce('Removed ' + removed.name + '.');
        }
    });

    dateInput.addEventListener('change', syncReturnMin);
    dateInput.addEventListener('input', syncReturnMin);
    if (timeInput) {
        timeInput.addEventListener('change', syncReturnMin);
        timeInput.addEventListener('input', syncReturnMin);
    }
    returnInput.addEventListener('change', syncReturnMin);

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        showText(formError, '', false);
        showText(cartError, '', false);

        if (!cart.length) {
            showText(cartError, 'Add at least one equipment item.', true);
            showText(formError, 'Add at least one equipment item.', true);
            searchInput.focus();
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
        if (!dateInput.value || (timeInput && !timeInput.value)) {
            showText(formError, 'Enter the claim / borrow date and time.', true);
            (!dateInput.value ? dateInput : timeInput).focus();
            return;
        }
        if (timeInput && !isOfficeTime(timeInput.value)) {
            showText(formError, 'Claim / borrow time must be between 9:00 AM and 5:00 PM.', true);
            timeInput.focus();
            return;
        }
        if (!returnInput.value || (returnTimeInput && !returnTimeInput.value)) {
            showText(formError, 'Enter the expected return date and time.', true);
            (!returnInput.value ? returnInput : returnTimeInput).focus();
            return;
        }
        if (returnTimeInput && !isOfficeTime(returnTimeInput.value)) {
            showText(formError, 'Expected return time must be between 9:00 AM and 5:00 PM.', true);
            returnTimeInput.focus();
            return;
        }
        const borrowIso = combinedBorrowFrom();
        const returnIso = combinedReturnBy();
        const borrowAt = new Date(borrowIso);
        const returnAt = new Date(returnIso);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (Number.isNaN(borrowAt.getTime()) || Number.isNaN(returnAt.getTime())) {
            showText(formError, 'Enter a valid borrow / return date and time.', true);
            return;
        }
        if (borrowAt < today) {
            showText(formError, 'Borrow date cannot be in the past.', true);
            dateInput.focus();
            return;
        }
        if (returnAt < borrowAt) {
            showText(formError, 'Expected return must be on or after the borrow date and time.', true);
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
                    borrowing_date: borrowIso,
                    borrowing_expected_return_date: returnIso,
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
                return d.toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                });
            };

            const slip = data.slip_no || '';
            const isPending = data.status === 'Pending';
            const statusPill = isPending
                ? '<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#fffbeb;color:#92400e;border:1px solid #fde68a;font-size:12px;font-weight:600">Pending approval</span>'
                : '<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;font-size:12px;font-weight:600">Recorded</span>';
            const nextStep = data.next_step
                || 'A confirmation was sent to your email. Wait for the property custodian to review your request — you’ll be notified by email once it’s approved for pickup.';
            const html = `
                <div style="text-align:left;font-size:14px;line-height:1.55;color:#334155">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin:0 0 12px">
                        <p style="margin:0;color:#475569">${escapeHtml(data.message || 'Your borrow request was submitted.')}</p>
                        ${statusPill}
                    </div>
                    <div style="margin:0 0 12px;padding:12px 14px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0">
                        ${slip ? `<p style="margin:0 0 10px"><span style="display:block;font-size:11px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#64748b">Reference #</span><span style="font-size:17px;font-weight:700;color:#0025cc;letter-spacing:0.02em">${escapeHtml(slip)}</span></p>` : ''}
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                            <p style="margin:0"><span style="display:block;font-size:11px;font-weight:600;color:#64748b">Borrow from</span><strong style="color:#0f172a;font-weight:600">${escapeHtml(formatWhen(data.borrow_from))}</strong></p>
                            <p style="margin:0"><span style="display:block;font-size:11px;font-weight:600;color:#64748b">Return by</span><strong style="color:#0f172a;font-weight:600">${escapeHtml(formatWhen(data.expected_return))}</strong></p>
                        </div>
                    </div>
                    <p style="margin:0;padding:10px 12px;border-radius:12px;background:#ffffff;border:1px solid #e2e8f0;color:#475569;font-size:13px">
                        <strong style="color:#0f172a">Next step:</strong> ${escapeHtml(nextStep)}
                    </p>
                </div>
            `;

            if (window.Swal) {
                await Swal.fire({
                    icon: 'success',
                    title: isPending ? 'Request submitted' : 'Borrow recorded',
                    html,
                    confirmButtonText: 'Done',
                    confirmButtonColor: '#0025cc',
                    width: 460,
                });
            } else {
                alert((data.message || 'Request submitted.') + (slip ? (' Reference #' + slip + '.') : '') + ' ' + nextStep);
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
