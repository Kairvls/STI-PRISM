@props([
    'value' => null,
    'required' => false,
    'optional' => false,
])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700 dark:text-gray-300']) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <x-required-mark />
    @endif
    @if ($optional)
        <span class="font-normal text-gray-400">(optional)</span>
    @endif
</label>
