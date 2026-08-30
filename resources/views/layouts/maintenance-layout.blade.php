@extends ("layouts.app")

@section ("body-class", "mp-layout")

@section ("main-bg", "bg-white")
@section ("main-pad", "px-8 pb-8 pt-5")

@section ("sidebar")
    @include ("layouts.maintenance-sidebar")

@endsection

@section ("topbar")
    @include ("layouts.maintenance-topbar")

@endsection

@push('scripts')
<style>
    /* =====================================================
       Maintenance toasts — Cursor-like clean minimalist card
       ===================================================== */

    body.mp-layout {
        --mp-toast-blue: #0025cc;
    }

    #mp-toast-host {
        position: fixed;
        right: 20px;
        bottom: 20px;
        z-index: 99999;
        display: flex;
        flex-direction: column-reverse;
        gap: 10px;
        width: min(340px, calc(100vw - 2rem));
        pointer-events: none;
    }

    .mp-toast {
        pointer-events: auto;
        width: 100%;
        padding: 12px 14px 14px;
        border: 1px solid #e8e8e8;
        border-radius: 10px;
        background: #ffffff;
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.04),
            0 10px 28px rgba(15, 23, 42, 0.08);
        color: #0a0a0a;
        opacity: 0;
        transform: translateY(8px);
        transition: opacity .2s ease, transform .2s ease;
    }

    .mp-toast.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .mp-toast-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }

    .mp-toast-brand {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .mp-toast-brand-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        border-radius: 4px;
        background: var(--mp-toast-blue);
        color: #ffffff;
    }

    .mp-toast-brand-icon svg {
        width: 11px;
        height: 11px;
    }

    .mp-toast-brand-name {
        font-size: 12px;
        font-weight: 500;
        color: #0a0a0a;
        letter-spacing: -0.01em;
    }

    .mp-toast-actions {
        display: flex;
        align-items: center;
        gap: 2px;
        flex-shrink: 0;
    }

    .mp-toast-close {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: #6b7280;
        cursor: pointer;
        transition: background .15s ease, color .15s ease;
    }

    .mp-toast-close:hover {
        background: #f3f4f6;
        color: #0a0a0a;
    }

    .mp-toast-close svg {
        width: 14px;
        height: 14px;
    }

    .mp-toast-title {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.35;
        color: #0a0a0a;
        letter-spacing: -0.01em;
    }

    .mp-toast-message {
        margin: 4px 0 0;
        font-size: 12px;
        font-weight: 400;
        line-height: 1.4;
        color: #6b7280;
    }

    /* Alpine inline toast (infrastructure) */
    .mp-toast.mp-toast-inline {
        opacity: 1;
        transform: none;
        width: min(340px, calc(100vw - 2rem));
    }

    /* Hide native Swal toasts; we redirect them to showMpToast */
    body.mp-layout .swal2-container.swal2-toast-shown {
        display: none !important;
    }

    /* Live report toast → same card language */
    body.mp-layout #report-toast {
        right: 20px;
        bottom: 20px;
        width: min(340px, calc(100vw - 2rem));
        padding: 12px 14px 14px;
        border: 1px solid #e8e8e8;
        border-radius: 10px;
        background: #ffffff;
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.04),
            0 10px 28px rgba(15, 23, 42, 0.08);
        gap: 0;
        flex-direction: column;
        align-items: stretch;
    }

    body.mp-layout #report-toast .toast-icon {
        display: none;
    }

    body.mp-layout #report-toast .toast-content {
        width: 100%;
    }

    body.mp-layout #report-toast .toast-title {
        font-size: 13px;
        font-weight: 600;
        color: #0a0a0a;
    }

    body.mp-layout #report-toast .toast-message {
        margin-top: 4px;
        font-size: 12px;
        font-weight: 400;
        color: #6b7280;
    }

    body.mp-layout #report-toast .toast-subtitle {
        margin-top: 2px;
        font-size: 12px;
        color: #6b7280;
    }

    /* Messaging toast (maintenance) */
    body.mp-layout #messageToastContainer {
        right: 20px !important;
        bottom: 20px !important;
        width: min(340px, calc(100vw - 2rem));
    }

    body.mp-layout #messageToastContainer > button,
    body.mp-layout #messageToastContainer > .mp-msg-toast {
        border: 1px solid #e8e8e8 !important;
        border-radius: 10px !important;
        background: #ffffff !important;
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.04),
            0 10px 28px rgba(15, 23, 42, 0.08) !important;
        padding: 12px 14px !important;
    }

    body.mp-layout #messageToastContainer [class*='bg-blue-'] {
        background-color: var(--mp-toast-blue) !important;
        border-radius: 4px !important;
        width: 18px !important;
        height: 18px !important;
    }

    body.mp-layout #messageToastContainer [class*='bg-blue-'] svg,
    body.mp-layout #messageToastContainer [class*='bg-blue-'] i {
        width: 11px !important;
        height: 11px !important;
    }
</style>

<script>
    (function () {
        function mpToastIconSvg(type) {
            if (type === 'error') {
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
            }
            if (type === 'warning') {
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>';
            }
            if (type === 'info') {
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>';
            }
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
        }

        function ensureHost() {
            let host = document.getElementById('mp-toast-host');
            if (!host) {
                host = document.createElement('div');
                host.id = 'mp-toast-host';
                document.body.appendChild(host);
            }
            return host;
        }

        function formatTitle(title, type) {
            const clean = String(title || '').replace(/^✔\s*/, '').trim();
            if (!clean) {
                return type === 'error' ? 'Something went wrong' : 'Done';
            }
            if (type === 'success' && !/^done\b/i.test(clean) && !clean.includes('•')) {
                return 'Done • ' + clean;
            }
            if (type === 'error' && !/^error\b/i.test(clean) && !clean.includes('•')) {
                return 'Error • ' + clean;
            }
            if (type === 'warning' && !/^warning\b/i.test(clean) && !clean.includes('•')) {
                return 'Warning • ' + clean;
            }
            return clean;
        }

        window.showMpToast = function (message, options) {
            const opts = typeof message === 'object' && message !== null
                ? message
                : (options || {});

            const rawMessage = typeof message === 'object' && message !== null
                ? (opts.message || opts.text || '')
                : (message || '');

            const type = opts.type || 'success';
            const timer = opts.timer == null ? 3200 : opts.timer;
            const title = formatTitle(opts.title || rawMessage, type);
            const subtitle = opts.title
                ? String(rawMessage || opts.subtitle || opts.text || '').trim()
                : String(opts.subtitle || opts.text || '').trim();

            const host = ensureHost();
            const toast = document.createElement('div');
            toast.className = 'mp-toast';
            toast.setAttribute('role', 'status');
            toast.innerHTML =
                '<div class="mp-toast-top">' +
                    '<div class="mp-toast-brand">' +
                        '<span class="mp-toast-brand-icon">' + mpToastIconSvg(type) + '</span>' +
                        '<span class="mp-toast-brand-name">PRISM</span>' +
                    '</div>' +
                    '<div class="mp-toast-actions">' +
                        '<button type="button" class="mp-toast-close" aria-label="Dismiss">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>' +
                        '</button>' +
                    '</div>' +
                '</div>' +
                '<p class="mp-toast-title"></p>' +
                (subtitle ? '<p class="mp-toast-message"></p>' : '');

            toast.querySelector('.mp-toast-title').textContent = title;
            if (subtitle) {
                toast.querySelector('.mp-toast-message').textContent = subtitle;
            }

            const dismiss = function () {
                toast.classList.remove('is-visible');
                setTimeout(function () {
                    toast.remove();
                }, 200);
            };

            toast.querySelector('.mp-toast-close').addEventListener('click', dismiss);
            host.appendChild(toast);
            requestAnimationFrame(function () {
                toast.classList.add('is-visible');
            });

            if (timer > 0) {
                setTimeout(dismiss, timer);
            }

            return { close: dismiss };
        };

        function enhanceSwalToasts() {
            if (!window.Swal || Swal.__mpToastWrapped) {
                return;
            }

            const originalFire = Swal.fire.bind(Swal);

            Swal.fire = function (options) {
                // Toast calls → custom PRISM toast card
                if (options && typeof options === 'object' && options.toast) {
                    const icon = options.icon || 'success';
                    const type = icon === 'error' || icon === 'warning' || icon === 'info'
                        ? icon
                        : 'success';

                    let subtitle = '';
                    if (typeof options.text === 'string') {
                        subtitle = options.text;
                    } else if (typeof options.html === 'string') {
                        const tmp = document.createElement('div');
                        tmp.innerHTML = options.html;
                        subtitle = (tmp.textContent || '').trim();
                    }

                    window.showMpToast(subtitle, {
                        title: options.title || '',
                        type: type,
                        timer: options.timer == null ? 3200 : options.timer,
                    });

                    return Promise.resolve({ isConfirmed: false, isDenied: false, isDismissed: true });
                }

                return originalFire(options);
            };

            Swal.__mpToastWrapped = true;
        }

        // Report toast → PRISM card
        const originalShowReportToast = window.showReportToast;
        window.showReportToast = function (report) {
            if (!report) {
                return;
            }
            window.showMpToast(
                'Report #' + report.report_id + (report.report_urgency_level ? ' • ' + report.report_urgency_level : ''),
                {
                    title: 'New maintenance report',
                    type: 'info',
                    timer: 4500,
                }
            );
            if (typeof originalShowReportToast === 'function' && !document.body.classList.contains('mp-layout')) {
                originalShowReportToast(report);
            }
        };

        // Session flash → toast
        document.addEventListener('DOMContentLoaded', function () {
            enhanceSwalToasts();

            const flashSuccess = @json(session('success'));
            const flashError = @json(session('error'));
            const flashStatus = @json(session('status'));

            if (flashSuccess) {
                window.showMpToast(flashSuccess, { title: 'Success', type: 'success' });
            }
            if (flashError) {
                window.showMpToast(flashError, { title: 'Unable to complete', type: 'error', timer: 4200 });
            }
            if (flashStatus && !flashSuccess) {
                window.showMpToast(flashStatus, { title: 'Update', type: 'info' });
            }
        });

        if (document.readyState !== 'loading') {
            enhanceSwalToasts();
        }
    })();
</script>
<script>
    (function () {
        const ITEM_H = 40;
        const MAX_VISIBLE = 5;
        let panel = null;
        let openSelect = null;
        let uid = 0;

        function closeMpSelect() {
            if (panel) {
                panel.remove();
                panel = null;
            }
            openSelect = null;
        }

        function shouldSkip(select) {
            if (!select || select.tagName !== 'SELECT') {
                return true;
            }
            if (select.multiple || Number(select.getAttribute('size') || 0) > 1) {
                return true;
            }
            if (select.dataset.nativeSelect === '1') {
                return true;
            }
            return false;
        }

        function bindSelect(select) {
            if (shouldSkip(select) || select.dataset.mpSelectBound === '1') {
                return;
            }
            if (!select.id) {
                select.id = 'mp-select-' + (++uid);
            }
            select.dataset.mpSelectBound = '1';

            select.addEventListener('mousedown', function (event) {
                if (event.button !== 0 || select.disabled) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                if (openSelect === select) {
                    closeMpSelect();
                    return;
                }
                openMpSelect(select);
            });

            select.addEventListener('keydown', function (event) {
                if (select.disabled) {
                    return;
                }
                if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown') {
                    event.preventDefault();
                    openMpSelect(select);
                }
            });
        }

        function placePanel(select) {
            if (!panel || !select) {
                return;
            }

            const rect = select.getBoundingClientRect();
            const count = Math.min(select.options.length, MAX_VISIBLE);
            const height = count * ITEM_H + 8;
            const width = Math.max(rect.width, 160);
            panel.style.width = width + 'px';
            panel.style.maxHeight = (MAX_VISIBLE * ITEM_H + 8) + 'px';
            panel.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8)) + 'px';

            const spaceBelow = window.innerHeight - rect.bottom - 8;
            const spaceAbove = rect.top - 8;
            const openUp = spaceBelow < height && spaceAbove > spaceBelow;

            if (openUp) {
                panel.style.top = Math.max(8, rect.top - height - 4) + 'px';
            } else {
                panel.style.top = (rect.bottom + 4) + 'px';
            }
        }

        function openMpSelect(select) {
            closeMpSelect();
            select.focus();

            panel = document.createElement('div');
            panel.setAttribute('data-mp-select-panel', '1');
            panel.className = 'overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-xl shadow-slate-950/15';
            panel.style.position = 'fixed';
            panel.style.zIndex = '2147483646';

            Array.from(select.options).forEach(function (option) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className =
                    'flex h-10 w-full items-center px-3.5 text-left text-sm transition hover:bg-slate-50 ' +
                    (option.disabled ? 'cursor-not-allowed text-slate-300 ' : '') +
                    (option.value === select.value && !option.disabled ? 'font-medium text-slate-900' : 'text-slate-600');
                item.textContent = option.text;
                item.disabled = option.disabled;
                item.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                });
                item.addEventListener('click', function () {
                    if (option.disabled) {
                        return;
                    }
                    select.value = option.value;
                    select.dispatchEvent(new Event('input', { bubbles: true }));
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    closeMpSelect();
                });
                panel.appendChild(item);
            });

            document.body.appendChild(panel);
            placePanel(select);
            openSelect = select;

            panel.addEventListener('wheel', function (event) {
                event.stopPropagation();
            }, { passive: true });
        }

        function setMpSelectValue(id, value) {
            const select = document.getElementById(id);
            if (!select) {
                return;
            }
            select.value = value;
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function scan() {
            document.querySelectorAll('select').forEach(bindSelect);
        }

        window.mpSelect = {
            close: closeMpSelect,
            setValue: setMpSelectValue,
            scan: scan,
        };
        window.closeEqSelectPanel = closeMpSelect;
        window.setEqSelectValue = setMpSelectValue;

        scan();
        new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });

        document.addEventListener('mousedown', function (event) {
            if (!panel) {
                return;
            }
            if (panel.contains(event.target) || event.target === openSelect) {
                return;
            }
            closeMpSelect();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMpSelect();
            }
        });

        window.addEventListener('resize', function () {
            if (openSelect) {
                placePanel(openSelect);
            }
        });

        window.addEventListener('scroll', function (event) {
            if (!openSelect || !panel) {
                return;
            }
            if (panel.contains(event.target)) {
                return;
            }
            placePanel(openSelect);
        }, true);
    })();
</script>

@include('layouts.partials.maintenance-daily-reminder')
@endpush
