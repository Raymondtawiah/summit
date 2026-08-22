import db from './idb.js';

const SYNC_STATUS = {
  QUEUED: 'queued',
  SYNCING: 'syncing',
  SYNCED: 'synced',
  FAILED: 'failed',
  CONFLICT: 'conflict',
};

const BATCH_SIZE = 50;

function generateLocalUuid() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID();
  }
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

function isOnline() {
  return navigator.onLine;
}

async function initializeDevice(staffId, deviceIdentifier) {
  const deviceUuid = await db.getDeviceInfo('device_uuid');
  if (!deviceUuid) {
    const newUuid = generateLocalUuid();
    await db.setDeviceInfo('device_uuid', newUuid);
    return newUuid;
  }
  return deviceUuid;
}

async function getSyncStatus() {
  const datasetVersion = await db.getMetadata('dataset_version');
  const lastSyncAt = await db.getMetadata('last_sync_at');
  const deviceUuid = await db.getDeviceInfo('device_uuid');
  const dataInvalidated = (await db.getDeviceInfo('data_invalidated')) || false;

  const queuedCount = await db.count(db.STORES.attendanceQueue);

  return {
    datasetVersion: datasetVersion || 0,
    lastSyncAt,
    deviceUuid,
    dataInvalidated,
    queuedCount,
    online: isOnline(),
    updateAvailable: false,
  };
}

async function downloadData(page = 1) {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const deviceUuid = await db.getDeviceInfo('device_uuid');

  const response = await fetch(`/staff/api/sync/download`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrfToken,
      'X-Device-Token': deviceUuid || '',
    },
    body: JSON.stringify({ page }),
    credentials: 'same-origin',
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({}));
    throw new Error(error.message || 'Download failed.');
  }

  return response.json();
}

async function persistDownloadedData(payload) {
  const { participants = [], tickets = [], access_points = [], scan_points = [], dataset_version, meta } = payload;
  const points = access_points.length ? access_points : scan_points;

  if (participants.length) {
    await db.putAll(db.STORES.participants, participants.map((p) => ({ ...p, _syncedAt: Date.now() })));
  }

  if (tickets.length) {
    await db.putAll(db.STORES.tickets, tickets.map((t) => ({ ...t, _syncedAt: Date.now() })));
  }

  if (points.length) {
    await db.putAll(db.STORES.scanPoints, points);
  }

  await db.setMetadata('dataset_version', dataset_version);
  await db.setMetadata('last_sync_at', meta?.server_time || new Date().toISOString());
  await db.setMetadata('participants_count', meta?.participants_count || participants.length);
  await db.setMetadata('tickets_count', meta?.tickets_count || tickets.length);

  return {
    datasetVersion: dataset_version,
    participants: participants.length,
    tickets: tickets.length,
    scanPoints: points.length,
  };
}

async function queueAttendanceScan(scanResult, staffId, scanPointId, deviceUuid) {
  const localUuid = generateLocalUuid();

  const record = {
    local_uuid: localUuid,
    ticket_id: scanResult.ticket?.id || scanResult.ticket_id,
    participant_id: scanResult.participant?.id || scanResult.participant_id,
    registration_number: scanResult.participant?.registration_number || '',
    ticket_number: scanResult.ticket?.ticket_number || '',
    staff_id: staffId,
    scan_point_id: scanPointId,
    device_id: deviceUuid,
    scanned_at_local: scanResult.scanned_at || new Date().toISOString(),
    scan_mode: 'offline',
    status: SYNC_STATUS.QUEUED,
    created_at: new Date().toISOString(),
    sync_attempts: 0,
    last_sync_attempt: null,
    sync_error: null,
  };

  await db.put(db.STORES.attendanceQueue, record);

  return record;
}

async function findLocalTicketByQrToken(qrToken) {
  const ticket = await db.get(db.STORES.tickets, qrToken);
  return ticket || null;
}

async function findParticipantById(participantId) {
  return db.get(db.STORES.participants, participantId);
}

async function findQueuedScan(ticketId, scanPointId) {
  const all = await db.getAll(db.STORES.attendanceQueue);
  return all.find(
    (r) => r.ticket_id === ticketId && r.scan_point_id === scanPointId && r.status !== SYNC_STATUS.FAILED
  );
}

async function getQueuedCount() {
  return db.count(db.STORES.attendanceQueue);
}

async function uploadQueuedScans() {
  const records = await db.getAll(db.STORES.attendanceQueue);
  const pending = records.filter((r) => r.status === SYNC_STATUS.QUEUED || r.status === SYNC_STATUS.FAILED);

  if (!pending.length) {
    return { success: true, results: [] };
  }

  const batches = [];
  for (let i = 0; i < pending.length; i += BATCH_SIZE) {
    batches.push(pending.slice(i, i + BATCH_SIZE));
  }

  const allResults = [];
  for (const batch of batches) {
    const results = await uploadBatch(batch);
    allResults.push(...results);
  }

  return { success: true, results: allResults };
}

async function uploadBatch(batch) {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const deviceUuid = await db.getDeviceInfo('device_uuid');

  for (const record of batch) {
    await db.put(db.STORES.attendanceQueue, { ...record, status: SYNC_STATUS.SYNCING });
  }

  try {
    const response = await fetch('/staff/api/sync/upload', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
        'X-Device-Token': deviceUuid || '',
      },
      body: JSON.stringify({ records: batch }),
      credentials: 'same-origin',
    });

    if (!response.ok) {
      throw new Error('Upload failed');
    }

    const payload = await response.json();
    const resultMap = new Map((payload.results || []).map((r) => [r.local_uuid, r]));

    for (const record of batch) {
      const result = resultMap.get(record.local_uuid) || { status: 'failed', message: 'Unknown error' };
      const updated = {
        ...record,
        status: result.status === 'synced' ? SYNC_STATUS.SYNCED : result.status === 'duplicate' ? SYNC_STATUS.SYNCED : SYNC_STATUS.FAILED,
        last_sync_attempt: new Date().toISOString(),
        sync_attempts: (record.sync_attempts || 0) + 1,
        sync_error: result.message || null,
        server_attendance_id: result.attendance_id || null,
      };

      if (result.status === 'synced' || result.status === 'duplicate') {
        await db.delete(db.STORES.attendanceQueue, record.local_uuid);
      } else {
        await db.put(db.STORES.attendanceQueue, updated);
      }
    }

    return payload.results || [];
  } catch (error) {
    for (const record of batch) {
      await db.put(db.STORES.attendanceQueue, {
        ...record,
        status: SYNC_STATUS.FAILED,
        last_sync_attempt: new Date().toISOString(),
        sync_attempts: (record.sync_attempts || 0) + 1,
        sync_error: error.message,
      });
    }
    throw error;
  }
}

async function clearLocalData() {
  await db.clearStore(db.STORES.participants);
  await db.clearStore(db.STORES.tickets);
  await db.clearStore(db.STORES.scanPoints);
  await db.clearStore(db.STORES.attendanceQueue);
  await db.clearStore(db.STORES.syncMetadata);
  await db.clearStore(db.STORES.deviceInfo);
}

async function getDeviceInfo(key) {
  return db.getDeviceInfo(key);
}

async function setDeviceInfo(key, value) {
  return db.setDeviceInfo(key, value);
}

export {
  SYNC_STATUS,
  BATCH_SIZE,
  generateLocalUuid,
  isOnline,
  initializeDevice,
  getSyncStatus,
  downloadData,
  persistDownloadedData,
  queueAttendanceScan,
  findLocalTicketByQrToken,
  findParticipantById,
  findQueuedScan,
  getQueuedCount,
  uploadQueuedScans,
  clearLocalData,
  getDeviceInfo,
  setDeviceInfo,
};
