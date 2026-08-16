@extends('layouts.accounting-layout')

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
    class="space-y-6 p-6"
>
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div>
        <h2 class="text-2xl font-semibold text-slate-900">Request for Check</h2>
        <p class="text-sm text-slate-600">Verify submitted requests, forward to Admin for signature, then release funds so the purchaser can collect the check.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <a href="{{ route('accounting.rfc.index', ['status' => 'submitted']) }}" class="rounded-xl border border-gray-200 bg-white p-5 {{ $filter === 'submitted' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">For review</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $counts['submitted'] }}</p>
        </a>
        <a href="{{ route('accounting.rfc.index', ['status' => 'forwarded']) }}" class="rounded-xl border border-gray-200 bg-white p-5 {{ $filter === 'forwarded' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Forwarded / Approved</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $counts['forwarded'] }}</p>
        </a>
        <a href="{{ route('accounting.rfc.index', ['status' => 'release']) }}" class="rounded-xl border border-gray-200 bg-white p-5 {{ $filter === 'release' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Ready to release</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $counts['release'] }}</p>
        </a>
        <a href="{{ route('accounting.rfc.index', ['status' => 'rejected']) }}" class="rounded-xl border border-gray-200 bg-white p-5 {{ $filter === 'rejected' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Rejected</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $counts['rejected'] }}</p>
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
                        $reviewable = in_array($rfc->request_check_status, ['Submitted', 'Resubmitted', 'Under Review'], true);
                        $canRelease = $rfc->request_check_status === 'Approved' && empty($rfc->request_check_funds_released_at);
                        $fundsReleased = $rfc->request_check_status === 'Approved' && !empty($rfc->request_check_funds_released_at);
                    @endphp
                    <tr>
                        <td class="px-4 py-4 font-medium">{{ $rfc->request_check_form_number }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $rfc->authority_purchase_form_number ?? '—' }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $rfc->request_check_payee ?: '—' }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $rfc->request_check_amount_figures !== null ? '₱'.number_format((float) $rfc->request_check_amount_figures, 2) : '—' }}</td>
                        <td class="px-4 py-4">
                            @if($fundsReleased)
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Funds released</span>
                            @elseif($canRelease)
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Approved · release funds</span>
                            @else
                                {{ $rfc->request_check_status }}
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    @click="openView({{ $rfc->request_check_id }}); {{ $reviewable ? "fetch('".route('accounting.rfc.start-review', $rfc->request_check_id)."', {method:'POST', headers:{'X-CSRF-TOKEN':'".csrf_token()."','Accept':'application/json'}})" : '' }}"
                                    class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700"
                                >View</button>
                                @if($reviewable)
                                    <form method="POST" action="{{ route('accounting.rfc.verify', $rfc->request_check_id) }}">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Forward this Request for Check to Admin?')" class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700">Verify & forward</button>
                                    </form>
                                    <button type="button" @click="openRevise({{ $rfc->request_check_id }})" class="rounded-lg border border-amber-300 px-3 py-2 text-xs font-medium text-amber-700">Revise</button>
                                    <button type="button" @click="openReject({{ $rfc->request_check_id }})" class="rounded-lg border border-red-300 px-3 py-2 text-xs font-medium text-red-700">Reject</button>
                                @endif
                                @if($canRelease)
                                    <form method="POST" action="{{ route('accounting.rfc.release-funds', $rfc->request_check_id) }}">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Notify the purchaser that funds are ready to collect?')" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white">Release funds</button>
                                    </form>
                                @elseif($fundsReleased)
                                    <span class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs font-medium text-green-700">Funds released</span>
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
                        @foreach($rfcFiles as $file)
                            <p class="mx-auto mt-2 w-[297mm] max-w-full text-sm">
                                <a class="text-blue-700 underline" href="{{ Storage::url($file->request_check_attachment_path) }}" target="_blank">{{ $file->request_check_attachment_original_name }}</a>
                            </p>
                        @endforeach
                    </div>
                    @if($rfc->request_check_status === 'Approved' && empty($rfc->request_check_funds_released_at))
                        <div class="flex justify-end border-t px-6 py-4">
                            <form method="POST" action="{{ route('accounting.rfc.release-funds', $rfc->request_check_id) }}">
                                @csrf
                                <button type="submit" onclick="return confirm('Notify the purchaser that funds are ready to collect?')" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white">Release funds</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <div x-show="reviseOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <form method="POST" :action="'/accounting/request-check/' + remarksRfc + '/revise'" @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">
            @csrf
            <h3 class="text-lg font-semibold">Request revision</h3>
            <textarea name="remarks" required class="mt-4 w-full rounded-lg border border-gray-300 p-3 text-sm" rows="4"></textarea>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="reviseOpen = false" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm text-white">Send back</button>
            </div>
        </form>
    </div>

    <div x-show="rejectOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <form method="POST" :action="'/accounting/request-check/' + remarksRfc + '/reject'" @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">
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
