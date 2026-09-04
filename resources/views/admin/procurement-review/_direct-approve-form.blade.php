{{-- Admin Checked by / Issued by: Direct Approve, Forward to President, or Co-sign --}}
@php
    $adminName = Auth::user()->user_full_name ?? 'Admin';
    $todayDisplay = now()->format('d/m/Y');
    $items = $risItems ?? collect();
    $mode = in_array(($mode ?? 'direct'), ['direct', 'forward', 'cosign'], true) ? $mode : 'direct';
    $isForward = $mode === 'forward';
    $isCosign = $mode === 'cosign';
    $isDirect = $mode === 'direct';
    $formAction = $isCosign
        ? route('admin.digital-signatures.ris.decide')
        : ($isForward
            ? route('admin.procurement-review.ris.approve', $ris->ris_id)
            : route('admin.procurement-review.ris.direct-approve', $ris->ris_id));
    $approvedRaw = trim((string) ($ris->ris_approved_by_signature ?? ''));
    $approvedIsImage = $approvedRaw !== '' && str_starts_with($approvedRaw, 'data:image');
    $secondColumnLabel = $isDirect ? 'Checked by:' : 'Approved by:';
@endphp

<style>
    .admin-da-ris-form {
        width: 100%;
        max-width: 1095px;
        min-height: 845px;
        border: 2px solid #1f2937;
        padding: 26px 24px 24px;
        font-family: Arial, Helvetica, sans-serif;
        color: #000;
        background: #fff;
    }
    .admin-da-ris-form .ris-document-header { position: relative; height: 120px; text-align: center; }
    .admin-da-ris-form .ris-school-name { font-size: 19px; line-height: 1.2; font-weight: 700; }
    .admin-da-ris-form .ris-document-title { margin-top: 9px; font-size: 15px; line-height: 1.2; font-weight: 700; }
    .admin-da-ris-form .ris-number-area { position: absolute; right: 0; bottom: 18px; display: flex; align-items: flex-end; gap: 10px; }
    .admin-da-ris-form .ris-number-label { font-size: 15px; font-weight: 600; }
    .admin-da-ris-form .ris-number-line {
        display: flex; align-items: flex-end; justify-content: center;
        width: 160px; min-height: 24px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px;
    }
    .admin-da-ris-form .ris-items-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .admin-da-ris-form .ris-items-table th,
    .admin-da-ris-form .ris-items-table td { border: 1px solid #1f2937; }
    .admin-da-ris-form .ris-items-table th {
        padding: 9px 5px; vertical-align: middle; text-align: center;
        font-size: 12px; line-height: 1.2; font-weight: 700;
    }
    .admin-da-ris-form .ris-items-table tbody td {
        height: 45px; padding: 4px 6px; font-size: 12px; vertical-align: middle;
    }
    .admin-da-ris-form .ris-item-column { width: 20%; }
    .admin-da-ris-form .ris-brand-column { width: 10%; }
    .admin-da-ris-form .ris-unit-column { width: 7%; font-size: 11px !important; }
    .admin-da-ris-form .ris-supplier-column { width: 14%; font-size: 11px !important; }
    .admin-da-ris-form .ris-quantity-header { width: 20%; }
    .admin-da-ris-form .ris-requested-column { width: 10%; font-size: 11px !important; }
    .admin-da-ris-form .ris-issued-column { width: 10%; font-size: 11px !important; }
    .admin-da-ris-form .ris-unit-cost-column { width: 14%; }
    .admin-da-ris-form .ris-amount-column { width: 16%; }
    .admin-da-ris-form .ris-purpose-area { margin-top: 31px; }
    .admin-da-ris-form .ris-purpose-label { font-size: 13px; font-weight: 700; }
    .admin-da-ris-form .ris-purpose-line-row { display: flex; margin-top: 29px; }
    .admin-da-ris-form .ris-purpose-spacer { width: 80px; flex-shrink: 0; }
    .admin-da-ris-form .ris-purpose-line {
        flex: 1; min-height: 40px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px; font-weight: 400;
    }
    .admin-da-ris-form .ris-signatures {
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
        column-gap: 32px; margin-top: 40px;
    }
    .admin-da-ris-form .ris-signature-column { min-width: 0; }
    .admin-da-ris-form .ris-signature-label { font-size: 12px; color: #374151; }
    .admin-da-ris-form .ris-signature-line {
        display: flex; align-items: flex-end; justify-content: center;
        height: 49px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px; text-align: center;
    }
    .admin-da-ris-form .ris-date-label { margin-top: 16px; font-size: 12px; color: #374151; }
    .admin-da-ris-form .ris-date-line {
        display: flex; align-items: flex-end; justify-content: center;
        height: 31px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px; text-align: center;
    }
    .admin-da-ris-form .ris-signature-input,
    .admin-da-ris-form .ris-date-input {
        width: 100%;
        border: 0;
        border-bottom: 1px solid #1f2937;
        border-radius: 0;
        background: #fffbeb;
        outline: none;
        box-shadow: none;
        text-align: center;
        font-size: 12px;
    }
    .admin-da-ris-form .ris-signature-input {
        height: 49px;
        padding: 0 6px 4px;
    }
    .admin-da-ris-form .ris-date-input {
        height: 31px;
        padding: 0 6px 4px;
    }
    .admin-da-ris-form .ris-editable-hint {
        margin-top: 4px;
        font-size: 10px;
        color: #64748b;
        text-align: center;
        font-weight: 600;
    }
    .admin-da-locked {
        pointer-events: none;
        user-select: none;
    }
</style>

<form
    id="directApproveForm"
    method="POST"
    action="{{ $formAction }}"
    enctype="multipart/form-data"
    class="flex min-h-0 flex-1 flex-col"
    data-mode="{{ $mode }}"
>
    @csrf
    @if ($isCosign)
        <input type="hidden" name="target_id" value="{{ $ris->ris_id }}">
        <input type="hidden" name="decision" value="Approved">
    @endif
    <input type="hidden" name="ris_issued_by_signature_image" id="ris_issued_by_signature_image" value="">
    @if ($isDirect)
        <input type="hidden" name="ris_checked_by_signature_image" id="ris_checked_by_signature_image" value="">
    @endif

    <div class="min-h-0 flex-1 overflow-y-auto">
        <div class="space-y-4 p-4 md:px-5 md:pt-3 md:pb-4">
            <div class="flex gap-3 rounded-xl border px-4 py-3 {{ $isForward ? 'border-sky-100 bg-sky-50/90' : ($isCosign ? 'border-slate-200 bg-slate-50' : 'border-emerald-100 bg-emerald-50/80') }}">
                <svg class="mt-0.5 h-4 w-4 shrink-0 {{ $isForward ? 'text-sky-600' : ($isCosign ? 'text-slate-500' : 'text-emerald-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"></path>
                </svg>
                <p class="text-xs leading-relaxed {{ $isForward ? 'text-sky-950/80' : ($isCosign ? 'text-slate-700' : 'text-emerald-950/80') }}">
                    @if ($isForward)
                        All RIS details are locked. You may add optional <strong>supporting details</strong> and an attachment for the President. Confirming forwards this RIS without an Issued by signature. After the President approves, sign Issued by here on Sign RIS.
                    @elseif ($isCosign)
                        The President has already signed <strong>Approved by</strong>. You can only fill <strong>Issued by</strong> and its <strong>Date</strong>. Confirming returns this RIS to the Purchaser.
                    @else
                        Direct approval bypasses presidential signing. Fill <strong>Checked by</strong> and <strong>Issued by</strong>, provide a <strong>reason</strong> (and optional proof), then confirm. This returns the RIS to the Purchaser, keeps a copy in <strong>Signature History</strong>, and records it for the President.
                    @endif
                </p>
            </div>

            @if ($isDirect)
            <div class="rounded-xl border border-amber-100 bg-white p-4 shadow-sm shadow-slate-900/5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">
                            Reason for direct approval
                        </h4>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">
                            Required for the President’s record. Explain why this RIS was approved without presidential signing.
                        </p>
                    </div>
                </div>

                <label for="direct_approval_reason" class="mt-3 block text-xs font-medium text-slate-700">
                    Reason <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="direct_approval_reason"
                    name="direct_approval_reason"
                    rows="3"
                    required
                    maxlength="2000"
                    placeholder="e.g. Urgent classroom equipment replacement; amount within admin authority; emergency purchase needed before class resumes."
                    class="mt-1.5 block w-full resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                >{{ old('direct_approval_reason') }}</textarea>

                <label for="direct_approval_proof" class="mt-4 flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-3 transition hover:border-slate-300 hover:bg-slate-50">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-medium text-slate-700">Proof attachment</span>
                        <span class="mt-0.5 block text-[11px] text-slate-400">Optional · Image, PDF, or Word · max 10 MB</span>
                        <span id="direct_approval_proofName" class="mt-1 hidden block truncate text-[11px] font-medium text-slate-600"></span>
                    </span>
                    <span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-slate-600">
                        Choose file
                    </span>
                    <input
                        type="file"
                        id="direct_approval_proof"
                        name="direct_approval_proof"
                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,image/*"
                        class="sr-only"
                    >
                </label>
            </div>
            @endif

            @if ($isForward)
            <div class="rounded-xl border border-sky-100 bg-white p-4 shadow-sm shadow-slate-900/5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">
                            Admin supporting details
                        </h4>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">
                            Optional note and attachment for the President explaining why this RIS should be approved. Separate from Purchaser supporting documents below.
                        </p>
                    </div>
                </div>

                <label for="forward_details" class="mt-3 block text-xs font-medium text-slate-700">
                    Supporting details
                </label>
                <textarea
                    id="forward_details"
                    name="forward_details"
                    rows="3"
                    maxlength="2000"
                    placeholder="e.g. Priority replacement for Room 204; budget already verified; recommended for presidential approval."
                    class="mt-1.5 block w-full resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-300 focus:ring-2 focus:ring-sky-100"
                >{{ old('forward_details') }}</textarea>

                <label for="forward_attachment" class="mt-4 flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-sky-200 bg-sky-50/50 px-3.5 py-3 transition hover:border-sky-300 hover:bg-sky-50">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-sky-500 ring-1 ring-sky-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-medium text-slate-700">Supporting attachment</span>
                        <span class="mt-0.5 block text-[11px] text-slate-400">Optional · Image, PDF, or Word · max 10 MB</span>
                        <span id="forward_attachmentName" class="mt-1 hidden block truncate text-[11px] font-medium text-sky-700"></span>
                    </span>
                    <span class="rounded-lg border border-sky-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-sky-700">
                        Choose file
                    </span>
                    <input
                        type="file"
                        id="forward_attachment"
                        name="forward_attachment"
                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,image/*"
                        class="sr-only"
                    >
                </label>
            </div>
            <script>
            (function () {
                var input = document.getElementById('forward_attachment');
                var nameOut = document.getElementById('forward_attachmentName');
                if (!input) return;
                input.addEventListener('change', function () {
                    var file = input.files && input.files[0];
                    if (!nameOut) return;
                    if (file) {
                        nameOut.textContent = file.name;
                        nameOut.classList.remove('hidden');
                    } else {
                        nameOut.textContent = '';
                        nameOut.classList.add('hidden');
                    }
                });
            })();
            </script>
            @endif

            {{-- Supporting documents already attached to this RIS --}}
            @php $risSupportingDocs = $supportingDocuments ?? collect(); @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">
                            Supporting documents
                        </h4>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">
                            Files the Purchaser attached to this RIS. Open any file to review before you confirm.
                        </p>
                    </div>
                    <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                        {{ $risSupportingDocs->count() }} file{{ $risSupportingDocs->count() === 1 ? '' : 's' }}
                    </span>
                </div>

                @if ($risSupportingDocs->isNotEmpty())
                    <ul class="mt-3 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200">
                        @foreach ($risSupportingDocs as $file)
                            <li>
                                <a
                                    href="{{ route('admin.ris.attachments.download', $file->ris_attachment_id) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex items-center gap-3 px-3.5 py-2.5 transition hover:bg-slate-50"
                                    title="{{ $file->ris_attachment_original_name }}"
                                >
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                        </svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-xs font-medium text-slate-800">
                                            {{ $file->ris_attachment_original_name }}
                                        </span>
                                        @if (!empty($file->ris_attachment_size))
                                            <span class="mt-0.5 block text-[11px] text-slate-400">
                                                {{ number_format(((int) $file->ris_attachment_size) / 1024, 1) }} KB
                                            </span>
                                        @endif
                                    </span>
                                    <span class="text-[11px] font-medium text-slate-500">Open</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-3 text-xs text-slate-500">
                        No supporting documents were attached to this RIS.
                    </div>
                @endif
            </div>

            @if (!$isForward)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">
                            {{ $isDirect ? 'Signature images (optional)' : 'Issued by signature' }}
                        </h4>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">
                            @if ($isDirect)
                                Type names on the form below. Optionally upload handwritten signature images for Checked by and/or Issued by.
                            @else
                                Type your name on the form below and/or upload a handwritten signature. Both are saved on <strong>Issued by</strong> only.
                            @endif
                        </p>
                    </div>
                </div>

                @if ($isDirect)
                <label for="sigUploadChecked" class="mt-3 flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-3 transition hover:border-slate-300 hover:bg-slate-50">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-medium text-slate-700">Checked by signature image</span>
                        <span class="mt-0.5 block text-[11px] text-slate-400">Optional · PNG, JPG, or similar</span>
                    </span>
                    <span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-slate-600">
                        Choose file
                    </span>
                    <input
                        type="file"
                        id="sigUploadChecked"
                        name="ris_checked_by_signature_file"
                        accept="image/*"
                        class="sr-only"
                    >
                </label>
                <p id="sigUploadCheckedName" class="mt-2 hidden truncate text-[11px] text-slate-500"></p>
                @endif

                <label for="sigUploadIssued" class="mt-3 flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-3 transition hover:border-slate-300 hover:bg-slate-50">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-medium text-slate-700">Issued by signature image</span>
                        <span class="mt-0.5 block text-[11px] text-slate-400">Optional · PNG, JPG, or similar</span>
                    </span>
                    <span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-slate-600">
                        Choose file
                    </span>
                    <input
                        type="file"
                        id="sigUploadIssued"
                        name="ris_issued_by_signature_file"
                        accept="image/*"
                        class="sr-only"
                    >
                </label>
                <p id="sigUploadIssuedName" class="mt-2 hidden truncate text-[11px] text-slate-500"></p>
            </div>
            @endif

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-100/80 shadow-inner">
                <div class="overflow-x-auto p-3 md:p-5">
                    <div class="admin-da-ris-form mx-auto">
                <div class="ris-document-header admin-da-locked">
                    <div class="ris-school-name">STI COLLEGE - ORMOC, INC.</div>
                    <div class="ris-document-title">REQUISITION AND ISSUE SLIP</div>
                    <div class="ris-number-area">
                        <span class="ris-number-label">No.</span>
                        <div class="ris-number-line">{{ $ris->ris_form_number ?: '—' }}</div>
                    </div>
                </div>

                <table class="ris-items-table admin-da-locked">
                    <thead>
                        <tr>
                            <th rowspan="2" class="ris-item-column">ITEM</th>
                            <th rowspan="2" class="ris-brand-column">BRAND</th>
                            <th rowspan="2" class="ris-unit-column">UNIT</th>
                            <th rowspan="2" class="ris-supplier-column">SUPPLIER</th>
                            <th colspan="2" class="ris-quantity-header">QUANTITY</th>
                            <th rowspan="2" class="ris-unit-cost-column">UNIT COST</th>
                            <th rowspan="2" class="ris-amount-column">AMOUNT</th>
                        </tr>
                        <tr>
                            <th class="ris-requested-column">REQUESTED</th>
                            <th class="ris-issued-column">ISSUED</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowCount = max(10, $items->count()); @endphp
                        @for($i = 0; $i < $rowCount; $i++)
                            @php $item = $items->get($i); @endphp
                            <tr>
                                <td>{{ $item->ris_item_name_description ?? '' }}</td>
                                <td class="text-center">{{ $item?->brand_name ?? '' }}</td>
                                <td class="text-center">{{ $item?->uom_name ?? '' }}</td>
                                <td>{{ $item->supplier_display_name ?? '' }}</td>
                                <td class="text-center">{{ $item->ris_quantity_requested ?? '' }}</td>
                                <td class="text-center">{{ $item->ris_quantity_issued ?? '' }}</td>
                                <td class="text-right">{{ isset($item->ris_unit_cost) ? number_format((float) $item->ris_unit_cost, 2) : '' }}</td>
                                <td class="text-right">{{ isset($item->ris_total_amount) ? number_format((float) $item->ris_total_amount, 2) : (isset($item) ? number_format(((float)($item->ris_quantity_issued ?? 0)) * ((float)($item->ris_unit_cost ?? 0)), 2) : '') }}</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>

                <div class="ris-purpose-area admin-da-locked">
                    <div class="ris-purpose-label">PURPOSE</div>
                    <div class="ris-purpose-line-row">
                        <div class="ris-purpose-spacer"></div>
                        <div class="ris-purpose-line">{{ $ris->ris_purpose_description ?: ($ris->ris_manual_description ?? '') }}</div>
                    </div>
                </div>

                <div class="ris-signatures">
                    <div class="ris-signature-column admin-da-locked">
                        <div class="ris-signature-label">Requested by:</div>
                        <div class="ris-signature-line" id="sigLineRequested">{{ $ris->ris_requested_by_signature ?: ' ' }}</div>
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line">
                            {{ $ris->ris_requested_by_date ? \Carbon\Carbon::parse($ris->ris_requested_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                        </div>
                    </div>

                    <div class="ris-signature-column {{ $isDirect ? '' : 'admin-da-locked' }}">
                        <div class="ris-signature-label">{{ $secondColumnLabel }}</div>
                        @if ($isDirect)
                            <div id="sigLineCheckedPreview" class="ris-signature-line" style="display:flex; flex-direction:column; align-items:center; justify-content:flex-end; gap:2px;"></div>
                            <input
                                type="text"
                                name="ris_checked_by"
                                id="da_checked_by"
                                value="{{ old('ris_checked_by', $adminName) }}"
                                required
                                maxlength="255"
                                autocomplete="off"
                                class="ris-signature-input"
                                title="Admin name for Checked by"
                            >
                            <div class="ris-editable-hint">Name is required · signature image optional</div>
                            <div class="ris-date-label">Date:</div>
                            <input
                                type="text"
                                name="ris_checked_by_date"
                                id="da_checked_by_date"
                                value="{{ old('ris_checked_by_date', $todayDisplay) }}"
                                required
                                placeholder="dd/mm/yyyy"
                                inputmode="numeric"
                                maxlength="10"
                                autocomplete="off"
                                class="ris-date-input"
                                title="Checked by date (dd/mm/yyyy)"
                            >
                            <div class="ris-editable-hint">Editable</div>
                        @else
                            <div class="ris-signature-line" id="sigLineApproved">
                                @if ($isCosign && $approvedIsImage)
                                    <img src="{{ $approvedRaw }}" alt="Approved by" style="max-height: 36px; width: auto;">
                                @elseif ($isCosign && $approvedRaw !== '')
                                    {{ $approvedRaw }}
                                @else
                                    {{ ' ' }}
                                @endif
                            </div>
                            <div class="ris-date-label">Date:</div>
                            <div class="ris-date-line">
                                @if ($isCosign && !empty($ris->ris_approved_by_date))
                                    {{ \Carbon\Carbon::parse($ris->ris_approved_by_date)->format('d/m/Y') }}
                                @else
                                    dd/mm/yyyy
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="ris-signature-column {{ $isForward ? 'admin-da-locked' : '' }}">
                        <div class="ris-signature-label">Issued by:</div>
                        @if ($isForward)
                            <div class="ris-signature-line"> </div>
                            <div class="ris-date-label">Date:</div>
                            <div class="ris-date-line">dd/mm/yyyy</div>
                        @else
                        <div id="sigLineIssuedPreview" class="ris-signature-line" style="display:flex; flex-direction:column; align-items:center; justify-content:flex-end; gap:2px;"></div>
                        <input
                            type="text"
                            name="ris_issued_by"
                            id="da_issued_by"
                            value="{{ old('ris_issued_by', $adminName) }}"
                            required
                            maxlength="255"
                            autocomplete="off"
                            class="ris-signature-input"
                            title="Admin name for Issued by"
                        >
                        <div class="ris-editable-hint">Name is required · signature image optional</div>
                        <div class="ris-date-label">Date:</div>
                        <input
                            type="text"
                            name="ris_issued_by_date"
                            id="da_issued_by_date"
                            value="{{ old('ris_issued_by_date', $todayDisplay) }}"
                            required
                            placeholder="dd/mm/yyyy"
                            inputmode="numeric"
                            maxlength="10"
                            autocomplete="off"
                            class="ris-date-input"
                            title="Issued by date (dd/mm/yyyy)"
                        >
                        <div class="ris-editable-hint">Editable</div>
                        @endif
                    </div>

                    <div class="ris-signature-column admin-da-locked">
                        <div class="ris-signature-label">Received by:</div>
                        <div class="ris-signature-line">{{ $ris->ris_received_by_signature ?: ' ' }}</div>
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line">
                            {{ $ris->ris_received_by_date ? \Carbon\Carbon::parse($ris->ris_received_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-white px-5 py-4 md:px-6">
        <button
            type="button"
            onclick="closeDirectApproveModal()"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
            Cancel
        </button>
        <button
            type="submit"
            class="rounded-xl px-4 py-2.5 text-sm font-medium text-white shadow-sm transition {{ $isForward ? 'bg-sky-600 hover:bg-sky-700' : 'bg-slate-900 hover:bg-slate-800' }}"
            title="{{ $isForward ? 'Forward this RIS to the President' : ($isCosign ? 'Sign Issued by and return to Purchaser' : 'Confirm direct approval, notify President for record, and return to Purchaser') }}"
        >
            {{ $isForward ? 'Forward to President' : ($isCosign ? 'Confirm Issued by' : 'Confirm Admin Approval') }}
        </button>
    </div>
</form>

@if (!$isForward)
<script>
(function () {
    var form = document.getElementById('directApproveForm');
    var nameInput = document.getElementById('da_issued_by');
    var fileInput = document.getElementById('sigUploadIssued');
    var hidden = document.getElementById('ris_issued_by_signature_image');
    var preview = document.getElementById('sigLineIssuedPreview');
    var checkedNameInput = document.getElementById('da_checked_by');
    var checkedFileInput = document.getElementById('sigUploadChecked');
    var checkedHidden = document.getElementById('ris_checked_by_signature_image');
    var checkedPreview = document.getElementById('sigLineCheckedPreview');
    var proofInput = document.getElementById('direct_approval_proof');
    var proofNameOut = document.getElementById('direct_approval_proofName');
    var dateInputs = [
        document.getElementById('da_issued_by_date'),
        document.getElementById('da_checked_by_date'),
    ];

    if (proofInput) {
        proofInput.addEventListener('change', function () {
            var file = proofInput.files && proofInput.files[0];
            if (!proofNameOut) return;
            if (file) {
                proofNameOut.textContent = file.name;
                proofNameOut.classList.remove('hidden');
            } else {
                proofNameOut.textContent = '';
                proofNameOut.classList.add('hidden');
            }
        });
    }

    function escapeHtml(value) {
        return String(value || '').replace(/</g, '&lt;');
    }

    function syncPreview(nameEl, hiddenEl, previewEl, roleLabel) {
        if (!previewEl) return;
        var name = nameEl ? String(nameEl.value || '').trim() : '';
        var dataUrl = hiddenEl ? String(hiddenEl.value || '').trim() : '';
        var html = '';
        if (dataUrl.indexOf('data:image/') === 0) {
            html += '<img src="' + dataUrl + '" alt="' + roleLabel + ' signature" style="max-height:36px;width:auto;">';
            if (name !== '') {
                html += '<span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">' + escapeHtml(name) + '</span>';
                html += '<span style="font-size:10px;color:#4b5563;">' + escapeHtml(roleLabel) + '</span>';
            }
            previewEl.innerHTML = html;
            previewEl.style.display = 'flex';
            if (nameEl) nameEl.style.display = 'none';
            return;
        }
        previewEl.innerHTML = '';
        previewEl.style.display = 'none';
        if (nameEl) nameEl.style.display = '';
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

    function bindUpload(fileEl, nameEl, hiddenEl, previewEl, roleLabel) {
        if (!fileEl) return;
        fileEl.addEventListener('change', function () {
            var nameOut = document.getElementById(fileEl.id + 'Name');
            var file = fileEl.files && fileEl.files[0];
            if (nameOut) {
                if (file) {
                    nameOut.textContent = file.name;
                    nameOut.classList.remove('hidden');
                } else {
                    nameOut.textContent = '';
                    nameOut.classList.add('hidden');
                }
            }
            readFileAsDataUrl(file).then(function (url) {
                if (hiddenEl) hiddenEl.value = url || '';
                syncPreview(nameEl, hiddenEl, previewEl, roleLabel);
            }).catch(function () {});
        });
    }

    dateInputs.forEach(function (input) {
        if (!input) return;
        input.addEventListener('input', function () {
            var digits = String(this.value || '').replace(/\D/g, '').slice(0, 8);
            var parts = [];
            if (digits.length > 0) parts.push(digits.slice(0, 2));
            if (digits.length > 2) parts.push(digits.slice(2, 4));
            if (digits.length > 4) parts.push(digits.slice(4, 8));
            this.value = parts.join('/');
        });
    });

    if (nameInput) {
        nameInput.addEventListener('input', function () {
            syncPreview(nameInput, hidden, preview, 'Admin');
        });
        syncPreview(nameInput, hidden, preview, 'Admin');
    }

    if (checkedNameInput) {
        checkedNameInput.addEventListener('input', function () {
            syncPreview(checkedNameInput, checkedHidden, checkedPreview, 'Admin');
        });
        syncPreview(checkedNameInput, checkedHidden, checkedPreview, 'Admin');
    }

    bindUpload(fileInput, nameInput, hidden, preview, 'Admin');
    bindUpload(checkedFileInput, checkedNameInput, checkedHidden, checkedPreview, 'Admin');

    if (form) {
        form.addEventListener('submit', function (event) {
            var pending = [];
            if (fileInput && fileInput.files && fileInput.files[0] && hidden && String(hidden.value || '').indexOf('data:image/') !== 0) {
                pending.push(readFileAsDataUrl(fileInput.files[0]).then(function (url) {
                    if (url) hidden.value = url;
                }));
            }
            if (checkedFileInput && checkedFileInput.files && checkedFileInput.files[0] && checkedHidden && String(checkedHidden.value || '').indexOf('data:image/') !== 0) {
                pending.push(readFileAsDataUrl(checkedFileInput.files[0]).then(function (url) {
                    if (url) checkedHidden.value = url;
                }));
            }
            if (!pending.length) return;
            event.preventDefault();
            Promise.all(pending).then(function () {
                form.submit();
            }).catch(function () {
                form.submit();
            });
        });
    }
})();
</script>
@endif
