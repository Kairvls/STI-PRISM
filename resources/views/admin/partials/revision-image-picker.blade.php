{{--
  Optional proof images (max 3) for revision / return remarks (RIS, ATP, PO, RFC, LIQ, RR).
  Expects: $pickerId (unique per form). The parent <form> must use enctype="multipart/form-data".
--}}
@php
    $max = \App\Support\RisRevisionImages::MAX;
    $field = \App\Support\RisRevisionImages::FIELD;
@endphp
<div class="revision-image-picker" id="{{ $pickerId }}" data-max="{{ $max }}" data-max-bytes="{{ \App\Support\RisRevisionImages::MAX_KB * 1024 }}">
    <div class="flex items-baseline justify-between gap-2">
        <label class="block text-sm font-medium text-slate-700">
            Proof images <span class="font-normal text-slate-400">(optional)</span>
        </label>
        <span class="text-[11px] font-medium text-slate-400" data-rip-count>0 / {{ $max }}</span>
    </div>
    <input
        type="file"
        name="{{ $field }}[]"
        accept="image/png,image/jpeg,image/webp"
        multiple
        class="hidden"
        data-rip-input
    >
    <div class="mt-1.5 grid grid-cols-3 gap-2" data-rip-grid></div>
    <p class="mt-1.5 text-xs text-slate-400">
        Up to {{ $max }} images (JPG, PNG or WEBP, 5 MB each) to show exactly what needs to be revised.
    </p>
    <p class="mt-1 hidden text-xs font-medium text-rose-600" data-rip-error></p>
</div>

@once
<script>
    (function () {
        function escapeAttr(value) {
            return String(value || '').replace(/[&<>"']/g, function (ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        }

        function state(root) {
            if (!root._ripFiles) root._ripFiles = [];
            return root._ripFiles;
        }

        function showError(root, message) {
            var el = root.querySelector('[data-rip-error]');
            if (!el) return;
            el.textContent = message || '';
            el.classList.toggle('hidden', !message);
        }

        function sync(root) {
            var input = root.querySelector('[data-rip-input]');
            var files = state(root);
            if (input && typeof DataTransfer !== 'undefined') {
                var dt = new DataTransfer();
                files.forEach(function (file) { dt.items.add(file); });
                input.files = dt.files;
            }
            render(root);
        }

        function render(root) {
            var grid = root.querySelector('[data-rip-grid]');
            var count = root.querySelector('[data-rip-count]');
            var max = parseInt(root.getAttribute('data-max'), 10) || 3;
            var files = state(root);
            if (!grid) return;

            (root._ripUrls || []).forEach(function (url) { URL.revokeObjectURL(url); });
            root._ripUrls = [];

            var html = '';
            files.forEach(function (file, index) {
                var url = URL.createObjectURL(file);
                root._ripUrls.push(url);
                html += '<div class="group relative aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-50">'
                    + '<img src="' + url + '" alt="' + escapeAttr(file.name) + '" class="h-full w-full object-cover">'
                    + '<button type="button" data-rip-remove="' + index + '" class="absolute right-1 top-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-900/70 text-white transition hover:bg-rose-600" title="Remove image" aria-label="Remove image">'
                    + '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>'
                    + '</button>'
                    + '<span class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-slate-900/70 to-transparent px-2 pb-1 pt-4 text-[10px] font-medium text-white">' + escapeAttr(file.name) + '</span>'
                    + '</div>';
            });
            if (files.length < max) {
                html += '<button type="button" data-rip-add class="flex aspect-square flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-slate-300 bg-white text-slate-500 transition hover:border-slate-400 hover:bg-slate-50 hover:text-slate-700">'
                    + '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2l1.6-1.6a2 2 0 012.8 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>'
                    + '<span class="text-[11px] font-medium">Add image</span>'
                    + '</button>';
            }
            grid.innerHTML = html;
            if (count) count.textContent = files.length + ' / ' + max;
        }

        function addFiles(root, list) {
            var max = parseInt(root.getAttribute('data-max'), 10) || 3;
            var maxBytes = parseInt(root.getAttribute('data-max-bytes'), 10) || (5 * 1024 * 1024);
            var files = state(root);
            var message = '';
            Array.prototype.forEach.call(list || [], function (file) {
                if (!/^image\/(png|jpe?g|webp)$/i.test(file.type || '')) {
                    message = 'Only JPG, PNG or WEBP images can be attached.';
                    return;
                }
                if (file.size > maxBytes) {
                    message = '"' + file.name + '" is larger than 5 MB.';
                    return;
                }
                if (files.length >= max) {
                    message = 'You can attach up to ' + max + ' images only.';
                    return;
                }
                files.push(file);
            });
            showError(root, message);
            sync(root);
        }

        window.resetRevisionImagePicker = function (id) {
            var root = document.getElementById(id);
            if (!root) return;
            root._ripFiles = [];
            showError(root, '');
            sync(root);
        };

        document.addEventListener('click', function (event) {
            var root = event.target.closest('.revision-image-picker');
            if (!root) return;
            var remove = event.target.closest('[data-rip-remove]');
            if (remove) {
                event.preventDefault();
                state(root).splice(parseInt(remove.getAttribute('data-rip-remove'), 10), 1);
                showError(root, '');
                sync(root);
                return;
            }
            if (event.target.closest('[data-rip-add]')) {
                event.preventDefault();
                var input = root.querySelector('[data-rip-input]');
                if (input) input.click();
            }
        });

        document.addEventListener('change', function (event) {
            var input = event.target;
            if (!input.matches || !input.matches('[data-rip-input]')) return;
            var root = input.closest('.revision-image-picker');
            if (!root) return;
            // input.files now holds only the new picks; addFiles merges them into the kept list and re-syncs the input.
            addFiles(root, Array.prototype.slice.call(input.files || []));
        });

        function initAll() {
            Array.prototype.forEach.call(document.querySelectorAll('.revision-image-picker'), render);
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAll);
        } else {
            initAll();
        }
    })();
</script>
@endonce
