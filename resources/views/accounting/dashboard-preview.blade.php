<div class="bg-slate-100 p-3 md:p-5">
    @if (($kind ?? '') === 'dv')
        @include('partials.disbursement-voucher-paper', [
            'dv' => $dv,
            'editable' => false,
        ])
    @elseif (($kind ?? '') === 'rfc')
        @include('partials.request-check-paper', [
            'editable' => false,
            'rfc' => $rfc,
        ])
    @elseif (($kind ?? '') === 'liq')
        @include('partials.liquidation-report-paper', [
            'editable' => false,
            'liq' => $liq,
            'rows' => $rows ?? collect(),
        ])
    @elseif (($kind ?? '') === 'atp')
        @include('partials.authority-to-purchase-paper', [
            'editable' => false,
            'atp' => $atp,
            'items' => $items ?? collect(),
        ])
    @endif
</div>
