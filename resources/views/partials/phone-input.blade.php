@php
    $name = $name ?? 'contact_number';
    $value = $value ?? old($name, '');
    $id = $id ?? $name . '-' . uniqid();
    $storageId = $id . '-storage';
    // Omit placeholder so intl-tel-input can inject the selected country's example format.
    $placeholder = $placeholder ?? null;
    $inputClass = $inputClass ?? '';
    // Max length is applied per-country in JS (strictMode + applyPrismPhoneCountryRules).
    $maxlength = $maxlength ?? null;
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

            window.formatPrismPhoneDisplay = function (raw) {
                let digits = String(raw || '').replace(/\D/g, '');
                if (!digits) {
                    return '';
                }

                if (digits.startsWith('63') && digits.length >= 11) {
                    digits = '0' + digits.slice(2);
                } else if (digits.length === 10 && digits.startsWith('9')) {
                    digits = '0' + digits;
                }

                digits = digits.replace(/^0+(?=\d)/, '0');
                digits = digits.slice(0, 11);

                if (digits.length <= 4) {
                    return digits;
                }

                if (digits.length <= 7) {
                    return digits.slice(0, 4) + ' ' + digits.slice(4);
                }

                return digits.slice(0, 4) + ' ' + digits.slice(4, 7) + ' ' + digits.slice(7, 11);
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

            window.getPrismPhoneUtils = function () {
                if (window.intlTelInputUtils) {
                    return window.intlTelInputUtils;
                }
                if (window.intlTelInput && window.intlTelInput.utils) {
                    return window.intlTelInput.utils;
                }
                return null;
            };

            window.getPrismPhoneCountryIso2 = function (input) {
                const iti = input ? window.PRISM_PHONE_INPUTS.get(input) : null;
                if (iti && typeof iti.getSelectedCountryData === 'function') {
                    try {
                        const country = iti.getSelectedCountryData();
                        if (country && country.iso2) {
                            return String(country.iso2).toLowerCase();
                        }
                    } catch (e) {
                        // fall through
                    }
                }
                return 'ph';
            };

            window.getPrismPhoneMaxDigits = function (input) {
                if (!input) return 15;

                const iso2 = window.getPrismPhoneCountryIso2(input);
                const utils = window.getPrismPhoneUtils();

                if (utils && typeof utils.getExampleNumber === 'function') {
                    try {
                        const numberType = (utils.numberType && utils.numberType.MOBILE != null)
                            ? utils.numberType.MOBILE
                            : 1;
                        // National example (true) matches separateDialCode + nationalMode display.
                        const example = utils.getExampleNumber(iso2, true, numberType)
                            || utils.getExampleNumber(iso2, true, 0);
                        const digits = String(example || '').replace(/\D/g, '');
                        if (digits.length > 0) {
                            return digits.length;
                        }
                    } catch (e) {
                        // fall through to defaults
                    }
                }

                if (iso2 === 'ph') return 10; // national digits with +63 separate dial code
                return 15;
            };

            window.applyPrismPhoneCountryRules = function (input) {
                if (!input) return;

                const maxDigits = window.getPrismPhoneMaxDigits(input);
                // Room for spaces / dashes from country-specific as-you-type formatting.
                const maxLen = Math.max(maxDigits + 8, 12);
                input.setAttribute('maxlength', String(maxLen));
                input.setAttribute('data-phone-max-digits', String(maxDigits));
                input.setAttribute('inputmode', 'numeric');

                const iti = window.PRISM_PHONE_INPUTS.get(input);
                if (iti && typeof iti.setPlaceholderNumberType === 'function') {
                    try {
                        iti.setPlaceholderNumberType('MOBILE');
                    } catch (e) {
                        // ignore
                    }
                }
            };

            window.writePrismPhoneStorage = function (input, raw) {
                const storage = window.getPrismPhoneStorage(input);
                const iti = input ? window.PRISM_PHONE_INPUTS.get(input) : null;
                const iso2 = window.getPrismPhoneCountryIso2(input);
                let formatted = '';

                if (iti && iso2 !== 'ph') {
                    try {
                        const e164 = iti.getNumber();
                        if (e164) {
                            formatted = e164;
                        }
                    } catch (e) {
                        // fall through
                    }
                }

                if (!formatted) {
                    if (iso2 === 'ph') {
                        formatted = window.formatPrismPhoneForStorage(raw);
                    } else {
                        const digits = String(raw || '').replace(/\D/g, '');
                        formatted = digits ? ('+' + digits) : '';
                    }
                }

                if (storage) {
                    storage.value = formatted;
                }
                return formatted;
            };

            window.normalizePrismPhoneDigits = function (input, digits) {
                if (!digits) return '';

                const iso2 = window.getPrismPhoneCountryIso2(input);
                const maxDigits = window.getPrismPhoneMaxDigits(input);
                let cleaned = String(digits).replace(/\D/g, '');

                if (iso2 === 'ph') {
                    if (cleaned.startsWith('63') && cleaned.length > 10) {
                        cleaned = '0' + cleaned.slice(2);
                    }

                    // Keep full national 0XXXXXXXXXX for storage helpers.
                    if (cleaned.length === 10 && cleaned.startsWith('9')) {
                        cleaned = '0' + cleaned;
                    }

                    cleaned = cleaned.replace(/^0+/, '0');
                    cleaned = cleaned.slice(0, 11);

                    if (cleaned && !cleaned.startsWith('09') && cleaned.startsWith('0')) {
                        cleaned = cleaned.replace(/^0+/, '');
                        cleaned = '09' + cleaned.slice(0, 9);
                        cleaned = cleaned.slice(0, 11);
                    }

                    return cleaned;
                }

                // International: keep country code + national digits, capped by example length + dial code headroom.
                return cleaned.slice(0, Math.max(maxDigits + 3, 15));
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

                return window.normalizePrismPhoneDigits(input, digits);
            };

            window.initPrismPhoneInput = function (input) {
                if (!input || !window.intlTelInput) {
                    return null;
                }

                // Never wrap the hidden storage field — that duplicates the flag UI.
                if (input.hasAttribute('data-phone-storage') || !input.hasAttribute('data-phone-input')) {
                    return null;
                }

                if (input.dataset.phoneInitialized === '1') {
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
                    formatAsYouType: true,
                    formatOnDisplay: true,
                    // Cap typed digits to the selected country's max valid length (libphonenumber).
                    strictMode: true,
                    placeholderNumberType: 'MOBILE',
                    validationNumberType: 'MOBILE',
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

                const applyCountryRules = function () {
                    window.applyPrismPhoneCountryRules(input);
                };

                applyCountryRules();
                if (iti.promise && typeof iti.promise.then === 'function') {
                    iti.promise.then(applyCountryRules).catch(function () {});
                }

                // When the country changes, refresh placeholder / max digits and re-sync storage.
                input.addEventListener('countrychange', function () {
                    applyCountryRules();
                    try {
                        const digits = window.readPrismPhoneDigits(input);
                        // Trim to the new country's max if the previous value was longer.
                        const maxDigits = window.getPrismPhoneMaxDigits(input);
                        const iso2 = window.getPrismPhoneCountryIso2(input);
                        if (iso2 !== 'ph' && digits) {
                            const national = String(input.value || '').replace(/\D/g, '');
                            if (national.length > maxDigits) {
                                input.value = national.slice(0, maxDigits);
                            }
                        }
                        window.writePrismPhoneStorage(input, window.readPrismPhoneDigits(input));
                    } catch (e) {
                        // ignore
                    }
                });

                // Keep storage in sync while intl-tel-input owns visible formatting.
                input.addEventListener('input', function () {
                    try {
                        if (input.dataset.phMaskApplying === '1') return;

                        const rawDigits = window.readPrismPhoneDigits(input);
                        if (rawDigits) {
                            window.writePrismPhoneStorage(input, rawDigits);
                        } else {
                            window.writePrismPhoneStorage(input, '');
                        }
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

                // If a caller passed the storage field, redirect to the visible tel input.
                if (input.hasAttribute('data-phone-storage') || !input.hasAttribute('data-phone-input')) {
                    const form = input.closest('form') || document;
                    const visible = form.querySelector('[data-phone-input][data-phone-storage-id="' + (input.id || '') + '"]')
                        || form.querySelector('[data-phone-input]');
                    if (!visible || visible === input) {
                        return null;
                    }
                    return window.refreshPrismPhoneInput(visible);
                }

                const storage = window.getPrismPhoneStorage(input);
                const keep = (storage && storage.value) || input.value || '';

                const existing = window.PRISM_PHONE_INPUTS.get(input);
                if (existing && typeof existing.destroy === 'function') {
                    existing.destroy();
                }

                // Unwrap leftover .iti wrappers from a bad double-init.
                let parent = input.parentElement;
                while (parent && parent.classList && parent.classList.contains('iti') && parent.parentElement) {
                    const grand = parent.parentElement;
                    grand.insertBefore(input, parent);
                    parent.remove();
                    parent = input.parentElement;
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
                    window.writePrismPhoneStorage(input, digits);

                    // Prefer intl-tel-input's country-aware formatting for the visible field.
                    const iti = window.PRISM_PHONE_INPUTS.get(input);
                    if (iti && digits) {
                        try {
                            const iso2 = window.getPrismPhoneCountryIso2(input);
                            if (iso2 === 'ph') {
                                const national = String(digits).replace(/\D/g, '');
                                if (national.length === 11 && national.startsWith('09') && !input.value) {
                                    input.value = window.formatPrismPhoneDisplay(national);
                                }
                            } else if (!input.value) {
                                const e164 = iti.getNumber();
                                if (e164) {
                                    iti.setNumber(e164);
                                }
                            }
                        } catch (e) {
                            // leave visible value as-is
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
    @if($placeholder !== null && $placeholder !== '') placeholder="{{ $placeholder }}" @endif
    data-phone-input
    data-phone-storage-id="{{ $storageId }}"
    @if($required) required @endif
    @if($maxlength !== null) maxlength="{{ $maxlength }}" @endif
    class="phone-input {{ $inputClass }}"
    autocomplete="tel"
    inputmode="numeric"
>
