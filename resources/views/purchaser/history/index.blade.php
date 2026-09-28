@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'Purchase History')
@section('page-subtitle', 'Everything this purchaser account has bought, requested, and received.')

@section('content')
@php
    use App\Support\PurchaserHistory;
    use Carbon\Carbon;

    $pp = $pp ?? 'purchaser';
    $toneClasses = [
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-100',
        'red' => 'bg-rose-50 text-rose-700 ring-rose-100',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
    $peso = fn ($amount) => $amount === null ? '—' : '₱'.number_format((float) $amount, 2);
    $shortDate = fn ($date) => $date ? Carbon::parse($date)->format('M d, Y') : '—';

    // Account + period are carried across tabs; tab-specific filters are not.
    $baseQuery = array_filter([
        'purchaser' => $isAdmin && ! $isOwnHistory ? $subject->user_id : null,
        'from' => $filters['from'],
        'to' => $filters['to'],
    ]);
    $tabUrl = fn (string $key) => route($pp.'.history.index', array_merge($baseQuery, ['tab' => $key]));

    $maxMonthly = max(1, collect($monthlySpend)->max('amount'));
    $hasFilters = collect($filters)->filter()->isNotEmpty();
    $firstName = \Illuminate\Support\Str::of($subject->name)->before(' ');
@endphp

<div class="space-y-6">

    {{-- ===================================================== --}}
    {{-- ACCOUNT HEADER --}}
    {{-- ===================================================== --}}

    <div class="pur-card">
        <div class="flex flex-col gap-5 px-5 py-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#0025cc] text-xl font-bold text-white">
                    {{ strtoupper(mb_substr($subject->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                        {{ $isOwnHistory ? 'Your purchase history' : 'Purchase history of' }}
                    </p>
                    <h2 class="truncate text-xl font-bold tracking-tight text-slate-900">{{ $subject->name }}</h2>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 font-semibold ring-1 {{ $subject->is_primary ? $toneClasses['blue'] : $toneClasses['slate'] }}">
                            <i data-lucide="badge-check" class="h-3 w-3"></i>
                            {{ $subject->role_label }}
                        </span>
                        @if($subject->employee_id)
                            <span class="inline-flex items-center gap-1">
                                <i data-lucide="id-card" class="h-3.5 w-3.5"></i>
                                {{ $subject->employee_id }}
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1">
                            <i data-lucide="calendar-clock" class="h-3.5 w-3.5"></i>
                            Last purchase: {{ $summary['last_purchase'] ?? 'none yet' }}
                        </span>
                    </div>
                </div>
            </div>

            @if($isAdmin && $accounts->count() > 1)
                <form method="GET" action="{{ route($pp.'.history.index') }}" class="flex flex-col gap-1.5 sm:min-w-[300px]">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <label for="historyPurchaser" class="text-xs font-semibold text-slate-500">View purchaser account</label>
                    <select
                        id="historyPurchaser"
                        name="purchaser"
                        onchange="this.form.submit()"
                        class="h-10 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white"
                    >
                        @foreach($accounts as $account)
                            <option value="{{ $account->user_id }}" @selected($account->user_id === $subject->user_id)>
                                {{ $account->name }} — {{ $account->is_primary ? 'Primary' : 'Additional' }} purchaser
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- KPIs + 6-MONTH TREND --}}
    {{-- ===================================================== --}}

    @php
        $compactPeso = function (float $amount): string {
            return match (true) {
                $amount >= 1_000_000 => '₱'.rtrim(rtrim(number_format($amount / 1_000_000, 1), '0'), '.').'M',
                $amount >= 1_000 => '₱'.rtrim(rtrim(number_format($amount / 1_000, 1), '0'), '.').'K',
                default => '₱'.number_format($amount),
            };
        };
        $deliveredPct = $summary['approved'] > 0 ? round(($summary['delivered'] / $summary['approved']) * 100) : 0;
        $statCards = [
            ['label' => 'Purchases', 'value' => number_format($summary['purchases']), 'icon' => 'shopping-cart', 'tint' => 'bg-blue-50 text-blue-700 ring-blue-100'],
            ['label' => 'Items purchased', 'value' => number_format($summary['items_purchased']), 'hint' => 'Units on approved ATPs', 'icon' => 'package', 'tint' => 'bg-violet-50 text-violet-700 ring-violet-100'],
            ['label' => 'Suppliers used', 'value' => number_format($summary['suppliers']), 'hint' => 'Distinct suppliers bought from', 'icon' => 'truck', 'tint' => 'bg-amber-50 text-amber-700 ring-amber-100'],
            ['label' => 'Delivered', 'value' => number_format($summary['delivered']).' of '.number_format($summary['approved']), 'icon' => 'package-check', 'tint' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
        ];
    @endphp

    <div class="grid gap-4 xl:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">

        {{-- Spend + trend --}}
        <div class="pur-stat-card flex flex-col">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#0025cc] text-white">
                        <i data-lucide="wallet" class="h-4 w-4"></i>
                    </span>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Approved spend</p>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-500">
                    {{ $filters['from'] || $filters['to'] ? 'Selected period' : 'All time' }}
                </span>
            </div>

            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $peso($summary['approved_spend']) }}</p>
            <p class="mt-1 text-xs text-slate-500">
                of {{ $peso($summary['requested_spend']) }} requested across {{ $summary['purchases'] }} {{ \Illuminate\Support\Str::plural('purchase', $summary['purchases']) }}
            </p>

            <p class="mt-5 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                <i data-lucide="trending-up" class="h-3.5 w-3.5"></i>
                Monthly approved spend · last 6 months
            </p>
            <div class="mt-2 flex min-h-[120px] flex-1 items-stretch gap-2 sm:gap-3">
                @foreach($monthlySpend as $month)
                    @php $height = $month['amount'] > 0 ? max(8, round(($month['amount'] / $maxMonthly) * 100)) : 4; @endphp
                    <div class="flex flex-1 flex-col items-center gap-1.5" title="{{ $month['label'] }}: {{ $peso($month['amount']) }}">
                        <span class="h-4 whitespace-nowrap text-[10px] font-semibold tabular-nums text-slate-500">
                            {{ $month['amount'] > 0 ? $compactPeso($month['amount']) : '' }}
                        </span>
                        <div class="relative w-full max-w-[56px] flex-1">
                            <div class="absolute inset-x-0 bottom-0 rounded-md {{ $month['amount'] > 0 ? 'bg-[#0025cc]' : 'bg-slate-100' }}" style="height: {{ $height }}%"></div>
                        </div>
                        <span class="text-[11px] font-medium text-slate-400">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Counts --}}
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4 xl:grid-cols-2">
            @foreach($statCards as $card)
                <div class="pur-stat-card flex flex-col">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ring-1 {{ $card['tint'] }}">
                            <i data-lucide="{{ $card['icon'] }}" class="h-4 w-4"></i>
                        </span>
                        <p class="text-xs font-semibold uppercase leading-tight tracking-wide text-slate-500">{{ $card['label'] }}</p>
                    </div>

                    <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ $card['value'] }}</p>

                    @if($card['label'] === 'Purchases')
                        <div class="mt-auto flex flex-wrap gap-1.5 pt-2">
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $toneClasses['green'] }}">{{ $summary['approved'] }} approved</span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $toneClasses['amber'] }}">{{ $summary['in_review'] }} in review</span>
                            @if($summary['returned'] > 0)
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $toneClasses['red'] }}">{{ $summary['returned'] }} returned</span>
                            @endif
                        </div>
                    @elseif($card['label'] === 'Delivered')
                        <div class="mt-auto pt-3">
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $deliveredPct }}%"></div>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-500">{{ $deliveredPct }}% of approved purchases received</p>
                        </div>
                    @else
                        <p class="mt-auto pt-1 text-xs text-slate-500">{{ $card['hint'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    @if($filters['from'] || $filters['to'])
        <p class="-mt-2 text-xs text-slate-500">
            Figures above cover {{ $filters['from'] ? $shortDate($filters['from']) : 'the beginning' }} to {{ $filters['to'] ? $shortDate($filters['to']) : 'today' }}.
        </p>
    @endif

    {{-- ===================================================== --}}
    {{-- TABS --}}
    {{-- ===================================================== --}}

    <nav class="pur-tabs !mb-0" aria-label="Purchase history views">
        <a href="{{ $tabUrl('items') }}" class="pur-tab {{ $tab === 'items' ? 'is-active' : '' }}">
            <i data-lucide="package" class="h-3.5 w-3.5"></i>
            Purchased items
        </a>
        <a href="{{ $tabUrl('documents') }}" class="pur-tab {{ $tab === 'documents' ? 'is-active' : '' }}">
            <i data-lucide="files" class="h-3.5 w-3.5"></i>
            Documents
            <span class="rounded-full bg-black/10 px-1.5 text-[10px]">{{ array_sum($documentCounts) }}</span>
        </a>
        <a href="{{ $tabUrl('suppliers') }}" class="pur-tab {{ $tab === 'suppliers' ? 'is-active' : '' }}">
            <i data-lucide="truck" class="h-3.5 w-3.5"></i>
            Suppliers
        </a>
    </nav>

    <div class="pur-card overflow-visible">

        {{-- ===================================================== --}}
        {{-- FILTER BAR --}}
        {{-- ===================================================== --}}

        <div class="border-b border-gray-100 px-5 py-4">
            <form method="GET" action="{{ route($pp.'.history.index') }}" class="flex flex-col gap-2 lg:flex-row lg:flex-wrap lg:items-end">
                <input type="hidden" name="tab" value="{{ $tab }}">
                @if($isAdmin && ! $isOwnHistory)
                    <input type="hidden" name="purchaser" value="{{ $subject->user_id }}">
                @endif

                @if($tab !== 'suppliers')
                    <div class="relative flex-1 lg:min-w-[240px]">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                        <input
                            type="search"
                            name="search"
                            value="{{ $filters['search'] }}"
                            placeholder="{{ $tab === 'items' ? 'Search item, ATP, RIS, or supplier…' : 'Search reference, details, or status…' }}"
                            class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white"
                        >
                    </div>
                @endif

                <label class="flex items-center gap-2 text-xs font-medium text-gray-500">
                    From
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 outline-none focus:border-gray-300 focus:bg-white">
                </label>
                <label class="flex items-center gap-2 text-xs font-medium text-gray-500">
                    To
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 outline-none focus:border-gray-300 focus:bg-white">
                </label>

                @if($tab === 'items')
                    <select name="status" class="h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-600 outline-none focus:border-gray-300 focus:bg-white">
                        <option value="">All statuses</option>
                        @foreach(PurchaserHistory::STATUS_FILTERS as $key => $label)
                            <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                @elseif($tab === 'documents')
                    <select name="type" class="h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-600 outline-none focus:border-gray-300 focus:bg-white">
                        <option value="">All documents</option>
                        @foreach(PurchaserHistory::DOCUMENT_TYPES as $key => $label)
                            <option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }} ({{ $documentCounts[$key] ?? 0 }})</option>
                        @endforeach
                    </select>
                @endif

                <button type="submit" class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold text-white transition hover:bg-blue-800">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Apply
                </button>
                @if($hasFilters)
                    <a
                        href="{{ route($pp.'.history.index', array_filter(['tab' => $tab, 'purchaser' => $isAdmin && ! $isOwnHistory ? $subject->user_id : null])) }}"
                        class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                    >Clear</a>
                @endif
            </form>
        </div>

        {{-- ===================================================== --}}
        {{-- PURCHASED ITEMS --}}
        {{-- ===================================================== --}}

        @if($tab === 'items')
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-sm">
                    <thead class="bg-gray-50/70">
                        <tr class="border-b border-gray-100">
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Item</th>
                            <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Qty</th>
                            <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Unit price</th>
                            <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ATP / RIS</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Supplier</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($items as $item)
                            <tr class="align-top transition hover:bg-gray-50/70">
                                <td class="max-w-[320px] px-5 py-4">
                                    <p class="line-clamp-2 font-medium text-gray-900">{{ $item->atp_description ?: 'Unnamed item' }}</p>
                                    @if($item->atp_unit)
                                        <p class="mt-0.5 text-xs text-gray-500">per {{ $item->atp_unit }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right font-semibold tabular-nums text-gray-900">{{ number_format((int) $item->atp_quantity) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums text-gray-600">{{ $peso($item->atp_unit_price) }}</td>
                                <td class="px-5 py-4 text-right font-semibold tabular-nums text-gray-900">{{ $peso($item->line_amount) }}</td>
                                <td class="px-5 py-4">
                                    @if($isOwnHistory && $item->authority_purchase_form_number)
                                        <a
                                            href="{{ route($pp.'.atp.index', array_filter(['view_atp' => $item->authority_purchase_id, 'view' => $item->authority_purchase_is_archived ? 'archive' : null])) }}"
                                            class="font-semibold text-[#0025cc] hover:underline"
                                        >{{ $item->authority_purchase_form_number }}</a>
                                    @else
                                        <span class="font-semibold text-gray-800">{{ $item->authority_purchase_form_number ?: 'ATP #'.$item->authority_purchase_id }}</span>
                                    @endif
                                    <p class="mt-0.5 text-xs text-gray-500">RIS {{ $item->ris_form_number ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-4 text-gray-700">{{ $item->supplier_name ?: '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $shortDate($item->purchase_date) }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col items-start gap-1.5">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $toneClasses[$item->status['tone']] }}">{{ $item->status['label'] }}</span>
                                        @if($item->delivery)
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 {{ $toneClasses[$item->delivery['tone']] }}">
                                                <i data-lucide="truck" class="h-3 w-3"></i>
                                                {{ $item->delivery['label'] }}
                                            </span>
                                        @endif
                                        @if($item->authority_purchase_is_archived)
                                            <span class="text-[11px] text-gray-400">Archived</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="pur-empty">
                                        <i data-lucide="shopping-cart" class="mx-auto mb-2 h-8 w-8 text-slate-300"></i>
                                        @if($hasFilters)
                                            No purchased items match these filters.
                                        @else
                                            {{ $isOwnHistory ? 'You have' : $firstName.' has' }} no submitted purchases yet. Items appear here once an Authority to Purchase is submitted.
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($items->hasPages())
                <div class="border-t border-gray-100 px-5 py-3">{{ $items->links() }}</div>
            @endif
        @endif

        {{-- ===================================================== --}}
        {{-- DOCUMENTS --}}
        {{-- ===================================================== --}}

        @if($tab === 'documents')
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-gray-50/70">
                        <tr class="border-b border-gray-100">
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Document</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Details</th>
                            <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                            @if($isOwnHistory)
                                <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @php
                            $docIcons = ['ris' => 'package-open', 'atp' => 'file-check-2', 'po' => 'shopping-bag', 'rfc' => 'wallet', 'rr' => 'package-check', 'liq' => 'receipt-text'];
                        @endphp
                        @forelse($documents as $doc)
                            <tr class="transition hover:bg-gray-50/70">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                            <i data-lucide="{{ $docIcons[$doc->type] ?? 'file' }}" class="h-4 w-4"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold tracking-tight text-gray-900">{{ $doc->number }}</p>
                                            <p class="text-xs text-gray-500">{{ $doc->type_label }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="max-w-[340px] px-5 py-4">
                                    <p class="line-clamp-2 text-gray-700">{{ $doc->title ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-4 text-right font-semibold tabular-nums text-gray-900">{{ $peso($doc->amount) }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $toneClasses[$doc->tone] ?? $toneClasses['slate'] }}">{{ $doc->status ?: '—' }}</span>
                                    @if($doc->is_archived)
                                        <span class="ml-1 text-[11px] text-gray-400">Archived</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $shortDate($doc->date) }}</td>
                                @if($isOwnHistory)
                                    <td class="px-5 py-4 text-right">
                                        <a
                                            href="{{ route($pp.'.'.$doc->route, $doc->params) }}"
                                            class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-100"
                                        >
                                            Open
                                            <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                                        </a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isOwnHistory ? 6 : 5 }}">
                                    <div class="pur-empty">
                                        <i data-lucide="files" class="mx-auto mb-2 h-8 w-8 text-slate-300"></i>
                                        {{ $hasFilters ? 'No documents match these filters.' : 'No documents authored by this account yet.' }}
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($documents->hasPages())
                <div class="border-t border-gray-100 px-5 py-3">{{ $documents->links() }}</div>
            @endif
        @endif

        {{-- ===================================================== --}}
        {{-- SUPPLIERS --}}
        {{-- ===================================================== --}}

        @if($tab === 'suppliers')
            @php $topSpend = max(1, (float) $suppliers->max('spend')); @endphp
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="bg-gray-50/70">
                        <tr class="border-b border-gray-100">
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Supplier</th>
                            <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Purchases</th>
                            <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Items</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Approved spend</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Last purchase</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($suppliers as $supplier)
                            <tr class="transition hover:bg-gray-50/70">
                                <td class="px-5 py-4">
                                    @if($supplier->supplier_id)
                                        <a href="{{ route($pp.'.suppliers.show', $supplier->supplier_id) }}" class="font-semibold text-gray-900 hover:text-[#0025cc] hover:underline">{{ $supplier->name }}</a>
                                    @else
                                        <span class="font-semibold text-gray-500">{{ $supplier->name }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right tabular-nums text-gray-700">{{ number_format($supplier->purchases) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums text-gray-700">{{ number_format($supplier->items) }}</td>
                                <td class="w-[34%] px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-[#0025cc]" style="width: {{ max(2, round(($supplier->spend / $topSpend) * 100)) }}%"></div>
                                        </div>
                                        <span class="w-28 shrink-0 text-right font-semibold tabular-nums text-gray-900">{{ $peso($supplier->spend) }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $shortDate($supplier->last_purchase) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="pur-empty">
                                        <i data-lucide="truck" class="mx-auto mb-2 h-8 w-8 text-slate-300"></i>
                                        No approved purchases from any supplier{{ $filters['from'] || $filters['to'] ? ' in this period' : '' }} yet.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
