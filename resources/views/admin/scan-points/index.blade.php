<x-layouts::admin :title="__('Scan Points')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Scan Points</flux:heading>
                <flux:text class="mt-1 text-black/70">
                    Manage summit scanning locations.
                </flux:text>
            </div>
            <flux:button variant="primary" href="{{ route('admin.scan-points.create') }}" wire:navigate>
                <flux:icon name="plus" class="mr-2 h-4 w-4" />
                Create Scan Point
            </flux:button>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <form method="GET" action="{{ route('admin.scan-points') }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <flux:label>Search</flux:label>
                        <flux:input type="search" name="search" value="{{ request('search') }}" placeholder="Search by name or location..." />
                    </div>
                    <div>
                        <flux:label>Status</flux:label>
                        <flux:select name="status">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </flux:select>
                    </div>
                    <div class="flex gap-2">
                        <flux:button variant="primary" type="submit">Filter</flux:button>
                        <flux:button variant="ghost" href="{{ route('admin.scan-points') }}">Reset</flux:button>
                    </div>
                </form>
            </div>
            <div class="p-6">
                @if($scanPoints->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-black/5 bg-black/5 text-xs uppercase text-black/70">
                                <tr>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">Rule</th>
                                    <th class="px-4 py-3">Time</th>
                                    <th class="px-4 py-3">Assigned Staff</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/5">
                                @foreach($scanPoints as $scanPoint)
                                    <tr class="hover:bg-black/5">
                                        <td class="px-4 py-3 font-medium text-black">{{ $scanPoint->name }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ ucfirst($scanPoint->type) }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ ucwords(str_replace('_', ' ', $scanPoint->duplicate_rule)) }}</td>
                                        <td class="px-4 py-3 text-black/70">
                                            @if($scanPoint->start_time || $scanPoint->end_time)
                                                {{ $scanPoint->start_time?->format('H:i') ?? '--:--' }} – {{ $scanPoint->end_time?->format('H:i') ?? '--:--' }}
                                            @else
                                                Any time
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-black/70">{{ $scanPoint->users_count }}</td>
                                        <td class="px-4 py-3">
                                            @if($scanPoint->status === 'active')
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @else
                                                <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-2">
                                                <flux:button size="sm" variant="ghost" :href="route('admin.scan-points.show', $scanPoint)" wire:navigate>View</flux:button>
                                                <flux:button size="sm" variant="ghost" :href="route('admin.scan-points.edit', $scanPoint)" wire:navigate>Edit</flux:button>
                                                @if($scanPoint->status === 'active')
                                                    <form method="POST" action="{{ route('admin.scan-points.deactivate', $scanPoint) }}" class="inline" onsubmit="return confirm('Are you sure you want to deactivate this access point?')">
                                                        @csrf
                                                        @method('POST')
                                                        <flux:button size="sm" variant="ghost" type="submit" class="!text-red-600 hover:!text-red-700">Deactivate</flux:button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.scan-points.activate', $scanPoint) }}" class="inline">
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
                        {{ $scanPoints->links() }}
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center gap-3 p-12">
                        <flux:icon name="map-pin" class="h-12 w-12 text-black/20" />
                        <flux:text class="text-black/70">No scan points have been created.</flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::admin>
