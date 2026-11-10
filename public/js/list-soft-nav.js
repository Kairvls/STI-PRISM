/**
 * Soft AJAX navigation for GET filter forms (data-auto-filter + data-list-panel).
 * - Prefetch / short HTML cache (chat-style warm load)
 * - Lightweight overlay instead of full page reload
 * - Swap table+pagination panel as soon as HTML arrives
 */
(function () {
    if (window.PrismListSoftNav) return;

    var CACHE_TTL_MS = 90000;
    var htmlCache = new Map();
    var prefetchMap = new Map();

    function now() {
        return Date.now();
    }

    function cacheGet(url) {
        var hit = htmlCache.get(url);
        if (!hit) return null;
        if (now() - hit.at > CACHE_TTL_MS) {
            htmlCache.delete(url);
            return null;
        }
        return hit.html;
    }

    function cacheSet(url, html) {
        if (!url || !html) return;
        htmlCache.set(url, { at: now(), html: html });
    }

    function absoluteUrl(href) {
        try {
            return new URL(href, window.location.origin).href;
        } catch (e) {
            return href;
        }
    }

    function formUrl(form) {
        var action = form.getAttribute('action') || window.location.pathname;
        var params = new URLSearchParams(new FormData(form));
        params.delete('page');
        var query = params.toString();
        var url = new URL(action, window.location.origin);
        url.search = query;
        return url.href;
    }

    function findPanel(form) {
        var sel = form.getAttribute('data-list-panel');
        if (sel) {
            return document.querySelector(sel);
        }
        return null;
    }

    function ensureOverlay(panel) {
        var overlay = panel.querySelector(':scope > .prism-list-soft-overlay');
        if (overlay) return overlay;

        overlay = document.createElement('div');
        overlay.className = 'prism-list-soft-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        overlay.innerHTML =
            '<div class="prism-list-soft-overlay__skel" role="status" aria-label="Loading records">' +
            (typeof window.prismSkeleton === 'function'
                ? window.prismSkeleton('table', { rows: 5, cols: 5, label: 'Loading records' })
                : '<div class="p-6 text-sm text-gray-500">Loading…</div>') +
            '</div>';

        if (getComputedStyle(panel).position === 'static') {
            panel.style.position = 'relative';
        }
        panel.appendChild(overlay);
        return overlay;
    }

    function showOverlay(panel, soft) {
        var overlay = ensureOverlay(panel);
        overlay.classList.toggle('is-soft', !!soft);
        overlay.classList.add('is-visible');
    }

    function hideOverlay(panel) {
        var overlay = panel.querySelector(':scope > .prism-list-soft-overlay');
        if (overlay) {
            overlay.classList.remove('is-visible');
        }
    }

    function fetchHtml(url, signal) {
        if (prefetchMap.has(url)) {
            return prefetchMap.get(url);
        }

        var request = fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
            },
            signal: signal,
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('List refresh failed');
                }
                return response.text();
            })
            .then(function (html) {
                cacheSet(url, html);
                return html;
            })
            .finally(function () {
                setTimeout(function () {
                    if (prefetchMap.get(url) === request) {
                        prefetchMap.delete(url);
                    }
                }, 1200);
            });

        prefetchMap.set(url, request);
        return request;
    }

    function prefetch(url) {
        url = absoluteUrl(url);
        if (!url || cacheGet(url) || prefetchMap.has(url)) return;
        fetchHtml(url).catch(function () {});
    }

    function swapPanel(panel, html, panelSelector) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var next =
            (panelSelector && doc.querySelector(panelSelector)) ||
            (panel.id && doc.getElementById(panel.id)) ||
            doc.querySelector('[data-list-panel-root]');

        if (!next) {
            throw new Error('List panel missing in response');
        }

        if (window.Alpine && typeof Alpine.destroyTree === 'function') {
            try {
                Alpine.destroyTree(panel);
            } catch (e) {}
        }

        panel.innerHTML = next.innerHTML;

        if (window.Alpine && typeof Alpine.initTree === 'function') {
            try {
                Alpine.initTree(panel);
            } catch (e) {}
        }

        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }

        if (typeof window.bindPageCarousels === 'function') {
            window.bindPageCarousels();
        }

        if (window.PrismAutoFilter && typeof window.PrismAutoFilter.scan === 'function') {
            window.PrismAutoFilter.scan(panel.closest('.pur-card') || document);
        }

        bindPagination(panel);
        bindPanelLinks(panel);
        if (panel.id) {
            bindPanelLinks(document);
        }
    }

    function resolvePanel(selectorOrEl) {
        if (!selectorOrEl) return null;
        if (typeof selectorOrEl === 'string') {
            return document.querySelector(selectorOrEl);
        }
        return selectorOrEl;
    }

    function bindPanelLinks(root) {
        (root || document).querySelectorAll('a[data-list-panel]').forEach(function (link) {
            if (link.dataset.softNavBound === '1') return;
            var href = link.getAttribute('href') || '';
            if (!href || href === '#' || href.indexOf('javascript:') === 0) return;
            link.dataset.softNavBound = '1';
            link.addEventListener('pointerenter', function () {
                prefetch(link.href);
            });
            link.addEventListener('click', function (event) {
                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }
                var panelSel = link.getAttribute('data-list-panel');
                var panel = resolvePanel(panelSel);
                if (!panel) return;
                event.preventDefault();
                softNavigate(absoluteUrl(link.href), panel, panelSel, null);
            });
        });
    }

    function bindPagination(root) {
        root.querySelectorAll('a[href]').forEach(function (link) {
            if (link.dataset.softNavBound === '1') return;
            if (link.hasAttribute('data-list-panel')) return;
            var href = link.getAttribute('href') || '';
            if (!href || href === '#' || href.indexOf('javascript:') === 0) return;
            // Laravel pagination links typically include ?page=
            if (href.indexOf('page=') === -1 && !link.closest('nav[role="navigation"]') && !link.closest('.pagination')) {
                return;
            }
            link.dataset.softNavBound = '1';
            link.addEventListener('pointerenter', function () {
                prefetch(link.href);
            });
            link.addEventListener('click', function (event) {
                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }
                var panel = root.closest('[data-list-panel-root]') || root;
                var panelSel = panel.id ? '#' + panel.id : null;
                if (!panelSel) {
                    var form = document.querySelector('form[data-auto-filter][data-list-panel]');
                    panelSel = form && form.getAttribute('data-list-panel');
                    panel = panelSel ? document.querySelector(panelSel) : panel;
                }
                if (!panel) return;
                event.preventDefault();
                softNavigate(absoluteUrl(link.href), panel, panelSel, null);
            });
        });
    }

    var activeAbort = null;

    async function softNavigate(url, panel, panelSelector, form) {
        if (!panel) {
            window.location.href = url;
            return;
        }

        if (activeAbort) {
            try {
                activeAbort.abort();
            } catch (e) {}
        }
        activeAbort = typeof AbortController !== 'undefined' ? new AbortController() : null;

        var cached = cacheGet(url);
        showOverlay(panel, !!cached);

        try {
            var html = cached;
            if (!html) {
                html = await fetchHtml(url, activeAbort ? activeAbort.signal : undefined);
            } else {
                fetchHtml(url).catch(function () {});
            }

            swapPanel(panel, html, panelSelector);
            try {
                var next = new URL(url, window.location.origin);
                window.history.replaceState({}, '', next.pathname + next.search);
            } catch (e) {}
        } catch (error) {
            if (error && error.name === 'AbortError') {
                return;
            }
            console.error(error);
            window.location.href = url;
        } finally {
            hideOverlay(panel);
        }
    }

    function onSubmit(event) {
        var form = event.target;
        if (!form || form.tagName !== 'FORM') return;
        if (!form.hasAttribute('data-auto-filter')) return;
        if (!form.hasAttribute('data-list-panel')) return;
        if (form.hasAttribute('data-no-soft-nav')) return;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'get') return;

        var panelSelector = form.getAttribute('data-list-panel');
        var panel = findPanel(form);
        if (!panel) return;

        event.preventDefault();
        event.stopPropagation();
        softNavigate(formUrl(form), panel, panelSelector, form);
    }

    function boot() {
        document.addEventListener('submit', onSubmit, true);

        document.querySelectorAll('form[data-auto-filter][data-list-panel]').forEach(function (form) {
            var panel = findPanel(form);
            if (panel) {
                bindPagination(panel);
            }
        });
        bindPanelLinks(document);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.PrismListSoftNav = {
        prefetch: prefetch,
        softNavigate: softNavigate,
        cacheGet: cacheGet,
        cacheSet: cacheSet,
    };

    // Shared cache helper for Alpine list refreshers (RIS / RFC).
    window.PrismListCache = {
        get: cacheGet,
        set: cacheSet,
        prefetch: prefetch,
        ttl: CACHE_TTL_MS,
    };
})();
