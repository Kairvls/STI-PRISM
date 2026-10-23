@extends('layouts.document-viewer', ['title' => $title ?? 'Form'])

@section('document')
    @if (($kind ?? '') === 'dv')
        @include('partials.disbursement-voucher-paper', [
            'dv' => $dv,
            'editable' => false,
        ])
    @elseif (($kind ?? '') === 'atp')
        @include('partials.authority-to-purchase-paper', [
            'editable' => false,
            'atp' => $atp,
            'items' => $items ?? collect(),
        ])
    @endif
@endsection
