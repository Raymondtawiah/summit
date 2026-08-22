<x-layouts::staff :title="__('Staff Dashboard')">
    <div class="flex flex-col gap-6 p-4">
        <div class="text-center">
            <flux:heading size="xl" class="text-black dark:text-white">LDS SUMMITPASS</flux:heading>
            <flux:text class="text-black/70 dark:text-white/70">STAFF PORTAL</flux:text>
        </div>

        <div class="rounded-xl border border-black/10 bg-white p-4">
            <flux:text class="text-sm text-black/70">Welcome</flux:text>
            <flux:heading size="lg" class="text-black">{{ $staff->name }}</flux:heading>
        </div>

        @if($scanPoint)
            <div class="rounded-xl border border-black/10 bg-white p-4">
                <flux:text class="text-xs text-black/70 uppercase tracking-wide">Current Duty</flux:text>
                <flux:heading size="lg" class="text-black mt-1">{{ $scanPoint->name }}</flux:heading>
                <div class="mt-2 flex flex-wrap gap-2 text-xs text-black/70">
                    @if($scanPoint->start_time || $scanPoint->end_time)
                        <span class="rounded-full border border-black/10 px-2 py-0.5">
                            {{ $scanPoint->start_time?->format('H:i') ?? '--:--' }} – {{ $scanPoint->end_time?->format('H:i') ?? '--:--' }}
                        </span>
                    @endif
                    <span class="rounded-full border border-black/10 px-2 py-0.5">{{ ucwords(str_replace('_', ' ', $scanPoint->duplicate_rule)) }}</span>
                </div>
                <div class="mt-2">
                    <flux:badge :color="$isReady ? 'green' : 'red'" size="sm">
                        {{ $isReady ? 'ACTIVE' : 'INACTIVE' }}
                    </flux:badge>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                <flux:text class="text-sm text-red-700">No scan point assigned. Please contact the administrator.</flux:text>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-4">
            <flux:card class="border-black/10 bg-white p-4">
                <flux:text class="text-xs text-black/70">Today's Scans</flux:text>
                <flux:heading size="lg" class="text-black">{{ $stats['total'] }}</flux:heading>
            </flux:card>
            <flux:card class="border-black/10 bg-white p-4">
                <flux:text class="text-xs text-black/70">Successful</flux:text>
                <flux:heading size="lg" class="text-green-600">{{ $stats['successful'] }}</flux:heading>
            </flux:card>
            <flux:card class="border-black/10 bg-white p-4">
                <flux:text class="text-xs text-black/70">Duplicates</flux:text>
                <flux:heading size="lg" class="text-yellow-600">{{ $stats['duplicates'] }}</flux:heading>
            </flux:card>
            <flux:card class="border-black/10 bg-white p-4">
                <flux:text class="text-xs text-black/70">Pending Sync</flux:text>
                <flux:heading size="lg" class="text-black">{{ $stats['pending_sync'] }}</flux:heading>
            </flux:card>
        </div>

        <div class="flex flex-col gap-3">
            <flux:button variant="primary" href="{{ route('staff.scanner') }}" wire:navigate class="w-full">
                <flux:icon name="qr-code" class="mr-2 h-4 w-4" />
                Open Scanner
            </flux:button>
            <flux:button variant="ghost" href="{{ route('staff.scans') }}" wire:navigate class="w-full">
                <flux:icon name="clock" class="mr-2 h-4 w-4" />
                Recent Scans
            </flux:button>
            <flux:button variant="ghost" href="{{ route('staff.sync') }}" wire:navigate class="w-full">
                <flux:icon name="arrow-path" class="mr-2 h-4 w-4" />
                Synchronization
            </flux:button>
            <flux:button variant="ghost" href="{{ route('staff.profile') }}" wire:navigate class="w-full">
                <flux:icon name="user" class="mr-2 h-4 w-4" />
                Profile
            </flux:button>
        </div>

        @if($device)
            <div class="rounded-xl border border-black/10 bg-white p-4">
                <flux:text class="text-xs text-black/70">Device</flux:text>
                <flux:text class="text-sm font-medium text-black">{{ $device->device_identifier }}</flux:text>
                <flux:text class="text-xs text-black/70">Last sync: {{ $device->last_sync_at?->diffForHumans() ?? 'Never' }}</flux:text>
            </div>
        @endif
    </div>
</x-layouts::staff>
