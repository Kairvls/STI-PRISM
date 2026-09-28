{{-- Full-size viewer for RIS revision proof images (see partials.ris-revision-images). Include once per page. --}}
<div id="risRevisionImageViewer" class="fixed inset-0 hidden items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm" style="z-index: 13000;" onclick="if (event.target === this) window.closeRisRevisionImage()">
    <div class="relative flex max-h-full w-full max-w-4xl flex-col items-center">
        <div class="mb-2 flex w-full items-center justify-between gap-3 text-white">
            <p id="risRevisionImageName" class="truncate text-sm font-medium"></p>
            <div class="flex shrink-0 items-center gap-2">
                <a id="risRevisionImageOpen" href="#" target="_blank" rel="noopener" class="rounded-lg bg-white/10 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-white/20">Open full size</a>
                <button type="button" onclick="window.closeRisRevisionImage()" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 text-white transition hover:bg-white/20" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
        <img id="risRevisionImageFull" src="" alt="" class="max-h-[80vh] w-auto max-w-full rounded-xl bg-white object-contain shadow-2xl">
    </div>
</div>
<script>
    (function () {
        var viewer = document.getElementById('risRevisionImageViewer');
        if (viewer && viewer.parentElement !== document.body) {
            document.body.appendChild(viewer);
        }

        window.openRisRevisionImage = function (trigger) {
            var box = document.getElementById('risRevisionImageViewer');
            if (!box || !trigger) return;
            var src = trigger.getAttribute('href') || '';
            var name = trigger.getAttribute('data-name') || '';
            var full = document.getElementById('risRevisionImageFull');
            full.src = src;
            full.alt = name;
            document.getElementById('risRevisionImageName').textContent = name;
            document.getElementById('risRevisionImageOpen').href = src;
            box.classList.remove('hidden');
            box.classList.add('flex');
        };

        window.closeRisRevisionImage = function () {
            var box = document.getElementById('risRevisionImageViewer');
            if (!box) return;
            box.classList.add('hidden');
            box.classList.remove('flex');
            document.getElementById('risRevisionImageFull').src = '';
        };

        document.addEventListener('keydown', function (event) {
            var box = document.getElementById('risRevisionImageViewer');
            if (event.key === 'Escape' && box && !box.classList.contains('hidden')) {
                event.stopImmediatePropagation();
                window.closeRisRevisionImage();
            }
        }, true);
    })();
</script>
