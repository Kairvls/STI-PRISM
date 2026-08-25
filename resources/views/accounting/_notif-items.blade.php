@php
    $emptyMessages = [
        'today' => 'No alerts today.',
        'week' => 'No alerts this week.',
        'month' => 'No alerts this month.',
        'year' => 'No alerts this year.',
    ];
    $emptyMessage = $emptyMessages[$period ?? 'today'] ?? 'No notifications for Accounting yet.';
@endphp

@forelse ($items as $item)
    <div class="acc-notif-item">
        <p class="text-sm font-semibold text-slate-900">{{ $item->notification_title }}</p>
        <p class="mt-0.5 text-xs leading-relaxed text-slate-600">{{ $item->notification_message }}</p>
        <p class="mt-1.5 text-[11px] text-slate-400">{{ $item->notification_created_at ? \Carbon\Carbon::parse($item->notification_created_at)->format('M d, Y g:i A') : '' }}</p>
    </div>
@empty
    <div class="p-6"><div class="acc-empty">{{ $emptyMessage }}</div></div>
@endforelse
