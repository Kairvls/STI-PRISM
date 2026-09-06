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
    class="rfc-print-sheet mx-auto w-full max-w-[1095px] bg-white px-16 pb-5 pt-10 text-[15px] text-black shadow {{ $printClass }}"
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
                <div class="relative mt-6 w-64">
                    <img
                        id="purSigOverlay-{{ $signKey }}"
                        alt=""
                        class="pointer-events-none absolute bottom-2 left-1/2 z-[2] max-h-10 w-auto max-w-[92%] -translate-x-1/2 object-contain"
                        style="display:none;"
                    >
                    <input
                        type="text"
                        name="request_check_requested_by"
                        id="purSigName-{{ $signKey }}"
                        value="{{ $requestedByValue }}"
                        maxlength="255"
                        autocomplete="off"
                        class="relative z-[1] block w-full min-h-[2.5rem] border-0 border-b border-black bg-transparent pb-1 text-center text-sm outline-none"
                    >
                    <input
                        type="hidden"
                        name="request_check_requested_by_signature"
                        id="purSigImage-{{ $signKey }}"
                        value="{{ \App\Support\RisWorkflow::isDrawnSignature((string) $requestedBySignature) ? $requestedBySignature : '' }}"
                    >
                </div>
            @else
                <div class="relative mt-6 w-64 border-b border-black pb-1 min-h-[2.5rem]">
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
                <div
                    class="relative mt-6 w-full min-h-[2.5rem] border-b border-black pb-1 flex items-end justify-center"
                    @if(!empty($accLiveSign)) id="accPaperSigTarget" @endif
                >
                    @if(!empty($accLiveSign) && !\App\Support\RisWorkflow::isDrawnSignature((string) ($approvedBy ?? '')))
                        <img
                            id="accPaperSigOverlay"
                            alt=""
                            class="pointer-events-none absolute bottom-2 left-1/2 z-[2] max-h-10 w-auto max-w-[92%] -translate-x-1/2 object-contain"
                            style="display:none;"
                        >
                        <span id="accPaperSigPrintedName" class="relative z-[1] text-center text-xs font-medium leading-5">
                            {{ \App\Support\AccountingSigner::currentUserName() ?: 'Accountant' }}
                        </span>
                    @else
                        @include('partials.drawn-signature', [
                            'value' => $approvedBy,
                            'printedName' => \App\Support\AccountingSigner::forRfc($rfc ?? null),
                        ])
                    @endif
                </div>
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
