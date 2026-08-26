<div
    x-data="{
        openModal: null,
        form: { id: null, category_id: '', name: '', description: '', status: 'Active' },
        deleteTarget: { id: null, name: '' },
        openCreate() {
            this.form = { id: null, category_id: '', name: '', description: '', status: 'Active' };
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
            Add Sub Category
        </button>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('purchaser.file-maintenance.index', ['tab' => 'subcategories']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Sub Categories</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($summary['total'] ?? 0) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <i data-lucide="folder-tree" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>All sub category records</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>
        <a href="{{ route('purchaser.file-maintenance.index', ['tab' => 'subcategories', 'status' => 'Active']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Active</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($summary['active'] ?? 0) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <i data-lucide="circle-check-big" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Available for use</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>
        <a href="{{ route('purchaser.file-maintenance.index', ['tab' => 'subcategories', 'status' => 'Inactive']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Inactive</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($summary['inactive'] ?? 0) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                    <i data-lucide="circle-pause" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Temporarily disabled</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>
    </div>

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Sub Category Records</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $subcategories->total() }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Search by sub category, parent category, or description.</p>
                </div>
                <form method="GET" action="{{ route('purchaser.file-maintenance.index') }}" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    <input type="hidden" name="tab" value="subcategories">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search sub categories..."
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-56"
                        >
                    </div>
                    <select name="category_id" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All categories</option>
                        @foreach($parentCategories as $category)
                            <option value="{{ $category->item_category_id }}" @selected((string) request('category_id') === (string) $category->item_category_id)>
                                {{ $category->item_category_name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="status" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All statuses</option>
                        <option value="Active" @selected(request('status') === 'Active')>Active</option>
                        <option value="Inactive" @selected(request('status') === 'Inactive')>Inactive</option>
                    </select>
                    <button type="submit" class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold leading-none text-white transition hover:bg-blue-800">
                        <i data-lucide="filter" class="h-4 w-4 shrink-0"></i>
                        Apply
                    </button>
                    @if(request()->filled('search') || request()->filled('status') || request()->filled('category_id'))
                        <a href="{{ route('purchaser.file-maintenance.index', ['tab' => 'subcategories']) }}" class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50">Clear</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ID</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Sub Category</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Category Name</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($subcategories as $subcategory)
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4 text-gray-500">#{{ $subcategory->item_subcategory_id }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="folder-tree" class="h-4 w-4"></i>
                                    </div>
                                    <p class="font-semibold text-gray-900">{{ $subcategory->item_subcategory_name }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $subcategory->item_category_name ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $subcategory->item_subcategory_status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $subcategory->item_subcategory_status }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        @click="openEdit({ id: {{ $subcategory->item_subcategory_id }}, category_id: @js((string) $subcategory->item_category_id), name: @js($subcategory->item_subcategory_name), description: @js($subcategory->item_subcategory_description ?? ''), status: @js($subcategory->item_subcategory_status) })"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                                    >
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        @click="openDelete({ id: {{ $subcategory->item_subcategory_id }}, name: @js($subcategory->item_subcategory_name) })"
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
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-400">
                                    <i data-lucide="folder-tree" class="h-5 w-5"></i>
                                </div>
                                <p class="mt-4 font-medium text-gray-700">No sub categories found</p>
                                <p class="mt-1 text-sm text-gray-400">Add a sub category or adjust the current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subcategories->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $subcategories->links() }}</div>
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
            <div class="my-auto w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="subcategory-form-title">
                <form method="POST" :action="openModal === 'create' ? @js(route('purchaser.subcategories.store')) : (`{{ url('/purchaser/subcategories') }}/${form.id}`)">
                    @csrf
                    <template x-if="openModal === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-white">
                                <i data-lucide="folder-tree" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 id="subcategory-form-title" class="text-lg font-semibold tracking-tight text-gray-950" x-text="openModal === 'create' ? 'Add Sub Category' : 'Edit Sub Category'"></h3>
                                <p class="mt-0.5 text-sm text-gray-500">Group under a parent category.</p>
                            </div>
                        </div>
                        <button type="button" @click="openModal = null" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    <div class="space-y-4 px-5 py-5">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-600">Category Name <span class="text-red-500">*</span></label>
                            <select name="item_category_id" x-model="form.category_id" required class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition focus:border-gray-300 focus:bg-white">
                                <option value="">Choose...</option>
                                @foreach($parentCategories as $category)
                                    <option value="{{ $category->item_category_id }}">{{ $category->item_category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-600">Sub Category <span class="text-red-500">*</span></label>
                            <input type="text" name="item_subcategory_name" x-model="form.name" required placeholder="Enter sub category name" class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-600">Description</label>
                            <textarea name="item_subcategory_description" x-model="form.description" rows="3" placeholder="Optional description" class="box-border w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"></textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-600">Status</label>
                            <select name="item_subcategory_status" x-model="form.status" class="box-border h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 outline-none transition focus:border-gray-300 focus:bg-white">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                        <button type="button" @click="openModal = null" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">Cancel</button>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800">
                            <i data-lucide="check" class="h-4 w-4"></i>
                            <span x-text="openModal === 'create' ? 'Save Sub Category' : 'Update Sub Category'"></span>
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
            <div class="my-auto w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="subcategory-delete-title">
                <form method="POST" :action="`{{ url('/purchaser/subcategories') }}/${deleteTarget.id}`">
                    @csrf
                    @method('DELETE')
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600">
                                <i data-lucide="trash-2" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 id="subcategory-delete-title" class="text-lg font-semibold tracking-tight text-gray-950">Delete Sub Category</h3>
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
