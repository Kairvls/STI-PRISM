@extends('layouts.maintenance-layout')

@section('title', $person ? 'Edit '.$person->custodian_full_name : 'Add person')

@section('content')
@php
    $editing = $person !== null;
    $value = fn (string $field, $default = null) => old($field, $editing ? ($person->{$field} ?? $default) : $default);
    $fieldClass = 'h-10 w-full rounded-xl border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $errorClass = 'ring-rose-300 bg-rose-50/40';
    $selectedRoom = (string) $value('custodian_room_id', $defaultRoomId);
    $selectedReporter = (string) $value('custodian_reporter_id');
    $selectedStatus = $value('custodian_status', \App\Support\Custodians::STATUS_ACTIVE);
    $selectedDepartment = (string) $value('custodian_department_id');
    $nameParts = \App\Support\Custodians::nameParts($person);
@endphp

<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center gap-2 text-sm text-slate-400">
        <a href="{{ url('/maintenance/property-assignments') }}" class="transition hover:text-slate-700">Property Assignment</a>
        <i data-lucide="chevron-right" class="h-4 w-4"></i>
        @if ($editing)
            <a href="{{ route('maintenance.property-assignments.person', $person->custodian_id) }}" class="transition hover:text-slate-700">{{ $person->custodian_full_name }}</a>
            <i data-lucide="chevron-right" class="h-4 w-4"></i>
            <span class="font-medium text-slate-600">Edit</span>
        @else
            <span class="font-medium text-slate-600">Add person</span>
        @endif
    </div>

    <form
        method="POST"
        action="{{ $editing ? route('maintenance.property-assignments.people.update', $person->custodian_id) : route('maintenance.property-assignments.people.store') }}"
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
    >
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">{{ $editing ? 'Edit person' : 'Add a person to the directory' }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">
                Anyone who can be accountable for school property: office staff, faculty, guards, utility staff. They do not need a system account.
            </p>
        </div>

        @if ($errors->any())
            <div class="mx-5 mt-5 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100">
                <p class="font-semibold">Please fix the following:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('partials.duplicate-name-warning', ['matches' => session('duplicate_people', []), 'noun' => 'person'])

        <div class="grid grid-cols-1 gap-4 px-5 py-5 sm:grid-cols-2">
            <div class="grid grid-cols-1 gap-4 sm:col-span-2 sm:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">First name <span class="text-rose-500">*</span></span>
                    <input type="text" name="custodian_first_name" required maxlength="100" value="{{ old('custodian_first_name', $nameParts['first']) }}" placeholder="e.g. Ana" class="{{ $fieldClass }} @error('custodian_first_name') {{ $errorClass }} @enderror">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Middle name</span>
                    <input type="text" name="custodian_middle_name" maxlength="100" value="{{ old('custodian_middle_name', $nameParts['middle']) }}" placeholder="Optional" class="{{ $fieldClass }} @error('custodian_middle_name') {{ $errorClass }} @enderror">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Last name <span class="text-rose-500">*</span></span>
                    <input type="text" name="custodian_last_name" required maxlength="100" value="{{ old('custodian_last_name', $nameParts['last']) }}" placeholder="e.g. Reyes" class="{{ $fieldClass }} @error('custodian_last_name') {{ $errorClass }} @enderror">
                </label>
            </div>

            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500">Employee ID</span>
                <input type="text" name="custodian_employee_id" maxlength="50" value="{{ $value('custodian_employee_id') }}" placeholder="Optional" class="{{ $fieldClass }} font-mono @error('custodian_employee_id') {{ $errorClass }} @enderror">
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500">Position / role</span>
                <input type="text" name="custodian_position" maxlength="120" value="{{ $value('custodian_position') }}" placeholder="e.g. Registrar Staff, Security Guard" class="{{ $fieldClass }}">
            </label>

            <div class="block">
                <label for="custodian-department" class="mb-1 flex items-center justify-between text-xs font-medium text-slate-500">
                    Department / office
                    <a href="{{ route('maintenance.departments.index') }}" target="_blank" rel="noopener" class="font-semibold text-[#0025cc] hover:underline">Manage</a>
                </label>
                <select name="custodian_department_id" id="custodian-department" data-searchable="1" data-search-placeholder="Search department…" class="{{ $fieldClass }} @error('custodian_department_id') {{ $errorClass }} @enderror">
                    <option value="">Not set</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->department_id }}" @selected($selectedDepartment === (string) $department->department_id)>
                            {{ $department->department_name }}{{ $department->department_is_archived ? ' (archived)' : '' }}
                        </option>
                    @endforeach
                </select>
                <span class="mt-1 block text-[11px] text-slate-400">The unit the person belongs to, not where they sit.</span>
            </div>

            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500">Home room</span>
                <select name="custodian_room_id" id="custodian-room" data-searchable="1" data-search-placeholder="Search room…" class="{{ $fieldClass }}">
                    <option value="">Not set</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->room_id }}" @selected($selectedRoom === (string) $room->room_id)>
                            {{ $room->room_name }}{{ $room->room_type ? ' · '.$room->room_type : '' }}
                        </option>
                    @endforeach
                </select>
                <span class="mt-1 block text-[11px] text-slate-400">Where the person usually works. Items in this room are suggested first when assigning.</span>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500">Email</span>
                <input type="email" name="custodian_email_address" maxlength="150" value="{{ $value('custodian_email_address') }}" placeholder="Optional" class="{{ $fieldClass }} @error('custodian_email_address') {{ $errorClass }} @enderror">
            </label>

            <div class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500">Contact no.</span>
                @include('partials.phone-input', [
                    'name' => 'custodian_contact_number',
                    'value' => $value('custodian_contact_number'),
                    'id' => 'custodian-contact-number',
                    'placeholder' => '9XX XXX XXXX',
                    'inputClass' => 'h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100',
                ])
            </div>

            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500">Status <span class="text-red-500">*</span></span>
                <select name="custodian_status" class="{{ $fieldClass }} @error('custodian_status') {{ $errorClass }} @enderror">
                    @foreach (\App\Support\Custodians::STATUSES as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <span class="mt-1 block text-[11px] text-slate-400">Only Active people can receive new items. Inactive requires all items returned first.</span>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500">Linked faculty/staff account</span>
                <select name="custodian_reporter_id" data-searchable="1" data-search-placeholder="Search name or employee ID…" class="{{ $fieldClass }} @error('custodian_reporter_id') {{ $errorClass }} @enderror">
                    <option value="">None (no system account)</option>
                    @foreach ($linkableReporters as $reporter)
                        <option value="{{ $reporter->reporter_id }}" @selected($selectedReporter === (string) $reporter->reporter_id)>
                            {{ $reporter->reporter_full_name }} ({{ $reporter->reporter_employee_id }})
                        </option>
                    @endforeach
                </select>
                <span class="mt-1 block text-[11px] text-slate-400">Linked people see their items as quick picks on the report form.</span>
            </label>

            <label class="block sm:col-span-2">
                <span class="mb-1 block text-xs font-medium text-slate-500">Notes</span>
                <textarea name="custodian_notes" rows="3" maxlength="1000" placeholder="Optional" class="w-full rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10">{{ $value('custodian_notes') }}</textarea>
            </label>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-4">
            <a
                href="{{ $editing ? route('maintenance.property-assignments.person', $person->custodian_id) : url('/maintenance/property-assignments') }}"
                class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Cancel
            </a>
            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad]">
                <i data-lucide="{{ $editing ? 'save' : 'user-plus' }}" class="h-4 w-4"></i>
                {{ $editing ? 'Save changes' : 'Add person' }}
            </button>
        </div>
    </form>
</div>
@endsection
