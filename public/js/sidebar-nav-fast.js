/**
 * Faster module switching for every role sidebar + portal switcher.
 * - Prefetch same-origin destinations on hover / focus / touch / idle
 * - Immediate click feedback (top progress bar)
 * Speculation Rules when the browser supports them
 */
(function () {
    if (window.PrismSidebarNavFast) {
        return;
    }

    var warmed = new Set();
    var inflight = new Map();
    var progressEl = null;
    var progressTimer = null;
    var IDLE_WARM_LIMIT = 6;

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

    function warm(url) {
        url = absoluteUrl(url);
        if (!url || !sameOrigin(url) || warmed.has(url) || inflight.has(url)) {
            return;
        }

        var link = document.createElement('link');
        link.rel = 'prefetch';
        link.as = 'document';
        link.href = url;
        document.head.appendChild(link);

        var request = fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Purpose: 'prefetch',
                'Sec-Purpose': 'prefetch',
                'X-Prism-Prefetch': '1',
            },
            priority: 'low',
        })
            .then(function () {
                warmed.add(url);
            })
            .catch(function () {
                /* ignore failed warm */
            })
            .finally(function () {
                if (inflight.get(url) === request) {
                    inflight.delete(url);
                }
            });

        inflight.set(url, request);
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

    function bindAnchor(anchor) {
        if (anchor.dataset.prismNavFastBound === '1') {
            return;
        }
        anchor.dataset.prismNavFastBound = '1';

        var url = absoluteUrl(anchor.href);
        var warmNow = function () {
            warm(url);
        };

        anchor.addEventListener('pointerenter', warmNow, { passive: true });
        anchor.addEventListener('focus', warmNow, { passive: true });
        anchor.addEventListener('touchstart', warmNow, { passive: true });
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
                warm(url);
            },
            { passive: true }
        );
    }

    function installSpeculationRules(anchors) {
        if (!HTMLScriptElement.supports || !HTMLScriptElement.supports('speculationrules')) {
            return;
        }
        if (document.getElementById('prismSidebarSpeculation')) {
            return;
        }

        var urls = [];
        for (var i = 0; i < anchors.length && urls.length < IDLE_WARM_LIMIT; i++) {
            var href = absoluteUrl(anchors[i].href);
            if (href && urls.indexOf(href) === -1) {
                urls.push(href);
            }
        }
        if (!urls.length) {
            return;
        }

        var script = document.createElement('script');
        script.type = 'speculationrules';
        script.id = 'prismSidebarSpeculation';
        script.textContent = JSON.stringify({
            prefetch: [
                {
                    source: 'list',
                    urls: urls,
                    eagerness: 'moderate',
                },
            ],
        });
        document.head.appendChild(script);
    }

    function boot() {
        var anchors = collectAnchors();
        anchors.forEach(bindAnchor);
        installSpeculationRules(anchors);

        var idle = window.requestIdleCallback || function (cb) {
            return setTimeout(cb, 700);
        };
        idle(function () {
            collectAnchors().slice(0, IDLE_WARM_LIMIT).forEach(function (anchor) {
                warm(anchor.href);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.PrismSidebarNavFast = {
        warm: warm,
        refresh: boot,
    };
})();
