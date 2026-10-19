@extends('layouts.accounting-layout')

@section('title', $dv->dv_form_number ?: ((($dv->dv_status ?? '') === 'Draft') ? 'Draft DV' : 'Disbursement Voucher'))


@section('content')
@include('accounting.partials.flash')

@php
    $rfc = $package['rfc'] ?? null;
    $editable = in_array((string) ($dv->dv_status ?? ''), ['Draft', 'Revision'], true);
@endphp

<div class="acx mx-auto max-w-[880px] space-y-4">
    <div class="print-hidden flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('accounting.dv.index') }}" class="text-sm text-slate-500 hover:text-[#0025cc]">← Requests</a>
            <h1 class="acx-h mt-2 text-2xl font-semibold">{{ $dv->dv_form_number ?: ((($dv->dv_status ?? '') === 'Draft') ? 'Draft DV' : 'Disbursement Voucher') }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Status: <span class="font-medium text-slate-800">{{ $dv->dv_status }}</span>
                @if(!empty($dv->dv_revision_reason))
                    <span class="text-amber-700"> · {{ $dv->dv_revision_reason }}</span>
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
        </div>
        <button
            type="button"
            onclick="window.accountingPrintForm && window.accountingPrintForm({ sheetId: 'dv-print-{{ $dv->dv_id }}', page: 'portrait' })"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
            <i data-lucide="printer" class="h-4 w-4"></i>
            Print
        </button>
    </div>

    @if($editable)
        @include('partials.purchaser-confirm-dialog')
        <form
            method="POST"
            action="{{ route('accounting.dv.update', $dv->dv_id) }}"
            id="dv-edit-form"
            data-pur-confirm-title="Submit DV"
            data-pur-confirm-ok="Submit"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="save_action" id="dv-save-action" value="draft">

            @include('partials.disbursement-voucher-paper', [
                'dv' => $dv,
                'editable' => true,
                'printId' => 'dv-print-'.$dv->dv_id,
            ])

            <div class="print-hidden mt-4 flex flex-wrap gap-2">
                <button
                    type="submit"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    onclick="document.getElementById('dv-save-action').value='draft'; this.form.removeAttribute('data-pur-confirm'); this.form.removeAttribute('data-pur-confirm-reviewer-role');"
                >Save draft</button>
                <button
                    type="submit"
                    class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-medium text-white hover:bg-[#001ea3]"
                    onclick="document.getElementById('dv-save-action').value='submit'; this.form.setAttribute('data-pur-confirm','Submit this Disbursement Voucher to the School Administrator?'); this.form.setAttribute('data-pur-confirm-reviewer-role','School Administrator');"
                >Submit to School Administrator</button>
            </div>
        </form>
    @else
        @include('partials.disbursement-voucher-paper', [
            'dv' => $dv,
            'editable' => false,
            'printId' => 'dv-print-'.$dv->dv_id,
        ])

        <div class="print-hidden mt-4 flex flex-wrap gap-2">
            @if(($dv->dv_status ?? '') === 'Approved')
                <form method="POST" action="{{ route('accounting.dv.submit-president', $dv->dv_id) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Submit to President</button>
                </form>
            @endif
            @if(($dv->dv_status ?? '') === 'President Approved')
                <form method="POST" action="{{ route('accounting.dv.release-check', $dv->dv_id) }}" onsubmit="return confirm('Mark this check as released and notify the Purchaser?');">
                    @csrf
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Release check</button>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection
