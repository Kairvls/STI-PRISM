        <div
            id="viewEquipmentModal"
            class="fixed inset-0 z-50 hidden"
            aria-hidden="true"
        >
            <div
                class="absolute inset-0 bg-gray-950/40 transition-opacity"
                onclick="closeEquipmentModal()"
            ></div>

            <aside
                id="viewEquipmentModalPanel"
                class="absolute right-0 top-0 flex h-full w-full max-w-[420px] translate-x-full flex-col overflow-hidden rounded-l-2xl border-l border-slate-200 bg-white shadow-2xl shadow-slate-950/10 transition-transform duration-300 ease-out"
                role="dialog"
                aria-modal="true"
                aria-labelledby="eqAssetModal_drawer_title"
            >
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="min-w-0">
                        <h2 id="eqAssetModal_drawer_title" class="text-xl font-semibold tracking-tight text-slate-900">Asset details</h2>
                        <p id="eqAssetModal_drawer_subtitle" class="mt-1 text-sm text-slate-500">Review equipment information and lifecycle.</p>
                    </div>
                    <button type="button" onclick="closeEquipmentModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                @include('maintenance-personnel.equipment.partials.equipment-asset-details-body')
            </aside>
        </div>

        <style>
            .eq-drawer-scroll {
                scrollbar-width: thin;
                scrollbar-color: #cbd5e1 transparent;
            }

            .eq-drawer-scroll::-webkit-scrollbar {
                width: 6px;
            }

            .eq-drawer-scroll::-webkit-scrollbar-thumb {
                border-radius: 999px;
                background: #cbd5e1;
            }
        </style>

    <script>
        const equipmentModalLifecycleUrl = @json($assetLifecycleUrl ?? url('/maintenance/equipment/lifecycle/__ID__'));
        let equipmentModalLifecycleRequest = 0;
        let equipmentModalCurrentAssetTag = '';

        function formatEquipmentAssetDate(value) {
            if (!value) return '—';
            const date = new Date(String(value).replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) return String(value);
            return date.toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            });
        }

        function setEquipmentModalText(id, value) {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = value && String(value).trim() !== '' ? value : '—';
            }
        }

        function applyEquipmentStatusBadge(status) {
            const el = document.getElementById('eqAssetModal_status_badge');
            if (!el) return;

            const label = status && String(status).trim() !== '' ? status : 'Unknown';
            el.textContent = label;
            el.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold';

            const map = {
                'Active': 'bg-emerald-50 text-emerald-700',
                'Under Maintenance': 'bg-amber-50 text-amber-700',
                'Borrowed': 'bg-sky-50 text-sky-700',
                'For Replacement': 'bg-orange-50 text-orange-700',
                'Disposed': 'bg-rose-50 text-rose-700',
            };

            el.classList.add(...(map[label] || 'bg-slate-100 text-slate-600').split(' '));
        }

        function applyEquipmentConditionBadge(condition) {
            const el = document.getElementById('eqAssetModal_condition_badge');
            if (!el) return;

            const label = condition && String(condition).trim() !== '' ? condition : '—';
            el.textContent = label;
            el.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium';

            if (label === 'Good') {
                el.classList.add('bg-emerald-50', 'text-emerald-700');
            } else if (label === 'Damaged') {
                el.classList.add('bg-rose-50', 'text-rose-700');
            } else {
                el.classList.add('bg-slate-100', 'text-slate-600');
            }
        }

        function applyEquipmentPlacementBadge(roomType) {
            const el = document.getElementById('eqAssetModal_placement_badge');
            if (!el) return;

            const isStorage = roomType === @json(\App\Support\RoomCategories::STORAGE_TYPE);
            el.textContent = isStorage ? 'In storage' : 'Deployed';
            el.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium';
            el.classList.add(
                ...(isStorage
                    ? ['bg-amber-50', 'text-amber-700']
                    : ['bg-sky-50', 'text-sky-700'])
            );
        }

        function switchEquipmentModalTab(tab) {
            const tabs = ['overview', 'lifecycle', 'activity'];
            tabs.forEach((name) => {
                const button = document.getElementById(`eqAssetModal_tab_${name}`);
                const panel = document.getElementById(`eqAssetModal_panel_${name}`);
                const active = name === tab;

                if (button) {
                    button.classList.toggle('border-[#0025cc]', active);
                    button.classList.toggle('text-[#0025cc]', active);
                    button.classList.toggle('font-semibold', active);
                    button.classList.toggle('border-transparent', !active);
                    button.classList.toggle('text-slate-500', !active);
                    button.classList.toggle('font-medium', !active);
                    button.setAttribute('aria-selected', active ? 'true' : 'false');
                }

                panel?.classList.toggle('hidden', !active);
            });
        }

        function copyEquipmentAssetTag() {
            if (!equipmentModalCurrentAssetTag) return;

            navigator.clipboard?.writeText(equipmentModalCurrentAssetTag).then(() => {
                const btn = document.getElementById('eqAssetModal_copy_tag');
                if (!btn) return;
                const original = btn.innerHTML;
                btn.innerHTML = '<i data-lucide="check" class="h-3.5 w-3.5 text-emerald-600"></i>';
                if (window.lucide) window.lucide.createIcons();
                setTimeout(() => {
                    btn.innerHTML = original;
                    if (window.lucide) window.lucide.createIcons();
                }, 1500);
            });
        }

        const EQUIPMENT_TIMELINE_LIMIT = 10;
        const EQUIPMENT_STAGE_CLASSES = {
            purchase: 'bg-indigo-50 text-indigo-700',
            received: 'bg-sky-50 text-sky-700',
            inventory: 'bg-slate-100 text-slate-700',
            assigned: 'bg-violet-50 text-violet-700',
            borrowed: 'bg-cyan-50 text-cyan-700',
            repair: 'bg-amber-50 text-amber-700',
            for_replacement: 'bg-orange-50 text-orange-700',
            replacement: 'bg-emerald-50 text-emerald-700',
            disposal: 'bg-rose-50 text-rose-700',
        };
        let equipmentModalTimeline = { events: [], stages: {}, counts: {}, stage: 'all', expanded: false };

        function escapeEquipmentHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            })[ch]);
        }

        function renderEquipmentLifecycleTimeline() {
            const { events, stages, counts, stage } = equipmentModalTimeline;
            const stagesEl = document.getElementById('eqAssetModal_lifecycle_stages');
            const timelineEl = document.getElementById('eqAssetModal_lifecycle_timeline');
            const noteEl = document.getElementById('eqAssetModal_lifecycle_note');
            const moreEl = document.getElementById('eqAssetModal_lifecycle_more');
            if (!stagesEl || !timelineEl) return;

            const chip = (key, label, count) => {
                const on = stage === key;
                return `<button type="button" onclick="filterEquipmentLifecycle('${key}')" class="rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 transition ${on ? 'bg-[#0025cc] text-white ring-[#0025cc]' : 'bg-slate-50 text-slate-600 ring-slate-200 hover:bg-slate-100'}">${escapeEquipmentHtml(label)} · ${count}</button>`;
            };

            stagesEl.innerHTML = events.length
                ? chip('all', 'All', events.length) + Object.keys(stages)
                    .filter((key) => counts[key])
                    .map((key) => chip(key, stages[key], counts[key]))
                    .join('')
                : '';

            const matching = stage === 'all' ? events : events.filter((event) => event.stage === stage);
            const expanded = equipmentModalTimeline.expanded;
            const visible = expanded ? matching : matching.slice(0, EQUIPMENT_TIMELINE_LIMIT);

            timelineEl.innerHTML = visible.length
                ? visible.map((event, index) => `
                    <div class="relative flex gap-3 ${index < visible.length - 1 ? 'pb-4' : ''}">
                        ${index < visible.length - 1 ? '<span class="absolute left-[5px] top-4 bottom-0 w-px bg-slate-200"></span>' : ''}
                        <span class="relative z-10 mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ring-4 ring-white ${escapeEquipmentHtml(event.meta?.dot || 'bg-slate-400')}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <p class="text-sm font-semibold text-slate-900">${escapeEquipmentHtml(event.title)}</p>
                                <span class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold ${EQUIPMENT_STAGE_CLASSES[event.stage] || 'bg-slate-100 text-slate-700'}">${escapeEquipmentHtml(event.stage_label || event.type_label)}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">${escapeEquipmentHtml(formatEquipmentAssetDate(event.occurred_at))}</p>
                            ${event.description ? `<p class="mt-1 text-xs leading-5 text-slate-600">${escapeEquipmentHtml(event.description)}</p>` : ''}
                        </div>
                    </div>`).join('')
                : `<p class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-400">${events.length ? 'No events for this stage.' : 'No lifecycle events recorded yet.'}</p>`;

            const older = matching.length - EQUIPMENT_TIMELINE_LIMIT;
            if (noteEl) {
                noteEl.textContent = older > 0 && !expanded ? `Latest ${EQUIPMENT_TIMELINE_LIMIT} of ${matching.length}` : '';
            }
            if (moreEl) {
                moreEl.classList.toggle('hidden', older <= 0);
                moreEl.classList.toggle('inline-flex', older > 0);
                document.getElementById('eqAssetModal_lifecycle_more_icon')?.classList.toggle('rotate-180', expanded);
                const label = document.getElementById('eqAssetModal_lifecycle_more_label');
                if (label) {
                    label.textContent = expanded
                        ? 'Show fewer'
                        : `Show ${older} older ${older === 1 ? 'event' : 'events'}`;
                }
            }
        }

        function filterEquipmentLifecycle(stage) {
            equipmentModalTimeline.stage = stage;
            equipmentModalTimeline.expanded = false;
            renderEquipmentLifecycleTimeline();
        }

        function toggleEquipmentLifecycle() {
            const collapsing = equipmentModalTimeline.expanded;
            equipmentModalTimeline.expanded = !collapsing;
            renderEquipmentLifecycleTimeline();
            if (collapsing) {
                document.getElementById('eqAssetModal_lifecycle_more')?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        }

        function renderEquipmentActivity(data) {
            const contentEl = document.getElementById('eqAssetModal_activity_content');
            const emptyEl = document.getElementById('eqAssetModal_activity_empty');
            if (!contentEl) return;

            contentEl.innerHTML = '';
            const transfers = Array.isArray(data.transfers) ? data.transfers.slice(0, 6) : [];
            const maintenance = Array.isArray(data.maintenance) ? data.maintenance.slice(0, 6) : [];

            if (!transfers.length && !maintenance.length) {
                contentEl.classList.add('hidden');
                emptyEl?.classList.remove('hidden');
                return;
            }

            emptyEl?.classList.add('hidden');
            contentEl.classList.remove('hidden');

            if (transfers.length) {
                const block = document.createElement('div');
                block.className = 'overflow-hidden rounded-xl border border-slate-200 bg-white';
                block.innerHTML = '<div class="border-b border-slate-100 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Recent moves</div>';
                transfers.forEach((move, index) => {
                    const item = document.createElement('div');
                    item.className = `px-4 py-3.5 ${index ? 'border-t border-slate-100' : ''}`;
                    item.innerHTML = `
                        <p class="text-sm font-medium text-slate-800">${move.from_room_name || 'Unassigned'} → ${move.to_room_name || '—'}</p>
                        <p class="mt-1 text-xs text-slate-400">${formatEquipmentAssetDate(move.created_at)}</p>
                    `;
                    block.appendChild(item);
                });
                contentEl.appendChild(block);
            }

            if (maintenance.length) {
                const block = document.createElement('div');
                block.className = 'overflow-hidden rounded-xl border border-slate-200 bg-white';
                block.innerHTML = '<div class="border-b border-slate-100 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Maintenance</div>';
                maintenance.forEach((row, index) => {
                    const item = document.createElement('div');
                    item.className = `px-4 py-3.5 ${index ? 'border-t border-slate-100' : ''}`;
                    item.innerHTML = `
                        <p class="truncate text-sm font-medium text-slate-800">${row.findings || row.status || 'Maintenance'}</p>
                        <p class="mt-1 text-xs text-slate-400">${formatEquipmentAssetDate(row.at)}</p>
                    `;
                    block.appendChild(item);
                });
                contentEl.appendChild(block);
            }
        }

        function renderEquipmentModalLifecycle(data) {
            const loadingEl = document.getElementById('eqAssetModal_lifecycle_loading');
            const contentEl = document.getElementById('eqAssetModal_lifecycle_content');
            const emptyEl = document.getElementById('eqAssetModal_lifecycle_empty');
            const activityLoadingEl = document.getElementById('eqAssetModal_activity_loading');
            const activityContentEl = document.getElementById('eqAssetModal_activity_content');
            const activityEmptyEl = document.getElementById('eqAssetModal_activity_empty');

            loadingEl?.classList.add('hidden');
            activityLoadingEl?.classList.add('hidden');
            emptyEl?.classList.add('hidden');
            activityEmptyEl?.classList.add('hidden');

            equipmentModalTimeline = {
                events: Array.isArray(data.events) ? data.events : [],
                stages: data.stages || {},
                counts: data.stage_counts || {},
                stage: 'all', expanded: false,
            };
            renderEquipmentLifecycleTimeline();
            contentEl?.classList.remove('hidden');
            if (window.lucide) window.lucide.createIcons();

            renderEquipmentActivity(data);
            renderEquipmentModalProcurement(data?.procurement || null);
        }

        function renderEquipmentModalProcurement(profile) {
            if (!profile || typeof profile !== 'object') return;

            setEquipmentModalText('eqAssetModal_supplier', profile.supplier_name || '');
            setEquipmentModalText('eqAssetModal_rr', profile.receiving_report_number || '');

            const atp = String(profile.atp_number || '').trim();
            const ris = String(profile.ris_number || '').trim();
            const atpRis = [atp || null, ris || null].filter(Boolean).join(' / ');
            setEquipmentModalText('eqAssetModal_atp_ris', atpRis);

            if (profile.purchase_date || profile.purchase_order_date) {
                const purchaseEl = document.getElementById('eqAssetModal_purchase_date');
                if (purchaseEl && (!purchaseEl.textContent || purchaseEl.textContent.trim() === '—' || purchaseEl.textContent.trim() === '-')) {
                    setEquipmentModalText(
                        'eqAssetModal_purchase_date',
                        formatEquipmentAssetDate(profile.purchase_order_date || profile.purchase_date)
                    );
                }
            }
            if (profile.acquired_date || profile.receiving_report_date) {
                const acquiredEl = document.getElementById('eqAssetModal_acquired_date');
                if (acquiredEl && (!acquiredEl.textContent || acquiredEl.textContent.trim() === '—' || acquiredEl.textContent.trim() === '-')) {
                    setEquipmentModalText(
                        'eqAssetModal_acquired_date',
                        formatEquipmentAssetDate(profile.acquired_date || profile.receiving_report_date)
                    );
                }
            }
        }

        async function loadEquipmentModalLifecycle(equipmentId) {
            const loadingEl = document.getElementById('eqAssetModal_lifecycle_loading');
            const contentEl = document.getElementById('eqAssetModal_lifecycle_content');
            const emptyEl = document.getElementById('eqAssetModal_lifecycle_empty');
            const activityLoadingEl = document.getElementById('eqAssetModal_activity_loading');
            const activityContentEl = document.getElementById('eqAssetModal_activity_content');
            const activityEmptyEl = document.getElementById('eqAssetModal_activity_empty');
            const requestId = ++equipmentModalLifecycleRequest;

            loadingEl?.classList.remove('hidden');
            activityLoadingEl?.classList.remove('hidden');
            contentEl?.classList.add('hidden');
            activityContentEl?.classList.add('hidden');
            emptyEl?.classList.add('hidden');
            activityEmptyEl?.classList.add('hidden');
            equipmentModalTimeline = { events: [], stages: {}, counts: {}, stage: 'all', expanded: false };
            const noteEl = document.getElementById('eqAssetModal_lifecycle_note');
            if (noteEl) noteEl.textContent = '';
            if (activityContentEl) activityContentEl.innerHTML = '';

            if (!equipmentId) {
                loadingEl?.classList.add('hidden');
                activityLoadingEl?.classList.add('hidden');
                emptyEl?.classList.remove('hidden');
                activityEmptyEl?.classList.remove('hidden');
                return;
            }

            try {
                const response = await fetch(equipmentModalLifecycleUrl.replace('__ID__', encodeURIComponent(equipmentId)), {
                    headers: { Accept: 'application/json' },
                });

                if (requestId !== equipmentModalLifecycleRequest) return;

                if (!response.ok) {
                    throw new Error('Failed to load lifecycle');
                }

                const data = await response.json();
                if (requestId !== equipmentModalLifecycleRequest) return;

                renderEquipmentModalLifecycle(data);
            } catch (error) {
                if (requestId !== equipmentModalLifecycleRequest) return;
                loadingEl?.classList.add('hidden');
                activityLoadingEl?.classList.add('hidden');
                contentEl?.classList.add('hidden');
                activityContentEl?.classList.add('hidden');
                emptyEl?.classList.remove('hidden');
                activityEmptyEl?.classList.remove('hidden');
            }
        }

        function openEquipmentModal(asset) {
            if (!asset || typeof asset !== 'object') return;

            equipmentModalCurrentAssetTag = asset.asset_tag || '';
            const displayName = asset.name || 'Equipment';

            setEquipmentModalText('eqAssetModal_drawer_subtitle', `Review ${displayName} information and lifecycle.`);
            setEquipmentModalText('eqAssetModal_profile_name', displayName);
            setEquipmentModalText('eqAssetModal_meta_tag', asset.asset_tag);
            setEquipmentModalText('eqAssetModal_meta_serial', asset.serial_number);
            setEquipmentModalText('eqAssetModal_meta_room', asset.room_name);
            setEquipmentModalText('eqAssetModal_brand', asset.brand);
            setEquipmentModalText('eqAssetModal_model', asset.model);
            setEquipmentModalText('eqAssetModal_serial', asset.serial_number);
            setEquipmentModalText('eqAssetModal_category', asset.category_name);
            setEquipmentModalText('eqAssetModal_quantity', String(asset.quantity ?? 1));
            setEquipmentModalText('eqAssetModal_tracking_mode', asset.tracking_mode || 'Individual');
            setEquipmentModalText('eqAssetModal_condition', asset.condition);
            setEquipmentModalText('eqAssetModal_status', asset.inventory_status);
            setEquipmentModalText('eqAssetModal_borrowable', asset.is_borrowable ? 'Yes' : 'No');
            setEquipmentModalText('eqAssetModal_purchase_date', formatEquipmentAssetDate(asset.purchase_date));
            setEquipmentModalText(
                'eqAssetModal_purchase_cost',
                asset.purchase_cost != null && asset.purchase_cost !== ''
                    ? ('₱' + Number(asset.purchase_cost).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
                    : ''
            );
            setEquipmentModalText(
                'eqAssetModal_useful_life',
                asset.useful_life_years != null && asset.useful_life_years !== ''
                    ? (String(asset.useful_life_years) + (Number(asset.useful_life_years) === 1 ? ' year' : ' years'))
                    : ''
            );
            setEquipmentModalText('eqAssetModal_warranty', formatEquipmentAssetDate(asset.warranty_expiration));
            setEquipmentModalText('eqAssetModal_acquired_date', formatEquipmentAssetDate(asset.acquired_date || asset.purchase_date));
            setEquipmentModalText('eqAssetModal_room', asset.room_name);
            setEquipmentModalText('eqAssetModal_zone', asset.placement_zone || asset.location);
            setEquipmentModalText('eqAssetModal_supplier', '');
            setEquipmentModalText('eqAssetModal_rr', '');
            setEquipmentModalText('eqAssetModal_atp_ris', '');

            const categoryBadge = document.getElementById('eqAssetModal_category_badge');
            if (categoryBadge) {
                categoryBadge.textContent = asset.category_name || 'Uncategorized';
            }

            applyEquipmentStatusBadge(asset.inventory_status);
            applyEquipmentConditionBadge(asset.condition);
            applyEquipmentPlacementBadge(asset.room_type);
            switchEquipmentModalTab('overview');

            const profileLink = document.getElementById('eqAssetModal_profile_link');
            if (profileLink) {
                const baseUrl = (asset.view_url || `/maintenance/equipment/view/${asset.id}`).split('?')[0];
                const returnParam = encodeURIComponent(window.location.href);
                profileLink.href = `${baseUrl}?return=${returnParam}`;
            }

            const imageUrl = asset.image_url || '';
            const imageWrap = document.getElementById('modal_image_wrap');
            const image = document.getElementById('modal_image');
            const iconWrap = document.getElementById('modal_layout_icon_wrap');
            const icon = document.getElementById('modal_layout_icon');

            if (imageUrl) {
                image.src = imageUrl;
                image.alt = displayName;
                imageWrap.classList.remove('hidden');
                iconWrap.classList.add('hidden');
                iconWrap.classList.remove('flex');
            } else {
                image.src = '';
                image.alt = '';
                imageWrap.classList.add('hidden');
                iconWrap.classList.remove('hidden');
                iconWrap.classList.add('flex');
                if (icon && window.PrismEquipmentIcons) {
                    icon.innerHTML = window.PrismEquipmentIcons.svg(displayName);
                }
            }

            const modal = document.getElementById('viewEquipmentModal');
            const panel = document.getElementById('viewEquipmentModalPanel');

            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            requestAnimationFrame(() => {
                panel?.classList.remove('translate-x-full');
            });

            loadEquipmentModalLifecycle(asset.id);

            if (window.lucide) window.lucide.createIcons();
        }

        function closeEquipmentModal() {
            equipmentModalLifecycleRequest++;
            const modal = document.getElementById('viewEquipmentModal');
            const panel = document.getElementById('viewEquipmentModalPanel');

            panel?.classList.add('translate-x-full');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';

            setTimeout(() => {
                if (panel?.classList.contains('translate-x-full')) {
                    modal.classList.add('hidden');
                }
            }, 300);
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                const modal = document.getElementById('viewEquipmentModal');
                if (modal && !modal.classList.contains('hidden')) {
                    closeEquipmentModal();
                }
            }
        });
    </script>
