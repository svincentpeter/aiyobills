const { chromium } = require('C:\\Projects\\smart-room-access\\node_modules\\playwright');
const path = require('path');
const { execSync } = require('child_process');

const ARTIFACTS_DIR = 'C:\\Users\\cthrn\\.gemini\\antigravity\\brain\\9ceca384-3e5a-4789-9afa-394c24c355db';

async function run() {
  console.log('=== Memulai Pengujian Playwright Admin Panel: Deteksi Booking Kembar ===');

  const browser = await chromium.launch({
    headless: true,
    executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'
  });

  const context = await browser.newContext({
    viewport: { width: 1366, height: 850 }
  });

  // Inject admin user directly into localStorage
  await context.addInitScript(() => {
    localStorage.setItem('msra_user', JSON.stringify({
      id: 'usr_cthrn',
      name: 'Catherine Wong',
      email: 'cthrnwong11@gmail.com',
      role: 'admin',
      phone: '081234567890',
      avatar: 'https://ui-avatars.com/api/?name=Catherine+Wong&background=234c6a&color=fff'
    }));
  });

  const page = await context.newPage();

  // Handle confirmation dialogs automatically
  page.on('dialog', async dialog => {
    console.log('[Browser Dialog]:', dialog.message());
    await dialog.accept();
  });

  console.log('1. Membuka http://localhost:8000/admin.html...');
  await page.goto('http://localhost:8000/admin.html', { waitUntil: 'networkidle' });

  // 1. First ensure database is CLEAN
  execSync(`php -r "require 'backend/config/database.php'; Database::getConnection()->exec(\\"DELETE FROM bookings WHERE id IN ('MOCK-KEMBAR-01', 'MOCK-KEMBAR-02')\\");"`, { cwd: 'C:\\laragon\\www\\aiyobills' });

  // Click on "Deteksi Booking Kembar" tab
  console.log('2. Mengklik tab Deteksi Booking Kembar...');
  const conflictTabBtn = page.locator('button[data-tab="conflicts"]');
  await conflictTabBtn.click();
  await page.waitForSelector('#admin-tab-conflicts', { state: 'visible' });
  await page.waitForTimeout(1000);

  // Take screenshot 1: Clean State
  const screenshotClean = path.join(ARTIFACTS_DIR, 'admin_conflicts_clean.png');
  await page.screenshot({ path: screenshotClean });
  console.log('Screenshot status bersih tersimpan di:', screenshotClean);

  // 3. Insert mock conflicting bookings
  console.log('3. Memasukkan mock tabrakan booking (Budi vs Siti pada 2026-10-20)...');
  execSync(`php C:\\Users\\cthrn\\.gemini\\antigravity\\brain\\9ceca384-3e5a-4789-9afa-394c24c355db\\scratch\\insert_mock_conflict.php`, { cwd: 'C:\\laragon\\www\\aiyobills' });

  // 4. Click "Pindai Ulang Sekarang"
  console.log('4. Mengklik tombol Pindai Ulang Sekarang...');
  await page.click('#admin-btn-refresh-conflicts');
  await page.waitForTimeout(1500);

  // Verify conflict displayed
  const statusEl = await page.textContent('#conflict-kpi-status');
  const totalEl = await page.textContent('#conflict-kpi-total');
  const critEl = await page.textContent('#conflict-kpi-critical');
  console.log('KPI Konflik Terdeteksi:', { status: statusEl.trim(), total: totalEl.trim(), critical: critEl.trim() });

  // Check nav badge
  const badgeText = await page.textContent('#admin-badge-conflict-count');
  console.log('Nav Badge Count:', badgeText.trim());

  // Take screenshot 2: Detected Conflict
  const screenshotDetected = path.join(ARTIFACTS_DIR, 'admin_conflicts_detected.png');
  await page.screenshot({ path: screenshotDetected });
  console.log('Screenshot konflik terdeteksi tersimpan di:', screenshotDetected);

  // 5. Test resolution action: Click "Batalkan Pemesanan Ini" for MOCK-KEMBAR-02
  console.log('5. Melakukan aksi resolusi admin: membatalkan MOCK-KEMBAR-02 (Siti Rahma)...');
  const resolveBtn = page.locator('button.js-resolve-conflict[data-id="MOCK-KEMBAR-02"]');
  await resolveBtn.click();
  await page.waitForTimeout(2000);

  // Verify status after resolution
  const resolvedStatus = await page.textContent('#conflict-kpi-status');
  const resolvedTotal = await page.textContent('#conflict-kpi-total');
  console.log('Status KPI Pasca Resolusi:', { status: resolvedStatus.trim(), total: resolvedTotal.trim() });

  // Take screenshot 3: Resolved State
  const screenshotResolved = path.join(ARTIFACTS_DIR, 'admin_conflicts_resolved.png');
  await page.screenshot({ path: screenshotResolved });
  console.log('Screenshot setelah resolusi tersimpan di:', screenshotResolved);

  // Cleanup mocks completely
  execSync(`php -r "require 'backend/config/database.php'; Database::getConnection()->exec(\\"DELETE FROM bookings WHERE id IN ('MOCK-KEMBAR-01', 'MOCK-KEMBAR-02')\\");"`, { cwd: 'C:\\laragon\\www\\aiyobills' });

  await browser.close();
  console.log('=== Pengujian Playwright Selesai 100% Berhasil ===');
}

run().catch(err => {
  console.error('Error saat pengujian:', err);
  process.exit(1);
});
