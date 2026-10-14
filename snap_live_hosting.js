const path = require('path');
const { chromium, devices } = require('C:/Users/cthrn/AppData/Roaming/npm/node_modules/omniroute/node_modules/playwright');

const outDir = 'C:/Users/cthrn/.gemini/antigravity/brain/c420fef9-974a-4a45-a770-fc6b29ca5b5b/screenshots';

(async () => {
  console.log('=== STARTING LIVE HOSTING SCREENSHOT CAPTURE (CARA 2) ===');
  const browser = await chromium.launch({ channel: 'chrome', headless: true });

  // Android device emulation (Pixel 7)
  const device = devices['Pixel 7'] || {
    userAgent: 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Mobile Safari/537.36',
    viewport: { width: 412, height: 915 },
    deviceScaleFactor: 2,
    isMobile: true,
    hasTouch: true
  };

  const context = await browser.newContext({
    ...device,
    locale: 'id-ID'
  });

  const page = await context.newPage();

  // Helper
  async function snap(filename) {
    const fullPath = path.join(outDir, filename);
    await page.screenshot({ path: fullPath });
    console.log(`[SAVED] ${filename}`);
  }

  try {
    // 1. Visit live admin.html first (shows guard screen before Cara 2)
    console.log('1. Navigating to live admin.html (Guard Screen)...');
    await page.goto('https://app.mesinbayar.com/msra/admin.html', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);
    await snap('live_01_android_admin_guard_screen.png');

    // 2. Execute CARA 2 in browser context
    console.log('2. Executing Cara 2 in browser localStorage...');
    await page.evaluate(() => {
      localStorage.setItem('msra_user', JSON.stringify({
        id: 'usr_dimas',
        email: 'dimasrzk06@gmail.com',
        name: 'Dimas Rizky (Admin)',
        role: 'admin',
        avatar: 'https://ui-avatars.com/api/?name=Dimas+Rizky&background=234c6a&color=fff'
      }));
    });

    // Reload page
    console.log('Reloading live admin.html after Cara 2...');
    await page.reload({ waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);

    // 3. Capture Live Admin Dashboard
    console.log('3. Capturing Live Admin Dashboard...');
    await snap('live_02_android_admin_dashboard.png');

    // 4. Capture Live Ruangan Aktif
    console.log('4. Capturing Live Active Rooms...');
    const activeRoomsNav = await page.locator('button.admin-nav-btn[data-tab="active_rooms"]');
    if (await activeRoomsNav.isVisible()) {
      await activeRoomsNav.click();
      await page.waitForTimeout(1000);
      await snap('live_03_android_admin_active_rooms.png');
    }

    // 5. Capture Live Katalog Ruangan
    console.log('5. Capturing Live Rooms Catalog...');
    const roomsNav = await page.locator('button.admin-nav-btn[data-tab="rooms"]');
    if (await roomsNav.isVisible()) {
      await roomsNav.click();
      await page.waitForTimeout(1000);
      await snap('live_04_android_admin_rooms_catalog.png');
    }

    // 6. Capture Live Kelola Administrator
    console.log('6. Capturing Live Manage Admins...');
    const adminsNav = await page.locator('button.admin-nav-btn[data-tab="admins"]');
    if (await adminsNav.isVisible()) {
      await adminsNav.click();
      await page.waitForTimeout(1000);
      await snap('live_05_android_admin_manage_admins.png');

      // Now add Catherine Wong as Admin on live hosting!
      console.log('Adding Catherine Wong as Admin on live hosting...');
      const emailInput = await page.locator('#admin-new-email');
      const nameInput = await page.locator('#admin-new-name');
      const submitBtn = await page.locator('#admin-form-add-admin button[type="submit"]');

      if (await emailInput.isVisible() && await submitBtn.isVisible()) {
        await emailInput.fill('cthrnwong11@gmail.com');
        if (await nameInput.isVisible()) await nameInput.fill('Catherine Wong');
        await submitBtn.click();
        await page.waitForTimeout(1500);
        await snap('live_06_android_admin_catherine_registered.png');
        console.log('Catherine Wong registration executed!');
      }
    }

    // 7. Capture Live Audit Log Akses Pintu IoT
    console.log('7. Capturing Live Access Logs...');
    const logsNav = await page.locator('button.admin-nav-btn[data-tab="access_logs"]');
    if (await logsNav.isVisible()) {
      await logsNav.click();
      await page.waitForTimeout(1000);
      await snap('live_07_android_admin_access_logs.png');
    }

    // 8. Now visit Live index.html as Admin
    console.log('8. Navigating to live index.html as Admin...');
    await page.goto('https://app.mesinbayar.com/msra/index.html', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);
    await snap('live_08_android_index_admin_logged_in.png');

    // 9. Open Booking Modal on Live Hosting
    console.log('9. Opening Live Booking Modal...');
    const bookBtn = await page.locator('.js-book-room-btn').first();
    if (await bookBtn.isVisible()) {
      await bookBtn.scrollIntoViewIfNeeded();
      await bookBtn.click();
      await page.waitForTimeout(1000);
      await snap('live_09_android_booking_modal.png');

      // 10. Click Confirm to open Live Aiyo Bills Payment Modal
      console.log('10. Opening Live Aiyo Bills Payment Modal...');
      const confirmBookingBtn = await page.locator('#msra-btn-confirm-booking');
      if (await confirmBookingBtn.isVisible()) {
        await confirmBookingBtn.click();
        await page.waitForTimeout(2500);
        await snap('live_10_android_aiyo_qris_payment.png');

        // Switch to Virtual Account tab on live hosting
        const vaTab = await page.locator('#msra-tab-va');
        if (await vaTab.isVisible()) {
          await vaTab.click();
          await page.waitForTimeout(1000);
          await snap('live_11_android_aiyo_va_payment.png');
        }
      }
    }

    console.log('=== ALL LIVE HOSTING SCREENSHOTS CAPTURED SUCCESSFULLY! ===');
  } catch (err) {
    console.error('Error during live capture:', err);
  } finally {
    await browser.close();
  }
})();
