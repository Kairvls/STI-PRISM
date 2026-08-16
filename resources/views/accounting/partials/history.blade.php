<div class="acc-panel">
    <h3 class="acc-panel-title">Workflow history</h3>
    <p class="acc-panel-sub">Accounting actions on this record</p>
    <div class="mt-3 space-y-2.5">
        @forelse ($history as $event)
            @php
                $st = (string) ($event->approval_log_approval_status ?? '');
                $dot = 'bg-slate-400';
                if (in_array($st, ['Approved'], true)) { $dot = 'bg-emerald-500'; }
                elseif (in_array($st, ['Rejected'], true)) { $dot = 'bg-rose-500'; }
                elseif (in_array($st, ['Under Review', 'Minor Revision', 'Revision'], true)) { $dot = 'bg-amber-500'; }
            @endphp
            <div class="flex gap-2.5">
                <span class="acc-timeline-dot {{ $dot }}"></span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-slate-900">{{ $st }}</p>
                    <p class="text-[11px] leading-snug text-slate-500">
                        {{ $event->user_full_name ?? 'Accounting' }} · {{ $event->approval_log_level }}
                        @if ($event->approval_log_approved_at)
                            · {{ \Carbon\Carbon::parse($event->approval_log_approved_at)->format('M d, Y g:i A') }}
                        @endif
                    </p>
                    @if ($event->approval_log_approval_remarks)
                        <p class="mt-0.5 text-[11px] text-slate-600">{{ $event->approval_log_approval_remarks }}</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="acc-empty !py-6">No Accounting actions recorded yet.</div>
        @endforelse
    </div>
</div>
