@php
    $size = $size ?? 'md';
    $classes = match ($size) {
        'lg' => 'h-14 w-14',
        'sm' => 'h-8 w-8',
        default => 'h-9 w-9',
    };
    $iconSize = match ($size) {
        'lg' => 'h-6 w-6',
        'sm' => 'h-3.5 w-3.5',
        default => 'h-4 w-4',
    };
    $path = $path ?? ($supplier->supplier_logo_path ?? null);
    $url = \App\Support\SupplierLogo::url($path);
    $alt = $alt ?? 'Supplier logo';
@endphp
@if($url)
    <div class="{{ $classes }} shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-white">
        <img src="{{ $url }}" alt="{{ $alt }}" class="h-full w-full object-contain p-0.5">
    </div>
@else
    <div class="{{ $classes }} flex shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
        <i data-lucide="building-2" class="{{ $iconSize }}"></i>
    </div>
@endif
