{{--
    Banner shown when a list is opened from a daily reminder item (?focus=...).
    Expects $focus = ['label' => string, 'description' => ?string, 'clear_url' => string, 'scope' => 'mine'|'shared'|null]
    and optional $total (number of matching rows).
--}}
@if(!empty($focus))
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50/70 px-4 py-3" data-attention-focus>
        <div class="flex min-w-0 items-start gap-3">
            <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-[#0025cc] ring-1 ring-blue-100">
                <i data-lucide="filter" class="h-4 w-4"></i>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-900">
                    Showing: {{ $focus['label'] }}
                    @isset($total)
                        <span class="ml-1 rounded-md bg-white px-1.5 py-0.5 text-xs font-semibold text-[#0025cc] ring-1 ring-blue-100">{{ number_format((int) $total) }}</span>
                    @endisset
                </p>
                <p class="mt-0.5 text-xs leading-5 text-slate-600">
                    @if(!empty($focus['description'])){{ $focus['description'] }}@endif
                    @if(($focus['scope'] ?? null) === 'mine')
                        <span class="text-slate-500">Only your documents or items assigned to you.</span>
                    @elseif(($focus['scope'] ?? null) === 'shared')
                        <span class="text-slate-500">Shared with everyone in your role.</span>
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ $focus['clear_url'] }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-[#0025cc] transition hover:bg-blue-100">
            <i data-lucide="x" class="h-3.5 w-3.5"></i>
            Clear filter
        </a>
    </div>
@endif
