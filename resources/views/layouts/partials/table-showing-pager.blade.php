@php
    $pager = $pager ?? null;
    $linkClass = $linkClass ?? '';
    $noun = $noun ?? 'records';
@endphp

@if($pager && method_exists($pager, 'total') && $pager->total() > 0)
    <div class="print-hidden flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-5 py-4">
        <p class="text-xs text-gray-500">
            Showing
            <span class="font-semibold text-gray-700">{{ $pager->firstItem() }}</span>
            –
            <span class="font-semibold text-gray-700">{{ $pager->lastItem() }}</span>
            of
            <span class="font-semibold text-gray-700">{{ $pager->total() }}</span>
            {{ $noun }}
        </p>
        @if($pager->hasPages())
            <div class="flex items-center gap-1">
                @if($pager->onFirstPage())
                    <span class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-300" title="No previous page">&lt;</span>
                @else
                    <a href="{{ $pager->previousPageUrl() }}" data-page="{{ $pager->currentPage() - 1 }}" title="Previous page" class="{{ $linkClass }} flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900">&lt;</a>
                @endif
                <span class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-slate-900 px-3 text-sm font-semibold text-white" title="Current page {{ $pager->currentPage() }}">
                    {{ $pager->currentPage() }}
                </span>
                @if($pager->hasMorePages())
                    <a href="{{ $pager->nextPageUrl() }}" data-page="{{ $pager->currentPage() + 1 }}" title="Next page" class="{{ $linkClass }} flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900">&gt;</a>
                @else
                    <span class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-300" title="No next page">&gt;</span>
                @endif
            </div>
        @endif
    </div>
@endif
