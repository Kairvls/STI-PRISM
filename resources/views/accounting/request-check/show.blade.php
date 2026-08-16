@extends('layouts.accounting-layout')

@section('title', 'Review Request Check')

@section('content')
@include('accounting.partials.flash')
<div class="flex flex-wrap items-start justify-between gap-4 fade-in">
    <div>
        <a href="/accounting/request-check" class="text-xs font-semibold text-gray-500 hover:text-gray-900">← Request Check queue</a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">{{ $rfc->request_check_form_number ?? ('RFC-'.$rfc->request_check_id) }}</h1>
        <p class="text-sm text-gray-500">Official Request for Check. Funds are collected in person.</p>
    </div>
    <div class="acc-actions">
        @if ($reviewable)
            <form method="POST" action="/accounting/request-check/{{ $rfc->request_check_id }}/approve" onsubmit="return confirm('Approve this Request Check?');">
                @csrf
                <button class="acc-btn acc-btn-approve">Approve</button>
            </form>
            <button type="button" onclick="document.getElementById('revise-box').classList.toggle('hidden')" class="acc-btn acc-btn-revise">Request revision</button>
        @endif
        @if ($releasable)
            <form method="POST" action="/accounting/request-check/{{ $rfc->request_check_id }}/release-funds" onsubmit="return confirm('Mark funds as ready for personal collection?');">
                @csrf
                <button class="acc-btn acc-btn-funds">Release funds</button>
            </form>
        @endif
        @include('accounting.partials.status-badge', ['status' => !empty($rfc->request_check_funds_released_at) ? 'Released' : $rfc->request_check_status])
    </div>
</div>

<form id="revise-box" method="POST" action="/accounting/request-check/{{ $rfc->request_check_id }}/revise" class="acc-modal mt-4 hidden rounded-xl border border-amber-200 bg-amber-50 p-4">
    @csrf
    <h3 class="text-sm font-bold text-amber-900">Request revision</h3>
    <textarea name="remarks" required rows="3" class="mt-2 w-full rounded-xl border border-amber-200 bg-white p-3 text-sm"></textarea>
    <div class="mt-3 flex justify-end gap-2">
        <button type="button" onclick="document.getElementById('revise-box').classList.add('hidden')" class="acc-btn acc-btn-ghost">Cancel</button>
        <button class="acc-btn acc-btn-revise">Send to Purchaser</button>
    </div>
</form>

@if (!empty($rfc->request_check_funds_released_at))
    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
        Funds ready for personal collection since {{ \Carbon\Carbon::parse($rfc->request_check_funds_released_at)->format('M d, Y g:i A') }}.
    </div>
@endif

<div class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,1fr)_300px]">
    <div>
        <div class="acc-viewer rounded-xl border border-gray-200 p-4">
            @include('partials.request-check-paper', ['editable' => false, 'rfc' => $rfc])
        </div>
        @if ($attachments->isNotEmpty())
            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-bold text-gray-900">Supporting documents</h3>
                <ul class="mt-2 space-y-1 text-sm">
                    @foreach ($attachments as $file)
                        <li>
                            <a class="font-medium text-gray-900 hover:text-amber-600" href="/accounting/request-check/{{ $rfc->request_check_id }}/attachments/{{ $file->request_check_attachment_id }}">
                                {{ $file->request_check_attachment_original_name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
    <div class="space-y-4">
        @include('accounting.partials.related-docs', ['chain' => $chain])
        @include('accounting.partials.history', ['history' => $history])
        @if (!empty($rfc->request_check_revision_notes))
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">Last revision note: {{ $rfc->request_check_revision_notes }}</div>
        @endif
    </div>
</div>
@endsection
