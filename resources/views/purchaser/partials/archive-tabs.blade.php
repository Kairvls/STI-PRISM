@php
    $archiveView = $archiveView ?? false;
    $activeRoute = $activeRoute ?? '';
    $activeLabel = $activeLabel ?? 'Active';
@endphp
<div class="flex items-center gap-2">
    <a
        href="{{ route($activeRoute) }}"
        class="{{ !$archiveView ? 'pur-btn-primary' : 'pur-btn-secondary' }}"
        @unless($archiveView) aria-current="page" @endunless
    >{{ $activeLabel }}</a>
    <a
        href="{{ route($activeRoute, ['view' => 'archive']) }}"
        class="{{ $archiveView ? 'pur-btn-primary' : 'pur-btn-secondary' }}"
        @if($archiveView) aria-current="page" @endif
    >Archive</a>
</div>
