{{-- Share procurement document into Messages (multi-select) --}}
<div
    id="shareDocumentModal"
    class="fixed inset-0 z-[80] hidden items-center justify-center p-4"
    aria-hidden="true"
>
    <div
        id="shareDocumentBackdrop"
        class="absolute inset-0 bg-[#0b1220]/60"
    ></div>

    <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-slate-900">Share to Messages</h2>
                <p id="shareDocumentSummary" class="mt-1 truncate text-sm text-slate-500">Select people</p>
            </div>
            <button
                type="button"
                id="shareDocumentClose"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"
                aria-label="Close"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div class="px-5 py-4">
            <div class="mb-1.5 flex items-center justify-between gap-2">
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="shareDocumentSearch">
                    Find conversation or person
                </label>
                <span id="shareDocumentSelectedCount" class="text-xs font-medium text-slate-400">None selected</span>
            </div>
            <input
                id="shareDocumentSearch"
                type="search"
                autocomplete="off"
                placeholder="Search…"
                class="mb-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-300 focus:bg-white"
            >

            <div
                id="shareDocumentList"
                class="max-h-64 space-y-1 overflow-y-auto rounded-xl border border-slate-100 p-1"
            >
                <p class="px-3 py-6 text-center text-sm text-slate-400">Loading…</p>
            </div>

            <label class="mb-1.5 mt-4 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="shareDocumentNote">
                Note (optional)
            </label>
            <textarea
                id="shareDocumentNote"
                rows="2"
                maxlength="1000"
                placeholder="Add a short note…"
                class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-300 focus:bg-white"
            ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
            <button
                type="button"
                id="shareDocumentCancel"
                class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50"
            >
                Cancel
            </button>
            <button
                type="button"
                id="shareDocumentSend"
                disabled
                class="rounded-xl bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#001db3] disabled:cursor-not-allowed disabled:opacity-50"
            >
                Share
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const authUserId = {{ (int) auth()->id() }};
    window.currentUserId = window.currentUserId || authUserId;
    const modal = document.getElementById('shareDocumentModal');
    if (!modal) return;

    const summaryEl = document.getElementById('shareDocumentSummary');
    const listEl = document.getElementById('shareDocumentList');
    const searchEl = document.getElementById('shareDocumentSearch');
    const noteEl = document.getElementById('shareDocumentNote');
    const sendBtn = document.getElementById('shareDocumentSend');
    const closeBtn = document.getElementById('shareDocumentClose');
    const cancelBtn = document.getElementById('shareDocumentCancel');
    const backdrop = document.getElementById('shareDocumentBackdrop');
    const selectedCountEl = document.getElementById('shareDocumentSelectedCount');

    let pending = null;
    /** @type {Map<string, {kind: string, id: number, label?: string}>} */
    let selected = new Map();
    let searchTimer = null;

    function selectionKey(kind, id) {
        return String(kind) + ':' + String(id);
    }

    function lucideRefresh(root) {
        if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
            lucide.createIcons({ nodes: root ? [root] : undefined });
        } else if (typeof window.lucideCreateIcons === 'function') {
            window.lucideCreateIcons(root || document);
        }
    }

    function syncSelectedUi() {
        const count = selected.size;
        sendBtn.disabled = count < 1;
        if (selectedCountEl) {
            selectedCountEl.textContent = count < 1
                ? 'None selected'
                : (count === 1 ? '1 selected' : count + ' selected');
            selectedCountEl.classList.toggle('text-[#0025cc]', count > 0);
            selectedCountEl.classList.toggle('text-slate-400', count < 1);
        }
        sendBtn.textContent = count > 1 ? ('Share (' + count + ')') : 'Share';

        listEl.querySelectorAll('[data-share-item]').forEach((el) => {
            const key = selectionKey(el.dataset.kind, el.dataset.id);
            const active = selected.has(key);
            el.classList.toggle('bg-blue-50', active);
            el.classList.toggle('border-blue-200', active);
            el.classList.toggle('border-transparent', !active);
            el.setAttribute('aria-pressed', active ? 'true' : 'false');
            const check = el.querySelector('[data-share-check]');
            if (check) check.classList.toggle('hidden', !active);
        });
    }

    function closeShareModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        pending = null;
        selected = new Map();
        sendBtn.disabled = true;
        sendBtn.textContent = 'Share';
        noteEl.value = '';
        searchEl.value = '';
        if (selectedCountEl) {
            selectedCountEl.textContent = 'None selected';
            selectedCountEl.classList.add('text-slate-400');
            selectedCountEl.classList.remove('text-[#0025cc]');
        }
    }

    function toggleSelected(item) {
        const key = selectionKey(item.kind, item.id);
        if (selected.has(key)) {
            selected.delete(key);
        } else {
            selected.set(key, item);
        }
        syncSelectedUi();
    }

    function renderList(conversations, users) {
        const chunks = [];

        (conversations || []).forEach((conv) => {
            const id = conv.conversation_id;
            const participants = Array.isArray(conv.participants) ? conv.participants : [];
            const others = participants
                .map((p) => p.user || p)
                .filter((u) => Number(u.user_id) !== Number(window.currentUserId || 0));
            let name = conv.conversation_name;
            if (!name || !String(name).trim()) {
                name = others.map((u) => u.user_full_name || u.name || 'User').join(', ') || 'Conversation';
            }
            chunks.push(`
                <button type="button" data-share-item data-kind="conversation" data-id="${id}" data-label="${escapeAttr(name)}"
                    aria-pressed="false"
                    class="flex w-full items-center gap-3 rounded-xl border border-transparent px-3 py-2.5 text-left hover:bg-slate-50">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                        <i data-lucide="message-circle" class="h-4 w-4"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-slate-900">${escapeHtml(name)}</span>
                        <span class="block text-xs text-slate-500">Conversation</span>
                    </span>
                    <span data-share-check class="hidden inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#0025cc] text-white">
                        <i data-lucide="check" class="h-3 w-3"></i>
                    </span>
                </button>
            `);
        });

        (users || []).forEach((user) => {
            const id = user.user_id;
            const name = user.user_full_name || user.name || 'User';
            const role = (typeof user.role === 'string')
                ? user.role
                : (user.role?.role_name || user.role_name || '');
            chunks.push(`
                <button type="button" data-share-item data-kind="user" data-id="${id}" data-label="${escapeAttr(name)}"
                    aria-pressed="false"
                    class="flex w-full items-center gap-3 rounded-xl border border-transparent px-3 py-2.5 text-left hover:bg-slate-50">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-50 text-[#0025cc]">
                        <i data-lucide="user" class="h-4 w-4"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-slate-900">${escapeHtml(name)}</span>
                        <span class="block text-xs text-slate-500">${escapeHtml(role || 'Staff')}</span>
                    </span>
                    <span data-share-check class="hidden inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#0025cc] text-white">
                        <i data-lucide="check" class="h-3 w-3"></i>
                    </span>
                </button>
            `);
        });

        if (!chunks.length) {
            listEl.innerHTML = '<p class="px-3 py-6 text-center text-sm text-slate-400">No matches.</p>';
            syncSelectedUi();
            return;
        }

        listEl.innerHTML = chunks.join('');
        lucideRefresh(listEl);

        listEl.querySelectorAll('[data-share-item]').forEach((el) => {
            el.addEventListener('click', () => {
                toggleSelected({
                    kind: el.dataset.kind,
                    id: Number(el.dataset.id),
                    label: el.dataset.label || '',
                });
            });
        });

        syncSelectedUi();
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/\n/g, ' ');
    }

    async function loadTargets(query = '') {
        listEl.innerHTML = '<p class="px-3 py-6 text-center text-sm text-slate-400">Loading…</p>';

        try {
            const q = encodeURIComponent(query.trim());
            const [convRes, userRes] = await Promise.all([
                fetch(`/messages/conversations?search=${q}`, { headers: { Accept: 'application/json' } }),
                fetch(`/messages/users?search=${q}`, { headers: { Accept: 'application/json' } }),
            ]);

            const convJson = convRes.ok ? await convRes.json() : { data: [] };
            const userJson = userRes.ok ? await userRes.json() : { data: [] };

            const conversations = Array.isArray(convJson.data?.data)
                ? convJson.data.data
                : (Array.isArray(convJson.data) ? convJson.data : []);
            const users = Array.isArray(userJson.data?.data)
                ? userJson.data.data
                : (Array.isArray(userJson.data) ? userJson.data : []);

            renderList(conversations.slice(0, 12), users.slice(0, 12));
        } catch (e) {
            listEl.innerHTML = '<p class="px-3 py-6 text-center text-sm text-red-500">Could not load people.</p>';
        }
    }

    async function sendShare() {
        if (!pending || selected.size < 1) return;

        const recipients = Array.from(selected.values()).map((item) => ({
            kind: item.kind,
            id: item.id,
        }));

        sendBtn.disabled = true;
        sendBtn.textContent = 'Sharing…';

        const body = {
            document_type: pending.type,
            document_id: pending.id,
            note: noteEl.value.trim() || null,
            recipients: recipients,
        };

        try {
            const res = await fetch('/messages/share-document', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(body),
            });

            const json = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(json.message || 'Share failed.');
            }

            const sharedCount = Number(json.shared_count || recipients.length || 1);
            const conversationId = json.conversation_id;
            closeShareModal();

            if (typeof window.showMpToast === 'function') {
                window.showMpToast(
                    sharedCount > 1
                        ? ('Document shared with ' + sharedCount + ' conversations.')
                        : 'Document shared in Messages.',
                    {
                        type: 'success',
                        title: 'Shared',
                        timer: 3000,
                        action: {
                            label: 'View in Messages',
                            onClick: function () {
                                if (typeof window.openMessagingModal === 'function') {
                                    window.openMessagingModal();
                                }
                                if (conversationId && typeof window.openModalConversation === 'function') {
                                    window.openModalConversation(conversationId);
                                }
                            },
                        },
                    }
                );
            }
        } catch (e) {
            const msg = e.message || 'Could not share this document right now.';
            if (typeof window.showMpToast === 'function') {
                window.showMpToast(msg, { type: 'info', title: 'Share' });
            } else {
                alert(msg);
            }
            syncSelectedUi();
        } finally {
            if (selected.size > 0) {
                sendBtn.textContent = selected.size > 1 ? ('Share (' + selected.size + ')') : 'Share';
            } else {
                sendBtn.textContent = 'Share';
            }
        }
    }

    window.openShareDocumentToMessages = function (type, id, label) {
        pending = {
            type: String(type || '').toLowerCase(),
            id: Number(id),
            label: label || String(type || 'Document').toUpperCase(),
        };

        if (!pending.type || !pending.id) return;

        selected = new Map();
        summaryEl.textContent = pending.label;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        lucideRefresh(modal);
        syncSelectedUi();
        loadTargets('');
        searchEl.focus();
    };

    searchEl?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadTargets(searchEl.value), 250);
    });

    sendBtn?.addEventListener('click', sendShare);
    closeBtn?.addEventListener('click', closeShareModal);
    cancelBtn?.addEventListener('click', closeShareModal);
    backdrop?.addEventListener('click', closeShareModal);

    document.addEventListener('click', function (event) {
        const btn = event.target && event.target.closest
            ? event.target.closest('.js-share-document')
            : null;
        if (!btn) return;
        event.preventDefault();
        window.openShareDocumentToMessages(
            btn.getAttribute('data-share-doc-type'),
            btn.getAttribute('data-share-doc-id'),
            btn.getAttribute('data-share-doc-label')
        );
    });
})();
</script>
