@php
    $current = $propertyAssignment ?? null;
    $pastAssignments = collect($assignmentHistory ?? [])
        ->reject(fn ($row) => $current && (int) $row->assignment_id === (int) $current->assignment_id);
    $people = collect($assignablePeople ?? []);
    $returnUrl = $equipmentBack['url'] ?? '';
    $fieldClass = 'h-10 w-full rounded-xl border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $textareaClass = 'w-full rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10';
@endphp

<section id="property-assignment" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Accountable person</h2>
            <p class="mt-0.5 text-xs text-slate-500">Who is responsible for this item.</p>
        </div>
        <i data-lucide="user-round-check" class="h-4 w-4 text-slate-400"></i>
    </div>

    <div class="space-y-4 p-5">
        @if ($current)
            <div class="rounded-2xl bg-teal-50/60 px-4 py-3 ring-1 ring-teal-100">
                <a href="{{ url('/maintenance/property-assignments/people/'.$current->assignment_custodian_id) }}" class="text-sm font-semibold text-slate-900 hover:underline">
                    {{ $current->custodian_full_name ?? 'Unknown person' }}
                </a>
                <p class="mt-0.5 text-xs text-slate-500">
                    {{ implode(' · ', array_filter([$current->custodian_position ?? null, $current->custodian_employee_id ?? null])) ?: '—' }}
                </p>
                <dl class="mt-3 space-y-1.5 text-xs">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Document no.</dt>
                        <dd class="font-semibold text-slate-800">{{ $current->assignment_document_no ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Assigned since</dt>
                        <dd class="font-semibold text-slate-800">{{ $formatDate($current->assignment_issued_at) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Last verified</dt>
                        <dd class="font-semibold {{ filled($current->assignment_verified_at ?? null) ? 'text-slate-800' : 'text-slate-400' }}">
                            {{ filled($current->assignment_verified_at ?? null) ? $formatDate($current->assignment_verified_at) : 'Not yet' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Room / desk</dt>
                        <dd class="text-right font-semibold text-slate-800">
                            {{ $current->room_name ?? '—' }}@if (!empty($current->workstation_slot_label)) · {{ $current->workstation_slot_label }}@endif
                        </dd>
                    </div>
                    @if (!empty($current->issued_by_name))
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Issued by</dt>
                            <dd class="font-semibold text-slate-800">{{ $current->issued_by_name }}</dd>
                        </div>
                    @endif
                </dl>
                @if (!empty($current->assignment_notes))
                    <p class="mt-3 text-xs text-slate-600">{{ $current->assignment_notes }}</p>
                @endif
            </div>

            <details class="group rounded-xl ring-1 ring-slate-200/80">
                <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-2.5 text-sm font-semibold text-slate-700">
                    <span class="inline-flex items-center gap-2">
                        <i data-lucide="undo-2" class="h-4 w-4"></i>
                        Return item
                    </span>
                    <i data-lucide="chevron-down" class="h-4 w-4 transition group-open:rotate-180"></i>
                </summary>
                <form method="POST" action="{{ route('maintenance.property-assignments.return', $current->assignment_id) }}" class="space-y-3 border-t border-slate-100 px-4 py-3">
                    @csrf
                    <input type="hidden" name="return" value="{{ $returnUrl }}">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Condition on return</span>
                        <select name="return_condition" required class="{{ $fieldClass }}">
                            @foreach (\App\Support\PropertyAssignments::RETURN_CONDITIONS as $conditionOption)
                                <option value="{{ $conditionOption }}">{{ $conditionOption }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Notes (optional)</span>
                        <textarea name="return_notes" rows="2" maxlength="1000" class="{{ $textareaClass }}"></textarea>
                    </label>
                    <p class="text-xs text-slate-400">Returning as Damaged also marks the equipment as Damaged.</p>
                    <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Confirm return
                    </button>
                </form>
            </details>
        @endif

        @if ($assignmentBlocker)
            <p class="rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500 ring-1 ring-slate-200/80">
                {{ $assignmentBlocker }}
            </p>
        @elseif ($people->isEmpty())
            <p class="rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500 ring-1 ring-slate-200/80">
                No one is in the people directory yet.
                <a href="{{ route('maintenance.property-assignments.people.create') }}" class="font-semibold text-[#0025cc] hover:underline">Add a person</a>
                first.
            </p>
        @else
            <details class="group rounded-xl ring-1 ring-slate-200/80" @if (! $current) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-2.5 text-sm font-semibold text-slate-700">
                    <span class="inline-flex items-center gap-2">
                        <i data-lucide="{{ $current ? 'arrow-right-left' : 'user-plus' }}" class="h-4 w-4"></i>
                        {{ $current ? 'Transfer to another person' : 'Assign to a person' }}
                    </span>
                    <i data-lucide="chevron-down" class="h-4 w-4 transition group-open:rotate-180"></i>
                </summary>
                <form method="POST" action="{{ route('maintenance.property-assignments.store', $equipment->equipment_id) }}" class="space-y-3 border-t border-slate-100 px-4 py-3">
                    @csrf
                    <input type="hidden" name="return" value="{{ $returnUrl }}">
                    <label class="block">
                        <span class="mb-1 flex items-center justify-between text-xs font-medium text-slate-500">
                            <span>Person <span class="text-red-500">*</span></span>
                            <a href="{{ route('maintenance.property-assignments.people.create') }}" class="font-semibold text-[#0025cc] hover:underline">+ Add person</a>
                        </span>
                        <select name="custodian_id" required data-searchable="1" data-search-placeholder="Search name, position, or ID…" class="{{ $fieldClass }}">
                            <option value="" disabled selected>Select a person</option>
                            @foreach ($people as $personOption)
                                @continue($current && (int) $current->assignment_custodian_id === (int) $personOption->custodian_id)
                                <option value="{{ $personOption->custodian_id }}" @selected((string) old('custodian_id') === (string) $personOption->custodian_id)>
                                    {{ \App\Support\Custodians::optionLabel($personOption) }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Document no. (optional)</span>
                        <input type="text" name="document_no" maxlength="64" value="{{ old('document_no') }}" placeholder="Auto-generated if blank" class="{{ $fieldClass }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Notes (optional)</span>
                        <textarea name="notes" rows="2" maxlength="1000" class="{{ $textareaClass }}">{{ old('notes') }}</textarea>
                    </label>
                    <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                        {{ $current ? 'Transfer custody' : 'Assign item' }}
                    </button>
                </form>
            </details>
        @endif

        @if ($pastAssignments->isNotEmpty())
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Past custodians</p>
                <ul class="mt-2 space-y-2">
                    @foreach ($pastAssignments as $past)
                        <li class="text-xs">
                            <a href="{{ url('/maintenance/property-assignments/people/'.$past->assignment_custodian_id) }}" class="font-semibold text-slate-800 hover:underline">{{ $past->custodian_full_name ?? 'Unknown person' }}</a>
                            <p class="text-slate-500">
                                {{ $formatDate($past->assignment_issued_at) }} – {{ $formatDate($past->assignment_returned_at) }}
                                · {{ $past->assignment_status }}
                                @if (!empty($past->assignment_return_condition)) · {{ $past->assignment_return_condition }} @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
