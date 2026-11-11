@php
    $fieldClass = 'h-10 w-full rounded-xl border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $oldSetId = (string) old('computer_set_id', '');
@endphp

<form
    method="POST"
    action="{{ \App\Support\PropertyAssignments::route('computer-set') }}"
    x-data="{ selected: @js($oldSetId) }"
>
    @csrf
    <input type="hidden" name="custodian_id" value="{{ $person->custodian_id }}">

    <ul class="max-h-[28rem] divide-y divide-slate-100 overflow-y-auto">
        @foreach ($sets as $set)
            @php
                $roomLabel = trim(($set->floor_level ? $set->floor_level.' — ' : '').($set->room_name ?? ''));
                $setId = (string) $set->computer_set_id;
            @endphp
            <li>
                <label class="flex cursor-pointer items-start gap-3 px-5 py-3 text-sm hover:bg-slate-50/80">
                    <input
                        type="radio"
                        name="computer_set_id"
                        value="{{ $set->computer_set_id }}"
                        x-model="selected"
                        class="mt-1 border-slate-300 text-[#0025cc]"
                        @checked($oldSetId === $setId)
                    >
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold text-slate-800">
                            {{ $set->computer_set_name ?: 'Computer set' }}
                        </span>
                        <span class="mt-0.5 block font-mono text-xs text-slate-500">{{ $set->computer_set_tag }}</span>
                        <span class="mt-1 block truncate text-xs text-slate-500">
                            {{ implode(' · ', array_filter([
                                $roomLabel ?: null,
                                $set->computer_set_placement_zone ?: null,
                                ((int) $set->assignable_count).' of '.((int) $set->member_count).' '.Str::plural('item', (int) $set->member_count).' to assign',
                            ])) }}
                        </span>
                    </span>
                </label>
            </li>
        @endforeach
    </ul>

    <div class="grid gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Document no. (optional)</span>
            <input type="text" name="document_no" maxlength="64" value="{{ old('document_no') }}" placeholder="One number for the whole set" class="{{ $fieldClass }}">
        </label>

        <button
            type="submit"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad] disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!selected"
        >
            <i data-lucide="pc-case" class="h-4 w-4"></i>
            Assign computer set
        </button>
    </div>
</form>
