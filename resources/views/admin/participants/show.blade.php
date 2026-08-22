<x-layouts::admin :title="__('Participant Details')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" :href="route('admin.participants')" wire:navigate>
                <flux:icon name="arrow-left" class="mr-2 h-4 w-4" />
                Back to Participants
            </flux:button>
            <flux:button variant="primary" :href="route('admin.participants.edit', $participant)" wire:navigate>
                <flux:icon name="pencil" class="mr-2 h-4 w-4" />
                Edit Participant
            </flux:button>
            @if($participant->activeTicket)
                <flux:button variant="filled" :href="route('admin.tickets.show', $participant->activeTicket)" wire:navigate>
                    <flux:icon name="ticket" class="mr-2 h-4 w-4" />
                    View Active Ticket
                </flux:button>
            @else
                <form method="POST" action="{{ route('admin.tickets.generate', $participant) }}">
                    @csrf
                    <flux:button variant="primary" type="submit">
                        <flux:icon name="ticket" class="mr-2 h-4 w-4" />
                        Generate Ticket
                    </flux:button>
                </form>
            @endif
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="md:col-span-2 space-y-6">
                <div class="rounded-xl border border-black/5 bg-white">
                    <div class="border-b border-black/5 px-6 py-4">
                        <flux:heading size="md">Participant Information</flux:heading>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <dt class="text-sm text-black/70">First Name</dt>
                                <dd class="mt-1 font-medium text-black">{{ $participant->first_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Last Name</dt>
                                <dd class="mt-1 font-medium text-black">{{ $participant->last_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Contact</dt>
                                <dd class="mt-1 font-medium text-black">{{ $participant->contact ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Age</dt>
                                <dd class="mt-1 font-medium text-black">{{ $participant->age ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Unit</dt>
                                <dd class="mt-1 font-medium text-black">{{ $participant->unit ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Stake/District</dt>
                                <dd class="mt-1 font-medium text-black">{{ $participant->stake_district ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Shirt Size</dt>
                                <dd class="mt-1 font-medium text-black">{{ $participant->shirt_size ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="rounded-xl border border-black/5 bg-white">
                    <div class="border-b border-black/5 px-6 py-4">
                        <flux:heading size="md">Registration</flux:heading>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <dt class="text-sm text-black/70">Registration Number</dt>
                                <dd class="mt-1 font-mono font-medium text-black">{{ $participant->registration_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Status</dt>
                                <dd class="mt-1">
                                    @if($participant->status === 'active')
                                        <flux:badge color="green" size="sm">Active</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Created Date</dt>
                                <dd class="mt-1 font-medium text-black">{{ $participant->created_at->format('Y-m-d H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Updated Date</dt>
                                <dd class="mt-1 font-medium text-black">{{ $participant->updated_at->format('Y-m-d H:i') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="rounded-xl border border-black/5 bg-white">
                    <div class="border-b border-black/5 px-6 py-4">
                        <flux:heading size="md">Ticket</flux:heading>
                    </div>
                    <div class="p-6">
                        @if($participant->activeTicket)
                            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <dt class="text-sm text-black/70">Ticket Number</dt>
                                    <dd class="mt-1 font-mono font-medium text-black">{{ $participant->activeTicket->ticket_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-black/70">Ticket Status</dt>
                                    <dd class="mt-1">
                                        @if($participant->activeTicket->status === 'active')
                                            <flux:badge color="blue" size="sm">Active</flux:badge>
                                        @elseif($participant->activeTicket->status === 'revoked')
                                            <flux:badge color="red" size="sm">Revoked</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">Replaced</flux:badge>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-black/70">QR Status</dt>
                                    <dd class="mt-1">
                                        @if($participant->activeTicket->qr_token)
                                            <flux:badge color="green" size="sm">Generated</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">Not Generated</flux:badge>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-black/70">Generated Date</dt>
                                    <dd class="mt-1 font-medium text-black">{{ $participant->activeTicket->generated_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-black/70">Printed Date</dt>
                                    <dd class="mt-1 font-medium text-black">{{ $participant->activeTicket->printed_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                                </div>
                            </dl>
                        @else
                            <flux:text class="text-black/70">No ticket generated.</flux:text>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl border border-black/5 bg-white">
                    <div class="border-b border-black/5 px-6 py-4">
                        <flux:heading size="md">Attendance</flux:heading>
                    </div>
                    <div class="p-6">
                        @if($attendanceStats['total_scans'] > 0)
                            <dl class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-6">
                                <div>
                                    <dt class="text-sm text-black/70">Total Scans</dt>
                                    <dd class="mt-1 font-medium text-black">{{ $attendanceStats['total_scans'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-black/70">Latest Scan</dt>
                                    <dd class="mt-1 font-medium text-black">{{ $attendanceStats['latest_scan']?->scanned_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-black/70">Scan Points Visited</dt>
                                    <dd class="mt-1 font-medium text-black">{{ $attendanceStats['scan_points_visited']->count() }}</dd>
                                </div>
                            </dl>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="border-b border-black/5 bg-black/[0.02]">
                                        <tr>
                                            <th class="px-4 py-2 font-medium">Scan Point</th>
                                            <th class="px-4 py-2 font-medium">Staff</th>
                                            <th class="px-4 py-2 font-medium">Date/Time</th>
                                            <th class="px-4 py-2 font-medium">Mode</th>
                                            <th class="px-4 py-2 font-medium">Sync</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-black/5">
                                        @foreach($participant->attendanceLogs as $log)
                                            <tr>
                                                <td class="px-4 py-3">{{ $log->scanPoint->name ?? '—' }}</td>
                                                <td class="px-4 py-3">{{ $log->staff->name ?? '—' }}</td>
                                                <td class="px-4 py-3">{{ $log->scanned_at->format('Y-m-d H:i') }}</td>
                                                <td class="px-4 py-3">
                                                    <flux:badge color="{{ $log->scan_mode === 'online' ? 'green' : 'yellow' }}" size="sm">
                                                        {{ ucfirst($log->scan_mode) }}
                                                    </flux:badge>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <flux:badge color="{{ $log->sync_status === 'synced' ? 'green' : 'zinc' }}" size="sm">
                                                        {{ ucfirst($log->sync_status) }}
                                                    </flux:badge>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <flux:text class="text-black/70">No attendance records found.</flux:text>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-black/5 bg-white">
                    <div class="border-b border-black/5 px-6 py-4">
                        <flux:heading size="md">Quick Info</flux:heading>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <dt class="text-sm text-black/70">Registration Number</dt>
                            <dd class="mt-1 font-mono font-medium text-black">{{ $participant->registration_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-black/70">Status</dt>
                            <dd class="mt-1">
                                @if($participant->status === 'active')
                                    <flux:badge color="green" size="sm">Active</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-black/70">Ticket Status</dt>
                            <dd class="mt-1">
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
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-black/70">Total Scans</dt>
                            <dd class="mt-1 font-medium text-black">{{ $attendanceStats['total_scans'] }}</dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts::admin>
