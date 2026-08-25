@php
    $deadlineRaw = $deadline ?? null;
    $tone = null;
    $label = null;

    if (!empty($deadlineRaw)) {
        try {
            $due = \Carbon\Carbon::parse($deadlineRaw)->startOfDay();
            $today = now()->startOfDay();

            if ($due->lt($today)) {
                $tone = 'overdue';
                $label = 'Overdue';
            } elseif ($due->equalTo($today)) {
                $tone = 'today';
                $label = 'Due today';
            } else {
                $days = (int) $today->diffInDays($due);
                if ($days <= 7) {
                    $tone = 'week';
                    $label = $days === 1 ? 'Due tomorrow' : ('Due in ' . $days . 'd');
                }
            }
        } catch (\Throwable $e) {
            $tone = null;
        }
    }
@endphp
@if ($tone)
    <span class="acc-deadline-badge is-{{ $tone }}" title="Deadline: {{ \Carbon\Carbon::parse($deadlineRaw)->format('M d, Y') }}">
        {{ $label }}
    </span>
@endif
