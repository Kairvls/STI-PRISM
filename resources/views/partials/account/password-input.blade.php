{{--
  Password input with show/hide toggle.
  Expects: $id, $name, $label, optional $autocomplete, $required, $showError, $inputClass
--}}
@php
    $id = $id ?? 'password';
    $name = $name ?? $id;
    $label = $label ?? 'Password';
    $required = $required ?? true;
    $autocomplete = $autocomplete ?? 'current-password';
    $showError = $showError ?? true;
    $inputClass = $inputClass ?? 'h-11 w-full rounded-xl border border-slate-200 bg-white px-4 pr-11 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100';
@endphp

<div>
    <label for="{{ $id }}" class="mb-2 block text-sm font-semibold text-slate-700">
        {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
    </label>
    <div class="relative" data-password-field>
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="password"
            @if ($required) required @endif
            autocomplete="{{ $autocomplete }}"
            class="{{ $inputClass }}"
        >
        <button
            type="button"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition hover:text-slate-700 focus:outline-none"
            data-password-toggle
            aria-label="Show password"
            aria-pressed="false"
            title="Show password"
        >
            <span data-password-icon="show" class="inline-flex">
                <i data-lucide="eye" class="h-4 w-4"></i>
            </span>
            <span data-password-icon="hide" class="inline-flex" hidden>
                <i data-lucide="eye-off" class="h-4 w-4"></i>
            </span>
        </button>
    </div>
    @if ($showError)
        @error($name)
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    @endif
</div>

@once
    <script>
        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-password-toggle]');
            if (!button) return;

            const field = button.closest('[data-password-field]');
            const input = field ? field.querySelector('input') : null;
            if (!input) return;

            const showing = input.type === 'password';
            input.type = showing ? 'text' : 'password';
            button.setAttribute('aria-pressed', showing ? 'true' : 'false');
            button.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
            button.setAttribute('title', showing ? 'Hide password' : 'Show password');

            const showIcon = button.querySelector('[data-password-icon="show"]');
            const hideIcon = button.querySelector('[data-password-icon="hide"]');
            if (showIcon) showIcon.hidden = showing;
            if (hideIcon) hideIcon.hidden = !showing;
        });
    </script>
@endonce
