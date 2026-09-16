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
                background: #fff;
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
                border-color: #94a3b8;
                background: #fff;
                box-shadow: 0 0 0 2px rgba(241, 245, 249, 1);
            }
            .iti--separate-dial-code .iti__selected-flag {
                border-radius: 0.75rem 0 0 0.75rem;
                background: transparent;
            }
            .iti--separate-dial-code .iti__selected-flag:hover,
            .iti--separate-dial-code .iti__selected-flag:focus {
                background: transparent;
            }
            /* Match Account Settings white field look when bg-white is passed */
            .iti:has(.bg-white) .iti__tel-input,
            .iti:has(.bg-white) .iti__selected-flag {
                background: #fff !important;
            }
            .iti:has(.bg-white) .iti__selected-flag {
                background: transparent !important;
            }
            .pur-input.iti__tel-input,
            .phone-input--pur {
                height: auto;
                min-height: 2.75rem;
                border-radius: 0.75rem;
                padding-top: 0.625rem;
                padding-bottom: 0.625rem;
            }
            .phone-input--pur.iti__tel-input {
                background: #f8fafc;
            }
            .iti:has(.phone-input--pur) .iti__selected-flag {
                background: #f8fafc;
            }

            /* Country dropdown: teleport to body + sit above modals */
            .iti--container {
                position: fixed !important;
                z-index: 2147483646 !important;
            }
            .iti__dropdown-content {
                z-index: 2147483646 !important;
                border: 1px solid #e5e7eb !important;
                border-radius: 0.75rem !important;
                box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14) !important;
                overflow: hidden;
                background: #fff !important;
            }
            .iti__search-input {
                margin: 0.5rem 0.5rem 0.25rem !important;
                width: calc(100% - 1rem) !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 0.5rem !important;
                background: #fff !important;
                font-size: 0.875rem !important;
                outline: none !important;
            }
            .iti__search-input:focus {
                border-color: #94a3b8 !important;
                background: #fff !important;
                box-shadow: 0 0 0 2px rgba(241, 245, 249, 1);
            }
            .iti__country-list {
                max-height: 220px;
                /* Override global navy scrollbar from layouts/app */
                scrollbar-width: thin;
                scrollbar-color: #cbd5e1 transparent;
            }
            .iti__country-list::-webkit-scrollbar {
                width: 6px;
            }
            .iti__country-list::-webkit-scrollbar-track {
                background: transparent;
            }
            .iti__country-list::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 999px;
            }
            .iti__country-list::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }
            .iti__country.iti__highlight {
                background: #f1f5f9 !important;
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
                    // Keep country list out of overflow:hidden modal panels
                    dropdownContainer: document.body,
                    fixDropdownWidth: false,
                    useFullscreenPopup: false,
                    utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.10/build/js/utils.js',
                });

                // Reposition after layout settles (modals often open from display:none).
                input.addEventListener('open:countrydropdown', function () {
                    requestAnimationFrame(function () {
                        try {
                            if (typeof iti._setDropdownPosition === 'function') {
                                iti._setDropdownPosition();
                            }
                        } catch (e) {
                            // private API — ignore if unavailable
                        }
                    });
                });

                // Modal panels call stopPropagation, so intl-tel-input's documentElement
                // bubble listener never runs. Also, flag click only OPENs (never toggles close).
                const isCountryDropdownOpen = function () {
                    try {
                        if (iti.dropdownContent) {
                            return !iti.dropdownContent.classList.contains('iti__hide');
                        }
                    } catch (e) {
                        // fall through
                    }
                    return !!document.querySelector('body > .iti--container .iti__dropdown-content:not(.iti__hide), body > .iti--container');
                };

                const closeCountryDropdown = function () {
                    try {
                        if (typeof iti.closeCountrySelector === 'function') {
                            iti.closeCountrySelector();
                        } else if (typeof iti.closeDropdown === 'function') {
                            iti.closeDropdown();
                        } else if (typeof iti._closeDropdown === 'function') {
                            iti._closeDropdown();
                        }
                    } catch (e) {
                        // fall through to DOM cleanup
                    }

                    // Hard cleanup — leftover body-teleported lists from refresh/destroy races
                    document.querySelectorAll('body > .iti--container').forEach(function (node) {
                        node.remove();
                    });
                    if (iti.dropdownContent) {
                        iti.dropdownContent.classList.add('iti__hide');
                    }
                    if (iti.dropdownArrow) {
                        iti.dropdownArrow.classList.remove('iti__arrow--up');
                    }
                    if (iti.selectedCountry) {
                        iti.selectedCountry.setAttribute('aria-expanded', 'false');
                    }
                };

                // Expose for modal close / shared helpers
                iti.__prismCloseCountryDropdown = closeCountryDropdown;

                const eventTargetElement = function (event) {
                    const raw = event.target;
                    if (!raw) return null;
                    if (raw.nodeType === 1) return raw;
                    return raw.parentElement || null;
                };

                const onDocMouseDown = function (event) {
                    const target = eventTargetElement(event);
                    if (!target || typeof target.closest !== 'function') return;
                    if (!isCountryDropdownOpen()) return;

                    // Keep open when interacting with the list/search itself
                    if (target.closest('.iti--container, .iti__dropdown-content, .iti__country-list, .iti__search-input')) {
                        return;
                    }

                    const wrap = input.closest('.iti');
                    const onFlag = !!(wrap && target.closest(
                        '.iti__selected-country, .iti__selected-flag, .iti__flag-container, .iti__arrow, .iti__country-container'
                    ));

                    // Flag click while open: force close (library does not toggle)
                    if (onFlag) {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        closeCountryDropdown();
                        return;
                    }

                    // Any other outside click (including modal fields — capture beats stopPropagation)
                    closeCountryDropdown();
                };

                document.addEventListener('mousedown', onDocMouseDown, true);
                document.addEventListener('touchstart', onDocMouseDown, true);

                const onDocKeyDown = function (event) {
                    if (event.key === 'Escape' && isCountryDropdownOpen()) {
                        closeCountryDropdown();
                    }
                };
                document.addEventListener('keydown', onDocKeyDown, true);

                // Clean up when the widget is destroyed/refreshed
                const originalDestroy = iti.destroy && iti.destroy.bind(iti);
                if (originalDestroy) {
                    iti.destroy = function () {
                        document.removeEventListener('mousedown', onDocMouseDown, true);
                        document.removeEventListener('touchstart', onDocMouseDown, true);
                        document.removeEventListener('keydown', onDocKeyDown, true);
                        closeCountryDropdown();
                        return originalDestroy();
                    };
                }

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

            window.closeAllPrismPhoneDropdowns = function () {
                document.querySelectorAll('[data-phone-input]').forEach(function (input) {
                    const iti = window.PRISM_PHONE_INPUTS.get(input);
                    if (iti && typeof iti.__prismCloseCountryDropdown === 'function') {
                        iti.__prismCloseCountryDropdown();
                    }
                });
                document.querySelectorAll('body > .iti--container').forEach(function (node) {
                    node.remove();
                });
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
