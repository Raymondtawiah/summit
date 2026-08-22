<x-layouts::admin :title="__('Admin Dashboard')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" class="text-black dark:text-white">Summit Dashboard</flux:heading>
                <flux:text class="mt-1 text-black/70">
                    Welcome back, {{ auth()->user()->name }}. Here is your summit management overview.
                </flux:text>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button variant="primary" href="{{ route('admin.import') }}" wire:navigate>
                    <flux:icon name="arrow-up-tray" class="mr-2 h-4 w-4" />
                    Import Participants
                </flux:button>
                <flux:button variant="filled" href="{{ route('admin.tickets') }}" wire:navigate>
                    <flux:icon name="printer" class="mr-2 h-4 w-4" />
                    Print Tickets
                </flux:button>
                        <flux:button variant="ghost" href="{{ route('admin.reports.attendance') }}" wire:navigate>
                            <flux:icon name="chart-bar" class="mr-2 h-4 w-4" />
                            Reports
                        </flux:button>
            </div>
        </div>

        <div class="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-6">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600/10">
                        <flux:icon name="users" class="h-4 w-4 text-blue-600" />
                    </div>
                    <flux:text class="text-sm text-black/70">Total Participants</flux:text>
                </div>
                <flux:heading size="2xl" class="mt-3 text-black">{{ number_format($summary['participants']['total']) }}</flux:heading>
                <flux:text class="mt-1 text-xs text-black/50">
                    {{ number_format($summary['participants']['scanned']) }} scanned ({{ $summary['participants']['participation_rate'] }}%)
                </flux:text>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-6">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-600/10">
                        <flux:icon name="ticket" class="h-4 w-4 text-green-600" />
                    </div>
                    <flux:text class="text-sm text-black/70">Active Tickets</flux:text>
                </div>
                <flux:heading size="2xl" class="mt-3 text-black">{{ number_format($summary['tickets']['active']) }}</flux:heading>
                <flux:text class="mt-1 text-xs text-black/50">
                    {{ number_format($summary['tickets']['revoked']) }} revoked
                </flux:text>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-6">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600/10">
                        <flux:icon name="clipboard-document-check" class="h-4 w-4 text-blue-600" />
                    </div>
                    <flux:text class="text-sm text-black/70">Total Scans</flux:text>
                </div>
                <flux:heading size="2xl" class="mt-3 text-black">{{ number_format($summary['attendance']['total_scans']) }}</flux:heading>
                <flux:text class="mt-1 text-xs text-black/50">
                    {{ number_format($summary['attendance']['today_scans']) }} today
                </flux:text>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-6">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-yellow-600/10">
                        <flux:icon name="clock" class="h-4 w-4 text-yellow-600" />
                    </div>
                    <flux:text class="text-sm text-black/70">Pending Sync</flux:text>
                </div>
                <flux:heading size="2xl" class="mt-3 text-black">{{ number_format($summary['attendance']['pending_sync']) }}</flux:heading>
                <flux:text class="mt-1 text-xs text-black/50">
                    {{ number_format($summary['attendance']['failed_sync']) }} failed · {{ number_format($summary['attendance']['conflict_sync']) }} conflicts
                </flux:text>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white">
                <div class="border-b border-black/5 px-6 py-4">
                    <flux:heading size="lg">Attendance Overview</flux:heading>
                </div>
                <div class="p-6">
                    <canvas id="attendanceChart" height="200"></canvas>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white">
                <div class="border-b border-black/5 px-6 py-4">
                    <flux:heading size="lg">Access Point Performance</flux:heading>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($accessPointPerformance as $point)
                            <div class="flex items-center justify-between">
                                <div>
                                    <flux:text class="text-sm font-medium text-black">{{ $point['name'] }}</flux:text>
                                    <flux:text class="text-xs text-black/50">{{ ucfirst($point['type']) }}</flux:text>
                                </div>
                                <div class="text-right">
                                    <flux:text class="text-sm font-medium text-black">{{ $point['granted'] }} granted</flux:text>
                                    <flux:text class="text-xs text-black/50">{{ $point['duplicates'] }} dup · {{ $point['denied'] }} denied</flux:text>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <flux:heading size="lg">Recent Scans</flux:heading>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-black/5 bg-black/5 text-xs uppercase text-black/70">
                        <tr>
                            <th class="px-4 py-3">Participant</th>
                            <th class="px-4 py-3">Registration No.</th>
                            <th class="px-4 py-3">Access Point</th>
                            <th class="px-4 py-3">Staff</th>
                            <th class="px-4 py-3">Time</th>
                            <th class="px-4 py-3">Mode</th>
                            <th class="px-4 py-3">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse($recentScans as $scan)
                            <tr class="hover:bg-black/5">
                                <td class="px-4 py-3 font-medium text-black">{{ $scan['participant'] }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-black/70">{{ $scan['registration_number'] }}</td>
                                <td class="px-4 py-3 text-black/70">{{ $scan['access_point'] }}</td>
                                <td class="px-4 py-3 text-black/70">{{ $scan['staff'] }}</td>
                                <td class="px-4 py-3 text-black/70">{{ $scan['time'] }}</td>
                                <td class="px-4 py-3">
                                    <flux:badge color="{{ $scan['mode'] === 'online' ? 'green' : 'yellow' }}" size="sm">
                                        {{ ucfirst($scan['mode']) }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $resultColor = match($scan['result']) {
                                            'access_granted', 'valid' => 'green',
                                            'duplicate' => 'yellow',
                                            default => 'red',
                                        };
                                    @endphp
                                    <flux:badge color="{{ $resultColor }}" size="sm">{{ ucwords(str_replace('_', ' ', $scan['result'] ?? '—')) }}</flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-black/70">No recent scans.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('attendanceChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode(collect($attendanceOverTime)->pluck('date')) !!},
                    datasets: [{
                        label: 'Scans',
                        data: {!! json_encode(collect($attendanceOverTime)->pluck('scans')) !!},
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        }
    </script>
    @endpush
</x-layouts::admin>
