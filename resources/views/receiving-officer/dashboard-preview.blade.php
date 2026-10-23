@extends('layouts.document-viewer', ['title' => $title ?? 'Form'])

@section('document')
    @if (($kind ?? '') === 'rfc')
        @include('partials.request-check-paper', [
            'editable' => false,
            'rfc' => $rfc,
        ])
    @endif
@endsection
