@php
    $name = $name ?? 'contact_number';
    $value = $value ?? old($name, '');
    $id = $id ?? $name . '-' . uniqid();
    $storageId = $id . '-storage';
    $placeholder = $placeholder ?? 'XXX XXX XXXX';
    $inputClass = $inputClass ?? '';
    // Allow formatted local numbers like 0910 202 8282
    $maxlength = $maxlength ?? 16;
    $required = !empty($required);
    $displayValue = \App\Support\PhoneNumber::formatForDisplay(is_string($value) ? $value : null) ?? $value;
@endphp

@once
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.10/build/css/intlTelInput.css">
        <style>
            .iti { display: block; width: 100%; }
            .iti__tel-input {
                box-sizing: border-box;
                height: 2.5rem;
                width: 100%;
                border-radius: 0.5rem;
                border: 1px solid #e5e7eb;
                background: #f9fafb;
                font-size: 0.875rem;
                color: #1f2937;
                outline: none;
                transition: border-color 0.15s ease, background 0.15s ease;
            }
            .iti--separate-dial-code .iti__tel-input {
                /* Make sure national number never renders under the +63 overlay */
                padding-left: 6.75rem !important;
            }
            .iti__tel-input::placeholder { color: #9ca3af; }
            .iti__tel-input:focus {
                border-color: #d1d5db;
                background: #fff;
            }
            .iti--separate-dial-code .iti__selected-flag {
                border-radius: 0.5rem 0 0 0.5rem;
                background: #f3f4f6;
            }
            .pur-input.iti__tel-input,
            .phone-input--pur {
                height: auto;
                min-height: 2.75rem;
                border-radius: 0.75rem;
                padding-top: 0.625rem;
                padding-bottom: 0.625rem;
            }
            .iti__dropdown-content,
            .iti--container {
                z-index: 2147483646 !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.10/build/js/intlTelInput.min.js"></script>
        <script>
            window.PRISM_PHONE_INPUTS = window.PRISM_PHONE_INPUTS || new WeakMap();

            window.formatPrismPhoneForStorage = function (raw) {
                let digits = String(raw || '').replace(/\D/g, '');
                if (!digits) {
                    return '';
                }

                if (digits.startsWith('63') && digits.length >= 11) {
                    digits = '0' + digits.slice(2);
                } else if (digits.length === 10 && digits.startsWith('9')) {
                    digits = '0' + digits;
                }

                if (digits.length >= 11 && digits.startsWith('09')) {
                    digits = digits.slice(0, 11);
                    return digits.slice(0, 4) + ' ' + digits.slice(4, 7) + ' ' + digits.slice(7, 11);
                }

                return digits;
            };

            window.getPrismPhoneStorage = function (input) {
                if (!input) return null;
                const storageId = input.getAttribute('data-phone-storage-id');
                if (storageId) {
                    return document.getElementById(storageId);
                }
                const form = input.closest('form');
                if (!form) return null;
                return form.querySelector('[data-phone-storage]');
            };

            window.writePrismPhoneStorage = function (input, raw) {
                const storage = window.getPrismPhoneStorage(input);
                const formatted = window.formatPrismPhoneForStorage(raw);
                if (storage) {
                    storage.value = formatted;
                }
                return formatted;
            };

            window.readPrismPhoneDigits = function (input) {
                if (!input) return '';

                let raw = String(input.value || '').trim();
                const iti = window.PRISM_PHONE_INPUTS.get(input);

                if (iti) {
                    try {
                        const full = iti.getNumber();
                        if (full) {
                            raw = full;
                        }
                    } catch (e) {
                        // Keep typed national digits when intl-tel-input cannot parse.
                    }

                    if (!String(raw).replace(/\D/g, '')) {
                        try {
                            const selected = iti.getSelectedCountryData && iti.getSelectedCountryData();
                            const national = String(input.value || '').replace(/\D/g, '');
                            if (selected && selected.dialCode && national) {
                                raw = '+' + selected.dialCode + national;
                            }
                        } catch (e) {
                            // ignore
                        }
                    }
                }

                let digits = String(raw).replace(/\D/g, '');
                if (!digits) {
                    const storage = window.getPrismPhoneStorage(input);
                    if (storage && storage.value) {
                        digits = String(storage.value).replace(/\D/g, '');
                    }
                }

                return digits;
            };

            window.initPrismPhoneInput = function (input) {
                if (!input || input.dataset.phoneInitialized === '1' || !window.intlTelInput) {
                    return window.PRISM_PHONE_INPUTS.get(input) || null;
                }

                // Visible field must not submit — the hidden storage field owns the name.
                if (input.hasAttribute('name')) {
                    input.removeAttribute('name');
                }

                const iti = window.intlTelInput(input, {
                    initialCountry: 'ph',
                    preferredCountries: ['ph'],
                    separateDialCode: true,
                    nationalMode: true,
                    autoPlaceholder: 'aggressive',
                    formatOnDisplay: true,
                    dropdownContainer: document.body,
                    utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.10/build/js/utils.js',
                });

                const seed = input.value || (window.getPrismPhoneStorage(input) || {}).value || '';
                if (seed) {
                    try {
                        iti.setNumber(String(seed));
                    } catch (e) {
                        input.value = String(seed).replace(/^0/, '').trim();
                    }
                    window.writePrismPhoneStorage(input, seed);
                }

                // Enforce a friendly PH mobile format while typing: 9XX XXX XXXX
                input.addEventListener('input', function () {
                    try {
                        if (input.dataset.phMaskApplying === '1') return;
                        const selected = iti.getSelectedCountryData && iti.getSelectedCountryData();
                        if (!selected || selected.iso2 !== 'ph') {
                            window.writePrismPhoneStorage(input, window.readPrismPhoneDigits(input));
                            return;
                        }

                        let digits = String(input.value).replace(/\D/g, '');
                        if (digits.startsWith('0') && digits.length > 1) {
                            digits = digits.slice(1);
                        }
                        digits = digits.slice(0, 10);
                        const part1 = digits.slice(0, 3);
                        const part2 = digits.slice(3, 6);
                        const part3 = digits.slice(6, 10);
                        const formatted = [part1, part2, part3].filter(Boolean).join(' ');

                        if (formatted !== input.value) {
                            input.dataset.phMaskApplying = '1';
                            input.value = formatted;
                            setTimeout(function () { input.dataset.phMaskApplying = '0'; }, 0);
                        }

                        window.writePrismPhoneStorage(input, digits ? ('0' + digits) : '');
                    } catch (e) {
                        // Ignore masking errors; intl-tel-input still owns formatting.
                    }
                });

                input.addEventListener('blur', function () {
                    window.writePrismPhoneStorage(input, window.readPrismPhoneDigits(input));
                });

                input.dataset.phoneInitialized = '1';
                window.PRISM_PHONE_INPUTS.set(input, iti);
                return iti;
            };

            window.refreshPrismPhoneInput = function (input) {
                if (!input || !window.intlTelInput) {
                    return null;
                }

                const storage = window.getPrismPhoneStorage(input);
                const keep = (storage && storage.value) || input.value || '';

                const existing = window.PRISM_PHONE_INPUTS.get(input);
                if (existing && typeof existing.destroy === 'function') {
                    existing.destroy();
                }

                input.dataset.phoneInitialized = '0';
                window.PRISM_PHONE_INPUTS.delete(input);
                if (keep) {
                    input.value = keep;
                }

                return window.initPrismPhoneInput(input);
            };

            window.syncPrismPhoneInputs = function (root) {
                (root || document).querySelectorAll('[data-phone-input]').forEach(function (input) {
                    if (input.disabled) {
                        return;
                    }

                    const digits = window.readPrismPhoneDigits(input);
                    const formatted = window.writePrismPhoneStorage(input, digits);

                    // Keep the visible field in sync for any fallbacks that still read it.
                    if (formatted) {
                        const national = formatted.replace(/\D/g, '');
                        if (national.length === 11 && national.startsWith('09')) {
                            input.value = national.slice(1, 4) + ' ' + national.slice(4, 7) + ' ' + national.slice(7, 11);
                        }
                    }
                });
            };

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-phone-input]').forEach(window.initPrismPhoneInput);
            });

            // Capture-phase delegation so teleported edit modals still sync before submit.
            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!form || form.tagName !== 'FORM') {
                    return;
                }

                if (form.querySelector('[data-phone-input], [data-phone-storage]')) {
                    window.syncPrismPhoneInputs(form);
                }

                if (typeof window.syncPrismLandlineInputs === 'function' && form.querySelector('[data-landline-input]')) {
                    window.syncPrismLandlineInputs(form);
                }
            }, true);
        </script>
    @endpush
@endonce

<input
    type="hidden"
    id="{{ $storageId }}"
    name="{{ $name }}"
    value="{{ $displayValue }}"
    data-phone-storage
>

<input
    type="tel"
    id="{{ $id }}"
    value="{{ $displayValue }}"
    placeholder="{{ $placeholder }}"
    data-phone-input
    data-phone-storage-id="{{ $storageId }}"
    @if($required) required @endif
    maxlength="{{ $maxlength }}"
    class="phone-input {{ $inputClass }}"
    autocomplete="tel"
>
