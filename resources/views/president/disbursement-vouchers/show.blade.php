@extends('layouts.president-layout')

@section('title', $dv->dv_form_number ?: 'Disbursement Voucher')

@section('content')
@include('accounting.partials.flash')
@include('accounting.partials.print-form')

@php
    $rfc = $package['rfc'] ?? null;
    $requestNo = $dv->dv_request_no ?? ($rfc->request_check_request_no ?? null);
@endphp

<div class="mx-auto max-w-[900px] space-y-4 p-6">
    <div class="print-hidden flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('president.dv.index') }}" class="text-sm text-slate-500 hover:text-[#0025cc]">← Disbursement Vouchers</a>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ $dv->dv_form_number ?: 'Disbursement Voucher' }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Status: <span class="font-medium text-slate-800">{{ $dv->dv_status }}</span>
                @if($requestNo)
                    · @include('partials.procurement-request-no', ['requestNo' => $requestNo, 'inline' => true])
                @endif
            </p>
            <p class="mt-1 text-xs text-slate-400">
                @if(filled(optional($rfc)->request_check_form_number) || !empty($package['atps']))
                    Request:
                    {{ optional($rfc)->request_check_form_number }}
                    @foreach($package['atps'] ?? [] as $atp)
                        @if(filled($atp->authority_purchase_form_number ?? null))
                            · {{ $atp->authority_purchase_form_number }}
                        @endif
                    @endforeach
                @endif
            </p>
            @if(!empty($dv->dv_president_revision_reason) && ($dv->dv_status ?? '') === 'Approved')
                <p class="mt-2 text-sm text-amber-700">Previous return: {{ $dv->dv_president_revision_reason }}</p>
            @endif
        </div>
        <button
            type="button"
            onclick="window.accountingPrintForm && window.accountingPrintForm({ sheetId: 'dv-print-{{ $dv->dv_id }}', page: 'portrait' })"
            class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >Print</button>
    </div>

    @include('partials.disbursement-voucher-paper', [
        'dv' => $dv,
        'editable' => false,
        'printId' => 'dv-print-'.$dv->dv_id,
    ])

    @if($reviewable)
        <div class="print-hidden flex flex-wrap gap-3 rounded-xl border border-slate-200 bg-white p-4">
            <form method="POST" action="{{ route('president.dv.approve', $dv->dv_id) }}">
                @csrf
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Approve (President signature)
                </button>
            </form>
            <form method="POST" action="{{ route('president.dv.revise', $dv->dv_id) }}" class="flex flex-1 flex-wrap items-end gap-2">
                @csrf
                <div class="min-w-[200px] flex-1">
                    <label class="block text-xs font-medium text-slate-600">Return reason</label>
                    <input type="text" name="remarks" required maxlength="2000" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="What should Accounting fix?">
                </div>
                <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-900 hover:bg-amber-100">Send back</button>
            </form>
        </div>
    @endif
</div>
@endsection
