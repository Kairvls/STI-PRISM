{{-- Shared maximize / minimize control for Purchaser document modals (RIS-style) --}}
<button
    type="button"
    @click="modalFullscreen = !modalFullscreen; $nextTick(() => window.lucide && window.lucide.createIcons())"
    class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
    :aria-label="modalFullscreen ? 'Exit full screen' : 'Full screen'"
    :title="modalFullscreen ? 'Exit full screen' : 'Full screen'"
>
    <i x-show="!modalFullscreen" data-lucide="maximize-2" class="h-4 w-4"></i>
    <i x-show="modalFullscreen" x-cloak data-lucide="minimize-2" class="h-4 w-4"></i>
</button>
