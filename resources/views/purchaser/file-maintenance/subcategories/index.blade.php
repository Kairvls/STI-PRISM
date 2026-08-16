@extends('layouts.purchaser-layout')

@section('page-title', 'Sub Categories')
@section('page-subtitle', 'Manage Item Sub Categories')

@section('content')

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
    x-cloak
>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-gray-900"></span>
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">File Maintenance</span>
                </div>
                <h1 class="text-3xl font-semibold tracking-tight text-gray-950">Sub Categories</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">Group sub categories under a parent procurement category.</p>
            </div>
            <button type="button" @click="openCreate()" class="rounded-xl bg-gray-950 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800">
                + Add Sub Category
            </button>
        </div>
    </div>

    <div class="mb-7 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
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

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950">Sub Category Information</h2>
                    <p class="mt-1 text-sm text-gray-500">Search by sub category, parent category, or description.</p>
                </div>
                <form method="GET" action="{{ route('purchaser.subcategories.index') }}" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search sub categories..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm outline-none focus:border-gray-300 focus:bg-white sm:w-56">
                    <select name="category_id" class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-gray-300 focus:bg-white">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->item_category_id }}" @selected((string) request('category_id') === (string) $category->item_category_id)>
                                {{ $category->item_category_name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="status" class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-gray-300 focus:bg-white">
                        <option value="">All statuses</option>
                        <option value="Active" @selected(request('status') === 'Active')>Active</option>
                        <option value="Inactive" @selected(request('status') === 'Inactive')>Inactive</option>
                    </select>
                    <button type="submit" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Apply</button>
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
                            <td class="px-5 py-4 text-gray-600">{{ $subcategory->item_subcategory_id }}</td>
                            <td class="px-5 py-4 font-medium text-gray-900">{{ $subcategory->item_subcategory_name }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $subcategory->item_category_name ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $subcategory->item_subcategory_status === 'Active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $subcategory->item_subcategory_status }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        @click="openEdit({ id: {{ $subcategory->item_subcategory_id }}, category_id: @js((string) $subcategory->item_category_id), name: @js($subcategory->item_subcategory_name), description: @js($subcategory->item_subcategory_description ?? ''), status: @js($subcategory->item_subcategory_status) })"
                                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                    >Edit</button>
                                    <button
                                        type="button"
                                        @click="openDelete({ id: {{ $subcategory->item_subcategory_id }}, name: @js($subcategory->item_subcategory_name) })"
                                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50"
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

    <div x-show="openModal === 'create' || openModal === 'edit'" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModal = null" class="w-full max-w-md rounded-xl bg-white shadow-2xl">
            <form method="POST" :action="openModal === 'create' ? @js(route('purchaser.subcategories.store')) : (`{{ url('/purchaser/subcategories') }}/${form.id}`)">
                @csrf
                <template x-if="openModal === 'edit'"><input type="hidden" name="_method" value="PUT"></template>
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="openModal === 'create' ? 'Add Sub Category' : 'Edit Sub Category'"></h3>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Category Name <span class="text-red-500">*</span></label>
                        <select name="item_category_id" x-model="form.category_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-gray-500">
                            <option value="">Choose...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->item_category_id }}">{{ $category->item_category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Sub Category <span class="text-red-500">*</span></label>
                        <input type="text" name="item_subcategory_name" x-model="form.name" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-gray-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="item_subcategory_description" x-model="form.description" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-gray-500"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                        <select name="item_subcategory_status" x-model="form.status" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-gray-500">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <button type="button" @click="openModal = null" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-white">Cancel</button>
                    <button type="submit" class="rounded-lg bg-gray-950 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800" x-text="openModal === 'create' ? 'Save' : 'Update'"></button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="openModal === 'delete'" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="openModal = null" class="w-full max-w-md rounded-xl bg-white shadow-2xl">
            <form method="POST" :action="`{{ url('/purchaser/subcategories') }}/${deleteTarget.id}`">
                @csrf
                @method('DELETE')
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Delete Sub Category</h3>
                </div>
                <div class="px-6 py-5 text-sm text-gray-600">
                    Are you sure you want to delete <span class="font-semibold text-gray-900" x-text="deleteTarget.name"></span>?
                </div>
                <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <button type="button" @click="openModal = null" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-white">Cancel</button>
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Yes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
