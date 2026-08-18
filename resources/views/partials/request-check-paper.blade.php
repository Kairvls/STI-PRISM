@php
    $editable = $editable ?? false;
    $rfc = $rfc ?? null;
    $dateValue = old('request_check_date', $rfc->request_check_date ?? ($editable && !$rfc ? now()->toDateString() : ''));
    $payeeValue = old('request_check_payee', $rfc->request_check_payee ?? '');
    $amountValue = old('request_check_amount_figures', $rfc->request_check_amount_figures ?? '');
    $purposeValue = old('request_check_particulars_purpose', $rfc->request_check_particulars_purpose ?? '');
    $requestedByValue = old('request_check_requested_by', $rfc->request_check_requested_by ?? (auth()->user()->user_full_name ?? ''));
    $approvedBy = $rfc->request_check_approved_by_signature ?? $rfc->request_check_approved_by_admin ?? '';
    $printClass = $printClass ?? '';
    $printId = $printId ?? null;
@endphp

<div
    @if($printId) id="{{ $printId }}" @endif
    class="rfc-print-sheet mx-auto w-[297mm] max-w-full bg-[#d7eef8] px-16 py-10 text-[15px] text-black shadow {{ $printClass }}"
    style="min-height: 210mm;"
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
                <span class="flex-1 border-b border-black px-1 pb-0.5">{{ $dateValue ? \Carbon\Carbon::parse($dateValue)->format('F d, Y') : '' }}</span>
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
                <div class="mt-6 border-b border-black"></div>
            @endunless
        </div>
    </div>

    <div class="mt-20 grid grid-cols-2 gap-16">
        <div class="text-center">
            <div class="font-semibold">Requested by:</div>
            @if($editable)
                <input type="text" name="request_check_requested_by" value="{{ $requestedByValue }}" class="mx-auto mt-10 w-64 border-0 border-b border-black bg-transparent text-center outline-none">
            @else
                <div class="mx-auto mt-10 w-64 border-b border-black pb-1">{{ $requestedByValue }}</div>
            @endif
        </div>
        <div class="text-center">
            <div class="font-semibold">Approved by:</div>
            <div class="mx-auto mt-10 w-64 border-b border-black pb-1 min-h-[1.75rem]">
                @include('partials.drawn-signature', ['value' => $approvedBy])
            </div>
            <div class="mt-1 font-medium">Administrator</div>
        </div>
    </div>
</div>
