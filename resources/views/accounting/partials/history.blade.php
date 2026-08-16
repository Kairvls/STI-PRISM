<div class="rounded-xl border border-gray-200 bg-white p-5">
    <h3 class="text-sm font-bold text-gray-900">Workflow history</h3>
    <p class="mt-0.5 text-xs text-gray-400">Accounting actions on this record</p>
    <div class="mt-4 space-y-3">
        @forelse ($history as $event)
            @php
                $st = (string) ($event->approval_log_approval_status ?? '');
                $dot = 'bg-gray-400';
                if (in_array($st, ['Approved'], true)) { $dot = 'bg-emerald-500'; }
                elseif (in_array($st, ['Rejected'], true)) { $dot = 'bg-rose-500'; }
                elseif (in_array($st, ['Under Review'], true)) { $dot = 'bg-amber-500'; }
            @endphp
            <div class="flex gap-3">
                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $dot }}"></span>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $st }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $event->user_full_name ?? 'Accounting' }} · {{ $event->approval_log_level }}
                        @if ($event->approval_log_approved_at)
                            · {{ \Carbon\Carbon::parse($event->approval_log_approved_at)->format('M d, Y g:i A') }}
                        @endif
                    </p>
                    @if ($event->approval_log_approval_remarks)
                        <p class="mt-1 text-xs text-gray-600">{{ $event->approval_log_approval_remarks }}</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="acc-empty rounded-lg p-6 text-center text-sm text-gray-500">No Accounting actions recorded yet.</div>
        @endforelse
    </div>
</div>
