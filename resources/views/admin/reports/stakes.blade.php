<x-layouts::admin :title="__('Stake/District Report')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Stake/District Report</flux:heading>
                <flux:text class="mt-1 text-black/70">Participation by Stake/District.</flux:text>
            </div>
            <a href="{{ route('admin.reports.export', ['type' => 'stakes', 'format' => 'csv']) }}" class="rounded-lg bg-black px-3 py-2 text-xs text-white hover:bg-black/80">Export CSV</a>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-black/5 bg-black/5 text-xs uppercase text-black/70">
                        <tr>
                            <th class="px-4 py-3">Stake/District</th>
                            <th class="px-4 py-3">Registered</th>
                            <th class="px-4 py-3">Scanned</th>
                            <th class="px-4 py-3">Percentage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse($report as $row)
                            <tr class="hover:bg-black/5">
                                <td class="px-4 py-3 font-medium text-black">{{ $row['stake_district'] }}</td>
                                <td class="px-4 py-3 text-black/70">{{ $row['registered'] }}</td>
                                <td class="px-4 py-3 text-black/70">{{ $row['scanned'] }}</td>
                                <td class="px-4 py-3">
                                    <flux:badge color="{{ $row['percentage'] >= 80 ? 'green' : ($row['percentage'] >= 50 ? 'yellow' : 'red') }}" size="sm">{{ $row['percentage'] }}%</flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-black/70">No stake/district data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts::admin>
