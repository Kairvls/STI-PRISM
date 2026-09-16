{{-- Quick Access modal: users table only --}}
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px]">
            <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Employee ID</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Full Name</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Username</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Last Active</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                    @php
                        $isActive = !empty($user->last_active_at)
                            && \Carbon\Carbon::parse($user->last_active_at)->gte(now()->subDays(30));
                    @endphp
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-5 py-3.5 text-sm font-semibold text-gray-900">
                            {{ $user->user_employee_id ?: '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-sm text-gray-700">
                            {{ $user->user_full_name ?: '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-sm text-gray-600">
                            {{ $user->user_username ?: '—' }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                                {{ $user->role_name ?: 'Unknown' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($isActive)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-sm text-gray-500">
                            {{ $user->last_active_at ? \Carbon\Carbon::parse($user->last_active_at)->diffForHumans() : 'Never' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">
                            No users found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($users, 'hasPages') && $users->hasPages())
        @include('layouts.partials.table-showing-pager', ['pager' => $users, 'noun' => 'users'])
    @endif
</div>
