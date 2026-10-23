@php
    $atpSavedSignatures = collect($savedSignatures ?? [])->map(function ($saved) {
        return [
            'id' => (int) ($saved->user_signature_id ?? 0),
            'preview_url' => (string) ($saved->preview_url ?? ''),
            'label' => (string) ($saved->user_signature_label ?: 'Signature'),
        ];
    })->filter(fn ($row) => $row['preview_url'] !== '')->values();
@endphp
<script>
    function atpSubmitModal() {
        return {
            open: false,
            sending: false,
            action: '',
            number: '',
            editFormId: '',
            existingSignature: '',
            replaceSignature: false,
            signature: '',
            signatureSource: '',
            savedSignatureId: null,
            uploadName: '',
            signatureError: '',
            savedSignatures: @json($atpSavedSignatures),
            init() {
                window.addEventListener('open-atp-submit', (event) => this.openFrom(event.detail || {}));
                this.$nextTick(() => {
                    if (this.$el && this.$el.parentElement !== document.body) {
                        document.body.appendChild(this.$el);
                    }
                });
            },
            openFrom(detail) {
                this.sending = false;
                this.action = detail.action || '#';
                this.number = detail.number || 'this ATP';
                this.editFormId = detail.editFormId || '';
                const onFile = detail.signature && String(detail.signature).indexOf('data:image/') === 0
                    ? String(detail.signature)
                    : '';
                this.existingSignature = onFile;
                this.replaceSignature = !onFile;
                this.signature = '';
                this.signatureSource = '';
                this.savedSignatureId = null;
                this.uploadName = '';
                this.signatureError = '';
                this.open = true;
                window.atpSubmitOpen = true;
                this.$nextTick(() => {
                    this.resetPad();
                    if (this.$refs.upload) this.$refs.upload.value = '';
                    if (window.lucide) window.lucide.createIcons();
                });
            },
            close() {
                if (this.sending) return;
                this.open = false;
                this.editFormId = '';
                window.atpSubmitOpen = false;
            },
            ready() {
                if (!this.replaceSignature && this.existingSignature) return true;
                return String(this.signature || '').indexOf('data:image/') === 0;
            },
            canvasHasInk() {
                const canvas = document.getElementById('atpSubmitSignatureCanvas');
                if (!canvas) return false;
                const pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
                for (let i = 3; i < pixels.length; i += 4) {
                    if (pixels[i] > 0) return true;
                }
                return false;
            },
            resetPad() {
                if (window.initSignaturePad) window.initSignaturePad('atpSubmitSignatureCanvas');
                if (window.clearSignaturePad) window.clearSignaturePad('atpSubmitSignatureCanvas', '');
            },
            setSignature(dataUrl, source, savedId) {
                const raw = (dataUrl && String(dataUrl).indexOf('data:image/') === 0) ? String(dataUrl) : '';
                this.signatureError = '';
                this.signatureSource = raw ? source : '';
                this.savedSignatureId = raw ? savedId : null;
                if (raw && source !== 'pad' && typeof window.trimSignatureDataUrl === 'function') {
                    const current = this;
                    window.trimSignatureDataUrl(raw).then((url) => {
                        if (current.open) current.signature = url || '';
                    });
                    return;
                }
                this.signature = raw;
            },
            pickSaved(sig) {
                this.resetPad();
                this.uploadName = '';
                if (this.$refs.upload) this.$refs.upload.value = '';
                this.setSignature(sig.preview_url, 'saved', sig.id);
            },
            syncPad() {
                const canvas = document.getElementById('atpSubmitSignatureCanvas');
                if (!canvas) return;
                if (!this.canvasHasInk()) {
                    if (this.signatureSource === 'pad') this.setSignature('', '');
                    return;
                }
                const dataUrl = (window.exportTrimmedSignatureDataUrl
                    ? window.exportTrimmedSignatureDataUrl(canvas)
                    : '') || canvas.toDataURL('image/png');
                this.uploadName = '';
                if (this.$refs.upload) this.$refs.upload.value = '';
                this.setSignature(dataUrl, 'pad');
            },
            undoPad() {
                if (window.undoSignaturePad) window.undoSignaturePad('atpSubmitSignatureCanvas');
                this.syncPad();
            },
            clearSignature() {
                this.resetPad();
                this.uploadName = '';
                if (this.$refs.upload) this.$refs.upload.value = '';
                this.setSignature('', '');
            },
            async onUpload(event) {
                const file = event.target.files && event.target.files[0];
                if (!file) return;
                try {
                    const dataUrl = await new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = () => resolve(reader.result);
                        reader.onerror = reject;
                        reader.readAsDataURL(file);
                    });
                    this.resetPad();
                    this.setSignature(dataUrl || '', 'upload');
                    this.uploadName = file.name || 'Uploaded signature';
                } catch (err) {
                    this.signatureError = 'Could not read that signature file.';
                }
            },
            useDifferent() {
                this.replaceSignature = true;
                this.$nextTick(() => this.resetPad());
            },
            keepOnFile() {
                if (!this.existingSignature) return;
                this.clearSignature();
                this.replaceSignature = false;
            },
            beforeSubmit(event) {
                if (this.replaceSignature && this.signatureSource !== 'saved' && this.signatureSource !== 'upload') {
                    this.syncPad();
                }
                if (!this.ready()) {
                    event.preventDefault();
                    this.signatureError = 'Please sign the ATP before submitting to the School Administrator.';
                    return;
                }
                const reviewer = event.target.querySelector('[name=assigned_reviewer_id]');
                if (!reviewer || !String(reviewer.value || '').trim()) {
                    event.preventDefault();
                    this.signatureError = 'Please select a School Administrator.';
                    return;
                }
                if (this.editFormId) {
                    event.preventDefault();
                    const form = document.getElementById(this.editFormId);
                    if (!form) {
                        this.signatureError = 'The ATP form is no longer open. Close this and try again.';
                        return;
                    }
                    if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') {
                        window.purchaserDocumentSignature.flush();
                    }
                    const modalSignature = this.replaceSignature ? String(this.signature || '') : '';
                    const sigInput = form.querySelector('[name="authority_purchase_received_by_signature"]');
                    if (sigInput && modalSignature.indexOf('data:image/') === 0) {
                        sigInput.value = modalSignature;
                    } else if (sigInput && this.existingSignature && String(sigInput.value || '').indexOf('data:image/') !== 0) {
                        sigInput.value = this.existingSignature;
                    }
                    let reviewerInput = form.querySelector('[name="assigned_reviewer_id"]');
                    if (!reviewerInput) {
                        reviewerInput = document.createElement('input');
                        reviewerInput.type = 'hidden';
                        reviewerInput.name = 'assigned_reviewer_id';
                        form.appendChild(reviewerInput);
                    }
                    reviewerInput.value = reviewer.value;
                    const saveAction = form.querySelector('[name="save_action"]');
                    if (saveAction) saveAction.value = 'submit';
                    this.sending = true;
                    this.open = false;
                    window.atpSubmitOpen = false;
                    form.submit();
                    return;
                }
                this.sending = true;
                window.atpSubmitOpen = false;
            },
        };
    }

    function atpEscapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
        });
    }

    function atpClearSubmitMarks(form) {
        form.querySelectorAll('[data-atp-invalid]').forEach(function (el) {
            el.removeAttribute('data-atp-invalid');
            el.classList.remove('ring-1', 'ring-red-400', 'rounded');
        });
        const box = form.querySelector('[data-atp-form-errors]');
        if (box) {
            box.hidden = true;
            box.innerHTML = '';
        }
    }

    function atpMarkInvalid(el) {
        if (!el) return;
        el.setAttribute('data-atp-invalid', '1');
        el.classList.add('ring-1', 'ring-red-400', 'rounded');
    }

    function atpShowSubmitErrors(form, errors) {
        let box = form.querySelector('[data-atp-form-errors]');
        if (!box) {
            box = document.createElement('div');
            box.setAttribute('data-atp-form-errors', '');
            const footer = form.querySelector('[data-atp-form-footer]') || form.lastElementChild;
            form.insertBefore(box, footer);
        }
        box.className = 'mx-5 mt-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 md:mx-6';
        box.innerHTML = '<ul class="list-disc space-y-1 pl-4">' + errors.map(function (message) {
            return '<li>' + atpEscapeHtml(message) + '</li>';
        }).join('') + '</ul>';
        box.hidden = false;
        box.scrollIntoView({ block: 'nearest' });
    }

    function atpSubmitFieldErrors(form) {
        const errors = [];
        const ris = form.querySelector('[name="authority_purchase_ris_id"]');
        if (ris && !String(ris.value || '').trim()) {
            errors.push('Select an approved RIS.');
            atpMarkInvalid(form.querySelector('[x-ref="risSelect"]') || ris);
        }

        const supplier = form.querySelector('[name="authority_purchase_supplier_id"]');
        if (!supplier || !String(supplier.value || '').trim()) {
            errors.push('Select a supplier.');
            atpMarkInvalid(form.querySelector('[data-atp-supplier]') || supplier);
        }

        const date = form.querySelector('[name="authority_purchase_date"]');
        const dateValue = date ? String(date.value || '').trim() : '';
        const today = new Date();
        const todayIso = today.getFullYear() + '-'
            + String(today.getMonth() + 1).padStart(2, '0') + '-'
            + String(today.getDate()).padStart(2, '0');
        if (!dateValue) {
            errors.push('Enter a date.');
            atpMarkInvalid(date);
        } else if (dateValue < todayIso) {
            errors.push('The date cannot be before today.');
            atpMarkInvalid(date);
        }

        const receivedBy = form.querySelector('[name="authority_purchase_received_by_name"]');
        if (!receivedBy || !String(receivedBy.value || '').trim()) {
            errors.push('Enter who received the ATP.');
            atpMarkInvalid(receivedBy);
        }

        const signature = form.querySelector('[name="authority_purchase_received_by_signature"]');
        if (!signature || String(signature.value || '').indexOf('data:image/') !== 0) {
            errors.push('Draw or upload your signature before submitting.');
        }

        const rows = {};
        form.querySelectorAll('[name^="items["]').forEach(function (input) {
            const match = input.name.match(/^items\[(.+?)\]\[(.+)\]$/);
            if (!match || match[1] === '__INDEX__') return;
            if (!rows[match[1]]) rows[match[1]] = {};
            rows[match[1]][match[2]] = input;
        });
        const filled = Object.keys(rows).filter(function (key) {
            const description = rows[key].description;
            return description && String(description.value || '').trim() !== '';
        });
        if (!filled.length) {
            errors.push('Add at least one item before submitting.');
        }
        filled.forEach(function (key, index) {
            const row = rows[key];
            const rowNumber = index + 1;
            const quantity = parseInt(row.quantity ? row.quantity.value : '', 10);
            if (!quantity || quantity < 1) {
                errors.push('Item ' + rowNumber + ' requires a quantity of at least 1.');
                atpMarkInvalid(row.quantity);
            }
            if (!row.unit || !String(row.unit.value || '').trim()) {
                errors.push('Item ' + rowNumber + ' requires a unit.');
                atpMarkInvalid(row.unit);
            }
            const price = row.unit_price ? String(row.unit_price.value || '').trim() : '';
            if (price === '' || Number.isNaN(Number(price)) || Number(price) < 0) {
                errors.push('Item ' + rowNumber + ' requires a unit price.');
                atpMarkInvalid(row.unit_price);
            }
        });

        return errors;
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest && event.target.closest('[data-atp-submit]');
        if (!button) return;
        // Edit/view dialogs use @click.stop, which blocks this from bubbling to document.
        const editFormId = button.getAttribute('data-atp-edit-form') || '';
        let signature = button.getAttribute('data-atp-signature') || '';
        if (editFormId) {
            const form = document.getElementById(editFormId);
            if (!form) return;
            if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') {
                window.purchaserDocumentSignature.flush();
            }
            atpClearSubmitMarks(form);
            const fieldErrors = atpSubmitFieldErrors(form);
            if (fieldErrors.length) {
                event.preventDefault();
                event.stopPropagation();
                atpShowSubmitErrors(form, fieldErrors);
                return;
            }
            const sigInput = form.querySelector('[name="authority_purchase_received_by_signature"]');
            if (sigInput && String(sigInput.value || '').indexOf('data:image/') === 0) {
                signature = sigInput.value;
            }
        }
        window.dispatchEvent(new CustomEvent('open-atp-submit', {
            detail: {
                action: button.getAttribute('data-atp-action') || '#',
                number: button.getAttribute('data-atp-number') || 'this ATP',
                signature: signature,
                editFormId: editFormId,
            },
        }));
    }, true);
</script>

<div
    x-data="atpSubmitModal()"
    x-cloak
    x-show="open"
    x-transition.opacity
    class="fixed inset-0 z-[13000] flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
    x-effect="window.purDialog && window.purDialog.sync(open, $el)"
    @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
    @keydown.escape.window="close()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="atp-submit-title"
>
    <div @click.self="close()" class="flex min-h-full w-full justify-center">
        <div class="my-auto w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
            <form method="POST" x-bind:action="action" x-on:submit="beforeSubmit($event)">
                @csrf
                <input type="hidden" name="signature_data" x-bind:value="replaceSignature ? (signature || '') : ''">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-[#0f172a]">
                            <i data-lucide="send" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="atp-submit-title" class="text-lg font-semibold tracking-tight text-gray-950">Submit to School Administrator</h3>
                            <p class="mt-0.5 text-sm text-gray-500">This will send the ATP for review.</p>
                        </div>
                    </div>
                    <button type="button" x-on:click="close()" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                <div class="space-y-4 px-5 py-5 text-sm leading-6 text-gray-600">
                    <p>
                        Submit
                        <span class="font-semibold text-gray-900" x-text="number"></span>
                        to School Administrator?
                    </p>

                    <div class="rounded-xl border border-slate-200 bg-white p-3.5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold text-slate-900">Received by signature <span class="text-red-500">*</span></p>
                                <p class="mt-0.5 text-[11px] text-slate-500">Required before this ATP can be sent to the School Administrator.</p>
                            </div>
                            <span
                                x-show="ready()"
                                x-cloak
                                class="inline-flex shrink-0 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700"
                            >Signed</span>
                        </div>

                        <div x-show="!replaceSignature" x-cloak class="mt-3">
                            <div class="flex items-center justify-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                                <img x-bind:src="existingSignature" alt="Signature on file" class="max-h-14 w-auto max-w-full object-contain">
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <span class="text-[11px] text-slate-500">Using the signature already on this ATP.</span>
                                <button type="button" class="text-[11px] font-semibold text-[#0025cc] hover:underline" x-on:click="useDifferent()">Use a different signature</button>
                            </div>
                        </div>

                        <div x-show="replaceSignature" x-cloak class="mt-3 space-y-3">
                            <div x-show="savedSignatures.length">
                                <p class="text-[11px] font-medium text-slate-600">My saved signatures</p>
                                <div class="mt-1.5 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    <template x-for="sig in savedSignatures" :key="'atp-submit-sig-' + sig.id">
                                        <button
                                            type="button"
                                            class="flex h-12 items-center justify-center rounded-lg border bg-white px-1.5 transition hover:border-slate-300"
                                            x-bind:class="signatureSource === 'saved' && savedSignatureId === sig.id ? 'border-[#0025cc] ring-2 ring-blue-100' : 'border-slate-200'"
                                            x-bind:title="sig.label || 'Signature'"
                                            x-on:click="pickSaved(sig)"
                                        >
                                            <img x-bind:src="sig.preview_url" alt="" class="max-h-9 w-auto max-w-full object-contain">
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div x-show="signatureSource === 'upload' && signature" x-cloak class="flex items-center justify-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                                <img x-bind:src="signature" alt="Uploaded signature" class="max-h-14 w-auto max-w-full object-contain">
                            </div>

                            <div>
                                <p class="text-[11px] font-medium text-slate-600" x-text="savedSignatures.length ? 'Or draw your signature' : 'Draw your signature'"></p>
                                <canvas
                                    id="atpSubmitSignatureCanvas"
                                    class="signature-pad-canvas mt-1.5 w-full rounded-lg border border-slate-200 bg-white"
                                    width="520"
                                    height="160"
                                    tabindex="0"
                                    aria-label="Signature drawing area"
                                    x-on:mouseup="syncPad()"
                                    x-on:mouseleave="syncPad()"
                                    x-on:touchend="syncPad()"
                                    x-on:keyup="syncPad()"
                                ></canvas>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-[11px] font-medium text-slate-700" x-on:click="undoPad()">Undo</button>
                                    <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-[11px] font-medium text-slate-700" x-on:click="clearSignature()">Clear</button>
                                    <label class="relative ml-auto inline-flex cursor-pointer items-center rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50">
                                        <span x-text="uploadName ? uploadName : 'Upload image'" class="max-w-[10rem] truncate"></span>
                                        <input type="file" accept="image/*" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" x-ref="upload" x-on:change="onUpload($event)">
                                    </label>
                                </div>
                            </div>

                            <button type="button" x-show="existingSignature" x-cloak class="text-[11px] font-semibold text-slate-600 hover:text-slate-900 hover:underline" x-on:click="keepOnFile()">Keep the signature on file</button>
                        </div>

                        <p x-show="signatureError" x-cloak class="mt-2 text-xs font-medium text-red-600" x-text="signatureError"></p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-600">Assign to School Administrator <span class="text-red-500">*</span></label>
                        <select name="assigned_reviewer_id" required class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-800 outline-none transition focus:border-gray-300">
                            <option value="">Select School Administrator…</option>
                            @foreach(\App\Support\ReviewerAssignment::options(\App\Support\WorkflowNotifier::ROLE_SCHOOL_ADMIN) as $reviewer)
                                <option value="{{ $reviewer['id'] }}">{{ $reviewer['name'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-gray-400">Only the selected School Administrator will receive this ATP for review.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                    <button type="button" x-on:click="close()" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">Cancel</button>
                    <button
                        type="submit"
                        x-bind:disabled="sending || !ready()"
                        x-bind:title="ready() ? '' : 'Sign the ATP first'"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-[#001db3] disabled:cursor-not-allowed disabled:opacity-50"
                    >Yes, submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
