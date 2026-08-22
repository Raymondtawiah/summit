<x-layouts::admin :title="__('Attendance Management')">
    <div class="flex flex-col gap-6 p-6">
        <div>
            <flux:heading size="xl">Attendance Management</flux:heading>
            <flux:text class="mt-1 text-black/70">
                Monitor and review attendance across all scan points.
            </flux:text>
        </div>

        <div class="grid auto-rows-min gap-4 md:grid-cols-4">
            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-xs text-black/70">Total Scans</flux:text>
                <flux:heading size="lg" class="mt-1 text-black">{{ number_format($stats['total_scans']) }}</flux:heading>
            </div>
            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-xs text-black/70">Today's Scans</flux:text>
                <flux:heading size="lg" class="mt-1 text-green-600">{{ number_format($stats['today_scans']) }}</flux:heading>
            </div>
            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-xs text-black/70">Successful Scans</flux:text>
                <flux:heading size="lg" class="mt-1 text-green-600">{{ number_format($stats['successful_scans']) }}</flux:heading>
            </div>
            <div class="relative overflow-hidden rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-xs text-black/70">Rejected Scans</flux:text>
                <flux:heading size="lg" class="mt-1 text-red-600">{{ number_format($stats['rejected_scans']) }}</flux:heading>
            </div>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <form method="GET" action="{{ route('admin.attendance') }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <flux:label>Search</flux:label>
                        <flux:input type="search" name="search" value="{{ request('search') }}" placeholder="Search by participant name, registration number, or ticket..." />
                    </div>
                    <div>
                        <flux:label>Scan Point</flux:label>
                        <flux:select name="scan_point_id">
                            <option value="">All</option>
                            @foreach($filterOptions['scan_points'] as $scanPoint)
                                <option value="{{ $scanPoint->id }}" {{ request('scan_point_id') == $scanPoint->id ? 'selected' : '' }}>{{ $scanPoint->name }}</option>
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
                        <flux:label>Scan Mode</flux:label>
                        <flux:select name="scan_mode">
                            <option value="">All</option>
                            <option value="online" {{ request('scan_mode') === 'online' ? 'selected' : '' }}>Online</option>
                            <option value="offline" {{ request('scan_mode') === 'offline' ? 'selected' : '' }}>Offline</option>
                        </flux:select>
                    </div>
                    <div>
                        <flux:label>Access Type</flux:label>
                        <flux:select name="access_type">
                            <option value="">All</option>
                            @foreach($filterOptions['access_types'] as $type)
                                <option value="{{ $type }}" {{ request('access_type') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
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
                    <div>
                        <flux:label>Date</flux:label>
                        <flux:input type="date" name="date" value="{{ request('date') }}" />
                    </div>
                    <div class="flex gap-2">
                        <flux:button variant="primary" type="submit">Filter</flux:button>
                        <flux:button variant="ghost" href="{{ route('admin.attendance') }}">Reset</flux:button>
                    </div>
                </form>
            </div>
            <div class="p-6">
                @if($attendanceLogs->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-black/5 bg-black/5 text-xs uppercase text-black/70">
                                <tr>
                                    <th class="px-4 py-3">Participant</th>
                                    <th class="px-4 py-3">Registration No.</th>
                                    <th class="px-4 py-3">Ticket</th>
                                    <th class="px-4 py-3">Staff</th>
                                    <th class="px-4 py-3">Scan Point</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">Result</th>
                                    <th class="px-4 py-3">Mode</th>
                                    <th class="px-4 py-3">Date/Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/5">
                                @foreach($attendanceLogs as $log)
                                    <tr class="hover:bg-black/5">
                                        <td class="px-4 py-3 font-medium text-black">{{ $log->participant->full_name ?? '—' }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-black/70">{{ $log->participant->registration_number ?? '—' }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-black/70">{{ $log->ticket->ticket_number ?? '—' }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $log->staff->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ $log->scanPoint->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-black/70">{{ ucfirst($log->scanPoint->type ?? '—') }}</td>
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
                                        <td class="px-4 py-3">
                                            <flux:badge color="{{ $log->scan_mode === 'online' ? 'green' : 'yellow' }}" size="sm">
                                                {{ ucfirst($log->scan_mode) }}
                                            </flux:badge>
                                        </td>
                                        <td class="px-4 py-3 text-black/70">{{ $log->scanned_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $attendanceLogs->links() }}
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
