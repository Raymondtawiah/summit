<x-layouts::admin :title="__('Participants')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="lg">Participants</flux:heading>
                <flux:text class="text-black/70">Manage summit participants</flux:text>
            </div>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <form method="GET" action="{{ route('admin.participants') }}" class="flex flex-col gap-3 md:flex-row md:items-end">
                    <div class="flex-1">
                        <flux:input
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search registration number, name, or contact..."
                            icon="magnifying-glass"
                        />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <flux:select name="status" placeholder="All Statuses">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </flux:select>
                        <flux:select name="ticket_status" placeholder="All Ticket Statuses">
                            <option value="">All Ticket Statuses</option>
                            <option value="active" {{ request('ticket_status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="revoked" {{ request('ticket_status') === 'revoked' ? 'selected' : '' }}>Revoked</option>
                            <option value="replaced" {{ request('ticket_status') === 'replaced' ? 'selected' : '' }}>Replaced</option>
                            <option value="no_ticket" {{ request('ticket_status') === 'no_ticket' ? 'selected' : '' }}>No Ticket</option>
                        </flux:select>
                        <flux:select name="stake_district" placeholder="All Stake/District">
                            <option value="">All Stake/District</option>
                            @foreach($filterOptions['stake_districts'] as $district)
                                <option value="{{ $district }}" {{ request('stake_district') === $district ? 'selected' : '' }}>{{ $district }}</option>
                            @endforeach
                        </flux:select>
                        <flux:select name="unit" placeholder="All Units">
                            <option value="">All Units</option>
                            @foreach($filterOptions['units'] as $unit)
                                <option value="{{ $unit }}" {{ request('unit') === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                            @endforeach
                        </flux:select>
                        <flux:select name="shirt_size" placeholder="All Shirt Sizes">
                            <option value="">All Shirt Sizes</option>
                            @foreach($filterOptions['shirt_sizes'] as $size)
                                <option value="{{ $size }}" {{ request('shirt_size') === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </flux:select>
                        <flux:button type="submit" variant="primary" size="sm">Filter</flux:button>
                        @if(request()->hasAny(['search', 'status', 'ticket_status', 'stake_district', 'unit', 'shirt_size']))
                            <flux:button type="submit" variant="ghost" size="sm" href="{{ route('admin.participants') }}">Clear</flux:button>
                        @endif
                    </div>
                </form>
            </div>

            @if($participants->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-black/5 bg-black/[0.02]">
                            <tr>
                                <th class="px-6 py-3 font-medium">
                                    <a href="{{ route('admin.participants', array_merge(request()->query(), ['sort' => 'registration_number', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-blue-600">
                                        Registration #
                                        @if(request('sort') === 'registration_number')
                                            <flux:icon name="{{ request('direction') === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-3 w-3" />
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    <a href="{{ route('admin.participants', array_merge(request()->query(), ['sort' => 'first_name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-blue-600">
                                        Name
                                        @if(request('sort') === 'first_name')
                                            <flux:icon name="{{ request('direction') === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-3 w-3" />
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-3 font-medium">Contact</th>
                                <th class="px-6 py-3 font-medium">Age</th>
                                <th class="px-6 py-3 font-medium">
                                    <a href="{{ route('admin.participants', array_merge(request()->query(), ['sort' => 'unit', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-blue-600">
                                        Unit
                                        @if(request('sort') === 'unit')
                                            <flux:icon name="{{ request('direction') === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-3 w-3" />
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-3 font-medium">Stake/District</th>
                                <th class="px-6 py-3 font-medium">Shirt</th>
                                <th class="px-6 py-3 font-medium">Status</th>
                                <th class="px-6 py-3 font-medium">Ticket</th>
                                <th class="px-6 py-3 font-medium">
                                    <a href="{{ route('admin.participants', array_merge(request()->query(), ['sort' => 'created_at', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center gap-1 hover:text-blue-600">
                                        Created
                                        @if(request('sort') === 'created_at')
                                            <flux:icon name="{{ request('direction') === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="h-3 w-3" />
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5">
                            @foreach($participants as $participant)
                                <tr class="hover:bg-black/[0.01]">
                                    <td class="px-6 py-4 font-mono text-xs">{{ $participant->registration_number }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-black">{{ $participant->first_name }} {{ $participant->last_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-black/70">{{ $participant->contact ?? '—' }}</td>
                                    <td class="px-6 py-4 text-black/70">{{ $participant->age ?? '—' }}</td>
                                    <td class="px-6 py-4 text-black/70">{{ $participant->unit ?? '—' }}</td>
                                    <td class="px-6 py-4 text-black/70">{{ $participant->stake_district ?? '—' }}</td>
                                    <td class="px-6 py-4 text-black/70">{{ $participant->shirt_size ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        @if($participant->status === 'active')
                                            <flux:badge color="green" size="sm">Active</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($participant->activeTicket)
                                            @if($participant->activeTicket->status === 'active')
                                                <flux:badge color="blue" size="sm">Active</flux:badge>
                                            @elseif($participant->activeTicket->status === 'revoked')
                                                <flux:badge color="red" size="sm">Revoked</flux:badge>
                                            @else
                                                <flux:badge color="zinc" size="sm">Replaced</flux:badge>
                                            @endif
                                        @else
                                            <flux:badge color="zinc" size="sm">No Ticket</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-black/70">{{ $participant->created_at->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <flux:button size="sm" variant="ghost" :href="route('admin.participants.show', $participant)" wire:navigate>View</flux:button>
                                            <flux:button size="sm" variant="ghost" :href="route('admin.participants.edit', $participant)" wire:navigate>Edit</flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-black/5 px-6 py-4">
                    {{ $participants->links() }}
                </div>
            @else
                <div class="flex flex-col items-center justify-center gap-3 p-12">
                    <flux:icon name="users" class="h-12 w-12 text-black/20" />
                    <flux:text class="text-black/70">No participants have been added yet.</flux:text>
                </div>
            @endif
        </div>
    </div>
</x-layouts::admin>
