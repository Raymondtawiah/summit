<x-layouts::admin :title="__('Scan Point Details')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('admin.scan-points') }}" wire:navigate>
                <flux:icon name="arrow-left" class="mr-2 h-4 w-4" />
                Back to Scan Points
            </flux:button>
            <flux:button variant="primary" :href="route('admin.scan-points.edit', $scanPoint)" wire:navigate>
                <flux:icon name="pencil" class="mr-2 h-4 w-4" />
                Edit Scan Point
            </flux:button>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="md:col-span-2 space-y-6">
                <div class="rounded-xl border border-black/5 bg-white">
                    <div class="border-b border-black/5 px-6 py-4">
                        <flux:heading size="md">Scan Point Information</flux:heading>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <dt class="text-sm text-black/70">Name</dt>
                                <dd class="mt-1 font-medium text-black">{{ $scanPoint->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Location</dt>
                                <dd class="mt-1 font-medium text-black">{{ $scanPoint->location }}</dd>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-sm text-black/70">Description</dt>
                                <dd class="mt-1 font-medium text-black">{{ $scanPoint->description ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Status</dt>
                                <dd class="mt-1">
                                    @if($scanPoint->status === 'active')
                                        <flux:badge color="green" size="sm">Active</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Created Date</dt>
                                <dd class="mt-1 font-medium text-black">{{ $scanPoint->created_at->format('Y-m-d H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-black/70">Updated Date</dt>
                                <dd class="mt-1 font-medium text-black">{{ $scanPoint->updated_at->format('Y-m-d H:i') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="rounded-xl border border-black/5 bg-white">
                    <div class="border-b border-black/5 px-6 py-4">
                        <flux:heading size="md">Assigned Staff</flux:heading>
                    </div>
                    <div class="p-6">
                        @if($assignedStaff->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="border-b border-black/5 bg-black/[0.02]">
                                        <tr>
                                            <th class="px-4 py-2 font-medium">Name</th>
                                            <th class="px-4 py-2 font-medium">Email</th>
                                            <th class="px-4 py-2 font-medium">Status</th>
                                            <th class="px-4 py-2 font-medium">Last Login</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-black/5">
                                        @foreach($assignedStaff as $staff)
                                            <tr>
                                                <td class="px-4 py-3">
                                                    <flux:button size="sm" variant="ghost" :href="route('admin.staff.show', $staff)" wire:navigate>{{ $staff->name }}</flux:button>
                                                </td>
                                                <td class="px-4 py-3">{{ $staff->email }}</td>
                                                <td class="px-4 py-3">
                                                    @if($staff->status === 'active')
                                                        <flux:badge color="green" size="sm">Active</flux:badge>
                                                    @else
                                                        <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3">{{ $staff->last_login_at?->format('Y-m-d H:i') ?? 'Never' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <flux:text class="text-black/70">No staff assigned to this scan point.</flux:text>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl border border-black/5 bg-white">
                    <div class="border-b border-black/5 px-6 py-4">
                        <flux:heading size="md">Recent Scanning Activity</flux:heading>
                    </div>
                    <div class="p-6">
                        @if($recentActivity->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="border-b border-black/5 bg-black/[0.02]">
                                        <tr>
                                            <th class="px-4 py-2 font-medium">Participant</th>
                                            <th class="px-4 py-2 font-medium">Registration Number</th>
                                            <th class="px-4 py-2 font-medium">Staff</th>
                                            <th class="px-4 py-2 font-medium">Scan Mode</th>
                                            <th class="px-4 py-2 font-medium">Date/Time</th>
                                            <th class="px-4 py-2 font-medium">Sync</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-black/5">
                                        @foreach($recentActivity as $log)
                                            <tr>
                                                <td class="px-4 py-3">{{ $log->participant->full_name ?? '—' }}</td>
                                                <td class="px-4 py-3">{{ $log->participant->registration_number ?? '—' }}</td>
                                                <td class="px-4 py-3">{{ $log->staff->name ?? '—' }}</td>
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
                            <flux:text class="text-black/70">No scanning activity yet.</flux:text>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-black/5 bg-white p-6">
                    <flux:heading size="md" class="mb-4">Quick Info</flux:heading>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Status</flux:text>
                            @if($scanPoint->status === 'active')
                                <flux:badge color="green" size="sm">Active</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Assigned Staff</flux:text>
                            <flux:text class="text-black">{{ $assignedStaff->count() }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-black/70">Total Scans</flux:text>
                            <flux:text class="text-black">{{ $scanPoint->attendanceLogs()->count() }}</flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts::admin>
