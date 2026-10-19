@php
    $inputName = $inputName ?? 'department_ids[]';
    $selectedIds = array_map('strval', $selectedIds ?? []);
    $departments = $departments ?? collect();
    $fieldClass = $fieldClass ?? 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80';
    $selectedCount = count($selectedIds);
    $selectedNames = $departments
        ->filter(fn ($dept) => in_array((string) $dept->department_id, $selectedIds, true))
        ->pluck('department_name')
        ->values();
    $summary = $selectedCount === 0
        ? 'No department'
        : ($selectedCount === 1
            ? $selectedNames->first()
            : $selectedCount.' departments');
@endphp
<div class="room-dept-picker relative" data-dept-picker>
    <button
        type="button"
        data-dept-trigger
        class="{{ $fieldClass }} flex items-center justify-between gap-2 text-left"
        aria-haspopup="listbox"
        aria-expanded="false"
    >
        <span data-dept-summary class="min-w-0 flex-1 truncate {{ $selectedCount ? 'text-slate-900' : 'text-slate-400' }}">{{ $summary }}</span>
        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400"></i>
    </button>
    <div
        data-dept-panel
        class="absolute left-0 right-0 top-full z-30 mt-1 hidden overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-slate-200"
    >
        <div class="border-b border-slate-100 p-2">
            <input
                type="search"
                data-dept-search
                placeholder="Search departments…"
                class="h-9 w-full rounded-lg border-0 bg-slate-50 px-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:ring-2 focus:ring-slate-900/10"
                autocomplete="off"
            >
        </div>
        <ul class="max-h-52 overflow-y-auto py-1" role="listbox" aria-multiselectable="true">
            @forelse ($departments as $department)
                <li data-dept-option data-label="{{ mb_strtolower($department->department_name) }}">
                    <label class="flex cursor-pointer items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                        <input
                            type="checkbox"
                            name="{{ $inputName }}"
                            value="{{ $department->department_id }}"
                            class="h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]/30"
                            data-dept-check
                            data-dept-name="{{ $department->department_name }}"
                            @checked(in_array((string) $department->department_id, $selectedIds, true))
                        >
                        <span class="min-w-0 truncate">{{ $department->department_name }}</span>
                    </label>
                </li>
            @empty
                <li class="px-3 py-4 text-center text-xs text-slate-400">No departments yet.</li>
            @endforelse
        </ul>
        <div class="flex items-center justify-between border-t border-slate-100 px-3 py-2">
            <button type="button" data-dept-clear class="text-xs font-semibold text-slate-500 hover:text-slate-800">Clear</button>
            <button type="button" data-dept-done class="text-xs font-semibold text-[#0025cc] hover:underline">Done</button>
        </div>
    </div>
</div>
