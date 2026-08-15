@php
    $filters = $filters ?? ['q' => '', 'from' => '', 'to' => ''];
    $action = $action ?? url()->current();
    $hidden = $hidden ?? [];
    $placeholder = $placeholder ?? 'Search RIS, ATP, supplier, OR...';
@endphp

<form method="GET" action="{{ $action }}" class="flex w-full flex-wrap items-center gap-2">
    @foreach($hidden as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <div class="relative min-w-[220px] flex-1">
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
            <i data-lucide="search" class="h-4 w-4"></i>
        </span>
        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ $placeholder }}" class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm text-gray-700 outline-none placeholder:text-gray-400 focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
    </div>
    <input type="date" name="from" value="{{ $filters['from'] }}" title="From" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
    <input type="date" name="to" value="{{ $filters['to'] }}" title="To" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
    <button type="submit" class="rounded-lg bg-[#0037c7] px-3 py-2 text-sm font-semibold text-white">Filter</button>
    <a href="{{ $action }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-600">Clear</a>
</form>
