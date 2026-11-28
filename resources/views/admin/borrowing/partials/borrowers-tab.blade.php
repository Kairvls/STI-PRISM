@php
    use Carbon\Carbon;

    $hasBorrowerFilters = filled($borrowerDirQuery ?? '') || (($borrowerDirFilter ?? 'all') !== 'all');
@endphp

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    {{-- Compact Filter Toolbar (matching Equipment Inventory) --}}
    <div class="border-b border-slate-200 bg-white px-4 py-2.5">
        <form method="GET" action="{{ route('admin.borrowing.index') }}" class="flex flex-wrap items-center gap-2 lg:flex-nowrap">
            <input type="hidden" name="view" value="borrowers">

            <div class="relative min-w-[200px] flex-1">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                <input
                    type="search"
                    name="borrower_q"
                    value="{{ $borrowerDirQuery ?? '' }}"
                    placeholder="Search borrower name or organization…"
                    class="h-9 w-full rounded-lg border border-slate-200 bg-slate-50/70 pl-9 pr-3 text-xs text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100"
                >
            </div>

            <div class="relative w-full sm:w-52 lg:shrink-0">
                <i data-lucide="list-filter" class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                <select
                    name="borrower_filter"
                    aria-label="Filter borrowers"
                    onchange="this.form.submit()"
                    class="h-9 w-full appearance-none rounded-lg border border-slate-200 bg-white pl-8 pr-8 text-xs font-medium text-slate-700 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                >
                    <option value="all" @selected(($borrowerDirFilter ?? 'all') === 'all')>
                        All borrowers ({{ number_format($borrowerDirectoryStats['total'] ?? 0) }})
                    </option>
                    <option value="registered" @selected(($borrowerDirFilter ?? '') === 'registered')>
                        Registered only ({{ number_format($borrowerDirectoryStats['registered'] ?? 0) }})
                    </option>
                    <option value="history" @selected(($borrowerDirFilter ?? '') === 'history')>
                        History only ({{ number_format($borrowerDirectoryStats['history'] ?? 0) }})
                    </option>
                </select>
                <i data-lucide="chevron-down" class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
            </div>

            @if ($hasBorrowerFilters)
                <a
                    href="{{ route('admin.borrowing.index', ['view' => 'borrowers']) }}"
                    class="inline-flex h-9 shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                >
                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                    <span>Clear</span>
                </a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="border-b border-slate-200 bg-slate-50/70">
                <tr class="text-[11px] font-semibold uppercase tracking-[0.06em] text-slate-500">
                    <th class="px-5 py-2.5">Borrower</th>
                    <th class="px-4 py-2.5">Status</th>
                    <th class="px-4 py-2.5">Organization</th>
                    <th class="px-4 py-2.5">Contact</th>
                    <th class="px-4 py-2.5">Activity</th>
                    <th class="px-4 py-2.5">Last borrow</th>
                    <th class="w-[156px] px-4 py-2.5 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse (($borrowerDirectory ?? collect()) as $person)
                    @php
                        $isRegistered = !empty($person->is_registered);
                        $org = trim((string) ($person->organization ?? ''));
                    @endphp
                    <tr class="transition-colors hover:bg-slate-50/70">
                        <td class="px-5 py-3">
                            <p class="text-[15px] font-semibold text-slate-900">{{ $person->name }}</p>
                            @if (!empty($person->position))
                                <p class="mt-0.5 text-xs text-slate-500">{{ $person->position }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($isRegistered)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200/70">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Registered
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-200/70">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                    History only
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs font-medium text-slate-700">{{ $org !== '' ? $org : '—' }}</td>
                        <td class="px-4 py-3">
                            <p class="text-xs font-medium text-slate-700">{{ \App\Support\PhoneNumber::formatForDisplay($person->contact ?? null) ?: '—' }}</p>
                            @if (!empty($person->email))
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ $person->email }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">{{ (int) ($person->total_borrows ?? 0) }} total</span>
                                @if ((int) ($person->open_borrows ?? 0) > 0)
                                    <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-200/60">{{ (int) $person->open_borrows }} open</span>
                                @endif
                                @if ((int) ($person->overdue_borrows ?? 0) > 0)
                                    <span class="rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 ring-1 ring-rose-200/60">{{ (int) $person->overdue_borrows }} overdue</span>
                                @endif
                                @if ((int) ($person->event_borrows ?? 0) > 0)
                                    <span class="rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 ring-1 ring-indigo-200/60">{{ (int) $person->event_borrows }} event</span>
                                @endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-xs font-medium text-slate-600">
                            {{ !empty($person->last_borrow_date) ? Carbon::parse($person->last_borrow_date)->format('M j, Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-left">
                            <div class="inline-flex items-center gap-1.5">
                                <button
                                    type="button"
                                    onclick="openBorrowerRecordsModal(@js($person->name))"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                >
                                    <i data-lucide="eye" class="h-3.5 w-3.5 text-slate-500"></i>
                                    <span>Records</span>
                                </button>
                                @unless ($isRegistered)
                                    @php $bRowMenuId = 'brwDirMenu_' . $loop->index; @endphp
                                    <button
                                        type="button"
                                        onclick="toggleBrwMenu(this, '{{ $bRowMenuId }}', event)"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
                                        title="More actions"
                                    >
                                        <i data-lucide="more-horizontal" class="h-4 w-4"></i>
                                    </button>
                                    <div
                                        id="{{ $bRowMenuId }}"
                                        data-brw-menu
                                        role="menu"
                                        class="fixed z-[90] hidden w-48 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-950/10"
                                    >
                                        <button
                                            type="button"
                                            role="menuitem"
                                            onclick="closeBrwMenus(); openRegisterBorrowerModal(@js(['name' => $person->name, 'organization' => $org]))"
                                            class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-[#0025cc] transition hover:bg-blue-50"
                                        >
                                            <i data-lucide="user-plus" class="h-3.5 w-3.5"></i>
                                            <span>Register borrower</span>
                                        </button>
                                    </div>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14 text-center">
                            <p class="text-sm font-semibold text-slate-700">No borrowers found</p>
                            <p class="mt-1 text-xs text-slate-400">People appear here from borrow history or when you register an external borrower.</p>
                            <button
                                type="button"
                                onclick="openRegisterBorrowerModal()"
                                class="mt-4 inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3.5 text-xs font-semibold text-white transition hover:bg-blue-800"
                            >
                                <i data-lucide="user-plus" class="h-3.5 w-3.5"></i>
                                <span>Register borrower</span>
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($borrowerDirectory, 'hasPages') && $borrowerDirectory->hasPages())
        <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700">{{ $borrowerDirectory->firstItem() }}</span>
                to <span class="font-semibold text-slate-700">{{ $borrowerDirectory->lastItem() }}</span>
                of <span class="font-semibold text-slate-700">{{ $borrowerDirectory->total() }}</span> borrowers
            </p>
            <div>{{ $borrowerDirectory->links() }}</div>
        </div>
    @endif
</div>
