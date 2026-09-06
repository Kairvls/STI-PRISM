@extends('layouts.app')

@section('body-class', 'pp-layout ac-layout')

@section('main-bg', 'bg-white')
@section('main-pad', 'px-4 pb-6 pt-4 sm:px-6 sm:pb-8 sm:pt-5 lg:px-8')

@section('sidebar')
    @include('layouts.accounting-sidebar')
@endsection

@section('topbar')
    @include('layouts.accounting-topbar')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/president-modern.css') }}">
<link rel="stylesheet" href="{{ asset('css/accounting-modern.css') }}">
@include('accounting.partials.ui')
@endpush

@push('scripts')
    @include('layouts.partials.prism-toast')
    @include('layouts.partials.accounting-daily-reminder')
    @include('accounting.partials.ui-scripts')
    @include('accounting.partials.print-form')
    <script>
        (function () {
            if (window.__pmFloatingTipInit) return;
            window.__pmFloatingTipInit = true;

            const tip = document.createElement('div');
            tip.id = 'pm-floating-tip';
            tip.setAttribute('role', 'tooltip');
            tip.hidden = true;
            document.body.appendChild(tip);

            let activeEl = null;

            function hideTip() {
                activeEl = null;
                tip.classList.remove('is-visible', 'is-above', 'is-below');
                tip.hidden = true;
            }

            function placeTip(el) {
                const text = (el.getAttribute('data-tip') || '').trim();
                if (!text) {
                    hideTip();
                    return;
                }

                tip.textContent = text;
                tip.hidden = false;
                tip.classList.remove('is-visible', 'is-above', 'is-below');
                tip.style.left = '0px';
                tip.style.top = '0px';

                const rect = el.getBoundingClientRect();
                const tipRect = tip.getBoundingClientRect();
                const gap = 8;
                const vw = window.innerWidth;
                const vh = window.innerHeight;

                let left = rect.left + rect.width / 2 - tipRect.width / 2;
                left = Math.max(8, Math.min(left, vw - tipRect.width - 8));

                const spaceAbove = rect.top;
                const spaceBelow = vh - rect.bottom;
                const preferBelow = spaceAbove < tipRect.height + gap + 4;

                let top;
                if (preferBelow && spaceBelow >= tipRect.height + gap) {
                    top = rect.bottom + gap;
                    tip.classList.add('is-below');
                } else if (spaceAbove >= tipRect.height + gap) {
                    top = rect.top - tipRect.height - gap;
                    tip.classList.add('is-above');
                } else if (spaceBelow >= spaceAbove) {
                    top = Math.min(vh - tipRect.height - 8, Math.max(8, rect.bottom + gap));
                    tip.classList.add('is-below');
                } else {
                    top = Math.max(8, rect.top - tipRect.height - gap);
                    tip.classList.add('is-above');
                }

                const arrowX = rect.left + rect.width / 2 - left;
                tip.style.setProperty('--pm-tip-arrow-x', Math.max(10, Math.min(arrowX, tipRect.width - 10)) + 'px');
                tip.style.left = left + 'px';
                tip.style.top = top + 'px';
                requestAnimationFrame(function () {
                    tip.classList.add('is-visible');
                });
            }

            function showTip(el) {
                activeEl = el;
                placeTip(el);
            }

            document.addEventListener('mouseover', function (e) {
                const el = e.target.closest('[data-tip]');
                if (!el || !document.body.contains(el)) return;
                if (el === activeEl) return;
                showTip(el);
            });

            document.addEventListener('mouseout', function (e) {
                if (!activeEl) return;
                const next = e.relatedTarget;
                if (next && activeEl.contains(next)) return;
                if (next && next.closest && next.closest('[data-tip]') === activeEl) return;
                hideTip();
            });

            document.addEventListener('focusin', function (e) {
                const el = e.target.closest('[data-tip]');
                if (el) showTip(el);
            });

            document.addEventListener('focusout', function () {
                hideTip();
            });

            window.addEventListener('scroll', function () {
                if (activeEl) placeTip(activeEl);
            }, true);

            window.addEventListener('resize', function () {
                if (activeEl) placeTip(activeEl);
            });
        })();

        window.pmUpdateSegControl = window.pmUpdateSegControl || function (trackOrId, activeFilter, animate) {
            const track = typeof trackOrId === 'string'
                ? document.getElementById(trackOrId)
                : trackOrId;
            if (!track) return;

            const thumb = track.querySelector('.pm-seg-thumb, .pm-filter-thumb');
            const buttons = track.querySelectorAll('.pm-seg-btn, .pm-filter-btn');
            if (!thumb || !buttons.length) return;

            let activeBtn = null;
            buttons.forEach(function (btn) {
                const isActive = btn.getAttribute('data-filter') === String(activeFilter);
                btn.classList.toggle('is-active', isActive);
                btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                btn.style.color = isActive ? '#020617' : '#64748b';
                if (isActive) activeBtn = btn;
            });
            if (!activeBtn) activeBtn = buttons[0];

            const x = activeBtn.offsetLeft;
            const w = activeBtn.offsetWidth;
            const shouldAnimate = animate !== false;
            const transition = 'transform 220ms cubic-bezier(0.22, 1, 0.36, 1), width 220ms cubic-bezier(0.22, 1, 0.36, 1)';

            if (!shouldAnimate) {
                const previous = thumb.style.transition;
                thumb.style.transition = 'none';
                thumb.style.width = w + 'px';
                thumb.style.transform = 'translate3d(' + x + 'px, 0, 0)';
                void thumb.offsetWidth;
                thumb.style.transition = previous || transition;
            } else {
                thumb.style.width = w + 'px';
                thumb.style.transform = 'translate3d(' + x + 'px, 0, 0)';
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) window.lucide.createIcons();
            document.querySelectorAll('.pm-seg[data-active], .pm-filter-group[data-active]').forEach(function (track) {
                window.pmUpdateSegControl(track, track.getAttribute('data-active') || 'all', false);
            });
        });
    </script>
@endpush
