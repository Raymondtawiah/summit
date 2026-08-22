<x-layouts::staff :title="__('Scan History')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('staff.scanner') }}" wire:navigate>
                <flux:icon name="arrow-left" class="mr-2 h-4 w-4" />
                Back to Scanner
            </flux:button>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <flux:heading size="lg">Scan History</flux:heading>
                <flux:text class="text-black/70 mt-1">Your recent scan activity.</flux:text>
            </div>
            <div class="p-6">
                @if($scans->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-black/5 bg-black/5 text-xs uppercase text-black/70">
                                <tr>
                                    <th class="px-4 py-3">Participant</th>
                                    <th class="px-4 py-3">Registration Number</th>
                                    <th class="px-4 py-3">Ticket Number</th>
                                    <th class="px-4 py-3">Scan Point</th>
                                    <th class="px-4 py-3">Scan Mode</th>
                                    <th class="px-4 py-3">Date/Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/5">
                                @foreach($scans as $scan)
                                    <tr class="hover:bg-black/5">
                                        <td class="px-4 py-3 font-medium text-black">{{ $scan->participant->full_name ?? '—' }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-black/70">{{ $scan->participant->registration_number ?? '—' }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-black/70">{{ $scan->ticket->ticket_number ?? '—' }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $scan->scanPoint->name ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <flux:badge color="{{ $scan->scan_mode === 'online' ? 'green' : 'yellow' }}" size="sm">
                                                {{ ucfirst($scan->scan_mode) }}
                                            </flux:badge>
                                        </td>
                                        <td class="px-4 py-3 text-black/70">{{ $scan->scanned_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $scans->links() }}
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center gap-3 p-12">
                        <flux:icon name="clock" class="h-12 w-12 text-black/20" />
                        <flux:text class="text-black/70">No scan history yet.</flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::staff>
