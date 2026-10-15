@php
    $scheduleTabs = [
        [
            'key' => 'equipment',
            'label' => 'Equipment',
            'hint' => 'Per-asset maintenance',
            'icon' => 'wrench',
            'url' => \App\Support\SchedulePortal::schedulesUrl(),
            'active' => request()->is('maintenance/schedules', 'maintenance/schedules/today', 'admin/schedules'),
            'overdue' => \Illuminate\Support\Facades\DB::table('maintenance_schedules_table')
                ->where('maintenance_schedule_status', '!=', 'Completed')
                ->whereNotNull('maintenance_schedule_next_date')
                ->whereDate('maintenance_schedule_next_date', '<', today())
                ->count(),
        ],
        [
            'key' => 'room',
            'label' => 'Room',
            'hint' => 'Per-room inspections',
            'icon' => 'door-open',
            'url' => \App\Support\SchedulePortal::schedulesUrl('/rooms'),
            'active' => request()->is('maintenance/schedules/rooms*', 'admin/schedules/rooms*'),
            'overdue' => \App\Support\RoomInspections::overdueCount(),
        ],
        [
            'key' => 'semestral',
            'label' => 'Semestral',
            'hint' => 'Campus-wide inspections',
            'icon' => 'clipboard-list',
            'url' => \App\Support\SchedulePortal::semesterUrl(),
            'active' => request()->is('maintenance/semester-inspections*', 'admin/semester-inspections*'),
            'overdue' => \Illuminate\Support\Facades\Schema::hasTable('semester_inspection_campaigns_table')
                ? \Illuminate\Support\Facades\DB::table('semester_inspection_campaigns_table')
                    ->whereIn('campaign_status', ['Active', 'In Progress'])
                    ->whereDate('campaign_due_date', '<', today())
                    ->count()
                : 0,
        ],
    ];
@endphp

<nav class="grid grid-cols-1 gap-2 rounded-2xl border border-slate-200 bg-white p-1.5 sm:grid-cols-3" aria-label="Maintenance schedule type">
    @foreach ($scheduleTabs as $tab)
        <a
            href="{{ $tab['url'] }}"
            class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition {{ $tab['active'] ? 'bg-[#0025cc] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
            @if ($tab['active']) aria-current="page" @endif
        >
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $tab['active'] ? 'bg-white/15' : 'bg-slate-100 text-slate-500' }}">
                <i data-lucide="{{ $tab['icon'] }}" class="h-4 w-4"></i>
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold">{{ $tab['label'] }}</span>
                <span class="block truncate text-xs {{ $tab['active'] ? 'text-white/75' : 'text-slate-400' }}">{{ $tab['hint'] }}</span>
            </span>
            @if ($tab['overdue'] > 0)
                <span
                    class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold {{ $tab['active'] ? 'bg-white text-rose-600' : 'bg-rose-50 text-rose-600 ring-1 ring-rose-100' }}"
                    title="{{ $tab['overdue'] }} overdue"
                >{{ $tab['overdue'] }} overdue</span>
            @endif
        </a>
    @endforeach
</nav>
