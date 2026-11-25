@php
    $actions = array_values($actions ?? []);
    $keepOutClasses = ['pin-btn', 'review-btn', 'approve-btn', 'revise-btn', 'cost-summary-btn'];
    $pinActions = array_values(array_filter(
        $actions,
        fn ($action) => in_array(($action['class'] ?? ''), $keepOutClasses, true)
    ));
    $menuActions = array_values(array_filter(
        $actions,
        fn ($action) => ! in_array(($action['class'] ?? ''), $keepOutClasses, true)
    ));
    $asMenu = count($actions) >= 4 && count($menuActions) > 0;
@endphp
<div class="row-actions">
    @foreach ($asMenu ? $pinActions : $actions as $action)
        <button
            type="button"
            class="icon-btn {{ $action['class'] ?? '' }}"
            data-tip="{{ $action['label'] }}"
            aria-label="{{ $action['label'] }}"
            @if (!empty($action['data_summary'])) data-summary="{{ $action['data_summary'] }}" @endif
            onclick="event.stopPropagation(); {!! $action['onclick'] !!}"
        >
            <i data-lucide="{{ $action['icon'] }}" class="h-4 w-4"></i>
        </button>
    @endforeach
    @if ($asMenu)
        <div class="row-action-menu">
            <button
                type="button"
                class="icon-btn"
                data-tip="Actions"
                aria-label="Actions"
                aria-haspopup="true"
                onclick="event.stopPropagation(); toggleRowActionMenu(this)"
            >
                <i data-lucide="more-horizontal" class="h-4 w-4"></i>
            </button>
            <div class="row-action-menu-panel" hidden>
                @foreach ($menuActions as $action)
                    <button
                        type="button"
                        class="row-action-menu-item {{ $action['class'] ?? '' }}"
                        aria-label="{{ $action['label'] }}"
                        onclick="event.stopPropagation(); closeRowActionMenus(); {!! $action['onclick'] !!}"
                    >
                        <i data-lucide="{{ $action['icon'] }}" class="h-4 w-4"></i>
                        <span>{{ $action['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
