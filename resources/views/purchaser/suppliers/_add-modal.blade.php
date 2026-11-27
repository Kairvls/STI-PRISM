{{--
  Shared Add Supplier modal (suppliers index + RIS supplier dropdown).
  Expects Alpine: addSupplierModal, supplierType
  Optional: $formAction, $modalZ, $fromRis
--}}
@php
    $formAction = $formAction ?? route(($pp ?? 'purchaser').'.suppliers.store');
    $modalZ = $modalZ ?? 'z-[200]';
    $fromRis = (bool) ($fromRis ?? false);
    $ajaxSubmit = (bool) ($ajaxSubmit ?? false);
    $openOnErrors = $errors->any() && ! old('_editing_supplier_id') && (! $fromRis || old('_from_ris'));
@endphp
<template x-teleport="body">
    <div
        x-cloak
        x-show="addSupplierModal"
        x-transition.opacity
        x-effect="if (addSupplierModal) { $nextTick(() => { window.lucide && window.lucide.createIcons(); const phone = document.getElementById('supplier-contact-number'); if (phone && window.refreshPrismPhoneInput) window.refreshPrismPhoneInput(phone); }); }"
        x-on:keydown.escape.window="if (addSupplierModal) addSupplierModal = false"
        class="fixed inset-0 {{ $modalZ }} flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
        role="dialog"
        aria-modal="true"
        aria-labelledby="add-supplier-title"
    >
        <div
            x-on:click.self="addSupplierModal = false"
            class="flex min-h-full w-full justify-center"
        >
            <div class="my-auto flex h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                                <i data-lucide="building-2" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 id="add-supplier-title" class="text-lg font-semibold tracking-tight text-gray-950">
                                    Add Supplier
                                </h3>
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        x-on:click="addSupplierModal = false"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col" novalidate @if($ajaxSubmit) @submit.prevent="submitQuickSupplier($event)" @endif>
                    @csrf
                    @if ($fromRis)
                        <input type="hidden" name="_from_ris" value="1">
                    @endif
                    <input
                        type="hidden"
                        name="supplier_store_type"
                        value="{{ old('supplier_store_type', 'Physical Store') }}"
                        x-bind:value="supplierType"
                    >

                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5 md:px-6">
                        @if($ajaxSubmit)
                            <div x-cloak x-show="supplierModalError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                                <p class="font-semibold">Could not save supplier</p>
                                <p class="mt-1 text-xs" x-text="supplierModalError"></p>
                            </div>
                        @endif
                        @if($openOnErrors)
                            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                                <p class="font-semibold">Could not save supplier</p>
                                <ul class="mt-1.5 list-disc space-y-0.5 pl-4">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- TYPE SELECTOR --}}
                        <div>
                            <p class="mb-2 text-sm font-medium text-gray-700">Supplier Type <span class="text-red-500">*</span></p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <button
                                    type="button"
                                    x-on:click="supplierType = 'Physical Store'; $nextTick(() => window.lucide && window.lucide.createIcons())"
                                    class="flex items-start gap-3 rounded-xl border p-4 text-left transition"
                                    :class="supplierType === 'Physical Store'
                                        ? 'border-[#0025cc] bg-[#0025cc] text-white shadow-sm'
                                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'"
                                >
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                        :class="supplierType === 'Physical Store' ? 'bg-white/15 text-white' : 'bg-gray-100 text-gray-600'"
                                    >
                                        <i data-lucide="store" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold">Physical Store</p>
                                        <p class="mt-0.5 text-xs" :class="supplierType === 'Physical Store' ? 'text-white/70' : 'text-gray-500'">
                                            Walk-in shop with a street address
                                        </p>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    x-on:click="supplierType = 'Online Store'; $nextTick(() => window.lucide && window.lucide.createIcons())"
                                    class="flex items-start gap-3 rounded-xl border p-4 text-left transition"
                                    :class="supplierType === 'Online Store'
                                        ? 'border-[#0025cc] bg-[#0025cc] text-white shadow-sm'
                                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'"
                                >
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                        :class="supplierType === 'Online Store' ? 'bg-white/15 text-white' : 'bg-gray-100 text-gray-600'"
                                    >
                                        <i data-lucide="globe" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold">Online Store</p>
                                        <p class="mt-0.5 text-xs" :class="supplierType === 'Online Store' ? 'text-white/70' : 'text-gray-500'">
                                            Marketplace or website seller
                                        </p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        {{-- CONTACT DETAILS --}}
                        <div
                            class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
                            x-data="{ onlinePlatform: @js((string) old('app_used', '')) }"
                        >
                            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/80 px-4 py-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-slate-200">
                                    <i data-lucide="id-card" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Supplier details</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    @include('purchaser.suppliers._logo-field', [
                                        'inputId' => 'add-supplier-logo',
                                    ])
                                </div>

                                <div :class="supplierType === 'Online Store' ? '' : 'sm:col-span-2'">
                                    <label class="mb-1.5 block text-xs font-medium text-slate-600">
                                        <span x-text="supplierType === 'Online Store' ? 'Shop / Store Name' : 'Company Name'">Company Name</span>
                                        <span style="color:#dc2626;">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="supplier_name"
                                        value="{{ old('supplier_name', old('company_name', old('shop_name'))) }}"
                                        required
                                        autocomplete="off"
                                        maxlength="{{ \App\Support\InputLimits::NAME }}"
                                        :placeholder="supplierType === 'Online Store' ? 'e.g. Octagon Official Store' : 'e.g. Wilcon Depot Ormoc'"
                                        class="box-border h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#0025cc]"
                                    >
                                </div>

                                <template x-if="supplierType === 'Online Store'">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-medium text-slate-600">
                                            Primary Platform <span style="color:#dc2626;">*</span>
                                        </label>
                                        <select
                                            name="app_used"
                                            x-model="onlinePlatform"
                                            required
                                            class="box-border h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-[#0025cc]"
                                        >
                                            <option value="">Select platform</option>
                                            @foreach(['Shopee', 'Lazada', 'Facebook', 'TikTok Shop', 'Website', 'Other'] as $platform)
                                                <option value="{{ $platform }}" @selected(old('app_used') === $platform)>
                                                    {{ $platform }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </template>

                                <div class="sm:col-span-2">
                                    <label class="mb-1.5 block text-xs font-medium text-slate-600">
                                        Payee / Check Name <span class="font-normal text-slate-400">(optional)</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="supplier_check_name"
                                        value="{{ old('supplier_check_name') }}"
                                        autocomplete="off"
                                        maxlength="{{ \App\Support\InputLimits::NAME }}"
                                        :placeholder="supplierType === 'Online Store' ? 'e.g. Octagon Computer Superstore' : 'e.g. Wilcon Depot, Inc.'"
                                        class="box-border h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#0025cc]"
                                    >
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Contact Person</label>
                                    <input
                                        type="text"
                                        name="contact_person"
                                        value="{{ old('contact_person') }}"
                                        autocomplete="off"
                                        maxlength="{{ \App\Support\InputLimits::NAME }}"
                                        placeholder="e.g. Juan Dela Cruz"
                                        class="box-border h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#0025cc]"
                                    >
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Email Address</label>
                                    <input
                                        type="email"
                                        name="email_address"
                                        value="{{ old('email_address') }}"
                                        autocomplete="off"
                                        maxlength="{{ \App\Support\InputLimits::EMAIL }}"
                                        placeholder="name@example.com"
                                        class="box-border h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#0025cc]"
                                    >
                                </div>

                                <div :class="supplierType === 'Online Store' ? 'sm:col-span-2' : ''">
                                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Mobile / Contact Number</label>
                                    @include('partials.phone-input', [
                                        'name' => 'contact_number',
                                        'value' => old('contact_number'),
                                        'id' => 'supplier-contact-number',
                                    ])
                                </div>

                                <template x-if="supplierType === 'Physical Store'">
                                    <div
                                        x-init="$nextTick(() => { const el = document.getElementById('supplier-landline-number'); if (el && window.initPrismLandlineInput) window.initPrismLandlineInput(el); window.lucide && window.lucide.createIcons(); })"
                                    >
                                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Landline Number</label>
                                        @include('partials.landline-input', [
                                            'name' => 'landline_number',
                                            'value' => old('landline_number'),
                                            'id' => 'supplier-landline-number',
                                            'placeholder' => '(0XX) XXX-XXXX',
                                        ])
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- PHYSICAL LOCATION & HOURS --}}
                        <template x-if="supplierType === 'Physical Store'">
                            <div
                                class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
                                x-init="$nextTick(() => window.lucide && window.lucide.createIcons())"
                            >
                                <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/80 px-4 py-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-slate-200">
                                        <i data-lucide="map-pin" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Physical location &amp; hours</p>
                                    </div>
                                </div>
                                <div class="space-y-4 p-4">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Company Address</label>
                                        <textarea
                                            name="company_address"
                                            rows="2"
                                            autocomplete="off"
                                            maxlength="{{ \App\Support\InputLimits::NOTES }}"
                                            placeholder="Street, barangay, city..."
                                            class="box-border w-full resize-none rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#0025cc]"
                                        >{{ old('company_address') }}</textarea>
                                    </div>
                                    <div
                                        x-data="{
                                            days: '',
                                            openTime: '',
                                            closeTime: '',
                                            fmtTime(val) {
                                                if (!val || !val.includes(':')) return '';
                                                const parts = val.split(':');
                                                let h = parseInt(parts[0], 10);
                                                const m = parts[1];
                                                if (isNaN(h)) return '';
                                                const ap = h >= 12 ? 'PM' : 'AM';
                                                h = h % 12 || 12;
                                                return h + ':' + m + ' ' + ap;
                                            },
                                            formatted() {
                                                const open = this.fmtTime(this.openTime);
                                                const close = this.fmtTime(this.closeTime);
                                                const timePart = (open && close) ? (open + ' – ' + close) : (open || close);
                                                if (this.days && timePart) return this.days + ', ' + timePart;
                                                return this.days || timePart || '';
                                            },
                                            clearHours() {
                                                this.days = '';
                                                this.openTime = '';
                                                this.closeTime = '';
                                            }
                                        }"
                                    >
                                        <div class="mb-1.5 flex items-center justify-between gap-2">
                                            <label class="block text-xs font-medium text-slate-600">
                                                Operating Hours <span class="font-normal text-slate-400">(optional)</span>
                                            </label>
                                            <button
                                                type="button"
                                                x-show="days || openTime || closeTime"
                                                @click="clearHours()"
                                                class="text-[11px] font-semibold text-slate-400 transition hover:text-rose-600"
                                            >
                                                Clear
                                            </button>
                                        </div>
                                        <input type="hidden" name="operating_hours" :value="formatted()">
                                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                                            <select
                                                x-model="days"
                                                class="box-border h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-[#0025cc]"
                                            >
                                                <option value="">Days (e.g. Mon–Sat)</option>
                                                <option value="Mon–Fri">Mon–Fri</option>
                                                <option value="Mon–Sat">Mon–Sat</option>
                                                <option value="Daily (Mon–Sun)">Daily (Mon–Sun)</option>
                                                <option value="Tue–Sun">Tue–Sun</option>
                                            </select>
                                            <div class="relative">
                                                <input
                                                    type="time"
                                                    x-model="openTime"
                                                    aria-label="Opening time"
                                                    title="Opening time"
                                                    class="box-border h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-[#0025cc]"
                                                >
                                            </div>
                                            <div class="relative">
                                                <input
                                                    type="time"
                                                    x-model="closeTime"
                                                    aria-label="Closing time"
                                                    title="Closing time"
                                                    class="box-border h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-[#0025cc]"
                                                >
                                            </div>
                                        </div>
                                        <p x-show="formatted()" x-cloak class="mt-1.5 text-[11px] font-medium text-slate-500">
                                            Saved as: <span class="font-semibold text-slate-700" x-text="formatted()"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- ONLINE INFORMATION --}}
                        <template x-if="supplierType === 'Online Store'">
                            <div
                                class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
                                x-init="$nextTick(() => window.lucide && window.lucide.createIcons())"
                            >
                                <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/80 px-4 py-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-slate-200">
                                        <i data-lucide="shopping-bag" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Store link &amp; ID <span class="font-normal text-xs text-slate-400">(optional)</span></p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Store URL</label>
                                        <input
                                            type="url"
                                            name="store_url"
                                            value="{{ old('store_url') }}"
                                            autocomplete="off"
                                            maxlength="500"
                                            placeholder="https://shopee.ph/shop/..."
                                            class="box-border h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#0025cc]"
                                        >
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Seller / Store ID</label>
                                        <input
                                            type="text"
                                            name="seller_id"
                                            value="{{ old('seller_id') }}"
                                            autocomplete="off"
                                            maxlength="100"
                                            placeholder="Platform seller or store ID"
                                            class="box-border h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#0025cc]"
                                        >
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex shrink-0 justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 md:px-6">
                        <button
                            type="button"
                            x-on:click="addSupplierModal = false"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
                        >
                            <i data-lucide="check" class="h-4 w-4"></i>
                            Save Supplier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
