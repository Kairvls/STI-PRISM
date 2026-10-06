{{-- Shared skeleton loader styles + window.prismSkeleton(type, options) for JS-rendered placeholders.
     Blade pages can use @include('partials.skeleton', [...]) which renders the same markup. --}}
<style>
    .prism-skel {
        position: relative;
        overflow: hidden;
        border-radius: 6px;
        background: #eef0f3;
    }

    .prism-skel::after {
        content: "";
        position: absolute;
        inset: 0;
        transform: translateX(-100%);
        background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.65) 50%, rgba(255, 255, 255, 0) 100%);
        animation: prism-skel-shimmer 1.4s ease-in-out infinite;
    }

    .prism-skel-round {
        border-radius: 999px;
    }

    @keyframes prism-skel-shimmer {
        100% {
            transform: translateX(100%);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .prism-skel::after {
            animation: none;
        }
    }
</style>
<script>
(function () {
    if (window.prismSkeleton) return;

    var widths = ['92%', '78%', '85%', '70%', '88%', '64%', '81%', '74%'];

    function bar(width, height, extra) {
        return '<div class="prism-skel ' + (extra || '') + '" style="width:' + width + ';height:' + (height || 10) + 'px"></div>';
    }

    function tableRows(rows, cols) {
        var html = '<div class="divide-y divide-gray-100" aria-hidden="true">';
        for (var r = 0; r < rows; r++) {
            html += '<div class="flex items-center gap-4 px-5 py-4">';
            html += '<div class="min-w-0 flex-[2] space-y-2">' + bar(widths[r % widths.length], 11) + bar('48%', 9) + '</div>';
            for (var c = 1; c < cols; c++) {
                html += '<div class="hidden min-w-0 flex-1 sm:block">' + bar(widths[(r + c) % widths.length], 10) + '</div>';
            }
            html += '<div class="shrink-0">' + bar('64px', 26, 'prism-skel-round') + '</div>';
            html += '</div>';
        }
        return html + '</div>';
    }

    function listRows(rows) {
        var html = '<div class="divide-y divide-gray-100" aria-hidden="true">';
        for (var r = 0; r < rows; r++) {
            html += '<div class="flex items-center gap-3 px-5 py-3.5">';
            html += '<div class="prism-skel prism-skel-round shrink-0" style="width:32px;height:32px"></div>';
            html += '<div class="min-w-0 flex-1 space-y-2">' + bar(widths[r % widths.length], 11) + bar('40%', 9) + '</div>';
            html += '</div>';
        }
        return html + '</div>';
    }

    function detailBlock(rows) {
        var html = '<div class="space-y-5 px-5 py-5" aria-hidden="true">';
        html += '<div class="space-y-2 rounded-xl border border-gray-100 px-4 py-4">' + bar('30%', 9) + bar('55%', 14) + bar('72%', 10) + '</div>';
        for (var r = 0; r < rows; r++) {
            html += '<div class="flex items-center justify-between gap-4 rounded-xl border border-gray-100 px-4 py-3">';
            html += '<div class="min-w-0 flex-1 space-y-2">' + bar('22%', 9) + bar(widths[r % widths.length], 11) + '</div>';
            html += bar('56px', 28, 'shrink-0');
            html += '</div>';
        }
        return html + '</div>';
    }

    function documentBlock() {
        var html = '<div class="mx-auto w-full max-w-3xl space-y-4 bg-white p-6" aria-hidden="true">';
        html += '<div class="flex flex-col items-center gap-2">' + bar('40%', 14) + bar('28%', 11) + '</div>';
        html += '<div class="flex justify-end">' + bar('30%', 10) + '</div>';
        html += '<div class="space-y-2">';
        for (var r = 0; r < 8; r++) {
            html += bar('100%', 18);
        }
        html += '</div>';
        html += '<div class="grid grid-cols-4 gap-4 pt-4">' + bar('100%', 28) + bar('100%', 28) + bar('100%', 28) + bar('100%', 28) + '</div>';
        return html + '</div>';
    }

    // type: 'table' | 'list' | 'detail' | 'document'
    // options: rows (number), cols (number, table only), label (screen-reader text)
    window.prismSkeleton = function (type, options) {
        var opts = options || {};
        var rows = opts.rows || (type === 'detail' ? 3 : 5);
        var label = opts.label || 'Loading';
        var body;

        if (type === 'list') {
            body = listRows(rows);
        } else if (type === 'detail') {
            body = detailBlock(rows);
        } else if (type === 'document') {
            body = documentBlock();
        } else {
            body = tableRows(rows, opts.cols || 4);
        }

        return '<div role="status" aria-live="polite" aria-busy="true"><span class="sr-only">' + label + '…</span>' + body + '</div>';
    };
})();
</script>
