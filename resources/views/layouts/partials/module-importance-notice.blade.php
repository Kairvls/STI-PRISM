@php
    $moduleNotice = \App\Support\ModuleImportanceNotice::forCurrentRequest();
    $muteOptions = \App\Support\ModuleImportanceNotice::MUTE_OPTIONS;
    $moduleLabels = \App\Support\ModuleImportanceNotice::moduleLabels();
    $muteUserId = (int) (Auth::id() ?? 0);
@endphp

@if (!empty($moduleNotice))
    @php
        $toneRing = [
            'amber' => 'bg-amber-50 text-amber-700 ring-amber-100 group-hover:bg-amber-100',
            'rose' => 'bg-rose-50 text-rose-600 ring-rose-100 group-hover:bg-rose-100',
            'blue' => 'bg-blue-50 text-blue-700 ring-blue-100 group-hover:bg-blue-100',
            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100 group-hover:bg-emerald-100',
            'violet' => 'bg-violet-50 text-violet-700 ring-violet-100 group-hover:bg-violet-100',
        ];
        $toneHover = [
            'amber' => 'hover:border-amber-200 hover:bg-amber-50/50',
            'rose' => 'hover:border-rose-200 hover:bg-rose-50/50',
            'blue' => 'hover:border-blue-200 hover:bg-blue-50/50',
            'emerald' => 'hover:border-emerald-200 hover:bg-emerald-50/50',
            'violet' => 'hover:border-violet-200 hover:bg-violet-50/50',
        ];
        $toneChevron = [
            'amber' => 'group-hover:text-amber-400',
            'rose' => 'group-hover:text-rose-400',
            'blue' => 'group-hover:text-blue-400',
            'emerald' => 'group-hover:text-emerald-400',
            'violet' => 'group-hover:text-violet-400',
        ];
    @endphp
    <div
        id="moduleImportanceModal"
        class="fixed inset-0 z-[72] hidden items-center justify-center bg-[#0b1220]/70 p-4 backdrop-blur-[2px]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="moduleImportanceTitle"
        data-module-key="{{ $moduleNotice['key'] }}"
        data-module-label="{{ $moduleNotice['label'] }}"
        data-attention-total="{{ $moduleNotice['total'] }}"
    >
        <div
            class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.22)]"
            onclick="event.stopPropagation()"
        >
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                        {{ $moduleNotice['eyebrow'] }}
                    </p>
                    <h2
                        id="moduleImportanceTitle"
                        class="mt-1 text-xl font-semibold tracking-tight text-slate-950"
                    >
                        {{ $moduleNotice['title'] }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $moduleNotice['subtitle'] }}
                    </p>
                </div>
                <button
                    type="button"
                    id="moduleImportanceClose"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-6 py-5">
                @foreach ($moduleNotice['items'] as $item)
                    @php
                        $tone = $item['tone'] ?? 'amber';
                    @endphp
                    <a
                        href="{{ $item['url'] }}"
                        class="module-importance-item-link group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition {{ $toneHover[$tone] ?? $toneHover['amber'] }}"
                    >
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 transition {{ $toneRing[$tone] ?? $toneRing['amber'] }}">
                            <i data-lucide="{{ $item['icon'] ?? 'circle-alert' }}" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $item['title'] }}
                            </p>
                            <p class="mt-0.5 text-xs leading-5 text-slate-500">
                                {{ $item['description'] }}
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition {{ $toneChevron[$tone] ?? $toneChevron['amber'] }}"></i>
                    </a>
                @endforeach
            </div>

            <div class="flex shrink-0 flex-col gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Mute popup</span>
                    @foreach ($muteOptions as $opt)
                        <button
                            type="button"
                            class="module-importance-mute-btn rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                            data-mute-minutes="{{ $opt['minutes'] }}"
                            data-mute-label="{{ $opt['label'] }}"
                        >
                            {{ $opt['label'] }}
                        </button>
                    @endforeach
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p id="moduleImportanceMuteHint" class="text-xs text-slate-500"></p>
                    <button
                        type="button"
                        id="moduleImportanceDismiss"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                    >
                        Close for now
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

<script>
(function () {
    const USER_ID = @json($muteUserId);
    const MODULE_LABELS = @json($moduleLabels);
    const MUTE_PREFIX = 'paayo:modImpMute:v1:' + USER_ID + ':';
    const SEEN_PREFIX = 'paayo:modImpSeen:v1:' + USER_ID + ':';
    const CURRENT_KEY = @json($moduleNotice['key'] ?? null);
    const CURRENT_LABEL = @json($moduleNotice['label'] ?? null);

    function muteStorageKey(moduleKey) {
        return MUTE_PREFIX + moduleKey;
    }

    function seenStorageKey(moduleKey) {
        return SEEN_PREFIX + moduleKey;
    }

    function markSessionSeen(moduleKey) {
        if (!moduleKey) return;
        try {
            sessionStorage.setItem(seenStorageKey(moduleKey), '1');
        } catch (e) {}
    }

    function clearSessionSeen(moduleKey) {
        if (!moduleKey) return;
        try {
            sessionStorage.removeItem(seenStorageKey(moduleKey));
        } catch (e) {}
    }

    function isSessionSeen(moduleKey) {
        if (!moduleKey) return false;
        try {
            return sessionStorage.getItem(seenStorageKey(moduleKey)) === '1';
        } catch (e) {
            return false;
        }
    }

    function readMute(moduleKey) {
        try {
            const raw = localStorage.getItem(muteStorageKey(moduleKey));
            if (!raw) return null;
            const data = JSON.parse(raw);
            if (!data || !data.until) return null;
            if (Date.now() >= Number(data.until)) {
                localStorage.removeItem(muteStorageKey(moduleKey));
                return null;
            }
            return data;
        } catch (e) {
            return null;
        }
    }

    function writeMute(moduleKey, minutes, label) {
        const until = Date.now() + (Number(minutes) * 60 * 1000);
        const payload = {
            until: until,
            label: label || '',
            moduleLabel: MODULE_LABELS[moduleKey] || moduleKey,
            mutedAt: Date.now(),
        };
        try {
            localStorage.setItem(muteStorageKey(moduleKey), JSON.stringify(payload));
        } catch (e) {}
        return payload;
    }

    function clearMute(moduleKey) {
        try {
            localStorage.removeItem(muteStorageKey(moduleKey));
        } catch (e) {}
    }

    function listActiveMutes() {
        const out = [];
        try {
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (!key || key.indexOf(MUTE_PREFIX) !== 0) continue;
                const moduleKey = key.slice(MUTE_PREFIX.length);
                const data = readMute(moduleKey);
                if (!data) continue;
                out.push({
                    moduleKey: moduleKey,
                    moduleLabel: data.moduleLabel || MODULE_LABELS[moduleKey] || moduleKey,
                    until: Number(data.until),
                    label: data.label || '',
                });
            }
        } catch (e) {}
        out.sort(function (a, b) { return a.until - b.until; });
        return out;
    }

    function formatRemaining(until) {
        const ms = Math.max(0, Number(until) - Date.now());
        const mins = Math.ceil(ms / 60000);
        if (mins <= 1) return 'about 1 minute';
        if (mins < 60) return mins + ' minutes';
        const hours = Math.floor(mins / 60);
        const rem = mins % 60;
        if (hours >= 24 && rem === 0) return '24 hours';
        if (rem === 0) return hours + (hours === 1 ? ' hour' : ' hours');
        return hours + 'h ' + rem + 'm';
    }

    function formatUntilClock(until) {
        try {
            return new Date(Number(until)).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        } catch (e) {
            return '';
        }
    }

    function toastMute(label) {
        const message = 'Popup muted for ' + (label || 'a while') + '. You can unmute anytime from Notifications.';
        if (typeof window.showMpToast === 'function') {
            window.showMpToast(message, { title: 'Popup muted', type: 'success' });
            return;
        }
        if (typeof window.showToast === 'function') {
            window.showToast(message, { title: 'Popup muted', type: 'success' });
        }
    }

    function renderTopbarMutePanel() {
        const panels = document.querySelectorAll('[data-module-importance-mutes]');
        if (!panels.length) return;

        const mutes = listActiveMutes();
        panels.forEach(function (panel) {
            panel.innerHTML = '';
            if (!mutes.length) {
                panel.classList.add('hidden');
                return;
            }
            panel.classList.remove('hidden');

            const header = document.createElement('div');
            header.className = 'border-b border-slate-100 bg-amber-50/70 px-4 py-2.5';
            header.innerHTML = '<p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Muted module popups</p>';
            panel.appendChild(header);

            mutes.forEach(function (m) {
                const row = document.createElement('div');
                row.className = 'flex items-start gap-2.5 border-b border-slate-100 px-4 py-2.5';
                row.innerHTML =
                    '<div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">' +
                        '<i data-lucide="bell-off" class="h-4 w-4"></i>' +
                    '</div>' +
                    '<div class="min-w-0 flex-1">' +
                        '<p class="text-[12px] font-semibold text-slate-900">Muted: ' + escapeHtml(m.moduleLabel) + '</p>' +
                        '<p class="mt-0.5 text-[11px] leading-4 text-slate-500">' +
                            'Popup muted for ' + escapeHtml(formatRemaining(m.until)) +
                            (formatUntilClock(m.until) ? ' (until ' + escapeHtml(formatUntilClock(m.until)) + ')' : '') +
                        '</p>' +
                    '</div>' +
                    '<button type="button" class="shrink-0 rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-[#0025cc] transition hover:bg-slate-50" data-unmute="' + escapeAttr(m.moduleKey) + '">Unmute</button>';
                panel.appendChild(row);
            });

            panel.querySelectorAll('[data-unmute]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const key = btn.getAttribute('data-unmute');
                    clearMute(key);
                    clearSessionSeen(key);
                    renderTopbarMutePanel();
                    if (CURRENT_KEY && key === CURRENT_KEY) {
                        showModal(true);
                    }
                });
            });
        });

        if (window.lucide && typeof lucide.createIcons === 'function') {
            lucide.createIcons();
        }
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeAttr(str) {
        return escapeHtml(str).replace(/'/g, '&#39;');
    }

    window.PaayoModuleImportance = {
        listActiveMutes: listActiveMutes,
        clearMute: clearMute,
        renderTopbarMutePanel: renderTopbarMutePanel,
        formatRemaining: formatRemaining,
        markSessionSeen: markSessionSeen,
        currentKey: CURRENT_KEY,
        open: function () {
            showModal(true);
        },
    };

    const modal = document.getElementById('moduleImportanceModal');
    if (!modal || !CURRENT_KEY) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderTopbarMutePanel);
        } else {
            renderTopbarMutePanel();
        }
        return;
    }

    function isVisible() {
        return modal.classList.contains('flex') && !modal.classList.contains('hidden');
    }

    function showModal(force) {
        const mute = readMute(CURRENT_KEY);
        if (mute) return;
        if (!force && isSessionSeen(CURRENT_KEY)) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide && typeof lucide.createIcons === 'function') {
            lucide.createIcons();
        }
    }

    function hideModalAndMarkSeen() {
        markSessionSeen(CURRENT_KEY);
        hideModal();
    }

    function hideModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function maybeAutoShow() {
        if (isVisible()) return;
        if (readMute(CURRENT_KEY)) {
            renderTopbarMutePanel();
            return;
        }
        if (isSessionSeen(CURRENT_KEY)) {
            renderTopbarMutePanel();
            return;
        }
        showModal(false);
        renderTopbarMutePanel();
    }

    window.openModuleImportanceNotice = function () {
        showModal(true);
        try {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) dropdown.classList.add('hidden');
        } catch (e) {}
    };

    var closeBtn = document.getElementById('moduleImportanceClose');
    var dismissBtn = document.getElementById('moduleImportanceDismiss');
    if (closeBtn) closeBtn.addEventListener('click', hideModalAndMarkSeen);
    if (dismissBtn) dismissBtn.addEventListener('click', hideModalAndMarkSeen);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            hideModalAndMarkSeen();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && isVisible()) {
            hideModalAndMarkSeen();
        }
    });

    // Mark this module as seen before navigating so the destination page does not re-open the popup.
    modal.querySelectorAll('.module-importance-item-link').forEach(function (link) {
        link.addEventListener('click', function () {
            markSessionSeen(CURRENT_KEY);
        });
    });

    document.querySelectorAll('.module-importance-mute-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const minutes = btn.getAttribute('data-mute-minutes');
            const label = btn.getAttribute('data-mute-label') || '';
            writeMute(CURRENT_KEY, minutes, label);
            markSessionSeen(CURRENT_KEY);
            hideModal();
            const hint = document.getElementById('moduleImportanceMuteHint');
            if (hint) {
                hint.textContent = 'Muted ' + (CURRENT_LABEL || 'this module') + ' for ' + label + '.';
            }
            renderTopbarMutePanel();
            toastMute(label);
        });
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', maybeAutoShow);
    } else {
        maybeAutoShow();
    }

    setInterval(renderTopbarMutePanel, 60 * 1000);
})();
</script>
