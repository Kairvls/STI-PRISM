{{-- RIS row/card 3-dot action menu — teleports to body so it floats above the table --}}
@php
    $risId = $risId ?? ($ris->ris_id ?? null);
    $btnSizeClass = $btnSizeClass ?? 'h-8 w-8';
@endphp
@if ($risId)
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
            <button
                type="button"
                role="menuitem"
                @click="runAction(() => openDirectApproveModal('{{ $risId }}', 'forward'))"
                title="Forward this RIS to the President (no Issued by signature required)"
                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
            >
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
                Forward RIS to President
            </button>

            <button
                type="button"
                role="menuitem"
                @click="runAction(() => openDirectApproveModal('{{ $risId }}', 'direct'))"
                title="Sign Issued by and mark this RIS as Admin Approved for the Purchaser"
                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
            >
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Approve Directly
            </button>

            <div class="my-1 border-t border-slate-100"></div>

            <button
                type="button"
                role="menuitem"
                @click="runAction(() => openAmendModal('{{ $risId }}'))"
                title="Return this RIS to the Purchaser for revision (no signature)"
                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-amber-700 transition hover:bg-amber-50 hover:text-amber-900"
            >
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Return for Revision
            </button>
        </div>
    </template>
</div>
@endif
