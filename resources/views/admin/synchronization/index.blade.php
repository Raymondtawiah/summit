<x-layouts::admin :title="__('Synchronization')">
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <flux:card class="border-black/10 bg-white p-4">
                <flux:text class="text-xs text-black/70">Total Devices</flux:text>
                <flux:text class="text-2xl font-semibold text-black">{{ number_format($stats['total_devices']) }}</flux:text>
            </flux:card>
            <flux:card class="border-black/10 bg-white p-4">
                <flux:text class="text-xs text-black/70">Active Devices</flux:text>
                <flux:text class="text-2xl font-semibold text-black">{{ number_format($stats['active_devices']) }}</flux:text>
            </flux:card>
            <flux:card class="border-black/10 bg-white p-4">
                <flux:text class="text-xs text-black/70">Queued Attendance</flux:text>
                <flux:text class="text-2xl font-semibold text-black">{{ number_format($stats['queued_attendance']) }}</flux:text>
            </flux:card>
            <flux:card class="border-black/10 bg-white p-4">
                <flux:text class="text-xs text-black/70">Synced Attendance</flux:text>
                <flux:text class="text-2xl font-semibold text-black">{{ number_format($stats['synced_attendance']) }}</flux:text>
            </flux:card>
        </div>

        <div class="overflow-x-auto rounded-xl border border-black/10 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-black/10 bg-black/5">
                    <tr>
                        <th class="px-4 py-3">Device</th>
                        <th class="px-4 py-3">Staff</th>
                        <th class="px-4 py-3">Scan Point</th>
                        <th class="px-4 py-3">Last Sync</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Data Invalidated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        <tr class="border-b border-black/5 last:border-0">
                            <td class="px-4 py-3">
                                <div class="font-medium text-black">{{ $device->name ?? 'Unnamed Device' }}</div>
                                <div class="text-xs text-black/50">{{ $device->device_identifier }}</div>
                            </td>
                            <td class="px-4 py-3 text-black">{{ $device->staff->name ?? 'Unassigned' }}</td>
                            <td class="px-4 py-3 text-black">{{ $device->staff->scanPoint->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-black">{{ $device->last_sync_at?->format('Y-m-d H:i') ?? 'Never' }}</td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $device->status === 'active' ? 'green' : 'red' }}" size="sm">
                                    {{ ucfirst($device->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $device->data_invalidated ? 'red' : 'green' }}" size="sm">
                                    {{ $device->data_invalidated ? 'Yes' : 'No' }}
                                </flux:badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-black/70">No devices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $devices->links() }}
    </div>
</x-layouts::admin>
