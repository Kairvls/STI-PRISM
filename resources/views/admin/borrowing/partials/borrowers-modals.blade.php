{{-- Borrower Records Modal --}}
<div
    id="borrowerRecordsModal"
    class="fixed inset-0 z-[11000] hidden items-center justify-center bg-slate-950/40 p-4"
    onclick="if (event.target === this) closeBorrowerRecordsModal()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="borrowerRecordsTitle"
>
    <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
                <h3 id="borrowerRecordsTitle" class="truncate text-base font-semibold text-slate-950">Borrow records</h3>
                <p id="borrowerRecordsSubtitle" class="mt-0.5 text-sm text-slate-500">Loading…</p>
            </div>
            <button type="button" onclick="closeBorrowerRecordsModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div id="borrowerRecordsBody" class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
            <div class="flex items-center justify-center py-16 text-sm text-slate-400">Loading records…</div>
        </div>

        <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 px-5 py-3">
            <button type="button" onclick="closeBorrowerRecordsModal()" class="rounded-lg px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">Close</button>
        </div>
    </div>
</div>

{{-- Register External Borrower Modal --}}
<div
    id="registerBorrowerModal"
    class="fixed inset-0 z-[11000] hidden items-center justify-center bg-slate-950/40 p-4"
    onclick="if (event.target === this) closeRegisterBorrowerModal()"
>
    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="text-base font-semibold text-slate-950">Register borrower</h3>
                <p class="mt-0.5 text-xs text-slate-500">Save outside borrowers for future loans.</p>
            </div>
            <button type="button" onclick="closeRegisterBorrowerModal()" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <form
            id="registerBorrowerForm"
            method="POST"
            action="{{ route('admin.borrowing.external-borrowers.store') }}"
            class="space-y-4 px-5 py-5"
        >
            @csrf
            <input type="hidden" name="redirect_to" value="borrowers">

            <div>
                <label for="regFullName" class="mb-1.5 block text-xs font-semibold text-slate-600">Full name <span class="text-red-500">*</span></label>
                <input id="regFullName" type="text" name="full_name" value="{{ old('full_name') }}" required maxlength="50" placeholder="Borrower full name" class="h-10 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
            </div>

            <div>
                <label for="regOrganization" class="mb-1.5 block text-xs font-semibold text-slate-600">Organization / company <span class="text-red-500">*</span></label>
                <input id="regOrganization" type="text" name="organization" value="{{ old('organization') }}" required maxlength="50" placeholder="e.g. VSU, WLC, City Hall" class="h-10 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="regPosition" class="mb-1.5 block text-xs font-semibold text-slate-600">Role / title</label>
                    <input id="regPosition" type="text" name="position" value="{{ old('position') }}" maxlength="120" placeholder="Optional" class="h-10 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
                </div>
                <div>
                    <label for="borrowers-register-contact" class="mb-1.5 block text-xs font-semibold text-slate-600">Contact number</label>
                    @include('partials.phone-input', [
                        'name' => 'contact_number',
                        'value' => old('contact_number'),
                        'id' => 'borrowers-register-contact',
                        'inputClass' => 'h-10 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-800 outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10',
                    ])
                </div>
            </div>

            <div>
                <label for="regEmail" class="mb-1.5 block text-xs font-semibold text-slate-600">Email</label>
                <input id="regEmail" type="email" name="email" value="{{ old('email') }}" maxlength="150" inputmode="email" autocomplete="email" pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$" placeholder="name@example.com" class="h-10 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
            </div>

            <div>
                <label for="regNotes" class="mb-1.5 block text-xs font-semibold text-slate-600">Notes</label>
                <textarea id="regNotes" name="notes" rows="2" maxlength="1000" placeholder="Optional" class="w-full resize-none rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" onclick="closeRegisterBorrowerModal()" class="h-9 rounded-lg px-3.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-4 text-xs font-semibold text-white hover:bg-blue-800">
                    <i data-lucide="user-plus" class="h-3.5 w-3.5"></i>
                    Save borrower
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const borrowerRecordsUrl = @js(route('admin.borrowing.borrowers.records'));
    const borrowingIndexUrl = @js(route('admin.borrowing.index'));

    function formatBorrowerRecordDate(value) {
        if (!value) return '—';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return String(value);
        return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function borrowerStatusBadgeClass(status) {
        switch (String(status || '')) {
            case 'Borrowed': return 'bg-sky-50 text-sky-700';
            case 'Returned': return 'bg-emerald-50 text-emerald-700';
            case 'Overdue': return 'bg-rose-50 text-rose-700';
            case 'Pending': return 'bg-amber-50 text-amber-800';
            default: return 'bg-slate-100 text-slate-600';
        }
    }

    function escapeBorrowerHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderBorrowerRecords(payload) {
        const body = document.getElementById('borrowerRecordsBody');
        const subtitle = document.getElementById('borrowerRecordsSubtitle');
        if (!body) return;

        const records = Array.isArray(payload.records) ? payload.records : [];
        if (subtitle) {
            subtitle.textContent = records.length
                ? `${records.length} borrow ${records.length === 1 ? 'record' : 'records'} · click a row to open it`
                : 'No borrow records for this person';
        }

        if (!records.length) {
            body.innerHTML = '<div class="py-16 text-center text-sm text-slate-500">No borrow records found.</div>';
            return;
        }

        const rows = records.map((row) => {
            const slip = String(row.slip || '').trim();
            const eventName = String(row.event_name || '').trim();
            const eventLoc = String(row.event_location || '').trim();
            const asset = String(row.asset_tag || '').trim();
            const purpose = String(row.purpose || '').trim();
            const meta = [];
            if (slip) meta.push(`Slip ${escapeBorrowerHtml(slip)}`);
            if (eventName) meta.push(escapeBorrowerHtml(eventName) + (eventLoc ? ` · ${escapeBorrowerHtml(eventLoc)}` : ''));
            if (purpose) meta.push(escapeBorrowerHtml(purpose));

            const href = new URL(borrowingIndexUrl, window.location.origin);
            href.searchParams.set('record', String(row.id || ''));

            return `
                <tr
                    class="align-top cursor-pointer transition hover:bg-[#0025cc]/5 focus-within:bg-[#0025cc]/5"
                    role="link"
                    tabindex="0"
                    data-href="${escapeBorrowerHtml(href.toString())}"
                    onclick="window.location.href = this.dataset.href"
                    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href = this.dataset.href; }"
                >
                    <td class="px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">${escapeBorrowerHtml(row.equipment || 'Equipment')}</p>
                        ${asset ? `<p class="mt-0.5 font-mono text-[11px] text-slate-400">${escapeBorrowerHtml(asset)}</p>` : ''}
                        ${meta.length ? `<p class="mt-1 text-[11px] text-slate-500">${meta.join(' · ')}</p>` : ''}
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold ${borrowerStatusBadgeClass(row.status)}">${escapeBorrowerHtml(row.status || '—')}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">${escapeBorrowerHtml(formatBorrowerRecordDate(row.borrow_date))}</td>
                    <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">${escapeBorrowerHtml(formatBorrowerRecordDate(row.expected_return))}</td>
                    <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">${escapeBorrowerHtml(formatBorrowerRecordDate(row.actual_return))}</td>
                </tr>
            `;
        }).join('');

        body.innerHTML = `
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400">
                            <th class="px-4 py-2.5">Equipment</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5">Borrowed</th>
                            <th class="px-4 py-2.5">Expected</th>
                            <th class="px-4 py-2.5">Returned</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">${rows}</tbody>
                </table>
            </div>
        `;
    }

    async function openBorrowerRecordsModal(name) {
        const modal = document.getElementById('borrowerRecordsModal');
        const title = document.getElementById('borrowerRecordsTitle');
        const subtitle = document.getElementById('borrowerRecordsSubtitle');
        const body = document.getElementById('borrowerRecordsBody');
        if (!modal || !body) return;

        const borrowerName = String(name || '').trim();
        if (title) title.textContent = borrowerName || 'Borrow records';
        if (subtitle) subtitle.textContent = 'Loading…';
        body.innerHTML = '<div class="flex items-center justify-center py-16 text-sm text-slate-400">Loading records…</div>';

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        if (window.lucide) window.lucide.createIcons();

        try {
            const url = new URL(borrowerRecordsUrl, window.location.origin);
            url.searchParams.set('name', borrowerName);
            const response = await fetch(url.toString(), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('Failed to load records');
            const payload = await response.json();
            renderBorrowerRecords(payload);
        } catch (error) {
            if (subtitle) subtitle.textContent = 'Could not load records';
            body.innerHTML = '<div class="py-16 text-center text-sm text-rose-600">Unable to load borrow records. Try again.</div>';
        }
    }

    function closeBorrowerRecordsModal() {
        const modal = document.getElementById('borrowerRecordsModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (!document.getElementById('registerBorrowerModal')?.classList.contains('flex')) {
            document.body.style.overflow = '';
        }
    }

    function openRegisterBorrowerModal(prefill) {
        const modal = document.getElementById('registerBorrowerModal');
        if (!modal) return;
        const data = prefill && typeof prefill === 'object' ? prefill : {};
        const nameInput = document.getElementById('regFullName');
        const orgInput = document.getElementById('regOrganization');
        if (nameInput) nameInput.value = data.name || '';
        if (orgInput) orgInput.value = data.organization || '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        const phone = document.getElementById('borrowers-register-contact');
        if (phone && typeof window.initPrismPhoneInput === 'function') {
            window.initPrismPhoneInput(phone);
        }
        if (window.lucide) window.lucide.createIcons();
        nameInput?.focus();
    }

    function closeRegisterBorrowerModal() {
        const modal = document.getElementById('registerBorrowerModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (!document.getElementById('borrowerRecordsModal')?.classList.contains('flex')) {
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (document.getElementById('registerBorrowerModal')?.classList.contains('flex')) {
            closeRegisterBorrowerModal();
        } else if (document.getElementById('borrowerRecordsModal')?.classList.contains('flex')) {
            closeBorrowerRecordsModal();
        }
    });
</script>
