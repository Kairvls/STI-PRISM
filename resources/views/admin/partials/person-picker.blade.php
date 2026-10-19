{{--
  Searchable person picker. Expects Alpine parent with:
  personId, personQuery, personOpen, personResults, personLabel, pickPerson(person), clearPerson(), searchPersons()
  Optional: hiddenName (default person_id)
--}}
@php
    $hiddenName = $hiddenName ?? 'person_id';
    $label = $label ?? 'Person';
    $required = $required ?? false;
    $hint = $hint ?? 'Search Faculty & Staff by name or employee ID';
    $searchUrl = $searchUrl ?? (request()->is('admin/*')
        ? route('admin.persons.search')
        : (Route::has('maintenance.persons.search')
            ? route('maintenance.persons.search')
            : route('admin.persons.search')));
@endphp

<div class="relative" @click.outside="personOpen = false">
    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
        {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
    </label>
    <input type="hidden" name="{{ $hiddenName }}" :value="personId" @if ($required) required @endif>
    <button
        type="button"
        @click="personOpen = !personOpen; if (personOpen) { $nextTick(() => $refs.personSearch?.focus()); if (!personResults.length) searchPersons(@js($searchUrl)); }"
        class="flex h-10 w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-left text-sm outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
    >
        <span class="min-w-0 truncate" :class="personId ? 'text-slate-800' : 'text-slate-400'" x-text="personLabel || 'Search faculty & staff…'"></span>
        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400"></i>
    </button>
    <p class="mt-1 text-[11px] text-slate-400">{{ $hint }}</p>
    <div
        x-show="personOpen"
        x-cloak
        class="absolute left-0 right-0 top-full z-50 mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
    >
        <div class="border-b border-slate-100 p-2">
            <input
                type="search"
                x-ref="personSearch"
                x-model="personQuery"
                @input.debounce.250ms="searchPersons(@js($searchUrl))"
                placeholder="Search name or employee ID…"
                class="h-8 w-full rounded-lg border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                @keydown.escape.stop="personOpen = false"
            >
        </div>
        <ul class="max-h-52 overflow-y-auto py-1">
            <li>
                <button type="button" class="flex w-full px-3 py-2 text-left text-sm text-slate-500 hover:bg-slate-50" @click="clearPerson()">Clear selection</button>
            </li>
            <template x-for="row in personResults" :key="'person-' + row.person_id">
                <li>
                    <button
                        type="button"
                        class="flex w-full flex-col px-3 py-2 text-left hover:bg-slate-50"
                        :class="String(personId) === String(row.person_id) ? 'bg-slate-50' : ''"
                        @click="pickPerson(row)"
                    >
                        <span class="text-sm font-medium text-slate-900" x-text="row.person_full_name"></span>
                        <span class="font-mono text-[11px] text-slate-500" x-text="row.label"></span>
                    </button>
                </li>
            </template>
            <li x-show="personResults.length === 0" class="px-3 py-3 text-center text-xs text-slate-400">No faculty or staff found</li>
        </ul>
    </div>
</div>
