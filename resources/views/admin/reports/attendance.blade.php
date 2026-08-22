<x-layouts::admin :title="__('Attendance Report')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Attendance Report</flux:heading>
                <flux:text class="mt-1 text-black/70">Detailed attendance records.</flux:text>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.export', ['type' => 'attendance', 'format' => 'csv']) }}" class="rounded-lg bg-black px-3 py-2 text-xs text-white hover:bg-black/80">Export CSV</a>
                <a href="{{ route('admin.reports.export', ['type' => 'attendance', 'format' => 'excel']) }}" class="rounded-lg bg-black px-3 py-2 text-xs text-white hover:bg-black/80">Export Excel</a>
            </div>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <form method="GET" action="{{ route('admin.reports.attendance') }}" class="flex flex-wrap items-end gap-4">
                    <div>
                        <flux:label>Date</flux:label>
                        <flux:input type="date" name="date" value="{{ request('date') }}" />
                    </div>
                    <div>
                        <flux:label>Access Point</flux:label>
                        <flux:select name="access_point_id">
                            <option value="">All</option>
                            @foreach($filterOptions['access_points'] as $ap)
                                <option value="{{ $ap->id }}" {{ request('access_point_id') == $ap->id ? 'selected' : '' }}>{{ $ap->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div>
                        <flux:label>Staff</flux:label>
                        <flux:select name="staff_id">
                            <option value="">All</option>
                            @foreach($filterOptions['staff'] as $staff)
                                <option value="{{ $staff->id }}" {{ request('staff_id') == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div>
                        <flux:label>Result</flux:label>
                        <flux:select name="result">
                            <option value="">All</option>
                            @foreach($filterOptions['results'] as $result)
                                <option value="{{ $result }}" {{ request('result') === $result ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $result)) }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="flex gap-2">
                        <flux:button variant="primary" type="submit">Filter</flux:button>
                        <flux:button variant="ghost" href="{{ route('admin.reports.attendance') }}">Reset</flux:button>
                    </div>
                </form>
            </div>
            <div class="p-6">
                @if($report->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-black/5 bg-black/5 text-xs uppercase text-black/70">
                                <tr>
                                    <th class="px-4 py-3">Participant</th>
                                    <th class="px-4 py-3">Registration No.</th>
                                    <th class="px-4 py-3">Unit</th>
                                    <th class="px-4 py-3">Stake/District</th>
                                    <th class="px-4 py-3">Access Point</th>
                                    <th class="px-4 py-3">Staff</th>
                                    <th class="px-4 py-3">Device</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Time</th>
                                    <th class="px-4 py-3">Mode</th>
                                    <th class="px-4 py-3">Result</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/5">
                                @foreach($report as $log)
                                    <tr class="hover:bg-black/5">
                                        <td class="px-4 py-3 font-medium text-black">{{ $log->participant->full_name ?? '—' }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-black/70">{{ $log->participant->registration_number ?? '—' }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $log->participant->unit ?? '—' }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $log->participant->stake_district ?? '—' }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $log->scanPoint->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $log->staff->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $log->device->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $log->scanned_at?->format('Y-m-d') }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $log->scanned_at?->format('H:i') }}</td>
                                        <td class="px-4 py-3">
                                            <flux:badge color="{{ $log->scan_mode === 'online' ? 'green' : 'yellow' }}" size="sm">{{ ucfirst($log->scan_mode) }}</flux:badge>
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $resultColor = match($log->result) {
                                                    'access_granted', 'valid' => 'green',
                                                    'duplicate' => 'yellow',
                                                    default => 'red',
                                                };
                                            @endphp
                                            <flux:badge color="{{ $resultColor }}" size="sm">{{ ucwords(str_replace('_', ' ', $log->result ?? '—')) }}</flux:badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $report->links() }}
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center gap-3 p-12">
                        <flux:icon name="clipboard-document-check" class="h-12 w-12 text-black/20" />
                        <flux:text class="text-black/70">No attendance records found.</flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::admin>
