{{--
  Shared Add Supplier modal (suppliers index + RIS supplier dropdown).
  Expects Alpine: addSupplierModal, supplierType
  Optional: $formAction, $modalZ, $fromRis
--}}
@php
    $formAction = $formAction ?? route(($pp ?? 'purchaser').'.suppliers.store');
    $modalZ = $modalZ ?? 'z-[200]';
    $fromRis = (bool) ($fromRis ?? false);
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

                <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col" novalidate>
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
                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                            <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/80 px-4 py-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-gray-200">
                                    <i data-lucide="id-card" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Supplier details</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2">
                                <div class="sm:col-span-2" style="order: 0">
                                    @include('purchaser.suppliers._logo-field', [
                                        'inputId' => 'add-supplier-logo',
                                    ])
                                </div>

                                <div class="sm:col-span-2" style="order: 1">
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">
                                        Company / Shop Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="supplier_name"
                                        value="{{ old('supplier_name', old('company_name', old('shop_name'))) }}"
                                        required
                                        maxlength="{{ \App\Support\InputLimits::NAME }}"
                                        placeholder="Enter supplier name"
                                        class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                    >
                                </div>

                                <div class="sm:col-span-2" style="order: 2">
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Check Name</label>
                                    <input
                                        type="text"
                                        name="supplier_check_name"
                                        value="{{ old('supplier_check_name') }}"
                                        maxlength="{{ \App\Support\InputLimits::NAME }}"
                                        placeholder="e.g. Wilcon Depot Ormoc for Computer"
                                        class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                    >
                                    <p class="mt-1 text-[11px] text-slate-400">Printed as the Payee on Direct Payment and Cash Installment. Leave blank to use the supplier name.</p>
                                </div>

                                <div style="order: 3">
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Contact Person</label>
                                    <input
                                        type="text"
                                        name="contact_person"
                                        value="{{ old('contact_person') }}"
                                        maxlength="{{ \App\Support\InputLimits::NAME }}"
                                        placeholder="Enter contact person"
                                        class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                    >
                                </div>

                                <div
                                    x-bind:style="supplierType === 'Physical Store' ? 'order: 3' : 'order: 5'"
                                    x-bind:class="supplierType === 'Online Store' ? 'sm:col-span-2' : ''"
                                >
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Email Address</label>
                                    <input
                                        type="email"
                                        name="email_address"
                                        value="{{ old('email_address') }}"
                                        maxlength="{{ \App\Support\InputLimits::EMAIL }}"
                                        placeholder="name@example.com"
                                        class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                    >
                                </div>

                                <div
                                    x-bind:style="supplierType === 'Physical Store' ? 'order: 4' : 'order: 3'"
                                >
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Contact Number</label>
                                    @include('partials.phone-input', [
                                        'name' => 'contact_number',
                                        'value' => old('contact_number'),
                                        'id' => 'supplier-contact-number',
                                    ])
                                </div>

                                <template x-if="supplierType === 'Physical Store'">
                                    <div
                                        style="order: 5"
                                        x-init="$nextTick(() => { const el = document.getElementById('supplier-landline-number'); if (el && window.initPrismLandlineInput) window.initPrismLandlineInput(el); window.lucide && window.lucide.createIcons(); })"
                                    >
                                        <label class="mb-1.5 block text-xs font-medium text-gray-600">Landline Number</label>
                                        @include('partials.landline-input', [
                                            'name' => 'landline_number',
                                            'value' => old('landline_number'),
                                            'id' => 'supplier-landline-number',
                                            'placeholder' => '(0XX) XXX-XXXX',
                                        ])
                                    </div>
                                </template>

                                <div class="sm:col-span-2" style="order: 6">
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Operating Hours</label>
                                    <input
                                        type="text"
                                        name="operating_hours"
                                        value="{{ old('operating_hours') }}"
                                        placeholder="e.g. Mon-Fri 9:00 AM - 6:00 PM"
                                        maxlength="255"
                                        class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                    >
                                </div>
                            </div>
                        </div>

                        {{-- PHYSICAL LOCATION --}}
                        <template x-if="supplierType === 'Physical Store'">
                            <div
                                class="overflow-hidden rounded-2xl border border-gray-200 bg-white"
                                x-init="$nextTick(() => window.lucide && window.lucide.createIcons())"
                            >
                                <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/80 px-4 py-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-gray-200">
                                        <i data-lucide="map-pin" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Physical location</p>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <label class="mb-1.5 block text-xs font-medium text-gray-600">Company Address</label>
                                    <textarea
                                        name="company_address"
                                        rows="3"
                                        maxlength="{{ \App\Support\InputLimits::NOTES }}"
                                        placeholder="Street, barangay, city..."
                                        class="box-border w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                    >{{ old('company_address') }}</textarea>
                                </div>
                            </div>
                        </template>

                        {{-- ONLINE INFORMATION --}}
                        <template x-if="supplierType === 'Online Store'">
                            <div
                                class="overflow-hidden rounded-2xl border border-gray-200 bg-white"
                                x-init="$nextTick(() => window.lucide && window.lucide.createIcons())"
                            >
                                <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/80 px-4 py-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 ring-1 ring-gray-200">
                                        <i data-lucide="shopping-bag" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Online platform</p>
                                    </div>
                                </div>
                                <div class="space-y-4 p-4">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-medium text-gray-600">
                                            Primary Platform <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            name="app_used"
                                            required
                                            class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition focus:border-gray-300 focus:bg-white"
                                        >
                                            <option value="">Select platform</option>
                                            @foreach(['Shopee', 'Lazada', 'Facebook', 'TikTok Shop', 'Website', 'Other'] as $platform)
                                                <option value="{{ $platform }}" @selected(old('app_used') === $platform)>
                                                    {{ $platform }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-medium text-gray-600">Store URL</label>
                                        <input
                                            type="text"
                                            name="store_url"
                                            value="{{ old('store_url') }}"
                                            maxlength="{{ \App\Support\InputLimits::NAME }}"
                                            placeholder="https://shopee.ph/shop/..."
                                            class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                                        >
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-medium text-gray-600">Seller / Store ID</label>
                                        <input
                                            type="text"
                                            name="seller_id"
                                            value="{{ old('seller_id') }}"
                                            maxlength="{{ \App\Support\InputLimits::SHORT }}"
                                            placeholder="Platform seller or store ID"
                                            class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
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
