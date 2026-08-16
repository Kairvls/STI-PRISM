<div
    x-data="{
        openModal: null,
        form: { id: null, name: '', status: 'Active' },
        deleteTarget: { id: null, name: '' },
        openCreate() {
            this.form = { id: null, name: '', status: 'Active' };
            this.openModal = 'create';
        },
        openEdit(record) {
            this.form = { id: record.id, name: record.name, status: record.status };
            this.openModal = 'edit';
        },
        openDelete(record) {
            this.deleteTarget = record;
            this.openModal = 'delete';
        }
    }"
>
    <div class="mb-7 flex justify-end">
        <button type="button" @click="openCreate()" class="pur-btn-primary">+ Add Brand</button>
    </div>

    <div class="pur-card mb-7">
        <div class="grid grid-cols-3 divide-x divide-gray-100">
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $summary['total'] ?? 0 }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Total Brands</p>
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
                    <h2 class="text-base font-semibold text-gray-950">Brand Records</h2>
                    <p class="mt-1 text-sm text-gray-500">Search and manage registered brands.</p>
                </div>
                <form method="GET" action="{{ route('purchaser.file-maintenance.index') }}" class="flex flex-col gap-2 sm:flex-row">
                    <input type="hidden" name="tab" value="brands">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search brands..." class="pur-input sm:w-64">
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
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Brand Name</th>
                        <th>Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($brands as $brand)
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4 text-gray-600">{{ $brand->brand_id }}</td>
                            <td class="px-5 py-4 font-medium text-gray-900">{{ $brand->brand_name }}</td>
                            <td class="px-5 py-4">
                                <span class="pur-badge {{ $brand->brand_status === 'Active' ? 'pur-badge-active' : 'pur-badge-inactive' }}">
                                    {{ $brand->brand_status }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        @click="openEdit({ id: {{ $brand->brand_id }}, name: @js($brand->brand_name), status: @js($brand->brand_status) })"
                                        class="pur-btn-secondary !px-3 !py-1.5 !text-xs"
                                    >Edit</button>
                                    <button
                                        type="button"
                                        @click="openDelete({ id: {{ $brand->brand_id }}, name: @js($brand->brand_name) })"
                                        class="pur-btn-danger !px-3 !py-1.5 !text-xs"
                                    >Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="pur-empty">No brands found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($brands->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $brands->links() }}</div>
        @endif
    </div>

    <div x-cloak x-show="openModal === 'create' || openModal === 'edit'" x-transition.opacity class="pur-modal">
        <div @click.outside="openModal = null" class="pur-modal-panel max-w-md">
            <form method="POST" :action="openModal === 'create' ? @js(route('purchaser.brands.store')) : (`{{ url('/purchaser/brands') }}/${form.id}`)">
                @csrf
                <template x-if="openModal === 'edit'">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <div class="pur-modal-header">
                    <h3 x-text="openModal === 'create' ? 'Add Brand' : 'Edit Brand'"></h3>
                </div>
                <div class="pur-modal-body space-y-4">
                    <div>
                        <label class="pur-label">Brand Name <span class="text-red-500">*</span></label>
                        <input type="text" name="brand_name" x-model="form.name" required class="pur-input">
                    </div>
                    <div>
                        <label class="pur-label">Status</label>
                        <select name="brand_status" x-model="form.status" class="pur-select">
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

    <div x-cloak x-show="openModal === 'delete'" x-transition.opacity class="pur-modal">
        <div @click.outside="openModal = null" class="pur-modal-panel max-w-md">
            <form method="POST" :action="`{{ url('/purchaser/brands') }}/${deleteTarget.id}`">
                @csrf
                @method('DELETE')
                <div class="pur-modal-header">
                    <h3>Delete Brand</h3>
                </div>
                <div class="pur-modal-body text-sm text-gray-600">
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
