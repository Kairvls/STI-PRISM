@php
    $current = $current ?? '';
@endphp

@once
    @push('styles')
        <style>
            @media print {
                #sidebar, .sidebar, [id="sidebar"], header, .print-hidden, .admin-reports-nav { display: none !important; }
                .print-only { display: block !important; }
                main, .content-wrapper { overflow: visible !important; height: auto !important; }
                body { overflow: visible !important; background: #fff !important; }
            }
            .print-only { display: none; }
        </style>
    @endpush
@endonce
