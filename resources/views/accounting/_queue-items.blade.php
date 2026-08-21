@forelse ($queue as $item)
    @php
        $feedIcons = [
            'ATP' => 'file-check',
            'Request Check' => 'clipboard-list',
            'Funds' => 'banknote',
            'Liquidation' => 'receipt',
        ];
        $feedIcon = $feedIcons[$item->type] ?? 'file-text';
    @endphp
    <a href="{{ $item->url }}" class="acc-feed-item">
        <span class="acc-feed-icon">
            <i data-lucide="{{ $feedIcon }}"></i>
        </span>
        <span class="acc-feed-label">{{ $item->type }}</span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-slate-900">{{ $item->ref }}</p>
            <p class="truncate text-[11px] text-slate-500">{{ $item->related }} · {{ $item->who }}</p>
        </div>
        <span class="acc-feed-money">{{ $item->amount !== null ? '₱'.number_format((float)$item->amount, 2) : '—' }}</span>
        @include('accounting.partials.status-badge', ['status' => $item->status])
        @if ($item->action === 'Release funds')
            <span class="icon-btn" data-tip="Release funds" aria-label="Release funds">
                <i data-lucide="banknote" class="h-4 w-4"></i>
            </span>
        @else
            <span class="icon-btn" data-tip="Review {{ $item->type }}" aria-label="Review {{ $item->type }}">
                <i data-lucide="eye" class="h-4 w-4"></i>
            </span>
        @endif
    </a>
@empty
    <div class="p-6"><div class="acc-empty">Nothing waiting for Accounting right now.</div></div>
@endforelse