{{-- One-time display of a reporter PIN right after it was generated or set. --}}
@php
    $pinResult = session('reporter_pin_result');
    $pinBulk = session('reporter_pin_bulk_result');
@endphp

@if ($pinResult || $pinBulk)
    <div
        id="reporterPinResultModal"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
            <div class="max-h-[78vh] overflow-y-auto px-6 pb-4 pt-6">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-[#0025cc]">
                    <i data-lucide="key-round" class="h-5 w-5"></i>
                </div>

                @if ($pinResult)
                    <h2 class="mt-4 text-lg font-semibold text-slate-900">Reporter PIN</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $pinResult['name'] }} &middot; <span class="font-mono">{{ $pinResult['employee_id'] }}</span>
                    </p>

                    <div class="mt-4 rounded-xl bg-slate-50 px-4 py-4 text-center ring-1 ring-slate-200/70">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">PIN</p>
                        <p class="mt-1 font-mono text-3xl font-bold tracking-[0.35em] text-[#0025cc]">{{ $pinResult['pin'] }}</p>
                    </div>

                    @if ($pinResult['emailed'])
                        <p class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            Emailed to {{ $pinResult['email'] }}.
                        </p>
                    @else
                        <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Not emailed{{ $pinResult['email'] ? ' (sending failed or was turned off)' : ' (no email on file)' }}.
                            Give this PIN to the reporter privately.
                        </p>
                    @endif
                @endif

                @if ($pinBulk)
                    <h2 class="mt-4 text-lg font-semibold text-slate-900">PINs created</h2>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-emerald-50 px-4 py-3">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-emerald-700">Emailed</p>
                            <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $pinBulk['emailed'] }}</p>
                        </div>
                        <div class="rounded-xl bg-amber-50 px-4 py-3">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-amber-700">Hand over in person</p>
                            <p class="mt-1 text-2xl font-semibold text-amber-900">{{ count($pinBulk['hand_over']) }}</p>
                        </div>
                    </div>

                    @if (count($pinBulk['hand_over']) > 0)
                        <p class="mt-4 text-sm text-slate-500">
                            These reporters have no email on file or the email failed. Copy their PINs now and give them privately.
                        </p>
                        <div class="mt-3 max-h-64 overflow-y-auto rounded-xl border border-slate-200">
                            <table class="w-full text-left text-sm">
                                <thead class="sticky top-0 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <th class="px-4 py-2">Reporter</th>
                                        <th class="px-4 py-2">Employee ID</th>
                                        <th class="px-4 py-2">PIN</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($pinBulk['hand_over'] as $row)
                                        <tr>
                                            <td class="px-4 py-2.5 text-slate-700">{{ $row['name'] }}</td>
                                            <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $row['employee_id'] }}</td>
                                            <td class="px-4 py-2.5 font-mono font-bold tracking-widest text-[#0025cc]">{{ $row['pin'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif

                <p class="mt-4 text-xs text-slate-400">
                    PINs are saved in a form that can't be read back, so this is the only time it is shown. If one is lost, set a new PIN.
                </p>
            </div>

            <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                <button
                    type="button"
                    onclick="document.getElementById('reporterPinResultModal').remove();"
                    class="h-10 rounded-lg bg-[#0025cc] px-4 text-sm font-medium text-white"
                >
                    Done
                </button>
            </div>
        </div>
    </div>
@endif
