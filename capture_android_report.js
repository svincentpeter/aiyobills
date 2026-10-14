const path = require('path');
const { chromium, devices } = require('C:/Users/cthrn/AppData/Roaming/npm/node_modules/omniroute/node_modules/playwright');

const outDir = 'C:/Users/cthrn/.gemini/antigravity/brain/c420fef9-974a-4a45-a770-fc6b29ca5b5b/screenshots';
const baseUrl = 'http://aiyobills.test';

(async () => {
  console.log('=== STARTING ANDROID MOBILE SCREENSHOT CAPTURE ===');
  const browser = await chromium.launch({
    channel: 'chrome',
    headless: true
  });

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

  // Helper to save screenshot
  async function snap(filename, fullPage = false) {
    const fullPath = path.join(outDir, filename);
    await page.screenshot({ path: fullPath, fullPage });
    console.log(`[SAVED] ${filename}`);
  }

  try {
    // 1. Android Catalog (Home / Rooms list)
    console.log('1. Capturing Android Catalog...');
    await page.goto(`${baseUrl}/index.html`, { waitUntil: 'networkidle' });
    // Clear storage and bypass login as Catherine Wong (Admin)
    await page.evaluate(() => localStorage.clear());
    await page.reload({ waitUntil: 'networkidle' });
    await page.waitForTimeout(600);
    await snap('01_android_catalog.png');

    // 2. Dev Bypass Login Modal
    console.log('2. Capturing Dev Bypass Modal...');
    const bypassBtn = await page.locator('#msra-btn-localhost-bypass');
    if (await bypassBtn.isVisible()) {
      await bypassBtn.click();
      await page.waitForTimeout(400);
      await snap('02_android_dev_bypass.png');

      // Login as Catherine Wong (Admin)
      await page.click('.js-dev-quick-login[data-email="cthrnwong11@gmail.com"][data-role="admin"]');
      await page.waitForTimeout(800);
    }

    // 3. Room Booking Modal
    console.log('3. Capturing Booking Modal...');
    const bookBtn = await page.locator('.js-book-room-btn').first();
    await bookBtn.scrollIntoViewIfNeeded();
    await bookBtn.click();
    await page.waitForTimeout(600);
    await snap('03_android_booking_modal.png');

    // 4. Submit Booking to open Aiyo Bills Payment Modal
    console.log('4. Capturing Aiyo Bills QRIS Modal...');
    const confirmBookingBtn = await page.locator('#msra-btn-confirm-booking');
    await confirmBookingBtn.click();
    await page.waitForTimeout(1000);
    await snap('04_android_payment_qris.png');

    // 5. Virtual Account tab
    console.log('5. Capturing Aiyo Bills VA Modal...');
    const vaTab = await page.locator('#msra-tab-va');
    if (await vaTab.isVisible()) {
      await vaTab.click();
      await page.waitForTimeout(400);
      await snap('05_android_payment_va.png');
    }

    // 6. Simulate successful payment to activate access ticket
    console.log('6. Simulating payment and capturing active access ticket...');
    const simPayBtn = await page.locator('#msra-btn-simulate-pay');
    if (await simPayBtn.isVisible()) {
      await simPayBtn.click();
      await page.waitForTimeout(1500);
    }
    // Switch to tickets tab
    const ticketTabBtn = await page.locator('button.msra-mob-btn[data-tab="tickets"], button.msra-nav-btn[data-tab="tickets"]').first();
    if (await ticketTabBtn.isVisible()) {
      await ticketTabBtn.click();
      await page.waitForTimeout(600);
    }
    await snap('06_android_active_ticket.png');

    // 7. Scanner tab
    console.log('7. Capturing Door Scanner tab...');
    const scannerTabBtn = await page.locator('button.msra-mob-btn[data-tab="scanner"], button.msra-nav-btn[data-tab="scanner"]').first();
    if (await scannerTabBtn.isVisible()) {
      await scannerTabBtn.click();
      await page.waitForTimeout(600);
    }
    await snap('07_android_scanner.png');

    // 8. AiYO Fintech Transactions Log (Fintech/lihat.php)
    console.log('8. Capturing AiYO Fintech Transactions Log...');
    await page.goto(`${baseUrl}/Fintech/lihat.php`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(600);
    await snap('08_android_fintech_transactions.png');

    // 9. Admin Console Dashboard (admin.html)
    console.log('9. Capturing Admin Dashboard...');
    await page.goto(`${baseUrl}/admin.html`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);
    await snap('09_android_admin_dashboard.png');

    // 10. Admin Console Access Audit Logs
    console.log('10. Capturing Admin Access Logs...');
    const logsNavBtn = await page.locator('button.admin-nav-btn[data-tab="access_logs"]');
    if (await logsNavBtn.isVisible()) {
      await logsNavBtn.click();
      await page.waitForTimeout(600);
      await snap('10_android_admin_audit_logs.png');
    }

    console.log('=== ALL SCREENSHOTS CAPTURED SUCCESSFULLY! ===');
  } catch (err) {
    console.error('Error during capture:', err);
    process.exitCode = 1;
  } finally {
    await browser.close();
  }
})();
