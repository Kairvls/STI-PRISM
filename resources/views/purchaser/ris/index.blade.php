@extends('layouts.purchaser-layout')

{{-- ===================================================== --}}
{{-- ADDED RIS MODULE: PAGE TITLE --}}
{{-- ===================================================== --}}

@section(
    "page-title",
    "Requisition Issue Slip"
)


{{-- ===================================================== --}}
{{-- ADDED RIS MODULE: PAGE SUBTITLE --}}
{{-- ===================================================== --}}

@section(
    "page-subtitle",
    "Create RIS from replacement requests or new procurement"
)


{{-- ===================================================== --}}
{{-- ADDED RIS MODULE: PAGE CONTENT --}}
{{-- ===================================================== --}}

@section("content")

<div x-data="{ openModal: null, requestType: @json(old('ris_request_type', $eligibleReplacementRequests->isEmpty() ? 'New Procurement' : 'Replacement Procurement')) }" x-cloak>

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: ALERT MESSAGES --}}
    {{-- ===================================================== --}}

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: SHOW VALIDATION ERRORS WHEN SAVE FAILS --}}
    {{-- ===================================================== --}}

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-medium">Please fix the following RIS form errors:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: DASHBOARD SUMMARY --}}
    {{-- ===================================================== --}}

    <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-5">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Total RIS</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $risSummary['total'] }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Draft</p>
            <p class="mt-2 text-3xl font-semibold text-gray-600">{{ $risSummary['draft'] }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Submitted</p>
            <p class="mt-2 text-3xl font-semibold text-blue-600">{{ $risSummary['submitted'] }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Approved</p>
            <p class="mt-2 text-3xl font-semibold text-green-600">{{ $risSummary['approved'] }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Rejected</p>
            <p class="mt-2 text-3xl font-semibold text-red-600">{{ $risSummary['rejected'] }}</p>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: TWO PRIMARY RIS ENTRY POINTS --}}
    {{-- ===================================================== --}}

    <div class="mb-6 grid gap-4 md:grid-cols-2">
        <button
            type="button"
            x-on:click="requestType = 'Replacement Procurement'; document.getElementById('ris-creation-form').scrollIntoView({ behavior: 'smooth' });"
            class="rounded-lg border border-gray-300 bg-white px-6 py-4 text-left font-medium text-gray-900 hover:bg-gray-50"
        >
            + Create RIS from Replacement Request........
        </button>
        <button
            type="button"
            x-on:click="requestType = 'New Procurement'; document.getElementById('ris-creation-form').scrollIntoView({ behavior: 'smooth' });"
            class="rounded-lg border border-gray-300 bg-white px-6 py-4 text-left font-medium text-gray-900 hover:bg-gray-50"
        >
            + Create New RIS
        </button>
    </div>

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: CREATE RIS FORM FOR BOTH WORKFLOWS --}}
    {{-- ===================================================== --}}

    <form
        id="ris-creation-form"
        method="POST"
        action="{{ route('purchaser.ris.store') }}"
        enctype="multipart/form-data"
        class="mb-6 rounded-lg border border-gray-200 bg-white p-6"
    >

        @csrf

        {{-- ===================================================== --}}
        {{-- ADDED RIS MODULE: REQUEST TYPE FIELD --}}
        {{-- ===================================================== --}}

        <fieldset class="mb-6">
            <legend class="text-sm font-medium text-gray-900">Request Type</legend>
            <div class="mt-3 space-y-3">
                <label class="flex items-center gap-3">
                    <input
                        type="radio"
                        name="ris_request_type"
                        value="Replacement Procurement"
                        class="h-4 w-4"
                        x-model="requestType"
                    >
                    <span class="text-sm text-gray-700">Replacement Procurement</span>
                </label>
                <label class="flex items-center gap-3">
                    <input
                        type="radio"
                        name="ris_request_type"
                        value="New Procurement"
                        class="h-4 w-4"
                        x-model="requestType"
                    >
                    <span class="text-sm text-gray-700">New Procurement</span>
                </label>
            </div>
        </fieldset>

        {{-- ===================================================== --}}
        {{-- ADDED RIS MODULE: REPLACEMENT REQUEST SELECTION --}}
        {{-- ===================================================== --}}

        <div class="mb-6" x-show="requestType === 'Replacement Procurement'">
            <label for="procurement_request" class="block text-sm font-medium text-gray-900">Replacement Request</label>
            <select
                id="procurement_request"
                name="procurement_request_id"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            >
                <option value="">Select a replacement request</option>
                @foreach($eligibleReplacementRequests as $req)
                    <option value="{{ $req->procurement_request_id }}" {{ old('procurement_request_id') == $req->procurement_request_id ? 'selected' : '' }}>
                        Request #{{ $req->procurement_request_id }}
                        - {{ $req->equipment_name ?? $req->report_unlisted_equipment_name ?? 'Equipment' }}
                    </option>
                @endforeach
            </select>

            {{-- ===================================================== --}}
            {{-- ADDED RIS MODULE: NO APPROVED REPLACEMENT REQUESTS MESSAGE --}}
            {{-- ===================================================== --}}

            @if($eligibleReplacementRequests->isEmpty())
                <p class="mt-2 rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-sm text-yellow-800">
                    No approved replacement requests are available yet. Use New Procurement, or approve a replacement request first.
                </p>
            @endif
        </div>

        {{-- ===================================================== --}}
        {{-- ADDED RIS MODULE: NEW PROCUREMENT MANUAL FIELDS --}}
        {{-- ===================================================== --}}

        <div class="mb-6 space-y-4" x-show="requestType === 'New Procurement'">
            <div>
                <label for="ris_manual_title" class="block text-sm font-medium text-gray-900">Procurement Title</label>
                <input
                    type="text"
                    id="ris_manual_title"
                    name="ris_manual_title"
                    value="{{ old('ris_manual_title') }}"
                    placeholder="e.g. New Television for Classroom 301"
                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                >
                {{-- ADDED RIS MODULE: PROCUREMENT TITLE VALIDATION MESSAGE --}}
                @error('ris_manual_title')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="ris_manual_requested_for" class="block text-sm font-medium text-gray-900">Requested for</label>
                <input
                    type="text"
                    id="ris_manual_requested_for"
                    name="ris_manual_requested_for"
                    value="{{ old('ris_manual_requested_for') }}"
                    placeholder="Department or location"
                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                >
            </div>
            <div>
                <label for="ris_manual_description" class="block text-sm font-medium text-gray-900">Description</label>
                <textarea
                    id="ris_manual_description"
                    name="ris_manual_description"
                    rows="3"
                    placeholder="Equipment specifications and requirements"
                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                >{{ old('ris_manual_description') }}</textarea>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- ADDED RIS MODULE: PURPOSE --}}
        {{-- ===================================================== --}}

        <div class="mb-6">
            <label for="ris_purpose" class="block text-sm font-medium text-gray-900">Purpose / Reason</label>
            <textarea
                id="ris_purpose"
                name="ris_purpose_description"
                rows="2"
                placeholder="Why is this equipment needed?"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            >{{ old('ris_purpose_description') }}</textarea>
        </div>

        {{-- ===================================================== --}}
        {{-- ADDED RIS MODULE: MULTIPLE RIS ITEM INPUT ROWS --}}
        {{-- ===================================================== --}}

        <div class="mb-6">
            <h3 class="mb-3 text-sm font-medium text-gray-900">Line Items</h3>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-700">Item Description</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-700">Qty</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-700">Unit</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-700">Unit Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @for($i = 0; $i < 8; $i++)
                            <tr>
                                <td class="px-3 py-2">
                                    <input
                                        type="text"
                                        name="ris_items[{{ $i }}][name_description]"
                                        value="{{ old('ris_items.' . $i . '.name_description') }}"
                                        class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
                                    >
                                </td>
                                <td class="px-3 py-2">
                                    <input
                                        type="number"
                                        name="ris_items[{{ $i }}][quantity_requested]"
                                        value="{{ old('ris_items.' . $i . '.quantity_requested') }}"
                                        min="1"
                                        class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
                                    >
                                </td>
                                <td class="px-3 py-2">
                                    <input
                                        type="text"
                                        name="ris_items[{{ $i }}][unit]"
                                        value="{{ old('ris_items.' . $i . '.unit') }}"
                                        placeholder="pcs, box, etc"
                                        class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
                                    >
                                </td>
                                <td class="px-3 py-2">
                                    <input
                                        type="number"
                                        name="ris_items[{{ $i }}][unit_cost]"
                                        value="{{ old('ris_items.' . $i . '.unit_cost') }}"
                                        step="0.01"
                                        min="0"
                                        class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
                                    >
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- ADDED RIS MODULE: MULTIPLE SUPPORTING DOCUMENT UPLOAD --}}
        {{-- ===================================================== --}}

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-900">Supporting Documents</label>
            <div class="mt-2 rounded-lg border-2 border-dashed border-gray-300 p-6">
                <input
                    type="file"
                    name="ris_attachments[]"
                    multiple
                    class="block w-full text-sm text-gray-500"
                >
                <p class="mt-2 text-xs text-gray-500">Word or Excel files only: .doc, .docx, .xls, .xlsx (up to 10MB per file)</p>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button
                type="reset"
                class="rounded-lg border border-gray-300 px-6 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Clear
            </button>
            <button
                type="submit"
                class="rounded-lg bg-gray-900 px-6 py-2 text-sm font-medium text-white hover:bg-gray-800"
            >
                Save RIS
            </button>
        </div>

    </form>

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: FILTERS --}}
    {{-- ===================================================== --}}

    <form method="GET" class="mb-6 grid gap-3 rounded-lg border border-gray-200 bg-white p-4 lg:grid-cols-6">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search RIS, request, equipment, or title"
            class="h-10 rounded-lg border border-gray-300 px-3 text-sm lg:col-span-2"
        >

        <select name="request_type" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
            <option value="">All Types</option>
            <option value="Replacement Procurement" {{ request('request_type') === 'Replacement Procurement' ? 'selected' : '' }}>Replacement</option>
            <option value="New Procurement" {{ request('request_type') === 'New Procurement' ? 'selected' : '' }}>New Procurement</option>
        </select>

        <select name="status" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
            <option value="">All Statuses</option>
            <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
            <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
            <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">

        <div class="flex gap-2 lg:col-span-6">
            <button type="submit" class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white">Search</button>
            <a href="{{ route('purchaser.ris.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Reset</a>
        </div>
    </form>

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: RIS LIST --}}
    {{-- ===================================================== --}}

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1120px]">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">RIS No.</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Source</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Purpose</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Documents</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Submitted</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($risRecords as $ris)
                        @php($attachments = $attachmentsByRis[$ris->ris_id] ?? collect())
                        <tr>
                            <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                {{ $ris->ris_form_number ?? 'RIS-' . $ris->ris_id }}
                            </td>

                            <td class="px-4 py-4 text-sm text-gray-600">
                                {{ $ris->ris_request_type ?? 'Replacement Procurement' }}
                            </td>

                            <td class="px-4 py-4 text-sm text-gray-600">
                                @if(($ris->ris_request_type ?? 'Replacement Procurement') === 'New Procurement')
                                    {{ $ris->ris_manual_title ?? 'New Procurement' }}<br>
                                    <span class="text-xs text-gray-400">{{ $ris->ris_manual_requested_for ?? 'No requested-for detail' }}</span>
                                @else
                                    Request #{{ $ris->procurement_request_id ?? 'N/A' }}<br>
                                    <span class="text-xs text-gray-400">Report #{{ $ris->report_id ?? 'N/A' }}</span>
                                @endif
                            </td>

                            <td class="max-w-xs px-4 py-4 text-sm text-gray-600">
                                {{ $ris->ris_purpose_description ?? 'No purpose provided.' }}
                            </td>

                            <td class="px-4 py-4 text-sm text-gray-600">
                                @forelse($attachments as $attachment)
                                    <a
                                        href="{{ route('purchaser.ris.attachments.download', $attachment->ris_attachment_id) }}"
                                        class="mb-1 block text-xs font-medium text-blue-600 hover:underline"
                                    >
                                        {{ $attachment->ris_attachment_original_name }}
                                    </a>
                                @empty
                                    <span class="text-xs text-gray-400">No documents</span>
                                @endforelse
                            </td>

                            <td class="px-4 py-4 text-sm">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold
                                    {{ $ris->ris_status === 'Approved' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $ris->ris_status === 'Rejected' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ $ris->ris_status === 'Pending' && $ris->ris_requested_by_date ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $ris->ris_status === 'Pending' && !$ris->ris_requested_by_date ? 'bg-gray-100 text-gray-700' : '' }}
                                ">
                                    {{ $ris->ris_status === 'Pending' && $ris->ris_requested_by_date ? 'Submitted to Admin' : $ris->ris_status }}
                                </span>
                            </td>

                            <td class="px-4 py-4 text-sm text-gray-600">
                                {{ $ris->ris_requested_by_date ? \Carbon\Carbon::parse($ris->ris_requested_by_date)->format('M d, Y') : 'Not submitted' }}
                            </td>

                            <td class="px-4 py-4 text-sm">
                                <button
                                    type="button"
                                    x-on:click="openModal = 'ris-{{ $ris->ris_id }}'"
                                    class="mb-2 inline-flex rounded-lg border border-blue-600 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-600"
                                >
                                    View
                                </button>

                                @if($ris->ris_status === 'Pending' && !$ris->ris_requested_by_date)
                                    <form method="POST" action="{{ route('purchaser.ris.submit', $ris->ris_id) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white">
                                            Submit to Admin
                                        </button>
                                    </form>
                                @elseif($ris->ris_status === 'Approved')
                                    @if(in_array($ris->ris_id, $risHasAtp))
                                        <span class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs font-medium text-green-700">ATP Created</span>
                                    @else
                                        <a
                                            href="{{ route('purchaser.atp.create', ['selected_ris' => $ris->ris_id]) }}"
                                            class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white"
                                        >
                                            Create ATP
                                        </a>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400">No action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-gray-500">
                                No RIS records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach($risRecords as $ris)
        <div
            x-show="openModal === 'ris-{{ $ris->ris_id }}'"
            x-cloak
            x-on:click.outside="openModal = null"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-3xl bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">RIS Details</h3>
                        <p class="text-sm text-slate-500">{{ $ris->ris_form_number ?? 'RIS-' . $ris->ris_id }}</p>
                    </div>
                    <button
                        type="button"
                        x-on:click="openModal = null"
                        class="rounded-full bg-slate-100 p-2 text-slate-600 hover:bg-slate-200"
                        aria-label="Close"
                    >
                        ×
                    </button>
                </div>

                <div class="space-y-6 p-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Request type</dt>
                            <p class="mt-1 text-sm text-slate-700">{{ $ris->ris_request_type }}</p>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Status</dt>
                            <p class="mt-1 text-sm text-slate-700">{{ $ris->ris_status }}</p>
                        </div>

                        @if($ris->ris_request_type === 'New Procurement')
                            <div class="md:col-span-2">
                                <dt class="text-xs uppercase tracking-wide text-gray-500">Title</dt>
                                <p class="mt-1 text-sm text-slate-700">{{ $ris->ris_manual_title ?? '—' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-xs uppercase tracking-wide text-gray-500">Requested for</dt>
                                <p class="mt-1 text-sm text-slate-700">{{ $ris->ris_manual_requested_for ?? '—' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-xs uppercase tracking-wide text-gray-500">Description</dt>
                                <p class="mt-1 text-sm text-slate-700">{{ $ris->ris_manual_description ?? '—' }}</p>
                            </div>
                        @else
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-gray-500">Request #</dt>
                                <p class="mt-1 text-sm text-slate-700">{{ $ris->procurement_request_id ?? '—' }}</p>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-gray-500">Equipment</dt>
                                <p class="mt-1 text-sm text-slate-700">{{ $ris->equipment_name ?? $ris->report_unlisted_equipment_name ?? '—' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-xs uppercase tracking-wide text-gray-500">Problem</dt>
                                <p class="mt-1 text-sm text-slate-700">{{ $ris->report_problem_description ?? '—' }}</p>
                            </div>
                        @endif

                        <div class="md:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Purpose</dt>
                            <p class="mt-1 text-sm text-slate-700">{{ $ris->ris_purpose_description ?? '-' }}</p>
                        </div>

                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">Line items</h4>
                        <div class="mt-3 overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Item</th>
                                        <th class="px-3 py-2 text-right">Qty</th>
                                        <th class="px-3 py-2 text-right">Unit Cost</th>
                                        <th class="px-3 py-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($itemsByRis[$ris->ris_id] ?? collect() as $item)
                                        <tr>
                                            <td class="px-3 py-2">{{ $item->ris_item_name_description ?? '—' }}</td>
                                            <td class="px-3 py-2 text-right">{{ $item->ris_quantity_requested ?? '—' }}</td>
                                            <td class="px-3 py-2 text-right">{{ $item->ris_unit_cost !== null ? number_format($item->ris_unit_cost, 2) : '—' }}</td>
                                            <td class="px-3 py-2 text-right">{{ $item->ris_total_amount !== null ? number_format($item->ris_total_amount, 2) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                    @if(($itemsByRis[$ris->ris_id] ?? collect())->isEmpty())
                                        <tr>
                                            <td colspan="4" class="px-3 py-4 text-center text-sm text-gray-500">No line items recorded.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">Attachments</h4>
                        <div class="mt-3 space-y-2">
                            @forelse($attachmentsByRis[$ris->ris_id] ?? collect() as $attachment)
                                <a href="{{ route('purchaser.ris.attachments.download', $attachment->ris_attachment_id) }}" class="block text-sm text-blue-600 hover:underline">
                                    {{ $attachment->ris_attachment_original_name }}
                                </a>
                            @empty
                                <p class="text-sm text-gray-500">No attachments.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 flex justify-between items-center px-6 py-4 bg-gray-50">
                    <button
                        type="button"
                        x-on:click="openModal = null"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Close
                    </button>

                    @if($ris->ris_status === 'Approved')
                        @if(in_array($ris->ris_id, $risHasAtp))
                            <span class="rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-700">ATP Created</span>
                        @else
                            <a
                                href="{{ route('purchaser.atp.create', ['selected_ris' => $ris->ris_id]) }}"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                Create ATP
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: PAGINATION --}}
    {{-- ===================================================== --}}

    <div>
        {{ $risRecords->links() }}
    </div>

</div>

@endsection
