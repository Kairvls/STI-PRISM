@extends('layouts.accounting-layout')
@section('content')
<div x-data="{ viewOpen:false, selectedLiq:null, reviseOpen:false, rejectOpen:false, remarksId:null }" class="space-y-6 p-6">
    @if(session('success'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>@endif
    <div>
        <h2 class="text-2xl font-semibold">Liquidation Reports</h2>
        <p class="text-sm text-slate-600">Check submitted liquidations, then forward to Admin.</p>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <a href="{{ route('accounting.liq.index', ['status'=>'submitted']) }}" class="rounded-xl border bg-white p-5 {{ $filter==='submitted'?'ring-2 ring-slate-900':'' }}"><p class="text-sm text-gray-500">For review</p><p class="mt-3 text-3xl font-semibold">{{ $counts['submitted'] }}</p></a>
        <a href="{{ route('accounting.liq.index', ['status'=>'forwarded']) }}" class="rounded-xl border bg-white p-5 {{ $filter==='forwarded'?'ring-2 ring-slate-900':'' }}"><p class="text-sm text-gray-500">Forwarded / Approved</p><p class="mt-3 text-3xl font-semibold">{{ $counts['forwarded'] }}</p></a>
        <a href="{{ route('accounting.liq.index', ['status'=>'rejected']) }}" class="rounded-xl border bg-white p-5 {{ $filter==='rejected'?'ring-2 ring-slate-900':'' }}"><p class="text-sm text-gray-500">Rejected</p><p class="mt-3 text-3xl font-semibold">{{ $counts['rejected'] }}</p></a>
    </div>
    <div class="overflow-hidden rounded-xl border bg-white">
        <table class="w-full min-w-[900px] text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">No.</th><th class="px-4 py-3">RR</th><th class="px-4 py-3">Employee</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Action</th></tr></thead>
            <tbody class="divide-y">
                @forelse($reports as $liq)
                    @php $reviewable = in_array($liq->liquidation_report_status, ['Submitted','Resubmitted','Under Review'], true); @endphp
                    <tr>
                        <td class="px-4 py-4 font-medium">{{ $liq->liquidation_report_form_number }}</td>
                        <td class="px-4 py-4">{{ $liq->receiving_report_form_number ?? '—' }}</td>
                        <td class="px-4 py-4">{{ $liq->liquidation_report_employee_name }}</td>
                        <td class="px-4 py-4">{{ $liq->liquidation_report_status }}</td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="selectedLiq={{ $liq->liquidation_report_id }}; viewOpen=true; fetch('{{ route('accounting.liq.start-review', $liq->liquidation_report_id) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}})" class="rounded-lg border px-3 py-2 text-xs">View</button>
                                <a href="{{ route('accounting.liq.export-xlsx', $liq->liquidation_report_id) }}" class="rounded-lg border px-3 py-2 text-xs">Excel</a>
                                @if($reviewable)
                                    <form method="POST" action="{{ route('accounting.liq.check', $liq->liquidation_report_id) }}">@csrf<button class="rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-700" onclick="return confirm('Check and forward to Admin?')">Check</button></form>
                                    <button type="button" @click="remarksId={{ $liq->liquidation_report_id }}; reviseOpen=true" class="rounded-lg border border-amber-300 px-3 py-2 text-xs text-amber-700">Revise</button>
                                    <button type="button" @click="remarksId={{ $liq->liquidation_report_id }}; rejectOpen=true" class="rounded-lg border border-red-300 px-3 py-2 text-xs text-red-700">Reject</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500">No liquidation reports in this queue.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $reports->links() }}</div>
    @foreach($reports as $liq)
        @php $liqItems = $items->get($liq->liquidation_report_id, collect())->values(); @endphp
        <div x-show="viewOpen && selectedLiq === {{ $liq->liquidation_report_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/40" @click="viewOpen=false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div @click.stop class="w-full max-w-6xl rounded-2xl bg-white shadow-xl">
                    <div class="flex justify-between border-b px-6 py-5"><h3 class="text-xl font-semibold">{{ $liq->liquidation_report_form_number }}</h3><button type="button" @click="viewOpen=false">✕</button></div>
                    <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">@include('partials.liquidation-report-paper', ['editable'=>false,'liq'=>$liq,'rows'=>$liqItems])</div>
                </div>
            </div>
        </div>
    @endforeach
    <div x-show="reviseOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <form method="POST" :action="'/accounting/liquidation-reports/'+remarksId+'/revise'" @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">@csrf<textarea name="remarks" required rows="4" class="w-full rounded-lg border p-3 text-sm"></textarea><div class="mt-4 flex justify-end gap-2"><button type="button" @click="reviseOpen=false" class="rounded-lg border px-4 py-2 text-sm">Cancel</button><button class="rounded-lg bg-amber-600 px-4 py-2 text-sm text-white">Send back</button></div></form>
    </div>
    <div x-show="rejectOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <form method="POST" :action="'/accounting/liquidation-reports/'+remarksId+'/reject'" @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">@csrf<textarea name="remarks" required rows="4" class="w-full rounded-lg border p-3 text-sm"></textarea><div class="mt-4 flex justify-end gap-2"><button type="button" @click="rejectOpen=false" class="rounded-lg border px-4 py-2 text-sm">Cancel</button><button class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white">Reject</button></div></form>
    </div>
</div>
<style>[x-cloak]{display:none!important}</style>
@endsection
