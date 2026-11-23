/**
 * Fast, non-blocking click feedback for role sidebar + portal switcher links.
 *
 * Intentionally avoids idle/hover background page prefetching so single-threaded
 * local PHP servers (php artisan serve on Windows) and database sessions are never
 * queued behind speculative full-page renders.
 */
(function () {
    if (window.PrismSidebarNavFast) {
        return;
    }

    var progressEl = null;
    var progressTimer = null;

    function absoluteUrl(href) {
        try {
            return new URL(href, window.location.href).href;
        } catch (e) {
            return '';
        }
    }

    function sameOrigin(url) {
        try {
            return new URL(url).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function shouldSkip(anchor) {
        if (!anchor || !anchor.getAttribute) {
            return true;
        }
        if (anchor.hasAttribute('data-no-prefetch') || anchor.hasAttribute('download')) {
            return true;
        }
        if (anchor.target && String(anchor.target).toLowerCase() === '_blank') {
            return true;
        }
        var href = String(anchor.getAttribute('href') || '').trim();
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0 || href.indexOf('mailto:') === 0) {
            return true;
        }
        var url = absoluteUrl(href);
        if (!url || !sameOrigin(url)) {
            return true;
        }
        if (url.split('#')[0] === window.location.href.split('#')[0]) {
            return true;
        }
        return false;
    }

    function collectAnchors() {
        return Array.prototype.slice.call(
            document.querySelectorAll(
                '#sidebar a[href], [data-portal-switcher] a[href], a[data-nav-prefetch][href]'
            )
        ).filter(function (anchor) {
            return !shouldSkip(anchor);
        });
    }

    function ensureProgress() {
        if (progressEl) {
            return progressEl;
        }
        progressEl = document.createElement('div');
        progressEl.id = 'prismSidebarNavProgress';
        progressEl.setAttribute('aria-hidden', 'true');
        progressEl.innerHTML = '<span></span>';
        document.body.appendChild(progressEl);
        return progressEl;
    }

    function startProgress() {
        var bar = ensureProgress();
        bar.classList.add('is-active');
        bar.classList.remove('is-done');
        if (progressTimer) {
            clearTimeout(progressTimer);
        }
        progressTimer = setTimeout(function () {
            bar.classList.add('is-done');
        }, 12000);
    }

    function resetProgress() {
        document.documentElement.classList.remove('prism-nav-leaving');
        if (progressTimer) {
            clearTimeout(progressTimer);
            progressTimer = null;
        }
        if (progressEl) {
            progressEl.classList.remove('is-active', 'is-done');
        }
    }

    function bindAnchor(anchor) {
        if (anchor.dataset.prismNavFastBound === '1') {
            return;
        }
        anchor.dataset.prismNavFastBound = '1';

        anchor.addEventListener(
            'click',
            function (event) {
                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }
                if (shouldSkip(anchor)) {
                    return;
                }
                startProgress();
                document.documentElement.classList.add('prism-nav-leaving');
            },
            { passive: true }
        );
    }

    function boot() {
        collectAnchors().forEach(bindAnchor);
    }

    window.addEventListener('pageshow', resetProgress);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.PrismSidebarNavFast = {
        warm: function () {},
        refresh: boot,
    };
})();
