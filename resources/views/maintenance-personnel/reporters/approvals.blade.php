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
            class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 xl:flex-row xl:items-center"
        >
            <div class="flex shrink-0 items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <i data-lucide="user-check" class="h-4 w-4"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Reporter applications</h2>
                    <p class="mt-0.5 text-xs text-slate-400">
                        {{ $applications->total() }}
                        {{ $applications->total() === 1 ? 'record' : 'records' }}
                        in this view
                    </p>
                </div>
            </div>

            <div class="min-w-0 flex-1 xl:ml-4">
                <div
                    class="flex items-center gap-1 overflow-x-auto whitespace-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <a
                        href="{{ url('/maintenance/reporters/approvals?status=pending') }}"
                        class="shrink-0 rounded-lg px-3 py-1.5
                            text-[13px] transition
                            {{
                                $status === 'pending'
                                    ? 'bg-slate-100/80 font-medium text-slate-900 shadow-sm'
                                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                            }}"
                    >
                        Waiting
                    </a>

                    <a
                        href="{{ url('/maintenance/reporters/approvals?status=approved') }}"
                        class="shrink-0 rounded-lg px-3 py-1.5
                            text-[13px] transition
                            {{
                                $status === 'approved'
                                    ? 'bg-slate-100/80 font-medium text-slate-900 shadow-sm'
                                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                            }}"
                    >
                        Approved
                    </a>

                    <a
                        href="{{ url('/maintenance/reporters/approvals?status=rejected') }}"
                        class="shrink-0 rounded-lg px-3 py-1.5
                            text-[13px] transition
                            {{
                                $status === 'rejected'
                                    ? 'bg-slate-100/80 font-medium text-slate-900 shadow-sm'
                                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                            }}"
                    >
                        Declined
                    </a>
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
            <table class="w-full min-w-[980px] text-left">
                <thead class="border-b border-slate-200 bg-slate-50/70">
                    <tr class="text-[12px] font-semibold uppercase tracking-[0.08em] text-black">
                        <th class="px-5 py-3">Employee ID</th>
                        <th class="px-5 py-3">Applicant</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Contact</th>
                        <th class="px-5 py-3">Submitted</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="w-28 px-5 py-3 text-center">Actions</th>
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
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <span class="font-mono text-sm font-medium tracking-wider text-black">
                                    {{ $application->employee_id }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">
                                        {{ strtoupper(substr($application->full_name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="max-w-[220px] truncate text-sm font-semibold text-slate-800">
                                            {{ $application->full_name }}
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">
                                            Applied to report
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                    {{ $application->employment_type }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="mail" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                    <span class="max-w-[240px] truncate text-xs text-slate-600">
                                        {{ $application->email }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="phone" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                    <span class="whitespace-nowrap text-xs text-slate-600">
                                        {{ $application->contact }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-xs text-slate-600">
                                    {{ \Carbon\Carbon::parse($application->created_at)->format('M j, Y') }}
                                </p>
                                <p class="mt-0.5 text-[11px] text-slate-400">
                                    {{ \Carbon\Carbon::parse($application->created_at)->format('g:i A') }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $statusClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                    {{ $statusLabel }}
                                </span>
                                @if ($application->reviewed_by_name)
                                    <p class="mt-1 text-[11px] text-slate-400">
                                        by {{ $application->reviewed_by_name }}
                                    </p>
                                @endif
                                @if ($appStatus === 'rejected' && $application->rejection_reason)
                                    <p class="mt-1 max-w-[180px] text-[11px] text-rose-500">
                                        {{ $application->rejection_reason }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($appStatus === 'pending')
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                            data-tooltip="Confirm faculty or staff"
                                            onclick="openApproveModal(this)"
                                            data-id="{{ $application->id }}"
                                            data-name="{{ $application->full_name }}"
                                            data-employee="{{ $application->employee_id }}"
                                            data-email="{{ $application->email }}"
                                            data-type="{{ $application->employment_type }}"
                                        >
                                            <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                            Confirm
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg  bg-white px-3 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"
                                            data-tooltip="Decline this application"
                                            onclick="openRejectModal(this)"
                                            data-id="{{ $application->id }}"
                                            data-name="{{ $application->full_name }}"
                                        >
                                            
                                            Decline
                                        </button>
                                    </div>
                                @else
                                    <p class="text-center text-xs text-slate-400">Reviewed</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16">
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

        document.getElementById('approveModal')?.addEventListener('click', function (event) {
            if (event.target === this) closeApproveModal();
        });
        document.getElementById('rejectModal')?.addEventListener('click', function (event) {
            if (event.target === this) closeRejectModal();
        });
    </script>

@endsection
