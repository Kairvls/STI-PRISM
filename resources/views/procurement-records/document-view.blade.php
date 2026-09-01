@extends('layouts.document-viewer', ['title' => $title ?? 'Document Preview'])

@section('document')
    @switch($type)
        @case('ris')
            @include('partials.ris-document-paper-styles', [
                'ris' => $ris,
                'risItems' => $risItems,
                'presidentName' => $presidentName ?? 'President',
                'isScreenPreview' => true,
            ])
            @break

        @case('atp')
            @include('partials.authority-to-purchase-paper', [
                'editable' => false,
                'atp' => $atp,
                'items' => $items,
            ])
            @break

        @case('rfc')
            @include('partials.request-check-paper', [
                'editable' => false,
                'rfc' => $rfc,
            ])
            @break

        @case('rr')
            @include('partials.receiving-report-paper', [
                'editable' => false,
                'rr' => $rr,
                'rows' => $rows,
                'allowMultiSupplier' => $allowMultiSupplier ?? false,
            ])
            @break

        @case('liq')
            @include('partials.liquidation-report-paper', [
                'editable' => false,
                'liq' => $liq,
                'rows' => $items,
            ])
            @break
    @endswitch
@endsection
