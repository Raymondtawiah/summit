<x-layouts::admin :title="__('Device Report')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Device Report</flux:heading>
                <flux:text class="mt-1 text-black/70">Device status and synchronization overview.</flux:text>
            </div>
            <a href="{{ route('admin.reports.export', ['type' => 'devices', 'format' => 'csv']) }}" class="rounded-lg bg-black px-3 py-2 text-xs text-white hover:bg-black/80">Export CSV</a>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-black/5 bg-black/5 text-xs uppercase text-black/70">
                        <tr>
                            <th class="px-4 py-3">Device</th>
                            <th class="px-4 py-3">Staff</th>
                            <th class="px-4 py-3">Access Point</th>
                            <th class="px-4 py-3">Last Sync</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Data Invalidated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse($report as $row)
                            <tr class="hover:bg-black/5">
                                <td class="px-4 py-3 font-medium text-black">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 text-black/70">{{ $row['staff'] }}</td>
                                <td class="px-4 py-3 text-black/70">{{ $row['access_point'] }}</td>
                                <td class="px-4 py-3 text-black/70">{{ $row['last_sync'] }}</td>
                                <td class="px-4 py-3">
                                    <flux:badge color="{{ $row['status'] === 'active' ? 'green' : 'red' }}" size="sm">{{ ucfirst($row['status']) }}</flux:badge>
                                </td>
                                <td class="px-4 py-3">
                                    <flux:badge color="{{ $row['data_invalidated'] ? 'red' : 'green' }}" size="sm">{{ $row['data_invalidated'] ? 'Yes' : 'No' }}</flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-black/70">No device data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts::admin>
