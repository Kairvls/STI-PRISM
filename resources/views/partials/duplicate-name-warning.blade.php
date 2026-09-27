{{--
    Soft duplicate-name warning shown inside a form.
    $matches: list of ['name', 'employee_id', 'department', 'position', 'status', 'items', 'type', 'url']
    $noun: "person", "reporter", …
    $wrapperClass: spacing around the box
--}}
@php
    $matches = collect($matches ?? []);
    $noun = $noun ?? 'person';
    $wrapperClass = $wrapperClass ?? 'mx-5 mt-5';
@endphp

@if ($matches->isNotEmpty())
    <div class="{{ $wrapperClass }} rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200" data-duplicate-warning>
        <div class="flex items-start gap-2.5">
            <i data-lucide="users" class="mt-0.5 h-4 w-4 shrink-0 text-amber-600"></i>
            <div class="min-w-0 flex-1">
                <p class="font-semibold">
                    {{ $matches->count() === 1 ? 'Someone with this name is already listed' : $matches->count().' people with this name are already listed' }}
                </p>
                <p class="mt-0.5 text-amber-800/90">Check that you are not adding the same {{ $noun }} twice. Two different people can share a name, so this is only a warning.</p>

                <ul class="mt-2.5 space-y-1.5">
                    @foreach ($matches as $match)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white/80 px-3 py-2 ring-1 ring-amber-100">
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900">{{ $match['name'] }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ implode(' · ', array_filter([
                                        $match['employee_id'] ?? null ?: 'No employee ID',
                                        $match['type'] ?? null,
                                        $match['position'] ?? null,
                                        $match['department'] ?? null,
                                        $match['status'] ?? null,
                                        isset($match['items']) ? $match['items'].' '.\Illuminate\Support\Str::plural('item', (int) $match['items']).' held' : null,
                                    ])) }}
                                </p>
                            </div>
                            @if (! empty($match['url']))
                                <a href="{{ $match['url'] }}" target="_blank" rel="noopener" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-[#0025cc] hover:underline">
                                    Open existing
                                    <i data-lucide="external-link" class="h-3 w-3"></i>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <label class="mt-3 flex cursor-pointer items-start gap-2 text-sm font-medium text-amber-900">
                    <input type="checkbox" name="confirm_duplicate" value="1" class="mt-0.5 h-4 w-4 rounded border-amber-300 text-[#0025cc] focus:ring-[#0025cc]/30">
                    <span>This is a different {{ $noun }}. Save anyway.</span>
                </label>
            </div>
        </div>
    </div>
@endif
