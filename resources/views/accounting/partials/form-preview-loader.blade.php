@once
    window.saFormPreview = window.saFormPreview || (function () {
        const cache = new Map();
        const inflight = new Map();

        function bar(width, height, marginTop) {
            return '<div class="prism-skel" style="width:' + width + ';height:' + height + 'px;margin:' + (marginTop || 0) + 'px auto 0;background:#e5e7eb;border-radius:6px"></div>';
        }

        const skeleton = ''
            + '<div role="status" aria-live="polite" aria-busy="true" style="min-height:62vh;background:#f1f5f9;padding:24px;display:flex;justify-content:center">'
            + '<span class="sr-only">Loading form…</span>'
            + '<div style="width:100%;max-width:760px;background:#fff;padding:36px 40px 28px;box-shadow:0 1px 3px rgba(15,23,42,.08)">'
            + bar('42%', 18)
            + bar('28%', 14, 10)
            + bar('34%', 12, 28)
            + bar('100%', 16, 22)
            + bar('100%', 16, 14)
            + bar('100%', 16, 14)
            + bar('72%', 16, 14)
            + '<div style="display:flex;gap:48px;margin-top:36px">'
            + '<div style="flex:1">' + bar('70%', 12, 0) + bar('100%', 36, 16) + '</div>'
            + '<div style="flex:1">' + bar('70%', 12, 0) + bar('100%', 36, 16) + '</div>'
            + '</div>'
            + '</div></div>';

        function stored(url) {
            const nodes = document.querySelectorAll('template[data-preview-store]');
            for (let i = 0; i < nodes.length; i++) {
                if (nodes[i].getAttribute('data-url') === url) {
                    return nodes[i].innerHTML;
                }
            }
            return '';
        }

        function prime(map) {
            if (!map) return;
            Object.keys(map).forEach(function (url) {
                if (map[url]) cache.set(url, map[url]);
            });
        }

        function load(url) {
            if (!url) return Promise.reject(new Error('Missing preview'));
            if (cache.has(url)) return Promise.resolve(cache.get(url));
            const inline = stored(url);
            if (inline) {
                cache.set(url, inline);
                return Promise.resolve(inline);
            }
            if (inflight.has(url)) return inflight.get(url);
            const job = fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                credentials: 'same-origin',
            }).then(function (response) {
                if (!response.ok) throw new Error('Preview failed');
                return response.text();
            }).then(function (html) {
                cache.set(url, html);
                inflight.delete(url);
                return html;
            }).catch(function (error) {
                inflight.delete(url);
                throw error;
            });
            inflight.set(url, job);
            return job;
        }

        document.addEventListener('mouseover', function (event) {
            const trigger = event.target.closest('[data-preview-url]');
            if (!trigger) return;
            const url = trigger.getAttribute('data-preview-url') || '';
            if (url) load(url).catch(function () {});
        });

        return { skeleton: skeleton, load: load, prime: prime };
    })();
@endonce
