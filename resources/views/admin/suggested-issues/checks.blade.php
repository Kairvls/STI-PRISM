@extends('layouts.maintenance-layout')

@section('title', 'Quick Checks')

@section('content')
@php
    $issueSeverity = \App\Support\ReportSeverity::isLevel($issue->issue_template_severity ?? null)
        ? $issue->issue_template_severity
        : \App\Support\ReportSeverity::defaultForIssue($issue->issue_template_name);
    $levelLabel = fn ($level) => $level ? $level : 'No change';
    $atLimit = $checks->count() >= $maxChecks;
@endphp

<style>[x-cloak]{display:none!important}</style>

<div class="min-h-full" x-data="issueChecksPage()">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a
            href="{{ route('admin.suggested-issues.index') }}"
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-slate-900"
        >
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Suggested Issues
        </a>

        <button
            type="button"
            @click="openCreate()"
            @disabled($atLimit)
            title="{{ $atLimit ? 'An issue can have up to '.$maxChecks.' quick checks.' : '' }}"
            class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            Add Check
        </button>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Quick checks for</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-950">{{ $issue->issue_template_name }}</h1>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-700">
                    {{ $issue->equipment_category_name ?? 'No category' }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    {{ $issue->issue_template_component ?: 'All in category' }}
                </span>
                <span class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-semibold {{ \App\Support\ReportSeverity::meta($issueSeverity)['pill'] }}">
                    Starts at {{ $issueSeverity }}
                </span>
            </div>
            <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-500">
                Reporters go through these in order after they pick this issue.
                <strong class="font-semibold text-slate-700">Try this</strong> checks end the report when the reporter taps "That fixed it".
                <strong class="font-semibold text-slate-700">Yes / No questions</strong> don't fix anything, but their answer can set the priority.
            </p>
        </div>

    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <h2 class="text-lg font-semibold text-slate-950">Checks · {{ $checks->count() }} of {{ $maxChecks }}</h2>
        </div>

        @forelse ($checks as $check)
            @php
                $isQuestion = $check->issue_check_kind === \App\Support\IssueChecks::KIND_ASK;
                $fixes = (int) ($fixCounts[$check->issue_check_id] ?? 0);
            @endphp
            <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 last:border-b-0 sm:flex-row sm:items-start">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-600">
                    {{ $loop->iteration }}
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($isQuestion)
                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-amber-700">Yes / No question</span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-blue-700">Try this</span>
                        @endif
                    </div>
                    <p class="mt-1.5 font-semibold text-slate-950">{{ $check->issue_check_question }}</p>
                    @if ($check->issue_check_hint)
                        <p class="mt-1 text-sm text-slate-500">{{ $check->issue_check_hint }}</p>
                    @endif

                    <div class="mt-2.5 flex flex-wrap items-center gap-2 text-xs">
                        @if ($isQuestion)
                            @foreach (['Yes' => $check->issue_check_severity_if_yes, 'No' => $check->issue_check_severity_if_no] as $answer => $level)
                                <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 font-semibold {{ $level ? \App\Support\ReportSeverity::meta($level)['soft'] : 'bg-slate-50 text-slate-500 ring-1 ring-slate-200' }}">
                                    {{ $answer }} → {{ $levelLabel($level) }}
                                    @if ($level === \App\Support\ReportSeverity::CRITICAL)
                                        <span class="font-medium">· stops the checks</span>
                                    @endif
                                </span>
                            @endforeach
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                Fixed it {{ number_format($fixes) }} {{ $fixes === 1 ? 'time' : 'times' }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-1.5">
                    <form method="POST" action="{{ route('admin.suggested-issues.checks.move', [$issue->issue_template_id, $check->issue_check_id]) }}">
                        @csrf
                        <input type="hidden" name="direction" value="up">
                        <button
                            type="submit"
                            data-tooltip="Move up"
                            aria-label="Move up"
                            @disabled($loop->first)
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <i data-lucide="arrow-up" class="h-3.5 w-3.5"></i>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.suggested-issues.checks.move', [$issue->issue_template_id, $check->issue_check_id]) }}">
                        @csrf
                        <input type="hidden" name="direction" value="down">
                        <button
                            type="submit"
                            data-tooltip="Move down"
                            aria-label="Move down"
                            @disabled($loop->last)
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <i data-lucide="arrow-down" class="h-3.5 w-3.5"></i>
                        </button>
                    </form>
                    <button
                        type="button"
                        data-tooltip="Edit check"
                        aria-label="Edit check"
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3] active:scale-95"
                        @click="openEdit({
                            id: '{{ $check->issue_check_id }}',
                            kind: @js($check->issue_check_kind),
                            question: @js($check->issue_check_question),
                            hint: @js($check->issue_check_hint ?? ''),
                            ifYes: @js($check->issue_check_severity_if_yes ?? ''),
                            ifNo: @js($check->issue_check_severity_if_no ?? '')
                        })"
                    >
                        <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>
                    </button>
                    <button
                        type="button"
                        data-tooltip="Remove check"
                        aria-label="Remove check"
                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-red-700 transition hover:bg-slate-50 active:scale-95"
                        @click="openDelete({ id: '{{ $check->issue_check_id }}', question: @js($check->issue_check_question) })"
                    >
                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="px-6 py-16 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400">
                        <i data-lucide="list-checks" class="h-5 w-5"></i>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-900">No quick checks yet</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Reporters who pick this issue go straight to the report. Add a check if there's something simple they can try first.
                    </p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Create / edit modal --}}
    <div
        x-show="formOpen"
        x-cloak
        @keydown.escape.window="closeForm()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div @click.self="closeForm()" class="absolute inset-0"></div>

        <div
            x-show="formOpen"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-3 scale-[0.98] opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
            class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-slate-950" x-text="editId ? 'Edit quick check' : 'Add quick check'"></h2>
                <button
                    type="button"
                    @click="closeForm()"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <form method="POST" :action="formAction()" @submit="submitting = true">
                @csrf
                <template x-if="editId">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="space-y-4 px-6 py-6">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Type</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label
                                class="cursor-pointer rounded-xl border px-3 py-2.5 text-sm transition"
                                :class="kind === 'troubleshoot' ? 'border-[#0025cc] bg-blue-50/60 ring-2 ring-blue-100' : 'border-slate-200 hover:bg-slate-50'"
                            >
                                <input type="radio" name="issue_check_kind" value="troubleshoot" x-model="kind" class="sr-only">
                                <span class="block font-semibold text-slate-900">Try this</span>
                                <span class="block text-xs text-slate-500">"That fixed it" / "Still not working"</span>
                            </label>
                            <label
                                class="cursor-pointer rounded-xl border px-3 py-2.5 text-sm transition"
                                :class="kind === 'question' ? 'border-[#0025cc] bg-blue-50/60 ring-2 ring-blue-100' : 'border-slate-200 hover:bg-slate-50'"
                            >
                                <input type="radio" name="issue_check_kind" value="question" x-model="kind" class="sr-only">
                                <span class="block font-semibold text-slate-900">Yes / No question</span>
                                <span class="block text-xs text-slate-500">The answer can set the priority</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Question <span class="text-red-500">*</span></label>
                        <input
                            name="issue_check_question"
                            x-model="question"
                            required
                            maxlength="255"
                            :placeholder="kind === 'question' ? 'Is there another computer you can use for now?' : 'Is the monitor turned on?'"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Hint</label>
                        <input
                            name="issue_check_hint"
                            x-model="hint"
                            maxlength="255"
                            placeholder="Press the monitor's power button. A small light should glow."
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                        <p class="mt-1.5 text-xs text-slate-400">Optional. Shown under the question to explain how.</p>
                    </div>

                    <div x-show="kind === 'question'" class="grid grid-cols-2 gap-3">
                        @foreach (['issue_check_severity_if_yes' => ['Priority if Yes', 'ifYes'], 'issue_check_severity_if_no' => ['Priority if No', 'ifNo']] as $field => [$label, $model])
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">{{ $label }}</label>
                                <select
                                    name="{{ $field }}"
                                    x-model="{{ $model }}"
                                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                                >
                                    <option value="">No change</option>
                                    @foreach ($levels as $level)
                                        <option value="{{ $level }}">{{ $level }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                        <p class="col-span-2 text-xs text-slate-400">
                            Critical means a safety hazard: the checks stop and the reporter is told to report it right away.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button
                        type="button"
                        @click="closeForm()"
                        class="inline-flex h-10 items-center justify-center px-2 text-sm font-medium text-slate-600 transition hover:text-slate-950"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="inline-flex h-10 min-w-32 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span x-text="submitting ? 'Saving...' : (editId ? 'Save changes' : 'Add check')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete modal --}}
    <div
        x-show="deleteOpen"
        x-cloak
        @keydown.escape.window="deleteOpen = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div @click.self="deleteOpen = false" class="absolute inset-0"></div>

        <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="px-6 pb-5 pt-6">
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                </div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-950">Remove quick check?</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Reporters will no longer see
                    <strong class="font-semibold text-slate-800" x-text="deleteQuestion"></strong>.
                    Answers already saved on reports stay as they are.
                </p>
            </div>

            <form method="POST" :action="baseUrl + '/' + deleteId" @submit="submitting = true">
                @csrf
                @method('DELETE')
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button
                        type="button"
                        @click="deleteOpen = false"
                        class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-red-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span x-text="submitting ? 'Removing...' : 'Remove'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function issueChecksPage() {
        return {
            baseUrl: @js(route('admin.suggested-issues.checks.index', $issue->issue_template_id)),
            formOpen: false,
            deleteOpen: false,
            submitting: false,
            editId: '',
            kind: 'troubleshoot',
            question: '',
            hint: '',
            ifYes: '',
            ifNo: '',
            deleteId: '',
            deleteQuestion: '',

            formAction() {
                return this.editId ? this.baseUrl + '/' + this.editId : this.baseUrl;
            },

            openCreate() {
                this.editId = '';
                this.kind = 'troubleshoot';
                this.question = '';
                this.hint = '';
                this.ifYes = '';
                this.ifNo = '';
                this.submitting = false;
                this.formOpen = true;
                this.$nextTick(() => window.lucide && window.lucide.createIcons());
            },

            openEdit(check) {
                this.editId = check.id;
                this.kind = check.kind || 'troubleshoot';
                this.question = check.question;
                this.hint = check.hint || '';
                this.ifYes = check.ifYes || '';
                this.ifNo = check.ifNo || '';
                this.submitting = false;
                this.formOpen = true;
                this.$nextTick(() => window.lucide && window.lucide.createIcons());
            },

            closeForm() {
                this.formOpen = false;
                this.submitting = false;
            },

            openDelete(check) {
                this.deleteId = check.id;
                this.deleteQuestion = check.question;
                this.submitting = false;
                this.deleteOpen = true;
                this.$nextTick(() => window.lucide && window.lucide.createIcons());
            },
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endsection
