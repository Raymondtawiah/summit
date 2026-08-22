<x-layouts::admin :title="__('Ticket Preview')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Ticket Preview</flux:heading>
                <flux:text class="mt-1 text-black/70">
                    {{ $ticket->ticket_number }}
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button variant="primary" :href="route('admin.tickets.print', $ticket)" wire:navigate>
                    <flux:icon name="printer" class="mr-2 h-4 w-4" />
                    Print Ticket
                </flux:button>
                <flux:button variant="ghost" :href="route('admin.tickets.pdf', $ticket)" wire:navigate>
                    <flux:icon name="arrow-down-tray" class="mr-2 h-4 w-4" />
                    Download PDF
                </flux:button>
                @if($ticket->status === 'active')
                    <flux:button variant="ghost" :href="route('admin.tickets')" wire:navigate>Back to Tickets</flux:button>
                @endif
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-xl border border-black/5 bg-white p-8">
                <div class="text-center border-b-2 border-dashed border-black/10 pb-6 mb-6">
                    <flux:heading size="2xl" class="text-black">{{ config('app.name', 'LDS SUMMITPASS') }}</flux:heading>
                    <flux:text class="text-black/70 mt-1">Official Summit Pass</flux:text>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-xs uppercase text-black/50">Participant</flux:text>
                            <flux:text class="text-lg font-semibold text-black">{{ $ticket->participant->full_name }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs uppercase text-black/50">Registration Number</flux:text>
                            <flux:text class="text-lg font-mono text-black">{{ $ticket->participant->registration_number }}</flux:text>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-xs uppercase text-black/50">Ticket Number</flux:text>
                            <flux:text class="text-lg font-mono text-black">{{ $ticket->ticket_number }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs uppercase text-black/50">Status</flux:text>
                            <div class="mt-1">
                                @if($ticket->status === 'active')
                                    <flux:badge color="green">Active</flux:badge>
                                @elseif($ticket->status === 'revoked')
                                    <flux:badge color="red">Revoked</flux:badge>
                                @elseif($ticket->status === 'replaced')
                                    <flux:badge color="yellow">Replaced</flux:badge>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text class="text-xs uppercase text-black/50">Unit</flux:text>
                            <flux:text class="text-black">{{ $ticket->participant->unit ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs uppercase text-black/50">Stake/District</flux:text>
                            <flux:text class="text-black">{{ $ticket->participant->stake_district ?? 'N/A' }}</flux:text>
                        </div>
                    </div>

                    <div>
                        <flux:text class="text-xs uppercase text-black/50">Shirt Size</flux:text>
                        <flux:text class="text-black">{{ $ticket->participant->shirt_size ?? 'N/A' }}</flux:text>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-black/5">
                    <div class="flex flex-col items-center">
                        <div class="bg-white p-4 rounded-lg border border-black/10">
                            <img src="data:image/png;base64,{{ $ticket->qrCodeImage(200) }}" width="200" height="200" alt="QR Code" />
                        </div>
                        <flux:text class="text-xs text-black/50 mt-2 font-mono">{{ $ticket->qr_token }}</flux:text>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-black/5 text-center">
                    <flux:text class="text-xs text-black/50">
                        Generated: {{ $ticket->generated_at?->format('Y-m-d H:i') }} &middot;
                        Printed: {{ $ticket->printed_at?->format('Y-m-d H:i') ?? 'Not printed' }}
                    </flux:text>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-black/5 bg-white p-6">
                    <flux:heading size="md" class="mb-4">Ticket Information</flux:heading>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Ticket Number</flux:text>
                            <flux:text class="font-mono text-black">{{ $ticket->ticket_number }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Registration Number</flux:text>
                            <flux:text class="font-mono text-black">{{ $ticket->participant->registration_number }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Status</flux:text>
                            @if($ticket->status === 'active')
                                <flux:badge color="green" size="sm">Active</flux:badge>
                            @elseif($ticket->status === 'revoked')
                                <flux:badge color="red" size="sm">Revoked</flux:badge>
                            @elseif($ticket->status === 'replaced')
                                <flux:badge color="yellow" size="sm">Replaced</flux:badge>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Generated At</flux:text>
                            <flux:text class="text-black">{{ $ticket->generated_at?->format('Y-m-d H:i') }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Printed At</flux:text>
                            <flux:text class="text-black">{{ $ticket->printed_at?->format('Y-m-d H:i') ?? 'Not printed' }}</flux:text>
                        </div>
                        @if($ticket->revoked_at)
                            <div class="flex justify-between">
                                <flux:text class="text-black/70">Revoked At</flux:text>
                                <flux:text class="text-red-600">{{ $ticket->revoked_at->format('Y-m-d H:i') }}</flux:text>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl border border-black/5 bg-white p-6">
                    <flux:heading size="md" class="mb-4">Participant Information</flux:heading>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Full Name</flux:text>
                            <flux:text class="text-black">{{ $ticket->participant->full_name }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Registration Number</flux:text>
                            <flux:text class="font-mono text-black">{{ $ticket->participant->registration_number }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Contact</flux:text>
                            <flux:text class="text-black">{{ $ticket->participant->contact ?? 'N/A' }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Age</flux:text>
                            <flux:text class="text-black">{{ $ticket->participant->age ?? 'N/A' }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Unit</flux:text>
                            <flux:text class="text-black">{{ $ticket->participant->unit ?? 'N/A' }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Stake/District</flux:text>
                            <flux:text class="text-black">{{ $ticket->participant->stake_district ?? 'N/A' }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Shirt Size</flux:text>
                            <flux:text class="text-black">{{ $ticket->participant->shirt_size ?? 'N/A' }}</flux:text>
                        </div>
                    </div>
                </div>

                @if($ticket->status === 'active')
                    <div class="rounded-xl border border-red-200 bg-red-50 p-6">
                        <flux:heading size="md" class="mb-2 text-red-800">Danger Zone</flux:heading>
                        <flux:text class="text-sm text-red-700 mb-4">These actions cannot be undone.</flux:text>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('admin.tickets.revoke', $ticket) }}" onsubmit="return confirm('Are you sure you want to revoke this ticket?')">
                                @csrf
                                @method('POST')
                                <flux:button variant="danger" type="submit">Revoke Ticket</flux:button>
                            </form>
                            <form method="POST" action="{{ route('admin.tickets.replace', $ticket) }}" onsubmit="return confirm('Replacing this ticket will invalidate the current QR code. Continue?')">
                                @csrf
                                @method('POST')
                                <flux:button type="submit" variant="outline" class="!border-yellow-500 !text-yellow-600 hover:!bg-yellow-50">Replace Ticket</flux:button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::admin>
