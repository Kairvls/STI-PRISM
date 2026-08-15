@php
    $modalId = $modalId ?? 'risPreviewModal';
    $iframeId = $iframeId ?? 'risPreviewIframe';
    $closeFn = $closeFn ?? 'closeRisPreviewModal';
    $printFn = $printFn ?? 'printRisPreview';
    $zIndex = $zIndex ?? '50';
@endphp

<div
    id="{{ $modalId }}"
    class="fixed inset-0 hidden overflow-y-auto bg-black/50 p-4 md:p-8"
    style="z-index: {{ $zIndex }};"
>
    <div
        class="flex min-h-full w-full justify-center"
        onclick="if (event.target === this) window.{{ $closeFn }}()"
    >
        <div class="my-auto w-full max-w-5xl rounded-xl bg-white shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Print RIS</h3>
                    <p class="mt-1 text-sm text-gray-500">Original Requisition and Issue Slip format.</p>
                </div>
                <button
                    type="button"
                    onclick="window.{{ $closeFn }}()"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                >
                    Close
                </button>
            </div>

            <div class="overflow-x-auto bg-gray-100 p-4 md:p-6">
                <iframe
                    id="{{ $iframeId }}"
                    class="mx-auto block bg-white"
                    style="width: 11in; height: 8.5in; max-width: 100%; border: 1px solid #e5e7eb;"
                    src="about:blank"
                    title="RIS Form Preview"
                ></iframe>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button
                    type="button"
                    onclick="window.{{ $closeFn }}()"
                    class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    onclick="window.{{ $printFn }}()"
                    class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-medium text-white hover:bg-gray-800"
                >
                    Print RIS
                </button>
            </div>
        </div>
    </div>
</div>
