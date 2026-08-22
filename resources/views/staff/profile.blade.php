<x-layouts::staff :title="__('Profile')">
    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('staff.dashboard') }}" wire:navigate>
                <flux:icon name="arrow-left" class="mr-2 h-4 w-4" />
                Back to Dashboard
            </flux:button>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <flux:heading size="lg">Profile</flux:heading>
                <flux:text class="text-black/70 mt-1">Your account information.</flux:text>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-xs text-black/70">Name</flux:text>
                        <flux:text class="text-sm font-medium text-black">{{ $staff->name }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-black/70">Email</flux:text>
                        <flux:text class="text-sm font-medium text-black">{{ $staff->email }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-black/70">Account Status</flux:text>
                        <flux:badge :color="$staff->status === 'active' ? 'green' : 'red'" size="sm">
                            {{ ucfirst($staff->status) }}
                        </flux:badge>
                    </div>
                    <div>
                        <flux:text class="text-xs text-black/70">Role</flux:text>
                        <flux:text class="text-sm font-medium text-black">{{ ucfirst($staff->role) }}</flux:text>
                    </div>
                </div>

                <div class="border-t border-black/5 pt-4">
                    <flux:heading size="sm" class="text-black mb-2">Assigned Access Point</flux:heading>
                    @if($staff->scanPoint)
                        <div class="rounded-lg border border-black/5 bg-white p-3">
                            <div class="text-sm font-medium text-black">{{ $staff->scanPoint->name }}</div>
                            <div class="text-xs text-black/70">{{ $staff->scanPoint->location ?? 'No location' }}</div>
                            <flux:badge :color="$staff->scanPoint->status === 'active' ? 'green' : 'red'" size="sm" class="mt-1">
                                {{ ucfirst($staff->scanPoint->status) }}
                            </flux:badge>
                        </div>
                    @else
                        <flux:text class="text-sm text-black/70">No access point assigned.</flux:text>
                    @endif
                </div>

                @if($device)
                    <div class="border-t border-black/5 pt-4">
                        <flux:heading size="sm" class="text-black mb-2">Device Information</flux:heading>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <flux:text class="text-xs text-black/70">Device ID</flux:text>
                                <flux:text class="text-sm font-mono text-black">{{ $device->device_identifier }}</flux:text>
                            </div>
                            <div>
                                <flux:text class="text-xs text-black/70">Last Sync</flux:text>
                                <flux:text class="text-sm text-black">{{ $device->last_sync_at?->diffForHumans() ?? 'Never' }}</flux:text>
                            </div>
                            <div>
                                <flux:text class="text-xs text-black/70">Data Version</flux:text>
                                <flux:text class="text-sm text-black">{{ $device->dataset_version ?? 'Not downloaded' }}</flux:text>
                            </div>
                            <div>
                                <flux:text class="text-xs text-black/70">Status</flux:text>
                                <flux:badge :color="$device->status === 'active' ? 'green' : 'red'" size="sm">
                                    {{ ucfirst($device->status) }}
                                </flux:badge>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <flux:heading size="lg">Change Password</flux:heading>
            </div>
            <div class="p-6">
                @if(session('status'))
                    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('staff.profile.password') }}" class="space-y-4">
                    @csrf
                    <div>
                        <flux:label for="current_password">Current Password</flux:label>
                        <flux:input id="current_password" name="current_password" type="password" class="mt-1" required autocomplete="current-password" />
                        @error('current_password')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </div>
                    <div>
                        <flux:label for="password">New Password</flux:label>
                        <flux:input id="password" name="password" type="password" class="mt-1" required autocomplete="new-password" />
                        @error('password')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </div>
                    <div>
                        <flux:label for="password_confirmation">Confirm New Password</flux:label>
                        <flux:input id="password_confirmation" name="password_confirmation" type="password" class="mt-1" required autocomplete="new-password" />
                    </div>
                    <flux:button type="submit" variant="primary">Update Password</flux:button>
                </form>
            </div>
        </div>
    </div>
</x-layouts::staff>
