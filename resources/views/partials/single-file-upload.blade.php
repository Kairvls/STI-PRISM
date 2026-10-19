{{--
  Single-file supporting document uploader (create / edit / draft / revision).
  Feels like one attachment only, with clear Remove.

  @var string $name           file input name (e.g. attachments[] or or_attachment)
  @var string|null $accept
  @var string $title
  @var string $hint
  @var bool $required
  @var string $refKey         unique Alpine x-ref key
  @var iterable $existing     [{ id, name }] already saved files
  @var string $deleteName     checkbox name for marking existing files for deletion
  @var string|null $class     wrapper classes
--}}
@php
    $name = $name ?? 'attachments[]';
    $accept = $accept ?? '.pdf,.jpg,.jpeg,.png';
    $title = $title ?? 'Supporting Document';
    $hint = $hint ?? 'Optional · 1 file · PDF, JPG, PNG · max 5MB';
    $required = (bool) ($required ?? false);
    $refKey = $refKey ?? ('fileUpload'.uniqid());
    $existing = collect($existing ?? [])->values();
    $deleteName = $deleteName ?? 'delete_attachments[]';
    $class = $class ?? 'mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white';
    $uploadLabel = $uploadLabel ?? 'Upload file';
    $replaceLabel = $replaceLabel ?? 'Replace file';
    $helpLine = $helpLine ?? 'Choose PDF, JPG, or PNG';
@endphp

<div
    class="{{ $class }}"
    x-data="{
        fileName: '',
        removed: {},
        existingCount: {{ $existing->count() }},
        clearFile() {
            this.fileName = '';
            if (this.$refs['{{ $refKey }}']) this.$refs['{{ $refKey }}'].value = '';
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },
        onPick(e) {
            const f = e.target.files && e.target.files[0];
            this.fileName = f ? f.name : '';
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },
        markRemove(id) {
            this.removed = { ...this.removed, [id]: true };
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },
        visibleExisting() {
            return this.existingCount - Object.keys(this.removed).filter((k) => this.removed[k]).length;
        },
        showUpload() {
            return !this.fileName && this.visibleExisting() < 1;
        }
    }"
>
    <div class="flex items-center justify-between gap-3 px-3.5 py-2.5">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-950">
                {{ $title }}
                @if($required)<span class="text-red-500">*</span>@endif
            </p>
            <p class="truncate text-[11px] text-slate-500">{{ $hint }}</p>
        </div>
    </div>

    <div class="space-y-1.5 border-t border-slate-100 px-3.5 py-2.5">
        @foreach($existing as $file)
            @php
                $fileId = is_array($file) ? ($file['id'] ?? null) : ($file->id ?? null);
                $fileLabel = is_array($file) ? ($file['name'] ?? 'File') : ($file->name ?? 'File');
            @endphp
            @if($fileId)
                <div
                    class="flex items-center gap-2 rounded-lg bg-slate-50 px-1.5 py-1.5"
                    x-show="!removed[{{ (int) $fileId }}]"
                    x-cloak
                >
                    <input type="checkbox" name="{{ $deleteName }}" value="{{ $fileId }}" class="sr-only" :checked="!!removed[{{ (int) $fileId }}]">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white text-slate-500 ring-1 ring-slate-200/80">
                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                    </div>
                    <p class="min-w-0 flex-1 truncate text-xs font-medium text-slate-800">{{ $fileLabel }}</p>
                    <button
                        type="button"
                        class="shrink-0 text-[11px] font-medium text-slate-400 transition hover:text-slate-800"
                        x-on:click="markRemove({{ (int) $fileId }})"
                    >Remove</button>
                </div>
            @endif
        @endforeach

        <template x-if="fileName">
            <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-1.5 py-1.5">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white text-slate-500 ring-1 ring-slate-200/80">
                    <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                </div>
                <p class="min-w-0 flex-1 truncate text-xs font-medium text-slate-800" x-text="fileName"></p>
                <button
                    type="button"
                    class="shrink-0 text-[11px] font-medium text-slate-400 transition hover:text-slate-800"
                    x-on:click="clearFile()"
                >Remove</button>
            </div>
        </template>

        <label
            x-show="showUpload()"
            x-cloak
            class="group flex cursor-pointer items-center gap-2.5 rounded-lg border border-dashed border-slate-300 bg-slate-50/70 px-2.5 py-2.5 transition hover:border-slate-400 hover:bg-slate-50"
        >
            <input
                type="file"
                name="{{ $name }}"
                accept="{{ $accept }}"
                class="sr-only"
                x-ref="{{ $refKey }}"
                x-on:change="onPick($event)"
                @if($required) data-single-file-required="1" @endif
            >
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-dashed border-slate-300 bg-white text-slate-500 transition group-hover:border-slate-400 group-hover:text-slate-800">
                <i data-lucide="upload" class="h-3.5 w-3.5"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-medium text-slate-800">{{ $uploadLabel }}</p>
                <p class="truncate text-[10px] text-slate-500">{{ $helpLine }}</p>
            </div>
        </label>

    </div>
</div>
