@php
    $pager = $pager ?? null;
    $linkClass = $linkClass ?? '';
    $noun = $noun ?? 'records';
@endphp

@if($pager && method_exists($pager, 'total') && $pager->total() > 0)
    @php
        $lastPage = method_exists($pager, 'lastPage') ? (int) $pager->lastPage() : 1;
        $currentPage = method_exists($pager, 'currentPage') ? (int) $pager->currentPage() : 1;
        $visible = min(5, max(1, $lastPage));
    @endphp
    <div class="print-hidden flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-500">
            Showing
            <span class="font-semibold text-slate-700">{{ $pager->firstItem() }}</span>
            to
            <span class="font-semibold text-slate-700">{{ $pager->lastItem() }}</span>
            of
            <span class="font-semibold text-slate-700">{{ $pager->total() }}</span>
            {{ $noun }}
        </p>

        @if(method_exists($pager, 'hasPages') && $pager->hasPages())
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
                                    href="{{ $pager->url($page) }}"
                                    data-page="{{ $page }}"
                                    class="{{ $linkClass }} flex h-10 w-10 shrink-0 items-center justify-center text-sm font-medium text-white/90 transition hover:bg-white/10"
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
        @endif
    </div>
@endif

@include('layouts.partials.page-carousel-script')
