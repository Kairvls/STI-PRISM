{{-- Short caution line under RIS supplier select. Full detail lives in the dropdown. --}}
<p
    x-show="itemSupplierWarning(item)"
    x-cloak
    class="mt-1 px-0.5 text-[10px] font-semibold leading-snug text-amber-800 sm:px-1 sm:text-[11px]"
    x-bind:class="{
        'text-rose-700': itemSupplierWarning(item)?.risk_key === 'high'
    }"
    x-text="itemSupplierWarning(item)?.short || 'Caution'"
></p>
