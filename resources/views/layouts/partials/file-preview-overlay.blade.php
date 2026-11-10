{{-- In-page file preview (Canvas / attachments) — no new tab --}}
<div
    id="filePreviewOverlay"
    class="fixed inset-0 z-[10050] hidden items-stretch justify-center bg-[#0b1220]/65 p-3 sm:p-6"
    aria-hidden="true"
>
    <div class="flex min-h-0 w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
            <p id="filePreviewTitle" class="min-w-0 truncate text-sm font-semibold text-slate-900">File preview</p>
            <div class="flex shrink-0 items-center gap-1.5">
                <a
                    id="filePreviewOpenTab"
                    href="#"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                >Open in tab</a>
                <button
                    type="button"
                    id="filePreviewClose"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close preview"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        </div>
        <div class="min-h-0 flex-1 bg-slate-100">
            <iframe
                id="filePreviewFrame"
                title="File preview"
                class="h-full min-h-[70vh] w-full border-0 bg-white"
            ></iframe>
        </div>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById('filePreviewOverlay');
    const frame = document.getElementById('filePreviewFrame');
    const titleEl = document.getElementById('filePreviewTitle');
    const openTab = document.getElementById('filePreviewOpenTab');
    const closeBtn = document.getElementById('filePreviewClose');
    if (!overlay || !frame) return;

    function closeFilePreview() {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
        overlay.setAttribute('aria-hidden', 'true');
        frame.removeAttribute('src');
        document.body.style.overflow = '';
    }

    function openFilePreview(url, title) {
        if (!url) return;
        titleEl.textContent = title || 'File preview';
        openTab.href = url;
        frame.src = url;
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons({ nodes: [overlay] });
        }
    }

    window.openFilePreviewOverlay = openFilePreview;
    window.closeFilePreviewOverlay = closeFilePreview;

    closeBtn?.addEventListener('click', closeFilePreview);
    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) closeFilePreview();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !overlay.classList.contains('hidden')) {
            closeFilePreview();
        }
    });

    document.addEventListener('click', function (event) {
        const link = event.target && event.target.closest
            ? event.target.closest('[data-file-preview]')
            : null;
        if (!link) return;

        const href = link.getAttribute('href') || link.getAttribute('data-file-preview-url') || '';
        if (!href || href === '#') return;

        event.preventDefault();
        openFilePreview(
            href,
            link.getAttribute('data-file-preview-title')
                || link.getAttribute('title')
                || link.textContent.trim()
                || 'File preview'
        );
    });
})();
</script>
