<div
    id="equipmentPhotoViewer"
    class="fixed inset-0 z-[120] hidden items-center justify-center overflow-auto bg-[#0b1220]/85 p-6 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
    aria-labelledby="equipmentPhotoViewerTitle"
    aria-hidden="true"
    onclick="closeEquipmentPhotoViewer()"
>
    <div class="flex min-h-full w-full flex-col items-center justify-center gap-4">
        <div
            id="equipmentPhotoViewerFrame"
            class="relative inline-block w-max"
            onclick="event.stopPropagation()"
        >
            <p id="equipmentPhotoViewerTitle" class="sr-only">Equipment photo</p>
            <img
                id="equipmentPhotoViewerImage"
                src=""
                alt="Equipment photo"
                class="block max-h-[75vh] max-w-[min(90vw,56rem)] rounded-2xl bg-white object-contain shadow-[0_24px_80px_rgba(15,23,42,0.45)]"
            >
            <button
                type="button"
                onclick="closeEquipmentPhotoViewer()"
                class="absolute right-0 top-0 z-10 flex h-10 w-10 -translate-y-1/2 translate-x-1/2 items-center justify-center rounded-full bg-white text-slate-700 shadow-lg transition hover:bg-slate-100"
                aria-label="Close photo"
            >
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div
            class="flex shrink-0 items-center gap-1 rounded-full border border-white/20 bg-white/95 p-1 shadow-lg"
            onclick="event.stopPropagation()"
        >
            <button
                type="button"
                onclick="zoomEquipmentPhoto(-0.25)"
                class="flex h-9 w-9 items-center justify-center rounded-full text-slate-700 transition hover:bg-slate-100"
                aria-label="Zoom out"
            >
                <i data-lucide="zoom-out" class="h-4 w-4"></i>
            </button>
            <span id="equipmentPhotoZoomLabel" class="min-w-[3.5rem] text-center text-xs font-semibold tabular-nums text-slate-700">
                100%
            </span>
            <button
                type="button"
                onclick="zoomEquipmentPhoto(0.25)"
                class="flex h-9 w-9 items-center justify-center rounded-full text-slate-700 transition hover:bg-slate-100"
                aria-label="Zoom in"
            >
                <i data-lucide="zoom-in" class="h-4 w-4"></i>
            </button>
            <span class="mx-0.5 h-5 w-px bg-slate-200"></span>
            <button
                type="button"
                onclick="resetEquipmentPhotoZoom()"
                class="inline-flex h-9 items-center gap-1.5 rounded-full px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                aria-label="Reset zoom"
            >
                <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                Reset
            </button>
        </div>
    </div>
</div>

<script>
    if (typeof window.openEquipmentPhotoViewer !== 'function') {
        window.equipmentPhotoZoom = 1;

        function applyEquipmentPhotoZoom() {
            const image = document.getElementById('equipmentPhotoViewerImage');
            const label = document.getElementById('equipmentPhotoZoomLabel');
            const scale = window.equipmentPhotoZoom || 1;

            if (image) {
                image.style.maxHeight = (75 * scale) + 'vh';
                image.style.maxWidth = 'min(' + (90 * scale) + 'vw, ' + (56 * scale) + 'rem)';
            }

            if (label) {
                label.textContent = Math.round(scale * 100) + '%';
            }
        }

        window.zoomEquipmentPhoto = function (delta) {
            const next = Math.round(((window.equipmentPhotoZoom || 1) + delta) * 100) / 100;
            window.equipmentPhotoZoom = Math.min(4, Math.max(0.5, next));
            applyEquipmentPhotoZoom();
        };

        window.resetEquipmentPhotoZoom = function () {
            window.equipmentPhotoZoom = 1;
            applyEquipmentPhotoZoom();
        };

        window.openEquipmentPhotoViewer = function (src, alt) {
            if (!src) {
                return;
            }

            const viewer = document.getElementById('equipmentPhotoViewer');
            const image = document.getElementById('equipmentPhotoViewerImage');
            const title = document.getElementById('equipmentPhotoViewerTitle');

            if (!viewer || !image) {
                return;
            }

            image.src = src;
            image.alt = alt || 'Equipment photo';
            if (title) {
                title.textContent = alt || 'Equipment photo';
            }

            window.resetEquipmentPhotoZoom();

            viewer.classList.remove('hidden');
            viewer.classList.add('flex');
            viewer.setAttribute('aria-hidden', 'false');

            if (window.lucide) {
                window.lucide.createIcons();
            }
        };

        window.closeEquipmentPhotoViewer = function () {
            const viewer = document.getElementById('equipmentPhotoViewer');
            const image = document.getElementById('equipmentPhotoViewerImage');

            if (!viewer) {
                return;
            }

            viewer.classList.add('hidden');
            viewer.classList.remove('flex');
            viewer.setAttribute('aria-hidden', 'true');
            window.resetEquipmentPhotoZoom();

            if (image) {
                image.src = '';
            }
        };

        document.addEventListener('keydown', function (event) {
            const viewer = document.getElementById('equipmentPhotoViewer');
            if (!viewer || !viewer.classList.contains('flex')) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopImmediatePropagation();
                window.closeEquipmentPhotoViewer();
                return;
            }

            if (event.key === '+' || event.key === '=') {
                event.preventDefault();
                window.zoomEquipmentPhoto(0.25);
            } else if (event.key === '-' || event.key === '_') {
                event.preventDefault();
                window.zoomEquipmentPhoto(-0.25);
            } else if (event.key === '0') {
                event.preventDefault();
                window.resetEquipmentPhotoZoom();
            }
        }, true);
    }
</script>
