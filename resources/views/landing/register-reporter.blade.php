<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Reporter details | PaAyo</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico?v=1') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --blue: #0025cc;
            --blue-dark: #001ca3;
            --ink: #1a1a2e;
            --muted: #6b7280;
            --line: #e8ecf4;
            --soft: #f3f6ff;
        }
        html, body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--soft);
            color: var(--ink);
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .field {
            width: 100%;
            height: 46px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: #fff;
            padding: 0 14px;
            font-size: 14px;
            outline: none;
        }
        .field:focus { border-color: #9db0ff; box-shadow: 0 0 0 4px rgba(0, 37, 204, .08); }
        .field[readonly] { background: #f8f9fd; color: #4b5563; }
        .phone-field {
            display: flex;
            align-items: center;
            height: 46px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: #fff;
            overflow: hidden;
        }
        .phone-field:focus-within {
            border-color: #9db0ff;
            box-shadow: 0 0 0 4px rgba(0, 37, 204, .08);
        }
        .phone-prefix {
            flex-shrink: 0;
            padding: 0 0 0 14px;
            font-size: 14px;
            font-weight: 600;
            color: var(--ink);
            letter-spacing: .04em;
        }
        .phone-field input {
            flex: 1;
            min-width: 0;
            height: 100%;
            border: 0;
            background: transparent;
            padding: 0 14px 0 0;
            font-size: 14px;
            font-family: inherit;
            color: var(--ink);
            outline: none;
            letter-spacing: .04em;
        }
        select.field {
            appearance: none;
            -webkit-appearance: none;
            background-image: none;
        }
        .type-trigger {
            width: 100%;
            height: 46px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: #fff;
            padding: 0 14px;
            font-size: 14px;
            font-family: inherit;
            color: var(--ink);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            cursor: pointer;
            text-align: left;
        }
        .type-trigger.is-placeholder { color: #9aa1b5; }
        .type-trigger svg {
            width: 18px;
            height: 18px;
            color: var(--blue);
            flex-shrink: 0;
        }
        .type-picker {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: none;
            align-items: flex-end;
            justify-content: center;
            background: rgba(11, 18, 32, .7);
            padding: 12px;
        }
        .type-picker.is-open { display: flex; }
        .type-sheet {
            width: min(100%, 420px);
            background: #fff;
            border-radius: 24px 24px 18px 18px;
            box-shadow: 0 28px 70px rgba(15, 23, 42, .22);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding: 18px 18px 16px;
        }
        @media (min-width: 768px) {
            .type-picker { align-items: center; }
            .type-sheet { border-radius: 24px; }
        }
        .type-sheet-kicker {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--blue);
            margin-bottom: 4px;
        }
        .type-sheet-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--ink);
            margin-bottom: 14px;
        }
        .type-option {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            text-align: left;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 16px 16px;
            margin-bottom: 10px;
            cursor: pointer;
            color: var(--ink);
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
        }
        .type-option.is-active {
            border-color: var(--blue);
            background: var(--soft);
            color: var(--blue);
            box-shadow: 0 0 0 4px rgba(0, 37, 204, .08);
        }
        .type-option-hint {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--muted);
            margin-top: 3px;
        }
        .type-option.is-active .type-option-hint { color: #6474c7; }
        .type-done {
            margin-top: 6px;
            height: 52px;
            border: 0;
            border-radius: 999px;
            background: var(--blue);
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            font-family: inherit;
            cursor: pointer;
        }
        .type-error {
            display: none;
            font-size: 12px;
            font-weight: 600;
            color: #dc2626;
            margin: 0 0 10px;
        }
        .type-error.is-visible { display: block; }
        .btn-blue {
            background: var(--blue);
            color: #fff;
            font-weight: 700;
            border: 0;
            border-radius: 999px;
            padding: 12px 22px;
            cursor: pointer;
        }
        .btn-blue:hover { background: var(--blue-dark); }
    </style>
</head>
<body class="min-h-screen px-4 py-10">
    <div class="max-w-[560px] mx-auto">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 no-underline mb-6" style="color:var(--ink);">
            <span class="font-extrabold tracking-tight text-lg">Pa<span style="color:var(--blue);">Ayo</span></span>
        </a>

        <div class="rounded-3xl bg-white p-6 md:p-8" style="border:1px solid var(--line); box-shadow:0 24px 60px rgba(15,23,42,.08);">
            <p class="text-xs font-bold tracking-[0.16em] uppercase mb-2" style="color:var(--blue);">Reporter details</p>
            <h1 class="text-2xl font-extrabold mb-2">Who is submitting this report?</h1>
            <p class="text-sm leading-relaxed mb-6" style="color:var(--muted);">
                    Faculty and staff fill this once. Maintenance personnel will confirm you are faculty or staff before you can submit reports with your employee ID.
            </p>

            @if ($errors->any())
                <div class="mb-5 rounded-xl px-4 py-3 text-sm" style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('reporter.register.complete', $token) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color:var(--muted);">Email address</label>
                    <input type="email" value="{{ $email }}" readonly class="field">
                    <p class="text-[12px] mt-1.5" style="color:var(--muted);">Verified from the link we sent. This cannot be changed here.</p>
                </div>

                <div>
                    <label for="employee_id" class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color:var(--muted);">Employee ID <span style="color:#e11d48;">*</span></label>
                    <input id="employee_id" name="employee_id" type="text" value="{{ old('employee_id') }}" required maxlength="100" placeholder="OMC****F" class="field">
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color:var(--muted);">First name <span style="color:#e11d48;">*</span></label>
                        <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" required maxlength="100" class="field">
                    </div>
                    <div>
                        <label for="middle_name" class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color:var(--muted);">Middle name</label>
                        <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name') }}" maxlength="100" placeholder="Optional" class="field">
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="last_name" class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color:var(--muted);">Last name <span style="color:#e11d48;">*</span></label>
                        <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" required maxlength="100" class="field">
                    </div>
                    <div>
                        <label for="type" class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color:var(--muted);">Type <span style="color:#e11d48;">*</span></label>
                        <div class="relative">
                            <select id="type" name="type" required class="sr-only">
                                <option value="">Select type</option>
                                <option value="Faculty" @selected(old('type') === 'Faculty')>Faculty</option>
                                <option value="Staff" @selected(old('type') === 'Staff')>Staff</option>
                            </select>
                            <button type="button" id="typeTrigger" class="type-trigger {{ old('type') ? '' : 'is-placeholder' }}">
                                <span id="typeTriggerLabel">{{ old('type') ?: 'Select type' }}</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="contact_rest" class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color:var(--muted);">Contact number <span style="color:#e11d48;">*</span></label>
                    @php
                        $oldContact = preg_replace('/\D+/', '', (string) old('contact', ''));
                        $oldRestDigits = str_starts_with($oldContact, '09') ? substr($oldContact, 2) : $oldContact;
                        $oldRestDigits = substr($oldRestDigits, 0, 9);
                        if (strlen($oldRestDigits) <= 2) {
                            $oldRest = $oldRestDigits;
                        } elseif (strlen($oldRestDigits) <= 5) {
                            $oldRest = substr($oldRestDigits, 0, 2).' '.substr($oldRestDigits, 2);
                        } else {
                            $oldRest = substr($oldRestDigits, 0, 2).' '.substr($oldRestDigits, 2, 3).' '.substr($oldRestDigits, 5, 4);
                        }
                    @endphp
                    <div class="phone-field">
                        <span class="phone-prefix">09</span>
                        <input id="contact_rest" type="text" inputmode="numeric" maxlength="11" placeholder="XX XXX XXXX" value="{{ $oldRest }}" required autocomplete="tel-national">
                    </div>
                    <input type="hidden" id="contact" name="contact" value="{{ old('contact') }}">
                    <p class="text-[12px] mt-1.5" style="color:var(--muted);">Philippine mobile, 11 digits. Start with: 09</p>
                </div>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <a href="{{ url('/') }}" class="text-sm font-semibold no-underline" style="color:var(--muted);">Back to home</a>
                    <button type="submit" class="btn-blue text-sm">Submit & wait for approval</button>
                </div>
            </form>
        </div>
    </div>

    <div id="typePicker" class="type-picker" hidden>
        <div class="type-sheet" role="dialog" aria-modal="true" aria-labelledby="typePickerTitle">
            <div class="type-sheet-kicker">Choose an option</div>
            <div class="type-sheet-title" id="typePickerTitle">Select type</div>
            <button type="button" class="type-option" data-value="Faculty">
                <span>
                    Faculty
                    <span class="type-option-hint">Teaching personnel</span>
                </span>
            </button>
            <button type="button" class="type-option" data-value="Staff">
                <span>
                    Staff
                    <span class="type-option-hint">Non-teaching personnel</span>
                </span>
            </button>
            <p id="typePickerError" class="type-error">Please select Faculty or Staff first.</p>
            <button type="button" class="type-done" id="typePickerDone">Done</button>
        </div>
    </div>

    <script>
        (function () {
            const select = document.getElementById('type');
            const trigger = document.getElementById('typeTrigger');
            const label = document.getElementById('typeTriggerLabel');
            const overlay = document.getElementById('typePicker');
            const done = document.getElementById('typePickerDone');
            const error = document.getElementById('typePickerError');
            if (!select || !trigger || !overlay) return;

            let pendingValue = select.value || '';

            const syncOptions = (value) => {
                overlay.querySelectorAll('.type-option').forEach((btn) => {
                    btn.classList.toggle('is-active', btn.dataset.value === value);
                });
            };

            const hideError = () => {
                if (error) error.classList.remove('is-visible');
            };

            const showError = () => {
                if (error) error.classList.add('is-visible');
            };

            const openPicker = () => {
                pendingValue = select.value || '';
                hideError();
                syncOptions(pendingValue);
                overlay.hidden = false;
                overlay.classList.add('is-open');
            };

            const closePicker = () => {
                hideError();
                overlay.classList.remove('is-open');
                overlay.hidden = true;
            };

            trigger.addEventListener('click', openPicker);

            done.addEventListener('click', () => {
                if (!pendingValue) {
                    showError();
                    return;
                }
                select.value = pendingValue;
                label.textContent = pendingValue;
                trigger.classList.remove('is-placeholder');
                closePicker();
            });

            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) closePicker();
            });

            overlay.querySelectorAll('.type-option').forEach((btn) => {
                btn.addEventListener('click', () => {
                    pendingValue = btn.dataset.value;
                    hideError();
                    syncOptions(pendingValue);
                });
            });
        })();

        (function () {
            const rest = document.getElementById('contact_rest');
            const full = document.getElementById('contact');
            const normalizeRest = (raw) => {
                let digits = String(raw || '').replace(/\D/g, '');
                if (digits.startsWith('09') && digits.length >= 11) {
                    digits = digits.slice(2);
                } else if (digits.startsWith('9') && digits.length === 10) {
                    digits = digits.slice(1);
                }
                return digits.slice(0, 9);
            };
            const formatRest = (digits) => {
                const a = digits.slice(0, 2);
                const b = digits.slice(2, 5);
                const c = digits.slice(5, 9);
                if (digits.length <= 2) return a;
                if (digits.length <= 5) return a + ' ' + b;
                return a + ' ' + b + ' ' + c;
            };
            const caretFromDigits = (formatted, digitCount) => {
                if (digitCount <= 0) return 0;
                let seen = 0;
                for (let i = 0; i < formatted.length; i++) {
                    if (/\d/.test(formatted[i])) {
                        seen += 1;
                        if (seen === digitCount) return i + 1;
                    }
                }
                return formatted.length;
            };
            const syncContact = (keepCaret) => {
                if (!rest || !full) return;
                const digitsBefore = rest.value.slice(0, rest.selectionStart || 0).replace(/\D/g, '').length;
                const digits = normalizeRest(rest.value);
                const formatted = formatRest(digits);
                rest.value = formatted;
                full.value = digits.length ? ('09' + digits) : '';
                if (keepCaret) {
                    const pos = caretFromDigits(formatted, digitsBefore);
                    rest.setSelectionRange(pos, pos);
                }
            };
            if (rest && full) {
                rest.addEventListener('input', () => syncContact(true));
                rest.form && rest.form.addEventListener('submit', (e) => {
                    syncContact(false);
                    if (!/^09[0-9]{9}$/.test(full.value)) {
                        e.preventDefault();
                        rest.setCustomValidity('Enter a complete 11-digit mobile number.');
                        rest.reportValidity(); 
                        return;
                    }
                    rest.setCustomValidity('');
                });
                syncContact(false);
            }
        })();
    </script>
</body>
</html>
