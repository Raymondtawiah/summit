<x-layouts::admin :title="__('Staff Details')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('admin.staff') }}" wire:navigate>
                <flux:icon name="arrow-left" class="mr-2 h-4 w-4" />
                Back to Staff
            </flux:button>
            <flux:button variant="primary" :href="route('admin.staff.edit', $staff)" wire:navigate>
                <flux:icon name="pencil" class="mr-2 h-4 w-4" />
                Edit Staff
            </flux:button>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="md:col-span-2 space-y-6">
                <div class="rounded-xl border border-black/5 bg-white">
                    <div class="border-b border-black/5 px-6 py-4">
                        <flux:heading size="md">Staff Information</flux:heading>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <dt class="text-sm text-black/70">Full Name</dt>
                                <dd class="mt-1 font-medium text-black">{{ $staff->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Email</dt>
                                <dd class="mt-1 font-medium text-black">{{ $staff->email }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Role</dt>
                                <dd class="mt-1 font-medium text-black">Staff</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Status</dt>
                                <dd class="mt-1">
                                    @if($staff->status === 'active')
                                        <flux:badge color="green" size="sm">Active</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Assigned Scan Point</dt>
                                <dd class="mt-1 font-medium text-black">{{ $staff->scanPoint->name ?? 'Unassigned' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Last Login</dt>
                                <dd class="mt-1 font-medium text-black">{{ $staff->last_login_at?->format('Y-m-d H:i') ?? 'Never' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Created Date</dt>
                                <dd class="mt-1 font-medium text-black">{{ $staff->created_at->format('Y-m-d H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Updated Date</dt>
                                <dd class="mt-1 font-medium text-black">{{ $staff->updated_at->format('Y-m-d H:i') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="rounded-xl border border-black/5 bg-white">
                    <div class="border-b border-black/5 px-6 py-4">
                        <flux:heading size="md">Attendance Activity</flux:heading>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 gap-4 md:grid-cols-5 mb-6">
                            <div>
                                <dt class="text-sm text-black/70">Total Scans</dt>
                                <dd class="mt-1 font-medium text-black">{{ $attendanceStats['total_scans'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Today's Scans</dt>
                                <dd class="mt-1 font-medium text-black">{{ $attendanceStats['today_scans'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Online Scans</dt>
                                <dd class="mt-1 font-medium text-black">{{ $attendanceStats['online_scans'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Offline Scans</dt>
                                <dd class="mt-1 font-medium text-black">{{ $attendanceStats['offline_scans'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Last Scan</dt>
                                <dd class="mt-1 font-medium text-black">{{ $attendanceStats['last_scan']?->scanned_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                            </div>
                        </dl>

                        @if($staff->attendanceLogs->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="border-b border-black/5 bg-black/[0.02]">
                                        <tr>
                                            <th class="px-4 py-2 font-medium">Participant</th>
                                            <th class="px-4 py-2 font-medium">Registration Number</th>
                                            <th class="px-4 py-2 font-medium">Scan Point</th>
                                            <th class="px-4 py-2 font-medium">Scan Mode</th>
                                            <th class="px-4 py-2 font-medium">Date/Time</th>
                                            <th class="px-4 py-2 font-medium">Sync</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-black/5">
                                        @foreach($staff->attendanceLogs as $log)
                                            <tr>
                                                <td class="px-4 py-3">{{ $log->participant->full_name ?? '—' }}</td>
                                                <td class="px-4 py-3">{{ $log->participant->registration_number ?? '—' }}</td>
                                                <td class="px-4 py-3">{{ $log->scanPoint->name ?? '—' }}</td>
                                                <td class="px-4 py-3">
                                                    <flux:badge color="{{ $log->scan_mode === 'online' ? 'green' : 'yellow' }}" size="sm">
                                                        {{ ucfirst($log->scan_mode) }}
                                                    </flux:badge>
                                                </td>
                                                <td class="px-4 py-3">{{ $log->scanned_at->format('Y-m-d H:i') }}</td>
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
                <div class="rounded-xl border border-black/5 bg-white p-6">
                    <flux:heading size="md" class="mb-4">Quick Info</flux:heading>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Role</flux:text>
                            <flux:text class="text-black">Staff</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Status</flux:text>
                            @if($staff->status === 'active')
                                <flux:badge color="green" size="sm">Active</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Scan Point</flux:text>
                            <flux:text class="text-black">{{ $staff->scanPoint->name ?? 'Unassigned' }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Total Scans</flux:text>
                            <flux:text class="text-black">{{ $attendanceStats['total_scans'] }}</flux:text>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-black/5 bg-white p-6">
                    <flux:heading size="md" class="mb-4">Assign Scan Point</flux:heading>
                    <form method="POST" action="{{ route('admin.staff.assign-scan-point', $staff) }}">
                        @csrf
                        <flux:select name="scan_point_id">
                            <option value="">None</option>
                            @foreach(\App\Models\ScanPoint::orderBy('name')->get(['id', 'name', 'location']) as $scanPoint)
                                <option value="{{ $scanPoint->id }}" {{ $staff->scan_point_id == $scanPoint->id ? 'selected' : '' }}>{{ $scanPoint->name }} - {{ $scanPoint->location }}</option>
                            @endforeach
                        </flux:select>
                        <flux:button variant="primary" type="submit" class="mt-3 w-full">Update Assignment</flux:button>
                    </form>
                </div>

                <div class="rounded-xl border border-black/5 bg-white p-6">
                    <flux:heading size="md" class="mb-4">Reset Password</flux:heading>
                    <form method="POST" action="{{ route('admin.staff.reset-password', $staff) }}" onsubmit="return confirm('Are you sure you want to reset this staff member\'s password?')">
                        @csrf
                        <flux:input type="password" name="password" placeholder="New password" required minlength="8" />
                        <flux:input type="password" name="password_confirmation" placeholder="Confirm new password" required class="mt-3" />
                        <flux:button type="submit" variant="outline" class="!border-yellow-500 !text-yellow-600 hover:!bg-yellow-50 mt-3 w-full">Reset Password</flux:button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts::admin>
