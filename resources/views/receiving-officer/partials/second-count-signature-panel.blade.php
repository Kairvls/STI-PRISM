{{-- Admin-style signature panel for Receiving Officer Second Count --}}
@php
    $savedSignatures = $savedSignatures ?? collect();
    $signTitle = $signTitle ?? 'Second Count signature';
    $signHint = $signHint ?? 'Pick a saved signature, draw one, or upload. It overlays Second Count on the form.';
    $padTitle = $padTitle ?? 'Sign Second Count';
    $padHint = $padHint ?? 'Sign clearly. This overlays your printed name on Second Count.';
@endphp

<div id="roSignaturePanel" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h4 class="text-sm font-semibold text-slate-900">{{ $signTitle }}</h4>
            <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $signHint }}</p>
        </div>
        <div id="roSignBadge" class="hidden inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
            Signature added
        </div>
    </div>

    <div class="mt-3">
        <div class="relative mx-auto flex min-h-[4.5rem] w-full max-w-sm items-end justify-center border-b border-slate-800 px-2 pb-1">
            <img
                id="roSigPreview"
                alt="Signature preview"
                class="pointer-events-none absolute bottom-2 left-1/2 max-h-12 w-auto max-w-[90%] -translate-x-1/2 object-contain"
                style="display:none;"
            >
            <span id="roSigPrintedName" class="relative z-[1] text-center text-xs font-medium text-slate-800">
                {{ auth()->user()->user_full_name ?? auth()->user()->user_username ?? 'Receiving Officer' }}
            </span>
        </div>
        <p class="mt-1 text-center text-[10px] uppercase tracking-wide text-slate-400">Preview · signature overlays printed name</p>
    </div>

    <div class="mt-3">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-medium text-slate-700">My saved signatures</p>
            <span id="roSavedCount" class="text-[11px] text-slate-400">{{ $savedSignatures->count() }} / 4 saved</span>
        </div>
        <div id="roSavedList" class="mt-2 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
            @forelse ($savedSignatures as $saved)
                <div class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/90 p-2.5 shadow-sm" data-saved-sig-id="{{ $saved->user_signature_id }}">
                    <button type="button" class="ro-use-saved flex w-full flex-col items-center gap-2 rounded-xl px-1 py-1 text-left" title="Use this signature">
                        <span class="flex h-14 w-full items-center justify-center rounded-xl bg-white ring-1 ring-slate-100">
                            <img src="{{ $saved->preview_url }}" alt="" class="ro-saved-preview max-h-10 w-auto max-w-[90%] object-contain">
                        </span>
                        <span class="w-full truncate text-center text-[11px] font-medium text-slate-600">{{ $saved->user_signature_label ?: 'Signature' }}</span>
                    </button>
                    <button
                        type="button"
                        class="ro-delete-saved absolute right-2 top-2 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white/95 text-slate-400 opacity-0 shadow-sm ring-1 ring-slate-200/80 transition hover:bg-rose-50 hover:text-rose-600 group-hover:opacity-100"
                        data-id="{{ $saved->user_signature_id }}"
                        data-label="{{ $saved->user_signature_label ?: 'Signature' }}"
                        title="Remove from list"
                        aria-label="Remove saved signature"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            @empty
                <div id="roSavedEmpty" class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-4 text-center text-xs text-slate-500">
                    No saved signatures yet. Upload or draw one below, then save it for next time.
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2">
        <button type="button" id="roOpenSignPad" class="inline-flex items-center gap-2 rounded-xl bg-[#0025cc] px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4l10.5-10.5a2.5 2.5 0 00-3.536-3.536L4 16.464V20z"></path>
            </svg>
            <span id="roOpenSignPadLabel">Draw signature</span>
        </button>
        <button type="button" id="roClearSign" class="hidden rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50">
            Clear signature
        </button>
        <button
            type="button"
            id="roSaveCurrentSign"
            class="hidden rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50"
            @if ($savedSignatures->count() >= 4) disabled title="Maximum of 4 saved signatures reached" @endif
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
            <span class="mt-0.5 block text-[11px] text-slate-400">PNG, JPG, or similar · can be saved to your list</span>
            <span id="roSigUploadName" class="mt-1 hidden block truncate text-[11px] font-medium text-slate-600"></span>
        </span>
        <span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-slate-600">Choose file</span>
        <input type="file" id="roSigUpload" accept="image/*" class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0">
    </label>

    <label class="mt-3 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5">
        <input
            type="checkbox"
            id="roSaveOnUpload"
            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]"
            @checked($savedSignatures->count() < 4)
            @disabled($savedSignatures->count() >= 4)
        >
        <span class="min-w-0">
            <span class="block text-xs font-medium text-slate-700">Save uploaded signature to my list</span>
            <span class="mt-0.5 block text-[11px] text-slate-400">Keeps up to 4 signatures for the next approval.</span>
        </span>
    </label>
</div>

<div id="roSignPadModal" class="fixed inset-0 z-[12100] hidden">
    <div class="absolute inset-0 flex items-center justify-center bg-slate-900/45 p-4" data-ro-pad-dismiss>
        <div class="relative w-full max-w-[560px] rounded-2xl bg-white p-5 shadow-[0_20px_60px_rgba(15,23,42,0.2)]" onclick="event.stopPropagation()">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">{{ $padTitle }}</h3>
                    <p class="mt-1.5 text-sm leading-6 text-slate-500">{{ $padHint }}</p>
                </div>
                <button type="button" id="roCloseSignPad" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mt-4">
                @include('partials.signature-pad', [
                    'canvasId' => 'roSignatureCanvas',
                    'hiddenName' => 'ro_pad_scratch',
                    'hiddenId' => 'roPadScratch',
                    'label' => 'Digital signature',
                    'hint' => $padHint,
                    'requiredMessage' => 'Please sign before applying.',
                ])
            </div>
            <label class="mt-4 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5">
                <input
                    type="checkbox"
                    id="roSaveOnDraw"
                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]"
                    @checked($savedSignatures->count() < 4)
                    @disabled($savedSignatures->count() >= 4)
                >
                <span class="min-w-0">
                    <span class="block text-xs font-medium text-slate-700">Also save this drawing to my list</span>
                    <span class="mt-0.5 block text-[11px] text-slate-400">Maximum 4 saved signatures.</span>
                </span>
            </label>
            <div class="mt-5 flex flex-wrap items-center justify-end gap-2">
                <button type="button" id="roCancelSignPad" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:text-slate-950">Cancel</button>
                <button type="button" id="roApplySignPad" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                    Apply signature
                </button>
            </div>
        </div>
    </div>
</div>

<div id="roNameSigModal" class="fixed inset-0 z-[12200] hidden">
    <div class="absolute inset-0 flex items-center justify-center bg-slate-900/50 p-4" data-ro-name-dismiss>
        <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200/80" onclick="event.stopPropagation()">
            <div class="border-b border-slate-100 px-5 pb-3 pt-4">
                <h3 class="text-base font-semibold text-slate-900">Save to my list</h3>
                <p class="mt-1 text-sm text-slate-500">Give this signature a short name so it’s easy to reuse later.</p>
            </div>
            <div class="space-y-3 px-5 py-4">
                <div class="flex items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5">
                    <img id="roNameSigPreview" src="" alt="Signature preview" class="max-h-14 w-auto max-w-full object-contain">
                </div>
                <div>
                    <label for="roNameSigInput" class="block text-xs font-medium text-slate-700">Signature name <span class="font-normal text-slate-400">(optional)</span></label>
                    <input type="text" id="roNameSigInput" maxlength="120" placeholder="e.g. My official signature" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-slate-300 focus:ring-2 focus:ring-slate-100">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-5 py-3">
                <button type="button" id="roNameSigCancel" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700">Cancel</button>
                <button type="button" id="roNameSigConfirm" class="rounded-xl bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white">Save signature</button>
            </div>
        </div>
    </div>
</div>

<div id="roDeleteSigModal" class="fixed inset-0 z-[12200] hidden">
    <div class="absolute inset-0 flex items-center justify-center bg-slate-900/50 p-4" data-ro-delete-dismiss>
        <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200/80" onclick="event.stopPropagation()">
            <div class="px-5 pb-2 pt-5">
                <h3 class="text-base font-semibold text-slate-900">Remove signature?</h3>
                <p class="mt-1.5 text-sm text-slate-500">
                    <span id="roDeleteSigLabel" class="font-medium text-slate-700">This signature</span> will be removed from your saved list.
                </p>
            </div>
            <div class="mt-2 flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-5 py-3">
                <button type="button" id="roDeleteSigCancel" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700">Keep it</button>
                <button type="button" id="roDeleteSigConfirm" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white">Remove</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var sigHidden = null;
    var boundRrId = null;
    var sigUsed = null;
    var previewImg = document.getElementById('roSigPreview');
    var padModal = document.getElementById('roSignPadModal');
    var openPadBtn = document.getElementById('roOpenSignPad');
    var openPadLabel = document.getElementById('roOpenSignPadLabel');
    var clearSignBtn = document.getElementById('roClearSign');
    var applyPadBtn = document.getElementById('roApplySignPad');
    var closePadBtn = document.getElementById('roCloseSignPad');
    var cancelPadBtn = document.getElementById('roCancelSignPad');
    var signBadge = document.getElementById('roSignBadge');
    var uploadInput = document.getElementById('roSigUpload');
    var uploadNameOut = document.getElementById('roSigUploadName');
    var saveCurrentBtn = document.getElementById('roSaveCurrentSign');
    var saveOnUpload = document.getElementById('roSaveOnUpload');
    var saveOnDraw = document.getElementById('roSaveOnDraw');
    var savedList = document.getElementById('roSavedList');
    var savedCount = document.getElementById('roSavedCount');
    var nameModal = document.getElementById('roNameSigModal');
    var nameInput = document.getElementById('roNameSigInput');
    var namePreview = document.getElementById('roNameSigPreview');
    var nameConfirmBtn = document.getElementById('roNameSigConfirm');
    var nameCancelBtn = document.getElementById('roNameSigCancel');
    var deleteModal = document.getElementById('roDeleteSigModal');
    var deleteLabel = document.getElementById('roDeleteSigLabel');
    var deleteConfirmBtn = document.getElementById('roDeleteSigConfirm');
    var deleteCancelBtn = document.getElementById('roDeleteSigCancel');
    var pendingSaveDataUrl = '';
    var pendingDeleteId = '';
    var maxSavedSignatures = 4;
    var storeUrl = @json(route('receiving.saved-signatures.store'));
    var destroyBase = @json(url('/receiving/saved-signatures'));

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta && meta.content ? meta.content : '';
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function canvasHasDrawing(canvas) {
        if (!canvas) return false;
        var pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
        for (var i = 3; i < pixels.length; i += 4) {
            if (pixels[i] > 0) return true;
        }
        return false;
    }

        function syncBoundForm(url) {
        if (!boundRrId) return;
        var hidden = document.getElementById('scSigImage-' + boundRrId);
        if (hidden) hidden.value = url || '';
        var nameInput = document.getElementById('scName-' + boundRrId);
        var paperPreview = document.getElementById('scSigPreview-' + boundRrId);
        var printed = document.getElementById('roSigPrintedName');
        if (printed && nameInput && String(nameInput.value || '').trim() !== '') {
            printed.textContent = String(nameInput.value || '').trim();
        }
        if (!paperPreview) return;
        var name = nameInput ? String(nameInput.value || '').trim() : '';
        if (url && String(url).indexOf('data:image/') === 0) {
            var html = '<img src="' + url + '" alt="Second Count signature" style="max-height:48px;width:auto;position:absolute;left:50%;bottom:18px;transform:translateX(-50%);pointer-events:none;">';
            if (name !== '') {
                html += '<span style="position:relative;z-index:1;font-size:11px;font-weight:500;">'
                    + name.replace(/</g, '&lt;') + '</span>';
            }
            paperPreview.innerHTML = html;
            paperPreview.style.display = '';
            paperPreview.style.position = 'relative';
            if (nameInput) nameInput.style.display = 'none';
        } else {
            paperPreview.innerHTML = '';
            paperPreview.style.display = 'none';
            if (nameInput) nameInput.style.display = '';
        }
    }

    function applySignature(dataUrl) {
        var url = (dataUrl && String(dataUrl).indexOf('data:image/') === 0) ? dataUrl : '';
        if (sigHidden) sigHidden.value = url;
        if (previewImg) {
            if (url) {
                previewImg.src = url;
                previewImg.style.display = '';
            } else {
                previewImg.removeAttribute('src');
                previewImg.style.display = 'none';
            }
        }
        syncBoundForm(url);
        var hasSig = url !== '';
        if (signBadge) signBadge.classList.toggle('hidden', !hasSig);
        if (clearSignBtn) clearSignBtn.classList.toggle('hidden', !hasSig);
        if (saveCurrentBtn) {
            saveCurrentBtn.classList.toggle('hidden', !hasSig);
            updateSaveAvailability(savedList ? savedList.querySelectorAll('[data-saved-sig-id]').length : 0);
        }
        if (openPadLabel) openPadLabel.textContent = hasSig ? 'Redraw signature' : 'Draw signature';
    }

    function clearSignature() {
        var canvas = document.getElementById('roSignatureCanvas');
        if (window.clearSignaturePad) {
            window.clearSignaturePad('roSignatureCanvas', 'roPadScratch');
        } else if (canvas) {
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        }
        if (uploadInput) uploadInput.value = '';
        if (uploadNameOut) {
            uploadNameOut.textContent = '';
            uploadNameOut.classList.add('hidden');
        }
        applySignature('');
    }

    function updateSaveAvailability(count) {
        var full = count >= maxSavedSignatures;
        if (savedCount) {
            savedCount.textContent = count + ' / ' + maxSavedSignatures + ' saved';
            savedCount.classList.toggle('text-amber-600', full);
            savedCount.classList.toggle('text-slate-400', !full);
        }
        if (saveCurrentBtn && !saveCurrentBtn.classList.contains('hidden')) {
            saveCurrentBtn.disabled = full;
            saveCurrentBtn.title = full ? 'Maximum of 4 saved signatures reached' : '';
        }
        if (saveOnUpload) {
            saveOnUpload.disabled = full;
            if (full) saveOnUpload.checked = false;
        }
        if (saveOnDraw) {
            saveOnDraw.disabled = full;
            if (full) saveOnDraw.checked = false;
        }
    }

    function renderSavedList(items) {
        if (!savedList) return;
        var list = Array.isArray(items) ? items : [];
        updateSaveAvailability(list.length);
        if (!list.length) {
            savedList.innerHTML = '<div id="roSavedEmpty" class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-4 text-center text-xs text-slate-500">No saved signatures yet. Upload or draw one below, then save it for next time.</div>';
            return;
        }
        savedList.innerHTML = list.map(function (item) {
            var id = item.id;
            var label = escapeHtml(item.label || 'Signature');
            var preview = String(item.preview_url || '').replace(/"/g, '&quot;');
            return ''
                + '<div class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/90 p-2.5 shadow-sm" data-saved-sig-id="' + id + '">'
                +   '<button type="button" class="ro-use-saved flex w-full flex-col items-center gap-2 rounded-xl px-1 py-1 text-left" title="Use this signature">'
                +     '<span class="flex h-14 w-full items-center justify-center rounded-xl bg-white ring-1 ring-slate-100">'
                +       '<img src="' + preview + '" alt="" class="ro-saved-preview max-h-10 w-auto max-w-[90%] object-contain">'
                +     '</span>'
                +     '<span class="w-full truncate text-center text-[11px] font-medium text-slate-600">' + label + '</span>'
                +   '</button>'
                +   '<button type="button" class="ro-delete-saved absolute right-2 top-2 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white/95 text-slate-400 opacity-0 shadow-sm ring-1 ring-slate-200/80 transition hover:bg-rose-50 hover:text-rose-600 group-hover:opacity-100" data-id="' + id + '" data-label="' + label + '" title="Remove from list">'
                +     '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>'
                +   '</button>'
                + '</div>';
        }).join('');
    }

    function saveSignatureToLibrary(options) {
        options = options || {};
        var dataUrl = options.dataUrl || (sigHidden ? String(sigHidden.value || '') : '');
        var file = options.file || null;
        var label = options.label || '';
        var body;
        var headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken()
        };

        if (file) {
            body = new FormData();
            body.append('signature_file', file);
            if (label) body.append('signature_label', label);
            body.append('_token', csrfToken());
        } else {
            if (!dataUrl || dataUrl.indexOf('data:image/') !== 0) {
                return Promise.reject(new Error('No signature to save.'));
            }
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify({
                signature_image: dataUrl,
                signature_label: label || null,
                _token: csrfToken()
            });
        }

        return fetch(storeUrl, {
            method: 'POST',
            headers: headers,
            body: body,
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload.ok) {
                    throw new Error((payload && payload.message) || 'Could not save signature.');
                }
                return payload;
            });
        }).then(function (payload) {
            renderSavedList(payload.signatures || []);
            return payload;
        });
    }

    function deleteSavedSignature(id) {
        return fetch(destroyBase + '/' + encodeURIComponent(id), {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken()
            },
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload.ok) {
                    throw new Error((payload && payload.message) || 'Could not delete signature.');
                }
                return payload;
            });
        }).then(function (payload) {
            renderSavedList(payload.signatures || []);
            return payload;
        });
    }

    function openSignPad() {
        if (window.initSignaturePad) window.initSignaturePad('roSignatureCanvas');
        if (padModal) padModal.classList.remove('hidden');
    }
    function closeSignPad() {
        if (padModal) padModal.classList.add('hidden');
    }
    function openNameModal(dataUrl) {
        pendingSaveDataUrl = dataUrl || '';
        if (namePreview) namePreview.src = pendingSaveDataUrl;
        if (nameInput) nameInput.value = '';
        if (nameConfirmBtn) {
            nameConfirmBtn.disabled = false;
            nameConfirmBtn.textContent = 'Save signature';
        }
        if (nameModal) nameModal.classList.remove('hidden');
        if (nameInput) nameInput.focus();
    }
    function closeNameModal() {
        pendingSaveDataUrl = '';
        if (nameModal) nameModal.classList.add('hidden');
    }
    function openDeleteModal(id, label) {
        pendingDeleteId = id;
        if (deleteLabel) deleteLabel.textContent = label || 'This signature';
        if (deleteConfirmBtn) {
            deleteConfirmBtn.disabled = false;
            deleteConfirmBtn.textContent = 'Remove';
        }
        if (deleteModal) deleteModal.classList.remove('hidden');
    }
    function closeDeleteModal() {
        pendingDeleteId = '';
        if (deleteModal) deleteModal.classList.add('hidden');
    }

    function applyPadDrawing() {
        var canvas = document.getElementById('roSignatureCanvas');
        if (!canvasHasDrawing(canvas)) {
            if (typeof window.showMpToast === 'function') {
                showMpToast('Please sign before applying.', { title: 'Signature required', type: 'warning', timer: 3200 });
            } else {
                alert('Please sign before applying.');
            }
            return;
        }
        var dataUrl = canvas.toDataURL('image/png');
        applySignature(dataUrl);
        if (uploadInput) uploadInput.value = '';
        if (uploadNameOut) {
            uploadNameOut.textContent = '';
            uploadNameOut.classList.add('hidden');
        }
        closeSignPad();
        if (saveOnDraw && saveOnDraw.checked) {
            var currentCount = savedList ? savedList.querySelectorAll('[data-saved-sig-id]').length : 0;
            if (currentCount < maxSavedSignatures) {
                saveSignatureToLibrary({ dataUrl: dataUrl, label: 'Drawn signature' }).catch(function () {});
            }
        }
    }

    function readFileAsDataUrl(file) {
        return new Promise(function (resolve, reject) {
            if (!file) return resolve(null);
            var reader = new FileReader();
            reader.onload = function () { resolve(reader.result); };
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    if (openPadBtn) openPadBtn.addEventListener('click', openSignPad);
    if (applyPadBtn) applyPadBtn.addEventListener('click', applyPadDrawing);
    if (closePadBtn) closePadBtn.addEventListener('click', closeSignPad);
    if (cancelPadBtn) cancelPadBtn.addEventListener('click', closeSignPad);
    if (clearSignBtn) clearSignBtn.addEventListener('click', clearSignature);

    if (saveCurrentBtn) {
        saveCurrentBtn.addEventListener('click', function () {
            var dataUrl = sigHidden ? String(sigHidden.value || '') : '';
            if (!dataUrl || dataUrl.indexOf('data:image/') !== 0) return;
            if ((savedList ? savedList.querySelectorAll('[data-saved-sig-id]').length : 0) >= maxSavedSignatures) return;
            openNameModal(dataUrl);
        });
    }

    if (nameConfirmBtn) {
        nameConfirmBtn.addEventListener('click', function () {
            if (!pendingSaveDataUrl) return;
            var label = nameInput ? String(nameInput.value || '').trim() : '';
            nameConfirmBtn.disabled = true;
            nameConfirmBtn.textContent = 'Saving...';
            saveSignatureToLibrary({ dataUrl: pendingSaveDataUrl, label: label || 'My signature' }).then(function () {
                closeNameModal();
            }).catch(function (err) {
                nameConfirmBtn.disabled = false;
                nameConfirmBtn.textContent = 'Save signature';
                if (typeof window.showMpToast === 'function') {
                    showMpToast(err.message || 'Could not save signature.', { title: 'Unable to save', type: 'error' });
                }
            });
        });
    }
    if (nameCancelBtn) nameCancelBtn.addEventListener('click', closeNameModal);
    if (nameModal) {
        var nameDismiss = nameModal.querySelector('[data-ro-name-dismiss]');
        if (nameDismiss) nameDismiss.addEventListener('click', function (e) { if (e.target === nameDismiss) closeNameModal(); });
    }

    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', function () {
            if (!pendingDeleteId) return;
            deleteConfirmBtn.disabled = true;
            deleteConfirmBtn.textContent = 'Removing...';
            deleteSavedSignature(pendingDeleteId).then(function () {
                closeDeleteModal();
            }).catch(function () {
                deleteConfirmBtn.disabled = false;
                deleteConfirmBtn.textContent = 'Remove';
            });
        });
    }
    if (deleteCancelBtn) deleteCancelBtn.addEventListener('click', closeDeleteModal);
    if (deleteModal) {
        var deleteDismiss = deleteModal.querySelector('[data-ro-delete-dismiss]');
        if (deleteDismiss) deleteDismiss.addEventListener('click', function (e) { if (e.target === deleteDismiss) closeDeleteModal(); });
    }

    if (savedList) {
        savedList.addEventListener('click', function (event) {
            var useBtn = event.target.closest('.ro-use-saved');
            if (useBtn) {
                var img = useBtn.querySelector('.ro-saved-preview');
                applySignature(img ? img.getAttribute('src') : '');
                if (uploadInput) uploadInput.value = '';
                if (uploadNameOut) {
                    uploadNameOut.textContent = '';
                    uploadNameOut.classList.add('hidden');
                }
                return;
            }
            var deleteBtn = event.target.closest('.ro-delete-saved');
            if (deleteBtn) {
                openDeleteModal(deleteBtn.getAttribute('data-id'), deleteBtn.getAttribute('data-label') || 'This signature');
            }
        });
    }

    if (padModal) {
        var dismiss = padModal.querySelector('[data-ro-pad-dismiss]');
        if (dismiss) dismiss.addEventListener('click', function (e) { if (e.target === dismiss) closeSignPad(); });
    }

    if (uploadInput) {
        uploadInput.addEventListener('change', function () {
            var file = uploadInput.files && uploadInput.files[0];
            if (uploadNameOut) {
                if (file) {
                    uploadNameOut.textContent = file.name;
                    uploadNameOut.classList.remove('hidden');
                } else {
                    uploadNameOut.textContent = '';
                    uploadNameOut.classList.add('hidden');
                }
            }
            if (!file) return;
            readFileAsDataUrl(file).then(function (url) {
                applySignature(url || '');
                if (saveOnUpload && saveOnUpload.checked) {
                    var currentCount = savedList ? savedList.querySelectorAll('[data-saved-sig-id]').length : 0;
                    if (currentCount < maxSavedSignatures) {
                        var label = file.name ? String(file.name).replace(/\.[^.]+$/, '') : 'Uploaded signature';
                        saveSignatureToLibrary({ file: file, label: label }).catch(function () {});
                    }
                }
            }).catch(function () {});
        });
    }

    window.receivingSignaturePanel = {
        bind: function (rrId) {
            ['roSignPadModal', 'roNameSigModal', 'roDeleteSigModal'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el && el.parentElement !== document.body) {
                    document.body.appendChild(el);
                }
            });
            boundRrId = rrId ? String(rrId) : null;
            sigHidden = boundRrId ? document.getElementById('scSigImage-' + boundRrId) : null;
            var existing = sigHidden ? String(sigHidden.value || '') : '';
            applySignature(existing.indexOf('data:image/') === 0 ? existing : '');
            var nameInput = boundRrId ? document.getElementById('scName-' + boundRrId) : null;
            var printed = document.getElementById('roSigPrintedName');
            if (printed) {
                printed.textContent = (nameInput && String(nameInput.value || '').trim())
                    ? String(nameInput.value || '').trim()
                    : printed.textContent;
            }
            var slot = boundRrId ? document.getElementById('roSigSlot-' + boundRrId) : null;
            var panel = document.getElementById('roSignaturePanel');
            if (slot && panel && panel.parentElement !== slot) {
                slot.appendChild(panel);
            }
        },
        hasSignature: function () {
            var v = sigHidden ? String(sigHidden.value || '') : '';
            if (v.indexOf('data:image/') === 0) return true;
            if (boundRrId) {
                var h = document.getElementById('scSigImage-' + boundRrId);
                return h && String(h.value || '').indexOf('data:image/') === 0;
            }
            return false;
        },
        clear: clearSignature,
        reset: function () {
            clearSignature();
            boundRrId = null;
            sigHidden = null;
        }
    };
})();
</script>
