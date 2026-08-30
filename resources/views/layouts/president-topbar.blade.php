<div class="topbar">
    <div class="topbar-left">
        <button onclick="toggleSidebar()" class="mobile-sidebar-btn" type="button" aria-label="Open sidebar">
            <i data-lucide="menu"></i>
        </button>

        @php
            $moduleHeading = match (true) {
                request()->is('president/dashboard') => ['Dashboard', 'Overview of RIS decisions and workload.'],
                request()->is('president/approvals/history*') => ['Approval History', 'Past presidential decisions on RIS documents.'],
                request()->is('president/approvals*') => ['RIS Approvals', 'Review, sign, and notify Admin when ready.'],
                request()->is('president/reports/monthly-summary*') => ['Reports & Summary', 'Monthly decision trends and totals.'],
                request()->is('president/reports*') => ['Decision History', 'Approved and rejected RIS decisions.'],
                request()->is('president/notifications*') => ['Alerts', 'Recent activity requiring your attention.'],
                request()->is('president/profile*') => ['Profile', 'Account settings for the President panel.'],
                default => [View::yieldContent('title', 'PRISM'), 'President Panel'],
            };
        @endphp

        <div class="min-w-0">
            <h1 class="truncate text-[20px] font-semibold leading-tight tracking-tight text-slate-900 sm:text-[22px]">
                {{ $moduleHeading[0] }}
            </h1>
            <p class="mt-0.5 truncate text-xs text-slate-500 sm:text-sm">
                {{ $moduleHeading[1] }}
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <div class="dashboard-toolbar-search hidden md:flex">
            <i data-lucide="search" class="dashboard-toolbar-search-icon"></i>
            <input
                type="search"
                id="dashboard-search"
                placeholder="Search..."
                autocomplete="off"
            />
        </div>

        <a
            href="javascript:void(0)"
            onclick="openMessagingModal()"
            class="dashboard-icon-action"
            aria-label="PRISM messages"
            data-tooltip="Messages"
        >
            <i data-lucide="messages-square" class="h-[18px] w-[18px]"></i>
            <span
                id="topbarMessageBadge"
                class="hidden absolute -right-1 -top-1 min-w-[18px] h-[18px]
                    items-center justify-center rounded-full
                    bg-rose-500 px-1 text-[10px] font-bold text-white
                    border-2 border-white"
            >0</span>
        </a>

        <div class="relative">
            <button
                type="button"
                onclick="toggleNotifications()"
                class="relative flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-950"
                aria-label="Notifications"
                data-tooltip="Notifications"
            >
                <i data-lucide="bell" class="h-5 w-5"></i>

                @if (($attentionTotal ?? 0) > 0)
                    <span
                        class="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full border-2 border-white bg-rose-500 px-1 text-[10px] font-bold leading-none text-white"
                    >
                        {{ $attentionTotal > 99 ? '99+' : $attentionTotal }}
                    </span>
                @elseif (($headerUnreadCount ?? 0) > 0)
                    <span
                        class="absolute right-[6px] top-[6px] h-2 w-2 rounded-full border-2 border-white bg-rose-500"
                    ></span>
                @endif
            </button>

            <div
                id="notificationDropdown"
                class="absolute right-0 top-[calc(100%+2px)] z-50 hidden
                    w-[340px]
                    overflow-hidden
                    rounded-xl
                    border border-black/5
                    bg-white
                    shadow-[0_16px_45px_rgba(0,0,0,0.13)]"
            >
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <div class="min-w-0">
                        <h3 class="text-[13px] font-semibold tracking-tight text-slate-950">
                            Notifications
                        </h3>
                        <p class="mt-0.5 text-[11px] text-slate-500">
                            Recent activity requiring your attention
                        </p>
                    </div>
                    <span
                        class="ml-3 shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[10px] font-medium text-slate-600"
                    >
                        {{ $headerUnreadCount ?? 0 }} new
                    </span>
                </div>

                <div class="max-h-[290px] overflow-y-auto">
                    @if (($attentionTotal ?? 0) > 0)
                        <button
                            type="button"
                            onclick="typeof openPresidentDailyReminder === 'function' && openPresidentDailyReminder()"
                            class="flex w-full items-start gap-2.5 border-b border-slate-100 bg-slate-50/80 px-4 py-2.5 text-left transition hover:bg-rose-50/60"
                        >
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                                <i data-lucide="triangle-alert" class="h-4 w-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="truncate text-[12px] font-semibold text-slate-900">
                                        Attention needed today
                                    </h4>
                                    <span class="shrink-0 rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                        {{ $attentionTotal }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-[11px] leading-4 text-slate-500">
                                    Open the daily summary of RIS awaiting decision or Admin notify.
                                </p>
                            </div>
                        </button>
                    @endif

                    @forelse (($headerNotifications ?? collect()) as $notification)
                        @php
                            $icon = match ($notification->notification_type) {
                                'ris_forwarded' => 'clipboard-check',
                                'ris_approved', 'decision_approved' => 'circle-check-big',
                                'ris_rejected', 'decision_rejected' => 'x-circle',
                                'admin_notified' => 'send',
                                default => 'bell',
                            };

                            $iconStyle = match ($notification->notification_category) {
                                'Approvals', 'approval', 'workflow' => 'bg-blue-50 text-blue-600',
                                'Rejections', 'rejection' => 'bg-slate-100 text-slate-600',
                                'Reports' => 'bg-slate-100 text-slate-600',
                                default => 'bg-slate-100 text-slate-500',
                            };
                        @endphp

                        <a
                            href="/president/notifications/{{ $notification->notification_id }}/open"
                            class="flex w-full items-start gap-2.5 border-b border-slate-100 px-4 py-2.5 text-left transition last:border-b-0 hover:bg-slate-50 {{ empty($notification->is_read) ? 'bg-slate-50/60' : '' }}"
                        >
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $iconStyle }}">
                                <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5"></i>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <h4 class="truncate text-[12px] font-semibold leading-4 text-slate-900">
                                        {{ $notification->notification_title }}
                                    </h4>
                                    @if (empty($notification->is_read))
                                        <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500"></span>
                                    @endif
                                </div>
                                <p class="mt-0.5 line-clamp-2 text-[11px] leading-4 text-slate-500">
                                    {{ $notification->notification_message }}
                                </p>
                                <p class="mt-1 text-[10px] leading-3 text-slate-400">
                                    {{ \Carbon\Carbon::parse($notification->notification_created_at)->diffForHumans() }}
                                </p>
                            </div>
                        </a>
                    @empty
                        <div class="flex min-h-[170px] flex-col items-center justify-center px-5 text-center">
                            @if (($attentionTotal ?? 0) > 0)
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-50 text-rose-500">
                                    <i data-lucide="triangle-alert" class="h-4 w-4"></i>
                                </div>
                                <h4 class="mt-2 text-[12px] font-medium text-slate-700">No new notifications</h4>
                                <p class="mt-1 text-[10px] text-slate-500">Open Attention needed today above for RIS items.</p>
                            @else
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <i data-lucide="bell-off" class="h-4 w-4"></i>
                                </div>
                                <h4 class="mt-2 text-[12px] font-medium text-slate-700">No notifications</h4>
                                <p class="mt-1 text-[10px] text-slate-400">New system activity will appear here.</p>
                            @endif
                        </div>
                    @endforelse
                </div>

                <div class="border-t border-slate-100 px-3 py-1.5">
                    <a
                        href="/president/notifications"
                        class="block w-full rounded-lg px-3 py-2 text-center text-[11px] font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        View all notifications
                    </a>
                </div>
            </div>
        </div>

        <div class="relative">
            <button
                type="button"
                onclick="toggleProfileDropdown()"
                class="flex items-center gap-3 rounded-xl px-2 py-1.5 text-left transition hover:bg-slate-100"
            >
                @include('partials.user-avatar', ['avatarUser' => Auth::user(), 'avatarSize' => 'sm'])

                {{-- Do not use Tailwind "hidden sm:block" here.
                     global CSS may force .hidden { display:none !important }
                     which permanently hides the name/role/chevron. --}}
                <div class="pm-topbar-profile-meta min-w-0">
                    <p class="max-w-[150px] truncate text-sm font-medium text-slate-900">
                        {{ Auth::user()->user_full_name }}
                    </p>
                    <p class="mt-0.5 max-w-[150px] truncate text-xs text-slate-500">President</p>
                </div>

                <i
                    data-lucide="chevron-down"
                    class="pm-topbar-profile-chevron h-4 w-4 shrink-0 text-slate-400"
                ></i>
            </button>

            <div
                id="profileDropdown"
                class="absolute right-0 top-[calc(100%+10px)] z-50 hidden w-[260px] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_20px_60px_rgba(0,0,0,0.14)]"
            >
                <div class="border-b border-slate-100 px-4 py-4">
                    <div class="flex items-center gap-3">
                        @include('partials.user-avatar', ['avatarUser' => Auth::user(), 'avatarSize' => 'md'])
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-950">{{ Auth::user()->user_full_name }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ Auth::user()->user_email_address }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-2">
                    <a
                        href="{{ url('/president/profile') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        <i data-lucide="user-cog" class="h-4 w-4 text-slate-400"></i>
                        Profile settings
                    </a>
                </div>

                <div class="border-t border-slate-100 p-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-rose-50 hover:text-rose-600"
                        >
                            <i data-lucide="log-out" class="h-4 w-4"></i>
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary: #2563EB;
        --primary-dark: #1D4ED8;
        --bg: #F8FAFC;
        --card: #FFFFFF;
        --text: #0F172A;
        --muted: #64748B;
        --border: #E5E7EB;
    }

    .topbar {
        height: 82px;
        background: #ffffff;
        border-bottom: none;
        box-shadow: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 16px;
        position: sticky;
        top: 0;
        z-index: 10;
        gap: 12px;
    }

    @media (min-width: 640px) {
        .topbar {
            padding: 0 24px;
        }
    }

    @media (min-width: 1024px) {
        .topbar {
            padding: 0 32px;
        }
    }

    .topbar-left {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
        flex: 1;
    }

    .dashboard-toolbar-search {
        width: 220px;
        height: 42px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 14px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        color: #64748b;
    }

    .dashboard-toolbar-search-icon {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        color: #94a3b8;
        stroke: currentColor;
    }

    .dashboard-toolbar-search input {
        min-width: 0;
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-size: 14px;
        color: #0f172a;
    }

    .dashboard-toolbar-search input::placeholder {
        color: #94a3b8;
    }

    .dashboard-icon-action {
        position: relative;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border-radius: 999px;
        color: #64748b;
        text-decoration: none;
        transition: background 0.2s ease, color 0.2s ease;
    }

    .dashboard-icon-action:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .dashboard-icon-action svg {
        width: 18px;
        height: 18px;
        stroke: currentColor;
    }

    /* Match accounting topbar: name + chevron visible on desktop (bypass .hidden overrides) */
    .pm-topbar-profile-meta,
    .pm-topbar-profile-chevron,
    i.pm-topbar-profile-chevron,
    svg.pm-topbar-profile-chevron {
        display: none !important;
    }

    @media (min-width: 640px) {
        .pm-topbar-profile-meta {
            display: block !important;
        }

        .pm-topbar-profile-chevron,
        i.pm-topbar-profile-chevron,
        svg.pm-topbar-profile-chevron {
            display: block !important;
        }
    }

    .mobile-sidebar-btn {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        border: none;
        background: #F8FAFC;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: .25s;
        flex-shrink: 0;
    }

    .mobile-sidebar-btn:hover {
        background: var(--primary);
        transform: translateY(-2px);
    }

    @media (max-width: 1280px) {
        .mobile-sidebar-btn {
            display: flex;
        }
    }

    @media (max-width: 640px) {
        .topbar {
            height: 72px;
        }
    }
</style>

<script>
    function toggleNotifications() {
        const dropdown = document.getElementById('notificationDropdown');
        const profile = document.getElementById('profileDropdown');
        if (profile) profile.classList.add('hidden');
        if (dropdown) dropdown.classList.toggle('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function toggleProfileDropdown() {
        const dropdown = document.getElementById('profileDropdown');
        const notif = document.getElementById('notificationDropdown');
        if (notif) notif.classList.add('hidden');
        if (dropdown) dropdown.classList.toggle('hidden');
        if (window.lucide) lucide.createIcons();
    }

    window.addEventListener('click', function (e) {
        const notif = document.getElementById('notificationDropdown');
        const profile = document.getElementById('profileDropdown');

        if (
            notif &&
            !e.target.closest('#notificationDropdown') &&
            !e.target.closest('[onclick="toggleNotifications()"]')
        ) {
            notif.classList.add('hidden');
        }

        if (
            profile &&
            !e.target.closest('#profileDropdown') &&
            !e.target.closest('[onclick="toggleProfileDropdown()"]')
        ) {
            profile.classList.add('hidden');
        }
    });
</script>
