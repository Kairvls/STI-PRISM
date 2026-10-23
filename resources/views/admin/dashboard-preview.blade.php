@extends('layouts.document-viewer', ['title' => $title])

@push('head')
    <style>
        html, body {
            min-height: 0 !important;
            height: auto !important;
            background: #dbeafe !important;
            padding: 16px 12px !important;
        }
    </style>
@endpush

@section('document')
<article class="mx-auto w-full max-w-3xl bg-white px-8 py-7 text-slate-900">
    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Record</p>
    <h1 class="mt-1 text-lg font-semibold">{{ $title }}</h1>
    <dl class="mt-6 divide-y divide-slate-200 border-y border-slate-200">
        @foreach($fields as $field)
            <div class="grid grid-cols-1 gap-1 py-3 text-sm sm:grid-cols-[9.5rem_minmax(0,1fr)] sm:gap-4">
                <dt class="text-slate-500">{{ $field['label'] }}</dt>
                <dd class="whitespace-pre-wrap font-medium text-slate-900">{{ $field['value'] !== '' ? $field['value'] : '—' }}</dd>
            </div>
        @endforeach
    </dl>
</article>
@endsection
