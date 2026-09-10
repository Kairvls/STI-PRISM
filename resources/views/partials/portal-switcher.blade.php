@php
    use App\Support\RoleAccess;

    $portalUser = $portalUser ?? Auth::user();
    $portalOptions = RoleAccess::availablePortals($portalUser);
    $showPortalSwitcher = count($portalOptions) > 1;
    $currentPortalKey = RoleAccess::currentPortalKey();
    $currentPortalLabel = RoleAccess::currentPortalLabel($portalUser);
@endphp

@if($showPortalSwitcher)
<div class="relative" id="portalSwitcherRoot" data-portal-switcher>
    <button
        type="button"
        id="portalSwitcherBtn"
        onclick="togglePortalSwitcher(event)"
        class="flex h-10 max-w-[190px] items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-left transition hover:bg-slate-50"
        aria-haspopup="true"
        aria-expanded="false"
        title="Switch portal"
    >
        <i data-lucide="layout-grid" class="h-4 w-4 shrink-0 text-slate-500"></i>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-[10px] font-semibold uppercase tracking-wide text-slate-400">Portal</span>
            <span class="block truncate text-xs font-semibold text-slate-800">{{ $currentPortalLabel }}</span>
        </span>
        <i data-lucide="chevron-down" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
    </button>

    <div
        id="portalSwitcherMenu"
        class="absolute right-0 top-[calc(100%+8px)] z-[60] hidden w-[240px] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_20px_60px_rgba(0,0,0,0.14)]"
    >
        <div class="border-b border-slate-100 px-4 py-3">
            <p class="text-xs font-semibold text-slate-900">Your assigned portals</p>
            <p class="mt-0.5 text-[11px] text-slate-500">Open any role the admin assigned to you.</p>
        </div>
        <div class="p-2">
            @foreach($portalOptions as $portal)
                @php $isCurrent = $currentPortalKey === $portal['key']; @endphp
                <a
                    href="{{ url($portal['path']) }}"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ $isCurrent ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950' }}"
                >
                    <i data-lucide="{{ $isCurrent ? 'check' : 'arrow-up-right' }}" class="h-4 w-4 shrink-0 {{ $isCurrent ? 'text-white' : 'text-slate-400' }}"></i>
                    <span class="min-w-0">
                        <span class="block truncate font-semibold">{{ $portal['label'] }}</span>
                        @if($isCurrent)
                            <span class="block text-[11px] opacity-80">Current portal</span>
                        @else
                            <span class="block text-[11px] text-slate-500">Open {{ $portal['label'] }} system</span>
                        @endif
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>

@once
<script>
    window.togglePortalSwitcher = function (event) {
        if (event) event.stopPropagation();
        var menu = document.getElementById('portalSwitcherMenu');
        var btn = document.getElementById('portalSwitcherBtn');
        if (!menu) return;

        var profile = document.getElementById('profileDropdown');
        if (profile) profile.classList.add('hidden');
        var notif = document.getElementById('notificationDropdown');
        if (notif) notif.classList.add('hidden');

        menu.classList.toggle('hidden');
        if (btn) btn.setAttribute('aria-expanded', menu.classList.contains('hidden') ? 'false' : 'true');
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    };

    function closePortalSwitcherMenu() {
        var menu = document.getElementById('portalSwitcherMenu');
        var btn = document.getElementById('portalSwitcherBtn');
        if (menu) menu.classList.add('hidden');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    }

    document.addEventListener('click', function (e) {
        var root = document.getElementById('portalSwitcherRoot');
        var menu = document.getElementById('portalSwitcherMenu');
        if (!root || !menu || root.contains(e.target)) return;
        closePortalSwitcherMenu();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        closePortalSwitcherMenu();
    });

    document.addEventListener('DOMContentLoaded', function () {
        ['toggleProfileDropdown', 'toggleNotifications'].forEach(function (name) {
            var original = window[name];
            if (typeof original !== 'function' || original.__portalPatched) return;
            var wrapped = function () {
                closePortalSwitcherMenu();
                return original.apply(this, arguments);
            };
            wrapped.__portalPatched = true;
            window[name] = wrapped;
        });
    });
</script>
@endonce
@endif
