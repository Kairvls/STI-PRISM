@php
    $inputId = $inputId ?? 'supplier-logo-input';
    $existingUrl = \App\Support\SupplierLogo::url($existingPath ?? null);
    $canRemove = (bool) ($canRemove ?? false);
@endphp
<div
    class="sm:col-span-2"
    x-data="{
        preview: @js($existingUrl),
        hadExisting: @js((bool) $existingUrl),
        onPick(event) {
            const file = event.target.files && event.target.files[0];
            if (!file) {
                this.preview = this.hadExisting ? @js($existingUrl) : null;
                this.$nextTick(() => window.lucide && window.lucide.createIcons());
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => { this.preview = e.target.result; };
            reader.readAsDataURL(file);
            const remove = this.$refs.removeLogo;
            if (remove) remove.checked = false;
        },
        clearPick() {
            const input = this.$refs.logoInput;
            if (input) input.value = '';
            this.preview = null;
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        }
    }"
>
    <label class="mb-1.5 block text-xs font-medium text-gray-600">Brand logo</label>
    <div class="flex flex-wrap items-center gap-4 rounded-xl border border-dashed border-gray-200 bg-gray-50/70 p-3">
        <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white">
            <template x-if="preview">
                <img :src="preview" alt="Logo preview" class="h-full w-full object-contain p-1">
            </template>
            <template x-if="!preview">
                <span class="text-gray-400">
                    <i data-lucide="building-2" class="h-6 w-6"></i>
                </span>
            </template>
        </div>
        <div class="min-w-0 flex-1">
            <input
                type="file"
                name="supplier_logo"
                id="{{ $inputId }}"
                x-ref="logoInput"
                accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
                x-on:change="onPick($event)"
                class="block w-full text-xs text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-[#0025cc] file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-[#001db3]"
            >
            <p class="mt-1.5 text-[11px] text-slate-400">PNG, JPG, WEBP or GIF · max 2 MB. Leave empty to keep the store icon.</p>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    x-show="preview && !hadExisting"
                    x-cloak
                    x-on:click="clearPick()"
                    class="text-[11px] font-semibold text-slate-500 hover:text-slate-800"
                >
                    Clear selected image
                </button>
                @if($canRemove)
                    <label class="inline-flex items-center gap-1.5 text-[11px] font-medium text-rose-700">
                        <input
                            type="checkbox"
                            name="remove_supplier_logo"
                            value="1"
                            x-ref="removeLogo"
                            x-on:change="if ($event.target.checked) { clearPick(); hadExisting = false; }"
                            class="rounded border-rose-300 text-rose-600 focus:ring-rose-500"
                        >
                        Remove current logo
                    </label>
                @endif
            </div>
        </div>
    </div>
</div>
