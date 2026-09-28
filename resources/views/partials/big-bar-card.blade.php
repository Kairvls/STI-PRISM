{{--
    Full-width line chart card used on the maintenance dashboard.
    Params: $title, $unit, $menuId, $stages = [['label', 'value', 'href']], $menuLinks = [['label', 'href']],
    optional $cardId, $panel (Overview tab that shows this card) and $hidden.
    The tooltip shows each point's share of the first stage.
    JS can redraw it with element.renderBars(stages).
--}}
@php
    $bbFormatCompact = function (int $n): string {
        if ($n >= 1000000) {
            return rtrim(rtrim(number_format($n / 1000000, 1, '.', ''), '0'), '.') . 'M';
        }
        if ($n >= 1000) {
            return rtrim(rtrim(number_format($n / 1000, 1, '.', ''), '0'), '.') . 'K';
        }
        return number_format($n);
    };

    $bbStages = array_values(array_map(fn ($stage) => [
        'label' => $stage['label'],
        'href' => $stage['href'],
        'value' => (int) $stage['value'],
        'display' => $bbFormatCompact((int) $stage['value']),
    ], $stages));
@endphp

@once
<style>
    .big-bar-card {
        background: #ffffff;
        border: 1px solid #e8eaed;
        border-radius: 22px;
        padding: 14px 18px 10px;
        height: 250px;
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
        position: relative;
    }

    .big-bar-card.is-hidden {
        display: none;
    }

    .big-bar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-shrink: 0;
    }

    .big-bar-title {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #111827;
        letter-spacing: -0.01em;
    }

    .big-bar-more {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        color: #9ca3af;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        padding: 0;
    }

    .big-bar-more:hover {
        color: #111827;
    }

    /* Fixed so the menu is never clipped by the card */
    .big-bar-menu {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1200;
        min-width: 168px;
        padding: 6px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.18);
    }

    .big-bar-menu.hidden {
        display: none;
    }

    .big-bar-menu a {
        display: block;
        border-radius: 8px;
        padding: 8px 10px;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        text-decoration: none;
    }

    .big-bar-menu a:hover {
        background: #f3f4f6;
        color: #111827;
    }

    .big-line-plot {
        position: relative;
        flex: 1 1 auto;
        min-height: 0;
        margin-top: 8px;
    }

    .big-line-svg {
        position: absolute;
        inset: 0;
        display: block;
        overflow: visible;
    }

    .big-line-grid {
        stroke: #e5e7eb;
        stroke-width: 1;
        stroke-dasharray: 4 4;
    }

    .big-line-grid.is-base {
        stroke: #e2e8f0;
        stroke-dasharray: none;
    }

    .big-line-axis,
    .big-line-xlabel {
        font-family: inherit;
        font-size: 11px;
        font-weight: 500;
        fill: #9ca3af;
    }

    .big-line-xlabel.is-active {
        fill: #0025cc;
        font-weight: 700;
    }

    .big-line-value {
        font-family: inherit;
        font-size: 12px;
        font-weight: 700;
        fill: #111827;
        transition: opacity 0.15s ease;
    }

    .big-line-value.is-peak {
        fill: #0025cc;
    }

    .big-line-value.is-active {
        opacity: 0;
    }

    .big-line-path {
        fill: none;
        stroke: #0025cc;
        stroke-width: 2.25;
        stroke-linejoin: round;
        stroke-linecap: round;
    }

    .big-line-guide {
        stroke: rgba(0, 37, 204, 0.35);
        stroke-width: 1;
        stroke-dasharray: 3 3;
        opacity: 0;
        transition: opacity 0.15s ease;
    }

    .big-line-guide.is-visible {
        opacity: 1;
    }

    .big-line-dot {
        fill: #ffffff;
        stroke: #0025cc;
        stroke-width: 2;
        transform-box: fill-box;
        transform-origin: center;
        transition: transform 0.15s ease, fill 0.15s ease;
    }

    .big-line-dot.is-peak {
        fill: #0025cc;
    }

    .big-line-dot.is-active {
        fill: #001a9e;
        stroke: #ffffff;
        transform: scale(1.6);
        filter: drop-shadow(0 2px 6px rgba(0, 37, 204, 0.45));
    }

    .big-line-hits {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        display: grid;
    }

    .big-line-hit {
        appearance: none;
        border: none;
        background: transparent;
        padding: 0;
        margin: 0;
        cursor: pointer;
        outline: none;
    }

    .big-line-hit:focus-visible {
        border-radius: 10px;
        background: rgba(0, 37, 204, 0.05);
    }

    .big-bar-tip {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 20;
        min-width: 150px;
        padding: 9px 12px 10px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.14);
        pointer-events: none;
        opacity: 0;
        transform: translate(-50%, calc(-100% - 6px));
        transition: opacity 0.15s ease, transform 0.15s ease;
    }

    .big-bar-tip.is-visible {
        opacity: 1;
        transform: translate(-50%, calc(-100% - 10px));
    }

    .big-bar-tip::after {
        content: "";
        position: absolute;
        left: 50%;
        bottom: -6px;
        width: 10px;
        height: 10px;
        background: #ffffff;
        border-right: 1px solid rgba(226, 232, 240, 0.9);
        border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        transform: translateX(-50%) rotate(45deg);
    }

    .big-bar-tip-label {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        white-space: nowrap;
    }

    .big-bar-tip-value {
        margin-top: 2px;
        font-size: 18px;
        font-weight: 700;
        color: #0025cc;
        line-height: 1.1;
        white-space: nowrap;
    }

    .big-bar-tip-value small {
        margin-left: 3px;
        font-size: 11px;
        font-weight: 500;
        color: #64748b;
    }

    .big-bar-tip-meta {
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px solid #f1f5f9;
        font-size: 10.5px;
        font-weight: 500;
        color: #94a3b8;
        white-space: nowrap;
    }
</style>
@endonce

<div
    class="big-bar-card {{ ($hidden ?? false) ? 'is-hidden' : '' }}"
    data-big-bar-card
    data-unit="{{ $unit }}"
    data-stages="{{ json_encode($bbStages) }}"
    @if (! empty($cardId)) id="{{ $cardId }}" @endif
    @if (! empty($panel)) data-eq-metrics-panel="{{ $panel }}" @endif
>
    <div class="big-bar-header">
        <h3 class="big-bar-title">{{ $title }}</h3>
        <button type="button" class="big-bar-more" data-big-bar-menu-btn aria-label="{{ $title }} options" aria-expanded="false" aria-controls="{{ $menuId }}">
            <i data-lucide="ellipsis" class="h-4 w-4"></i>
        </button>
        <div id="{{ $menuId }}" class="big-bar-menu hidden" role="menu">
            @foreach ($menuLinks as $link)
                <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
            @endforeach
        </div>
    </div>

    <div class="big-line-plot" data-big-line-plot>
        <svg class="big-line-svg" aria-hidden="true"></svg>
        <div class="big-line-hits" data-big-line-hits></div>
    </div>

    <div class="big-bar-tip" aria-hidden="true"></div>
</div>

<script>
    (function () {
        const card = document.currentScript.previousElementSibling;
        if (!card || !card.matches('[data-big-bar-card]')) return;

        const SVG_NS = 'http://www.w3.org/2000/svg';
        const plot = card.querySelector('[data-big-line-plot]');
        const svg = plot?.querySelector('svg');
        const hits = card.querySelector('[data-big-line-hits]');
        const tip = card.querySelector('.big-bar-tip');
        const unit = card.dataset.unit || 'items';
        const menuBtn = card.querySelector('[data-big-bar-menu-btn]');
        const menu = document.getElementById(menuBtn?.getAttribute('aria-controls') || '');
        const gradientId = `bigLineFill${Math.random().toString(36).slice(2, 9)}`;
        let stages = [];
        let points = [];
        let activeIndex = -1;

        const compact = (n) => {
            if (n >= 1e6) return `${Number((n / 1e6).toFixed(1))}M`;
            if (n >= 1e3) return `${Number((n / 1e3).toFixed(1))}K`;
            return String(n);
        };
        const niceStep = (raw) => {
            const exp = Math.pow(10, Math.floor(Math.log10(raw)));
            const f = raw / exp;
            return (f <= 1 ? 1 : f <= 2 ? 2 : f <= 2.5 ? 2.5 : f <= 5 ? 5 : 10) * exp;
        };
        const svgEl = (name, attrs = {}) => {
            const node = document.createElementNS(SVG_NS, name);
            Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, value));
            return node;
        };
        const normalize = (list) => (Array.isArray(list) ? list : []).map((stage) => {
            const value = Number(stage.value) || 0;
            return {
                label: String(stage.label ?? ''),
                href: stage.href || '',
                value,
                display: stage.display ?? compact(value),
            };
        });

        const clearActive = () => {
            activeIndex = -1;
            svg?.querySelectorAll('.is-active').forEach((node) => node.classList.remove('is-active'));
            svg?.querySelector('.big-line-guide')?.classList.remove('is-visible');
            tip?.classList.remove('is-visible');
        };

        const setActive = (index) => {
            const point = points[index];
            const stage = stages[index];
            if (!svg || !point || !stage) return clearActive();
            activeIndex = index;

            svg.querySelectorAll('[data-index]').forEach((node) => {
                node.classList.toggle('is-active', Number(node.dataset.index) === index);
            });
            const guide = svg.querySelector('.big-line-guide');
            guide?.setAttribute('x1', point.x);
            guide?.setAttribute('x2', point.x);
            guide?.classList.add('is-visible');

            if (!tip) return;
            const base = stages[0]?.value || 0;
            const meta = index === 0 || base <= 0
                ? 'Click to view the list'
                : `${Math.round((stage.value / base) * 100)}% of ${stages[0].label} · Click to view`;

            const label = document.createElement('div');
            label.className = 'big-bar-tip-label';
            label.textContent = stage.label;
            const value = document.createElement('div');
            value.className = 'big-bar-tip-value';
            value.textContent = stage.display;
            const unitEl = document.createElement('small');
            unitEl.textContent = unit;
            value.append(unitEl);
            const metaEl = document.createElement('div');
            metaEl.className = 'big-bar-tip-meta';
            metaEl.textContent = meta;
            tip.replaceChildren(label, value, metaEl);

            tip.style.left = `${plot.offsetLeft + point.x}px`;
            tip.style.top = `${plot.offsetTop + point.y - 8}px`;
            tip.classList.add('is-visible');
        };

        const draw = () => {
            if (!plot || !svg) return;
            const width = plot.clientWidth;
            const height = plot.clientHeight;
            if (!width || !height) return;

            const values = stages.map((stage) => stage.value);
            const maxValue = Math.max(0, ...values);
            const step = maxValue > 0 ? Math.max(1, Math.ceil(niceStep(maxValue / 4))) : 1;
            const top = maxValue > 0 ? Math.ceil(maxValue / step) * step : 4;
            const ticks = [];
            for (let tick = 0; tick <= top; tick += step) ticks.push(tick);

            const axisW = Math.max(...ticks.map((tick) => compact(tick).length)) * 7 + 14;
            const padTop = 20;
            const padBottom = 24;
            const plotW = Math.max(1, width - axisW);
            const plotH = Math.max(1, height - padTop - padBottom);
            const colW = plotW / Math.max(1, stages.length);
            const y = (value) => padTop + plotH - (value / top) * plotH;
            const baseY = y(0);
            points = values.map((value, i) => ({ x: axisW + colW * (i + 0.5), y: y(value) }));

            svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
            svg.setAttribute('width', width);
            svg.setAttribute('height', height);
            svg.replaceChildren();

            const defs = svgEl('defs');
            const gradient = svgEl('linearGradient', { id: gradientId, x1: 0, y1: 0, x2: 0, y2: 1 });
            gradient.append(
                svgEl('stop', { offset: '0%', 'stop-color': '#0025cc', 'stop-opacity': '0.22' }),
                svgEl('stop', { offset: '100%', 'stop-color': '#0025cc', 'stop-opacity': '0.02' }),
            );
            defs.append(gradient);
            svg.append(defs);

            ticks.forEach((tick) => {
                const tickY = y(tick);
                svg.append(svgEl('line', {
                    x1: axisW, x2: width, y1: tickY, y2: tickY,
                    class: tick === 0 ? 'big-line-grid is-base' : 'big-line-grid',
                }));
                const axisLabel = svgEl('text', {
                    x: axisW - 12, y: tickY, class: 'big-line-axis', 'text-anchor': 'end', 'dominant-baseline': 'middle',
                });
                axisLabel.textContent = compact(tick);
                svg.append(axisLabel);
            });

            if (points.length) {
                const line = points.map((p, i) => `${i ? 'L' : 'M'}${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ');
                const first = points[0];
                const last = points[points.length - 1];
                svg.append(svgEl('path', {
                    d: `${line} L${last.x.toFixed(1)},${baseY.toFixed(1)} L${first.x.toFixed(1)},${baseY.toFixed(1)} Z`,
                    fill: `url(#${gradientId})`,
                }));
                svg.append(svgEl('line', { class: 'big-line-guide', x1: 0, x2: 0, y1: padTop - 8, y2: baseY }));
                svg.append(svgEl('path', { d: line, class: 'big-line-path' }));
            }

            points.forEach((point, i) => {
                const isPeak = maxValue > 0 && values[i] === maxValue;
                svg.append(svgEl('circle', {
                    cx: point.x, cy: point.y, r: 4,
                    class: `big-line-dot${isPeak ? ' is-peak' : ''}`,
                    'data-index': i,
                }));

                const valueLabel = svgEl('text', {
                    x: point.x, y: point.y - 11, class: `big-line-value${isPeak ? ' is-peak' : ''}`,
                    'text-anchor': 'middle', 'data-index': i,
                });
                valueLabel.textContent = stages[i].display;
                svg.append(valueLabel);

                const xLabel = svgEl('text', {
                    x: point.x, y: height - 6, class: 'big-line-xlabel', 'text-anchor': 'middle', 'data-index': i,
                });
                xLabel.textContent = stages[i].label;
                svg.append(xLabel);
            });

            if (hits) {
                hits.style.left = `${axisW}px`;
                hits.style.gridTemplateColumns = `repeat(${Math.max(1, stages.length)}, minmax(0, 1fr))`;
            }
            if (activeIndex >= 0) setActive(activeIndex);
        };

        const buildHits = () => {
            if (!hits) return;
            hits.replaceChildren(...stages.map((stage, i) => {
                const hit = document.createElement('button');
                hit.type = 'button';
                hit.className = 'big-line-hit';
                hit.dataset.index = String(i);
                hit.setAttribute('aria-label', `${stage.label}: ${stage.display}`);
                return hit;
            }));
        };

        card.renderBars = (list) => {
            clearActive();
            stages = normalize(list);
            buildHits();
            draw();
        };

        try {
            stages = normalize(JSON.parse(card.dataset.stages || '[]'));
        } catch (error) {
            stages = [];
        }
        buildHits();
        draw();

        if (window.ResizeObserver && plot) {
            new ResizeObserver(draw).observe(plot);
        } else {
            window.addEventListener('resize', draw);
        }

        hits?.addEventListener('mouseover', (event) => {
            const hit = event.target.closest('.big-line-hit');
            if (hit && Number(hit.dataset.index) !== activeIndex) setActive(Number(hit.dataset.index));
        });
        hits?.addEventListener('mouseleave', clearActive);
        hits?.addEventListener('focusin', (event) => {
            const hit = event.target.closest('.big-line-hit');
            if (hit) setActive(Number(hit.dataset.index));
        });
        hits?.addEventListener('focusout', clearActive);
        hits?.addEventListener('click', (event) => {
            const hit = event.target.closest('.big-line-hit');
            const href = hit ? stages[Number(hit.dataset.index)]?.href : '';
            if (href) window.location.href = href;
        });

        if (!menuBtn || !menu) return;

        const placeMenu = () => {
            const rect = menuBtn.getBoundingClientRect();
            const width = Math.max(168, menu.offsetWidth || 168);
            const height = menu.offsetHeight || 160;
            let top = rect.bottom + 6;
            if (top + height > window.innerHeight - 8) {
                top = Math.max(8, rect.top - height - 6);
            }
            menu.style.left = `${Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8))}px`;
            menu.style.top = `${top}px`;
        };
        const closeMenu = () => {
            menu.classList.add('hidden');
            menuBtn.setAttribute('aria-expanded', 'false');
        };

        menuBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = menu.classList.contains('hidden');
            document.querySelectorAll('.big-bar-menu').forEach((other) => {
                if (other !== menu) other.classList.add('hidden');
            });
            menu.classList.toggle('hidden', !willOpen);
            menuBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (willOpen) placeMenu();
        });
        document.addEventListener('click', (event) => {
            if (!menuBtn.contains(event.target) && !menu.contains(event.target)) closeMenu();
        });
        window.addEventListener('resize', () => { if (!menu.classList.contains('hidden')) placeMenu(); });
        window.addEventListener('scroll', () => { if (!menu.classList.contains('hidden')) placeMenu(); }, true);
    })();
</script>
