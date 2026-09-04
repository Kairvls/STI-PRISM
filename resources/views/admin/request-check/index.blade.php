@extends('layouts.admin-layout')

@section('content')

<div
    x-data="{
        viewOpen: false,
        selectedRfc: null,
        reviseOpen: false,
        rejectOpen: false,
        remarksRfc: null,
        openView(id) { this.selectedRfc = id; this.viewOpen = true; },
        openRevise(id) { this.remarksRfc = id; this.reviseOpen = true; this.rejectOpen = false; },
        openReject(id) { this.remarksRfc = id; this.rejectOpen = true; this.reviseOpen = false; }
    }"
    class="space-y-6"
>
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Request for Check Approval</h1>
        <p class="mt-1 text-sm text-gray-600">Sign as Administrator after Accounting has verified the request.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <a href="{{ route('admin.rfc.index', ['status' => 'pending']) }}" class="rounded-xl border border-gray-200 bg-white p-5 {{ $filter === 'pending' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Pending signature</p>
            <p class="mt-3 text-3xl font-semibold">{{ $counts['pending'] }}</p>
        </a>
        <a href="{{ route('admin.rfc.index', ['status' => 'approved']) }}" class="rounded-xl border border-gray-200 bg-white p-5 {{ $filter === 'approved' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Approved</p>
            <p class="mt-3 text-3xl font-semibold">{{ $counts['approved'] }}</p>
        </a>
        <a href="{{ route('admin.rfc.index', ['status' => 'rejected']) }}" class="rounded-xl border border-gray-200 bg-white p-5 {{ $filter === 'rejected' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Rejected</p>
            <p class="mt-3 text-3xl font-semibold">{{ $counts['rejected'] }}</p>
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="w-full min-w-[900px] text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">RFC No.</th>
                    <th class="px-4 py-3">ATP</th>
                    <th class="px-4 py-3">Payee</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rfcs as $rfc)
                    @php
                        $reviewable = $rfc->request_check_status === 'Pending Admin Approval'
                            || ($rfc->request_check_status === 'Under Review' && $rfc->request_check_review_stage === 'admin');
                    @endphp
                    <tr>
                        <td class="px-4 py-4 font-medium">{{ $rfc->request_check_form_number }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $rfc->authority_purchase_form_number ?? '—' }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $rfc->request_check_payee ?: '—' }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $rfc->request_check_amount_figures !== null ? '₱'.number_format((float) $rfc->request_check_amount_figures, 2) : '—' }}</td>
                        <td class="px-4 py-4">{{ $rfc->request_check_status }}</td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="openView({{ $rfc->request_check_id }}); fetch('{{ route('admin.rfc.start-review', $rfc->request_check_id) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}})" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700">View</button>
                                @if($reviewable)
                                    <form method="POST" action="{{ route('admin.rfc.approve', $rfc->request_check_id) }}">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Approve and sign as Administrator?')" class="rounded-lg bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700">Approve</button>
                                    </form>
                                    <button type="button" @click="openRevise({{ $rfc->request_check_id }})" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700">Revise</button>
                                    <button type="button" @click="openReject({{ $rfc->request_check_id }})" class="rounded-lg border border-red-300 px-3 py-2 text-xs font-medium text-red-700">Reject</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">No Request for Check records in this queue.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $rfcs->links() }}</div>

    @foreach($rfcs as $rfc)
        @php $rfcFiles = $attachments->get($rfc->request_check_id, collect()); @endphp
        <div x-show="viewOpen && selectedRfc === {{ $rfc->request_check_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/40" @click="viewOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div @click.stop class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl">
                    <div class="flex justify-between border-b px-6 py-5">
                        <div>
                            <h3 class="text-xl font-semibold">{{ $rfc->request_check_form_number }}</h3>
                            <p class="text-sm text-gray-500">ATP: {{ $rfc->authority_purchase_form_number ?? '—' }}</p>
                        </div>
                        <button type="button" @click="viewOpen = false">✕</button>
                    </div>
                    <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                        @include('partials.request-check-paper', ['editable' => false, 'rfc' => $rfc])
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div x-show="reviseOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <form method="POST" :action="'/admin/request-check/' + remarksRfc + '/revise'" @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">
            @csrf
            <h3 class="text-lg font-semibold">Request revision</h3>
            <textarea name="remarks" required class="mt-4 w-full rounded-lg border border-gray-300 p-3 text-sm" rows="4"></textarea>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="reviseOpen = false" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm text-white">Send back</button>
            </div>
        </form>
    </div>

    <div x-show="rejectOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <form method="POST" :action="'/admin/request-check/' + remarksRfc + '/reject'" @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">
            @csrf
            <h3 class="text-lg font-semibold">Reject Request for Check</h3>
            <textarea name="remarks" required class="mt-4 w-full rounded-lg border border-gray-300 p-3 text-sm" rows="4"></textarea>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="rejectOpen = false" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white">Reject</button>
            </div>
        </form>
    </div>
</div>

<style>[x-cloak]{display:none!important}</style>
@endsection
