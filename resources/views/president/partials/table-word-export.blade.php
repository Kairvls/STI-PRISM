@php
    $target = $target ?? '';
    $filename = $filename ?? 'president-table';
    $label = $label ?? 'Print as Word';
@endphp

<div class="pm-table-word-bar">
    <button
        type="button"
        class="pm-table-word-btn"
        data-word-target="{{ $target }}"
        data-word-filename="{{ $filename }}"
        data-tip="Download this table as a Word file"
        onclick="exportPresidentTableToWord(this)"
    >
        <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
        <span>{{ $label }}</span>
    </button>
</div>
