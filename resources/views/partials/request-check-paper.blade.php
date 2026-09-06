@php
    $editable = $editable ?? false;
    $rfc = $rfc ?? null;
    $dateValue = old('request_check_date', $rfc?->request_check_date ?? ($editable && !$rfc ? now()->toDateString() : ''));
    $payeeValue = old('request_check_payee', $rfc?->request_check_payee ?? '');
    $amountValue = old('request_check_amount_figures', $rfc?->request_check_amount_figures ?? '');
    $purposeValue = old('request_check_particulars_purpose', $rfc?->request_check_particulars_purpose ?? '');
    $requestedByValue = old(
        'request_check_requested_by',
        $rfc?->request_check_requested_by ?? ($editable ? (auth()->user()->user_full_name ?? '') : '')
    );
    $requestedBySignature = old(
        'request_check_requested_by_signature',
        $rfc?->request_check_requested_by_signature ?? ''
    );
    $signKey = $signKey ?? ($rfc?->request_check_id ? 'rfc-'.$rfc->request_check_id : 'rfc-create');
    $approvedBy = $rfc?->request_check_approved_by_signature ?? $rfc?->request_check_approved_by_admin ?? '';
    $printClass = $printClass ?? '';
    $printId = $printId ?? null;
@endphp

<div
    @if($printId) id="{{ $printId }}" @endif
    class="rfc-print-sheet mx-auto w-[297mm] max-w-full bg-white px-16 pb-5 pt-10 text-[15px] text-black shadow {{ $printClass }}"
    style="min-height: 0; height: auto;"
>
    <div class="text-center">
        <div class="text-2xl font-bold tracking-wide">STI COLLEGE- ORMOC, INC.</div>
        <div class="mt-1 text-xl font-bold italic tracking-wide">REQUEST FOR CHECK</div>
    </div>

    <div class="mt-8 flex justify-end">
        <div class="flex w-72 items-end gap-2">
            <span class="font-semibold">Date:</span>
            @if($editable)
                <input type="date" name="request_check_date" value="{{ $dateValue }}" class="h-8 flex-1 border-0 border-b border-black bg-transparent px-1 outline-none">
            @else
                <span class="flex-1 border-b border-black px-1 pb-0.5">{{ $dateValue ? \Carbon\Carbon::parse($dateValue)->format('d/m/Y') : '' }}</span>
            @endif
        </div>
    </div>

    <div class="mt-10 space-y-8">
        <div class="flex items-end gap-3">
            <span class="w-24 shrink-0 font-semibold">Payee:</span>
            @if($editable)
                <input type="text" name="request_check_payee" value="{{ $payeeValue }}" class="h-8 flex-1 border-0 border-b border-black bg-transparent px-1 outline-none">
            @else
                <span class="flex-1 border-b border-black px-1 pb-0.5">{{ $payeeValue }}</span>
            @endif
        </div>

        <div class="flex items-end gap-3">
            <span class="w-24 shrink-0 font-semibold">Amount:</span>
            @if($editable)
                <input type="number" step="0.01" min="0" name="request_check_amount_figures" value="{{ $amountValue }}" class="h-8 flex-1 border-0 border-b border-black bg-transparent px-1 outline-none">
            @else
                <span class="flex-1 border-b border-black px-1 pb-0.5">
                    {{ $amountValue !== '' && $amountValue !== null ? '₱' . number_format((float) $amountValue, 2) : '' }}
                </span>
            @endif
        </div>

        <div>
            <div class="flex items-end gap-3">
                <span class="w-24 shrink-0 font-semibold">For:</span>
                @if($editable)
                    <textarea name="request_check_particulars_purpose" rows="2" class="flex-1 resize-none border-0 border-b border-black bg-transparent px-1 outline-none">{{ $purposeValue }}</textarea>
                @else
                    <span class="flex-1 border-b border-black px-1 pb-0.5 min-h-[1.5rem]">{{ $purposeValue }}</span>
                @endif
            </div>
            @unless($editable)
                <div class="mt-8 border-b border-black"></div>
            @endunless
        </div>
    </div>

    <div class="mt-12 grid grid-cols-2 gap-16">
        <div class="text-left">
            <div class="font-semibold">Requested by:</div>
            @if($editable)
                <div
                    id="purSigPreview-{{ $signKey }}"
                    class="relative mt-6 flex min-h-[2.5rem] w-64 items-end justify-center border-b border-black pb-1"
                    style="display:none;"
                ></div>
                <input
                    type="text"
                    name="request_check_requested_by"
                    id="purSigName-{{ $signKey }}"
                    value="{{ $requestedByValue }}"
                    maxlength="255"
                    autocomplete="off"
                    class="mt-8 block w-64 border-0 border-b border-black bg-transparent text-center outline-none"
                >
                <input
                    type="hidden"
                    name="request_check_requested_by_signature"
                    id="purSigImage-{{ $signKey }}"
                    value="{{ \App\Support\RisWorkflow::isDrawnSignature((string) $requestedBySignature) ? $requestedBySignature : '' }}"
                >
                <div class="mt-1 w-64 text-[10px] text-slate-500">Signature overlays printed name · use panel below</div>
            @else
                <div class="mt-8 w-64 border-b border-black pb-1 min-h-[1.75rem]">
                    @include('partials.drawn-signature', [
                        'value' => $requestedBySignature,
                        'printedName' => $requestedByValue,
                        'empty' => $requestedByValue,
                    ])
                </div>
            @endif
        </div>
        <div class="flex justify-end">
            <div class="w-64 text-left">
                <div class="font-semibold">Approved by:</div>
                <div class="mt-8 w-full border-b border-black pb-1 min-h-[1.75rem]">
                    @include('partials.drawn-signature', [
                        'value' => $approvedBy,
                        'printedName' => \App\Support\AccountingSigner::forRfc($rfc ?? null),
                    ])
                </div>
                <div class="mt-1 font-medium">{{ \App\Support\AccountingSigner::forRfc($rfc ?? null) ?: 'Accounting' }}</div>
            </div>
        </div>
    </div>
</div>

<style>
    .rfc-print-sheet {
        min-height: 0 !important;
        height: auto !important;
    }

    @media print {
        .rfc-print-sheet {
            min-height: 0 !important;
            height: auto !important;
            box-shadow: none !important;
        }
    }
</style>
