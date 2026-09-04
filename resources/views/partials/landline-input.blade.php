@php
    $name = $name ?? 'landline_number';
    $value = $value ?? old($name, '');
    $id = $id ?? $name . '-' . uniqid();
    $placeholder = $placeholder ?? '(0XX) XXX-XXXX';
    $inputClass = $inputClass ?? '';
    $maxlength = $maxlength ?? 16;
    $required = !empty($required);
    $disabled = !empty($disabled);
@endphp

@once('prism-landline-input')
    @push('styles')
        <style>
            .landline-input {
                box-sizing: border-box;
                height: 2.5rem;
                width: 100%;
                border-radius: 0.5rem;
                border: 1px solid #e5e7eb;
                background: #f9fafb;
                padding: 0 0.75rem;
                font-size: 0.875rem;
                color: #1f2937;
                outline: none;
                transition: border-color 0.15s ease, background 0.15s ease;
            }
            .landline-input::placeholder { color: #9ca3af; }
            .landline-input:focus {
                border-color: #d1d5db;
                background: #fff;
            }
            .pur-input.landline-input,
            .landline-input.phone-input--pur {
                height: auto;
                min-height: 2.75rem;
                border-radius: 0.75rem;
                padding-top: 0.625rem;
                padding-bottom: 0.625rem;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            window.formatPrismLandline = function (raw) {
                let digits = String(raw || '').replace(/\D/g, '');

                // Normalize PH trunk / country code
                if (digits.startsWith('63') && digits.length > 10) {
                    digits = '0' + digits.slice(2);
                }
                if (digits.length && !digits.startsWith('0')) {
                    digits = '0' + digits;
                }

                // Cap: 02 + 8 local = 10, or 0 + 2-digit area + 7 local = 10
                digits = digits.slice(0, 10);
                if (!digits) return '';

                // Manila (02): (02) XXXX-XXXX
                if (digits.startsWith('02')) {
                    const rest = digits.slice(2);
                    const a = rest.slice(0, 4);
                    const b = rest.slice(4, 8);
                    let out = '(02)';
                    if (a) out += ' ' + a;
                    if (b) out += '-' + b;
                    return out;
                }

                // Provincial (0XX): (0XX) XXX-XXXX
                const area = digits.slice(0, 3);
                const rest = digits.slice(3);
                const a = rest.slice(0, 3);
                const b = rest.slice(3, 7);
                let out = '(' + area + ')';
                if (a) out += ' ' + a;
                if (b) out += '-' + b;
                return out;
            };

            window.initPrismLandlineInput = function (input) {
                if (!input || input.dataset.landlineInitialized === '1') {
                    return;
                }

                if (input.value) {
                    input.value = window.formatPrismLandline(input.value);
                }

                input.addEventListener('input', function () {
                    if (input.dataset.landlineMaskApplying === '1') return;
                    const formatted = window.formatPrismLandline(input.value);
                    if (formatted !== input.value) {
                        input.dataset.landlineMaskApplying = '1';
                        input.value = formatted;
                        setTimeout(function () {
                            input.dataset.landlineMaskApplying = '0';
                        }, 0);
                    }
                });

                input.addEventListener('blur', function () {
                    input.value = window.formatPrismLandline(input.value);
                });

                input.dataset.landlineInitialized = '1';
            };

            window.syncPrismLandlineInputs = function (root) {
                (root || document).querySelectorAll('[data-landline-input]').forEach(function (input) {
                    if (input.disabled) return;
                    input.value = window.formatPrismLandline(input.value);
                });
            };

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-landline-input]').forEach(window.initPrismLandlineInput);
            });

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!form || form.tagName !== 'FORM') return;
                if (!form.querySelector('[data-landline-input]')) return;
                window.syncPrismLandlineInputs(form);
            }, true);
        </script>
    @endpush
@endonce

<input
    type="tel"
    id="{{ $id }}"
    name="{{ $name }}"
    value="{{ $value }}"
    placeholder="{{ $placeholder }}"
    data-landline-input
    @if($required) required @endif
    @if($disabled) disabled @endif
    @if(!empty($alpineDisabled)) x-bind:disabled="{{ $alpineDisabled }}" @endif
    maxlength="{{ $maxlength }}"
    class="landline-input {{ $inputClass }}"
    autocomplete="tel-national"
    inputmode="numeric"
>
