<x-layouts::staff :title="__('Synchronization')">
    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('staff.dashboard') }}" wire:navigate>
                <flux:icon name="arrow-left" class="mr-2 h-4 w-4" />
                Back to Dashboard
            </flux:button>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <flux:heading size="lg">Synchronization</flux:heading>
                <flux:text class="text-black/70 mt-1">Manage your device data and scans.</flux:text>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <flux:card class="border-black/10 bg-white p-4">
                        <flux:text class="text-xs text-black/70">Connection</flux:text>
                        <flux:text class="text-sm font-medium text-black">
                            @if($stats['online'])
                                <span class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                    Online
                                </span>
                            @else
                                <span class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                    Offline
                                </span>
                            @endif
                        </flux:text>
                    </flux:card>
                    <flux:card class="border-black/10 bg-white p-4">
                        <flux:text class="text-xs text-black/70">Data Version</flux:text>
                        <flux:text class="text-sm font-medium text-black">
                            @if($stats['data_invalidated'])
                                <span class="text-red-600">Data not downloaded</span>
                            @else
                                Version {{ $stats['device_version'] ?? 0 }}
                            @endif
                        </flux:text>
                    </flux:card>
                    <flux:card class="border-black/10 bg-white p-4">
                        <flux:text class="text-xs text-black/70">Last Sync</flux:text>
                        <flux:text class="text-sm font-medium text-black">{{ $stats['last_sync_at']?->diffForHumans() ?? 'Never' }}</flux:text>
                    </flux:card>
                    <flux:card class="border-black/10 bg-white p-4">
                        <flux:text class="text-xs text-black/70">Queued Scans</flux:text>
                        <flux:text class="text-sm font-medium text-black">{{ $queuedScans }}</flux:text>
                    </flux:card>
                    <flux:card class="border-black/10 bg-white p-4">
                        <flux:text class="text-xs text-black/70">Synced Today</flux:text>
                        <flux:text class="text-sm font-medium text-black">{{ $syncedScans }}</flux:text>
                    </flux:card>
                    <flux:card class="border-black/10 bg-white p-4">
                        <flux:text class="text-xs text-black/70">Failed</flux:text>
                        <flux:text class="text-sm font-medium text-black">{{ $failedScans }}</flux:text>
                    </flux:card>
                </div>

                @if($stats['update_available'] && !$stats['data_invalidated'])
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <flux:heading size="sm" class="text-blue-800 mb-1">New Data Available</flux:heading>
                        <flux:text class="text-sm text-blue-700">
                            A newer summit dataset is available. Current version: {{ $stats['device_version'] ?? 0 }}, Available: {{ $stats['dataset_version'] }}
                        </flux:text>
                    </div>
                @endif

                @if($stats['data_invalidated'])
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                        <flux:heading size="sm" class="text-red-800 mb-1">Offline Scanning Not Ready</flux:heading>
                        <flux:text class="text-sm text-red-700">
                            Connect to the Internet and download the summit data before working offline.
                        </flux:text>
                    </div>
                @endif

                <div class="flex flex-col gap-3">
                    <flux:button variant="primary" id="sync-now-btn" class="w-full">
                        <flux:icon name="arrow-path" class="mr-2 h-4 w-4" />
                        Sync Now
                    </flux:button>
                    <flux:button variant="outline" id="download-data-btn" class="w-full">
                        <flux:icon name="arrow-down-tray" class="mr-2 h-4 w-4" />
                        Update Data
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script type="module">
        import * as SyncService from '{{ Vite::asset('resources/js/sync-service.js') }}';

        document.getElementById('sync-now-btn')?.addEventListener('click', async () => {
            const btn = document.getElementById('sync-now-btn');
            btn.disabled = true;
            btn.innerHTML = '<span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent mr-2"></span>Syncing...';
            try {
                await SyncService.uploadQueuedScans();
                alert('Synchronization complete.');
                window.location.reload();
            } catch (e) {
                alert('Sync failed: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<flux:icon name="arrow-path" class="mr-2 h-4 w-4" />Sync Now';
            }
        });

        document.getElementById('download-data-btn')?.addEventListener('click', async () => {
            const btn = document.getElementById('download-data-btn');
            btn.disabled = true;
            btn.innerHTML = '<span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent mr-2"></span>Downloading...';
            try {
                const response = await fetch('/staff/api/sync/download', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Device-Token': (await SyncService.getDeviceInfo('device_uuid')) || '',
                    },
                    credentials: 'same-origin',
                });
                const data = await response.json();
                if (response.ok) {
                    await SyncService.persistDownloadedData(data);
                    alert('Data downloaded successfully.');
                    window.location.reload();
                } else {
                    alert(data.message || 'Download failed.');
                }
            } catch (e) {
                alert('Download failed: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<flux:icon name="arrow-down-tray" class="mr-2 h-4 w-4" />Update Data';
            }
        });
    </script>
    @endpush
</x-layouts::staff>
