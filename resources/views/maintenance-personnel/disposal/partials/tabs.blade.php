@php
    $inAdmin = request()->is('admin/*');
    $base = $inAdmin ? '/admin/disposal' : '/maintenance/disposal';
    $onRequests = request()->is('admin/disposal/requests*', 'maintenance/disposal/requests*');
    $recordCount = \Illuminate\Support\Facades\DB::table('disposal_records_table')
        ->when(
            \Illuminate\Support\Facades\Schema::hasColumn('disposal_records_table', 'disposal_is_archived'),
            fn ($q) => $q->where('disposal_is_archived', 0)
        )
        ->count();
    $disposalTabs = [
        'records' => [
            'label' => $inAdmin ? 'Disposal records' : 'Equipment disposal',
            'icon' => 'trash-2',
            'count' => $recordCount,
            'title' => 'Active disposal records',
            'url' => url($base),
        ],
        'requests' => [
            'label' => $inAdmin ? 'Approvals' : 'My requests',
            'icon' => 'clipboard-check',
            'count' => \App\Support\DisposalRequests::pendingCount(),
            'title' => 'Waiting for approval',
            'url' => url($base.'/requests'.($inAdmin ? '' : '?status=all')),
        ],
    ];
    $activeDisposalTab = $onRequests ? 'requests' : 'records';
@endphp

<div class="flex items-center">
    <div class="inline-flex rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Disposal sections">
        @foreach ($disposalTabs as $tabKey => $tab)
            <a
                href="{{ $tab['url'] }}"
                role="tab"
                aria-selected="{{ $activeDisposalTab === $tabKey ? 'true' : 'false' }}"
                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition {{ $activeDisposalTab === $tabKey ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}"
            >
                <i data-lucide="{{ $tab['icon'] }}" class="h-4 w-4"></i>
                {{ $tab['label'] }}
                <span
                    class="rounded-full px-1.5 text-[11px] font-semibold {{ $tabKey === 'requests' && $tab['count'] > 0 ? 'bg-amber-100 text-amber-700' : ($activeDisposalTab === $tabKey ? 'bg-slate-100 text-slate-600' : 'bg-white text-slate-500') }}"
                    title="{{ $tab['title'] }}"
                >{{ number_format($tab['count']) }}</span>
            </a>
        @endforeach
    </div>
</div>
