@if (\App\Support\ReportSeverity::hasTemplateColumn())
    <div>
        <label class="mb-2 block text-sm font-semibold text-slate-700">Priority when reported</label>
        <select
            name="issue_template_severity"
            @if ($model) x-model="{{ $model }}" @endif
            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
        >
            <option value="">Automatic (decided from the issue name)</option>
            @foreach (\App\Support\ReportSeverity::levels() as $level)
                <option value="{{ $level }}">
                    {{ $level }} — {{ \App\Support\ReportSeverity::meta($level)['meaning'] }}
                </option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-slate-400">
            Room, repeat reports, and safety signs can still raise or lower it.
        </p>
    </div>
@endif
