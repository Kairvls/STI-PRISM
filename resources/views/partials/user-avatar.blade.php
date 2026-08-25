@php
    $avatarUser = $avatarUser ?? auth()->user();
    $avatarUrl = $avatarUser?->profilePictureUrl();
    $avatarInitial = $avatarUser?->profileInitial() ?? 'U';
    $avatarSize = $avatarSize ?? 'md';
    $sizeClass = match ($avatarSize) {
        'lg' => 'h-20 w-20 text-2xl',
        'sm' => 'h-9 w-9 text-sm',
        'xs' => 'h-8 w-8 text-xs',
        default => 'h-10 w-10 text-sm',
    };
@endphp
@if ($avatarUrl)
    <img
        src="{{ $avatarUrl }}"
        alt="{{ $avatarUser->user_full_name ?? 'Profile' }}"
        class="{{ $sizeClass }} shrink-0 rounded-full object-cover bg-slate-200"
    >
@else
    <div class="{{ $sizeClass }} flex shrink-0 items-center justify-center rounded-full bg-slate-900 font-medium text-white">
        {{ $avatarInitial }}
    </div>
@endif
