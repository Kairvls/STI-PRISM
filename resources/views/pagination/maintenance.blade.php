@if ($paginator->hasPages())
    @php
        $lastPage = $paginator->lastPage();
        $currentPage = $paginator->currentPage();
        $visible = min(5, $lastPage);
    @endphp

    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="mt-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-700">
                {!! __('Showing') !!}
                <span class="font-medium">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="font-medium">{{ $paginator->lastItem() }}</span>
                {!! __('of') !!}
                <span class="font-medium">{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </p>

            <div
                class="page-carousel inline-flex items-center overflow-hidden rounded-lg bg-slate-800 text-white shadow-sm"
                data-page-carousel
                data-current="{{ $currentPage }}"
                data-total="{{ $lastPage }}"
                data-visible="{{ $visible }}"
            >
                <button
                    type="button"
                    class="page-carousel-prev flex h-10 w-10 shrink-0 items-center justify-center text-white transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-30"
                    aria-label="Previous page numbers"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div class="page-carousel-viewport overflow-hidden" style="width: {{ $visible * 2.5 }}rem">
                    <div class="page-carousel-track flex transition-transform duration-300 ease-out">
                        @for ($page = 1; $page <= $lastPage; $page++)
                            @if ($page == $currentPage)
                                <span
                                    aria-current="page"
                                    data-page="{{ $page }}"
                                    class="flex h-10 w-10 shrink-0 items-center justify-center bg-blue-500/40 text-sm font-medium text-white"
                                >{{ $page }}</span>
                            @else
                                <a
                                    href="{{ $paginator->url($page) }}"
                                    data-page="{{ $page }}"
                                    class="flex h-10 w-10 shrink-0 items-center justify-center text-sm font-medium text-white/90 transition hover:bg-white/10"
                                >{{ $page }}</a>
                            @endif
                        @endfor
                    </div>
                </div>

                <button
                    type="button"
                    class="page-carousel-next flex h-10 w-10 shrink-0 items-center justify-center text-white transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-30"
                    aria-label="Next page numbers"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>
@endif

@once
    <script>
        (function () {
            const ITEM_WIDTH = 40;

            function bindPageCarousels() {
                document.querySelectorAll('[data-page-carousel]').forEach((root) => {
                    if (root.dataset.bound === '1') {
                        return;
                    }
                    root.dataset.bound = '1';

                    const track = root.querySelector('.page-carousel-track');
                    const prev = root.querySelector('.page-carousel-prev');
                    const next = root.querySelector('.page-carousel-next');
                    const total = Number(root.dataset.total || 0);
                    const visible = Number(root.dataset.visible || 5);
                    const current = Number(root.dataset.current || 1);
                    const maxIndex = Math.max(0, total - visible);

                    let index = Math.min(
                        maxIndex,
                        Math.max(0, current - Math.ceil(visible / 2))
                    );

                    function render() {
                        track.style.transform = 'translateX(' + (-index * ITEM_WIDTH) + 'px)';
                        prev.disabled = index <= 0;
                        next.disabled = index >= maxIndex;
                    }

                    prev.addEventListener('click', function () {
                        index = Math.max(0, index - 1);
                        render();
                    });

                    next.addEventListener('click', function () {
                        index = Math.min(maxIndex, index + 1);
                        render();
                    });

                    render();
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bindPageCarousels);
            } else {
                bindPageCarousels();
            }
        })();
    </script>
@endonce
