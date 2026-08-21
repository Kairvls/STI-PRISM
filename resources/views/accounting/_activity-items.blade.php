@forelse ($recentActivity as $event)
    <div class="acc-activity-item">
        <p class="text-xs font-semibold text-slate-900">{{ $event->approval_log_approval_status }} · {{ $event->approval_log_reference_type }}</p>
        <p class="text-[11px] text-slate-500">{{ $event->user_full_name ?? 'Accounting' }} · {{ $event->approval_log_approved_at ? \Carbon\Carbon::parse($event->approval_log_approved_at)->format('M d, g:i A') : '' }}</p>
    </div>
@empty
    <p class="py-4 text-center text-xs text-slate-400">No recent Accounting actions.</p>
@endforelse