@extends('layouts.receiving-layout')

@section('content')
<div
    x-data="{
        viewOpen: false,
        selectedRr: null,
        reviseOpen: false,
        returnOpen: false,
        remarksRr: null,
        openView(id) { this.selectedRr = id; this.viewOpen = true; },
        openRevise(id) { this.remarksRr = id; this.reviseOpen = true; this.returnOpen = false; },
        openReturn(id) { this.remarksRr = id; this.returnOpen = true; this.reviseOpen = false; },
        printRr(id) {
            document.querySelectorAll('.rr-print-sheet').forEach(function (sheet) {
                sheet.classList.remove('rr-print-active');
            });
            const sheet = document.getElementById('rr-print-' + id);
            if (sheet) sheet.classList.add('rr-print-active');
            window.print();
        }
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
        <h2 class="text-2xl font-semibold text-slate-900">Receiving Reports</h2>
        <p class="text-sm text-slate-600">Confirm Second Count when delivered items match the report.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <a href="{{ route('receiving.rr.index', ['status' => 'queue']) }}" class="rounded-xl border bg-white p-5 {{ $filter === 'queue' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">For Second Count</p>
            <p class="mt-3 text-3xl font-semibold">{{ $counts['queue'] }}</p>
        </a>
        <a href="{{ route('receiving.rr.index', ['status' => 'completed']) }}" class="rounded-xl border bg-white p-5 {{ $filter === 'completed' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Completed</p>
            <p class="mt-3 text-3xl font-semibold">{{ $counts['completed'] }}</p>
        </a>
        <a href="{{ route('receiving.rr.index', ['status' => 'returned']) }}" class="rounded-xl border bg-white p-5 {{ $filter === 'returned' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Returned</p>
            <p class="mt-3 text-3xl font-semibold">{{ $counts['returned'] }}</p>
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white">
        <table class="w-full min-w-[900px] text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">RR No.</th>
                    <th class="px-4 py-3">RFC</th>
                    <th class="px-4 py-3">Received from</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($reports as $rr)
                    @php $reviewable = in_array($rr->receiving_report_status, ['Pending','Submitted','Resubmitted','Under Review'], true); @endphp
                    <tr>
                        <td class="px-4 py-4 font-medium">{{ $rr->receiving_report_form_number }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $rr->request_check_form_number ?? '—' }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $rr->receiving_report_received_from ?? '—' }}</td>
                        <td class="px-4 py-4">{{ $rr->receiving_report_status }}</td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="openView({{ $rr->receiving_report_id }}); fetch('{{ route('receiving.rr.start-review', $rr->receiving_report_id) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}})" class="rounded-lg border px-3 py-2 text-xs">View</button>
                                <button type="button" @click="printRr({{ $rr->receiving_report_id }})" class="rounded-lg border px-3 py-2 text-xs">Print</button>
                                @if($reviewable)
                                    <form method="POST" action="{{ route('receiving.rr.second-count', $rr->receiving_report_id) }}">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Confirm Second Count? Items arrived correctly?')" class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700">Second Count</button>
                                    </form>
                                    <button type="button" @click="openRevise({{ $rr->receiving_report_id }})" class="rounded-lg border border-amber-300 px-3 py-2 text-xs text-amber-700">Revise</button>
                                    <button type="button" @click="openReturn({{ $rr->receiving_report_id }})" class="rounded-lg border border-red-300 px-3 py-2 text-xs text-red-700">Return</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500">No receiving reports in this queue.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $reports->links() }}</div>

    @foreach($reports as $rr)
        @php $rrItems = $items->get($rr->receiving_report_id, collect())->values(); @endphp
        <div x-show="viewOpen && selectedRr === {{ $rr->receiving_report_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/40" @click="viewOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div @click.stop class="relative w-full max-w-5xl rounded-2xl bg-white shadow-xl">
                    <div class="flex justify-between border-b px-6 py-5">
                        <div>
                            <h3 class="text-xl font-semibold">{{ $rr->receiving_report_form_number }}</h3>
                            <p class="text-sm text-gray-500">RFC: {{ $rr->request_check_form_number ?? '—' }}</p>
                        </div>
                        <button type="button" @click="viewOpen = false">✕</button>
                    </div>
                    <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                        @include('partials.receiving-report-paper', ['editable' => false, 'rr' => $rr, 'rows' => $rrItems, 'printId' => 'rr-print-'.$rr->receiving_report_id])
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div x-show="reviseOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <form method="POST" :action="'/receiving/reports/' + remarksRr + '/revise'" @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">
            @csrf
            <h3 class="text-lg font-semibold">Request revision</h3>
            <textarea name="remarks" required rows="4" class="mt-4 w-full rounded-lg border p-3 text-sm"></textarea>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="reviseOpen = false" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm text-white">Send back</button>
            </div>
        </form>
    </div>

    <div x-show="returnOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <form method="POST" :action="'/receiving/reports/' + remarksRr + '/return'" @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">
            @csrf
            <h3 class="text-lg font-semibold">Return delivery</h3>
            <p class="mt-1 text-sm text-gray-500">Items did not arrive correctly.</p>
            <textarea name="remarks" required rows="4" class="mt-4 w-full rounded-lg border p-3 text-sm"></textarea>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="returnOpen = false" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white">Return</button>
            </div>
        </form>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    @media print {
        body * { visibility: hidden !important; }
        .rr-print-active, .rr-print-active * { visibility: visible !important; }
        .rr-print-active { position: absolute !important; left: 0 !important; top: 0 !important; width: 210mm !important; box-shadow: none !important; }
        @page { size: A4 portrait; margin: 10mm; }
    }
</style>
@endsection
