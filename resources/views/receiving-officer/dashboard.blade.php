@extends('layouts.receiving-layout')

@section('title', 'Dashboard')

@section('content')

<style>
.ro-dash { font-family: Inter, sans-serif; }
.ro-dash-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
.ro-date { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 13px; font-weight: 500; color: #475569; }
.ro-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 24px; align-items: start; }
.ro-stat-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 16px; margin-bottom: 16px; }
.ro-card-link { display: block; text-decoration: none; color: inherit; }
.ro-card-link:hover .ro-card { border-color: #cbd5e1; box-shadow: 0 6px 16px rgba(15,23,42,.06); transform: translateY(-1px); }
.ro-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 14px 16px; box-shadow: 0 1px 2px rgba(15,23,42,.03); transition: .15s ease; height: 100%; }
.ro-card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
.ro-icon { width: 42px; height: 42px; border-radius: 14px; display: flex; align-items: center; justify-content: center; }
.ro-icon i, .ro-icon svg { width: 18px; height: 18px; }
.ro-icon-amber { background: #fffbeb; color: #d97706; }
.ro-icon-emerald { background: #ecfdf5; color: #059669; }
.ro-icon-rose { background: #fff1f2; color: #e11d48; }
.ro-icon-blue { background: #eff6ff; color: #0037c7; }
.ro-pill { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px; }
.ro-pill-warn { background: #fffbeb; color: #d97706; }
.ro-pill-ok { background: #ecfdf5; color: #059669; }
.ro-label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }
.ro-value { font-family: Outfit, sans-serif; font-size: 1.75rem; font-weight: 700; color: #0f172a; line-height: 1; }
.ro-hint { margin-top: 6px; font-size: 12px; color: #94a3b8; }
.ro-alert { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; border-radius: 18px; margin-bottom: 16px; }
.ro-alert-warn { background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1px solid #fde68a; }
.ro-alert-ok { background: #f0fdf4; border: 1px solid #bbf7d0; }
.ro-alert-left { display: flex; align-items: center; gap: 12px; }
.ro-alert-icon { width: 40px; height: 40px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; }
.ro-alert-title { font-family: Outfit, sans-serif; font-size: 15px; font-weight: 700; color: #0f172a; }
.ro-alert-desc { margin-top: 2px; font-size: 12px; color: #64748b; }
.ro-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px; background: rgba(0,55,199,.85); color: #fff; border-radius: 12px; font-size: 12px; font-weight: 600; text-decoration: none; white-space: nowrap; }
.ro-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(15,23,42,.03); }
.ro-panel-h { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
.ro-panel-title { font-family: Outfit, sans-serif; font-size: 14px; font-weight: 700; color: #0f172a; }
.ro-panel-sub { font-size: 12px; color: #64748b; margin-top: 2px; }
.ro-link { font-size: 12px; font-weight: 600; color: #0037c7; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
.ro-quick { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; padding: 14px 16px; }
.ro-quick button, .ro-quick a { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 4px; padding: 12px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; cursor: pointer; width: 100%; font-family: inherit; }
.ro-quick button:hover, .ro-quick a:hover { background: #fff; border-color: #cbd5e1; transform: translateY(-1px); }
.ro-quick-label { font-size: 11px; font-weight: 600; color: #0f172a; }
.ro-quick-desc { font-size: 10px; color: #94a3b8; }
.ro-table { width: 100%; border-collapse: collapse; }
.ro-table th { text-align: left; padding: 10px 14px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.ro-table td { padding: 10px 14px; font-size: 12px; color: #475569; border-bottom: 1px solid #f1f5f9; }
.ro-ref { font-weight: 600; color: #0f172a; }
.ro-badge { display: inline-flex; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.ro-badge-amber { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
.ro-badge-emerald { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
.ro-badge-rose { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
.ro-side { display: flex; flex-direction: column; gap: 16px; }
.ro-act { display: flex; gap: 10px; padding: 10px 14px; border-bottom: 1px solid #f8fafc; }
.ro-act:last-child { border-bottom: none; }
.ro-empty { padding: 28px 16px; text-align: center; color: #94a3b8; font-size: 13px; }
.ro-qa-loading { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 48px 16px; color: #64748b; font-size: 13px; }
.ro-qa-spinner { width: 20px; height: 20px; border: 2px solid #e2e8f0; border-top-color: #0037c7; border-radius: 50%; animation: ro-spin .7s linear infinite; }
@keyframes ro-spin { to { transform: rotate(360deg); } }
.sidebar-calendar-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden; box-shadow: 0 1px 2px rgba(15,23,42,.03); }
.sidebar-calendar-header { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
.sidebar-calendar-title { font-family: Outfit, sans-serif; font-size: 14px; font-weight: 700; color: #0f172a; display: flex; align-items: center; }
.sidebar-calendar-body { padding: 10px 12px 12px; }
.calendar-month-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.cal-nav-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid #e2e8f0; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: #64748b; }
.cal-month-label { font-size: 12px; font-weight: 700; color: #0f172a; }
.calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; margin-bottom: 10px; }
.cal-day-header { text-align: center; font-size: 8px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; padding: 2px 0; }
.cal-day { text-align: center; padding: 4px 1px; border-radius: 6px; font-size: 10px; font-weight: 500; color: #475569; min-height: 22px; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; }
.cal-day-empty { opacity: .3; }
.cal-day-today { background: #eef2ff; color: #0037c7; font-weight: 700; }
.cal-day-has-event { color: #0f172a; font-weight: 600; }
.cal-day-dot { width: 4px; height: 4px; border-radius: 50%; background: #f59e0b; margin-top: 1px; }
.cal-upcoming { border-top: 1px solid #f1f5f9; padding-top: 8px; }
.cal-upcoming-title { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 6px; }
.cal-upcoming-item { display: flex; align-items: flex-start; gap: 6px; padding: 4px 0; }
.cal-upcoming-dot { width: 6px; height: 6px; border-radius: 50%; background: #f59e0b; margin-top: 4px; flex-shrink: 0; }
.cal-upcoming-content { display: flex; flex-direction: column; min-width: 0; }
.cal-upcoming-name { font-size: 11px; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cal-upcoming-date { font-size: 9px; color: #94a3b8; }
.cal-upcoming-empty { font-size: 10px; color: #94a3b8; text-align: center; padding: 6px 0; }
@media (max-width: 1200px) { .ro-grid { grid-template-columns: 1fr; } .ro-stat-grid, .ro-quick { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) { .ro-stat-grid, .ro-quick { grid-template-columns: 1fr; } .ro-dash-header, .ro-alert { flex-direction: column; align-items: flex-start; } }
</style>

<div class="admin-page ro-dash">
    <div class="ro-dash-header">
        <div>
            <h1 class="admin-page-title">Dashboard</h1>
            <p class="admin-page-subtitle">Inspect deliveries, accept complete items into inventory, or return mismatches.</p>
        </div>
        <span class="ro-date">
            <i data-lucide="calendar" class="h-4 w-4"></i>
            {{ now()->format('l, F j, Y') }}
        </span>
    </div>

    @include('layouts.partials.receiving-query-error')

    <div class="ro-grid">
        <div>
            <div class="ro-stat-grid">
                <a href="/receiving/reports" class="ro-card-link">
                    <div class="ro-card">
                        <div class="ro-card-top">
                            <div class="ro-icon ro-icon-amber"><i data-lucide="clipboard-list"></i></div>
                            @if($pendingCount > 0)
                                <span class="ro-pill ro-pill-warn">Needs attention</span>
                            @else
                                <span class="ro-pill ro-pill-ok">All clear</span>
                            @endif
                        </div>
                        <p class="ro-label">Pending Receiving Reports</p>
                        <p class="ro-value">{{ $pendingCount }}</p>
                        <p class="ro-hint">₱{{ number_format($pendingAmount, 2) }} awaiting inspection</p>
                    </div>
                </a>
                <a href="/receiving/delivered-items" class="ro-card-link">
                    <div class="ro-card">
                        <div class="ro-card-top"><div class="ro-icon ro-icon-emerald"><i data-lucide="package-check"></i></div></div>
                        <p class="ro-label">Delivered Items</p>
                        <p class="ro-value">{{ $acceptedCount }}</p>
                        <p class="ro-hint">{{ $acceptedMonth }} accepted this month</p>
                    </div>
                </a>
                <a href="/receiving/supplier-records" class="ro-card-link">
                    <div class="ro-card">
                        <div class="ro-card-top"><div class="ro-icon ro-icon-blue"><i data-lucide="building-2"></i></div></div>
                        <p class="ro-label">Supplier Records</p>
                        <p class="ro-value">{{ $supplierCount }}</p>
                        <p class="ro-hint">Physical and online vendors</p>
                    </div>
                </a>
                <a href="/receiving/history" class="ro-card-link">
                    <div class="ro-card">
                        <div class="ro-card-top"><div class="ro-icon ro-icon-blue"><i data-lucide="history"></i></div></div>
                        <p class="ro-label">Delivery History</p>
                        <p class="ro-value">{{ $historyCount ?? ($acceptedCount + $returnedCount) }}</p>
                        <p class="ro-hint">{{ $returnedCount }} returned</p>
                    </div>
                </a>
                <a href="/receiving/logs" class="ro-card-link">
                    <div class="ro-card">
                        <div class="ro-card-top"><div class="ro-icon ro-icon-blue"><i data-lucide="scroll-text"></i></div></div>
                        <p class="ro-label">Receiving Logs</p>
                        <p class="ro-value">{{ $logCount }}</p>
                        <p class="ro-hint">Inspection audit trail</p>
                    </div>
                </a>
            </div>

            @if($pendingCount > 0)
                <div class="ro-alert ro-alert-warn">
                    <div class="ro-alert-left">
                        <div class="ro-alert-icon" style="background:#f59e0b;"><i data-lucide="bell"></i></div>
                        <div>
                            <p class="ro-alert-title">{{ $pendingCount }} {{ $pendingCount === 1 ? 'delivery needs' : 'deliveries need' }} inspection</p>
                            <p class="ro-alert-desc">Validate quantity, model, condition, and documents before updating inventory.</p>
                        </div>
                    </div>
                    <a href="/receiving/reports" class="ro-btn">Inspect now <i data-lucide="arrow-right" class="h-4 w-4"></i></a>
                </div>
            @else
                <div class="ro-alert ro-alert-ok">
                    <div class="ro-alert-left">
                        <div class="ro-alert-icon" style="background:#10b981;"><i data-lucide="check-circle-2"></i></div>
                        <div>
                            <p class="ro-alert-title">No deliveries waiting</p>
                            <p class="ro-alert-desc">Waiting for Purchaser ATP to be approved. Those records appear here for inspection.</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="ro-panel">
                <div class="ro-panel-h">
                    <div>
                        <p class="ro-panel-title">Quick access</p>
                        <p class="ro-panel-sub">Open a section overview in a modal</p>
                    </div>
                </div>
                <div class="ro-quick">
                    <button type="button" onclick="openReceivingQuickAccess('pending')">
                        <div class="ro-icon ro-icon-amber"><i data-lucide="clipboard-list"></i></div>
                        <span class="ro-quick-label">Pending Receiving Reports</span>
                        <span class="ro-quick-desc">Inspect incoming deliveries</span>
                    </button>
                    <button type="button" onclick="openReceivingQuickAccess('delivered')">
                        <div class="ro-icon ro-icon-emerald"><i data-lucide="package-check"></i></div>
                        <span class="ro-quick-label">Delivered Items</span>
                        <span class="ro-quick-desc">Accepted into inventory</span>
                    </button>
                    <button type="button" onclick="openReceivingQuickAccess('suppliers')">
                        <div class="ro-icon ro-icon-blue"><i data-lucide="building-2"></i></div>
                        <span class="ro-quick-label">Supplier Records</span>
                        <span class="ro-quick-desc">Vendor records</span>
                    </button>
                    <button type="button" onclick="openReceivingQuickAccess('history')">
                        <div class="ro-icon ro-icon-blue"><i data-lucide="history"></i></div>
                        <span class="ro-quick-label">Delivery History</span>
                        <span class="ro-quick-desc">Accepted and returned</span>
                    </button>
                    <button type="button" onclick="openReceivingQuickAccess('logs')">
                        <div class="ro-icon ro-icon-blue"><i data-lucide="scroll-text"></i></div>
                        <span class="ro-quick-label">Receiving Logs</span>
                        <span class="ro-quick-desc">Inspection audit trail</span>
                    </button>
                </div>
            </div>

            <div class="ro-panel">
                <div class="ro-panel-h">
                    <div>
                        <p class="ro-panel-title">Pending deliveries</p>
                        <p class="ro-panel-sub">Approved ATP waiting for physical validation</p>
                    </div>
                    <a class="ro-link" href="/receiving/reports">View all <i data-lucide="arrow-right" class="h-4 w-4"></i></a>
                </div>
                <div class="overflow-x-auto">
                    <table class="ro-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Items</th>
                                <th>Supplier</th>
                                <th>Value</th>
                                <th>Status</th>
                                <th>Preview</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingRows as $row)
                                @php $previewRisId = $row->ris_id ?? $row->authority_purchase_ris_id ?? null; @endphp
                                <tr>
                                    <td><span class="ro-ref">{{ $row->ris_form_number ?: ($row->authority_purchase_form_number ?: 'ATP-'.$row->authority_purchase_id) }}</span></td>
                                    <td>{{ \Illuminate\Support\Str::limit($row->item_names ?: '—', 40) }}</td>
                                    <td>{{ $row->supplier_name }}</td>
                                    <td>₱{{ number_format((float) ($row->total_amount ?? 0), 2) }}</td>
                                    <td>
                                        @if(($row->receiving_report_status ?? null) === 'Returned')
                                            <span class="ro-badge ro-badge-rose">Returned</span>
                                        @else
                                            <span class="ro-badge ro-badge-amber">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <button type="button" class="ro-preview-btn" @if($previewRisId) onclick="openReceivingRisPreview('{{ $previewRisId }}')" @else disabled @endif title="Preview RIS">
                                                <i data-lucide="eye" class="h-4 w-4"></i>
                                            </button>
                                            <a class="ro-link" href="/receiving/reports?atp={{ $row->authority_purchase_id }}">Inspect</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="ro-empty">Waiting for Purchaser ATP to be approved. Nothing is ready to inspect yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ro-panel">
                <div class="ro-panel-h">
                    <div>
                        <p class="ro-panel-title">Recently accepted</p>
                        <p class="ro-panel-sub">Items already received into inventory</p>
                    </div>
                    <a class="ro-link" href="/receiving/delivered-items">View all <i data-lucide="arrow-right" class="h-4 w-4"></i></a>
                </div>
                <div class="overflow-x-auto">
                    <table class="ro-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Items</th>
                                <th>Supplier</th>
                                <th>OR</th>
                                <th>Date</th>
                                <th>Officer</th>
                                <th>Preview</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($acceptedRows as $row)
                                @php $previewRisId = $row->ris_id ?? $row->authority_purchase_ris_id ?? null; @endphp
                                <tr>
                                    <td><span class="ro-ref">{{ $row->ris_form_number ?: $row->authority_purchase_form_number }}</span></td>
                                    <td>{{ \Illuminate\Support\Str::limit($row->item_names ?: '—', 40) }}</td>
                                    <td>{{ $row->supplier_name }}</td>
                                    <td>{{ $row->official_receipt ?: '—' }}</td>
                                    <td>{{ $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('M d, Y') : '—' }}</td>
                                    <td>{{ $row->officer_name ?: '—' }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <button type="button" class="ro-preview-btn" @if($previewRisId) onclick="openReceivingRisPreview('{{ $previewRisId }}')" @else disabled @endif title="Preview RIS">
                                                <i data-lucide="eye" class="h-4 w-4"></i>
                                            </button>
                                            @if(!empty($row->receiving_report_id))
                                                <a class="ro-link" href="/receiving/reports/{{ $row->receiving_report_id }}/print">Print</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="ro-empty">Waiting for the first accepted delivery.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="ro-side">
            @php
                $calendarEvents = $calendarEvents ?? collect();
                $calendarEventsByDate = $calendarEventsByDate ?? [];
            @endphp
            <div class="sidebar-calendar-card">
                <div class="sidebar-calendar-header">
                    <h3 class="sidebar-calendar-title">
                        <i data-lucide="calendar" class="h-4 w-4" style="margin-right: 6px;"></i>
                        Calendar of Events
                    </h3>
                </div>
                <div class="sidebar-calendar-body">
                    <div class="calendar-month-header">
                        <button type="button" class="cal-nav-btn" title="Previous month" disabled>
                            <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                        </button>
                        <span class="cal-month-label">{{ now()->format('F Y') }}</span>
                        <button type="button" class="cal-nav-btn" title="Next month" disabled>
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                        </button>
                    </div>
                    <div class="calendar-grid">
                        <div class="cal-day-header">Sun</div>
                        <div class="cal-day-header">Mon</div>
                        <div class="cal-day-header">Tue</div>
                        <div class="cal-day-header">Wed</div>
                        <div class="cal-day-header">Thu</div>
                        <div class="cal-day-header">Fri</div>
                        <div class="cal-day-header">Sat</div>
                        @php
                            $now = now();
                            $firstDay = $now->copy()->startOfMonth();
                            $lastDay = $now->copy()->endOfMonth();
                            $startPadding = $firstDay->dayOfWeek;
                            $totalCells = $startPadding + $lastDay->day;
                            $rows = ceil($totalCells / 7);
                            $totalSlots = $rows * 7;
                            $todayDate = $now->format('Y-m-d');
                            $currentMonthKey = $now->format('Y-m');
                        @endphp
                        @for($i = 0; $i < $startPadding; $i++)
                            <div class="cal-day cal-day-empty"></div>
                        @endfor
                        @for($day = 1; $day <= $lastDay->day; $day++)
                            @php
                                $dateKey = $currentMonthKey . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                                $hasEvents = isset($calendarEventsByDate[$dateKey]) && count($calendarEventsByDate[$dateKey]) > 0;
                                $isToday = $dateKey === $todayDate;
                                $eventCount = count($calendarEventsByDate[$dateKey] ?? []);
                            @endphp
                            <div class="cal-day {{ $isToday ? 'cal-day-today' : '' }} {{ $hasEvents ? 'cal-day-has-event' : '' }}" title="{{ $hasEvents ? $eventCount . ' event(s)' : '' }}">
                                <span class="cal-day-num">{{ $day }}</span>
                                @if($hasEvents)
                                    <span class="cal-day-dot"></span>
                                @endif
                            </div>
                        @endfor
                        @for($i = $startPadding + $lastDay->day; $i < $totalSlots; $i++)
                            <div class="cal-day cal-day-empty"></div>
                        @endfor
                    </div>
                    <div class="cal-upcoming">
                        <h4 class="cal-upcoming-title">Upcoming Events</h4>
                        @forelse($calendarEvents->take(3) as $event)
                            <div class="cal-upcoming-item">
                                <div class="cal-upcoming-dot"></div>
                                <div class="cal-upcoming-content">
                                    <span class="cal-upcoming-name">{{ $event->equipment_name ?? 'Equipment' }}</span>
                                    <span class="cal-upcoming-date">
                                        {{ $event->maintenance_schedule_next_date ? \Carbon\Carbon::parse($event->maintenance_schedule_next_date)->format('M d, Y') : 'No date set' }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="cal-upcoming-empty">No upcoming maintenance events</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="ro-panel">
                <div class="ro-panel-h">
                    <div>
                        <p class="ro-panel-title">Returned for correction</p>
                        <p class="ro-panel-sub">Need a follow-up inspection</p>
                    </div>
                </div>
                @forelse($returnedRows as $row)
                    <div class="ro-act">
                        <div class="ro-icon ro-icon-rose" style="width:32px;height:32px;border-radius:8px;"><i data-lucide="undo-2"></i></div>
                        <div>
                            <p class="text-xs font-semibold text-slate-800">{{ $row->ris_form_number ?: $row->authority_purchase_form_number }}</p>
                            <p class="text-[11px] text-slate-500">{{ \Illuminate\Support\Str::limit($row->item_names ?: $row->supplier_name, 42) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="ro-empty">No returned deliveries.</p>
                @endforelse
            </div>

            <div class="ro-panel">
                <div class="ro-panel-h">
                    <div>
                        <p class="ro-panel-title">Top suppliers</p>
                        <p class="ro-panel-sub">By accepted deliveries</p>
                    </div>
                    <a class="ro-link" href="/receiving/supplier-records">All</a>
                </div>
                @forelse($topSuppliers as $supplier)
                    <div class="ro-act">
                        <div class="ro-icon ro-icon-blue" style="width:32px;height:32px;border-radius:8px;"><i data-lucide="store"></i></div>
                        <div>
                            <p class="text-xs font-semibold text-slate-800">{{ $supplier->supplier_name }}</p>
                            <p class="text-[11px] text-slate-500">{{ $supplier->delivery_count }} accepted · {{ $supplier->contact_person ?: 'No contact' }}</p>
                        </div>
                    </div>
                @empty
                    <p class="ro-empty">No supplier records yet.</p>
                @endforelse
            </div>

            <div class="ro-panel">
                <div class="ro-panel-h">
                    <div>
                        <p class="ro-panel-title">Recent activity</p>
                        <p class="ro-panel-sub">Inspection and inventory log</p>
                    </div>
                    <a class="ro-link" href="/receiving/logs">Logs</a>
                </div>
                @forelse($recentLogs as $log)
                    <div class="ro-act">
                        <div class="ro-icon {{ str_contains(strtolower($log->receiving_log_action), 'return') ? 'ro-icon-rose' : 'ro-icon-emerald' }}" style="width:32px;height:32px;border-radius:8px;">
                            <i data-lucide="{{ str_contains(strtolower($log->receiving_log_action), 'return') ? 'undo-2' : 'check' }}"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-800">{{ $log->receiving_log_action }}</p>
                            <p class="text-[11px] text-slate-500">{{ $log->ris_form_number ?: ($log->authority_purchase_form_number ?: 'ATP') }} · {{ $log->officer_name ?: 'Receiving Officer' }}</p>
                            <p class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($log->receiving_log_created_at)->format('M d, Y g:i A') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="ro-empty">No receiving activity logged yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div id="receivingQuickAccessModal" class="ris-preview-modal-overlay" style="z-index: 12000;">
    <div class="ris-preview-modal-container" style="max-width: 95vw; width: 1200px;">
        <div class="ris-preview-modal-header">
            <h3 class="ris-preview-modal-title" id="receivingQaTitle">Quick Access</h3>
            <button type="button" class="ris-preview-modal-close" onclick="closeReceivingQuickAccess()" title="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <div class="ris-preview-modal-body" id="receivingQaBody" style="background:#fff; min-height: 280px; max-height: calc(90vh - 110px); overflow: auto; padding: 16px;">
            <div class="ro-qa-loading"><div class="ro-qa-spinner"></div><span>Loading...</span></div>
        </div>
        <div class="ris-preview-modal-footer">
            <button type="button" class="ris-preview-modal-btn-close" onclick="closeReceivingQuickAccess()">Close</button>
        </div>
    </div>
</div>

<script>
window.openReceivingQuickAccess = function (section) {
    var modal = document.getElementById('receivingQuickAccessModal');
    var body = document.getElementById('receivingQaBody');
    var title = document.getElementById('receivingQaTitle');
    if (!modal || !body) return;
    var titles = {
        pending: 'Pending Receiving Reports',
        delivered: 'Delivered Items',
        suppliers: 'Supplier Records',
        history: 'Delivery History',
        logs: 'Receiving Logs'
    };
    if (title) title.textContent = titles[section] || 'Quick Access';
    body.innerHTML = '<div class="ro-qa-loading"><div class="ro-qa-spinner"></div><span>Loading...</span></div>';
    modal.style.display = 'flex';
    fetch('/receiving/quick-access/' + encodeURIComponent(section), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
    })
    .then(function (response) {
        if (!response.ok) throw new Error('Failed to load');
        return response.text();
    })
    .then(function (html) {
        body.innerHTML = html;
        if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
    })
    .catch(function () {
        body.innerHTML = '<div class="ro-qa-loading" style="color:#e11d48;"><span>Failed to load this section.</span></div>';
    });
};
window.closeReceivingQuickAccess = function () {
    var modal = document.getElementById('receivingQuickAccessModal');
    var body = document.getElementById('receivingQaBody');
    if (modal) modal.style.display = 'none';
    if (body) body.innerHTML = '<div class="ro-qa-loading"><div class="ro-qa-spinner"></div><span>Loading...</span></div>';
};
document.addEventListener('click', function (e) {
    var modal = document.getElementById('receivingQuickAccessModal');
    if (modal && e.target === modal) closeReceivingQuickAccess();
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeReceivingQuickAccess();
});
</script>

@endsection
