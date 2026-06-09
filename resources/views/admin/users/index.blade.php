@extends('layouts.app')

@section('content')

<div class="flex justify-between mb-6">

    <h1 class="text-3xl font-bold">
        User Management
    </h1>

    <a href="/admin/users/create"
       class="bg-blue-600 text-white px-4 py-2 rounded">

        Create Account

    </a>

</div>

<table class="w-full bg-white rounded shadow">

    <thead class="bg-gray-200">

        <tr>
            <th class="p-3 text-left">Employee ID</th>
            <th class="p-3 text-left">Full Name</th>
            <th class="p-3 text-left">Role</th>
            <th class="p-3 text-left">Action</th>
        </tr>

    </thead>

    <tbody>

        <tr class="border-t">

            <td class="p-3">2026-001</td>
            <td class="p-3">Juan Dela Cruz</td>
            <td class="p-3">Maintenance Personnel</td>

            <td class="p-3">

                <button class="text-blue-600">
                    Edit
                </button>

            </td>

        </tr>

    </tbody>

</table>

@endsection