@extends('layouts.supply-officer-layout')

@section('title', 'Dashboard')

@section('content')
@php
    use Illuminate\Support\Str;
    use App\Support\RisWorkflow;
    use Carbon\Carbon;

    $firstName = Str::of(trim((string) ($user->user_full_name ?? '')) ?: 'there')->before(' ');

    $formatDate = function ($value) {
        if (blank($value)) {
            return '—';
        }

        return Carbon::parse($value)->format('M j, Y');
    };

    $atpLabel = function ($atp) {
        $number = trim((string) ($atp->authority_purchase_form_number ?? ''));

        return $number !== '' ? $number : 'ATP #'.$atp->authority_purchase_id;
    };

    $sections = [
        [
            'title' => 'Make ATP',
            'count' => $incomingRisCount,
            'url' => route('supply-officer.incoming-ris'),
            'empty' => 'No incoming RIS',
            'rows' => $latestIncomingRis->map(function ($ris) use ($formatDate) {
                return [
                    'title' => RisWorkflow::formNumber($ris),
                    'meta' => trim(($ris->sent_by_name ?: 'Purchaser').' · '.$formatDate($ris->ris_sent_to_supply_at)),
                    'url' => route('supply-officer.atp.index', ['selected_ris' => $ris->ris_id]),
                    'action' => 'Create',
                ];
            }),
        ],
        [
            'title' => 'ATP drafts',
            'count' => $atpDraftCount,
            'url' => route('supply-officer.atp.index', ['status' => 'Draft']),
            'empty' => 'No drafts',
            'rows' => $latestAtpDrafts->map(function ($atp) use ($formatDate, $atpLabel) {
                return [
                    'title' => $atpLabel($atp),
                    'meta' => $formatDate($atp->authority_purchase_updated_at ?: $atp->authority_purchase_created_at),
                    'url' => route('supply-officer.atp.index', ['edit_atp' => $atp->authority_purchase_id]),
                    'action' => 'Open',
                ];
            }),
        ],
        [
            'title' => 'Pending approval',
            'count' => $atpPendingCount,
            'url' => route('supply-officer.atp.index', ['status' => 'Submitted']),
            'empty' => 'None waiting',
            'rows' => $latestAtpPending->map(function ($atp) use ($formatDate, $atpLabel) {
                return [
                    'title' => $atpLabel($atp),
                    'meta' => 'Sent '.$formatDate($atp->authority_purchase_submitted_at),
                    'url' => route('supply-officer.atp.index', ['view_atp' => $atp->authority_purchase_id]),
                    'action' => 'View',
                ];
            }),
        ],
        [
            'title' => 'Return to Purchaser',
            'count' => $atpToReleaseCount,
            'url' => route('supply-officer.atp.index', ['status' => 'Approved']),
            'empty' => 'None ready',
            'rows' => $latestAtpToRelease->map(function ($atp) use ($formatDate, $atpLabel) {
                return [
                    'title' => $atpLabel($atp),
                    'meta' => 'Approved '.$formatDate($atp->authority_purchase_updated_at),
                    'url' => route('supply-officer.atp.index', ['view_atp' => $atp->authority_purchase_id]),
                    'action' => 'Open',
                ];
            }),
        ],
        [
            'title' => 'Returned to Purchaser',
            'count' => $atpReturnedCount,
            'url' => route('supply-officer.atp.index', ['status' => 'Approved']),
            'empty' => 'None yet',
            'rows' => $latestAtpReturned->map(function ($atp) use ($formatDate, $atpLabel) {
                return [
                    'title' => $atpLabel($atp),
                    'meta' => 'Returned '.$formatDate($atp->authority_purchase_released_to_purchaser_at ?? null),
                    'url' => route('supply-officer.atp.index', ['view_atp' => $atp->authority_purchase_id]),
                    'action' => 'View',
                ];
            }),
        ],
    ];
@endphp

<div class="space-y-6">
    <h1 class="text-2xl font-semibold tracking-tight text-slate-950" style="font-family: Outfit, sans-serif;">
        Hello, {{ $firstName }}
    </h1>

    <div class="grid gap-4 xl:grid-cols-2">
        @foreach ($sections as $section)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                    <div class="flex min-w-0 items-center gap-2">
                        <h2 class="truncate text-sm font-semibold text-slate-900">{{ $section['title'] }}</h2>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">
                            {{ number_format($section['count']) }}
                        </span>
                    </div>
                    <a href="{{ $section['url'] }}" class="shrink-0 text-xs font-semibold text-[#0025cc] hover:underline">
                        View all
                    </a>
                </div>

                <ul class="divide-y divide-slate-100">
                    @forelse ($section['rows'] as $row)
                        <li>
                            <a
                                href="{{ $row['url'] }}"
                                class="flex items-center justify-between gap-3 px-5 py-3 transition hover:bg-slate-50"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $row['title'] }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $row['meta'] }}</p>
                                </div>
                                <span class="shrink-0 text-xs font-semibold text-slate-500">{{ $row['action'] }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-slate-400">
                            {{ $section['empty'] }}
                        </li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</div>
@endsection
