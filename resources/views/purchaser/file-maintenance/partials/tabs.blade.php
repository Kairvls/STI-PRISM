<nav class="pur-tabs !mb-0" aria-label="File maintenance lookups">
    @php
        $fmTabs = [
            'brands' => ['title' => 'Brands', 'icon' => 'tag'],
            'uom' => ['title' => 'UOM', 'icon' => 'ruler'],
            'categories' => ['title' => 'Categories', 'icon' => 'folders'],
            'subcategories' => ['title' => 'Sub Categories', 'icon' => 'folder-tree'],
        ];
    @endphp
    @foreach($fmTabs as $key => $meta)
        <a
            href="{{ route(($pp ?? 'purchaser').'.file-maintenance.index', ['tab' => $key]) }}"
            class="pur-tab {{ ($tab ?? 'brands') === $key ? 'is-active' : '' }}"
        >
            <i data-lucide="{{ $meta['icon'] }}" class="h-3.5 w-3.5"></i>
            {{ $meta['title'] }}
        </a>
    @endforeach
</nav>
