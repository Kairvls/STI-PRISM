<div class="topbar">
    <div class="topbar-left">
        <button onclick="toggleSidebar()" class="mobile-sidebar-btn" type="button" aria-label="Open navigation menu" aria-controls="sidebar">
            <i data-lucide="menu" aria-hidden="true"></i>
        </button>

        @php
            $moduleHeading = match (true) {
                request()->is('receiving/dashboard') => ['Dashboard', 'Overview of receiving activity and deliveries.'],
                request()->is('receiving/reports*') => ['Receiving Reports', 'Review and process receiving reports.'],
                request()->is('receiving/delivered-items*') => ['Delivered Items', 'Track items already received.'],
                request()->is('receiving/supplier-records*') => ['Supplier Records', 'Supplier delivery and receiving records.'],
                request()->is('receiving/history*') => ['History', 'Past receiving activity and completed records.'],
                request()->is('receiving/logs*') => ['Logs', 'Receiving activity logs.'],
                request()->is('receiving/notifications*') => ['Notifications', 'Recent activity requiring your attention.'],
                request()->is('receiving/profile*') => ['Profile Settings', 'Update your Receiving Officer account details.'],
                request()->is('receiving/security*') => ['Security Settings', 'Manage your password and account security.'],
                default => [View::yieldContent('title', 'PRISM'), 'Receiving Officer'],
            };

            $unreadCount = 0;
            $recentNotes = collect();
            $attentionTotal = (int) ($attentionTotal ?? 0);
            try {
                $unreadCount = \DB::table('notifications_table')
                    ->where(function ($q) {
                        $q->where('notification_user_id', auth()->id())
                            ->orWhere('notification_target_role', 'Receiving Officer');
                    })
                    ->count();

                $recentNotes = \DB::table('notifications_table')
                    ->where(function ($q) {
                        $q->where('notification_user_id', auth()->id())
                            ->orWhere('notification_target_role', 'Receiving Officer');
                    })
                    ->orderByDesc('notification_created_at')
                    ->limit(8)
                    ->get();
            } catch (\Throwable $e) {
                $unreadCount = 0;
                $recentNotes = collect();
            }

            $topbarUser = Auth::user();
            $topbarInitial = strtoupper(substr($topbarUser->user_full_name ?? 'U', 0, 1));
            $topbarPictureUrl = $topbarUser->profile_picture_url ?? null;
        @endphp

        <div class="min-w-0">
            <h1 class="truncate text-[22px] font-semibold leading-tight tracking-tight text-slate-900">
                {{ $moduleHeading[0] }}
            </h1>
            <p class="mt-0.5 truncate text-sm text-slate-500">
                {{ $moduleHeading[1] }}
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2">
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
            >
                0
            </span>
        </a>

        <div class="relative">
            <button
                type="button"
                data-topbar-toggle="notifications"
                onclick="event.stopPropagation(); toggleNotifications()"
                class="relative flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-950"
                aria-label="Notifications"
                data-tooltip="Notifications"
            >
                <i data-lucide="bell" class="h-5 w-5"></i>

                @if ($attentionTotal > 0)
                    <span
                        class="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full border-2 border-white bg-rose-500 px-1 text-[10px] font-bold leading-none text-white"
                    >
                        {{ $attentionTotal > 99 ? '99+' : $attentionTotal }}
                    </span>
                @elseif ($unreadCount > 0)
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
                <div
                    class="flex items-center justify-between
                        border-b border-slate-100
                        px-4 py-3"
                >
                    <div class="min-w-0">
                        <h3 class="text-[13px] font-semibold tracking-tight text-slate-950">
                            Notifications
                        </h3>
                        <p class="mt-0.5 text-[11px] text-slate-500">
                            Recent activity requiring your attention
                        </p>
                    </div>

                    <span
                        class="ml-3 shrink-0 rounded-full
                            bg-slate-100
                            px-2 py-1
                            text-[10px] font-medium
                            text-slate-600"
                    >
                        {{ $unreadCount }} new
                    </span>
                </div>

                <div class="max-h-[290px] overflow-y-auto">
                    @if ($attentionTotal > 0)
                        <button
                            type="button"
                            onclick="typeof openReceivingDailyReminder === 'function' && openReceivingDailyReminder()"
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
                                    Open the daily summary of receiving items that need action.
                                </p>
                            </div>
                        </button>
                    @endif

                    @forelse ($recentNotes as $note)
                        @php
                            $icon = match ($note->notification_type ?? null) {
                                'receiving_report', 'rr' => 'package-check',
                                'delivery' => 'truck',
                                'ris' => 'package-open',
                                default => 'bell',
                            };
                            $iconStyle = match ($note->notification_category ?? null) {
                                'Procurement' => 'bg-amber-50 text-amber-600',
                                'Workflow' => 'bg-slate-100 text-slate-600',
                                'Reports' => 'bg-rose-50 text-rose-600',
                                default => 'bg-slate-100 text-slate-500',
                            };
                        @endphp
                        <a
                            href="{{ $note->notification_url ?: url('/receiving/notifications') }}"
                            class="flex items-start gap-2.5 border-b border-slate-100 px-4 py-2.5 transition hover:bg-slate-50"
                        >
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $iconStyle }}">
                                <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="truncate text-[12px] font-semibold text-slate-900">
                                    {{ $note->notification_title ?: 'Notification' }}
                                </h4>
                                <p class="mt-0.5 line-clamp-2 text-[11px] leading-4 text-slate-500">
                                    {{ $note->notification_message ?: 'System update' }}
                                </p>
                                @if(!empty($note->notification_created_at))
                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ \Carbon\Carbon::parse($note->notification_created_at)->diffForHumans() }}
                                    </p>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="flex min-h-[180px] flex-col items-center justify-center px-6 text-center">
                            @if ($attentionTotal > 0)
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-500">
                                    <i data-lucide="triangle-alert" class="h-4 w-4"></i>
                                </div>
                                <h4 class="mt-3 text-sm font-medium text-slate-700">No new notifications</h4>
                                <p class="mt-1 text-xs text-slate-500">Open Attention needed today above for pending items.</p>
                            @else
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <i data-lucide="bell-off" class="h-4 w-4"></i>
                                </div>
                                <h4 class="mt-3 text-sm font-medium text-slate-700">No notifications</h4>
                                <p class="mt-1 text-xs text-slate-400">New system activity will appear here.</p>
                            @endif
                        </div>
                    @endforelse
                </div>

                <div class="border-t border-slate-100 px-3 py-1.5">
                    <a
                        href="{{ url('/receiving/notifications') }}"
                        class="block w-full
                            rounded-lg
                            px-3 py-2
                            text-center
                            text-[11px]
                            font-medium
                            text-slate-600
                            transition
                            hover:bg-slate-100
                            hover:text-slate-950"
                    >
                        View all notifications
                    </a>
                </div>
            </div>
        </div>

        @include('partials.portal-switcher')

        <div class="relative">
            <button
                type="button"
                data-topbar-toggle="profile"
                onclick="event.stopPropagation(); toggleProfileDropdown()"
                class="flex items-center gap-3 rounded-xl px-2 py-1.5 text-left transition hover:bg-slate-100"
            >
                <div
                    data-user-avatar
                    class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-900 text-sm font-medium text-white"
                >
                    @if ($topbarPictureUrl)
                        <img
                            src="{{ $topbarPictureUrl }}?v={{ time() }}"
                            alt="{{ $topbarUser->user_full_name }}"
                            class="h-full w-full object-cover"
                        >
                    @else
                        {{ $topbarInitial }}
                    @endif
                </div>

                <div class="hidden min-w-0 sm:block">
                    <p class="max-w-[150px] truncate text-sm font-medium text-slate-900">
                        {{ $topbarUser->user_full_name }}
                    </p>
                    <p class="mt-0.5 max-w-[150px] truncate text-xs text-slate-500">
                        {{ \App\Support\RoleAccess::currentPortalLabel($topbarUser) }}
                    </p>
                </div>

                <i
                    data-lucide="chevron-down"
                    class="hidden h-4 w-4 shrink-0 text-slate-400 sm:block"
                ></i>
            </button>

            <div
                id="profileDropdown"
                class="absolute right-0 top-[calc(100%+10px)] z-50 hidden w-[260px] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_20px_60px_rgba(0,0,0,0.14)]"
            >
                <div class="border-b border-slate-100 px-4 py-4">
                    <div class="flex items-center gap-3">
                        <div
                            data-user-avatar
                            class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-900 text-sm font-medium text-white"
                        >
                            @if ($topbarPictureUrl)
                                <img
                                    src="{{ $topbarPictureUrl }}?v={{ time() }}"
                                    alt="{{ $topbarUser->user_full_name }}"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                {{ $topbarInitial }}
                            @endif
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-950">
                                {{ $topbarUser->user_full_name }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                {{ $topbarUser->user_email_address }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-2">
                    <a
                        href="{{ route('receiving.profile') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        <i data-lucide="user-cog" class="h-4 w-4 text-slate-400"></i>
                        Profile settings
                    </a>
                    <a
                        href="{{ route('receiving.security') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        <i data-lucide="shield" class="h-4 w-4 text-slate-400"></i>
                        Security settings
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
    .topbar{
        height:80px;
        background:#ffffff;
        border-bottom:none;
        display:flex;
        align-items:center;
        justify-content:space-between;
        padding:0 28px;
        position:sticky;
        top:0;
        z-index:40;
    }

    .topbar-left{
        display:flex;
        align-items:center;
        gap:16px;
        min-width:0;
        flex:1;
    }

    .mobile-sidebar-btn{
        display:none;
        border:none;
        background:#F8FAFC;
        width:42px;
        height:42px;
        border-radius:14px;
        align-items:center;
        justify-content:center;
        cursor:pointer;
    }

    @media (max-width: 1024px){
        .mobile-sidebar-btn{
            display:flex;
        }
    }

    .dashboard-icon-action{
        position:relative;
        display:flex;
        height:40px;
        width:40px;
        align-items:center;
        justify-content:center;
        border-radius:9999px;
        color:#64748B;
        transition:.2s;
    }

    .dashboard-icon-action:hover{
        background:#F1F5F9;
        color:#0F172A;
    }
</style>

<script>
    function toggleNotifications() {
        const dropdown = document.getElementById("notificationDropdown");
        const profile = document.getElementById("profileDropdown");
        if (profile) profile.classList.add("hidden");
        if (dropdown) dropdown.classList.toggle("hidden");
    }

    function toggleProfileDropdown() {
        const dropdown = document.getElementById("profileDropdown");
        const notif = document.getElementById("notificationDropdown");
        if (notif) notif.classList.add("hidden");
        if (dropdown) dropdown.classList.toggle("hidden");
    }

    window.addEventListener("click", function (e) {
        const notif = document.getElementById("notificationDropdown");
        const profile = document.getElementById("profileDropdown");

        if (
            notif &&
            !e.target.closest("#notificationDropdown") &&
            !e.target.closest('[data-topbar-toggle="notifications"]')
        ) {
            notif.classList.add("hidden");
        }

        if (
            profile &&
            !e.target.closest("#profileDropdown") &&
            !e.target.closest('[data-topbar-toggle="profile"]')
        ) {
            profile.classList.add("hidden");
        }
    });
</script>
