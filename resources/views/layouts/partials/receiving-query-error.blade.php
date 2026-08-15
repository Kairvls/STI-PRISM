@if(!empty($queryError))
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Receiving lists could not load some records. {{ $queryError }}
    </div>
@endif
