{{--
  Account settings sub-nav.
  Expects: $profileUrl, $securityUrl, $active = 'profile'|'security'
--}}
@php
    $active = $active ?? 'profile';
@endphp
<div class="inline-flex max-w-full items-center overflow-x-auto rounded-xl bg-slate-200/70 p-1" role="tablist" aria-label="Account settings">
    <a
        href="{{ $profileUrl }}"
        role="tab"
        aria-selected="{{ $active === 'profile' ? 'true' : 'false' }}"
        class="flex h-9 shrink-0 items-center rounded-lg px-4 text-xs font-semibold transition
            {{ $active === 'profile' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}"
    >
        Profile
    </a>
    <a
        href="{{ $securityUrl }}"
        role="tab"
        aria-selected="{{ $active === 'security' ? 'true' : 'false' }}"
        class="flex h-9 shrink-0 items-center rounded-lg px-4 text-xs font-semibold transition
            {{ $active === 'security' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}"
    >
        Security
    </a>
</div>
