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
        var emptyRow = root.querySelector('.receiving-empty-row');
        var pager = root.querySelector('.receiving-pager');
        var showingEl = root.querySelector('.receiving-showing');
        var prevBtn = root.querySelector('.receiving-page-prev');
        var nextBtn = root.querySelector('.receiving-page-next');
        var pageNum = root.querySelector('.receiving-page-num');
        var pageControls = root.querySelector('.receiving-page-controls');
        var searchTimer = null;
        var pageSize = 10;
        var currentPage = 1;

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
                card.classList.toggle('border-slate-900/20', isActive);
                card.classList.toggle('ring-2', isActive);
                card.classList.toggle('ring-slate-900/10', isActive);
                card.classList.toggle('border-gray-200', !isActive);
            });
        }

        function matchedRows() {
            var needle = search ? search.value.trim().toLowerCase() : '';
            return rows.filter(function (row) {
                var status = row.getAttribute('data-ro-status') || 'all';
                var hay = (row.getAttribute('data-ro-search') || row.textContent || '').toLowerCase();
                var statusOk = currentFilter === 'all' || status === currentFilter;
                var searchOk = !needle || hay.indexOf(needle) !== -1;
                return statusOk && searchOk;
            });
        }

        function apply() {
            var matched = matchedRows();
            var total = matched.length;
            var pageCount = Math.max(1, Math.ceil(total / pageSize));
            if (currentPage > pageCount) currentPage = pageCount;
            var start = (currentPage - 1) * pageSize;
            var end = start + pageSize;
            var pageRows = matched.slice(start, end);

            rows.forEach(function (row) { row.style.display = 'none'; });
            pageRows.forEach(function (row) { row.style.display = ''; });

            if (countEl) countEl.textContent = total + ' total';
            if (emptyRow) emptyRow.style.display = total ? 'none' : '';

            if (pager) {
                pager.style.display = total ? 'flex' : 'none';
                var first = total ? start + 1 : 0;
                var last = total ? Math.min(end, total) : 0;
                if (showingEl) showingEl.innerHTML = 'Showing <span class="font-semibold text-gray-700">' + first + '</span> – <span class="font-semibold text-gray-700">' + last + '</span> of <span class="font-semibold text-gray-700">' + total + '</span>';
                if (pageNum) pageNum.textContent = String(currentPage);
                if (pageControls) pageControls.style.display = total > pageSize ? 'flex' : 'none';
                if (prevBtn) {
                    prevBtn.disabled = currentPage <= 1;
                    prevBtn.classList.toggle('opacity-40', currentPage <= 1);
                    prevBtn.classList.toggle('cursor-not-allowed', currentPage <= 1);
                }
                if (nextBtn) {
                    nextBtn.disabled = currentPage >= pageCount;
                    nextBtn.classList.toggle('opacity-40', currentPage >= pageCount);
                    nextBtn.classList.toggle('cursor-not-allowed', currentPage >= pageCount);
                }
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
        if (prevBtn) prevBtn.addEventListener('click', function () {
            if (currentPage <= 1) return;
            currentPage -= 1;
            apply();
        });
        if (nextBtn) nextBtn.addEventListener('click', function () {
            currentPage += 1;
            apply();
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
