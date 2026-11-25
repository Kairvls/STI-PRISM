@extends($procurementLayout ?? 'layouts.purchaser-layout')

@php
    $atpIsSupplyPortal = \App\Support\ProcurementPortal::isSupplyOfficerContext();
    $atpPurchaserOwns = \App\Support\ProcurementPortal::PURCHASER_OWNS_ATP && ! $atpIsSupplyPortal;
    $atpCanCreate = $atpIsSupplyPortal || $atpPurchaserOwns;
@endphp
@section('page-title', 'Authority to Purchase')
@section('page-subtitle', $atpIsSupplyPortal
    ? 'Create from Incoming RIS, submit, then release to Purchaser.'
    : 'Review approved ATPs, choose a payment method, and create the Request for Check.')

@section('content')
@include('partials.floating-menu-script')

<script type="application/json" id="atp-ris-prefill">{!! json_encode($risPrefill ?? []) !!}</script>
@php
    $installmentModalBoot = [
        'terms' => collect($installmentTerms ?? [])->map(fn ($t) => [
            'id' => (int) $t->term_id,
            'name' => (string) $t->term_name,
            'months' => (int) $t->term_months,
        ])->values()->all(),
        'dayIntervals' => collect($installmentDayIntervals ?? [])->map(fn ($i) => [
            'id' => (int) $i->interval_id,
            'name' => (string) $i->interval_name,
            'days' => (int) $i->interval_days,
            'unit' => (string) ($i->interval_unit ?? 'days'),
            'preset' => (int) ($i->interval_is_preset ?? 0) === 1,
        ])->values()->all(),
        'atps' => collect(
            ($atps ?? null) instanceof \Illuminate\Contracts\Pagination\Paginator
                ? $atps->getCollection()
                : ($atps ?? [])
        )->mapWithKeys(fn ($a) => [
            (string) $a->authority_purchase_id => [
                'id' => (int) $a->authority_purchase_id,
                'number' => (string) ($a->authority_purchase_form_number ?? ('ATP #'.$a->authority_purchase_id)),
                'total' => (float) ($a->atp_total_amount ?? 0),
                'supplier' => (string) ($a->company_name ?? $a->shop_name ?? 'Supplier'),
                'plan_url' => route(($pp ?? 'purchaser').'.atp.cash-installment-plan', $a->authority_purchase_id),
            ],
        ])->all(),
        'openId' => (int) request('cash_installment', 0) ?: null,
        'termQuickStoreUrl' => route(($pp ?? 'purchaser').'.installment-terms.quick-store'),
        'intervalQuickStoreUrl' => route(($pp ?? 'purchaser').'.installment-terms.intervals.quick-store'),
    ];
@endphp
<script type="application/json" id="atp-installment-boot">{!! json_encode($installmentModalBoot) !!}</script>

<div
    x-data="{
        createOpen: {{ (
            ($errors->any() && old('authority_purchase_ris_id') !== null)
            || ! empty($selectedRisId)
            || request()->boolean('create')
        ) ? 'true' : 'false' }},
        viewOpen: {{ !empty($viewAtpId) ? 'true' : 'false' }},
        canGoBack: {{ !empty($viewAtpId) ? 'true' : 'false' }} && (() => {
            try {
                const ref = new URL(document.referrer);
                return window.history.length > 1 && ref.origin === window.location.origin && ref.pathname !== window.location.pathname;
            } catch (e) {
                return false;
            }
        })(),
        editOpen: {{ !empty($editAtpId) ? 'true' : 'false' }},
        emptyOpen: false,
        modalFullscreen: false,
        selectedAtp: {{ !empty($editAtpId) ? (int) $editAtpId : (!empty($viewAtpId) ? (int) $viewAtpId : 'null') }},
        risPrefill: JSON.parse(document.getElementById('atp-ris-prefill').textContent || '{}'),
        installmentBoot: JSON.parse(document.getElementById('atp-installment-boot').textContent || '{}'),
        installmentOpen: false,
        installmentAtpId: null,
        installmentTermId: '',
        installmentCountInput: '',
        installmentDayIntervalId: '',
        installmentDownpayment: '',
        installmentDates: [],
        installmentAutoDating: false,
        installmentPickerOpen: null,
        installmentPickerMonth: null,
        installmentTermSelectOpen: false,
        installmentIntervalSelectOpen: false,
        installmentTermQuery: '',
        installmentIntervalQuery: '',
        installmentNewTermMonths: '',
        installmentNewIntervalDays: '',
        installmentTermSaving: false,
        installmentIntervalSaving: false,
        installmentTermError: '',
        installmentIntervalError: '',
        risSplit: false,
        risChoiceTick: 0,
        atpSelectedKeys: [],
        atpCarouselIndex: 0,
        atpDrafts: {},
        atpSharedSignature: '',
        atpSharedReceivedBy: '',
        atpBatchSaving: false,
        expandedAtpGroups: {},
        atpPurchaserOwns: {{ $atpPurchaserOwns ? 'true' : 'false' }},
        risPickerOpen: false,
        risPickerQuery: '',
        risPickerShowAll: false,
        risPickerLimit: 10,

        init() {
            this.$watch('viewOpen', (open) => {
                if (!open && window.purDialog) window.purDialog.clearDeepLink(['view_atp', 'edit_atp', 'choose_path']);
            });
            this.$watch('editOpen', (open) => {
                if (!open && window.purDialog) window.purDialog.clearDeepLink(['view_atp', 'edit_atp']);
            });
            this.$watch('createOpen', (open) => {
                if (!open && window.purDialog) {
                    window.purDialog.clearDeepLink(['create', 'selected_ris', 'selected_supplier']);
                }
                if (open) {
                    this.$nextTick(() => this.bootAtpSelectionFromSelect());
                }
            });
            try {
                const flash = sessionStorage.getItem('atp_flash_success');
                if (flash) {
                    sessionStorage.removeItem('atp_flash_success');
                    this.$nextTick(() => {
                        if (window.purToast) window.purToast(flash, 'success');
                    });
                }
            } catch (e) {}
            // One-shot deep links (?create=1, ?selected_ris=): open Create once, then strip so refresh stays on the list.
            this.clearOneShotAtpCreateQuery();
            const intervals = this.installmentBoot?.dayIntervals || [];
            if (intervals.length && !this.installmentDayIntervalId) {
                this.installmentDayIntervalId = String(intervals[0].id);
            }
            this.$watch('installmentCountInput', () => this.$nextTick(() => this.syncInstallmentDates(true)));
            this.$watch('installmentDownpayment', () => this.$nextTick(() => this.syncInstallmentDates(true)));
            this.$watch('installmentDayIntervalId', () => this.$nextTick(() => this.syncInstallmentDates(true)));
            this.$watch('installmentDates', () => {
                if (this.installmentAutoDating) return;
                this.$nextTick(() => this.syncInstallmentDates(false));
            }, { deep: true });
            if (this.viewOpen && this.selectedAtp) {
                this.bindViewDocSig(this.selectedAtp);
            }
        },
        clearOneShotAtpCreateQuery() {
            const url = new URL(window.location.href);
            const keys = ['create', 'selected_ris', 'selected_supplier'];
            let changed = false;
            keys.forEach((key) => {
                if (url.searchParams.has(key)) {
                    url.searchParams.delete(key);
                    changed = true;
                }
            });
            if (!changed) return;
            const query = url.searchParams.toString();
            window.history.replaceState({}, '', url.pathname + (query ? `?${query}` : '') + url.hash);
        },

        installmentAtp() {
            return (this.installmentBoot?.atps || {})[String(this.installmentAtpId || '')] || null;
        },
        selectedInterval() {
            return (this.installmentBoot?.dayIntervals || []).find((i) => String(i.id) === String(this.installmentDayIntervalId)) || null;
        },
        installmentIntervalUnit() {
            const interval = this.selectedInterval();
            return interval ? String(interval.unit || 'days') : '';
        },
        installmentIntervalDays() {
            const interval = this.selectedInterval();
            if (!interval || this.installmentIntervalUnit() !== 'days') return 0;
            return Number(interval.days || 0);
        },
        installmentCount() {
            const count = Math.floor(Number(this.installmentCountInput || 0));
            if (!Number.isFinite(count) || count < 1) return 0;
            return Math.min(36, count);
        },
        installmentCountLabel() {
            const count = this.installmentCount();
            if (count < 1) return '';
            return count + ' Installment';
        },
        installmentTotal() {
            return Number(this.installmentAtp()?.total || 0);
        },
        installmentDownpaymentMax() {
            return Math.max(0, Math.round(this.installmentTotal() * 100) / 100);
        },
        clampInstallmentDownpayment(raw) {
            let value = (window.PrismInputLimits && window.PrismInputLimits.clampMoney(raw)) || String(raw == null ? '' : raw);
            if (value === '' || value === null) return '';
            const max = this.installmentDownpaymentMax();
            const num = Number(value);
            if (!Number.isFinite(num) || num < 0) return '0';
            if (num > max) {
                return window.PrismInputLimits
                    ? window.PrismInputLimits.formatMoney(max)
                    : String(max);
            }
            return value;
        },
        installmentDown() {
            const raw = Number(this.installmentDownpayment || 0);
            if (!Number.isFinite(raw) || raw < 0) return 0;
            return Math.min(raw, this.installmentDownpaymentMax());
        },
        installmentDownpaymentInvalid() {
            const raw = Number(this.installmentDownpayment || 0);
            if (this.installmentDownpayment === '' || this.installmentDownpayment === null) return false;
            if (!Number.isFinite(raw) || raw < 0) return true;
            return raw > this.installmentTotal();
        },
        installmentRemaining() {
            return Math.max(0, Math.round((this.installmentTotal() - this.installmentDown()) * 100) / 100);
        },
        installmentSchedule() {
            const count = this.installmentCount();
            const remaining = this.installmentRemaining();
            const down = this.installmentDown();
            const rows = [];
            let dateIndex = 0;
            if (down > 0) {
                rows.push({
                    kind: 'downpayment',
                    label: 'Downpayment',
                    amount: down,
                    dateIndex: dateIndex++,
                    sequence: 0,
                });
            }
            // Full downpayment covers the ATP — only the downpayment RFC.
            if (remaining <= 0) {
                return rows;
            }
            if (count > 0) {
                const base = Math.floor((remaining / count) * 100) / 100;
                let allocated = 0;
                for (let i = 1; i <= count; i++) {
                    const amount = i === count
                        ? Math.round((remaining - allocated) * 100) / 100
                        : base;
                    allocated = Math.round((allocated + amount) * 100) / 100;
                    rows.push({
                        kind: 'installment',
                        label: 'Installment ' + i + ' of ' + count,
                        amount,
                        dateIndex: dateIndex++,
                        sequence: i,
                    });
                }
            }
            return rows;
        },
        installmentTodayIso() {
            const today = new Date();
            return today.getFullYear()
                + '-' + String(today.getMonth() + 1).padStart(2, '0')
                + '-' + String(today.getDate()).padStart(2, '0');
        },
        isInstallmentDatePast(iso) {
            const value = String(iso || '');
            return value !== '' && value < this.installmentTodayIso();
        },
        addDaysIso(isoDate, days) {
            return this.shiftIso(isoDate, Number(days || 0), 0);
        },
        shiftIso(isoDate, days, months) {
            const parts = String(isoDate || '').split('-').map(Number);
            if (parts.length !== 3 || parts.some((n) => !Number.isFinite(n))) return '';
            const date = new Date(parts[0], parts[1] - 1, parts[2]);
            if (Number.isNaN(date.getTime())) return '';
            if (months) {
                const day = date.getDate();
                date.setDate(1);
                date.setMonth(date.getMonth() + Number(months));
                const last = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
                date.setDate(Math.min(day, last));
            }
            if (days) date.setDate(date.getDate() + Number(days));
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + d;
        },
        installmentStepDate(anchor, steps) {
            const unit = this.installmentIntervalUnit();
            const n = Number(steps || 0);
            if (n < 1) return anchor;
            if (unit === 'monthly') return this.shiftIso(anchor, 0, n);
            if (unit === 'quarterly') return this.shiftIso(anchor, 0, n * 3);
            if (unit === 'semiannual') return this.shiftIso(anchor, 0, n * 6);
            if (unit === 'annual') return this.shiftIso(anchor, 0, n * 12);
            return this.shiftIso(anchor, this.installmentIntervalDays() * n, 0);
        },
        syncInstallmentDates(forceFill = false) {
            const rows = this.installmentSchedule();
            const need = rows.length;
            let dates = [...(this.installmentDates || [])];
            if (dates.length < need) {
                dates = [...dates, ...Array(need - dates.length).fill('')];
            } else if (dates.length > need) {
                dates = dates.slice(0, need);
            }

            const unit = this.installmentIntervalUnit();
            const hasInterval = unit === 'days' ? this.installmentIntervalDays() > 0 : unit !== '';
            const hasDown = rows[0]?.kind === 'downpayment';
            const anchor = String(dates[0] || '').trim();

            if (hasInterval && anchor) {
                this.installmentAutoDating = true;
                rows.forEach((row, idx) => {
                    if (idx === 0) {
                        dates[row.dateIndex] = anchor;
                        return;
                    }
                    const steps = hasDown
                        ? Number(row.sequence || idx)
                        : Math.max(0, Number(row.sequence || (idx + 1)) - 1);
                    dates[row.dateIndex] = this.installmentStepDate(anchor, steps);
                });
                this.installmentDates = dates;
                this.$nextTick(() => { this.installmentAutoDating = false; });
                return;
            }

            if (forceFill || this.installmentDates.length !== need) {
                this.installmentDates = dates;
            }
        },
        openCashInstallment(atpId) {
            this.viewOpen = false;
            this.modalFullscreen = false;
            this.installmentAtpId = Number(atpId);
            this.installmentDownpayment = '';
            this.installmentDates = [];
            this.installmentCountInput = '';
            this.installmentTermId = '';
            const intervals = this.installmentBoot?.dayIntervals || [];
            this.installmentDayIntervalId = intervals.length ? String(intervals[0].id) : '';
            this.installmentOpen = true;
            this.$nextTick(() => {
                this.syncInstallmentDates(true);
                window.lucide && window.lucide.createIcons();
            });
        },
        closeCashInstallment() {
            this.installmentOpen = false;
            this.installmentAtpId = null;
            this.installmentPickerOpen = null;
            this.closeInstallmentTermSelect();
            this.closeInstallmentIntervalSelect();
        },
        csrfToken() {
            return document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                || document.querySelector('input[name=_token]')?.value
                || '';
        },
        termLabel(term) {
            if (!term) return 'Select term';
            return String(term.name || '');
        },
        intervalLabel(interval) {
            if (!interval) return 'Manual dates';
            return String(interval.name || '');
        },
        selectedTerm() {
            return (this.installmentBoot?.terms || []).find((t) => String(t.id) === String(this.installmentTermId)) || null;
        },
        filteredInstallmentTerms() {
            const q = String(this.installmentTermQuery || '').trim().toLowerCase();
            const list = this.installmentBoot?.terms || [];
            if (!q) return list;
            return list.filter((t) => this.termLabel(t).toLowerCase().includes(q) || String(t.months || '').includes(q));
        },
        filteredInstallmentIntervals() {
            const q = String(this.installmentIntervalQuery || '').trim().toLowerCase();
            const list = this.installmentBoot?.dayIntervals || [];
            if (!q) return list;
            return list.filter((i) => this.intervalLabel(i).toLowerCase().includes(q) || String(i.days || '').includes(q));
        },
        openInstallmentTermSelect() {
            this.closeInstallmentIntervalSelect();
            this.closeInstallmentPicker();
            this.installmentTermQuery = '';
            this.installmentNewTermMonths = '';
            this.installmentTermError = '';
            this.installmentTermSelectOpen = true;
        },
        closeInstallmentTermSelect() {
            this.installmentTermSelectOpen = false;
            this.installmentTermQuery = '';
            this.installmentTermError = '';
        },
        openInstallmentIntervalSelect() {
            this.closeInstallmentTermSelect();
            this.closeInstallmentPicker();
            this.installmentIntervalQuery = '';
            this.installmentNewIntervalDays = '';
            this.installmentIntervalError = '';
            this.installmentIntervalSelectOpen = true;
        },
        closeInstallmentIntervalSelect() {
            this.installmentIntervalSelectOpen = false;
            this.installmentIntervalQuery = '';
            this.installmentIntervalError = '';
        },
        pickInstallmentTerm(termId) {
            this.installmentTermId = String(termId || '');
            this.closeInstallmentTermSelect();
        },
        pickInstallmentInterval(intervalId) {
            this.installmentDayIntervalId = intervalId === null || intervalId === undefined || intervalId === ''
                ? ''
                : String(intervalId);
            this.closeInstallmentIntervalSelect();
        },
        async addInstallmentTermQuick() {
            if (this.installmentTermSaving) return;
            const months = Number(this.installmentNewTermMonths || this.installmentTermQuery || 0);
            if (!Number.isFinite(months) || months < 1 || months > 12) {
                this.installmentTermError = 'Enter months between 1 and 12.';
                return;
            }
            this.installmentTermSaving = true;
            this.installmentTermError = '';
            try {
                const res = await fetch(this.installmentBoot?.termQuickStoreUrl || '', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({ term_months: months }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.id) {
                    this.installmentTermError = (data.errors && data.errors.term_months && data.errors.term_months[0])
                        || data.message
                        || 'Could not add the term.';
                    return;
                }
                if (!this.installmentBoot.terms) this.installmentBoot.terms = [];
                if (!this.installmentBoot.terms.some((t) => String(t.id) === String(data.id))) {
                    this.installmentBoot.terms.push({
                        id: Number(data.id),
                        name: String(data.name || ''),
                        months: Number(data.months || months),
                    });
                    this.installmentBoot.terms.sort((a, b) => Number(a.months) - Number(b.months));
                }
                this.installmentNewTermMonths = '';
                this.pickInstallmentTerm(data.id);
            } catch (e) {
                this.installmentTermError = 'Could not add the term. Check your connection and try again.';
            } finally {
                this.installmentTermSaving = false;
            }
        },
        async addInstallmentIntervalQuick() {
            if (this.installmentIntervalSaving) return;
            const days = Number(this.installmentNewIntervalDays || this.installmentIntervalQuery || 0);
            if (!Number.isFinite(days) || days < 1 || days > 365) {
                this.installmentIntervalError = 'Enter days between 1 and 365.';
                return;
            }
            this.installmentIntervalSaving = true;
            this.installmentIntervalError = '';
            try {
                const res = await fetch(this.installmentBoot?.intervalQuickStoreUrl || '', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({ interval_days: days }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.id) {
                    this.installmentIntervalError = (data.errors && data.errors.interval_days && data.errors.interval_days[0])
                        || data.message
                        || 'Could not add the interval.';
                    return;
                }
                if (!this.installmentBoot.dayIntervals) this.installmentBoot.dayIntervals = [];
                if (!this.installmentBoot.dayIntervals.some((i) => String(i.id) === String(data.id))) {
                    this.installmentBoot.dayIntervals.push({
                        id: Number(data.id),
                        name: String(data.name || ''),
                        days: Number(data.days || days),
                    });
                    this.installmentBoot.dayIntervals.sort((a, b) => Number(a.days) - Number(b.days));
                }
                this.installmentNewIntervalDays = '';
                this.pickInstallmentInterval(data.id);
            } catch (e) {
                this.installmentIntervalError = 'Could not add the interval. Check your connection and try again.';
            } finally {
                this.installmentIntervalSaving = false;
            }
        },
        peso(amount) {
            return '₱' + Number(amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formatIsoDisplay(iso) {
            const parts = String(iso || '').split('-');
            if (parts.length !== 3) return '';
            return parts[2] + '/' + parts[1] + '/' + parts[0];
        },
        parseIsoParts(iso) {
            const parts = String(iso || '').split('-').map(Number);
            if (parts.length !== 3 || parts.some((n) => !Number.isFinite(n))) return null;
            return { y: parts[0], m: parts[1], d: parts[2] };
        },
        installmentDateLocked(dateIndex) {
            const unit = this.installmentIntervalUnit();
            const active = unit === 'days' ? this.installmentIntervalDays() > 0 : unit !== '';
            return Number(dateIndex) > 0 && active;
        },
        openInstallmentPicker(dateIndex) {
            if (this.installmentDateLocked(dateIndex)) return;
            const iso = String(this.installmentDates[dateIndex] || '');
            const parts = this.parseIsoParts(iso);
            const base = parts
                ? new Date(parts.y, parts.m - 1, parts.d)
                : new Date();
            this.installmentPickerMonth = new Date(base.getFullYear(), base.getMonth(), 1);
            this.installmentPickerOpen = dateIndex;
        },
        closeInstallmentPicker() {
            this.installmentPickerOpen = null;
        },
        shiftInstallmentPickerMonth(delta) {
            const current = this.installmentPickerMonth instanceof Date
                ? this.installmentPickerMonth
                : new Date();
            const next = new Date(current.getFullYear(), current.getMonth() + Number(delta || 0), 1);
            const today = new Date();
            const minMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            this.installmentPickerMonth = next < minMonth ? minMonth : next;
        },
        installmentPickerTitle() {
            const current = this.installmentPickerMonth instanceof Date
                ? this.installmentPickerMonth
                : new Date();
            return current.toLocaleString(undefined, { month: 'long', year: 'numeric' });
        },
        installmentPickerCells() {
            const current = this.installmentPickerMonth instanceof Date
                ? this.installmentPickerMonth
                : new Date();
            const year = current.getFullYear();
            const month = current.getMonth();
            const firstDow = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const todayIso = this.installmentTodayIso();
            const selectedIso = String(this.installmentDates[this.installmentPickerOpen] || '');
            const cells = [];
            for (let i = 0; i < firstDow; i++) {
                cells.push({ empty: true });
            }
            for (let day = 1; day <= daysInMonth; day++) {
                const iso = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                cells.push({
                    empty: false,
                    day,
                    iso,
                    selected: iso === selectedIso,
                    today: iso === todayIso,
                    past: iso < todayIso,
                });
            }
            return cells;
        },
        pickInstallmentDate(iso) {
            const index = this.installmentPickerOpen;
            if (index === null || index === undefined) return;
            if (this.isInstallmentDatePast(iso)) return;
            this.installmentAutoDating = true;
            const dates = [...(this.installmentDates || [])];
            while (dates.length <= index) dates.push('');
            dates[index] = iso;
            this.installmentDates = dates;
            this.installmentPickerOpen = null;
            this.$nextTick(() => {
                this.installmentAutoDating = false;
                this.syncInstallmentDates(false);
            });
        },
        clearInstallmentDate(dateIndex) {
            if (this.installmentDateLocked(dateIndex)) return;
            this.installmentAutoDating = true;
            const dates = [...(this.installmentDates || [])];
            while (dates.length <= dateIndex) dates.push('');
            dates[dateIndex] = '';
            this.installmentDates = dates;
            this.installmentPickerOpen = null;
            this.$nextTick(() => {
                this.installmentAutoDating = false;
                this.syncInstallmentDates(false);
            });
        },


        expandAtpGroup(key) {
            if (!key || this.expandedAtpGroups[key]) return;
            this.expandedAtpGroups = {
                ...this.expandedAtpGroups,
                [key]: true,
            };
            this.$nextTick(() => {
                if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
            });
        },

        toggleAtpGroup(key) {
            if (!key) return;
            this.expandedAtpGroups = {
                ...this.expandedAtpGroups,
                [key]: !this.expandedAtpGroups[key],
            };
            this.$nextTick(() => {
                if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
            });
        },

        openView(id) {
            this.selectedAtp = id;
            this.viewOpen = true;
            this.editOpen = false;
            this.modalFullscreen = false;
            this.bindViewDocSig(id);
        },

        lastSavedViewSig: {},

        bindViewDocSig(id) {
            this.$nextTick(() => {
                const key = 'atp-view-' + id;
                const slot = document.getElementById('purSigSlot-' + key);
                if (!slot || !window.purchaserDocumentSignature) return;
                const hidden = document.getElementById('purSigImage-' + key);
                if (this.lastSavedViewSig[id] === undefined) {
                    this.lastSavedViewSig[id] = hidden ? String(hidden.value || '') : '';
                }
                window.purchaserDocumentSignature.bind({
                    key: key,
                    hiddenId: 'purSigImage-' + key,
                    nameId: 'purSigName-' + key,
                    previewId: 'purSigOverlay-' + key,
                    slotId: 'purSigSlot-' + key,
                    title: 'Purchaser signature',
                    hint: 'Pick a saved signature, draw one, or upload. It overlays your printed name on the form above.',
                    onChange: (dataUrl) => this.saveViewAtpSignature(id, dataUrl)
                });
                if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
            });
        },

        saveViewAtpSignature(id, dataUrl) {
            const val = String(dataUrl || '');
            if (this.lastSavedViewSig[id] === val) return;
            this.lastSavedViewSig[id] = val;
            fetch('/{{ $pp ?? 'purchaser' }}/authority-to-purchase/' + id + '/received-by', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                },
                body: JSON.stringify({ authority_purchase_received_by_signature: val })
            }).catch(() => {});
        },

        openEdit(id) {
            this.selectedAtp = id;
            this.editOpen = true;
            this.viewOpen = false;
            this.modalFullscreen = false;
            this.bindDocSig('atp-' + id, 'Received by signature');
        },

        openCreate() {
            this.createOpen = true;
            this.modalFullscreen = false;
            this.bindDocSig('atp-create', 'Received by signature');
        },

        bindDocSig(key, title) {
            this.$nextTick(() => {
                if (window.purchaserDocumentSignature) {
                    const multiAtp = key === 'atp-create' && (this.atpSelectedKeys || []).length > 1;
                    const opts = {
                        key: key,
                        hiddenId: 'purSigImage-' + key,
                        nameId: 'purSigName-' + key,
                        previewId: 'purSigOverlay-' + key,
                        slotId: 'purSigSlot-' + key,
                        title: title || 'Purchaser signature',
                        hint: multiAtp
                            ? 'Sign once — this signature is applied to every ATP in this batch.'
                            : 'Pick a saved signature, draw one, or upload. It overlays your printed name on the form above.'
                    };
                    if (key === 'atp-create') {
                        opts.onChange = (dataUrl) => this.onCreateSignatureChange(dataUrl);
                    }
                    window.purchaserDocumentSignature.bind(opts);
                }
                if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
            });
        },

        onCreateSignatureChange(dataUrl) {
            const form = this.$refs.createForm;
            this.atpSharedSignature = dataUrl || '';
            this.atpSharedReceivedBy = form
                ? ((form.querySelector('[name=\'authority_purchase_received_by_name\']') || {}).value || '')
                : (this.atpSharedReceivedBy || '');
            this.propagateSignatureToAllAtps();
        },

        propagateSignatureToAllAtps() {
            const sig = this.atpSharedSignature || '';
            const name = this.atpSharedReceivedBy || '';
            const drafts = this.atpDrafts || {};
            Object.keys(drafts).forEach((k) => {
                if (!drafts[k]) return;
                drafts[k].signature = sig;
                if (name) drafts[k].received_by = name;
            });
        },

        applySharedSignatureToForm() {
            const form = this.$refs.createForm;
            if (!form) return;
            const sig = this.atpSharedSignature || '';
            const name = this.atpSharedReceivedBy || '';
            const sigEl = form.querySelector('[name=\'authority_purchase_received_by_signature\']');
            const nameEl = form.querySelector('[name=\'authority_purchase_received_by_name\']');
            if (sigEl) sigEl.value = sig;
            if (nameEl && name) nameEl.value = name;
            this.bindDocSig('atp-create', 'Received by signature');
        },

        closeAll() {
            this.createOpen = false;
            this.viewOpen = false;
            this.editOpen = false;
            this.emptyOpen = false;
            this.modalFullscreen = false;
            this.selectedAtp = null;
        },

        guardClose(kind) {
            const form = kind === 'create'
                ? this.$refs.createForm
                : document.querySelector('[data-pur-edit-form=atp][data-open=1]');
            const finish = () => {
                if (kind === 'create') this.createOpen = false;
                else this.editOpen = false;
                this.modalFullscreen = false;
            };
            if (window.purGuardFormClose) {
                window.purGuardFormClose(form, finish);
                return;
            }
            finish();
        },

        syncRisSplit() {
            const form = this.$refs.createForm;
            const option = this.$refs.risSelect ? this.$refs.risSelect.selectedOptions[0] : null;
            this.risSplit = !!(option && option.dataset.split === '1');
            const supplierSelect = form ? form.querySelector('[data-atp-supplier]') : null;
            if (supplierSelect) {
                if (supplierSelect.dataset.baseTitle === undefined) {
                    supplierSelect.dataset.baseTitle = supplierSelect.title || '';
                }
                supplierSelect.style.pointerEvents = this.risSplit ? 'none' : '';
                supplierSelect.tabIndex = this.risSplit ? -1 : 0;
                supplierSelect.title = this.risSplit ? 'Set by the selected RIS supplier' : supplierSelect.dataset.baseTitle;
            }
        },

        risChoices() {
            const tick = this.risChoiceTick;
            const select = this.$refs.risSelect;
            if (!select || tick === undefined) return [];
            const risId = this.currentRisId();
            if (!risId) return [];
            const selected = this.atpSelectedKeys || [];
            return Array.from(select.options).filter(function (opt) {
                return opt.dataset.risId === risId && opt.value;
            }).map(function (opt) {
                return {
                    key: opt.dataset.key,
                    label: opt.dataset.chip || opt.textContent.trim(),
                    active: selected.indexOf(opt.dataset.key) !== -1
                };
            });
        },

        currentRisId() {
            const select = this.$refs.risSelect;
            if (!select) return '';
            const fromKeys = (this.atpSelectedKeys || [])[0];
            if (fromKeys) {
                const opt = Array.from(select.options).find((o) => o.dataset.key === fromKeys);
                if (opt && opt.dataset.risId) return opt.dataset.risId;
            }
            const current = select.selectedOptions[0];
            return current ? (current.dataset.risId || '') : '';
        },

        currentAtpKey() {
            const keys = this.atpSelectedKeys || [];
            if (!keys.length) return '';
            const idx = Math.max(0, Math.min(this.atpCarouselIndex || 0, keys.length - 1));
            return keys[idx] || '';
        },

        currentAtpChipLabel() {
            const key = this.currentAtpKey();
            if (!key) return '';
            const choice = this.risChoices().find((c) => c.key === key);
            return choice ? choice.label : key;
        },

        bootAtpSelectionFromSelect() {
            const select = this.$refs.risSelect;
            if (!select) return;
            const option = select.selectedOptions[0];
            const key = option && option.value ? (option.dataset.key || option.value) : '';
            if (!key) {
                this.atpSelectedKeys = [];
                this.atpCarouselIndex = 0;
                return;
            }
            if (!(this.atpSelectedKeys || []).length) {
                this.atpSelectedKeys = [key];
                this.atpCarouselIndex = 0;
            }
            this.risChoiceTick = (this.risChoiceTick || 0) + 1;
            this.syncRisSplit();
        },

        onRisSelectChange(key) {
            this.atpDrafts = {};
            this.atpSelectedKeys = key ? [key] : [];
            this.atpCarouselIndex = 0;
            this.applyRisPrefill(key || '');
        },

        risPickerOptions() {
            const select = this.$refs.risSelect;
            if (!select) return [];
            return Array.from(select.options).filter(function (opt) {
                return !!opt.value;
            }).map(function (opt) {
                return {
                    key: opt.dataset.key || opt.value,
                    label: (opt.textContent || '').trim(),
                    chip: opt.dataset.chip || '',
                    risId: opt.dataset.risId || '',
                    split: opt.dataset.split === '1',
                };
            });
        },

        filteredRisPickerOptions() {
            const q = String(this.risPickerQuery || '').trim().toLowerCase();
            const opts = this.risPickerOptions();
            if (!q) return opts;
            return opts.filter(function (opt) {
                const hay = String(opt.label || '') + ' ' + String(opt.chip || '') + ' ' + String(opt.risId || '');
                return hay.toLowerCase().indexOf(q) !== -1;
            });
        },

        visibleRisPickerOptions() {
            const opts = this.filteredRisPickerOptions();
            if (this.risPickerShowAll) return opts;
            const limit = this.risPickerLimit || 10;
            return opts.slice(0, limit);
        },

        risPickerHiddenCount() {
            const total = this.filteredRisPickerOptions().length;
            const limit = this.risPickerLimit || 10;
            if (this.risPickerShowAll || total <= limit) return 0;
            return total - limit;
        },

        risPickerSelectedLabel() {
            const select = this.$refs.risSelect;
            const opt = select ? select.selectedOptions[0] : null;
            if (!opt || !opt.value) return 'Select approved RIS';
            return (opt.textContent || '').trim();
        },

        toggleRisPicker() {
            this.risPickerOpen = !this.risPickerOpen;
            if (this.risPickerOpen) {
                this.risPickerQuery = '';
                this.risPickerShowAll = false;
                this.$nextTick(() => {
                    const el = this.$refs.risPickerSearch;
                    if (el) el.focus({ preventScroll: true });
                });
            }
        },

        closeRisPicker() {
            this.risPickerOpen = false;
            this.risPickerQuery = '';
            this.risPickerShowAll = false;
        },

        pickRisOption(key) {
            const select = this.$refs.risSelect;
            if (!select) return;
            select.value = key || '';
            this.closeRisPicker();
            this.onRisSelectChange(key || '');
        },

        toggleAtpChoice(key) {
            if (!key) return;
            const select = this.$refs.risSelect;
            if (!select) return;
            const keys = Array.isArray(this.atpSelectedKeys) ? [...this.atpSelectedKeys] : [];
            const idx = keys.indexOf(key);
            if (idx !== -1) {
                if (keys.length === 1) return; // keep at least one ATP selected
                this.snapshotAtpDraft(this.currentAtpKey());
                keys.splice(idx, 1);
                this.atpSelectedKeys = keys;
                if (this.atpCarouselIndex >= keys.length) {
                    this.atpCarouselIndex = keys.length - 1;
                }
                this.goAtpSlide(this.atpCarouselIndex, true);
                return;
            }
            this.snapshotAtpDraft(this.currentAtpKey());
            keys.push(key);
            this.atpSelectedKeys = keys;
            this.goAtpSlide(keys.length - 1, true);
        },

        chooseRisOption(key) {
            this.toggleAtpChoice(key);
        },

        snapshotAtpDraft(key) {
            const form = this.$refs.createForm;
            if (!form || !key) return;
            if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') {
                window.purchaserDocumentSignature.flush();
            }
            const items = [];
            const rowsRoot = form.querySelector('[data-doc-rows]');
            const rowCount = rowsRoot ? rowsRoot.querySelectorAll('tr[data-doc-row]').length : 8;
            for (let i = 0; i < rowCount; i++) {
                items.push({
                    quantity: (form.querySelector('[name=\'items[' + i + '][quantity]\']') || {}).value || '',
                    unit: (form.querySelector('[name=\'items[' + i + '][unit]\']') || {}).value || '',
                    description: (form.querySelector('[name=\'items[' + i + '][description]\']') || {}).value || '',
                    unit_price: (form.querySelector('[name=\'items[' + i + '][unit_price]\']') || {}).value || '',
                    amount_display: (form.querySelector('[name=\'items[' + i + '][amount_display]\']') || {}).value || '',
                });
            }
            const supplierSelect = form.querySelector('[data-atp-supplier]');
            const locked = form.querySelector('input[data-locked-supplier]');
            const receivedBy = (form.querySelector('[name=\'authority_purchase_received_by_name\']') || {}).value || '';
            const signature = (form.querySelector('[name=\'authority_purchase_received_by_signature\']') || {}).value || '';
            this.atpDrafts[key] = {
                supplier_id: (locked && locked.value) || (supplierSelect && supplierSelect.value) || '',
                date: (form.querySelector('[name=\'authority_purchase_date\']') || {}).value || '',
                received_by: receivedBy,
                signature: signature,
                form_number: (form.querySelector('[name=\'authority_purchase_form_number\']') || {}).value || '',
                po_no: (form.querySelector('[name=\'authority_purchase_reference_po_no\']') || {}).value || '',
                items,
            };
            // One signature covers every ATP in the multi-supplier batch.
            this.atpSharedSignature = signature;
            this.atpSharedReceivedBy = receivedBy || this.atpSharedReceivedBy || '';
            this.propagateSignatureToAllAtps();
        },

        restoreAtpDraft(key) {
            if (!key) return;
            const draft = this.atpDrafts[key];
            if (!draft) {
                this.applyRisPrefill(key);
                return;
            }
            const form = this.$refs.createForm;
            if (!form) return;
            const select = this.$refs.risSelect;
            if (select) select.value = key;
            const risIdInput = this.$refs.risIdInput;
            const option = select ? select.selectedOptions[0] : null;
            if (risIdInput) {
                risIdInput.value = option && option.dataset.risId ? option.dataset.risId : '';
            }
            this.syncRisSplit();

            const setVal = (name, value) => {
                const el = form.querySelector('[name=\'' + name + '\']');
                if (el) {
                    el.value = value ?? '';
                    el.dispatchEvent(new Event('input'));
                    el.dispatchEvent(new Event('change'));
                }
            };
            setVal('authority_purchase_date', draft.date);
            setVal('authority_purchase_received_by_name', this.atpSharedReceivedBy || draft.received_by);
            setVal('authority_purchase_received_by_signature', this.atpSharedSignature || draft.signature);
            setVal('authority_purchase_form_number', draft.form_number);
            setVal('authority_purchase_reference_po_no', draft.po_no);

            if (draft.supplier_id) {
                const supplierSelect = form.querySelector('[data-atp-supplier]');
                if (supplierSelect) {
                    supplierSelect.disabled = false;
                    supplierSelect.value = draft.supplier_id;
                    supplierSelect.dispatchEvent(new Event('change'));
                }
            }

            const prefillItems = Array.isArray(draft.items) ? draft.items : [];
            const rowsRoot = form.querySelector('[data-doc-rows]');
            const rowTotal = (rowsRoot && window.docRows)
                ? window.docRows.fit(rowsRoot, prefillItems.length).length
                : Math.max(1, prefillItems.length);
            for (let i = 0; i < rowTotal; i++) {
                const item = prefillItems[i] || {};
                const qty = form.querySelector('[name=\'items[' + i + '][quantity]\']');
                const unit = form.querySelector('[name=\'items[' + i + '][unit]\']');
                const desc = form.querySelector('[name=\'items[' + i + '][description]\']');
                const price = form.querySelector('[name=\'items[' + i + '][unit_price]\']');
                const amount = form.querySelector('[name=\'items[' + i + '][amount_display]\']');
                if (qty) qty.value = item.quantity ?? '';
                if (unit) {
                    unit.value = item.unit ?? '';
                    unit.dispatchEvent(new Event('input'));
                }
                if (desc) desc.value = item.description ?? '';
                if (price) price.value = item.unit_price ?? '';
                if (amount) amount.value = item.amount_display ?? '';
            }
            const data = this.risPrefill[String(key)] || { supplier_id: draft.supplier_id, items: draft.items };
            this.lockFetchedRis(data);
            this.risChoiceTick = (this.risChoiceTick || 0) + 1;
            this.bindDocSig('atp-create', 'Received by signature');
        },

        goAtpSlide(index, skipSnapshot) {
            const keys = this.atpSelectedKeys || [];
            if (!keys.length) return;
            const next = Math.max(0, Math.min(index, keys.length - 1));
            if (!skipSnapshot) {
                this.snapshotAtpDraft(this.currentAtpKey());
            }
            this.atpCarouselIndex = next;
            const key = keys[next];
            const select = this.$refs.risSelect;
            if (select) select.value = key;
            this.restoreAtpDraft(key);
        },

        atpCarouselPrev() {
            if ((this.atpCarouselIndex || 0) <= 0) return;
            this.goAtpSlide(this.atpCarouselIndex - 1);
        },

        atpCarouselNext() {
            const max = (this.atpSelectedKeys || []).length - 1;
            if ((this.atpCarouselIndex || 0) >= max) return;
            this.goAtpSlide(this.atpCarouselIndex + 1);
        },

        async submitSelectedAtps(event) {
            const keys = this.atpSelectedKeys || [];
            const form = this.$refs.createForm;
            if (!form) return;
            if (keys.length <= 1) {
                return; // allow native submit
            }
            event.preventDefault();
            if (this.atpBatchSaving) return;
            this.atpBatchSaving = true;
            try {
                if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') {
                    window.purchaserDocumentSignature.flush();
                }
                this.snapshotAtpDraft(this.currentAtpKey());
                const saveAction = (form.querySelector('input[name=save_action]') || {}).value || 'draft';
                const action = form.getAttribute('action');
                const createdIds = [];
                let lastMessage = '';
                let lastRedirect = '';

                for (let i = 0; i < keys.length; i++) {
                    const key = keys[i];
                    this.goAtpSlide(i, true);
                    if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') {
                        window.purchaserDocumentSignature.flush();
                    }
                    const fd = new FormData(form);
                    fd.set('save_action', saveAction);
                    const res = await fetch(action, {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || data.ok === false) {
                        let msg = data.message || data.error || '';
                        if (!msg && data.errors) {
                            const first = Object.values(data.errors).flat();
                            msg = first[0] || '';
                        }
                        if (!msg) msg = 'Could not save ATP ' + (i + 1) + ' of ' + keys.length + '.';
                        if (window.purToast) window.purToast(msg, 'error');
                        else alert(msg);
                        this.atpBatchSaving = false;
                        return;
                    }
                    if (data.id) createdIds.push(data.id);
                    if (data.message) lastMessage = data.message;
                    if (data.redirect) lastRedirect = data.redirect;
                }

                const count = createdIds.length;
                const flash = lastMessage
                    || (count + ' Authority to Purchase draft' + (count === 1 ? '' : 's') + ' saved.');
                if (lastRedirect && count === 1) {
                    window.location = lastRedirect;
                    return;
                }
                const params = new URLSearchParams();
                if (createdIds[0]) {
                    params.set('view_atp', String(createdIds[0]));
                    if (saveAction === 'draft' && this.atpPurchaserOwns) {
                        params.set('choose_path', '1');
                    }
                }
                const base = window.location.pathname;
                window.location = base + (params.toString() ? ('?' + params.toString()) : '');
                try { sessionStorage.setItem('atp_flash_success', flash + (count > 1 ? ' (' + count + ' ATPs)' : '')); } catch (e) {}
            } catch (err) {
                if (window.purToast) window.purToast('Could not save the selected ATPs. Please try again.', 'error');
                else alert('Could not save the selected ATPs. Please try again.');
                this.atpBatchSaving = false;
            }
        },

        lockSupplierField(form, locked, supplierId) {
            const select = form.querySelector('[data-atp-supplier]');
            if (!select) return;
            select.disabled = !!locked;
            select.classList.toggle('cursor-not-allowed', !!locked);
            select.classList.toggle('!bg-gray-50', !!locked);
            select.title = locked ? 'Taken from the RIS' : (select.dataset.baseTitle || '');
            let hidden = form.querySelector('input[data-locked-supplier]');
            if (locked) {
                select.removeAttribute('name');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'authority_purchase_supplier_id';
                    hidden.setAttribute('data-locked-supplier', '');
                    select.insertAdjacentElement('afterend', hidden);
                }
                hidden.value = supplierId || select.value || '';
            } else {
                if (!select.getAttribute('name')) {
                    select.setAttribute('name', 'authority_purchase_supplier_id');
                }
                if (hidden) hidden.remove();
            }
        },

        lockFetchedRis(data) {
            const form = this.$refs.createForm;
            if (!form) return;
            const items = data && Array.isArray(data.items) ? data.items : [];
            this.lockSupplierField(form, !!(data && data.supplier_id), data ? data.supplier_id : '');
            const rows = form.querySelectorAll('tr[data-doc-row]');
            rows.forEach(function (row, index) {
                const locked = index < items.length;
                if (locked) row.setAttribute('data-doc-row-locked', '');
                else row.removeAttribute('data-doc-row-locked');
                row.querySelectorAll('input').forEach(function (input) {
                    const name = input.getAttribute('name') || '';
                    const isLine = name.indexOf('[quantity]') !== -1 || name.indexOf('[description]') !== -1 || name.indexOf('[unit_price]') !== -1;
                    if (!isLine) return;
                    input.readOnly = locked;
                    input.classList.toggle('cursor-not-allowed', locked);
                    input.classList.toggle('!bg-gray-50', locked);
                    input.title = locked ? 'Taken from the RIS' : '';
                });
                const unitButton = row.querySelector('button');
                if (unitButton) {
                    unitButton.disabled = locked;
                    unitButton.classList.toggle('cursor-not-allowed', locked);
                    unitButton.title = locked ? 'Taken from the RIS' : '';
                }
            });
        },

        applyRisPrefill(key) {
            const select = this.$refs.risSelect;
            if (select && key) {
                select.value = key;
            }
            const option = select ? select.selectedOptions[0] : null;
            const risIdInput = this.$refs.risIdInput;
            if (risIdInput) {
                risIdInput.value = option && option.dataset.risId ? option.dataset.risId : '';
            }
            if (key && (!(this.atpSelectedKeys || []).length || (this.atpSelectedKeys || []).indexOf(key) === -1)) {
                // Single-path / first load: seed carousel selection.
                if (!(this.atpSelectedKeys || []).length) {
                    this.atpSelectedKeys = key ? [key] : [];
                    this.atpCarouselIndex = 0;
                }
            }
            this.risChoiceTick = (this.risChoiceTick || 0) + 1;
            this.syncRisSplit();
            const data = this.risPrefill[String(key)];
            const form = this.$refs.createForm;
            if (!form || !data) {
                if (form) this.lockFetchedRis(null);
                this.applySharedSignatureToForm();
                return;
            }

            if (data.supplier_id) {
                const supplierSelect = form.querySelector('[data-atp-supplier]');
                if (supplierSelect) {
                    supplierSelect.disabled = false;
                    supplierSelect.value = data.supplier_id;
                    supplierSelect.dispatchEvent(new Event('change'));
                }
            }

            const prefillItems = Array.isArray(data.items) ? data.items : [];
            const rowsRoot = form.querySelector('[data-doc-rows]');
            const rowTotal = (rowsRoot && window.docRows)
                ? window.docRows.fit(rowsRoot, prefillItems.length).length
                : 8;
            for (let i = 0; i < rowTotal; i++) {
                const item = prefillItems[i] || {};
                const qty = form.querySelector('[name=\'items[' + i + '][quantity]\']');
                const unit = form.querySelector('[name=\'items[' + i + '][unit]\']');
                const desc = form.querySelector('[name=\'items[' + i + '][description]\']');
                const price = form.querySelector('[name=\'items[' + i + '][unit_price]\']');
                const amount = form.querySelector('[name=\'items[' + i + '][amount_display]\']');

                if (qty) qty.value = item.quantity ?? '';
                if (unit) {
                    unit.value = item.unit ?? '';
                    unit.dispatchEvent(new Event('input'));
                }
                if (desc) desc.value = item.description ?? '';
                if (price) price.value = item.unit_price ?? '';
                if (amount) {
                    const quantity = parseFloat(item.quantity || 0);
                    const unitPrice = parseFloat(item.unit_price || 0);
                    amount.value = quantity && unitPrice ? (quantity * unitPrice).toFixed(2) : '';
                }
            }
            this.lockFetchedRis(data);
            this.applySharedSignatureToForm();
        },

        printAtp(id) {
            const sheetId = id === 'blank' ? 'atp-print-blank' : ('atp-print-' + id);
            if (window.purchaserPrintSheet) {
                window.purchaserPrintSheet(sheetId, 'atp-print-active');
                return;
            }
            const sheet = document.getElementById(sheetId);
            if (sheet) {
                document.querySelectorAll('.atp-print-sheet').forEach(function (el) {
                    el.classList.remove('atp-print-active');
                });
                sheet.classList.add('atp-print-active');
            }
            window.print();
        }
    }"
    x-init="
        if (createOpen) {
            $nextTick(() => {
                bindDocSig('atp-create', 'Received by signature');
                const risOption = $refs.risSelect ? $refs.risSelect.selectedOptions[0] : null;
                if ({{ old('authority_purchase_ris_id') !== null ? 'true' : 'false' }}) {
                    bootAtpSelectionFromSelect();
                    syncRisSplit();
                    if (risOption && risOption.dataset.key && risPrefill[risOption.dataset.key]) {
                        lockFetchedRis(risPrefill[risOption.dataset.key]);
                    }
                } else if ('{{ $selectedRisId ?? '' }}' && risOption && risOption.dataset.key) {
                    atpSelectedKeys = [risOption.dataset.key];
                    atpCarouselIndex = 0;
                    applyRisPrefill(risOption.dataset.key);
                } else {
                    bootAtpSelectionFromSelect();
                }
            });
        }
        if (editOpen && selectedAtp) {
            $nextTick(() => bindDocSig('atp-' + selectedAtp, 'Received by signature'));
        }
    "
    @keydown.escape.window="if (window.atpSubmitOpen || document.getElementById('purConfirmOverlay')) return; if (createOpen) { guardClose('create'); } else if (editOpen) { guardClose('edit'); } else { closeAll(); }"
    class="space-y-6"
>

    {{-- ========================================================= --}}
    {{-- FLASH MESSAGES --}}
    {{-- ========================================================= --}}

    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="pur-tabs !mb-0" aria-label="ATP list view">
            <a
                href="{{ route(($pp ?? 'purchaser').'.atp.index') }}"
                class="pur-tab {{ !$archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="file-stack" class="h-3.5 w-3.5"></i>
                Active
            </a>
            <a
                href="{{ route(($pp ?? 'purchaser').'.atp.index', ['view' => 'archive']) }}"
                class="pur-tab {{ $archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                Archive
            </a>
        </nav>

                <div class="flex flex-wrap items-center gap-2">
            @include('layouts.partials.export-pdf-button', [
                'exportUrl' => route(($pp ?? 'purchaser').'.atp.export-pdf', array_filter([
                    'view' => !empty($archiveView) ? 'archive' : null,
                    'search' => request('search') ?: null,
                    'status' => request('status') ?: null,
                ])),
            ])
            @unless($archiveView)
                @if($atpCanCreate)
                    <button
                        type="button"
                        @click="emptyOpen = true; modalFullscreen = false; $nextTick(() => window.lucide && window.lucide.createIcons())"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-[13px] font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print Empty ATP
                    </button>
                    @if($atpIsSupplyPortal)
                        <button
                            type="button"
                            @click="openCreate()"
                            class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
                        >
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            Create ATP
                        </button>
                    @endif
                @endif
            @endunless
        </div>
    </div>

    @unless($archiveView)
        @if(\App\Support\ProcurementPortal::isSupplyOfficerContext())
            @include('partials.draft-handover', ['type' => 'atp'])
        @endif
    @endunless
    @php
        $handoverOutgoing = \App\Support\DraftHandover::outgoing('atp');
        $handoverDeclined = \App\Support\DraftHandover::declinedForSender('atp');
        $atpFormLabel = fn ($atp) => $atp->authority_purchase_form_number ?: ('ATP #'.$atp->authority_purchase_id);
        $directPaymentConfirm = fn ($atp) => 'Pay '.$atpFormLabel($atp).' through Direct Payment? Accounting will process payment to the supplier. Pipeline ends at Receiving Report — no liquidation. This can\'t be changed once the funding request is created.';
        $cashInstallmentConfirm = fn ($atp) => 'Pay '.$atpFormLabel($atp).' through Cash Installment? Accounting will process payment to the supplier. Pipeline ends at Receiving Report — no liquidation. This can\'t be changed once the funding request is created.';
        $cashAdvanceConfirm = fn ($atp) => 'Pay '.$atpFormLabel($atp).' through a Cash Advance? Accounting will release cash to you, and you must liquidate it with receipts after receiving. Pipeline ends at Liquidation Report. This can\'t be changed once the cash advance request is created.';
    @endphp

    {{-- SUMMARY CARDS --}}

    {{-- ATP RECORDS --}}
    <div class="pur-card">
        @if(!empty($attentionFocus))
            <div class="px-5 pt-5">
                @include('partials.attention-focus-chip', ['focus' => $attentionFocus, 'total' => $atps->total()])
            </div>
        @endif
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">
                            {{ $archiveView ? 'Archived ATP' : 'ATP Records' }}
                        </h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            {{ $atps->total() }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $archiveView ? 'Stored Authority to Purchase records.' : 'Search and manage Authority to Purchase records.' }}
                    </p>
                </div>

                <form data-auto-filter data-list-panel="#purchaser-atp-list-panel" method="GET" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    @if($archiveView)
                        <input type="hidden" name="view" value="archive">
                    @endif
                    @if(!empty($attentionFocus))
                        <input type="hidden" name="focus" value="{{ request('focus') }}">
                    @endif

                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search ATP, RIS, supplier..."
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>

                    <select name="status" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All statuses</option>
                        <option value="Reviewed" {{ request('status') === 'Reviewed' ? 'selected' : '' }}>Reviewed</option>
                        <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
                        <option value="Minor Revision" {{ request('status') === 'Minor Revision' ? 'selected' : '' }}>For revision</option>
                        <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>

                    <button
                        type="submit"
                        class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold leading-none text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="filter" class="h-4 w-4 shrink-0"></i>
                        Apply
                    </button>

                    @if(request()->filled('search') || request()->filled('status'))
                        <a
                            href="{{ $archiveView ? route(($pp ?? 'purchaser').'.atp.index', ['view' => 'archive']) : route(($pp ?? 'purchaser').'.atp.index') }}"
                            class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50"
                        >
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div id="purchaser-atp-list-panel" data-list-panel-root class="relative">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ATP / Group</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RIS</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @php
                        $atpGroups = $atpGroups ?? collect();
                    @endphp
                    @forelse($atpGroups as $group)
                        @foreach(($group['atps'] ?? collect()) as $atp)
                            @include('purchaser.authority-to-purchase._list-row', [
                                'atp' => $atp,
                                'isChildRow' => false,
                                'fallbackRequestNo' => $group['request_no'] ?? null,
                            ])
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-400">
                                    <i data-lucide="file-check-2" class="h-5 w-5"></i>
                                </div>
                                <p class="mt-4 font-medium text-gray-700">No ATP records found</p>
                                <p class="mt-1 text-sm text-gray-400">Create an ATP or adjust the current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($atps->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $atps->links() }}
            </div>
        @endif
        </div>
    </div>





    {{-- ========================================================= --}}
    {{-- CREATE ATP MODAL (teleport to body so overlay covers topbar) --}}
    {{-- ========================================================= --}}

    <template x-teleport="body">
    <div
        x-show="createOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
            :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
        x-effect="window.purDialog && window.purDialog.sync(createOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="atp-create-title"
    >
        <div
            x-on:click.self="guardClose('create')"
            class="flex min-h-full w-full justify-center"
        >
            <div
                @click.stop
                class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
            >
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                            <i data-lucide="file-check-2" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="atp-create-title" class="text-lg font-semibold tracking-tight text-slate-900">Create Authority to Purchase</h3>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @include('purchaser.partials.modal-fullscreen-button')
                        <button type="button" @click="guardClose('create')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>

                @if(empty($risOptions))
                    <div class="space-y-2 p-6 text-sm text-gray-600">
                        @if(!empty($selectedRisId))
                            <p>
                                This Canvass + RIS already has an Authority to Purchase (or is no longer available for a new ATP).
                                Open it from the ATP list, or go back to <strong>Incoming RIS</strong> for RIS that still need an ATP.
                            </p>
                        @else
                            <p>
                                No approved Canvass + RIS is waiting for ATP.
                                Use <strong>Incoming RIS</strong> after the Purchaser sends an approved request, or use <strong>Print Empty ATP</strong> for a blank paper form.
                            </p>
                        @endif
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 md:px-6">
                        <button type="button" @click="guardClose('create')" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">
                            Close
                        </button>
                    </div>
                @else
                <form
                    id="atp-create-form"
                    method="POST"
                    action="{{ route(($pp ?? 'purchaser').'.atp.store') }}"
                    x-ref="createForm"
                    x-on:submit="
                        if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') {
                            window.purchaserDocumentSignature.flush();
                        }
                        if ((atpSelectedKeys || []).length > 1) {
                            submitSelectedAtps($event);
                        }
                    "
                >
                    @csrf
                    <input type="hidden" name="save_action" value="draft">

                    <div class="bg-slate-100 p-3 md:p-5">
                            @php
                                $oldRisId = old('authority_purchase_ris_id');
                                $oldSupplierId = old('authority_purchase_supplier_id');
                                $selectedRisKey = null;
                                foreach ($risOptions as $opt) {
                                    if ($oldRisId !== null) {
                                        if ((string) $oldRisId === (string) $opt['ris_id']
                                            && (!$opt['split'] || (string) $oldSupplierId === (string) $opt['supplier_id'])) {
                                            $selectedRisKey = $opt['key'];
                                            break;
                                        }
                                    } elseif (!empty($selectedRisId) && (string) $selectedRisId === (string) $opt['ris_id']) {
                                        $selectedRisKey ??= $opt['key'];
                                        if (empty($selectedSupplierId) || (string) $selectedSupplierId === (string) $opt['supplier_id']) {
                                            $selectedRisKey = $opt['key'];
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            <div class="relative mb-4">
                                @php
                                    $selectedRisNumeric = '';
                                    foreach ($risOptions as $opt) {
                                        if ($selectedRisKey === $opt['key']) {
                                            $selectedRisNumeric = $opt['ris_id'];
                                            break;
                                        }
                                    }
                                @endphp
                                <label class="text-xs font-medium text-gray-500">
                                    Approved RIS <span class="text-red-500">*</span>
                                    <span class="font-normal text-gray-400">(required to create)</span>
                                </label>
                                <input type="hidden" name="authority_purchase_ris_id" x-ref="risIdInput" value="{{ old('authority_purchase_ris_id', $selectedRisNumeric) }}">
                                <select
                                    x-ref="risSelect"
                                    required
                                    tabindex="-1"
                                    aria-hidden="true"
                                    class="pointer-events-none absolute left-0 top-0 h-px w-px overflow-hidden opacity-0"
                                    x-on:change="onRisSelectChange($event.target.selectedOptions[0]?.dataset.key || '')"
                                >
                                    <option value="">Select approved RIS</option>
                                    @foreach($risOptions as $opt)
                                        <option
                                            value="{{ $opt['key'] }}"
                                            data-key="{{ $opt['key'] }}"
                                            data-ris-id="{{ $opt['ris_id'] }}"
                                            data-chip="{{ $opt['chip'] }}"
                                            data-split="{{ $opt['split'] ? '1' : '0' }}"
                                            {{ $selectedRisKey === $opt['key'] ? 'selected' : '' }}
                                        >{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                                <div class="relative mt-1" x-on:keydown.escape.window="if (risPickerOpen) closeRisPicker()">
                                    <button
                                        type="button"
                                        x-on:click="toggleRisPicker()"
                                        x-bind:aria-expanded="risPickerOpen ? 'true' : 'false'"
                                        class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 text-left text-sm text-slate-800 shadow-sm transition hover:border-slate-400"
                                    >
                                        <span
                                            class="min-w-0 flex-1 truncate"
                                            x-bind:class="($refs.risSelect && $refs.risSelect.value) ? 'text-slate-800' : 'text-slate-400'"
                                            x-text="risPickerSelectedLabel()"
                                        ></span>
                                        <svg class="h-4 w-4 shrink-0 text-slate-400 transition" x-bind:class="risPickerOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                                        </svg>
                                    </button>
                                    <div
                                        x-show="risPickerOpen"
                                        x-cloak
                                        x-transition
                                        x-on:click.outside="closeRisPicker()"
                                        class="absolute left-0 right-0 top-full z-[80] mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                                    >
                                        <div class="border-b border-slate-100 p-2">
                                            <div class="relative">
                                                <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.3-4.3m1.8-5.2a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                                                </svg>
                                                <input
                                                    type="text"
                                                    x-ref="risPickerSearch"
                                                    x-model="risPickerQuery"
                                                    x-on:input="risPickerShowAll = false"
                                                    x-on:keydown.escape.stop="closeRisPicker()"
                                                    placeholder="Search RIS, item, or supplier..."
                                                    class="h-9 w-full rounded-lg border border-slate-200 bg-slate-50 py-1.5 pl-8 pr-3 text-sm text-slate-800 outline-none focus:border-slate-300 focus:bg-white"
                                                >
                                            </div>
                                        </div>
                                        <ul class="max-h-64 overflow-y-auto py-1">
                                            <li>
                                                <button
                                                    type="button"
                                                    x-on:click="pickRisOption('')"
                                                    class="flex w-full px-3 py-2 text-left text-sm text-slate-500 hover:bg-slate-50"
                                                    x-bind:class="!($refs.risSelect && $refs.risSelect.value) ? 'bg-slate-50 font-medium text-slate-800' : ''"
                                                >
                                                    Select approved RIS
                                                </button>
                                            </li>
                                            <template x-for="opt in visibleRisPickerOptions()" :key="opt.key">
                                                <li>
                                                    <button
                                                        type="button"
                                                        x-on:click="pickRisOption(opt.key)"
                                                        class="flex w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-slate-50"
                                                        x-bind:class="($refs.risSelect && $refs.risSelect.value === opt.key) ? 'bg-blue-50 font-medium text-[#0025cc]' : ''"
                                                    >
                                                        <span class="min-w-0 flex-1 break-words" x-text="opt.label"></span>
                                                    </button>
                                                </li>
                                            </template>
                                            <li
                                                x-show="filteredRisPickerOptions().length === 0"
                                                class="px-3 py-3 text-sm text-slate-400"
                                            >
                                                No matches
                                            </li>
                                            <li x-show="risPickerHiddenCount() > 0" class="border-t border-slate-100">
                                                <button
                                                    type="button"
                                                    x-on:click="risPickerShowAll = true"
                                                    class="flex w-full items-center justify-between gap-2 px-3 py-2.5 text-left text-sm font-medium text-[#0025cc] hover:bg-slate-50"
                                                >
                                                    <span>
                                                        Show
                                                        <span x-text="risPickerHiddenCount()"></span>
                                                        more
                                                    </span>
                                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                                                    </svg>
                                                </button>
                                            </li>
                                            <li x-show="risPickerShowAll && filteredRisPickerOptions().length > (risPickerLimit || 10)" class="border-t border-slate-100">
                                                <button
                                                    type="button"
                                                    x-on:click="risPickerShowAll = false"
                                                    class="flex w-full px-3 py-2.5 text-left text-sm font-medium text-slate-600 hover:bg-slate-50"
                                                >
                                                    Show less
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div x-show="risSplit && risChoices().length > 1" x-cloak class="mt-2 flex flex-wrap gap-2">
                                    <template x-for="choice in risChoices()" :key="choice.key">
                                        <button
                                            type="button"
                                            x-on:click="toggleAtpChoice(choice.key)"
                                            x-bind:aria-pressed="choice.active ? 'true' : 'false'"
                                            x-bind:class="choice.active ? 'border-[#0025cc] bg-blue-50 text-[#0025cc] ring-1 ring-[#0025cc]/40' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'"
                                            class="inline-flex max-w-full items-center gap-1.5 rounded-lg border px-3 py-1.5 text-left text-xs font-semibold transition"
                                        >
                                            <span
                                                class="inline-flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded border"
                                                x-bind:class="choice.active ? 'border-[#0025cc] bg-[#0025cc] text-white' : 'border-slate-300 bg-white text-transparent'"
                                                aria-hidden="true"
                                            >
                                                <svg class="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.5 6.5 5 9l4.5-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </span>
                                            <span class="min-w-0 whitespace-normal break-words" x-text="choice.label"></span>
                                        </button>
                                    </template>
                                </div>
                                <p x-show="risSplit" x-cloak class="mt-1.5 text-xs text-slate-500">
                                    This RIS buys from more than one supplier, so each selected supplier gets its own ATP.
                                    Select one or more above — a carousel opens for each ATP.
                                    Sign once below and it applies to all selected ATPs.
                                </p>
                            </div>

                        <div x-show="!risSplit || (atpSelectedKeys || []).length">
                            <div
                                x-show="risSplit && (atpSelectedKeys || []).length > 1"
                                x-cloak
                                class="mb-3 flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm"
                            >
                                <button
                                    type="button"
                                    x-on:click="atpCarouselPrev()"
                                    x-bind:disabled="atpCarouselIndex <= 0 || atpBatchSaving"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                    aria-label="Previous ATP"
                                >
                                    <i data-lucide="chevron-left" class="h-4 w-4"></i>
                                </button>
                                <div class="min-w-0 flex-1 text-center">
                                    <p class="text-xs font-semibold text-slate-800">
                                        ATP
                                        <span x-text="(atpCarouselIndex || 0) + 1"></span>
                                        of
                                        <span x-text="(atpSelectedKeys || []).length"></span>
                                    </p>
                                    <p class="truncate text-[11px] text-slate-500" x-text="currentAtpChipLabel()"></p>
                                </div>
                                <button
                                    type="button"
                                    x-on:click="atpCarouselNext()"
                                    x-bind:disabled="atpCarouselIndex >= ((atpSelectedKeys || []).length - 1) || atpBatchSaving"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                    aria-label="Next ATP"
                                >
                                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                                </button>
                            </div>

                            @include('partials.authority-to-purchase-paper', [
                                'editable' => true,
                                'atp' => null,
                                'items' => collect(),
                                'suppliers' => $suppliers,
                                'signKey' => 'atp-create',
                                'suggestedAtpFormNumber' => $suggestedAtpFormNumber ?? '0001',
                            ])

                            <div
                                x-show="risSplit && (atpSelectedKeys || []).length > 1"
                                x-cloak
                                class="mt-3 flex items-center justify-center gap-1.5"
                            >
                                <template x-for="(key, idx) in (atpSelectedKeys || [])" :key="'dot-' + key">
                                    <button
                                        type="button"
                                        x-on:click="goAtpSlide(idx)"
                                        x-bind:aria-label="'Go to ATP ' + (idx + 1)"
                                        x-bind:class="idx === atpCarouselIndex ? 'bg-[#0025cc] w-5' : 'bg-slate-300 hover:bg-slate-400 w-2.5'"
                                        class="h-2.5 rounded-full transition-all"
                                    ></button>
                                </template>
                            </div>

                            <div id="purSigSlot-atp-create" class="mt-4 w-full"></div>
                            <p
                                x-show="risSplit && (atpSelectedKeys || []).length > 1"
                                x-cloak
                                class="mt-2 text-xs text-slate-500"
                            >
                                Signature applies to all
                                <span x-text="(atpSelectedKeys || []).length"></span>
                                ATPs in this batch.
                            </p>
                        </div>
                    </div>

                    <div data-atp-form-errors hidden></div>
                    <div data-atp-form-footer class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 md:px-6">
                        <button type="button" @click="guardClose('create')" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            onclick="this.form.querySelector('input[name=save_action]').value='draft'"
                            x-bind:disabled="atpBatchSaving || (risSplit && !(atpSelectedKeys || []).length)"
                            class="{{ $atpPurchaserOwns ? 'rounded-lg bg-[#0025cc] px-5 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-50' : 'rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-50' }}"
                        >
                            <span x-show="!atpBatchSaving">
                                <span x-show="(atpSelectedKeys || []).length > 1" x-cloak>
                                    {{ $atpPurchaserOwns ? 'Save' : \App\Support\ProcurementDraftLabel::SAVE }}
                                    <span x-text="(atpSelectedKeys || []).length"></span> ATPs
                                    @if($atpPurchaserOwns)
                                        &amp; Choose Path
                                    @endif
                                </span>
                                <span x-show="(atpSelectedKeys || []).length <= 1">
                                    {{ $atpPurchaserOwns ? 'Save & Choose Payment Path' : \App\Support\ProcurementDraftLabel::SAVE }}
                                </span>
                            </span>
                            <span x-show="atpBatchSaving" x-cloak>Saving ATPs…</span>
                        </button>
                        @if($atpIsSupplyPortal)
                            <button
                                type="button"
                                data-atp-submit
                                data-atp-edit-form="atp-create-form"
                                data-atp-action="{{ route(($pp ?? 'purchaser').'.atp.store') }}"
                                data-atp-number="this new ATP"
                                x-bind:disabled="atpBatchSaving || ((atpSelectedKeys || []).length > 1)"
                                x-bind:title="(atpSelectedKeys || []).length > 1 ? 'Save selected ATPs as drafts first, then submit each one' : ''"
                                class="rounded-lg bg-[#0025cc] px-5 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-50"
                            >
                                Submit to School Administrator
                            </button>
                        @endif
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
    </template>



    {{-- ========================================================= --}}
    {{-- VIEW ATP MODALS (paper preview, same direction as RIS) --}}
    {{-- ========================================================= --}}

    @foreach($atps as $atp)

        @php
            $items = $atpItems->get($atp->authority_purchase_id, collect());
        @endphp

        <template x-teleport="body">
        <div
            x-show="viewOpen && selectedAtp === {{ $atp->authority_purchase_id }}"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
            :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
            x-effect="window.purDialog && window.purDialog.sync(viewOpen && selectedAtp === {{ $atp->authority_purchase_id }}, $el)"
            @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
            role="dialog"
            aria-modal="true"
            aria-labelledby="atp-view-title-{{ $atp->authority_purchase_id }}"
        >
            <div
                x-on:click.self="viewOpen = false; modalFullscreen = false"
                class="flex min-h-full w-full justify-center"
            >
                <div
                    @click.stop
                    class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                    :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
                >
                    {{-- Header --}}
                    <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">

                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    x-show="canGoBack"
                                    x-cloak
                                    @click="window.history.back()"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-700 transition hover:border-slate-300 hover:bg-gray-50"
                                    data-tooltip="Back"
                                    aria-label="Back to previous page"
                                >
                                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                                </button>
                                <h3 id="atp-view-title-{{ $atp->authority_purchase_id }}" class="text-xl font-semibold text-slate-900">
                                    {{ $atp->authority_purchase_form_number ?: '—' }}
                                </h3>

                                @include('partials.ris-urgency-badge', [
                                    'urgent' => \App\Support\DocumentUrgency::isUrgent('ATP', $atp),
                                    'title' => 'Inherited from linked RIS',
                                ])
                                @include('accounting.partials.status-badge', [
                                    'status' => \App\Support\RisWorkflow::atpStatusLabel($atp),
                                    'submitted' => $atp->authority_purchase_submitted_at,
                                    'revision' => $atp->authority_purchase_rejection_reason,
                                ])
                            </div>

                            <p class="mt-1 text-sm text-gray-500">
                                RIS: {{ \App\Support\RisWorkflow::formNumber($atp, (int) ($atp->authority_purchase_ris_id ?? 0)) }}
                            </p>
                            @if($atp->authority_purchase_rejection_reason)
                                <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                    <p>
                                        {{ $atp->authority_purchase_status === 'Rejected' ? 'Rejection reason:' : 'Revision requested:' }}
                                        {{ $atp->authority_purchase_rejection_reason }}
                                    </p>
                                    @include('partials.ris-revision-images', [
                                        'revision' => \App\Support\DocumentRevisionNotes::latest('ATP', $atp->authority_purchase_id),
                                        'routeName' => ($pp ?? 'purchaser').'.document-revision-image',
                                        'size' => 'sm',
                                    ])
                                </div>
                            @endif
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5">
                            <button
                                type="button"
                                @click="printAtp({{ $atp->authority_purchase_id }})"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-700 transition hover:border-slate-300 hover:bg-gray-50"
                                data-tooltip="Print ATP"
                                aria-label="Print ATP"
                            >
                                <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                            </button>
                            <a
                                href="{{ route(($pp ?? 'purchaser').'.atp.export-xlsx', $atp->authority_purchase_id) }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 transition hover:border-emerald-300 hover:bg-emerald-50"
                                data-tooltip="Export to Excel"
                                aria-label="Export to Excel"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                    <path fill="#185C37" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                                    <path fill="#21A366" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                                    <path fill="#107C41" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                                    <path fill="#FFF" d="M7.35 21.35 9.9 16.75l-2.4-4.5h1.85l1.5 3.15c.14.3.24.53.31.72h.04c.08-.22.19-.47.33-.76l1.55-3.11h1.7l-2.48 4.52 2.55 4.68h-1.82l-1.7-3.45c-.09-.18-.16-.35-.21-.52h-.04c-.05.18-.12.36-.22.55l-1.74 3.42H7.35z"/>
                                </svg>
                            </a>
                            <a
                                href="{{ route(($pp ?? 'purchaser').'.atp.export-docx', $atp->authority_purchase_id) }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 transition hover:border-blue-300 hover:bg-blue-50"
                                data-tooltip="Export to Word file"
                                aria-label="Export to Word file"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                    <path fill="#185ABD" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                                    <path fill="#4CA1FF" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                                    <path fill="#2B7CD3" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                                    <path fill="#FFF" d="m6.55 21.2 1.45-6.55h1.55l.9 4.35c.08.4.14.74.18 1.02h.04c.05-.28.12-.62.22-1.02l1.05-4.35h1.45l1.1 4.35c.09.37.16.71.21 1.02h.04c.04-.28.11-.64.21-1.05l.95-4.32h1.48L15.4 21.2h-1.55l-1.05-4.2c-.08-.32-.14-.64-.18-.95h-.04c-.04.32-.11.64-.2.98l-1.1 4.17H9.7l-1.05-4.2c-.08-.33-.14-.64-.18-.95h-.03c-.04.3-.11.62-.2.95l-1.08 4.2H6.55z"/>
                                </svg>
                            </a>
                            @include('purchaser.partials.modal-fullscreen-button')
                            <button type="button" @click="viewOpen = false; modalFullscreen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>

                    </div>

                    @php
                        $atpIdKey = (int) $atp->authority_purchase_id;
                        $atpLineage = ($atpLineages[$atpIdKey] ?? null)
                            ?: \App\Support\DocumentLineage::forAtp($atpIdKey);
                        $atpHint = \App\Support\DocumentLineage::reviewHint(
                            \App\Support\RisWorkflow::atpStatusLabel($atp),
                            null,
                            'atp'
                        );
                    @endphp
                    @include('partials.document-lineage', [
                        'lineage' => $atpLineage,
                        'currentType' => 'ATP',
                        'statusHint' => $atpHint,
                    ])

                    {{-- ATP PAPER PREVIEW --}}
                    <div class="bg-slate-100 p-3 md:p-5">
                        @include('partials.authority-to-purchase-paper', [
                            'editable' => false,
                            'editableReceivedBy' => !$archiveView && !$atpIsSupplyPortal,
                            'receivedBySaveUrl' => route(($pp ?? 'purchaser').'.atp.received-by', $atp->authority_purchase_id),
                            'atp' => $atp,
                            'items' => $items,
                            'printId' => 'atp-print-'.$atp->authority_purchase_id,
                        ])
                        @if(!$archiveView && !$atpIsSupplyPortal)
                            <div id="purSigSlot-atp-view-{{ $atp->authority_purchase_id }}" class="mx-auto mt-4 w-full max-w-[1095px]"></div>
                        @endif
                    </div>

                    {{-- Footer Actions --}}
                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            type="button"
                            @click="viewOpen = false"
                            class="px-2 py-2 text-sm font-medium text-gray-500 transition hover:text-gray-950"
                        >
                            Close
                        </button>

                        @php
                            $viewFundingPath = \App\Support\ProcurementPaymentPath::normalize($atp->authority_purchase_payment_path ?? null);
                            $viewOpenFundingSubmit = $atpPurchaserOwns
                                && $atpCanCreate
                                && ! $archiveView
                                && ! $atp->authority_purchase_submitted_at
                                && in_array($atp->authority_purchase_status, ['Draft', 'Pending'], true)
                                && empty($atp->submits_via_po)
                                && ! empty($atp->can_joint_submit)
                                && ! empty($atp->has_rfc)
                                && $viewFundingPath;
                            $viewCanDirectSubmit = $atpCanCreate
                                && ! $archiveView
                                && ! $atp->authority_purchase_submitted_at
                                && in_array($atp->authority_purchase_status, ['Draft', 'Pending'], true)
                                && empty($atp->submits_via_po)
                                && (! $atpPurchaserOwns || $atpIsSupplyPortal);
                        @endphp
                        @if($viewOpenFundingSubmit)
                            <a
                                href="{{ route(($pp ?? 'purchaser').'.rfc.index', [
                                    'selected_atp' => (int) $atp->authority_purchase_id,
                                    'funding_type' => $viewFundingPath,
                                    'fund' => $viewFundingPath,
                                ]) }}"
                                class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
                            >
                                Submit on {{ \App\Support\ProcurementPaymentPath::label($viewFundingPath) }}
                            </a>
                        @elseif($viewCanDirectSubmit)
                            <button
                                type="button"
                                data-atp-submit
                                data-atp-action="{{ route(($pp ?? 'purchaser').'.atp.submit', $atp->authority_purchase_id) }}"
                                data-atp-number="{{ $atp->authority_purchase_form_number ?: (\App\Support\ProcurementDraftLabel::untitledWithId('atp', $atp->authority_purchase_id)) }}"
                                data-atp-signature="{{ \App\Support\RisWorkflow::isDrawnSignature((string) ($atp->authority_purchase_received_by_signature ?? '')) ? $atp->authority_purchase_received_by_signature : '' }}"
                                class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
                            >
                                Submit for Review
                            </button>
                        @endif

                        @if(!$archiveView && !empty($atp->can_release_to_purchaser))
                            <form method="POST" action="{{ route('supply-officer.atp.release-purchaser', $atp->authority_purchase_id) }}">
                                @csrf
                                <button
                                    type="submit"
                                    data-pur-confirm="Release {{ $atp->authority_purchase_form_number ?: 'ATP #'.$atp->authority_purchase_id }} (with approved Canvass + RIS) back to the Purchaser for Request for Check?"
                                    data-pur-confirm-title="Release to Purchaser"
                                    data-pur-confirm-ok="Release"
                                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                                >
                                    Release to Purchaser
                                </button>
                            </form>
                        @elseif(
                            !$archiveView
                            && !$atp->has_rfc
                            && !\App\Support\ProcurementPortal::isSupplyOfficerContext()
                            && (
                                ($atpPurchaserOwns && in_array($atp->authority_purchase_status, ['Draft', 'Pending'], true) && empty($atp->authority_purchase_submitted_at))
                                || ($atp->authority_purchase_status === 'Approved' && ($atpPurchaserOwns || !empty($atp->released_to_purchaser)))
                            )
                        )
                            @if(false)
                                <a href="#" class="hidden">PO</a>
                            @elseif(!$atp->has_rfc)
                                @if(empty($atp->authority_purchase_payment_path))
                                    <form
                                        method="POST"
                                        action="{{ route(($pp ?? 'purchaser').'.atp.payment-path', $atp->authority_purchase_id) }}"
                                        data-pur-confirm="{{ $directPaymentConfirm($atp) }}"
                                        data-pur-confirm-title="Use Direct Payment?"
                                        data-pur-confirm-ok="Confirm"
                                    >
                                        @csrf
                                        <input type="hidden" name="authority_purchase_payment_path" value="{{ \App\Support\ProcurementPaymentPath::DIRECT_PAYMENT }}">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-800 transition hover:bg-blue-100"
                                            data-pur-tip-title="Direct Payment"
                                            data-pur-tip="Accounting pays the supplier directly. Opens the funding request form for this ATP."
                                            data-pur-tip-note="Ends at Receiving Report — no liquidation."
                                            data-pur-tip-accent="#60a5fa"
                                        >Direct Payment</button>
                                    </form>
                                    <form
                                        method="POST"
                                        action="{{ route(($pp ?? 'purchaser').'.atp.payment-path', $atp->authority_purchase_id) }}"
                                        data-pur-confirm="{{ $cashInstallmentConfirm($atp) }}"
                                        data-pur-confirm-title="Use Cash Installment?"
                                        data-pur-confirm-ok="Confirm"
                                    >
                                        @csrf
                                        <input type="hidden" name="authority_purchase_payment_path" value="{{ \App\Support\ProcurementPaymentPath::CASH_INSTALLMENT }}">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-lg border border-violet-200 bg-violet-50 px-4 py-2 text-sm font-medium text-violet-800 transition hover:bg-violet-100"
                                            data-pur-tip-title="Cash Installment"
                                            data-pur-tip="Accounting pays the supplier. Opens the funding request form for this ATP."
                                            data-pur-tip-note="Ends at Receiving Report — no liquidation."
                                            data-pur-tip-accent="#a78bfa"
                                        >Cash Installment</button>
                                    </form>
                                    <form
                                        method="POST"
                                        action="{{ route(($pp ?? 'purchaser').'.atp.payment-path', $atp->authority_purchase_id) }}"
                                        data-pur-confirm="{{ $cashAdvanceConfirm($atp) }}"
                                        data-pur-confirm-title="Use Cash Advance?"
                                        data-pur-confirm-ok="Confirm"
                                    >
                                        @csrf
                                        <input type="hidden" name="authority_purchase_payment_path" value="{{ \App\Support\ProcurementPaymentPath::CASH_ADVANCE }}">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-lg border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-medium text-sky-800 transition hover:bg-sky-100"
                                            data-pur-tip-title="Cash Advance"
                                            data-pur-tip="Accounting releases cash to you to buy the items. Opens the cash advance form for this ATP."
                                            data-pur-tip-note="Ends at Liquidation Report — liquidate with receipts."
                                            data-pur-tip-accent="#38bdf8"
                                        >Cash Advance</button>
                                    </form>
                                @else
                                    @php
                                        $pathLabel = \App\Support\ProcurementPaymentPath::label($atp->authority_purchase_payment_path);
                                        $pathKey = \App\Support\ProcurementPaymentPath::normalize($atp->authority_purchase_payment_path)
                                            ?? $atp->authority_purchase_payment_path;
                                    @endphp
                                    <a
                                        href="{{ route(($pp ?? 'purchaser').'.rfc.index', ['selected_atp' => $atp->authority_purchase_id, 'funding_type' => $pathKey, 'fund' => $pathKey]) }}"
                                        data-pur-confirm="Create a {{ $pathLabel }} Request for Check for {{ $atp->authority_purchase_form_number ?: ('ATP #'.$atp->authority_purchase_id) }}? The RFC form will open with this ATP already selected."
                                        data-pur-confirm-title="Create {{ $pathLabel }}"
                                        data-pur-confirm-ok="Create {{ $pathLabel }}"
                                        class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
                                    >
                                        Create {{ $pathLabel }}
                                    </a>
                                @endif
                            @else
                                <span class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-700">
                                    RFC Created
                                </span>
                            @endif
                        @endif
                    </div>

                </div>
            </div>
        </div>
        </template>

    @endforeach

    {{-- PRINT EMPTY ATP MODAL --}}
    <template x-teleport="body">
    <div
        x-cloak
        x-show="emptyOpen"
        class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
        :class="modalFullscreen ? 'p-0' : 'p-4 md:p-8'"
        x-effect="window.purDialog && window.purDialog.sync(emptyOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="atp-empty-title"
    >
        <div
            x-on:click.self="emptyOpen = false; modalFullscreen = false"
            class="flex min-h-full w-full justify-center"
        >
            <div
                class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
            >

                <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 id="atp-empty-title" class="text-lg font-semibold text-gray-900">
                            Print Empty ATP
                        </h3>
                        <p class="mt-0.5 text-xs text-gray-500">Blank paper only. System ATPs must link an approved RIS.</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @include('purchaser.partials.modal-fullscreen-button')
                        <button
                            type="button"
                            x-on:click="emptyOpen = false; modalFullscreen = false"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                            aria-label="Close"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>

                <div class="bg-slate-100 p-3 md:p-5">
                    @include('partials.authority-to-purchase-paper', [
                        'editable' => false,
                        'atp' => null,
                        'items' => collect(),
                        'printId' => 'atp-print-blank',
                    ])
                </div>

                <div class="print-hidden flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <button
                        type="button"
                        x-on:click="emptyOpen = false"
                        class="px-2 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950"
                    >
                        Cancel
                    </button>
                    <a
                        href="{{ route(($pp ?? 'purchaser').'.atp.export-blank-xlsx') }}"
                        data-tooltip="Export to Excel"
                        aria-label="Export to Excel"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 transition hover:border-emerald-300"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                            <path fill="#185C37" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                            <path fill="#21A366" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                            <path fill="#107C41" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                            <path fill="#FFF" d="M7.35 21.35 9.9 16.75l-2.4-4.5h1.85l1.5 3.15c.14.3.24.53.31.72h.04c.08-.22.19-.47.33-.76l1.55-3.11h1.7l-2.48 4.52 2.55 4.68h-1.82l-1.7-3.45c-.09-.18-.16-.35-.21-.52h-.04c-.05.18-.12.36-.22.55l-1.74 3.42H7.35z"/>
                        </svg>
                    </a>
                    <a
                        href="{{ route(($pp ?? 'purchaser').'.atp.export-blank-docx') }}"
                        data-tooltip="Export to Word file"
                        aria-label="Export to Word file"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 transition hover:border-blue-300"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                            <path fill="#185ABD" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                            <path fill="#4CA1FF" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                            <path fill="#2B7CD3" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                            <path fill="#FFF" d="m6.55 21.2 1.45-6.55h1.55l.9 4.35c.08.4.14.74.18 1.02h.04c.05-.28.12-.62.22-1.02l1.05-4.35h1.45l1.1 4.35c.09.37.16.71.21 1.02h.04c.04-.28.11-.64.21-1.05l.95-4.32h1.48L15.4 21.2h-1.55l-1.05-4.2c-.08-.33-.14-.64-.18-.95h-.04c-.04.32-.11.64-.2.98l-1.1 4.17H9.7l-1.05-4.2c-.08-.33-.14-.64-.18-.95h-.03c-.04.3-.11.62-.2.95l-1.08 4.2H6.55z"/>
                        </svg>
                    </a>
                    <button
                        type="button"
                        @click="printAtp('blank')"
                        class="flex items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2 text-[13px] font-medium text-white hover:bg-blue-800"
                    >
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print Empty ATP
                    </button>
                </div>

            </div>
        </div>
    </div>
    </template>



    {{-- ========================================================= --}}
    {{-- EDIT ATP MODALS (editable paper layout) --}}
    {{-- ========================================================= --}}

    @foreach($atps as $atp)

        @php
            $editItems = $atpItems->get($atp->authority_purchase_id, collect());
        @endphp

        @if(!$atp->authority_purchase_submitted_at && $atp->authority_purchase_status === 'Pending')

            <template x-teleport="body">
            <div
                x-show="editOpen && selectedAtp === {{ $atp->authority_purchase_id }}"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
            :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
                x-effect="window.purDialog && window.purDialog.sync(editOpen && selectedAtp === {{ $atp->authority_purchase_id }}, $el)"
                @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
                role="dialog"
                aria-modal="true"
                aria-labelledby="atp-edit-title-{{ $atp->authority_purchase_id }}"
            >
                <div
                    x-on:click.self="guardClose('edit')"
                    class="flex min-h-full w-full justify-center"
                >
                    <div
                        @click.stop
                        class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                        :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
                    >
                        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                            <div>
                                <h3 id="atp-edit-title-{{ $atp->authority_purchase_id }}" class="text-xl font-semibold text-slate-900">{{ \App\Support\ProcurementDraftLabel::editTitle('atp') }}</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ $atp->authority_purchase_form_number ?: '—' }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                @include('purchaser.partials.modal-fullscreen-button')
                                <button type="button" @click="guardClose('edit')" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>

                        <form
                            id="atp-edit-form-{{ $atp->authority_purchase_id }}"
                            method="POST"
                            action="{{ route(($pp ?? 'purchaser').'.atp.update', $atp->authority_purchase_id) }}"
                            data-pur-edit-form="atp"
                            x-bind:data-open="editOpen && selectedAtp === {{ $atp->authority_purchase_id }} ? '1' : '0'"
                            x-on:submit="if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') { window.purchaserDocumentSignature.flush(); }"
                        >

                            @csrf
                            @method('PUT')
                            <input type="hidden" name="save_action" value="save">

                            <div class="bg-slate-100 p-3 md:p-5">
                                @include('partials.authority-to-purchase-paper', [
                                    'editable' => true,
                                    'atp' => $atp,
                                    'items' => $editItems,
                                    'suppliers' => $suppliers,
                                    'signKey' => 'atp-'.$atp->authority_purchase_id,
                                ])
                                <div id="purSigSlot-atp-{{ $atp->authority_purchase_id }}" class="mt-4 w-full"></div>
                            </div>

                            <div data-atp-form-errors hidden></div>
                            <div data-atp-form-footer class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                                <!--<p class="mr-auto max-w-md text-xs text-gray-500">
                                    @if($atpIsSupplyPortal)
                                        {{ \App\Support\ProcurementDraftLabel::SAVE }}, or choose a School Administrator and submit this ATP.
                                    @else
                                        Save your changes here (including signature), then use <strong>⋮ → Submit to School Administrator</strong> in the list.
                                    @endif
                                </p>-->
                                <button
                                    type="button"
                                    @click="guardClose('edit')"
                                    class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-950"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    onclick="this.form.querySelector('input[name=save_action]').value='save'"
                                    class="rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                                >
                                    Save Changes
                                </button>
                                @php
                                    $editFundingPath = \App\Support\ProcurementPaymentPath::normalize($atp->authority_purchase_payment_path ?? null);
                                    $editOpenFundingSubmit = $atpPurchaserOwns
                                        && ! empty($atp->can_joint_submit)
                                        && ! empty($atp->has_rfc)
                                        && $editFundingPath;
                                @endphp
                                @if($editOpenFundingSubmit)
                                    <a
                                        href="{{ route(($pp ?? 'purchaser').'.rfc.index', [
                                            'selected_atp' => (int) $atp->authority_purchase_id,
                                            'funding_type' => $editFundingPath,
                                            'fund' => $editFundingPath,
                                        ]) }}"
                                        class="rounded-lg bg-[#0025cc] px-5 py-2 text-sm font-medium text-white hover:bg-blue-800"
                                    >
                                        Submit on {{ \App\Support\ProcurementPaymentPath::label($editFundingPath) }}
                                    </a>
                                @elseif($atpCanCreate && (! $atpPurchaserOwns || $atpIsSupplyPortal))
                                    <button
                                        type="button"
                                        data-atp-submit
                                        data-atp-edit-form="atp-edit-form-{{ $atp->authority_purchase_id }}"
                                        data-atp-action="{{ route(($pp ?? 'purchaser').'.atp.submit', $atp->authority_purchase_id) }}"
                                        data-atp-number="{{ $atp->authority_purchase_form_number ?: (\App\Support\ProcurementDraftLabel::untitledWithId('atp', $atp->authority_purchase_id)) }}"
                                        data-atp-signature="{{ \App\Support\RisWorkflow::isDrawnSignature((string) ($atp->authority_purchase_received_by_signature ?? '')) ? $atp->authority_purchase_received_by_signature : '' }}"
                                        class="rounded-lg bg-[#0025cc] px-5 py-2 text-sm font-medium text-white hover:bg-blue-800"
                                    >
                                        Submit to School Administrator
                                    </button>
                                @endif
                            </div>

                        </form>

                    </div>
                </div>
            </div>
            </template>

        @endif

    @endforeach

    <div id="purDocSignatureDock" class="hidden" aria-hidden="true">
        @include('purchaser.partials.document-signature-panel', ['savedSignatures' => $savedSignatures ?? collect()])
    </div>


</div>

@include('purchaser.authority-to-purchase._submit-modal', ['savedSignatures' => $savedSignatures ?? collect()])


<style>
    [x-cloak] {
        display: none !important;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        .atp-print-active {
            background: #fff !important;
        }
    }
</style>

@include('partials.ris-revision-image-viewer')
@endsection
