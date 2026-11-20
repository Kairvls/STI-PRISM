{{-- Every read-only RIS uses the purchaser sheet. --}}
@include('partials.ris-purchaser-sheet', [
    'ris' => $ris,
    'risItems' => $risItems ?? ($ris->risItems ?? collect()),
    'printId' => $printId ?? null,
])
