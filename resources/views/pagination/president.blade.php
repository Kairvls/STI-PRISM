@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $window = 5;
        $half = (int) floor($window / 2);

        $start = max(1, $current - $half);
        $end = min($last, $start + $window - 1);
        $start = max(1, $end - $window + 1);
    @endphp

    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="mt-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-600">
                {!! __('Showing') !!}
                <span class="font-medium text-slate-900">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="font-medium text-slate-900">{{ $paginator->lastItem() }}</span>
                {!! __('of') !!}
                <span class="font-medium text-slate-900">{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </p>

            <ul class="inline-flex items-center gap-1">
                {{-- Previous --}}
                <li>
                    @if ($paginator->onFirstPage())
                        <span
                            aria-disabled="true"
                            class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-300"
                        >&laquo;</span>
                    @else
                        <a
                            href="{{ $paginator->previousPageUrl() }}"
                            rel="prev"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                            aria-label="{{ __('pagination.previous') }}"
                        >&laquo;</a>
                    @endif
                </li>

                {{-- Page numbers (max 5) --}}
                @for ($page = $start; $page <= $end; $page++)
                    <li>
                        @if ($page === $current)
                            <span
                                aria-current="page"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-sm font-semibold text-white"
                            >{{ $page }}</span>
                        @else
                            <a
                                href="{{ $paginator->url($page) }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                            >{{ $page }}</a>
                        @endif
                    </li>
                @endfor

                {{-- Next --}}
                <li>
                    @if ($paginator->hasMorePages())
                        <a
                            href="{{ $paginator->nextPageUrl() }}"
                            rel="next"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                            aria-label="{{ __('pagination.next') }}"
                        >&raquo;</a>
                    @else
                        <span
                            aria-disabled="true"
                            class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-300"
                        >&raquo;</span>
                    @endif
                </li>
            </ul>
        </div>
    </nav>
@endif
