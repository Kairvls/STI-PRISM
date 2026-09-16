<script>
window.initReceivingTableFilters = function () {
    document.querySelectorAll('[data-ro-table]').forEach(function (root) {
        if (root.dataset.roBound === '1') return;
        root.dataset.roBound = '1';

        var currentFilter = root.getAttribute('data-ro-default-filter') || 'all';
        var thumbTrack = root.querySelector('[role="tablist"]');
        var thumb = root.querySelector('.receiving-filter-thumb');
        var buttons = root.querySelectorAll('.receiving-filter-btn');
        var cards = root.querySelectorAll('.receiving-filter-card');
        var search = root.querySelector('.receiving-live-search');
        var countEl = root.querySelector('.receiving-total-count');
        var rows = Array.prototype.slice.call(root.querySelectorAll('tbody tr[data-ro-status]'));
        var cardItems = Array.prototype.slice.call(root.querySelectorAll('[data-ro-card-item]'));
        var emptyRow = root.querySelector('.receiving-empty-row');
        var emptyCards = root.querySelector('.receiving-empty-cards');
        var pager = root.querySelector('.receiving-pager');
        var showingEl = root.querySelector('.receiving-showing');
        var pageControls = root.querySelector('.receiving-page-controls');
        var track = root.querySelector('.receiving-carousel-track');
        var viewport = root.querySelector('.receiving-carousel-viewport');
        var carouselPrev = root.querySelector('.receiving-carousel-prev');
        var carouselNext = root.querySelector('.receiving-carousel-next');
        var searchTimer = null;
        var pageSize = 10;
        var currentPage = 1;
        var carouselIndex = 0;
        var lastPageCount = 1;
        var ITEM_WIDTH = 40;
        var VISIBLE = 5;

        function updateSlider(animate) {
            if (!thumbTrack || !thumb || !buttons.length) return;
            var activeBtn = null;
            buttons.forEach(function (btn) {
                var isActive = btn.getAttribute('data-filter') === currentFilter;
                btn.style.color = isActive ? '#020617' : '#64748b';
                btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                btn.setAttribute('tabindex', isActive ? '0' : '-1');
                if (isActive) activeBtn = btn;
            });
            if (!activeBtn) activeBtn = buttons[0];
            var x = activeBtn.offsetLeft;
            var w = activeBtn.offsetWidth;
            if (!animate) {
                var previous = thumb.style.transition;
                thumb.style.transition = 'none';
                thumb.style.width = w + 'px';
                thumb.style.transform = 'translate3d(' + x + 'px, 0, 0)';
                void thumb.offsetWidth;
                thumb.style.transition = previous || 'transform 220ms cubic-bezier(0.22, 1, 0.36, 1), width 220ms cubic-bezier(0.22, 1, 0.36, 1)';
            } else {
                thumb.style.width = w + 'px';
                thumb.style.transform = 'translate3d(' + x + 'px, 0, 0)';
            }
        }

        function updateCards() {
            cards.forEach(function (card) {
                var isActive = card.getAttribute('data-filter') === currentFilter;
                card.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                card.classList.toggle('border-[#0025cc]/60', isActive);
                card.classList.toggle('ring-2', isActive);
                card.classList.toggle('ring-[#0025cc]/15', isActive);
                card.classList.toggle('border-slate-200', !isActive);

                var indicator = card.querySelector('[data-ro-active-dot]');
                if (isActive && !indicator) {
                    indicator = document.createElement('span');
                    indicator.setAttribute('data-ro-active-dot', '1');
                    indicator.className = 'mt-0.5 h-2 w-2 shrink-0 rounded-full bg-[#0025cc]';
                    var labelRow = card.querySelector('.flex.items-start');
                    if (labelRow) labelRow.appendChild(indicator);
                } else if (!isActive && indicator) {
                    indicator.remove();
                }
            });
        }

        function renderCarouselWindow(pageCount) {
            if (!track || !viewport || !carouselPrev || !carouselNext) return;
            var visible = Math.min(VISIBLE, Math.max(1, pageCount));
            var maxIndex = Math.max(0, pageCount - visible);
            carouselIndex = Math.min(maxIndex, Math.max(0, currentPage - Math.ceil(visible / 2)));
            viewport.style.width = (visible * 2.5) + 'rem';
            track.style.transform = 'translateX(' + (-carouselIndex * ITEM_WIDTH) + 'px)';
            carouselPrev.disabled = carouselIndex <= 0;
            carouselNext.disabled = carouselIndex >= maxIndex;
        }

        function rebuildPageButtons(pageCount) {
            if (!track) return;
            track.innerHTML = '';
            for (var page = 1; page <= pageCount; page++) {
                var isCurrent = page === currentPage;
                var el = document.createElement(isCurrent ? 'span' : 'button');
                el.textContent = String(page);
                el.setAttribute('data-page', String(page));
                el.className = isCurrent
                    ? 'flex h-10 w-10 shrink-0 items-center justify-center bg-blue-500/40 text-sm font-medium text-white'
                    : 'flex h-10 w-10 shrink-0 items-center justify-center text-sm font-medium text-white/90 transition hover:bg-white/10';
                if (isCurrent) {
                    el.setAttribute('aria-current', 'page');
                } else {
                    el.type = 'button';
                    el.addEventListener('click', function () {
                        currentPage = Number(this.getAttribute('data-page')) || 1;
                        apply();
                    });
                }
                track.appendChild(el);
            }
            renderCarouselWindow(pageCount);
        }

        function itemMatches(el, needle) {
            var status = el.getAttribute('data-ro-status') || 'all';
            var hay = (el.getAttribute('data-ro-search') || el.textContent || '').toLowerCase();
            var statusOk = currentFilter === 'all' || status === currentFilter;
            var searchOk = !needle || hay.indexOf(needle) !== -1;
            return statusOk && searchOk;
        }

        function matched(list) {
            var needle = search ? search.value.trim().toLowerCase() : '';
            return list.filter(function (el) { return itemMatches(el, needle); });
        }

        function apply() {
            var matchedRows = matched(rows);
            var matchedCards = matched(cardItems);
            var total = Math.max(matchedRows.length, matchedCards.length);
            if (!cardItems.length) total = matchedRows.length;
            if (!rows.length && cardItems.length) total = matchedCards.length;

            var pageCount = Math.max(1, Math.ceil(total / pageSize));
            lastPageCount = pageCount;
            if (currentPage > pageCount) currentPage = pageCount;
            var start = (currentPage - 1) * pageSize;
            var end = start + pageSize;

            rows.forEach(function (row) { row.style.display = 'none'; });
            matchedRows.slice(start, end).forEach(function (row) { row.style.display = ''; });

            cardItems.forEach(function (card) { card.style.display = 'none'; });
            matchedCards.slice(start, end).forEach(function (card) { card.style.display = ''; });

            if (countEl) countEl.textContent = total + ' total';
            if (emptyRow) emptyRow.style.display = total ? 'none' : '';
            if (emptyCards) emptyCards.style.display = total ? 'none' : '';

            if (pager) {
                pager.style.display = total ? 'flex' : 'none';
                var first = total ? start + 1 : 0;
                var last = total ? Math.min(end, total) : 0;
                if (showingEl) {
                    showingEl.innerHTML =
                        'Showing <span class="font-semibold text-slate-700">' + first + '</span> to ' +
                        '<span class="font-semibold text-slate-700">' + last + '</span> of ' +
                        '<span class="font-semibold text-slate-700">' + total + '</span>';
                }
                if (pageControls) pageControls.style.display = total > pageSize ? 'inline-flex' : 'none';
                if (total > pageSize) rebuildPageButtons(pageCount);
            }

            updateSlider(true);
            updateCards();
        }

        function setFilter(filter) {
            if (!filter || filter === currentFilter) return;
            currentFilter = filter;
            currentPage = 1;
            apply();
        }

        root.querySelectorAll('.receiving-filter-btn, .receiving-filter-card').forEach(function (el) {
            el.addEventListener('click', function () {
                setFilter(this.getAttribute('data-filter'));
            });
        });

        buttons.forEach(function (btn, index) {
            btn.addEventListener('keydown', function (event) {
                if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
                event.preventDefault();
                var next = event.key === 'ArrowRight'
                    ? buttons[(index + 1) % buttons.length]
                    : buttons[(index - 1 + buttons.length) % buttons.length];
                next.focus();
                setFilter(next.getAttribute('data-filter'));
            });
        });

        if (search) {
            search.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    currentPage = 1;
                    apply();
                }, 180);
            });
        }

        if (carouselPrev) carouselPrev.addEventListener('click', function () {
            var visible = Math.min(VISIBLE, lastPageCount);
            var maxIndex = Math.max(0, lastPageCount - visible);
            carouselIndex = Math.max(0, carouselIndex - 1);
            if (track) track.style.transform = 'translateX(' + (-carouselIndex * ITEM_WIDTH) + 'px)';
            carouselPrev.disabled = carouselIndex <= 0;
            if (carouselNext) carouselNext.disabled = carouselIndex >= maxIndex;
        });

        if (carouselNext) carouselNext.addEventListener('click', function () {
            var visible = Math.min(VISIBLE, lastPageCount);
            var maxIndex = Math.max(0, lastPageCount - visible);
            carouselIndex = Math.min(maxIndex, carouselIndex + 1);
            if (track) track.style.transform = 'translateX(' + (-carouselIndex * ITEM_WIDTH) + 'px)';
            if (carouselPrev) carouselPrev.disabled = carouselIndex <= 0;
            carouselNext.disabled = carouselIndex >= maxIndex;
        });

        updateSlider(false);
        apply();
        window.addEventListener('resize', function () { updateSlider(false); });
    });
};

document.addEventListener('DOMContentLoaded', function () {
    window.initReceivingTableFilters();
});
</script>
