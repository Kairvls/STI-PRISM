@include('partials.ris-document-paper', [
    'ris' => $ris,
    'risItems' => $risItems ?? collect(),
    'printId' => $printId ?? null,
])
