/**
 * Google Apps Script untuk menerima data training dari WordPress
 *
 * Cara Deploy:
 * 1. Buka Google Sheet (default: 1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ)
 * 2. Extensions → Apps Script
 * 3. Paste script ini
 * 4. Deploy → New deployment → Web app
 *    - Execute as: Me
 *    - Who has access: Anyone
 * 5. Copy URL dan paste ke HCIS.YSQ Settings → Training → Web App URL
 */

const DEFAULT_SHEET_ID = '1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ';
const DEFAULT_TAB_NAME = 'Data';
const DEFAULT_DRIVE_FOLDER_ID = '1Wpf6k5G21Zb4kAILYDL7jfCjyKZd55zp';
const REQUIRED_HEADERS = [
  'Nama',
  'Jabatan',
  'Unit Kerja',
  'Nama Pelatihan/Workshop/Seminar',
  'Tahun Penyelenggaraan',
  'Pembiayaan',
  'Kategori',
  'Link Sertifikat/Bukti',
  'Timestamp',
];

function doPost(e) {
  try {
    const payload = JSON.parse(e.postData && e.postData.contents ? e.postData.contents : '{}');
    const sheetId = payload.sheetId || DEFAULT_SHEET_ID;
    const tabName = payload.tabName || DEFAULT_TAB_NAME;
    const driveFolderId = payload.driveFolderId || DEFAULT_DRIVE_FOLDER_ID;
    const entry = payload.entry || payload.data || {};

    if (!sheetId) {
      throw new Error('Sheet ID tidak ditemukan.');
    }

    const spreadsheet = SpreadsheetApp.openById(sheetId);
    let sheet = spreadsheet.getSheetByName(tabName);
    if (!sheet) {
      sheet = spreadsheet.insertSheet(tabName);
    }

    ensureHeader(sheet);

    const nama = (entry.nama || '').toString();
    const jabatan = (entry.jabatan || '').toString();
    const unitKerja = (entry.unit_kerja || entry.unit || '').toString();
    const namaPelatihan = (entry.nama_pelatihan || entry.namaPelatihan || '').toString();
    const tahun = (entry.tahun_penyelenggaraan || entry.tahun || '').toString();
    const pembiayaan = (entry.pembiayaan || '').toString();
    const kategori = (entry.kategori || '').toString();
    const timestamp = entry.timestamp || new Date().toISOString();
    const nip = (entry.nip || '').toString();

    let linkBukti = entry.link_sertifikat || entry.file_url || '';
    const fileUrl = entry.file_url || entry.link_sertifikat || '';

    if (fileUrl) {
      try {
        linkBukti = uploadFileToDrive(fileUrl, driveFolderId, nip, nama, namaPelatihan) || linkBukti;
      } catch (fileErr) {
        // Jika gagal upload ke Drive, tetap gunakan link asal agar tidak hilang jejak
        linkBukti = linkBukti || fileUrl;
      }
    }

    sheet.appendRow([
      nama,
      jabatan,
      unitKerja,
      namaPelatihan,
      tahun,
      pembiayaan,
      kategori,
      linkBukti,
      timestamp,
    ]);

    return ContentService.createTextOutput(JSON.stringify({
      ok: true,
      link: linkBukti || '',
    })).setMimeType(ContentService.MimeType.JSON);
  } catch (error) {
    return ContentService.createTextOutput(JSON.stringify({
      ok: false,
      msg: error.toString(),
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

function ensureHeader(sheet) {
  const lastRow = sheet.getLastRow();
  if (lastRow === 0) {
    sheet.appendRow(REQUIRED_HEADERS);
    return;
  }
  const headerRange = sheet.getRange(1, 1, 1, REQUIRED_HEADERS.length);
  const current = headerRange.getValues()[0];
  const mismatch = REQUIRED_HEADERS.some((title, idx) => (current[idx] || '') !== title);
  if (mismatch) {
    headerRange.setValues([REQUIRED_HEADERS]);
  }
}

function uploadFileToDrive(url, rootFolderId, nip, nama, pelatihan) {
  if (!rootFolderId) {
    throw new Error('Drive Folder ID kosong.');
  }
  const root = DriveApp.getFolderById(rootFolderId);
  const folderName = buildFolderName(nip, nama);
  let targetFolder;
  const matches = root.getFoldersByName(folderName);
  if (matches.hasNext()) {
    targetFolder = matches.next();
  } else {
    targetFolder = root.createFolder(folderName);
  }

  const response = UrlFetchApp.fetch(url, { muteHttpExceptions: true, followRedirects: true });
  const status = response.getResponseCode();
  if (status >= 300) {
    throw new Error('Gagal mengambil file (HTTP ' + status + ')');
  }

  const blob = response.getBlob();
  const safeName = sanitizeFileName(pelatihan || blob.getName() || ('Sertifikat-' + new Date().getTime()));
  const file = targetFolder.createFile(blob).setName(safeName);
  file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);

  return file.getUrl();
}

function buildFolderName(nip, nama) {
  const nipPart = nip ? nip.toString().trim() : 'UNKNOWN';
  const namaPart = nama ? nama.toString().trim() : 'Tanpa Nama';
  return sanitizeFileName(nipPart + '-' + namaPart);
}

function sanitizeFileName(name) {
  return name.replace(/[\\/:*?"<>|]+/g, ' ').trim();
}

// Test function untuk verifikasi manual
function testPost() {
  const dummy = {
    sheetId: DEFAULT_SHEET_ID,
    tabName: DEFAULT_TAB_NAME,
    driveFolderId: DEFAULT_DRIVE_FOLDER_ID,
    entry: {
      nip: '202012345678',
      nama: 'Test User',
      jabatan: 'Staff IT',
      unit_kerja: 'Divisi Teknologi',
      nama_pelatihan: 'Workshop Integrasi HRIS',
      tahun_penyelenggaraan: '2024',
      pembiayaan: 'yayasan',
      kategori: 'hard',
      link_sertifikat: 'https://example.com/certificate.pdf',
      timestamp: new Date().toISOString(),
    },
  };

  const spreadsheet = SpreadsheetApp.openById(dummy.sheetId);
  const existingSheet = spreadsheet.getSheetByName(dummy.tabName);
  const beforeRows = existingSheet ? existingSheet.getLastRow() : 0;

  const result = doPost({ postData: { contents: JSON.stringify(dummy) } });
  const raw = result && typeof result.getContent === 'function' ? result.getContent() : '';
  if (!raw) {
    throw new Error('Response kosong dari doPost');
  }

  let parsed;
  try {
    parsed = JSON.parse(raw);
  } catch (err) {
    throw new Error('Response bukan JSON: ' + raw);
  }

  if (!parsed.ok) {
    throw new Error('doPost mengembalikan error: ' + (parsed.msg || raw));
  }

  const sheetAfter = spreadsheet.getSheetByName(dummy.tabName);
  if (!sheetAfter) {
    throw new Error('Sheet tidak ditemukan setelah doPost dijalankan');
  }

  const afterRows = sheetAfter.getLastRow();
  const insertedRowIndex = afterRows;

  if (existingSheet) {
    if (afterRows !== beforeRows + 1) {
      throw new Error('Baris baru tidak ditambahkan. Sebelum: ' + beforeRows + ', sesudah: ' + afterRows);
    }
  } else if (afterRows < 2) {
    throw new Error('Sheet baru tidak berisi data yang diharapkan. Jumlah baris: ' + afterRows);
  }

  const latestRow = sheetAfter.getRange(insertedRowIndex, 1, 1, REQUIRED_HEADERS.length).getValues()[0];
  const [nama, jabatan, unitKerja] = latestRow;
  const expectedNama = (dummy.entry.nama || '').toString();
  const expectedJabatan = (dummy.entry.jabatan || '').toString();
  const expectedUnit = (dummy.entry.unit_kerja || '').toString();
  if (nama !== expectedNama || jabatan !== expectedJabatan || unitKerja !== expectedUnit) {
    throw new Error('Data baris terakhir tidak sesuai dengan payload dummy.');
  }

  sheetAfter.deleteRow(insertedRowIndex);

  Logger.log('testPost sukses. Link bukti: ' + (parsed.link || '(kosong)'));
}
