{{--
    Draft handover UI for one document type (ris | atp | po):
    incoming "passed to you" panel + the "Pass to co-worker" dialog.
    Open the dialog with: window.dispatchEvent(new CustomEvent('open-draft-handover', { detail: { type, id, label } }))
--}}
@php
    $handoverPortal = 'purchaser';
    $handoverIncoming = \App\Support\DraftHandover::incoming($type);
    $handoverCoworkers = \App\Support\DraftHandover::coworkers();
    $handoverTypeLabel = \App\Support\DraftHandover::typeLabel($type);
    $handoverDeclinedNotices = \App\Support\DraftHandover::declinedForSender($type);
@endphp

@if(\App\Support\DraftHandover::enabled())
    @if($handoverDeclinedNotices->isNotEmpty())
        <div class="mb-6 space-y-2">
            @foreach($handoverDeclinedNotices as $declined)
                <div class="flex flex-wrap items-start justify-between gap-3 rounded-2xl border border-rose-200 bg-rose-50/70 px-4 py-3">
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                            <i data-lucide="user-x" class="h-4 w-4"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm text-slate-800">
                                <span class="font-semibold text-slate-900">{{ $declined->to_name ?: 'Your co-worker' }}</span>
                                declined <span class="font-semibold text-slate-900">{{ $declined->document_label }}</span>.
                                It stays in your drafts.
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ \Carbon\Carbon::parse($declined->handover_responded_at)->diffForHumans() }}</p>
                            @if(filled($declined->handover_response_note))
                                <p class="mt-2 rounded-lg bg-white px-3 py-2 text-xs text-slate-700">Reason: “{{ $declined->handover_response_note }}”</p>
                            @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route($handoverPortal.'.handovers.dismiss', $declined->handover_id) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">Dismiss</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    @if($handoverIncoming->isNotEmpty())
        <div
            class="mb-6 rounded-2xl border border-blue-200 bg-blue-50/60 p-4"
            x-data="{ previewUrl: null, previewTitle: '', previewAccept: '' }"
        >
            <div class="mb-3 flex items-center gap-2">
                <i data-lucide="inbox" class="h-4 w-4 text-[#0025cc]"></i>
                <p class="text-sm font-semibold text-slate-900">
                    {{ $handoverTypeLabel }} drafts passed to you
                    <span class="ml-1 rounded-full bg-[#0025cc] px-1.5 py-0.5 text-[11px] font-semibold text-white">{{ $handoverIncoming->count() }}</span>
                </p>
            </div>
            <div class="space-y-2">
                @foreach($handoverIncoming as $handover)
                    <div class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-blue-100 bg-white px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-900">{{ $handover->document_label }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                From <span class="font-medium text-slate-700">{{ $handover->from_name ?: 'a co-worker' }}</span>
                                · {{ \Carbon\Carbon::parse($handover->handover_created_at)->diffForHumans() }}
                            </p>
                            @if(filled($handover->document_summary))
                                <p class="mt-1 text-xs text-slate-600">{{ $handover->document_summary }}</p>
                            @endif
                            @if(filled($handover->handover_note))
                                <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-700">“{{ $handover->handover_note }}”</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 items-start gap-2">
                            <button
                                type="button"
                                x-on:click="previewTitle = @js($handover->document_label); previewAccept = @js(route($handoverPortal.'.handovers.accept', $handover->handover_id)); previewUrl = @js(route($handoverPortal.'.handovers.preview', $handover->handover_id))"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                View draft
                            </button>
                            <details class="group relative">
                                <summary class="inline-flex cursor-pointer list-none items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
                                    Decline
                                </summary>
                                <form
                                    method="POST"
                                    action="{{ route($handoverPortal.'.handovers.decline', $handover->handover_id) }}"
                                    class="absolute right-0 z-30 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-3 shadow-lg"
                                >
                                    @csrf
                                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Reason (optional)</label>
                                    <textarea name="reason" rows="2" maxlength="500" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-slate-300" placeholder="e.g. I'm on leave this week"></textarea>
                                    <button type="submit" class="mt-2 w-full rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-700">Decline draft</button>
                                </form>
                            </details>
                            <form method="POST" action="{{ route($handoverPortal.'.handovers.accept', $handover->handover_id) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800">
                                    <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                    Accept
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <template x-teleport="body">
                <div
                    x-show="previewUrl"
                    x-cloak
                    x-transition.opacity
                    x-on:keydown.escape.window="previewUrl = null"
                    x-on:click.self="previewUrl = null"
                    class="fixed inset-0 z-[1200] flex items-center justify-center bg-black/50 p-3 sm:p-6"
                >
                    <div class="flex h-full max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold text-gray-950" x-text="previewTitle"></p>
                                <p class="text-xs text-gray-500">Read-only preview of the draft passed to you</p>
                            </div>
                            <button type="button" x-on:click="previewUrl = null" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                        <iframe x-bind:src="previewUrl || 'about:blank'" class="min-h-0 w-full flex-1 bg-slate-200" title="Draft preview"></iframe>
                        <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-3">
                            <button type="button" x-on:click="previewUrl = null" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">Close</button>
                            <form method="POST" x-bind:action="previewAccept">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-[#0025cc] px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-800">
                                    <i data-lucide="check" class="h-4 w-4"></i>
                                    Accept draft
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @endif

    <div
        x-data="{ open: false, type: '', id: 0, label: '' }"
        x-on:open-draft-handover.window="type = $event.detail.type; id = $event.detail.id; label = $event.detail.label; open = true"
    >
        <template x-teleport="body">
            <div
                x-show="open"
                x-cloak
                x-transition.opacity
                x-on:keydown.escape.window="open = false"
                x-on:click.self="open = false"
                class="fixed inset-0 z-[1200] flex items-center justify-center bg-black/50 p-4"
            >
                <form
                    method="POST"
                    action="{{ route($handoverPortal.'.handovers.store') }}"
                    class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl"
                    role="dialog"
                    aria-modal="true"
                >
                    @csrf
                    <input type="hidden" name="document_type" x-bind:value="type">
                    <input type="hidden" name="document_id" x-bind:value="id">

                    <div class="flex items-start gap-3 border-b border-gray-100 px-5 py-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                            <i data-lucide="user-round-plus" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold tracking-tight text-gray-950">Pass to co-worker</h3>
                            <p class="mt-0.5 truncate text-sm text-gray-500" x-text="label"></p>
                        </div>
                    </div>

                    <div class="space-y-4 px-5 py-5">
                        @if($handoverCoworkers === [])
                            <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                There are no other users with the Purchaser role to pass this draft to.
                            </p>
                        @else
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Co-worker <span class="text-red-500">*</span></label>
                                <select name="to_user_id" required class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-800 outline-none transition focus:border-gray-300">
                                    <option value="">Select a purchaser…</option>
                                    @foreach($handoverCoworkers as $coworker)
                                        <option value="{{ $coworker['id'] }}">{{ $coworker['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Note (optional)</label>
                                <textarea name="note" rows="3" maxlength="500" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 outline-none transition focus:border-gray-300" placeholder="e.g. Please finish the supplier and prices"></textarea>
                            </div>
                        @endif
                        <p class="text-xs leading-5 text-gray-500">
                            The draft stays yours until they accept it. Once accepted it moves to their drafts, and they sign it themselves before submitting.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                        <button type="button" x-on:click="open = false" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">Cancel</button>
                        @if($handoverCoworkers !== [])
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800">
                                <i data-lucide="send" class="h-4 w-4"></i>
                                Send for acceptance
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </template>
    </div>
@endif
