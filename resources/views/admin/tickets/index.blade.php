<x-layouts::admin :title="__('Ticket Management')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Ticket Management</flux:heading>
                <flux:text class="mt-1 text-black/70">
                    Generate, view, and manage summit tickets.
                </flux:text>
            </div>
            <form method="POST" action="{{ route('admin.tickets.generate-missing') }}" onsubmit="return confirm('Generate missing tickets for all active participants without active tickets?')">
                @csrf
                <flux:button variant="primary" type="submit">
                    <flux:icon name="ticket" class="mr-2 h-4 w-4" />
                    Generate Missing Tickets
                </flux:button>
            </form>
        </div>

        <div class="grid auto-rows-min gap-4 md:grid-cols-6">
            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-xs text-black/70">Total Participants</flux:text>
                <flux:heading size="lg" class="mt-1 text-black">{{ number_format($stats['total_participants']) }}</flux:heading>
            </div>
            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-xs text-black/70">With Tickets</flux:text>
                <flux:heading size="lg" class="mt-1 text-green-600">{{ number_format($stats['participants_with_tickets']) }}</flux:heading>
            </div>
            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-xs text-black/70">Without Tickets</flux:text>
                <flux:heading size="lg" class="mt-1 text-red-600">{{ number_format($stats['participants_without_tickets']) }}</flux:heading>
            </div>
            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-xs text-black/70">Active Tickets</flux:text>
                <flux:heading size="lg" class="mt-1 text-green-600">{{ number_format($stats['active_tickets']) }}</flux:heading>
            </div>
            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-xs text-black/70">Revoked Tickets</flux:text>
                <flux:heading size="lg" class="mt-1 text-red-600">{{ number_format($stats['revoked_tickets']) }}</flux:heading>
            </div>
            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-xs text-black/70">Replaced Tickets</flux:text>
                <flux:heading size="lg" class="mt-1 text-yellow-600">{{ number_format($stats['replaced_tickets']) }}</flux:heading>
            </div>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <form method="GET" action="{{ route('admin.tickets') }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <flux:label>Search</flux:label>
                        <flux:input type="search" name="search" value="{{ request('search') }}" placeholder="Ticket number, registration number, or participant name..." />
                    </div>
                    <div>
                        <flux:label>Status</flux:label>
                        <flux:select name="status">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>Revoked</option>
                            <option value="replaced" {{ request('status') === 'replaced' ? 'selected' : '' }}>Replaced</option>
                        </flux:select>
                    </div>
                    <div>
                        <flux:label>Stake/District</flux:label>
                        <flux:select name="stake_district">
                            <option value="">All</option>
                            @foreach($filterOptions['stake_districts'] as $district)
                                <option value="{{ $district }}" {{ request('stake_district') === $district ? 'selected' : '' }}>{{ $district }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div>
                        <flux:label>Unit</flux:label>
                        <flux:select name="unit">
                            <option value="">All</option>
                            @foreach($filterOptions['units'] as $unit)
                                <option value="{{ $unit }}" {{ request('unit') === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="flex gap-2">
                        <flux:button variant="primary" type="submit">Filter</flux:button>
                        <flux:button variant="ghost" href="{{ route('admin.tickets') }}">Reset</flux:button>
                    </div>
                </form>
            </div>
            <div class="p-6">
                @if($tickets->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-black/5 bg-black/5 text-xs uppercase text-black/70">
                                <tr>
                                    <th class="px-4 py-3">Ticket Number</th>
                                    <th class="px-4 py-3">Registration Number</th>
                                    <th class="px-4 py-3">Participant</th>
                                    <th class="px-4 py-3">Unit</th>
                                    <th class="px-4 py-3">Stake/District</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Generated</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/5">
                                @foreach($tickets as $ticket)
                                    <tr class="hover:bg-black/5">
                                        <td class="px-4 py-3 font-mono text-xs">{{ $ticket->ticket_number }}</td>
                                        <td class="px-4 py-3 font-mono text-xs">{{ $ticket->participant->registration_number }}</td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-black">{{ $ticket->participant->full_name }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-black/70">{{ $ticket->participant->unit }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $ticket->participant->stake_district }}</td>
                                        <td class="px-4 py-3">
                                            @if($ticket->status === 'active')
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @elseif($ticket->status === 'revoked')
                                                <flux:badge color="red" size="sm">Revoked</flux:badge>
                                            @elseif($ticket->status === 'replaced')
                                                <flux:badge color="yellow" size="sm">Replaced</flux:badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-black/70">{{ $ticket->generated_at?->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-2">
                                                <flux:button size="sm" variant="ghost" :href="route('admin.tickets.show', $ticket)" wire:navigate>View</flux:button>
                                                @if($ticket->status === 'active')
                                                    <flux:button size="sm" variant="ghost" :href="route('admin.tickets.print', $ticket)" wire:navigate>Print</flux:button>
                                                    <flux:button size="sm" variant="ghost" :href="route('admin.tickets.pdf', $ticket)" wire:navigate>PDF</flux:button>
                                                    <form method="POST" action="{{ route('admin.tickets.revoke', $ticket) }}" class="inline" onsubmit="return confirm('Are you sure you want to revoke this ticket?')">
                                                        @csrf
                                                        @method('POST')
                                                        <flux:button size="sm" variant="ghost" type="submit" class="!text-red-600 hover:!text-red-700">Revoke</flux:button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.tickets.replace', $ticket) }}" class="inline" onsubmit="return confirm('Replacing this ticket will invalidate the current QR code. Continue?')">
                                                        @csrf
                                                        @method('POST')
                                                        <flux:button size="sm" variant="ghost" type="submit" class="!text-yellow-600 hover:!text-yellow-700">Replace</flux:button>
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
                        {{ $tickets->links() }}
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center gap-3 p-12">
                        <flux:icon name="ticket" class="h-12 w-12 text-black/20" />
                        <flux:text class="text-black/70">No tickets found.</flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::admin>
