<div
    x-data="{
        openModal: null,
        form: { id: null, name: '', description: '' },
        deleteTarget: { id: null, name: '' },
        openCreate() {
            this.form = { id: null, name: '', description: '' };
            this.openModal = 'create';
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },
        openEdit(record) {
            this.form = record;
            this.openModal = 'edit';
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },
        openDelete(record) {
            this.deleteTarget = record;
            this.openModal = 'delete';
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        }
    }"
>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        @include('purchaser.file-maintenance.partials.tabs')
        <button
            type="button"
            @click="openCreate()"
            class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            Add UOM
        </button>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="pur-stat-card sm:col-span-3 md:col-span-1">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total UOM</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($summary['total'] ?? 0) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <i data-lucide="ruler" class="h-5 w-5"></i>
                </div>
            </div>
            <p class="mt-5 text-xs font-medium text-gray-500">Units of measure for RIS items</p>
        </div>
    </div>

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">UOM Records</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $uoms->total() }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Search units by name or description.</p>
                </div>
                <form method="GET" action="{{ route('purchaser.file-maintenance.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input type="hidden" name="tab" value="uom">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search UOM..."
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>
                    <button type="submit" class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold leading-none text-white transition hover:bg-blue-800">
                        <i data-lucide="filter" class="h-4 w-4 shrink-0"></i>
                        Apply
                    </button>
                    @if(request()->filled('search'))
                        <a href="{{ route('purchaser.file-maintenance.index', ['tab' => 'uom']) }}" class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50">Clear</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ID</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">UOM</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Description</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($uoms as $uom)
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4 text-gray-500">#{{ $uom->uom_id }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="ruler" class="h-4 w-4"></i>
                                    </div>
                                    <p class="font-semibold text-gray-900">{{ $uom->uom_name }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $uom->uom_description ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        @click="openEdit({ id: {{ $uom->uom_id }}, name: @js($uom->uom_name), description: @js($uom->uom_description ?? '') })"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                                    >
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        @click="openDelete({ id: {{ $uom->uom_id }}, name: @js($uom->uom_name) })"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50"
                                    >
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-400">
                                    <i data-lucide="ruler" class="h-5 w-5"></i>
                                </div>
                                <p class="mt-4 font-medium text-gray-700">No units of measure found</p>
                                <p class="mt-1 text-sm text-gray-400">Add a UOM or adjust the current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($uoms->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $uoms->links() }}</div>
        @endif
    </div>

    <div
        x-cloak
        x-show="openModal === 'create' || openModal === 'edit'"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
        x-effect="window.purDialog && window.purDialog.sync(openModal === 'create' || openModal === 'edit', $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        @keydown.escape.window="openModal = null"
    >
        <div @click.self="openModal = null" class="flex min-h-full w-full justify-center">
            <div class="my-auto w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="uom-form-title">
                <form method="POST" :action="openModal === 'create' ? @js(route('purchaser.uom.store')) : (`{{ url('/purchaser/uom') }}/${form.id}`)">
                    @csrf
                    <template x-if="openModal === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-white">
                                <i data-lucide="ruler" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 id="uom-form-title" class="text-lg font-semibold tracking-tight text-gray-950" x-text="openModal === 'create' ? 'Add UOM' : 'Edit UOM'"></h3>
                                <p class="mt-0.5 text-sm text-gray-500">Unit of measure for RIS line items.</p>
                            </div>
                        </div>
                        <button type="button" @click="openModal = null" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    <div class="space-y-4 px-5 py-5">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-600">UOM Name <span class="text-red-500">*</span></label>
                            <input type="text" name="uom_name" x-model="form.name" required placeholder="e.g. pcs, box, set" class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-600">Description</label>
                            <textarea name="uom_description" x-model="form.description" rows="3" placeholder="Optional description" class="box-border w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                        <button type="button" @click="openModal = null" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">Cancel</button>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800">
                            <i data-lucide="check" class="h-4 w-4"></i>
                            <span x-text="openModal === 'create' ? 'Save UOM' : 'Update UOM'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div
        x-cloak
        x-show="openModal === 'delete'"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
        x-effect="window.purDialog && window.purDialog.sync(openModal === 'delete', $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        @keydown.escape.window="openModal = null"
    >
        <div @click.self="openModal = null" class="flex min-h-full w-full justify-center">
            <div class="my-auto w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="uom-delete-title">
                <form method="POST" :action="`{{ url('/purchaser/uom') }}/${deleteTarget.id}`">
                    @csrf
                    @method('DELETE')
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600">
                                <i data-lucide="trash-2" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 id="uom-delete-title" class="text-lg font-semibold tracking-tight text-gray-950">Delete UOM</h3>
                                <p class="mt-0.5 text-sm text-gray-500">This action cannot be undone.</p>
                            </div>
                        </div>
                        <button type="button" @click="openModal = null" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <div class="px-5 py-5 text-sm text-gray-600">
                        Are you sure you want to delete <span class="font-semibold text-gray-900" x-text="deleteTarget.name"></span>?
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                        <button type="button" @click="openModal = null" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">Cancel</button>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-red-700">Yes, delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
