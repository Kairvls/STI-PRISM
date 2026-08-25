<div
    x-data="{
        openModal: null,
        form: { id: null, category_id: '', name: '', description: '', status: 'Active' },
        deleteTarget: { id: null, name: '' },
        openCreate() {
            this.form = { id: null, category_id: '', name: '', description: '', status: 'Active' };
            this.openModal = 'create';
        },
        openEdit(record) {
            this.form = record;
            this.openModal = 'edit';
        },
        openDelete(record) {
            this.deleteTarget = record;
            this.openModal = 'delete';
        }
    }"
>
    <div class="mb-7 flex justify-end">
        <button type="button" @click="openCreate()" class="pur-btn-primary">+ Add Sub Category</button>
    </div>

    <div class="pur-card mb-7">
        <div class="grid grid-cols-3 divide-x divide-gray-100">
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $summary['total'] ?? 0 }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Total Sub Categories</p>
            </div>
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $summary['active'] ?? 0 }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Active</p>
            </div>
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $summary['inactive'] ?? 0 }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Inactive</p>
            </div>
        </div>
    </div>

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950">Sub Category Information</h2>
                    <p class="mt-1 text-sm text-gray-500">Search by sub category, parent category, or description.</p>
                </div>
                <form method="GET" action="{{ route('purchaser.file-maintenance.index') }}" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                    <input type="hidden" name="tab" value="subcategories">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search sub categories..." class="pur-input sm:w-56">
                    <select name="category_id" class="pur-select">
                        <option value="">All categories</option>
                        @foreach($parentCategories as $category)
                            <option value="{{ $category->item_category_id }}" @selected((string) request('category_id') === (string) $category->item_category_id)>
                                {{ $category->item_category_name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="status" class="pur-select">
                        <option value="">All statuses</option>
                        <option value="Active" @selected(request('status') === 'Active')>Active</option>
                        <option value="Inactive" @selected(request('status') === 'Inactive')>Inactive</option>
                    </select>
                    <button type="submit" class="pur-btn-secondary">Apply</button>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="pur-table">
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
                            <td class="px-5 py-4 text-gray-600">{{ $subcategory->item_subcategory_id }}</td>
                            <td class="px-5 py-4 font-medium text-gray-900">{{ $subcategory->item_subcategory_name }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $subcategory->item_category_name ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="pur-badge {{ $subcategory->item_subcategory_status === 'Active' ? 'pur-badge-active' : 'pur-badge-inactive' }}">
                                    {{ $subcategory->item_subcategory_status }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        @click="openEdit({ id: {{ $subcategory->item_subcategory_id }}, category_id: @js((string) $subcategory->item_category_id), name: @js($subcategory->item_subcategory_name), description: @js($subcategory->item_subcategory_description ?? ''), status: @js($subcategory->item_subcategory_status) })"
                                        class="pur-btn-secondary !px-3 !py-1.5 !text-xs"
                                    >Edit</button>
                                    <button
                                        type="button"
                                        @click="openDelete({ id: {{ $subcategory->item_subcategory_id }}, name: @js($subcategory->item_subcategory_name) })"
                                        class="pur-btn-danger !px-3 !py-1.5 !text-xs"
                                    >Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-400">No sub categories found.</td>
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
        class="pur-modal"
        x-effect="window.purDialog && window.purDialog.sync(openModal === 'create' || openModal === 'edit', $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
    >
        <div
            @click.outside="openModal = null"
            class="pur-modal-panel max-w-md"
            role="dialog"
            aria-modal="true"
            aria-labelledby="subcategory-form-title"
        >
            <form method="POST" :action="openModal === 'create' ? @js(route('purchaser.subcategories.store')) : (`{{ url('/purchaser/subcategories') }}/${form.id}`)">
                @csrf
                <template x-if="openModal === 'edit'"><input type="hidden" name="_method" value="PUT"></template>
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 id="subcategory-form-title" class="text-lg font-semibold text-gray-900" x-text="openModal === 'create' ? 'Add Sub Category' : 'Edit Sub Category'"></h3>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="pur-label">Category Name <span class="text-red-500">*</span></label>
                        <select name="item_category_id" x-model="form.category_id" required class="pur-input">
                            <option value="">Choose...</option>
                            @foreach($parentCategories as $category)
                                <option value="{{ $category->item_category_id }}">{{ $category->item_category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="pur-label">Sub Category <span class="text-red-500">*</span></label>
                        <input type="text" name="item_subcategory_name" x-model="form.name" required class="pur-input">
                    </div>
                    <div>
                        <label class="pur-label">Description</label>
                        <textarea name="item_subcategory_description" x-model="form.description" rows="3" class="pur-input"></textarea>
                    </div>
                    <div>
                        <label class="pur-label">Status</label>
                        <select name="item_subcategory_status" x-model="form.status" class="pur-input">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="pur-modal-footer">
                    <button type="button" @click="openModal = null" class="pur-btn-secondary">Cancel</button>
                    <button type="submit" class="pur-btn-primary" x-text="openModal === 'create' ? 'Save' : 'Update'"></button>
                </div>
            </form>
        </div>
    </div>

    <div
        x-cloak
        x-show="openModal === 'delete'"
        x-transition.opacity
        class="pur-modal"
        x-effect="window.purDialog && window.purDialog.sync(openModal === 'delete', $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
    >
        <div
            @click.outside="openModal = null"
            class="pur-modal-panel max-w-md"
            role="dialog"
            aria-modal="true"
            aria-labelledby="subcategory-delete-title"
        >
            <form method="POST" :action="`{{ url('/purchaser/subcategories') }}/${deleteTarget.id}`">
                @csrf
                @method('DELETE')
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 id="subcategory-delete-title" class="text-lg font-semibold text-gray-900">Delete Sub Category</h3>
                </div>
                <div class="px-6 py-5 text-sm text-gray-600">
                    Are you sure you want to delete <span class="font-semibold text-gray-900" x-text="deleteTarget.name"></span>?
                </div>
                <div class="pur-modal-footer">
                    <button type="button" @click="openModal = null" class="pur-btn-secondary">Cancel</button>
                    <button type="submit" class="pur-btn-danger">Yes</button>
                </div>
            </form>
        </div>
    </div>
</div>
