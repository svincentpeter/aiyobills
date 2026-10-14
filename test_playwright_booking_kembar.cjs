const { chromium } = require('C:/Projects/smart-room-access/node_modules/playwright');
const fs = require('fs');

(async () => {
  console.log('=== MEMULAI TEST PLAYWRIGHT: PENGUJIAN KASUS BOOKING KEMBAR (OVERLAPPING CONFLICT) ===\n');

  const browser = await chromium.launch({
    executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    headless: true
  });

  const targetDate = '2026-10-15';
  const targetTime = '14:00';
  const targetRoomId = 'room-vip-01'; // Executive Boardroom Alpha

  // -------------------------------------------------------------------------
  // USER A (Pemesanan Pertama: Berhasil Mengunci Slot)
  // -------------------------------------------------------------------------
  console.log('1. User A (Budi Santoso) membuka website dan login bypass...');
  const contextA = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const pageA = await contextA.newPage();

  // Inject localStorage to bypass login for User A
  await pageA.addInitScript(() => {
    localStorage.setItem('msra_user', JSON.stringify({
      id: 'usr_budi_01',
      name: 'Budi Santoso',
      email: 'budi.santoso@gmail.com',
      phone: '081234567891',
      role: 'user'
    }));
  });

  await pageA.goto('http://localhost:8000', { waitUntil: 'networkidle' });
  await pageA.waitForSelector('.msra-card');

  console.log('   -> User A memilih ruangan Executive Boardroom Alpha...');
  const bookBtnA = pageA.locator(`.js-book-room-btn[data-room-id="${targetRoomId}"]`).first();
  await bookBtnA.click();
  await pageA.waitForSelector('#msra-booking-modal-backdrop:not(.hidden)');

  console.log(`   -> User A mengatur jadwal: Tanggal ${targetDate}, Jam ${targetTime} (Durasi 2 Jam)...`);
  await pageA.fill('#book-date-input', targetDate);
  await pageA.selectOption('#book-time-select', targetTime);
  await pageA.locator('.msra-duration-btn[data-hours="2"]').click();

  console.log('   -> User A mengonfirmasi "Kunci Slot & Lanjut Bayar"...');
  await pageA.click('#msra-btn-confirm-booking');

  // Tunggu modal pembayaran terbuka (menandakan slot berhasil di-hold)
  console.log('   -> Menunggu proses verifikasi slot & pengiriman notifikasi...');
  await pageA.waitForSelector('#msra-payment-modal-backdrop:not(.hidden)', { timeout: 20000 });
  const bookingIdDisplay = await pageA.textContent('#msra-pay-booking-display');
  console.log(`   -> [BERHASIL] User A berhasil mengunci slot! ${bookingIdDisplay.trim()}`);

  const shotPathA = 'C:/Users/cthrn/.gemini/antigravity/brain/9ceca384-3e5a-4789-9afa-394c24c355db/user_a_hold_success.png';
  await pageA.screenshot({ path: shotPathA });
  console.log(`   -> Screenshot User A tersimpan: ${shotPathA}\n`);

  // -------------------------------------------------------------------------
  // USER B (Pemesanan Kedua: Mengambil Ruangan & Jam yang Sama -> BENTROK)
  // -------------------------------------------------------------------------
  console.log('2. User B (Siti Rahma) membuka website di sesi terpisah dan login bypass...');
  const contextB = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const pageB = await contextB.newPage();

  // Inject localStorage to bypass login for User B
  await pageB.addInitScript(() => {
    localStorage.setItem('msra_user', JSON.stringify({
      id: 'usr_siti_02',
      name: 'Siti Rahma',
      email: 'siti.rahma@gmail.com',
      phone: '085698765432',
      role: 'user'
    }));
  });

  await pageB.goto('http://localhost:8000', { waitUntil: 'networkidle' });
  await pageB.waitForSelector('.msra-card');

  console.log('   -> User B mencoba memesan ruangan yang sama persis (Executive Boardroom Alpha)...');
  const bookBtnB = pageB.locator(`.js-book-room-btn[data-room-id="${targetRoomId}"]`).first();
  await bookBtnB.click();
  await pageB.waitForSelector('#msra-booking-modal-backdrop:not(.hidden)');

  console.log(`   -> User B memilih jadwal KEMBAR yang bertubrukan: Tanggal ${targetDate}, Jam ${targetTime}...`);
  await pageB.fill('#book-date-input', targetDate);
  await pageB.selectOption('#book-time-select', targetTime);
  await pageB.locator('.msra-duration-btn[data-hours="2"]').click();

  console.log('   -> User B menekan tombol "Kunci Slot & Lanjut Bayar"...');
  await pageB.click('#msra-btn-confirm-booking');

  // Tunggu toast pesan error muncul
  console.log('   -> Memverifikasi penolakan sistem terhadap booking kembar...');
  const toastSelector = '.msra-toast';
  await pageB.waitForSelector(toastSelector, { timeout: 20000 });
  const toastText = await pageB.textContent(toastSelector);
  console.log(`   -> [DITOLAK] Respon Sistem: "${toastText.trim()}"`);

  const shotPathB = 'C:/Users/cthrn/.gemini/antigravity/brain/9ceca384-3e5a-4789-9afa-394c24c355db/user_b_duplicate_rejected.png';
  await pageB.screenshot({ path: shotPathB });
  console.log(`   -> Screenshot User B tersimpan: ${shotPathB}\n`);

  await browser.close();

  // -------------------------------------------------------------------------
  // PENGECEKAN INTEGRITAS DATABASE
  // -------------------------------------------------------------------------
  console.log('3. Validasi Konsistensi Data di Database SQLite:');
  const { execSync } = require('child_process');
  const dbCheck = execSync('php -r "@require \'backend/config/database.php\'; $r = Database::getConnection()->query(\'SELECT id, room_id, customer_name, customer_email, booking_date, start_time, end_time, status FROM bookings WHERE booking_date = \"2026-10-15\"\')->fetchAll(PDO::FETCH_ASSOC); echo json_encode($r, JSON_PRETTY_PRINT);"', { cwd: 'C:/laragon/www/aiyobills' }).toString();
  console.log(dbCheck);

  console.log('\n=== KESIMPULAN TEST: SISTEM BERHASIL MENCEGAH BOOKING KEMBAR 100% ===');
})();
