@extends('layouts.maintenance-layout')

@section('title', 'Reporter / People conflicts')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-4">
        <h2 class="text-lg font-semibold">Reporter ↔ People conflicts</h2>
        <p class="mt-1 text-sm text-slate-500">Employee IDs present in both Reporter and People directories with differing details.</p>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-3 py-2">Employee ID</th>
                    <th class="px-3 py-2">Reporter name</th>
                    <th class="px-3 py-2">People name</th>
                    <th class="px-3 py-2">Reporter email</th>
                    <th class="px-3 py-2">People email</th>
                    <th class="px-3 py-2">Reporter contact</th>
                    <th class="px-3 py-2">People contact</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($conflicts as $c)
                    <tr>
                        <td class="px-3 py-2 font-mono text-xs text-slate-600">{{ $c->employee_id }}</td>
                        <td class="px-3 py-2 text-slate-800">{{ $c->reporter_name }}</td>
                        <td class="px-3 py-2 text-slate-800">{{ $c->custodian_name }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $c->reporter_email }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $c->custodian_email }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $c->reporter_contact }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $c->custodian_contact }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No conflicts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        <a href="{{ url()->previous() ?? route('admin.reporters.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700">Back</a>
    </div>
</div>
@endsection
