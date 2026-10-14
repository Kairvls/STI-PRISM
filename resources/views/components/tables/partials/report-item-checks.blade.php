@php
    $itemChecks = \App\Support\IssueChecks::decodeStored($item->report_item_checks ?? null);
@endphp

@if (count($itemChecks) > 0)
    <div class="mt-2 rounded-lg border border-slate-200 bg-white px-3 py-2">
        <p class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
            <i data-lucide="list-checks" class="h-3 w-3"></i>
            Quick checks before reporting
        </p>
        <ul class="mt-1.5 space-y-1">
            @foreach ($itemChecks as $check)
                @php
                    $answer = (string) ($check['answer'] ?? '');
                    $level = $check['level'] ?? null;
                    $answerClass = match (true) {
                        $level === \App\Support\ReportSeverity::CRITICAL => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                        $answer === \App\Support\IssueChecks::ANSWER_NOT_FIXED => 'bg-slate-100 text-slate-600',
                        default => 'bg-blue-50 text-blue-700',
                    };
                @endphp
                <li class="flex items-start justify-between gap-3 text-xs">
                    <span class="min-w-0 text-slate-700">{{ $check['question'] ?? '' }}</span>
                    <span class="flex shrink-0 items-center gap-1">
                        <span class="rounded-md px-1.5 py-0.5 font-semibold {{ $answerClass }}">
                            {{ $answer === \App\Support\IssueChecks::ANSWER_NOT_FIXED ? 'Still not working' : \App\Support\IssueChecks::answerLabel($answer) }}
                        </span>
                        @if (\App\Support\ReportSeverity::isLevel($level))
                            <span class="rounded-md px-1.5 py-0.5 font-semibold {{ \App\Support\ReportSeverity::meta($level)['soft'] }}">
                                {{ $level }}
                            </span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
