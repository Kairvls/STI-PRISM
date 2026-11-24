@php
    $rawPath = $path ?? ($rfc->request_check_funding_type ?? ($atp->authority_purchase_payment_path ?? null));
    $normalizedPath = \App\Support\ProcurementPaymentPath::normalize(is_string($rawPath) ? $rawPath : null);
    $label = $normalizedPath ? \App\Support\ProcurementPaymentPath::label($normalizedPath) : null;
    $badgeStyle = match ($normalizedPath) {
        \App\Support\ProcurementPaymentPath::DIRECT_PAYMENT => 'background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;',
        \App\Support\ProcurementPaymentPath::CASH_INSTALLMENT => 'background:#fffbeb;color:#b45309;border:1px solid #fde68a;',
        \App\Support\ProcurementPaymentPath::CASH_ADVANCE => 'background:#f3e8ff;color:#7e22ce;border:1px solid #d8b4fe;',
        default => 'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;',
    };
    $sizeClass = ($size ?? 'md') === 'sm' ? 'px-1.5 py-0.5 text-[10px]' : 'px-2.5 py-0.5 text-xs';
@endphp

@if ($label)
    <span
        class="inline-flex items-center gap-1 rounded-full font-semibold whitespace-nowrap {{ $sizeClass }}"
        style="{{ $badgeStyle }}"
        title="Payment method: {{ $label }}"
    >
        {{ $label }}
    </span>
@endif
