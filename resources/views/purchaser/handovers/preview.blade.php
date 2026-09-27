@extends('layouts.document-viewer', ['title' => $title])

@section('document')
    <div class="!shadow-none mb-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm text-slate-700">
        <span class="font-semibold text-slate-900">Read-only preview.</span>
        Passed to you by {{ $handover->from_name }}. Accept it to edit, sign, and submit.
    </div>

    @foreach($documents as $doc)
        <div class="{{ $loop->last ? '' : 'mb-6' }}">
            @if(count($documents) > 1)
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $doc['title'] ?? 'ATP' }}</p>
            @endif

            @switch($doc['type'])
                @case('ris')
                    @include('partials.ris-document-paper-styles', [
                        'ris' => $doc['ris'],
                        'risItems' => $doc['risItems'],
                        'presidentName' => $doc['presidentName'] ?? 'President',
                        'isScreenPreview' => true,
                    ])
                    @break

                @case('atp')
                    @include('partials.authority-to-purchase-paper', [
                        'editable' => false,
                        'atp' => $doc['atp'],
                        'items' => $doc['items'],
                    ])
                    @break
            @endswitch
        </div>
    @endforeach
@endsection
