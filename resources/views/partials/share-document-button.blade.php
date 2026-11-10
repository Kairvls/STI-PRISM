{{--
  Share a procurement document into Messages.
  @var string $type  ris|atp|rfc|dv|rr|liq
  @var int|string $id
  @var string|null $label
  @var string $variant  icon|button  (default icon)
--}}
@php
    $type = strtolower((string) ($type ?? ''));
    $id = (int) ($id ?? 0);
    $label = $label ?? \App\Support\RequestExplorer::shareUiLabel($type, null, $requestNo ?? null);
    $variant = $variant ?? 'icon';
@endphp
@if($type !== '' && $id > 0)
    @if($variant === 'button')
        <button
            type="button"
            data-share-doc-type="{{ $type }}"
            data-share-doc-id="{{ $id }}"
            data-share-doc-label="{{ $label }}"
            class="js-share-document inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
            title="Share to Messages"
            aria-label="Share to Messages"
        >
            <i data-lucide="share-2" class="h-3.5 w-3.5"></i>
            Share
        </button>
    @else
        <button
            type="button"
            data-share-doc-type="{{ $type }}"
            data-share-doc-id="{{ $id }}"
            data-share-doc-label="{{ $label }}"
            class="js-share-document inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
            title="Share to Messages"
            aria-label="Share to Messages"
        >
            <i data-lucide="share-2" class="h-4 w-4"></i>
        </button>
    @endif
@endif
