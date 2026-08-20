{{--
    Shared PRISM toast (same API as Maintenance showMpToast).
    Include from module layouts only — do not edit Maintenance to use this.
--}}
<style>
    body.pp-layout {
        --mp-toast-blue: #0025cc;
    }

    body.pp-layout #mp-toast-host {
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

    body.pp-layout .mp-toast {
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

    body.pp-layout .mp-toast.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    body.pp-layout .mp-toast-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }

    body.pp-layout .mp-toast-brand {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    body.pp-layout .mp-toast-brand-icon {
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

    body.pp-layout .mp-toast-brand-icon svg {
        width: 11px;
        height: 11px;
    }

    body.pp-layout .mp-toast-brand-name {
        font-size: 12px;
        font-weight: 500;
        color: #0a0a0a;
        letter-spacing: -0.01em;
    }

    body.pp-layout .mp-toast-actions {
        display: flex;
        align-items: center;
        gap: 2px;
        flex-shrink: 0;
    }

    body.pp-layout .mp-toast-close {
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

    body.pp-layout .mp-toast-close:hover {
        background: #f3f4f6;
        color: #0a0a0a;
    }

    body.pp-layout .mp-toast-close svg {
        width: 14px;
        height: 14px;
    }

    body.pp-layout .mp-toast-title {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.35;
        color: #0a0a0a;
        letter-spacing: -0.01em;
    }

    body.pp-layout .mp-toast-message {
        margin: 4px 0 0;
        font-size: 12px;
        font-weight: 400;
        line-height: 1.4;
        color: #6b7280;
    }

    body.pp-layout .swal2-container.swal2-toast-shown {
        display: none !important;
    }

    body.pp-layout #messageToastContainer {
        right: 20px !important;
        bottom: 20px !important;
        width: min(340px, calc(100vw - 2rem));
    }

    body.pp-layout #messageToastContainer > button,
    body.pp-layout #messageToastContainer > .mp-msg-toast {
        border: 1px solid #e8e8e8 !important;
        border-radius: 10px !important;
        background: #ffffff !important;
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.04),
            0 10px 28px rgba(15, 23, 42, 0.08) !important;
        padding: 12px 14px !important;
    }

    body.pp-layout #messageToastContainer [class*='bg-blue-'] {
        background-color: var(--mp-toast-blue) !important;
        border-radius: 4px !important;
        width: 18px !important;
        height: 18px !important;
    }

    body.pp-layout #messageToastContainer [class*='bg-blue-'] svg,
    body.pp-layout #messageToastContainer [class*='bg-blue-'] i {
        width: 11px !important;
        height: 11px !important;
    }

    @media (max-width: 640px) {
        body.pp-layout #mp-toast-host {
            right: 12px;
            bottom: 12px;
            left: 12px;
            width: auto;
        }
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

        // Convenience alias used by President approval pages
        window.showToast = function (message, options) {
            if (typeof options === 'string') {
                return window.showMpToast(message, { title: options, type: 'success' });
            }
            return window.showMpToast(message, options || { title: 'Success', type: 'success' });
        };

        function enhanceSwalToasts() {
            if (!window.Swal || Swal.__mpToastWrapped) {
                return;
            }

            const originalFire = Swal.fire.bind(Swal);

            Swal.fire = function (options) {
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
