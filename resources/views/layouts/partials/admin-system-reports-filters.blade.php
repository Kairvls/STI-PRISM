@php
    $filters = $filters ?? ['q' => '', 'from' => '', 'to' => ''];
    $action = $action ?? url()->current();
    $placeholder = $placeholder ?? 'Search...';
@endphp

<div class="print-hidden border-b border-gray-100 px-5 py-4">
    <form method="GET" action="{{ $action }}" class="flex flex-wrap items-center gap-2">
        <div class="relative min-w-[220px] flex-1">
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ $placeholder }}" class="w-full rounded-lg border border-gray-200 py-2 px-3 text-sm text-gray-700">
        </div>
        <input type="date" name="from" value="{{ $filters['from'] }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
        <input type="date" name="to" value="{{ $filters['to'] }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
        <button type="submit" class="rounded-lg bg-slate-700 px-3 py-2 text-sm font-semibold text-white">Filter</button>
        <a href="{{ $action }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-600">Clear</a>
        <button type="button" onclick="window.print()" class="ml-auto rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700">Print</button>
    </form>
</div>
