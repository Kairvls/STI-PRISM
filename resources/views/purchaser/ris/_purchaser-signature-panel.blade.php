{{--
  Purchaser signature picker (saved / draw / upload).
  $sigPrefix: usually '' — Alpine nests stack parent page scope into Edit RIS,
  so savedSignatures / applyCreateSignature resolve the same as Create.
--}}
@php
    $sigPrefix = $sigPrefix ?? '';
@endphp
<div class="mt-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h4 class="text-sm font-semibold text-slate-900">Purchaser signature <span class="text-red-500">*</span></h4>
        </div>
        <div
            x-show="{{ $sigPrefix }}createSignaturePreview"
            x-cloak
            class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"
        >
            Signature added
        </div>
    </div>

    <div class="mt-3">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-medium text-slate-700">My saved signatures</p>
            <span
                class="text-[11px]"
                x-bind:class="{{ $sigPrefix }}savedSignatures.length >= {{ $sigPrefix }}maxSavedSignatures ? 'font-semibold text-amber-600' : 'text-slate-400'"
                x-text="{{ $sigPrefix }}savedSignatures.length + ' / ' + {{ $sigPrefix }}maxSavedSignatures + ' saved'"
            ></span>
        </div>
        <div class="mt-2 grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4">
            <template x-for="sig in {{ $sigPrefix }}savedSignatures" :key="sig.id">
                <div
                    class="group relative overflow-hidden rounded-2xl border bg-gradient-to-b from-white to-slate-50/90 p-2.5 shadow-sm shadow-slate-900/[0.03] transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md hover:shadow-slate-900/5"
                    x-bind:class="{{ $sigPrefix }}createSignaturePreview && sig.preview_url === {{ $sigPrefix }}createSignaturePreview ? 'border-[#0025cc] ring-2 ring-blue-100' : 'border-slate-200/90'"
                >
                    <button
                        type="button"
                        class="flex w-full flex-col items-center gap-2 rounded-xl px-1 py-1 text-left"
                        title="Use this signature"
                        x-on:click="{{ $sigPrefix }}applyCreateSignature(sig.preview_url)"
                    >
                        <span class="flex h-14 w-full items-center justify-center rounded-xl bg-white ring-1 ring-slate-100">
                            <img x-bind:src="sig.preview_url" alt="" class="max-h-10 w-auto max-w-[90%] object-contain">
                        </span>
                        <span class="w-full truncate text-center text-[11px] font-medium text-slate-600" x-text="sig.label || 'Signature'"></span>
                    </button>
                    <button
                        type="button"
                        class="absolute right-2 top-2 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white/95 text-slate-400 opacity-0 shadow-sm ring-1 ring-slate-200/80 transition hover:bg-rose-50 hover:text-rose-600 group-hover:opacity-100"
                        title="Remove from list"
                        aria-label="Remove saved signature"
                        x-on:click="{{ $sigPrefix }}openCreateDeleteSigModal(sig.id, sig.label || 'Signature')"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </template>
            <div
                x-show="!{{ $sigPrefix }}savedSignatures.length"
                class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-4 text-center text-xs text-slate-500"
            >
                No saved signatures yet. Upload or draw one below, then save it for next time.
            </div>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2">
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl bg-[#0025cc] px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800"
            x-on:click="{{ $sigPrefix }}openCreateSignModal(false)"
        >
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4l10.5-10.5a2.5 2.5 0 00-3.536-3.536L4 16.464V20z"></path>
            </svg>
            <span x-text="{{ $sigPrefix }}createSignaturePreview ? 'Redraw signature' : 'Draw signature'"></span>
        </button>
        <button
            type="button"
            x-show="{{ $sigPrefix }}createSignaturePreview"
            x-cloak
            class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
            x-on:click="{{ $sigPrefix }}clearCreateSignature()"
        >
            Clear signature
        </button>
        <button
            type="button"
            x-show="{{ $sigPrefix }}createSignaturePreview"
            x-cloak
            class="rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50"
            x-bind:disabled="{{ $sigPrefix }}savedSignatures.length >= {{ $sigPrefix }}maxSavedSignatures"
            x-bind:title="{{ $sigPrefix }}savedSignatures.length >= {{ $sigPrefix }}maxSavedSignatures ? 'Maximum of 4 saved signatures reached' : ''"
            x-on:click="{{ $sigPrefix }}openCreateNameSigModal()"
        >
            Save to my list
        </button>
    </div>

    <label class="relative mt-3 flex cursor-pointer items-center gap-3 overflow-hidden rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-3 transition hover:border-slate-300 hover:bg-slate-50">
        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-xs font-medium text-slate-700">Or upload a signature image</span>
            <span class="mt-0.5 block text-[11px] text-slate-400">Optional · PNG, JPG, or paper photo · dark ink on light / white bond paper · paper cleared automatically</span>
            <span x-show="{{ $sigPrefix }}createSigUploadName" x-cloak class="mt-1 block truncate text-[11px] font-medium text-slate-600" x-text="{{ $sigPrefix }}createSigUploadName"></span>
        </span>
        <span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-slate-600">
            Choose file
        </span>
        <input
            type="file"
            accept="image/*"
            class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
            data-ris-sig-upload
            @if(($sigRef ?? '') !== '')
                x-ref="{{ $sigRef }}"
            @endif
            x-on:change="{{ $sigPrefix }}onCreateSigUploadChange($event)"
        >
    </label>

    <label class="mt-3 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5">
        <input
            type="checkbox"
            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#0f172a] focus:ring-[#0f172a]"
            x-model="{{ $sigPrefix }}createSaveOnUpload"
            x-bind:disabled="{{ $sigPrefix }}savedSignatures.length >= {{ $sigPrefix }}maxSavedSignatures"
        >
        <span class="min-w-0">
            <span class="block text-xs font-medium text-slate-700">Save uploaded signature to my list</span>
            <span class="mt-0.5 block text-[11px] text-slate-400">Keeps up to 4 signatures for the next RIS.</span>
        </span>
    </label>
</div>
