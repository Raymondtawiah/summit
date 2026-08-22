<x-layouts::staff :title="__('Scanner')">
    @php
        $staff = auth()->user();
        $scanPoint = $staff->scanPoint;
        $isReady = false;
        $readyMessage = 'Scanner is not ready.';

        if ($staff->role === 'staff' && $staff->status === 'active') {
            if ($scanPoint && $scanPoint->status === 'active') {
                $isReady = true;
                $readyMessage = 'Scanner is ready.';
            } elseif (!$scanPoint) {
                $readyMessage = 'No scan point has been assigned to your account. Please contact the administrator.';
            } elseif ($scanPoint->status !== 'active') {
                $readyMessage = 'This scan point is currently inactive. Please contact the administrator.';
            }
        } elseif ($staff->status !== 'active') {
            $readyMessage = 'Your account is inactive. Please contact the administrator.';
        }
    @endphp

    <div class="flex flex-col items-center gap-6 p-4">
        <div class="w-full max-w-lg">
            <div class="text-center mb-6">
                <flux:heading size="xl" class="text-black dark:text-white">SUMMIT STAFF SCANNER</flux:heading>
                <flux:text class="mt-2 text-black/70 dark:text-white/70">
                    {{ $readyMessage }}
                </flux:text>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <flux:card class="border-black/10 bg-white p-4">
                    <flux:text class="text-xs text-black/70">Staff</flux:text>
                    <flux:text class="text-sm font-medium text-black">{{ $staff->name }}</flux:text>
                </flux:card>
                <flux:card class="border-black/10 bg-white p-4">
                    <flux:text class="text-xs text-black/70">Scan Point</flux:text>
                    <flux:text class="text-sm font-medium text-black">{{ $scanPoint->name ?? 'Not assigned' }}</flux:text>
                </flux:card>
                <flux:card class="border-black/10 bg-white p-4">
                    <flux:text class="text-xs text-black/70">Status</flux:text>
                    <flux:text class="text-sm font-medium text-black">
                        @if($isReady)
                            <flux:badge color="green" size="sm" id="scanner-status-badge">READY</flux:badge>
                        @else
                            <flux:badge color="red" size="sm">NOT READY</flux:badge>
                        @endif
                    </flux:text>
                </flux:card>
                <flux:card class="border-black/10 bg-white p-4">
                    <flux:text class="text-xs text-black/70">Connection</flux:text>
                    <flux:text class="text-sm font-medium text-black">
                        <span id="status-dot" class="inline-block h-2 w-2 rounded-full bg-green-500"></span>
                        <span id="status-text">Online</span>
                    </flux:text>
                </flux:card>
            </div>

            @if($isReady && $scanPoint)
                <div class="mb-4 rounded-xl border border-black/10 bg-white p-4">
                    <div class="text-xs text-black/70">ACCESS POINT</div>
                    <div class="text-lg font-semibold text-black">{{ $scanPoint->name }}</div>
                    <div class="mt-1 flex flex-wrap gap-2 text-xs text-black/70">
                        <span class="rounded-full border border-black/10 px-2 py-0.5">{{ ucfirst($scanPoint->type) }}</span>
                        @if($scanPoint->start_time || $scanPoint->end_time)
                            <span class="rounded-full border border-black/10 px-2 py-0.5">
                                {{ $scanPoint->start_time?->format('H:i') ?? '--:--' }} – {{ $scanPoint->end_time?->format('H:i') ?? '--:--' }}
                            </span>
                        @endif
                        <span class="rounded-full border border-black/10 px-2 py-0.5">{{ ucwords(str_replace('_', ' ', $scanPoint->duplicate_rule)) }}</span>
                    </div>
                </div>
            @endif

            <div class="mb-4 rounded-xl border border-black/10 bg-white p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs text-black/70">Data</div>
                        <div class="text-sm font-medium text-black" id="sync-data-status">Checking...</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-black/70">Today's Scans</div>
                        <div class="text-sm font-medium text-black" id="today-scans-count">0</div>
                    </div>
                </div>
                <div class="mt-1 flex items-center justify-between">
                    <div class="text-right">
                        <div class="text-xs text-black/70">Queued Scans</div>
                        <div class="text-sm font-medium text-black" id="queued-scans-count">0</div>
                    </div>
                </div>
                <div class="mt-3 flex gap-2" id="sync-actions" style="display: none;">
                    <button id="download-data-btn" class="rounded-lg bg-black px-3 py-2 text-xs text-white hover:bg-black/80">Download Data</button>
                    <button id="sync-now-btn" class="rounded-lg bg-black px-3 py-2 text-xs text-white hover:bg-black/80">Sync Now</button>
                </div>
            </div>

            <div id="scanner-container" class="w-full rounded-xl border border-black/10 bg-white overflow-hidden mb-4">
                <div id="reader" class="w-full"></div>
            </div>

            <div id="scan-result" class="hidden w-full rounded-xl border p-4 mb-4">
                <div id="result-content"></div>
            </div>

            <div id="recent-scans" class="w-full">
                <flux:heading size="sm" class="mb-3 text-black">Recent Scans</flux:heading>
                <div id="recent-scans-list" class="space-y-2">
                    <flux:text class="text-sm text-black/70">No scans yet.</flux:text>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script type="module">
        import { Html5Qrcode } from 'html5-qrcode';
        import * as SyncService from '{{ Vite::asset('resources/js/sync-service.js') }}';
        import { verifyOffline, verifyOnline } from '{{ Vite::asset('resources/js/scanner-service.js') }}';

        const scanApiUrl = @json(route('staff.api.scan'));
        const todayScansUrl = @json(route('staff.api.scans.today'));
        const isReady = @json($isReady);
        const staffId = @json(Auth::user()->id);
        const scanPointId = @json($scanPoint->id ?? null);

        let html5QrCode = null;
        let isScanning = false;
        let lastSyncStatus = null;
        let soundEnabled = true;

        function playSound(type) {
            if (!soundEnabled) return;
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                if (type === 'success') {
                    oscillator.frequency.value = 800;
                    gainNode.gain.value = 0.1;
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.1);
                } else if (type === 'denied') {
                    oscillator.frequency.value = 300;
                    gainNode.gain.value = 0.1;
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.2);
                }
            } catch (e) {
                console.error('Sound error', e);
            }
        }

        function vibrate(pattern) {
            if (navigator.vibrate) {
                navigator.vibrate(pattern);
            }
        }

        async function initDevice() {
            const deviceUuid = await SyncService.initializeDevice(staffId, null);
            await SyncService.setDeviceInfo('staff_id', staffId);
            if (scanPointId) {
                await SyncService.setDeviceInfo('scan_point_id', scanPointId);
            }
            return deviceUuid;
        }

        async function refreshSyncStatus() {
            const status = await SyncService.getSyncStatus();
            lastSyncStatus = status;
            const dataStatus = document.getElementById('sync-data-status');
            const queuedCount = document.getElementById('queued-scans-count');
            const syncActions = document.getElementById('sync-actions');

            if (status.dataInvalidated || !status.datasetVersion) {
                dataStatus.textContent = 'Data not available';
                syncActions.style.display = 'flex';
                return;
            }

            const lastSync = status.lastSyncAt ? new Date(status.lastSyncAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Never';
            dataStatus.textContent = `Version ${status.datasetVersion} · Last sync ${lastSync}`;
            queuedCount.textContent = status.queuedCount || '0';

            if (!status.datasetVersion || status.dataInvalidated) {
                syncActions.style.display = 'flex';
            } else {
                syncActions.style.display = 'none';
            }
        }

        async function updateTodayScansCount() {
            try {
                const response = await fetch(todayScansUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json();
                const count = data.scans?.length || 0;
                const countEl = document.getElementById('today-scans-count');
                if (countEl) {
                    countEl.textContent = count;
                }
            } catch (e) {
                console.error('Failed to update today scans count', e);
            }
        }

        function updateConnectionStatus() {
            const dot = document.getElementById('status-dot');
            const text = document.getElementById('status-text');
            if (navigator.onLine) {
                dot.classList.remove('bg-red-500', 'bg-yellow-500');
                dot.classList.add('bg-green-500');
                text.textContent = 'Online';
            } else {
                dot.classList.remove('bg-green-500', 'bg-yellow-500');
                dot.classList.add('bg-red-500');
                text.textContent = 'Offline';
            }
        }

        window.addEventListener('online', async () => {
            updateConnectionStatus();
            await attemptSync();
        });
        window.addEventListener('offline', updateConnectionStatus);
        updateConnectionStatus();

        async function attemptSync() {
            if (!navigator.onLine) return;
            try {
                await SyncService.uploadQueuedScans();
                await refreshSyncStatus();
            } catch (e) {
                console.error('Auto sync failed', e);
            }
        }

        async function loadTodayScans() {
            try {
                const response = await fetch(todayScansUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json();
                renderRecentScans(data.scans || []);
                await updateTodayScansCount();
            } catch (e) {
                console.error('Failed to load today scans', e);
            }
        }

        function renderRecentScans(scans) {
            const container = document.getElementById('recent-scans-list');
            if (!scans.length) {
                container.innerHTML = '<flux:text class="text-sm text-black/70">No scans yet.</flux:text>';
                return;
            }

            container.innerHTML = scans.map(scan => {
                const participant = scan.participant || {};
                const time = scan.scanned_at ? new Date(scan.scanned_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
                let badgeColor = 'green';
                let badgeText = 'Granted';
                if (scan.result === 'duplicate') {
                    badgeColor = 'yellow';
                    badgeText = 'Duplicate';
                } else if (scan.result === 'access_closed' || scan.result === 'access_not_open') {
                    badgeColor = 'red';
                    badgeText = 'Denied';
                } else if (scan.result === 'invalid' || scan.result === 'revoked' || scan.result === 'replaced') {
                    badgeColor = 'red';
                    badgeText = 'Invalid';
                }
                return `
                    <div class="flex items-center justify-between rounded-lg border border-black/5 bg-white p-3">
                        <div>
                            <div class="text-sm font-medium text-black">${participant.first_name || ''} ${participant.last_name || ''}</div>
                            <div class="text-xs text-black/50">${participant.registration_number || ''} · ${time}</div>
                        </div>
                        <flux:badge color="${badgeColor}" size="sm">${badgeText}</flux:badge>
                    </div>
                `;
            }).join('');
        }

        function showResult(result) {
            const resultDiv = document.getElementById('scan-result');
            const content = document.getElementById('result-content');

            let color = 'border-green-500 bg-green-50';
            let icon = '<svg class="h-12 w-12 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            let resultTitle = 'ACCESS GRANTED';
            let soundType = 'success';
            let vibrationPattern = [100];

            if (result.result === 'invalid') {
                color = 'border-red-500 bg-red-50';
                icon = '<svg class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
                resultTitle = 'INVALID QR';
                soundType = 'denied';
                vibrationPattern = [200, 100, 200];
            } else if (result.result === 'revoked') {
                color = 'border-red-500 bg-red-50';
                icon = '<svg class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
                resultTitle = 'TICKET REVOKED';
                soundType = 'denied';
                vibrationPattern = [200, 100, 200];
            } else if (result.result === 'replaced') {
                color = 'border-red-500 bg-red-50';
                icon = '<svg class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
                resultTitle = 'TICKET REPLACED';
                soundType = 'denied';
                vibrationPattern = [200, 100, 200];
            } else if (result.result === 'duplicate') {
                color = 'border-yellow-500 bg-yellow-50';
                icon = '<svg class="h-12 w-12 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
                resultTitle = 'ALREADY SCANNED';
                soundType = 'denied';
                vibrationPattern = [100, 50, 100];
            } else if (result.result === 'access_closed') {
                color = 'border-red-500 bg-red-50';
                icon = '<svg class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
                resultTitle = 'ACCESS CLOSED';
                soundType = 'denied';
                vibrationPattern = [200, 100, 200];
            } else if (result.result === 'access_not_open') {
                color = 'border-yellow-500 bg-yellow-50';
                icon = '<svg class="h-12 w-12 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
                resultTitle = 'ACCESS NOT YET OPEN';
                soundType = 'denied';
                vibrationPattern = [100, 50, 100];
            } else if (result.result === 'scan_point_inactive') {
                color = 'border-red-500 bg-red-50';
                icon = '<svg class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
                resultTitle = 'ACCESS POINT INACTIVE';
                soundType = 'denied';
                vibrationPattern = [200, 100, 200];
            } else if (result.result === 'participant_inactive') {
                color = 'border-red-500 bg-red-50';
                icon = '<svg class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
                resultTitle = 'PARTICIPANT INACTIVE';
                soundType = 'denied';
                vibrationPattern = [200, 100, 200];
            }

            const participant = result.participant || {};
            const ticket = result.ticket || {};
            const scan = result.scan || {};
            const access = result.access || {};
            const verificationMode = result.offline ? 'OFFLINE' : 'ONLINE';
            const attendanceStatus = result.queued ? 'QUEUED FOR SYNC' : (result.attendance ? 'RECORDED' : 'N/A');
            const dataLastUpdated = lastSyncStatus?.lastSyncAt ? new Date(lastSyncStatus.lastSyncAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Unknown';

            content.innerHTML = `
                <div class="flex items-start gap-4">
                    <div class="mt-1">${icon}</div>
                    <div class="flex-1">
                        <div class="text-2xl font-bold text-black">${resultTitle}</div>
                        <div class="text-sm text-black/70 mt-1">${result.message}</div>
                        <div class="mt-3 space-y-1 text-sm text-black/70">
                            ${participant.first_name || participant.last_name ? `<div class="text-lg font-semibold text-black">${participant.first_name || ''} ${participant.last_name || ''}</div>` : ''}
                            <div><span class="font-medium">Registration:</span> ${participant.registration_number || ''}</div>
                            <div><span class="font-medium">Access:</span> ${access.name || ''}</div>
                            <div><span class="font-medium">Time:</span> ${scan.scanned_at ? new Date(scan.scanned_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : ''}</div>
                            <div><span class="font-medium">Mode:</span> ${verificationMode}</div>
                            <div><span class="font-medium">Attendance:</span> ${attendanceStatus}</div>
                            ${result.offline ? `<div><span class="font-medium">Data Last Updated:</span> ${dataLastUpdated}</div>` : ''}
                        </div>
                    </div>
                </div>
                <button id="scan-next-btn" class="mt-6 w-full rounded-lg bg-black px-6 py-3 text-lg font-medium text-white hover:bg-black/80">SCAN NEXT</button>
            `;

            resultDiv.className = `w-full rounded-xl border p-6 mb-4 ${color}`;
            resultDiv.classList.remove('hidden');

            playSound(soundType);
            vibrate(vibrationPattern);

            if (navigator.onLine && !result.offline) {
                loadTodayScans();
            }
        }

        async function handleScan(decodedText) {
            if (isScanning) return;
            isScanning = true;

            if (html5QrCode) {
                html5QrCode.pause();
            }

            let result;
            if (navigator.onLine) {
                try {
                    result = await verifyOnline(decodedText, scanApiUrl);
                } catch (e) {
                    result = {
                        success: false,
                        result: 'error',
                        message: 'Network error. Please try again.',
                        participant: null,
                        ticket: null,
                        scan: null,
                        scan_point: null,
                        offline: false,
                    };
                }
            } else {
                result = await verifyOffline(decodedText);
            }

            showResult(result);
            refreshSyncStatus();

            setTimeout(() => {
                document.getElementById('scan-result').classList.add('hidden');
            }, 5000);
        }

        function startScanner() {
            if (!isReady || html5QrCode) return;

            html5QrCode = new Html5Qrcode('reader');

            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
            };

            html5QrCode.start(
                { facingMode: 'environment' },
                config,
                (decodedText) => {
                    handleScan(decodedText);
                },
                () => {}
            ).catch(err => {
                console.error('Camera error', err);
                document.getElementById('reader').innerHTML = `
                    <div class="flex flex-col items-center justify-center p-8 text-center">
                        <svg class="h-12 w-12 text-black/20 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <flux:text class="text-black/70">Camera access is required to scan summit tickets.</flux:text>
                        <flux:text class="text-sm text-black/50 mt-2">Please allow camera access in your browser settings and try again.</flux:text>
                    </div>
                `;
            });
        }

        document.addEventListener('DOMContentLoaded', async () => {
            await initDevice();
            await refreshSyncStatus();
            await updateTodayScansCount();
            loadTodayScans();

            document.getElementById('download-data-btn')?.addEventListener('click', async () => {
                const btn = document.getElementById('download-data-btn');
                btn.textContent = 'Downloading...';
                btn.disabled = true;
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
                        await refreshSyncStatus();
                        alert('Data downloaded successfully.');
                    } else {
                        alert(data.message || 'Download failed.');
                    }
                } catch (e) {
                    alert('Download failed: ' + e.message);
                } finally {
                    btn.textContent = 'Download Data';
                    btn.disabled = false;
                }
            });

            document.getElementById('sync-now-btn')?.addEventListener('click', async () => {
                const btn = document.getElementById('sync-now-btn');
                btn.textContent = 'Syncing...';
                btn.disabled = true;
                try {
                    await SyncService.uploadQueuedScans();
                    await refreshSyncStatus();
                    alert('Sync complete.');
                } catch (e) {
                    alert('Sync failed: ' + e.message);
                } finally {
                    btn.textContent = 'Sync Now';
                    btn.disabled = false;
                }
            });

            if (isReady) {
                startScanner();
            } else {
                document.getElementById('reader').innerHTML = `
                    <div class="flex flex-col items-center justify-center p-8 text-center">
                        <flux:text class="text-black/70">${readyMessage}</flux:text>
                    </div>
                `;
            }

            document.getElementById('scan-result').addEventListener('click', (e) => {
                if (e.target.id === 'scan-next-btn' || e.target.closest('#scan-next-btn')) {
                    document.getElementById('scan-result').classList.add('hidden');
                    isScanning = false;
                    if (html5QrCode) {
                        html5QrCode.resume();
                    }
                }
            });

            setInterval(refreshSyncStatus, 30000);
        });
    </script>
    @endpush
</x-layouts::staff>
