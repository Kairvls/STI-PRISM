@php
    $previewRows = $rows->values()->map(function ($row) {
        $href = (string) ($row['href'] ?? '');
        $filterHref = ($row['tone'] ?? '') === 'semestral'
            ? $href
            : $href.(str_contains($href, '?') ? '&' : '?').http_build_query(['search' => $row['title']]);

        return [
            'title' => $row['title'],
            'detail' => $row['detail'],
            'identity' => $row['identity'] ?? '',
            'date' => $row['date'],
            'date_label' => $row['date_label'],
            'type' => $row['type'],
            'tone' => $row['tone'],
            'status' => $row['status'],
            'href' => $filterHref,
        ];
    });
@endphp
<section
    class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
    x-data="{ page: 1, perPage: 8, total: {{ $rows->count() }}, preview: null, rows: {{ \Illuminate\Support\Js::from($previewRows) }} }"
    @keydown.escape.window="preview = null"
>
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
            <h2 class="text-base font-semibold text-slate-950">All schedules</h2>
            @if (filled($search ?? null))
                <p class="mt-0.5 text-sm text-slate-500">
                    Filtered by “{{ $search }}”.
                    <a href="{{ url()->current() }}" class="font-semibold text-[#0025cc] hover:underline">Show all</a>
                </p>
            @else
                <p class="mt-0.5 text-sm text-slate-500">Equipment maintenance, room inspections, and semestral inspections.</p>
            @endif
        </div>
        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold tabular-nums text-slate-500">{{ number_format($rows->count()) }}</span>
    </div>

    <div class="divide-y divide-slate-100">
        @forelse ($rows as $index => $row)
            <button
                type="button"
                class="flex w-full items-center justify-between gap-4 px-5 py-3.5 text-left transition hover:bg-slate-50"
                x-show="{{ $index }} >= (page - 1) * perPage && {{ $index }} < page * perPage"
                @click="preview = rows[{{ $index }}]"
            >
                <span class="min-w-0">
                    <span class="block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Schedule name</span>
                    <span class="block truncate text-base font-semibold text-slate-900">{{ $row['title'] }}</span>
                    @if ($row['detail'] !== '')
                        <span class="mt-0.5 block truncate text-sm text-slate-500">{{ $row['detail'] }}</span>
                    @endif
                    @if (($row['identity'] ?? '') !== '')
                        <span class="mt-0.5 block truncate text-sm font-medium text-slate-600">{{ $row['identity'] }}</span>
                    @endif
                </span>
                <span class="flex shrink-0 items-center gap-2">
                    <span class="hidden text-sm font-medium text-slate-500 sm:inline">{{ $row['date_label'] }}</span>
                    <span @class([
                        'rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1',
                        'bg-sky-50 text-sky-700 ring-sky-100' => $row['tone'] === 'equipment',
                        'bg-amber-50 text-amber-800 ring-amber-100' => $row['tone'] === 'room',
                        'bg-violet-50 text-violet-700 ring-violet-100' => $row['tone'] === 'semestral',
                    ])>{{ $row['type'] }}</span>
                    <span @class([
                        'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                        'bg-rose-50 text-rose-700' => $row['status'] === 'Overdue',
                        'bg-emerald-50 text-emerald-700' => $row['status'] === 'Completed',
                        'bg-slate-100 text-slate-600' => ! in_array($row['status'], ['Overdue', 'Completed'], true),
                    ])>{{ $row['status'] }}</span>
                </span>
            </button>
        @empty
            <p class="px-5 py-10 text-center text-sm text-slate-400">No schedules yet</p>
        @endforelse
    </div>

    @if ($rows->count() > 8)
        <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-3">
            <p class="text-sm text-slate-500" x-text="'Showing ' + (((page - 1) * perPage) + 1) + '–' + Math.min(page * perPage, total) + ' of ' + total"></p>
            <div class="flex items-center gap-1">
                <button type="button" class="rounded-lg px-2.5 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100 disabled:cursor-not-allowed disabled:text-slate-300" :disabled="page <= 1" @click="page = Math.max(1, page - 1)">Previous</button>
                <template x-for="n in Math.ceil(total / perPage)" :key="'all-page-'+n">
                    <button
                        type="button"
                        class="min-w-8 rounded-lg px-2.5 py-1.5 text-sm font-semibold"
                        :class="n === page ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-slate-100'"
                        @click="page = n"
                        x-text="n"
                    ></button>
                </template>
                <button type="button" class="rounded-lg px-2.5 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100 disabled:cursor-not-allowed disabled:text-slate-300" :disabled="page >= Math.ceil(total / perPage)" @click="page = Math.min(Math.ceil(total / perPage), page + 1)">Next</button>
            </div>
        </div>
    @endif

    <template x-teleport="body">
        <div
            x-show="preview"
            x-cloak
            class="fixed inset-0 z-[12000] flex items-start justify-center bg-black/50 px-4 pt-16"
            @click.self="preview = null"
        >
            <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.18)] ring-1 ring-slate-200/80" @click.stop>
                <button type="button" class="absolute right-3 top-3 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close" @click="preview = null">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <div class="px-6 pb-2 pr-12 pt-6">
                    <p class="text-sm font-medium text-slate-400" x-text="preview ? preview.date_label : ''"></p>
                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Schedule name</p>
                    <h3 class="mt-0.5 text-lg font-semibold tracking-tight text-slate-900" x-text="preview ? preview.title : ''"></h3>
                    <p class="mt-1 text-base text-slate-500" x-show="preview && preview.detail" x-text="preview ? preview.detail : ''"></p>
                    <p class="mt-1 text-base font-medium text-slate-700" x-show="preview && preview.identity" x-text="preview ? preview.identity : ''"></p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span
                            class="rounded-full px-2.5 py-0.5 text-sm font-semibold ring-1"
                            :class="preview && preview.tone === 'room' ? 'bg-amber-50 text-amber-800 ring-amber-100' : (preview && preview.tone === 'semestral' ? 'bg-violet-50 text-violet-700 ring-violet-100' : 'bg-sky-50 text-sky-700 ring-sky-100')"
                            x-text="preview ? preview.type : ''"
                        ></span>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-sm font-semibold"
                            :class="preview && preview.status === 'Overdue' ? 'bg-rose-50 text-rose-700' : (preview && preview.status === 'Completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600')"
                            x-text="preview ? preview.status : ''"
                        ></span>
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-3.5">
                    <button type="button" class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900" @click="$dispatch('all-schedule-day', { date: preview.date, title: preview.title }); preview = null">Go to calendar</button>
                    <a :href="preview ? preview.href : '#'" class="rounded-lg bg-[#0025cc] px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#001fad]">Go to page</a>
                </div>
            </div>
        </div>
    </template>
</section>
