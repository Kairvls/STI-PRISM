@extends ("layouts.maintenance-layout")

@section ("content")

    @php
        $pendingCount = $pendingCount ?? 0;
        $approvedThisMonth = $approvedThisMonth ?? 0;
        $rejectedThisMonth = $rejectedThisMonth ?? 0;
        $totalApplications = $totalApplications ?? 0;
        $status = $status ?? 'pending';
    @endphp

    <div
        class="mb-6 overflow-hidden rounded-lg border-y border-slate-300 bg-gray-100 shadow-sm"
    >
        <div
            class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-y-0 xl:grid-cols-4"
        >
            <div class="flex items-center justify-between px-8 py-6">
                <div class="flex flex-col">
                    <p class="text-sm font-medium text-slate-500">Waiting</p>
                    <h2 class="mt-2 text-5xl font-medium text-slate-900">
                        {{ number_format($pendingCount) }}
                    </h2>
                    <p class="mt-3 text-sm text-slate-500">
                        Need confirmation as faculty or staff
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-between px-8 py-6">
                <div class="flex flex-col">
                    <p class="text-sm font-medium text-slate-500">Approved this month</p>
                    <h2 class="mt-2 text-5xl font-medium text-slate-900">
                        {{ number_format($approvedThisMonth) }}
                    </h2>
                    <p class="mt-3 text-sm text-slate-500">
                        Added to the reporters list
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-between px-8 py-6">
                <div class="flex flex-col">
                    <p class="text-sm font-medium text-slate-500">Declined this month</p>
                    <h2 class="mt-2 text-5xl font-medium text-slate-900">
                        {{ number_format($rejectedThisMonth) }}
                    </h2>
                    <p class="mt-3 text-sm text-slate-500">
                        Not added to the reporters list
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-between px-8 py-6">
                <div class="flex flex-col">
                    <p class="text-sm font-medium text-slate-500">Total applications</p>
                    <h2 class="mt-2 text-5xl font-medium text-slate-900">
                        {{ number_format($totalApplications) }}
                    </h2>
                    <p class="mt-3 text-sm text-slate-500">
                        All submitted reporter applications
                    </p>
                </div>
            </div>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div
            class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex shrink-0 items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <i data-lucide="user-check" class="h-4 w-4"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Reporter applications</h2>
                    <p class="mt-0.5 text-xs text-slate-400">
                        {{ match ($status) {
                            'approved' => 'Approved',
                            'rejected' => 'Declined',
                            default => 'Waiting',
                        } }}
                        ·
                        {{ $applications->total() }}
                        {{ $applications->total() === 1 ? 'record' : 'records' }}
                    </p>
                </div>
            </div>

            <form
                method="GET"
                action="{{ url('/maintenance/reporters/approvals') }}"
                class="flex w-full shrink-0 items-center gap-2 sm:w-auto"
            >
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="relative min-w-0 flex-1 sm:w-[260px] sm:flex-none">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search applications..."
                        class="h-9 w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-xs font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-slate-400"
                    >
                </div>
                <button
                    type="submit"
                    class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800"
                >
                    <i data-lucide="search" class="h-4 w-4"></i>
                    Search
                </button>
                @if (request()->filled('search'))
                    <a
                        href="{{ url('/maintenance/reporters/approvals?status='.$status) }}"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
                        data-tooltip="Clear search"
                        aria-label="Clear search"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full table-fixed text-left">
                <thead class="border-b border-slate-200 bg-slate-50/70">
                    <tr class="text-[11px] font-semibold uppercase tracking-[0.08em] text-black">
                        <th class="w-[18%] px-3 py-2.5 sm:px-4">Employee ID</th>
                        <th class="w-[28%] px-3 py-2.5 sm:px-4">Applicant</th>
                        <th class="w-[12%] px-3 py-2.5 sm:px-4">Type</th>
                        <th class="w-[14%] px-3 py-2.5 sm:px-4">Submitted</th>
                        <th class="w-[12%] px-3 py-2.5 sm:px-4">Status</th>
                        <th class="w-[16%] px-3 py-2.5 text-center sm:px-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($applications as $application)
                        @php
                            $appStatus = strtolower((string) $application->status);
                            $statusClass = match ($appStatus) {
                                'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                'rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                default => 'bg-amber-50 text-amber-700 ring-amber-200',
                            };
                            $statusDot = match ($appStatus) {
                                'approved' => 'bg-emerald-500',
                                'rejected' => 'bg-rose-500',
                                default => 'bg-amber-500',
                            };
                            $statusLabel = match ($appStatus) {
                                'approved' => 'Approved',
                                'rejected' => 'Declined',
                                default => 'Waiting',
                            };
                            $submittedAt = \Carbon\Carbon::parse($application->created_at);
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/70">
                            <td class="px-3 py-3 sm:px-4">
                                <span class="block truncate font-mono text-[13px] font-medium tracking-wide text-black" title="{{ $application->employee_id }}">
                                    {{ $application->employee_id }}
                                </span>
                            </td>
                            <td class="px-3 py-3 sm:px-4">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-semibold text-slate-600">
                                        {{ strtoupper(substr($application->full_name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800" title="{{ $application->full_name }}">
                                            {{ $application->full_name }}
                                        </p>
                                        <p class="mt-0.5 truncate text-[11px] text-slate-400" title="{{ $application->email }}">
                                            {{ $application->email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 sm:px-4">
                                <span class="inline-flex max-w-full truncate rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                    {{ $application->employment_type }}
                                </span>
                            </td>
                            <td class="px-3 py-3 sm:px-4">
                                <p class="text-xs text-slate-600">{{ $submittedAt->format('M j, Y') }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ $submittedAt->format('g:i A') }}</p>
                            </td>
                            <td class="px-3 py-3 sm:px-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $statusClass }}">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $statusDot }}"></span>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-3 py-3 sm:px-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button
                                        type="button"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition hover:bg-slate-200 hover:text-slate-900 active:scale-95"
                                        data-tooltip="View application"
                                        aria-label="View application"
                                        onclick="viewApplication(this)"
                                        data-employee="{{ $application->employee_id }}"
                                        data-name="{{ $application->full_name }}"
                                        data-first="{{ $application->first_name }}"
                                        data-middle="{{ $application->middle_name }}"
                                        data-last="{{ $application->last_name }}"
                                        data-type="{{ $application->employment_type }}"
                                        data-email="{{ $application->email }}"
                                        data-contact="{{ $application->contact }}"
                                        data-status="{{ $statusLabel }}"
                                        data-submitted="{{ $submittedAt->format('M j, Y g:i A') }}"
                                        data-reviewed-by="{{ $application->reviewed_by_name }}"
                                        data-reason="{{ $application->rejection_reason }}"
                                    >
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                    </button>

                                    @if ($appStatus === 'pending')
                                        <button
                                            type="button"
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white transition hover:bg-emerald-700 active:scale-95"
                                            data-tooltip="Confirm faculty or staff"
                                            aria-label="Confirm faculty or staff"
                                            onclick="openApproveModal(this)"
                                            data-id="{{ $application->id }}"
                                            data-name="{{ $application->full_name }}"
                                            data-employee="{{ $application->employee_id }}"
                                            data-email="{{ $application->email }}"
                                            data-type="{{ $application->employment_type }}"
                                        >
                                            <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-rose-600 ring-1 ring-rose-200 transition hover:bg-rose-50 active:scale-95"
                                            data-tooltip="Decline this application"
                                            aria-label="Decline this application"
                                            onclick="openRejectModal(this)"
                                            data-id="{{ $application->id }}"
                                            data-name="{{ $application->full_name }}"
                                        >
                                            <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16">
                                <div class="flex flex-col items-center text-center">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <i data-lucide="user-check" class="h-5 w-5"></i>
                                    </div>
                                    <h3 class="mt-4 text-sm font-semibold text-slate-700">
                                        {{ request()->filled('search') ? 'No matching applications' : ($status === 'pending' ? 'No applications waiting' : 'No records in this view') }}
                                    </h3>
                                    <p class="mt-1.5 max-w-sm text-xs leading-5 text-slate-400">
                                        {{ $status === 'pending'
                                            ? 'When a reporter clicks Submit & wait for approval, their details appear here until you confirm they are faculty or staff.'
                                            : 'Switch tabs to review waiting, approved, or declined applications.' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($applications->hasPages())
            <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    Showing
                    <span class="font-semibold text-slate-700">{{ $applications->firstItem() }}</span>
                    to
                    <span class="font-semibold text-slate-700">{{ $applications->lastItem() }}</span>
                    of
                    <span class="font-semibold text-slate-700">{{ $applications->total() }}</span>
                    applications
                </p>
                <div>
                    {{ $applications->links() }}
                </div>
            </div>
        @endif
    </section>

    <div id="viewApplicationModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-[#0b1220]/70 p-4">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
            <div class="flex items-start justify-between px-6 pb-4 pt-6">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400">Application</p>
                    <h2 class="mt-1 text-lg font-semibold tracking-tight text-slate-900">Reporter details</h2>
                </div>
                <button type="button" onclick="closeViewApplicationModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <div id="applicationDetails" class="px-6 pb-2"></div>
            <div class="flex justify-end px-6 py-4">
                <button type="button" onclick="closeViewApplicationModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-950 hover:text-slate-600">Close</button>
            </div>
        </div>
    </div>

    <div id="approveModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-[#0b1220]/70 p-4">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
            <form id="approveForm" method="POST">
                @csrf
                <div class="px-6 pb-4 pt-6">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                        <i data-lucide="user-check" class="h-5 w-5"></i>
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-slate-900">Confirm this reporter</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Confirm that <span id="approveName" class="font-semibold text-slate-700"></span>
                        (<span id="approveEmployee" class="font-mono text-slate-700"></span>) is faculty or staff. They will then be added to the reporters list.
                    </p>
                    <p id="approveEmail" class="mt-2 text-xs text-slate-400"></p>
                    <label class="mt-5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Confirmed type</label>
                    <select
                        id="approveType"
                        name="type"
                        required
                        data-native-select="1"
                        class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none focus:border-slate-400"
                    >
                        <option value="Faculty">Faculty</option>
                        <option value="Staff">Staff</option>
                    </select>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <button type="button" onclick="closeApproveModal()" class="h-10 rounded-xl px-4 text-sm font-semibold text-slate-600 hover:bg-white">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700">
                        Confirm and add
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="rejectModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-[#0b1220]/70 p-4">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
            <form id="rejectForm" method="POST">
                @csrf
                <div class="px-6 pb-4 pt-6">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-700">
                        <i data-lucide="user-x" class="h-5 w-5"></i>
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-slate-900">Decline this application</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        <span id="rejectName" class="font-semibold text-slate-700"></span> will not be added to the reporters list.
                    </p>
                    <label class="mt-5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Reason (optional)</label>
                    <textarea
                        name="reason"
                        rows="3"
                        maxlength="500"
                        placeholder="They are not faculty or staff, or the details do not match."
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-slate-400"
                    ></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <button type="button" onclick="closeRejectModal()" class="h-10 rounded-xl px-4 text-sm font-semibold text-slate-600 hover:bg-white">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-rose-600 px-4 text-sm font-semibold text-white hover:bg-rose-700">
                        Decline application
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function viewApplication(button) {
            const modal = document.getElementById('viewApplicationModal');
            const details = document.getElementById('applicationDetails');
            if (!modal || !details) return;

            const name = button.dataset.name || '—';
            const employee = button.dataset.employee || '—';
            const first = button.dataset.first || '';
            const middle = button.dataset.middle || '';
            const last = button.dataset.last || '';
            const type = button.dataset.type || '';
            const email = button.dataset.email || '—';
            const contact = button.dataset.contact || '—';
            const status = button.dataset.status || '—';
            const submitted = button.dataset.submitted || '—';
            const reviewedBy = button.dataset.reviewedBy || '';
            const reason = button.dataset.reason || '';

            const initials = name
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part[0].toUpperCase())
                .join('') || '?';

            const typeChip = type
                ? `<span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">${escapeHtml(type)}</span>`
                : `<span class="text-xs text-slate-400">No type set</span>`;

            const nameParts = [first, middle, last].filter(Boolean).join(' ') || name;

            details.innerHTML = `
                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200/70">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100/80 text-sm font-semibold text-slate-950">${escapeHtml(initials)}</div>
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold text-slate-900">${escapeHtml(name)}</p>
                            <p class="mt-0.5 font-mono text-xs text-slate-500">${escapeHtml(employee)}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        ${typeChip}
                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200">${escapeHtml(status)}</span>
                    </div>
                </div>
                <dl class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-2xl ring-1 ring-slate-200/70">
                    <div class="flex items-start justify-between gap-4 px-4 py-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Full name</dt>
                        <dd class="min-w-0 break-words text-right text-sm font-medium text-slate-800">${escapeHtml(nameParts)}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 px-4 py-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</dt>
                        <dd class="min-w-0 break-all text-right text-sm font-medium text-slate-800">${escapeHtml(email)}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 px-4 py-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Contact</dt>
                        <dd class="text-right text-sm font-medium text-slate-800">${escapeHtml(contact)}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 px-4 py-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Submitted</dt>
                        <dd class="text-right text-sm font-medium text-slate-800">${escapeHtml(submitted)}</dd>
                    </div>
                    ${reviewedBy ? `
                    <div class="flex items-start justify-between gap-4 px-4 py-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Reviewed by</dt>
                        <dd class="text-right text-sm font-medium text-slate-800">${escapeHtml(reviewedBy)}</dd>
                    </div>` : ''}
                    ${reason ? `
                    <div class="flex items-start justify-between gap-4 px-4 py-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Decline reason</dt>
                        <dd class="min-w-0 break-words text-right text-sm font-medium text-rose-600">${escapeHtml(reason)}</dd>
                    </div>` : ''}
                </dl>
            `;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeViewApplicationModal() {
            const modal = document.getElementById('viewApplicationModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openApproveModal(button) {
            const modal = document.getElementById('approveModal');
            const form = document.getElementById('approveForm');
            form.action = `/maintenance/reporters/approvals/${button.dataset.id}/approve`;
            document.getElementById('approveName').textContent = button.dataset.name || '';
            document.getElementById('approveEmployee').textContent = button.dataset.employee || '';
            document.getElementById('approveEmail').textContent = button.dataset.email || '';
            document.getElementById('approveType').value = button.dataset.type || 'Faculty';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeApproveModal() {
            const modal = document.getElementById('approveModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openRejectModal(button) {
            const modal = document.getElementById('rejectModal');
            const form = document.getElementById('rejectForm');
            form.action = `/maintenance/reporters/approvals/${button.dataset.id}/reject`;
            document.getElementById('rejectName').textContent = button.dataset.name || '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeRejectModal() {
            const modal = document.getElementById('rejectModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('viewApplicationModal')?.addEventListener('click', function (event) {
            if (event.target === this) closeViewApplicationModal();
        });
        document.getElementById('approveModal')?.addEventListener('click', function (event) {
            if (event.target === this) closeApproveModal();
        });
        document.getElementById('rejectModal')?.addEventListener('click', function (event) {
            if (event.target === this) closeRejectModal();
        });
    </script>

@endsection
