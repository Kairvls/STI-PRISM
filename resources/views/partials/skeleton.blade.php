{{--
  Skeleton placeholder (same look as window.prismSkeleton in layouts/partials/prism-skeleton).
  @include('partials.skeleton', ['skeletonType' => 'table'|'list'|'detail'|'document', 'skeletonRows' => 5, 'skeletonCols' => 4, 'skeletonLabel' => 'Loading'])
  Option names are prefixed because @include also receives every variable from the parent view.
--}}
@php
    $type = $skeletonType ?? 'table';
    $rows = (int) ($skeletonRows ?? ($type === 'detail' ? 3 : 5));
    $cols = (int) ($skeletonCols ?? 4);
    $label = $skeletonLabel ?? 'Loading';
    $widths = ['92%', '78%', '85%', '70%', '88%', '64%', '81%', '74%'];
@endphp
<div role="status" aria-live="polite" aria-busy="true">
    <span class="sr-only">{{ $label }}…</span>

    @if($type === 'list')
        <div class="divide-y divide-gray-100" aria-hidden="true">
            @for($r = 0; $r < $rows; $r++)
                <div class="flex items-center gap-3 px-5 py-3.5">
                    <div class="prism-skel prism-skel-round shrink-0" style="width:32px;height:32px"></div>
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="prism-skel" style="width:{{ $widths[$r % 8] }};height:11px"></div>
                        <div class="prism-skel" style="width:40%;height:9px"></div>
                    </div>
                </div>
            @endfor
        </div>
    @elseif($type === 'detail')
        <div class="space-y-5 px-5 py-5" aria-hidden="true">
            <div class="space-y-2 rounded-xl border border-gray-100 px-4 py-4">
                <div class="prism-skel" style="width:30%;height:9px"></div>
                <div class="prism-skel" style="width:55%;height:14px"></div>
                <div class="prism-skel" style="width:72%;height:10px"></div>
            </div>
            @for($r = 0; $r < $rows; $r++)
                <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-100 px-4 py-3">
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="prism-skel" style="width:22%;height:9px"></div>
                        <div class="prism-skel" style="width:{{ $widths[$r % 8] }};height:11px"></div>
                    </div>
                    <div class="prism-skel shrink-0" style="width:56px;height:28px"></div>
                </div>
            @endfor
        </div>
    @elseif($type === 'document')
        <div class="mx-auto w-full max-w-3xl space-y-4 bg-white p-6" aria-hidden="true">
            <div class="flex flex-col items-center gap-2">
                <div class="prism-skel" style="width:40%;height:14px"></div>
                <div class="prism-skel" style="width:28%;height:11px"></div>
            </div>
            <div class="flex justify-end">
                <div class="prism-skel" style="width:30%;height:10px"></div>
            </div>
            <div class="space-y-2">
                @for($r = 0; $r < 8; $r++)
                    <div class="prism-skel" style="width:100%;height:18px"></div>
                @endfor
            </div>
            <div class="grid grid-cols-4 gap-4 pt-4">
                @for($c = 0; $c < 4; $c++)
                    <div class="prism-skel" style="width:100%;height:28px"></div>
                @endfor
            </div>
        </div>
    @else
        <div class="divide-y divide-gray-100" aria-hidden="true">
            @for($r = 0; $r < $rows; $r++)
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="min-w-0 flex-[2] space-y-2">
                        <div class="prism-skel" style="width:{{ $widths[$r % 8] }};height:11px"></div>
                        <div class="prism-skel" style="width:48%;height:9px"></div>
                    </div>
                    @for($c = 1; $c < $cols; $c++)
                        <div class="hidden min-w-0 flex-1 sm:block">
                            <div class="prism-skel" style="width:{{ $widths[($r + $c) % 8] }};height:10px"></div>
                        </div>
                    @endfor
                    <div class="shrink-0">
                        <div class="prism-skel prism-skel-round" style="width:64px;height:26px"></div>
                    </div>
                </div>
            @endfor
        </div>
    @endif
</div>
