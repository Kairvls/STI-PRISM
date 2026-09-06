@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'Supplier Details')
@section('page-subtitle', 'Profile, notes, blacklist warnings, and linked documents')

@section('content')

@php
    $isActive = (int) ($supplier->supplier_is_active ?? 1) === 1;
    $isBlacklisted = (int) ($supplier->supplier_is_blacklisted ?? 0) === 1;
    $supplierName = $supplier->company_name ?? $supplier->shop_name ?? 'Unnamed Supplier';
    $supplierCode = $supplier->supplier_code
        ?: \App\Support\SupplierCode::generate(
            (string) $supplier->supplier_store_type,
            $supplierName,
            $supplier->supplier_created_at
        );
@endphp

<div class="space-y-6" x-data="{ trailTab: 'All' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="pur-page-title">{{ $supplierName }}</h2>
            <p class="pur-page-subtitle">{{ $supplierCode }} · {{ $supplier->supplier_store_type }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route(($pp ?? 'purchaser').'.suppliers.index') }}" class="pur-btn-secondary">Back to suppliers</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="pur-card p-6">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Supplier Information</h3>
                    <p class="text-sm text-slate-500">Core details and contact information.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                        {{ $isActive ? 'Active' : 'Inactive' }}
                    </span>
                    @if($isBlacklisted)
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800" title="{{ $supplier->supplier_blacklist_reason }}">
                            Blacklisted
                        </span>
                    @endif
                </div>
            </div>

            @if($isBlacklisted && filled($supplier->supplier_blacklist_reason))
                <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <p class="font-medium">Blacklist warning</p>
                    <p class="mt-1">{{ $supplier->supplier_blacklist_reason }}</p>
                </div>
            @endif

            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Store type</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $supplier->supplier_store_type }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Created at</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ optional(\Carbon\Carbon::parse($supplier->supplier_created_at))->format('M d, Y h:i A') ?? '—' }}</dd>
                </div>
                @if($supplier->supplier_store_type === 'Physical Store')
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Company name</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->company_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Contact person</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->contact_person ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Email address</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->email_address ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Contact number</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ \App\Support\PhoneNumber::formatForDisplay($supplier->contact_number) ?? '—' }}</dd>
                    </div>
                    @if($supplier->supplier_store_type === 'Physical Store')
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Landline number</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ \App\Support\PhoneNumber::formatLandlineForDisplay($supplier->landline_number) ?? '—' }}</dd>
                    </div>
                    @endif
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Company address</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->company_address ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Operating hours</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->operating_hours ?? '—' }}</dd>
                    </div>
                @else
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Shop name</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->shop_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">App used</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->app_used ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Contact person</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->contact_person ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Contact number</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ \App\Support\PhoneNumber::formatForDisplay($supplier->contact_number) ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Email address</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->email_address ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Store URL</dt>
                        <dd class="mt-1 text-sm text-slate-700">
                            @if(!empty($supplier->store_url))
                                <a href="{{ $supplier->store_url }}" target="_blank" rel="noopener noreferrer" class="break-all text-blue-700 hover:underline">{{ $supplier->store_url }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Seller / Store ID</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->seller_id ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Operating hours</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->operating_hours ?? '—' }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="pur-card space-y-5 p-6">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Notes &amp; blacklist</h3>
                <p class="text-sm text-slate-500">Blacklist is a warning only — the supplier remains selectable on forms.</p>
            </div>

            <form method="POST" action="{{ route(($pp ?? 'purchaser').'.suppliers.notes.store', $supplier->supplier_id) }}" class="space-y-3">
                @csrf
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="supplier_note_body">Add note</label>
                <textarea
                    id="supplier_note_body"
                    name="supplier_note_body"
                    rows="3"
                    maxlength="2000"
                    class="pur-input w-full"
                    placeholder="e.g. Late deliveries, incomplete invoices..."
                    required
                >{{ old('supplier_note_body') }}</textarea>
                <button type="submit" class="pur-btn-primary">Save note</button>
            </form>

            @if($isBlacklisted)
                <form method="POST" action="{{ route(($pp ?? 'purchaser').'.suppliers.unblacklist', $supplier->supplier_id) }}" class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    @csrf
                    <p class="text-sm font-medium text-slate-800">Clear blacklist</p>
                    <textarea
                        name="supplier_note_body"
                        rows="2"
                        maxlength="2000"
                        class="pur-input w-full"
                        placeholder="Optional reason for clearing..."
                    ></textarea>
                    <button type="submit" class="pur-btn-secondary">Clear blacklist</button>
                </form>
            @else
                <form method="POST" action="{{ route(($pp ?? 'purchaser').'.suppliers.blacklist', $supplier->supplier_id) }}" class="space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    @csrf
                    <p class="text-sm font-medium text-amber-900">Mark as blacklisted (warning)</p>
                    <textarea
                        name="supplier_note_body"
                        rows="2"
                        maxlength="2000"
                        class="pur-input w-full"
                        placeholder="Why is this supplier not recommended?"
                        required
                    ></textarea>
                    <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                        Blacklist supplier
                    </button>
                </form>
            @endif
        </section>
    </div>

    <section class="pur-card p-6">
        <h3 class="mb-4 text-lg font-semibold text-slate-900">Notes &amp; flags timeline</h3>
        @if(($notes ?? collect())->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-slate-600">
                No notes or blacklist events yet.
            </div>
        @else
            <ol class="space-y-3">
                @foreach($notes as $note)
                    @php
                        $type = $note->supplier_note_type ?? 'note';
                        $chipClass = match ($type) {
                            'blacklist' => 'bg-amber-100 text-amber-800',
                            'unblacklist' => 'bg-emerald-100 text-emerald-800',
                            default => 'bg-slate-100 text-slate-700',
                        };
                        $chipLabel = match ($type) {
                            'blacklist' => 'Blacklisted',
                            'unblacklist' => 'Cleared',
                            default => 'Note',
                        };
                    @endphp
                    <li class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $chipClass }}">{{ $chipLabel }}</span>
                            <span class="text-xs text-slate-500">{{ $note->author_name ?? 'Unknown user' }}</span>
                            <span class="text-xs text-slate-400">·</span>
                            <span class="text-xs text-slate-500">
                                {{ optional(\Carbon\Carbon::parse($note->created_at))->format('M d, Y h:i A') ?? '—' }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap">{{ $note->supplier_note_body }}</p>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    <section class="pur-card p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Document trail</h3>
                <p class="text-sm text-slate-500">RIS, ATP, RR, and procurement requests linked to this supplier.</p>
            </div>
            <div class="flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1">
                @foreach(['All', 'RIS', 'ATP', 'RR', 'Procurement'] as $tab)
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 text-xs font-medium transition"
                        x-on:click="trailTab = '{{ $tab }}'"
                        x-bind:class="trailTab === '{{ $tab }}' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    >
                        {{ $tab }}
                    </button>
                @endforeach
            </div>
        </div>

        @if(($documentTrail ?? collect())->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-slate-600">
                No linked documents found for this supplier.
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Document</th>
                            <th class="px-4 py-3">Created</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($documentTrail as $doc)
                            <tr x-show="trailTab === 'All' || trailTab === @js($doc->doc_type)">
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        {{ $doc->doc_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if(!empty($doc->doc_url))
                                        <a href="{{ $doc->doc_url }}" class="font-medium text-blue-700 hover:underline">
                                            {{ $doc->doc_number ?: ($doc->doc_type . ' #' . $doc->doc_id) }}
                                        </a>
                                    @else
                                        {{ $doc->doc_number ?: ($doc->doc_type . ' #' . $doc->doc_id) }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    {{ $doc->doc_date ? \Carbon\Carbon::parse($doc->doc_date)->format('M d, Y') : '—' }}
                                </td>
                                <td class="px-4 py-3">{{ $doc->doc_status ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $doc->doc_detail ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
