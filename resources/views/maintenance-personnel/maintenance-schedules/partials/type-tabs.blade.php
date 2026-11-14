@php
    $equipmentOverdue = \Illuminate\Support\Facades\Schema::hasTable('maintenance_schedules_table')
        ? \Illuminate\Support\Facades\DB::table('maintenance_schedules_table')
            ->where('maintenance_schedule_status', '!=', 'Completed')
            ->whereNotNull('maintenance_schedule_next_date')
            ->whereDate('maintenance_schedule_next_date', '<', today())
            ->count()
        : 0;
    $roomOverdue = \App\Support\RoomInspections::overdueCount();
    $semestralOverdue = \Illuminate\Support\Facades\Schema::hasTable('semester_inspection_campaigns_table')
        ? \Illuminate\Support\Facades\DB::table('semester_inspection_campaigns_table')
            ->whereIn('campaign_status', ['Active', 'In Progress'])
            ->whereDate('campaign_due_date', '<', today())
            ->count()
        : 0;

    $scheduleTabs = [
        [
            'key' => 'all',
            'label' => 'All',
            'hint' => 'Equipment, rooms, and semestral',
            'icon' => 'layers',
            'url' => \App\Support\SchedulePortal::schedulesUrl('/all'),
            'active' => request()->is('maintenance/schedules/all', 'admin/schedules/all'),
            'overdue' => $equipmentOverdue + $roomOverdue + $semestralOverdue,
        ],
        [
            'key' => 'equipment',
            'label' => 'Equipment',
            'hint' => 'Per-asset maintenance',
            'icon' => 'wrench',
            'url' => \App\Support\SchedulePortal::schedulesUrl(),
            'active' => request()->is('maintenance/schedules', 'maintenance/schedules/today', 'admin/schedules'),
            'overdue' => $equipmentOverdue,
        ],
        [
            'key' => 'room',
            'label' => 'Room',
            'hint' => 'Per-room inspections',
            'icon' => 'door-open',
            'url' => \App\Support\SchedulePortal::schedulesUrl('/rooms'),
            'active' => request()->is('maintenance/schedules/rooms*', 'admin/schedules/rooms*'),
            'overdue' => $roomOverdue,
        ],
        [
            'key' => 'semestral',
            'label' => 'Semestral',
            'hint' => 'Campus-wide inspections',
            'icon' => 'clipboard-list',
            'url' => \App\Support\SchedulePortal::semesterUrl(),
            'active' => request()->is('maintenance/semester-inspections*', 'admin/semester-inspections*'),
            'overdue' => $semestralOverdue,
        ],
    ];
@endphp

<nav class="grid grid-cols-1 gap-2 rounded-2xl border border-slate-200 bg-slate-100 p-1.5 sm:grid-cols-2 xl:grid-cols-4" aria-label="Maintenance schedule type">
    @foreach ($scheduleTabs as $tab)
        <a
            href="{{ $tab['url'] }}"
            class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition {{ $tab['active'] ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:bg-white/70 hover:text-slate-500' }}"
            @if ($tab['active']) aria-current="page" @endif
        >
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $tab['active'] ? 'bg-slate-100 text-slate-700' : 'bg-slate-200/80 text-slate-400' }}">
                <i data-lucide="{{ $tab['icon'] }}" class="h-4 w-4"></i>
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold">{{ $tab['label'] }}</span>
                <span class="block truncate text-xs {{ $tab['active'] ? 'text-slate-500' : 'text-slate-400' }}">{{ $tab['hint'] }}</span>
            </span>
            @if ($tab['overdue'] > 0)
                <span
                    class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 {{ $tab['active'] ? 'bg-rose-50 text-rose-600 ring-rose-100' : 'bg-slate-200 text-slate-500 ring-slate-200' }}"
                    title="{{ $tab['overdue'] }} overdue"
                >{{ $tab['overdue'] }} overdue</span>
            @endif
        </a>
    @endforeach
</nav>
