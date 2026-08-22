<x-layouts::admin :title="__('Staff Management')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Staff Management</flux:heading>
                <flux:text class="mt-1 text-black/70">
                    Manage staff accounts and scan point assignments.
                </flux:text>
            </div>
            <flux:button variant="primary" href="{{ route('admin.staff.create') }}" wire:navigate>
                <flux:icon name="user-plus" class="mr-2 h-4 w-4" />
                Create Staff
            </flux:button>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <form method="GET" action="{{ route('admin.staff') }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <flux:label>Search</flux:label>
                        <flux:input type="search" name="search" value="{{ request('search') }}" placeholder="Search by name or email..." />
                    </div>
                    <div>
                        <flux:label>Status</flux:label>
                        <flux:select name="status">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </flux:select>
                    </div>
                    <div>
                        <flux:label>Scan Point</flux:label>
                        <flux:select name="scan_point_id">
                            <option value="">All</option>
                            @foreach($filterOptions['scan_points'] as $scanPoint)
                                <option value="{{ $scanPoint->id }}" {{ request('scan_point_id') == $scanPoint->id ? 'selected' : '' }}>{{ $scanPoint->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="flex gap-2">
                        <flux:button variant="primary" type="submit">Filter</flux:button>
                        <flux:button variant="ghost" href="{{ route('admin.staff') }}">Reset</flux:button>
                    </div>
                </form>
            </div>
            <div class="p-6">
                @if($staff->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-black/5 bg-black/5 text-xs uppercase text-black/70">
                                <tr>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Email</th>
                                    <th class="px-4 py-3">Assigned Scan Point</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Last Login</th>
                                    <th class="px-4 py-3">Created</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/5">
                                @foreach($staff as $member)
                                    <tr class="hover:bg-black/5">
                                        <td class="px-4 py-3 font-medium text-black">{{ $member->name }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $member->email }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $member->scanPoint->name ?? 'Unassigned' }}</td>
                                        <td class="px-4 py-3">
                                            @if($member->status === 'active')
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @else
                                                <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-black/70">{{ $member->last_login_at?->format('Y-m-d H:i') ?? 'Never' }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $member->created_at->format('Y-m-d') }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-2">
                                                <flux:button size="sm" variant="ghost" :href="route('admin.staff.show', $member)" wire:navigate>View</flux:button>
                                                <flux:button size="sm" variant="ghost" :href="route('admin.staff.edit', $member)" wire:navigate>Edit</flux:button>
                                                @if($member->status === 'active')
                                                    <form method="POST" action="{{ route('admin.staff.deactivate', $member) }}" class="inline" onsubmit="return confirm('Are you sure you want to deactivate this staff member?')">
                                                        @csrf
                                                        @method('POST')
                                                        <flux:button size="sm" variant="ghost" type="submit" class="!text-red-600 hover:!text-red-700">Deactivate</flux:button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.staff.activate', $member) }}" class="inline">
                                                        @csrf
                                                        @method('POST')
                                                        <flux:button size="sm" variant="ghost" type="submit" class="!text-green-600 hover:!text-green-700">Activate</flux:button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $staff->links() }}
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center gap-3 p-12">
                        <flux:icon name="users" class="h-12 w-12 text-black/20" />
                        <flux:text class="text-black/70">No staff accounts found.</flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::admin>
