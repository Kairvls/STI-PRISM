@php
    $lifecycleAlerts = $lifecycleAlerts ?? collect();
    $semesterInspectionDue = $semesterInspectionDue ?? collect();
@endphp

@if ($lifecycleAlerts->isNotEmpty() || $semesterInspectionDue->isNotEmpty())
    <div class="mb-6 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Semester inspections due</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Active campaigns overdue or due within 7 days</p>
                </div>
                <a href="{{ url('/maintenance/semester-inspections') }}" class="text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
            </div>
            <ul class="space-y-3">
                @forelse ($semesterInspectionDue as $campaign)
                    @php
                        $due = \Carbon\Carbon::parse($campaign->campaign_due_date)->startOfDay();
                        $days = (int) now()->startOfDay()->diffInDays($due, false);
                        $tag = $days < 0 ? abs($days).'d overdue' : ($days === 0 ? 'Due today' : 'In '.$days.'d');
                        $tagClass = $days <= 0 ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700';
                    @endphp
                    <li>
                        <a href="{{ url('/maintenance/semester-inspections/'.$campaign->campaign_id) }}" class="flex items-center justify-between gap-3 rounded-xl px-2 py-1.5 transition hover:bg-slate-50">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $campaign->campaign_title }}</p>
                                <p class="truncate text-xs text-slate-500">
                                    {{ $campaign->campaign_semester }}
                                    · {{ $due->format('M j, Y') }}
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $tagClass }}">{{ $tag }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-2 py-6 text-center text-sm text-slate-400">No semester inspections due soon.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Replacement suggestions</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Aging assets nearing end of useful life</p>
                </div>
                <a href="{{ url('/maintenance/replacement-suggestions') }}" class="text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
            </div>
            <ul class="space-y-3">
                @forelse ($lifecycleAlerts as $alert)
                    @php
                        $yearsLeft = (int) ($alert->years_remaining ?? 0);
                        $suggestion = \App\Support\EquipmentLifecycle::suggestAction(
                            $yearsLeft,
                            $alert->equipment_inventory_status ?? null
                        );
                    @endphp
                    <li class="flex items-center justify-between gap-3 rounded-xl px-2 py-1.5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $alert->equipment_name }}</p>
                            <p class="truncate text-xs text-slate-500">
                                {{ $alert->room_name ?: 'No room' }}
                                · Age {{ (int) ($alert->age_years ?? 0) }}y
                                · {{ $suggestion['label'] }}
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700">
                            {{ $suggestion['hint'] }}
                        </span>
                    </li>
                @empty
                    <li class="px-2 py-6 text-center text-sm text-slate-400">No aging equipment needing attention.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endif
