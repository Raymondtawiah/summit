const DB_NAME = 'summitpass-offline';
const DB_VERSION = 1;

const STORES = {
  participants: 'participants',
  tickets: 'tickets',
  scanPoints: 'scan_points',
  attendanceQueue: 'attendance_queue',
  syncMetadata: 'sync_metadata',
  deviceInfo: 'device_info',
};

function openDb() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);

    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);

    request.onupgradeneeded = (event) => {
      const db = event.target.result;

      if (!db.objectStoreNames.contains(STORES.participants)) {
        const participantStore = db.createObjectStore(STORES.participants, { keyPath: 'id' });
        participantStore.createIndex('registration_number', 'registration_number', { unique: true });
        participantStore.createIndex('status', 'status', { unique: false });
      }

      if (!db.objectStoreNames.contains(STORES.tickets)) {
        const ticketStore = db.createObjectStore(STORES.tickets, { keyPath: 'id' });
        ticketStore.createIndex('qr_token', 'qr_token', { unique: true });
        ticketStore.createIndex('participant_id', 'participant_id', { unique: false });
        ticketStore.createIndex('status', 'status', { unique: false });
      }

      if (!db.objectStoreNames.contains(STORES.scanPoints)) {
        db.createObjectStore(STORES.scanPoints, { keyPath: 'id' });
      }

      if (!db.objectStoreNames.contains(STORES.attendanceQueue)) {
        const attendanceStore = db.createObjectStore(STORES.attendanceQueue, { keyPath: 'local_uuid' });
        attendanceStore.createIndex('ticket_id', 'ticket_id', { unique: false });
        attendanceStore.createIndex('scan_point_id', 'scan_point_id', { unique: false });
        attendanceStore.createIndex('status', 'status', { unique: false });
        attendanceStore.createIndex('created_at', 'created_at', { unique: false });
      }

      if (!db.objectStoreNames.contains(STORES.syncMetadata)) {
        db.createObjectStore(STORES.syncMetadata, { keyPath: 'key' });
      }

      if (!db.objectStoreNames.contains(STORES.deviceInfo)) {
        db.createObjectStore(STORES.deviceInfo, { keyPath: 'key' });
      }
    };
  });
}

async function transaction(storeName, mode = 'readonly') {
  const db = await openDb();
  const tx = db.transaction(storeName, mode);
  return { db, store: tx.objectStore(storeName) };
}

async function clearStore(storeName) {
  const { store } = await transaction(storeName, 'readwrite');
  return new Promise((resolve, reject) => {
    const request = store.clear();
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}

async function putAll(storeName, records) {
  const { db, store } = await transaction(storeName, 'readwrite');
  return new Promise((resolve, reject) => {
    records.forEach((record) => store.put(record));
    db.transaction.oncomplete = () => resolve();
    db.transaction.onerror = () => reject(db.transaction.error);
  });
}

async function put(storeName, record) {
  const { db, store } = await transaction(storeName, 'readwrite');
  return new Promise((resolve, reject) => {
    const request = store.put(record);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function get(storeName, key) {
  const { store } = await transaction(storeName, 'readonly');
  return new Promise((resolve, reject) => {
    const request = store.get(key);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function getAll(storeName) {
  const { store } = await transaction(storeName, 'readonly');
  return new Promise((resolve, reject) => {
    const request = store.getAll();
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function getByIndex(storeName, indexName, value) {
  const { store } = await transaction(storeName, 'readonly');
  return new Promise((resolve, reject) => {
    const index = store.index(indexName);
    const request = index.getAll(value);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function deleteRecord(storeName, key) {
  const { db, store } = await transaction(storeName, 'readwrite');
  return new Promise((resolve, reject) => {
    const request = store.delete(key);
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}

async function count(storeName) {
  const { store } = await transaction(storeName, 'readonly');
  return new Promise((resolve, reject) => {
    const request = store.count();
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function getMetadata(key) {
  const record = await get(STORES.syncMetadata, key);
  return record ? record.value : null;
}

async function setMetadata(key, value) {
  await put(STORES.syncMetadata, { key, value });
}

async function getDeviceInfo(key) {
  const record = await get(STORES.deviceInfo, key);
  return record ? record.value : null;
}

async function setDeviceInfo(key, value) {
  await put(STORES.deviceInfo, { key, value });
}

export default {
  STORES,
  openDb,
  clearStore,
  putAll,
  put,
  get,
  getAll,
  getByIndex,
  deleteRecord,
  count,
  getMetadata,
  setMetadata,
  getDeviceInfo,
  setDeviceInfo,
};
