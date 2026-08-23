  const staffId = await SyncService.getDeviceInfo('staff_id');
async function verifyOffline(qrToken) {
  const ticket = await SyncService.findLocalTicketByQrToken(qrToken);

  if (!ticket) {
    return {
      success: false,
      result: 'invalid',
      message: 'This QR code was not found in the data currently stored on this device.',
      participant: null,
      ticket: null,
      scan: null,
      access: null,
      offline: true,
    };
  }

  if (ticket.status === 'revoked') {
    return {
      success: false,
      result: 'revoked',
      message: 'This ticket was marked as revoked in the latest data downloaded to this device.',
      participant: ticket.participant ? { ...ticket.participant } : null,
      ticket: { ...ticket },
      scan: null,
      access: null,
      offline: true,
    };
  }

  if (ticket.status === 'replaced') {
    return {
      success: false,
      result: 'replaced',
      message: 'Please use the participant\'s latest ticket.',
      participant: ticket.participant ? { ...ticket.participant } : null,
      ticket: { ...ticket },
      scan: null,
      access: null,
      offline: true,
    };
  }

  if (ticket.status !== 'active') {
    return {
      success: false,
      result: 'invalid',
      message: 'This ticket is not valid.',
      participant: ticket.participant ? { ...ticket.participant } : null,
      ticket: { ...ticket },
      scan: null,
      access: null,
      offline: true,
    };
  }

  const participant = ticket.participant
    ? { ...ticket.participant }
    : await SyncService.findParticipantById(ticket.participant_id);

  if (!participant || participant.status !== 'active') {
    return {
      success: false,
      result: 'inactive_participant',
      message: 'This participant is not currently eligible for summit access.',
      participant: participant ? { ...participant } : null,
      ticket: { ...ticket },
      scan: null,
      access: null,
      offline: true,
    };
  }

const scanPoints = await SyncService.getAll(SyncService.STORES.scanPoints);
   const scanPointIdStr = await SyncService.getDeviceInfo('scan_point_id');
   const scanPointId = parseInt(scanPointIdStr);
   const accessPoint = scanPoints.find((sp) => sp.id === scanPointId);

  if (!accessPoint) {
    return {
      success: false,
      result: 'access_inactive',
      message: 'No access point has been assigned to this device.',
      participant: participant,
      ticket: { ...ticket },
      scan: null,
      access: null,
      offline: true,
    };
  }

  if (accessPoint.status !== 'active') {
    return {
      success: false,
      result: 'access_inactive',
      message: 'This access point is currently disabled.',
      participant: participant,
      ticket: { ...ticket },
      scan: null,
      access: { ...accessPoint },
      offline: true,
    };
  }

  const now = new Date();
  const currentTime = now.toTimeString().slice(0, 5);

  if (accessPoint.start_time && currentTime < accessPoint.start_time) {
    return {
      success: false,
      result: 'access_closed',
      message: 'Access opens at ' + accessPoint.start_time,
      participant: participant,
      ticket: { ...ticket },
      scan: null,
      access: { ...accessPoint },
      offline: true,
    };
  }

  if (accessPoint.end_time && currentTime > accessPoint.end_time) {
    return {
      success: false,
      result: 'access_closed',
      message: 'Access closed at ' + accessPoint.end_time,
      participant: participant,
      ticket: { ...ticket },
      scan: null,
      access: { ...accessPoint },
      offline: true,
    };
  }

  const deviceUuid = await SyncService.getDeviceInfo('device_uuid');
  const staffId = await SyncService.getDeviceInfo('staff_id');

  const existing = await SyncService.findQueuedScan(ticket.id, scanPointId);

  if (existing) {
    return {
      success: false,
      result: 'duplicate',
      message: 'Participant has already been recorded for this access point.',
      participant: participant,
      ticket: { ...ticket },
      scan: { scanned_at: existing.scanned_at_local },
      access: { ...accessPoint },
      offline: true,
      existing_status: existing.status,
    };
  }

  const scanResult = {
    ticket: { ...ticket },
    participant: participant,
    scanned_at: new Date().toISOString(),
  };

  const queued = await SyncService.queueAttendanceScan(scanResult, staffId, scanPointId, deviceUuid);

  return {
    success: true,
    result: 'access_granted',
    message: 'Access granted.',
    participant: participant,
    ticket: { ...ticket },
    scan: { scanned_at: queued.scanned_at_local, local_uuid: queued.local_uuid },
    access: { ...accessPoint },
    offline: true,
    queued: true,
  };
}

async function verifyOnline(qrToken, scanApiUrl) {
  const response = await fetch(scanApiUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    },
    body: JSON.stringify({ token: qrToken }),
    credentials: 'same-origin',
  });

  const result = await response.json();
  result.offline = false;
  return result;
}

export { verifyOffline, verifyOnline };
  const deviceUuid = await SyncService.getDeviceInfo('device_uuid');

