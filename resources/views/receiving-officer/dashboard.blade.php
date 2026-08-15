@extends('layouts.receiving-layout')

@section('title', 'Dashboard')

@section('content')

<style>
.ro-dash { font-family: Inter, sans-serif; }
.ro-dash-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
.ro-date { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 13px; font-weight: 500; color: #475569; }
.ro-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 24px; align-items: start; }
.ro-stat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 16px; }
.ro-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 14px 16px; box-shadow: 0 1px 2px rgba(15,23,42,.03); }
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
.ro-quick { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; padding: 14px 16px; }
.ro-quick a { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 4px; padding: 12px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; }
.ro-quick a:hover { background: #fff; border-color: #cbd5e1; transform: translateY(-1px); }
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
@media (max-width: 1200px) { .ro-grid { grid-template-columns: 1fr; } .ro-stat-grid { grid-template-columns: repeat(2, 1fr); } }
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
                <div class="ro-card">
                    <div class="ro-card-top">
                        <div class="ro-icon ro-icon-amber"><i data-lucide="package-search"></i></div>
                        @if($pendingCount > 0)
                            <span class="ro-pill ro-pill-warn">Needs attention</span>
                        @else
                            <span class="ro-pill ro-pill-ok">All clear</span>
                        @endif
                    </div>
                    <p class="ro-label">Pending inspection</p>
                    <p class="ro-value">{{ $pendingCount }}</p>
                    <p class="ro-hint">₱{{ number_format($pendingAmount, 2) }} awaiting check</p>
                </div>
                <div class="ro-card">
                    <div class="ro-card-top"><div class="ro-icon ro-icon-emerald"><i data-lucide="package-check"></i></div></div>
                    <p class="ro-label">Accepted this month</p>
                    <p class="ro-value">{{ $acceptedMonth }}</p>
                    <p class="ro-hint">{{ $acceptedCount }} accepted overall</p>
                </div>
                <div class="ro-card">
                    <div class="ro-card-top"><div class="ro-icon ro-icon-rose"><i data-lucide="undo-2"></i></div></div>
                    <p class="ro-label">Returned</p>
                    <p class="ro-value">{{ $returnedCount }}</p>
                    <p class="ro-hint">Sent back for correction</p>
                </div>
                <div class="ro-card">
                    <div class="ro-card-top"><div class="ro-icon ro-icon-blue"><i data-lucide="truck"></i></div></div>
                    <p class="ro-label">Suppliers</p>
                    <p class="ro-value">{{ $supplierCount }}</p>
                    <p class="ro-hint">{{ $logCount }} log entries</p>
                </div>
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
                        <p class="ro-panel-sub">Jump to the receiving tasks you use most</p>
                    </div>
                </div>
                <div class="ro-quick">
                    <a href="/receiving/reports">
                        <div class="ro-icon ro-icon-amber"><i data-lucide="clipboard-check"></i></div>
                        <span class="ro-quick-label">Pending reports</span>
                        <span class="ro-quick-desc">Inspect incoming deliveries</span>
                    </a>
                    <a href="/receiving/delivered-items">
                        <div class="ro-icon ro-icon-emerald"><i data-lucide="boxes"></i></div>
                        <span class="ro-quick-label">Delivered items</span>
                        <span class="ro-quick-desc">Accepted into inventory</span>
                    </a>
                    <a href="/receiving/supplier-records">
                        <div class="ro-icon ro-icon-blue"><i data-lucide="store"></i></div>
                        <span class="ro-quick-label">Suppliers</span>
                        <span class="ro-quick-desc">Vendor records</span>
                    </a>
                    <a href="/receiving/history">
                        <div class="ro-icon ro-icon-blue"><i data-lucide="history"></i></div>
                        <span class="ro-quick-label">Delivery history</span>
                        <span class="ro-quick-desc">Accepted and returned</span>
                    </a>
                    <a href="/receiving/logs">
                        <div class="ro-icon ro-icon-blue"><i data-lucide="scroll-text"></i></div>
                        <span class="ro-quick-label">Receiving logs</span>
                        <span class="ro-quick-desc">Inspection audit trail</span>
                    </a>
                    <a href="/receiving/reports">
                        <div class="ro-icon ro-icon-amber"><i data-lucide="scan-line"></i></div>
                        <span class="ro-quick-label">Start inspection</span>
                        <span class="ro-quick-desc">Open the receiving desk</span>
                    </a>
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

@endsection
