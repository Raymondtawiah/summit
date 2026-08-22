<x-layouts::admin :title="__('Participation Report')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Participation Report</flux:heading>
                <flux:text class="mt-1 text-black/70">Participant attendance breakdown.</flux:text>
            </div>
            <a href="{{ route('admin.reports.export', ['type' => 'participation', 'format' => 'csv']) }}" class="rounded-lg bg-black px-3 py-2 text-xs text-white hover:bg-black/80">Export CSV</a>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-black/5 bg-black/5 text-xs uppercase text-black/70">
                        <tr>
                            <th class="px-4 py-3">Participant</th>
                            <th class="px-4 py-3">Registration Number</th>
                            <th class="px-4 py-3">Unit</th>
                            <th class="px-4 py-3">Stake/District</th>
                            <th class="px-4 py-3">Attended</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Percentage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse($report as $row)
                            <tr class="hover:bg-black/5">
                                <td class="px-4 py-3 font-medium text-black">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-black/70">{{ $row['registration_number'] }}</td>
                                <td class="px-4 py-3 text-black/70">{{ $row['unit'] }}</td>
                                <td class="px-4 py-3 text-black/70">{{ $row['stake_district'] }}</td>
                                <td class="px-4 py-3 text-black/70">{{ $row['attended'] }}</td>
                                <td class="px-4 py-3 text-black/70">{{ $row['total'] }}</td>
                                <td class="px-4 py-3">
                                    <flux:badge color="{{ $row['percentage'] >= 80 ? 'green' : ($row['percentage'] >= 50 ? 'yellow' : 'red') }}" size="sm">
                                        {{ $row['percentage'] }}%
                                    </flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-black/70">No participation data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts::admin>
