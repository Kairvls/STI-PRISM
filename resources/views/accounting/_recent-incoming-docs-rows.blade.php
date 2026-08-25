@forelse ($recentIncomingDocs as $doc)
    <tr>
        <td class="whitespace-nowrap">
            <span class="acc-muted text-xs font-semibold">{{ $doc->type }}</span>
        </td>
        <td class="min-w-0">
            <span class="acc-ref truncate block max-w-[260px]" title="{{ $doc->ref }}">{{ $doc->ref }}</span>
        </td>
        <td class="acc-money whitespace-nowrap !text-right">
            {{ $doc->amount !== null ? '₱'.number_format((float) $doc->amount, 2) : '—' }}
        </td>
        <td class="whitespace-nowrap">
            <span
                class="acc-relative-time text-xs font-semibold text-slate-700"
                data-arrived-at-ms="{{ (int) $doc->arrived_at_ms }}"
                title="{{ $doc->arrived_at_iso }}"
            >{{ $doc->arrived_relative }}</span>
        </td>
        <td class="text-right">
            <a
                href="{{ $doc->url }}"
                class="icon-btn"
                data-tip="Open document"
                aria-label="Open document"
            >
                <i data-lucide="eye" class="h-4 w-4"></i>
            </a>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="py-6">
            <div class="acc-empty">No incoming documents for Accounting right now.</div>
        </td>
    </tr>
@endforelse
