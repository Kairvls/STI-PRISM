@props([
    'href' => null,
    'label' => 'View',
    'tag' => 'a',
])

@php
    $classes = 'inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-[#0025cc] transition hover:bg-blue-50';
@endphp

@if ($tag === 'button')
    <button
        type="button"
        {{ $attributes->merge(['class' => $classes]) }}
    >
        {{ $label }}
        <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
    </button>
@else
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => $classes]) }}
    >
        {{ $label }}
        <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
    </a>
@endif
