<section class="ad-panel nearest-lifespan-card mt-0 rounded-2xl border border-slate-200 bg-white p-5">
            @php
                $lifespan = $lifespan ?? ($lists['lifespan'] ?? ['total' => 0, 'rows' => [], 'due' => []]);
                $lifespanRows = collect($lifespan['rows'] ?? []);
                $lifespanDue = collect($lifespan['due'] ?? []);
                $lifespanTotal = (int) ($lifespan['total'] ?? 0);
                $lifespanDueCount = $lifespanDue->count();
            @endphp
            <div class="flex min-h-7 flex-wrap items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="text-sm font-semibold leading-none text-slate-950">3 nearest lifespan broadcasts</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($lifespanTotal) }}</span>
                    <button type="button" id="lifespanDueBell" class="relative inline-flex h-7 w-7 items-center justify-center rounded-full text-slate-500 transition hover:bg-rose-50 hover:text-rose-700" title="Equipment that reached its lifespan" aria-label="Equipment that reached its lifespan">
                        <i data-lucide="bell" class="h-4 w-4"></i>
                        @if ($lifespanDueCount > 0)
                            <span class="absolute -right-1 -top-1 inline-flex min-w-[1.05rem] items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold leading-4 text-white">{{ $lifespanDueCount > 99 ? '99+' : $lifespanDueCount }}</span>
                        @endif
                    </button>
                </div>
                <a href="{{ $lifespanIndexUrl ?? route('admin.lifespan-broadcasts.index') }}" class="text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
            </div>
            @if ($lifespanDueCount > 0)
                <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs leading-5 text-rose-800">
                    {{ $lifespanDueCount === 1 ? '1 equipment has reached the end of its useful life.' : number_format($lifespanDueCount).' equipment have reached the end of their useful life.' }} A replacement is suggested. Open the bell to see the date, then check stock before a procurement replacement request.
                </p>
            @endif
            <div class="mt-3 divide-y divide-slate-100">
                @forelse ($lifespanRows as $index => $row)
                    <button type="button" data-lifespan-open="{{ $index }}" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $row['title'] }}</span>
                            @if (!empty($row['meta']))
                                <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $row['meta'] }}</span>
                            @endif
                        </span>
                        @if (!empty($row['hint']))
                            <span @class([
                                'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                'bg-rose-50 text-rose-700' => ($row['tone'] ?? '') === 'alert',
                                'bg-amber-50 text-amber-700' => ($row['tone'] ?? '') !== 'alert',
                            ])>{{ $row['hint'] }}</span>
                        @endif
                    </button>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-slate-400">No equipment nearing the end of its useful life</p>
                @endforelse
            </div>
            <script type="application/json" id="lifespanPreviewData">@json($lifespanRows->values())</script>
            <script type="application/json" id="lifespanDueData">@json($lifespanDue->values())</script>
            @if ($lifespanTotal > $lifespanRows->count())
                <p class="mt-2 text-xs text-slate-500">{{ $lifespanRows->count() }} nearest of {{ number_format($lifespanTotal) }}</p>
            @endif
        </section>

        <div id="lifespanPreview" class="fixed inset-0 z-[12000] hidden items-start justify-center bg-black/50 px-4 pt-16">
            <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.18)] ring-1 ring-slate-200/80">
                <button type="button" data-lifespan-close class="absolute right-3 top-3 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <div class="px-6 pb-2 pr-12 pt-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Lifespan broadcast</p>
                    <h3 id="lifespanPreviewTitle" class="mt-1 text-lg font-semibold text-slate-950"></h3>
                    <p id="lifespanPreviewIdentity" class="mt-2 text-sm text-slate-600"></p>
                    <p id="lifespanPreviewMeta" class="mt-1 text-sm text-slate-500"></p>
                    <span id="lifespanPreviewHint" class="mt-3 inline-flex rounded-full px-2.5 py-0.5 text-sm font-semibold"></span>
                </div>
                <form id="lifespanPreviewForm" method="POST" action="#" class="mt-4 flex items-center gap-2 px-6" data-current-lifespan="">
                    @csrf
                    <label class="sr-only" for="lifespanPreviewYears">Useful lifespan in years</label>
                    <input id="lifespanPreviewYears" type="number" name="equipment_useful_life_years" min="1" max="50" required class="h-10 w-24 rounded-lg border border-slate-200 px-3 text-sm text-slate-800 outline-none focus:border-slate-300">
                    <button type="submit" class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 text-sm font-semibold text-white hover:bg-[#001fad]">
                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                        Edit
                    </button>
                </form>
                <div class="mt-5 flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-3.5">
                    <button type="button" data-lifespan-close class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">Close</button>
                    <a id="lifespanPreviewGo" href="#" class="rounded-lg bg-[#0025cc] px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#001fad]">Go to page</a>
                </div>
            </div>
        </div>
        @if ($includeLifespanConfirm ?? true)
            @include('partials.purchaser-confirm-dialog')
        @endif
        <script>
            (function () {
                var preview = document.getElementById('lifespanPreview');
                var rows = [];
                try {
                    rows = JSON.parse((document.getElementById('lifespanPreviewData') || {}).textContent || '[]');
                } catch (error) {
                    rows = [];
                }
                if (preview && preview.parentElement !== document.body) {
                    document.body.appendChild(preview);
                }
                function closeLifespanPreview() {
                    if (!preview) return;
                    preview.classList.add('hidden');
                    preview.classList.remove('flex');
                }
                function openLifespanPreview(row) {
                    if (!preview || !row) return;
                    document.getElementById('lifespanPreviewTitle').textContent = row.title || 'Equipment';
                    document.getElementById('lifespanPreviewIdentity').textContent = row.identity || '';
                    var meta = ['Room ' + (row.room || '—'), 'Age ' + (row.age || 0) + 'y', 'Lifespan ' + (row.lifespan || 0) + 'y'].join(' · ');
                    document.getElementById('lifespanPreviewMeta').textContent = meta;
                    var hint = document.getElementById('lifespanPreviewHint');
                    hint.textContent = row.hint || '';
                    hint.className = 'mt-3 inline-flex rounded-full px-2.5 py-0.5 text-sm font-semibold ' + (row.tone === 'alert' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700');
                    var form = document.getElementById('lifespanPreviewForm');
                    form.action = row.update || '#';
                    form.setAttribute('data-current-lifespan', String(row.lifespan || ''));
                    document.getElementById('lifespanPreviewYears').value = row.lifespan || '';
                    document.getElementById('lifespanPreviewGo').href = row.href || '#';
                    preview.classList.remove('hidden');
                    preview.classList.add('flex');
                    if (window.lucide) window.lucide.createIcons();
                }
                document.addEventListener('click', function (event) {
                    var opener = event.target.closest('[data-lifespan-open]');
                    if (opener) {
                        openLifespanPreview(rows[Number(opener.getAttribute('data-lifespan-open'))] || null);
                        return;
                    }
                    if (event.target === preview || event.target.closest('[data-lifespan-close]')) {
                        closeLifespanPreview();
                    }
                });
                document.addEventListener('submit', function (event) {
                    var form = event.target;
                    if (!form || form.id !== 'lifespanPreviewForm') return;
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
                document.addEventListener('submit', function (event) {
                    var form = event.target;
                    if (!form || form.id !== 'lifespanPreviewForm') return;
                    if (form.dataset.purConfirmAccepted === '1') return;
                    var input = form.querySelector('[name="equipment_useful_life_years"]');
                    var current = String(form.getAttribute('data-current-lifespan') || '').trim();
                    var next = input ? String(input.value || '').trim() : '';
                    if (next === '' || next === current) return;
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    if (!window.purConfirm) {
                        form.dataset.purConfirmAccepted = '1';
                        form.submit();
                        return;
                    }
                    window.purConfirm({
                        title: 'Edit this lifespan?',
                        text: 'A useful lifespan is rarely changed once it has been set. The 5-year default counts as a lifespan too. Editing it recalculates how many years this equipment has left. Continue only if the recorded lifespan was wrong.',
                        confirmText: 'Save lifespan',
                        danger: true,
                        align: 'top',
                        large: true,
                        close: true
                    }).then(function (ok) {
                        if (!ok) return;
                        form.dataset.purConfirmAccepted = '1';
                        form.submit();
                    });
                }, true);
            })();
        </script>

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
