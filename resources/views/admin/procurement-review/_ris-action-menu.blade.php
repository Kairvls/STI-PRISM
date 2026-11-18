{{-- RIS row/card 3-dot action menu — teleports to body so it floats above the table --}}
@php
    $risId = $risId ?? ($ris->ris_id ?? null);
    $btnSizeClass = $btnSizeClass ?? 'h-8 w-8';
    $mode = $mode ?? 'sign'; // sign | pending
    $acceptRef = $acceptRef ?? null;
    $acceptDetail = $acceptDetail ?? null;
    $shareLabel = $shareLabel
        ?? \App\Support\RequestExplorer::shareUiLabel(
            'ris',
            isset($ris) ? ($ris->ris_form_number ?? null) : null,
            isset($ris) ? ($ris->ris_request_no ?? null) : null
        );
@endphp
@if ($risId && \App\Support\AdminPortal::canActOnRis())
    <div
    class="relative inline-block"
    x-data="risActionMenu()"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        x-ref="trigger"
        @click="toggle()"
        class="inline-flex {{ $btnSizeClass }} items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
        title="More actions"
        aria-label="More actions"
        :aria-expanded="open.toString()"
    >
        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="6" cy="12" r="1.75"></circle>
            <circle cx="12" cy="12" r="1.75"></circle>
            <circle cx="18" cy="12" r="1.75"></circle>
        </svg>
    </button>

    <template x-teleport="body">
        <div
            x-ref="menu"
            x-show="open"
            x-cloak
            x-transition.opacity.duration.100ms
            @click.outside="onOutside($event)"
            class="fixed z-[9999] w-56 overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg shadow-slate-900/10"
            role="menu"
        >
            @if ($mode === 'pending')
                {{-- Accept / Return are icon buttons; menu keeps Share + Reject. --}}
                <button
                    type="button"
                    role="menuitem"
                    data-share-doc-type="ris"
                    data-share-doc-id="{{ $risId }}"
                    data-share-doc-label="{{ $shareLabel }}"
                    @click="open = false"
                    title="Share to Messages"
                    class="js-share-document flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                >
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                    </svg>
                    Share to Messages
                </button>

                <div class="my-1 border-t border-slate-100"></div>

                <button
                    type="button"
                    role="menuitem"
                    @click="runAction(() => openFinalRejectModal('{{ $risId }}'))"
                    title="Hard reject this RIS with remarks and images"
                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-rose-700 transition hover:bg-rose-50 hover:text-rose-900"
                >
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Reject
                </button>
            @else
                {{-- Accepted RIS: Share only. Forward / Approve Directly are icon buttons; revision/reject are not available after accept. --}}
                <button
                    type="button"
                    role="menuitem"
                    data-share-doc-type="ris"
                    data-share-doc-id="{{ $risId }}"
                    data-share-doc-label="{{ $shareLabel }}"
                    @click="open = false"
                    title="Share to Messages"
                    class="js-share-document flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                >
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                    </svg>
                    Share to Messages
                </button>
            @endif
        </div>
    </template>
</div>
@endif
