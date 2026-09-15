@if(session('success') || session('error') || session('status') || (isset($errors) && $errors->any()))
<style>
    .swal2-container.prism-swal-container {
        z-index: 100000 !important;
    }
    .prism-swal.swal2-popup {
        width: min(28rem, calc(100vw - 2rem)) !important;
        padding: 1rem 1.2rem !important;
        border-radius: 0.85rem !important;
        background: #ffffff !important;
        border: 1px solid #e5e7eb !important;
        box-shadow: 0 8px 28px rgba(15, 23, 42, 0.08) !important;
        color: #0f172a !important;
    }
    .prism-swal .swal2-icon { display: none !important; }
    .prism-swal .swal2-title {
        font-size: 0.9375rem !important;
        font-weight: 500 !important;
        line-height: 1.5 !important;
        padding: 0 !important;
        margin: 0 !important;
        color: #0f172a !important;
        text-align: left !important;
    }
    .prism-swal .swal2-html-container {
        margin: 0.55rem 0 0 !important;
        padding: 0 !important;
        font-size: 0.875rem !important;
        line-height: 1.5 !important;
        color: #334155 !important;
        text-align: left !important;
    }
    .prism-swal .swal2-actions {
        display: none !important;
    }
    .prism-swal .swal2-timer-progress-bar {
        background: #0025cc !important;
    }
    .prism-swal-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .prism-swal-list li + li { margin-top: 0.3rem; }
    .prism-swal--ok,
    .prism-swal--err {
        background: #ffffff !important;
        border: 1px solid #e5e7eb !important;
        color: #0f172a !important;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Layouts with PRISM toasts (admin / accounting / president / receiving / maintenance)
        // already show session flashes — do not also open SweetAlert there.
        if (!window.Swal || window.__prismFlashShown || typeof window.showMpToast === 'function') {
            return;
        }
        window.__prismFlashShown = true;

        const flashOpts = {
            icon: false,
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            buttonsStyling: false,
            backdrop: false,
            customClass: {
                container: 'prism-swal-container',
            },
        };

        @if(session('success'))
            Swal.fire(Object.assign({}, flashOpts, {
                title: @json(session('success')),
                customClass: Object.assign({}, flashOpts.customClass, {
                    popup: 'prism-swal prism-swal--ok',
                }),
            }));
        @elseif(session('error'))
            Swal.fire(Object.assign({}, flashOpts, {
                title: @json(session('error')),
                timer: 4000,
                customClass: Object.assign({}, flashOpts.customClass, {
                    popup: 'prism-swal prism-swal--err',
                }),
            }));
        @elseif(isset($errors) && $errors->any())
            Swal.fire(Object.assign({}, flashOpts, {
                title: 'Please check the form',
                html: @json('<ul class="prism-swal-list">'.collect($errors->all())->unique()->map(fn ($error) => '<li>'.e($error).'</li>')->implode('').'</ul>'),
                timer: 4000,
                customClass: Object.assign({}, flashOpts.customClass, {
                    popup: 'prism-swal prism-swal--err',
                }),
            }));
        @elseif(session('status'))
            Swal.fire(Object.assign({}, flashOpts, {
                title: @json(session('status')),
                customClass: Object.assign({}, flashOpts.customClass, {
                    popup: 'prism-swal prism-swal--ok',
                }),
            }));
        @endif
    });
</script>
@endif
