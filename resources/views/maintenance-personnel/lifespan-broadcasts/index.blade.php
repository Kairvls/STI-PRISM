@extends('layouts.maintenance-layout')

@section('title', 'Lifespan broadcasts')

@section('content')
@php
    $filters = [
        'all' => 'All',
        'due' => 'Due or past',
        'year' => 'This year',
        'later' => 'Years left',
    ];
@endphp
<div class="space-y-6">
    @if (session('success'))
        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <p class="rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $errors->first() }}</p>
    @endif
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-semibold text-slate-950">Lifespan broadcasts</h2>
                    @php $reachedCount = count($reachedLife ?? []); @endphp
                    <button type="button" id="lifespanDueBell" class="relative inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-500 transition hover:bg-rose-50 hover:text-rose-700" title="Equipment that reached its lifespan" aria-label="Equipment that reached its lifespan">
                        <i data-lucide="bell" class="h-4 w-4"></i>
                        @if ($reachedCount > 0)
                            <span class="absolute -right-1 -top-1 inline-flex min-w-[1.05rem] items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold leading-4 text-white">{{ $reachedCount > 99 ? '99+' : $reachedCount }}</span>
                        @endif
                    </button>
                </div>
                <p class="mt-0.5 text-sm text-slate-500">{{ number_format($broadcasts->total()) }} {{ \Illuminate\Support\Str::plural('equipment', $broadcasts->total()) }} in this list</p>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search name, tag, or room"
                    oninput="if (this.value === '') { var form = this.form; setTimeout(function () { form.requestSubmit ? form.requestSubmit() : form.submit(); }, 0); }"
                    onsearch="if (this.value === '') { var form = this.form; setTimeout(function () { form.requestSubmit ? form.requestSubmit() : form.submit(); }, 0); }"
                    class="h-10 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-slate-300 sm:w-64"
                >
                <select name="filter" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700" onchange="this.form.submit()" aria-label="Lifespan">
                    @foreach ($filters as $value => $label)
                        <option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @php
                    $roomLabel = 'All rooms';
                    foreach ($rooms as $room) {
                        if ($roomId === $room['id']) {
                            $roomLabel = $room['name'];
                        }
                    }
                    $setLabel = 'All computer sets';
                    if ($setId === 'none') {
                        $setLabel = 'Not in a computer set';
                    } else {
                        foreach ($computerSets as $set) {
                            if ((string) $setId === (string) $set['id']) {
                                $setLabel = $set['label'];
                            }
                        }
                    }
                @endphp
                <div class="relative w-full sm:w-52" data-search-select>
                    <input type="hidden" name="room" value="{{ $roomId }}">
                    <button type="button" data-search-select-button class="flex h-10 w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-left text-sm text-slate-700" aria-label="Room" aria-expanded="false">
                        <span class="truncate">{{ $roomLabel }}</span>
                        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400"></i>
                    </button>
                    <div data-search-select-menu class="absolute left-0 right-0 z-20 mt-1 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-100 p-2">
                            <input type="search" data-search-select-query placeholder="Search rooms" class="h-9 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none placeholder:text-slate-400 focus:border-slate-300">
                        </div>
                        <div class="max-h-56 overflow-y-auto py-1">
                            <button type="button" data-search-select-option data-value="0" class="block w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-slate-50">All rooms</button>
                            @foreach ($rooms as $room)
                                <button type="button" data-search-select-option data-value="{{ $room['id'] }}" class="block w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-slate-50">{{ $room['name'] }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="relative w-full sm:w-60" data-search-select>
                    <input type="hidden" name="set" value="{{ $setId }}">
                    <button type="button" data-search-select-button class="flex h-10 w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-left text-sm text-slate-700" aria-label="Computer set" aria-expanded="false">
                        <span class="truncate">{{ $setLabel }}</span>
                        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400"></i>
                    </button>
                    <div data-search-select-menu class="absolute left-0 right-0 z-20 mt-1 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-100 p-2">
                            <input type="search" data-search-select-query placeholder="Search computer sets" class="h-9 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none placeholder:text-slate-400 focus:border-slate-300">
                        </div>
                        <div class="max-h-56 overflow-y-auto py-1">
                            <button type="button" data-search-select-option data-value="all" class="block w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-slate-50">All computer sets</button>
                            <button type="button" data-search-select-option data-value="none" class="block w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-slate-50">Not in a computer set</button>
                            @foreach ($computerSets as $set)
                                <button type="button" data-search-select-option data-value="{{ $set['id'] }}" class="block w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-slate-50">{{ $set['label'] }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($broadcasts as $row)
                @php
                    $yearsLeft = (int) ($row->years_remaining ?? 0);
                    $suggestion = \App\Support\EquipmentLifecycle::suggestAction($yearsLeft, $row->equipment_inventory_status ?? null);
                    $identity = \App\Support\EquipmentIdentity::label($row);
                    $href = $inAdmin
                        ? \App\Support\EquipmentViewReturn::monitorShowUrl((int) $row->equipment_id)
                        : \App\Support\EquipmentViewReturn::viewUrl((int) $row->equipment_id);
                    $setYears = $row->set_useful_life_years ?? null;
                    $hasStoredLifespan = $setYears !== null && $setYears !== '';
                    $lifespanYears = $hasStoredLifespan
                        ? (int) $setYears
                        : (int) ($row->useful_life_years ?? \App\Support\EquipmentLifecycle::DEFAULT_USEFUL_LIFE_YEARS);
                    $updateUrl = $inAdmin
                        ? route('admin.lifespan-broadcasts.update', $row->equipment_id)
                        : route('maintenance.lifespan-broadcasts.update', $row->equipment_id);
                @endphp
                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ $href }}" class="min-w-0">
                        <span class="block truncate text-base font-semibold text-slate-950 hover:underline">{{ $row->equipment_name ?: 'Equipment' }}</span>
                        <span class="mt-0.5 block truncate text-xs text-slate-500">
                            {{ collect([
                                $identity !== '' ? $identity : 'No asset tag / serial',
                                $row->room_name ?? null,
                                'Age '.(int) ($row->age_years ?? 0).'y',
                                'Lifespan '.$lifespanYears.'y',
                                filled($row->computer_set_tag ?? null) ? $row->computer_set_tag : null,
                            ])->filter()->implode(' · ') }}
                        </span>
                    </a>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <span @class([
                            'rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
                            'bg-rose-50 text-rose-700' => $suggestion['tone'] === 'alert',
                            'bg-amber-50 text-amber-700' => $suggestion['tone'] !== 'alert',
                        ])>{{ $suggestion['hint'] }}</span>
                        <form
                            method="POST"
                            action="{{ $updateUrl }}"
                            class="flex items-center gap-2"
                            data-current-lifespan="{{ $lifespanYears }}"
                            data-pur-confirm="A useful lifespan is rarely changed once it has been set. The 5-year default counts as a lifespan too. Editing it recalculates how many years this equipment has left. Continue only if the recorded lifespan was wrong."
                            data-pur-confirm-title="Edit this lifespan?"
                            data-pur-confirm-ok="Save lifespan"
                            data-pur-confirm-danger="1"
                            data-pur-confirm-align="top"
                            data-pur-confirm-large="1"
                            data-pur-confirm-close="1"
                        >
                            @csrf
                            <label class="sr-only" for="life-{{ $row->equipment_id }}">Useful lifespan in years</label>
                            <input
                                id="life-{{ $row->equipment_id }}"
                                type="number"
                                name="equipment_useful_life_years"
                                min="1"
                                max="50"
                                required
                                value="{{ $lifespanYears }}"
                                class="h-9 w-20 rounded-lg border border-slate-200 px-2 text-sm text-slate-800 outline-none focus:border-slate-300"
                            >
                            <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 text-sm font-semibold text-white hover:bg-[#001fad]">
                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                Edit
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="px-5 py-16 text-center text-sm text-slate-400">No equipment matches this lifespan filter.</p>
            @endforelse
        </div>

        @if ($broadcasts->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">
                {{ $broadcasts->links() }}
            </div>
        @endif
    </section>
</div>
<script>
    document.querySelectorAll('[data-search-select]').forEach(function (select) {
        var button = select.querySelector('[data-search-select-button]');
        var menu = select.querySelector('[data-search-select-menu]');
        var query = select.querySelector('[data-search-select-query]');
        var hidden = select.querySelector('input[type="hidden"]');
        var options = Array.prototype.slice.call(select.querySelectorAll('[data-search-select-option]'));

        function closeMenu() {
            menu.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        }

        button.addEventListener('click', function () {
            var open = menu.classList.contains('hidden');
            document.querySelectorAll('[data-search-select-menu]').forEach(function (other) {
                other.classList.add('hidden');
            });
            if (!open) return;
            menu.classList.remove('hidden');
            button.setAttribute('aria-expanded', 'true');
            query.value = '';
            options.forEach(function (option) { option.classList.remove('hidden'); });
            query.focus();
        });

        query.addEventListener('input', function () {
            var needle = query.value.trim().toLowerCase();
            options.forEach(function (option) {
                option.classList.toggle('hidden', needle !== '' && option.textContent.toLowerCase().indexOf(needle) === -1);
            });
        });
        query.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') event.preventDefault();
        });

        options.forEach(function (option) {
            option.addEventListener('click', function () {
                hidden.value = option.getAttribute('data-value');
                select.closest('form').requestSubmit ? select.closest('form').requestSubmit() : select.closest('form').submit();
            });
        });
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.getAttribute || !form.hasAttribute('data-current-lifespan')) return;
        var input = form.querySelector('[name="equipment_useful_life_years"]');
        var current = String(form.getAttribute('data-current-lifespan') || '').trim();
        var next = input ? String(input.value || '').trim() : '';
        if (next === '' || next !== current) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (window.purConfirm) {
            window.purConfirm({
                title: 'No change',
                text: 'This equipment already has a lifespan of ' + current + ' ' + (current === '1' ? 'year' : 'years') + '. Change the number before saving.',
                confirmText: 'OK',
                cancelText: 'Close',
                kind: 'caution',
                align: 'top',
                large: true,
                close: true
            });
        }
    }, true);

    document.addEventListener('click', function (event) {
        if (event.target.closest('#lifespanDue') || event.target.closest('#lifespanStock') || event.target.closest('#lifespanDueBell')) return;
        document.querySelectorAll('[data-search-select]').forEach(function (select) {
            if (select.contains(event.target)) return;
            select.querySelector('[data-search-select-menu]').classList.add('hidden');
            select.querySelector('[data-search-select-button]').setAttribute('aria-expanded', 'false');
        });
    });
</script>
<script type="application/json" id="lifespanDueData">@json($reachedLife ?? [])</script>
<div id="lifespanDue" class="fixed inset-0 z-[12000] hidden items-start justify-center bg-black/50 px-4 pt-16">
    <div class="flex max-h-[min(34rem,calc(100vh-6rem))] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.18)] ring-1 ring-slate-200/80">
        <div class="flex items-start justify-between gap-3 px-5 pb-3 pt-5">
            <div>
                <h3 class="text-base font-semibold text-slate-950">Reached useful life</h3>
                <p class="mt-1 text-xs text-slate-500">These need a replacement. Open one for the suggestion, then check stock before a procurement request.</p>
            </div>
            <button type="button" data-lifespan-due-close class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div id="lifespanDueList" class="min-h-0 flex-1 overflow-y-auto border-t border-slate-100 px-2 py-2"></div>
    </div>
</div>
<div id="lifespanStock" class="fixed inset-0 z-[12010] hidden items-start justify-center bg-black/50 px-4 pt-16">
    <div class="flex max-h-[min(34rem,calc(100vh-6rem))] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.18)] ring-1 ring-slate-200/80">
        <div class="flex items-start justify-between gap-3 px-5 pb-3 pt-5">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Stock on hand</p>
                <h3 id="lifespanStockTitle" class="mt-1 truncate text-base font-semibold text-slate-950"></h3>
            </div>
            <button type="button" data-lifespan-stock-close class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div id="lifespanStockBody" class="min-h-0 flex-1 overflow-y-auto border-t border-slate-100 px-5 py-4"></div>
        <div class="flex justify-end border-t border-slate-100 bg-slate-50/80 px-5 py-3.5">
            <button type="button" data-lifespan-stock-close class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">Close</button>
        </div>
    </div>
</div>
<script>
    (function () {
        var due = [];
        try {
            due = JSON.parse((document.getElementById('lifespanDueData') || {}).textContent || '[]');
        } catch (error) {
            due = [];
        }
        var panel = document.getElementById('lifespanDue');
        var list = document.getElementById('lifespanDueList');
        var stock = document.getElementById('lifespanStock');
        var openIndex = -1;
        if (panel && panel.parentElement !== document.body) document.body.appendChild(panel);
        if (stock && stock.parentElement !== document.body) document.body.appendChild(stock);

        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
            });
        }
        function closeDue() {
            if (!panel) return;
            panel.classList.add('hidden');
            panel.classList.remove('flex');
            openIndex = -1;
        }
        function closeStock() {
            if (!stock) return;
            stock.classList.add('hidden');
            stock.classList.remove('flex');
        }
        function renderDue() {
            if (!list) return;
            if (!due.length) {
                list.innerHTML = '<p class="px-3 py-8 text-center text-sm text-slate-400">No equipment has reached its useful life.</p>';
                return;
            }
            list.innerHTML = due.map(function (row, index) {
                var when = row.reached_on && row.reached_on !== 'Date not on file'
                    ? 'It reached its useful life on ' + esc(row.reached_on) + '.'
                    : 'It has reached its useful life.';
                var suggestion = openIndex === index
                    ? '<div class="mx-2 mb-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-950">'
                        + '<p class="font-semibold">Replacement suggested</p>'
                        + '<p class="mt-1 leading-5">' + when + ' This equipment needs a replacement. Check stock first so a procurement replacement request is only opened when nothing is on hand.</p>'
                        + '<button type="button" data-lifespan-stock="' + index + '" class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#001fad]">Check stock</button>'
                        + '</div>'
                    : '';
                return '<div class="rounded-xl">'
                    + '<button type="button" data-lifespan-due-item="' + index + '" class="flex w-full items-start justify-between gap-3 rounded-xl px-3 py-2.5 text-left hover:bg-slate-50">'
                    + '<span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900">' + esc(row.title) + '</span>'
                    + '<span class="mt-0.5 block truncate text-xs text-slate-500">' + esc(row.identity) + (row.room ? ' · ' + esc(row.room) : '') + '</span></span>'
                    + '<span class="shrink-0 text-right text-xs font-semibold text-rose-700">Reached ' + esc(row.reached_on) + '</span>'
                    + '</button>' + suggestion + '</div>';
            }).join('');
        }
        function openDue() {
            if (!panel) return;
            openIndex = -1;
            renderDue();
            panel.classList.remove('hidden');
            panel.classList.add('flex');
        }
        function openStock(row) {
            if (!stock || !row) return;
            document.getElementById('lifespanStockTitle').textContent = row.title || 'Equipment';
            var units = Array.isArray(row.stock) ? row.stock : [];
            var body = document.getElementById('lifespanStockBody');
            if (!units.length) {
                body.innerHTML = '<p class="rounded-xl bg-rose-50 px-3 py-3 text-sm leading-5 text-rose-800">No matching stock is in storage for this equipment. A procurement replacement request is the next step.</p>';
            } else {
                body.innerHTML = '<p class="rounded-xl bg-emerald-50 px-3 py-3 text-sm leading-5 text-emerald-800">Stock is available. Use one of these units instead of opening a procurement replacement request.</p>'
                    + '<div class="mt-3 divide-y divide-slate-100">'
                    + units.map(function (unit) {
                        var tag = unit.asset_tag ? 'Tag ' + unit.asset_tag : (unit.serial ? 'Serial ' + unit.serial : 'No asset tag');
                        return '<div class="py-3"><p class="text-sm font-semibold text-slate-900">' + esc(unit.name) + '</p>'
                            + '<p class="mt-0.5 text-xs text-slate-500">' + esc(tag) + ' · ' + esc(unit.storage) + ' · ' + esc(unit.condition) + '</p>'
                            + '<p class="mt-1 text-xs font-semibold text-emerald-700">' + esc(unit.match) + '</p></div>';
                    }).join('')
                    + '</div>';
            }
            stock.classList.remove('hidden');
            stock.classList.add('flex');
        }
        document.getElementById('lifespanDueBell')?.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openDue();
        });
        document.addEventListener('click', function (event) {
            var item = event.target.closest('[data-lifespan-due-item]');
            if (item && list && list.contains(item)) {
                var index = Number(item.getAttribute('data-lifespan-due-item'));
                openIndex = openIndex === index ? -1 : index;
                renderDue();
                return;
            }
            var stockButton = event.target.closest('[data-lifespan-stock]');
            if (stockButton) {
                openStock(due[Number(stockButton.getAttribute('data-lifespan-stock'))] || null);
                return;
            }
            if (event.target === panel || event.target.closest('[data-lifespan-due-close]')) closeDue();
            if (event.target === stock || event.target.closest('[data-lifespan-stock-close]')) closeStock();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            if (stock && !stock.classList.contains('hidden')) {
                closeStock();
                return;
            }
            closeDue();
        });
    })();
</script>
@endsection
