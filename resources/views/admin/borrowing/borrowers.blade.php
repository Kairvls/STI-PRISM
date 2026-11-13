@extends('layouts.maintenance-layout')

@php
    use Carbon\Carbon;
@endphp

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
        <a
            href="{{ route('admin.borrowing.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50"
        >
            <i data-lucide="clipboard-list" class="h-4 w-4"></i>
            Borrowing records
        </a>
        <button
            type="button"
            onclick="openRegisterBorrowerModal()"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-[#001fa8]"
        >
            <i data-lucide="user-plus" class="h-4 w-4"></i>
            Register borrower
        </button>
    </div>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-950">Borrowers lists</h2>
                
            </div>
            <form method="GET" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <input
                    type="text"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search name or organization…"
                    class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none focus:border-slate-300 focus:bg-white sm:w-64"
                >
                <select
                    name="filter"
                    class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none"
                >
                    <option value="all" @selected(($filter ?? 'all') === 'all')>All borrowers</option>
                    <option value="registered" @selected(($filter ?? '') === 'registered')>Registered only</option>
                    <option value="history" @selected(($filter ?? '') === 'history')>History only (not registered)</option>
                </select>
                <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fa8]">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Apply
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400">
                        <th class="px-5 py-3">Borrower</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Organization</th>
                        <th class="px-5 py-3">Contact</th>
                        <th class="px-5 py-3">Activity</th>
                        <th class="px-5 py-3">Last borrow</th>
                        <th class="w-[180px] px-5 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse (($borrowers ?? collect()) as $person)
                        @php
                            $isRegistered = !empty($person->is_registered);
                            $org = trim((string) ($person->organization ?? ''));
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-slate-900">{{ $person->name }}</p>
                                @if (!empty($person->position))
                                    <p class="mt-0.5 text-[11px] text-slate-400">{{ $person->position }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($isRegistered)
                                    <span class="inline-flex rounded-md bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700">Registered</span>
                                @else
                                    <span class="inline-flex rounded-md bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-800">History only</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ $org !== '' ? $org : '—' }}</td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-slate-700">{{ \App\Support\PhoneNumber::formatForDisplay($person->contact ?? null) ?: '—' }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ $person->email ?: '' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ (int) ($person->total_borrows ?? 0) }} total</span>
                                    @if ((int) ($person->open_borrows ?? 0) > 0)
                                        <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800">{{ (int) $person->open_borrows }} open</span>
                                    @endif
                                    @if ((int) ($person->overdue_borrows ?? 0) > 0)
                                        <span class="rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">{{ (int) $person->overdue_borrows }} overdue</span>
                                    @endif
                                    @if ((int) ($person->event_borrows ?? 0) > 0)
                                        <span class="rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700">{{ (int) $person->event_borrows }} event</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ !empty($person->last_borrow_date) ? Carbon::parse($person->last_borrow_date)->format('M j, Y') : '—' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-center gap-1.5">
                                    <button
                                        type="button"
                                        onclick="openBorrowerRecordsModal(@js($person->name))"
                                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                    >
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                        Records
                                    </button>
                                    @unless ($isRegistered)
                                        <button
                                            type="button"
                                            class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-[#0025cc]/20 bg-[#0025cc]/5 px-3 text-xs font-semibold text-[#0025cc] transition hover:bg-[#0025cc]/10"
                                            onclick="openRegisterBorrowerModal(@js(['name' => $person->name, 'organization' => $org]))"
                                        >
                                            <i data-lucide="user-plus" class="h-3.5 w-3.5"></i>
                                            Register
                                        </button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <p class="text-sm font-medium text-slate-700">No borrowers found</p>
                                <p class="mt-1 text-xs text-slate-400">People appear here from borrow history or when you register an external borrower.</p>
                                <button
                                    type="button"
                                    onclick="openRegisterBorrowerModal()"
                                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white"
                                >
                                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                                    Register borrower
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($borrowers, 'links'))
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $borrowers->links() }}
            </div>
        @endif
    </section>

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
                <button type="button" onclick="closeBorrowerRecordsModal()" class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Close</button>
            </div>
        </div>
    </div>

    <div
        id="registerBorrowerModal"
        class="fixed inset-0 z-[11000] hidden items-center justify-center bg-slate-950/40 p-4"
        onclick="if (event.target === this) closeRegisterBorrowerModal()"
    >
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-950">Register borrower</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Save an outside borrower (other school, city office, partner) so they can be selected on future loans.</p>
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
                    <label for="regFullName" class="mb-1.5 block text-sm text-slate-600">Full name <span class="text-red-500">*</span></label>
                    <input id="regFullName" type="text" name="full_name" value="{{ old('full_name') }}" required maxlength="50" placeholder="Borrower full name" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div>
                    <label for="regOrganization" class="mb-1.5 block text-sm text-slate-600">Organization / company <span class="text-red-500">*</span></label>
                    <input id="regOrganization" type="text" name="organization" value="{{ old('organization') }}" required maxlength="50" placeholder="e.g. VSU, WLC, City Hall" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="regPosition" class="mb-1.5 block text-sm text-slate-600">Role / title</label>
                        <input id="regPosition" type="text" name="position" value="{{ old('position') }}" maxlength="120" placeholder="Optional" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:ring-2 focus:ring-slate-900/10">
                    </div>
                    <div>
                        <label for="borrowers-register-contact" class="mb-1.5 block text-sm text-slate-600">Contact number</label>
                        @include('partials.phone-input', [
                            'name' => 'contact_number',
                            'value' => old('contact_number'),
                            'id' => 'borrowers-register-contact',
                            'inputClass' => 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-800 outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10',
                        ])
                    </div>
                </div>

                <div>
                    <label for="regEmail" class="mb-1.5 block text-sm text-slate-600">Email</label>
                    <input id="regEmail" type="email" name="email" value="{{ old('email') }}" maxlength="150" inputmode="email" autocomplete="email" pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$" placeholder="name@example.com" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div>
                    <label for="regNotes" class="mb-1.5 block text-sm text-slate-600">Notes</label>
                    <textarea id="regNotes" name="notes" rows="2" maxlength="1000" placeholder="Optional" class="w-full resize-none rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm outline-none ring-1 ring-slate-200/80 focus:ring-2 focus:ring-slate-900/10">{{ old('notes') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" onclick="closeRegisterBorrowerModal()" class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#001fa8]">
                        <i data-lucide="user-plus" class="h-4 w-4"></i>
                        Save borrower
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const borrowerRecordsUrl = @js(route('admin.borrowing.borrowers.records'));
        const borrowingIndexUrl = @js(route('admin.borrowing.index'));

        function formatBorrowDate(value) {
            if (!value) return '—';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return String(value);
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function statusBadgeClass(status) {
            switch (String(status || '')) {
                case 'Borrowed': return 'bg-sky-50 text-sky-700';
                case 'Returned': return 'bg-emerald-50 text-emerald-700';
                case 'Overdue': return 'bg-rose-50 text-rose-700';
                default: return 'bg-slate-100 text-slate-600';
            }
        }

        function escapeHtml(value) {
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
                    ? `${records.length} borrow ${records.length === 1 ? 'record' : 'records'} · click a row to open it on Borrowing`
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
                if (slip) meta.push(`Slip ${escapeHtml(slip)}`);
                if (eventName) meta.push(escapeHtml(eventName) + (eventLoc ? ` · ${escapeHtml(eventLoc)}` : ''));
                if (purpose) meta.push(escapeHtml(purpose));

                const href = new URL(borrowingIndexUrl, window.location.origin);
                href.searchParams.set('record', String(row.id || ''));

                return `
                    <tr
                        class="align-top cursor-pointer transition hover:bg-[#0025cc]/5 focus-within:bg-[#0025cc]/5"
                        role="link"
                        tabindex="0"
                        data-href="${escapeHtml(href.toString())}"
                        onclick="window.location.href = this.dataset.href"
                        onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href = this.dataset.href; }"
                    >
                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-slate-900">${escapeHtml(row.equipment || 'Equipment')}</p>
                            ${asset ? `<p class="mt-0.5 text-[11px] text-slate-400">${escapeHtml(asset)}</p>` : ''}
                            ${meta.length ? `<p class="mt-1 text-[11px] text-slate-500">${meta.join(' · ')}</p>` : ''}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-semibold ${statusBadgeClass(row.status)}">${escapeHtml(row.status || '—')}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">${escapeHtml(formatBorrowDate(row.borrow_date))}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">${escapeHtml(formatBorrowDate(row.expected_return))}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">${escapeHtml(formatBorrowDate(row.actual_return))}</td>
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
            if (nameInput && data.name) nameInput.value = data.name;
            if (orgInput && data.organization) orgInput.value = data.organization;
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
                return;
            }
            closeBorrowerRecordsModal();
        });

        @if ($errors->any())
            document.addEventListener('DOMContentLoaded', function () {
                openRegisterBorrowerModal();
            });
        @endif
    </script>
@endsection
