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
            <label>Employee ID</label>

            <input type="text"
                name="employee_id"
                class="w-full border p-2 rounded">
        </div>

        <div>
            <label>Full Name</label>

            <input type="text"
                name="full_name"
                class="w-full border p-2 rounded">
        </div>

        <div>
            <label>Username</label>

            <input type="text"
                name="username"
                class="w-full border p-2 rounded">
        </div>

        <div>
            <label>Email</label>

            <input type="email"
                name="email"
                class="w-full border p-2 rounded">
        </div>

        <div>
            <label>Contact Number</label>
            @include('partials.phone-input', [
                'name' => 'contact_number',
                'value' => old('contact_number'),
                'id' => 'admin-create-user-contact-number',
                'inputClass' => 'w-full border p-2 rounded',
            ])
        </div>

        <div>
            <label>Password</label>

            <input type="password"
                name="password"
                class="w-full border p-2 rounded">
        </div>

        <div>
            <label>Role</label>

            <select name="role"
                class="w-full border p-2 rounded">

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

    </div>

    <button
        class="mt-6 bg-slate-800 text-white px-6 py-2 rounded">

        Save Account

    </button>

</form>

@endsection