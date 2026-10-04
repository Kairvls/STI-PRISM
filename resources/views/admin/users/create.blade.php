@extends('layouts.admin-layout')

@section('title', 'Create User Account')

@section('content')

<h1 class="text-3xl font-bold mb-6">
    Create User Account
</h1>

<form method="POST"
      action="/admin/users/store"
      class="bg-white p-6 rounded shadow">

    @csrf

    <div class="grid grid-cols-2 gap-4">

        <div>
            <label>Type <span class="text-red-500">*</span></label>
            <select name="user_type" class="w-full border p-2 rounded" required>
                <option value="">Select type...</option>
                <option value="Faculty" @selected(old('user_type') === 'Faculty')>Faculty</option>
                <option value="Staff" @selected(old('user_type') === 'Staff')>Staff</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Faculty ends with F, Staff ends with S.</p>
        </div>

        <div>
            <label>Employee ID <span class="text-red-500">*</span></label>

            <input type="text"
                name="employee_id"
                class="w-full border p-2 rounded"
                placeholder="e.g. OMC00127F"
                value="{{ old('employee_id') }}"
                required>
            <p class="mt-1 text-xs text-gray-500">Format: OMC + 5 digits + F/S (example: OMC00127F).</p>
        </div>

        <div class="col-span-2 flex flex-col gap-4 sm:flex-row">
            <div class="min-w-0 flex-1">
                <label>First Name <span class="text-red-500">*</span></label>

                <input type="text"
                    name="first_name"
                    class="w-full border p-2 rounded"
                    placeholder="e.g. Juan"
                    value="{{ old('first_name') }}"
                    required>
            </div>

            <div class="min-w-0 flex-1">
                <label>Middle Name <span class="text-sm text-gray-500">(optional)</span></label>

                <input type="text"
                    name="middle_name"
                    class="w-full border p-2 rounded"
                    placeholder="e.g. Santos"
                    value="{{ old('middle_name') }}">
            </div>
        </div>

        <div class="col-span-2 flex flex-col gap-4 sm:flex-row">
            <div class="min-w-0 flex-1">
                <label>Last Name <span class="text-red-500">*</span></label>

                <input type="text"
                    name="last_name"
                    class="w-full border p-2 rounded"
                    placeholder="e.g. Dela Cruz"
                    value="{{ old('last_name') }}"
                    required>
            </div>

            <div class="min-w-0 flex-1">
                <label>Contact Number</label>
                @include('partials.phone-input', [
                    'name' => 'contact_number',
                    'value' => old('contact_number'),
                    'id' => 'admin-create-user-contact-number',
                    'inputClass' => 'w-full border p-2 rounded',
                ])
            </div>
        </div>

        <div class="col-span-2">
            <label>Email <span class="text-red-500">*</span></label>

            <input type="email"
                name="email"
                class="w-full border p-2 rounded"
                placeholder="e.g. juan.demo@example.com"
                required>
        </div>

        <div class="col-span-2 flex flex-col gap-4 sm:flex-row">
            <div class="min-w-0 flex-1">
                <label>Username <span class="text-red-500">*</span></label>

                <input type="text"
                    name="username"
                    class="w-full border p-2 rounded"
                    required>
            </div>

            <div class="min-w-0 flex-1">
                <label>Password <span class="text-red-500">*</span></label>

                <input type="password"
                    name="password"
                    class="w-full border p-2 rounded"
                    required>
            </div>
        </div>

        <div>
            <label>Role <span class="text-red-500">*</span></label>

            <select name="role" id="standaloneCreateUserRole"
                class="w-full border p-2 rounded">

                <option value="1">
                    Administrator
                </option>

                <option value="2">
                    Maintenance Personnel
                </option>

                <option value="3">
                    Purchaser
                </option>

                <option value="4">
                    President
                </option>

                <option value="5">
                    Accounting
                </option>

                <option value="6">
                    Receiving Officer
                </option>

            </select>

        </div>

        <div id="standaloneProcurementAccessWrap" class="col-span-2 rounded border border-gray-200 bg-gray-50 p-3">
            <label class="flex items-start gap-2">
                <input type="checkbox" name="user_can_procurement" value="1" class="mt-1">
                <span>
                    <span class="block font-medium">Enable procurement workflow</span>
                    <span class="block text-sm text-gray-600">For Administrator or Maintenance. Assigns Purchaser access — use the portal switcher to run RIS → ATP → RFC → RR → Liquidation. Administrator portal stays accept/sign + monitor.</span>
                </span>
            </label>
        </div>

    </div>

    <button
        class="mt-6 bg-slate-800 text-white px-6 py-2 rounded">

        Save Account

    </button>

</form>

@push('scripts')
<script>
    (function () {
        var roleSelect = document.getElementById('standaloneCreateUserRole');
        var wrap = document.getElementById('standaloneProcurementAccessWrap');
        if (!roleSelect || !wrap) return;
        function sync() {
            wrap.style.display = (roleSelect.value === '1' || roleSelect.value === '2') ? '' : 'none';
        }
        roleSelect.addEventListener('change', sync);
        sync();
    })();
</script>
@endpush

@endsection