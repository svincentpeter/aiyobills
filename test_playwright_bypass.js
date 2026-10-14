const { chromium } = require('C:/Users/cthrn/AppData/Roaming/npm/node_modules/omniroute/node_modules/playwright');

(async () => {
  console.log('=== START PLAYWRIGHT E2E BYPASS TEST ===');
  const browser = await chromium.launch({
    channel: 'chrome',
    headless: true
  });
  const context = await browser.newContext();
  const page = await context.newPage();

  const baseUrls = ['http://aiyobills.test', 'http://localhost/aiyobills'];

  try {
    for (const baseUrl of baseUrls) {
    console.log(`\n========================================`);
    console.log(`Testing environment: ${baseUrl}`);
    console.log(`========================================`);
    // --- STEP 1: PUBLIC CATALOG & HEADER BYPASS ---
    console.log(`[TEST 1] Navigating to ${baseUrl}/index.html...`);
    await page.goto(`${baseUrl}/index.html`, { waitUntil: 'domcontentloaded' });
    
    // Clear any previous session
    await page.evaluate(() => localStorage.clear());
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    // Verify Dev Bypass button is displayed in localhost
    const bypassBtn = await page.locator('#msra-btn-localhost-bypass');
    const isVisible = await bypassBtn.isVisible();
    console.log(`[PASS] Dev Bypass button visible in header: ${isVisible}`);
    if (!isVisible) throw new Error('Dev Bypass button is not visible on localhost');

    // Click Dev Bypass button to open modal
    console.log('[TEST 2] Clicking Dev Bypass button...');
    await bypassBtn.click();
    await page.waitForTimeout(300);

    const modalBackdrop = await page.locator('#msra-dev-bypass-modal-backdrop');
    const isModalVisible = await modalBackdrop.isVisible();
    console.log(`[PASS] Dev Bypass Modal opened: ${isModalVisible}`);
    if (!isModalVisible) throw new Error('Dev Bypass modal did not open');

    // Click quick login button for Catherine Wong (Admin)
    console.log('[TEST 3] Clicking 1-click login for Catherine Wong (Admin)...');
    const cthrnBtn = await page.locator('.js-dev-quick-login[data-email="cthrnwong11@gmail.com"][data-role="admin"]');
    await cthrnBtn.click();

    // Wait for user info to appear in header
    await page.waitForSelector('#msra-auth-logged-in:not(.hidden)', { timeout: 5000 });
    const userName = await page.textContent('#msra-user-name');
    console.log(`[PASS] Logged in header shows user: "${userName.trim()}"`);

    // Verify user profile dropdown contains Admin Panel link
    await page.click('#msra-user-profile-btn');
    await page.waitForTimeout(200);
    const adminLink = await page.locator('#msra-link-admin');
    const isAdminLinkVisible = await adminLink.isVisible();
    console.log(`[PASS] Admin Panel link visible in dropdown: ${isAdminLinkVisible}`);
    if (!isAdminLinkVisible) throw new Error('Admin panel link not visible for Catherine Wong admin');

    // --- STEP 2: ADMIN PANEL NAVIGATION ---
    console.log(`[TEST 4] Navigating to ${baseUrl}/admin.html...`);
    await page.goto(`${baseUrl}/admin.html`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1000);

    const guardCard = await page.locator('#admin-guard-container');
    const isGuardVisible = await guardCard.isVisible();
    console.log(`[PASS] Admin guard screen blocked? ${isGuardVisible ? 'YES (UNAUTHORIZED)' : 'NO (AUTHORIZED)'}`);
    if (isGuardVisible) throw new Error('Admin guard screen is blocking authorized Catherine Wong!');

    const adminContent = await page.locator('#admin-content-container');
    const isContentVisible = await adminContent.isVisible();
    console.log(`[PASS] Admin dashboard content visible: ${isContentVisible}`);
    if (!isContentVisible) throw new Error('Admin dashboard content is not visible');

    const adminUserName = await page.textContent('#admin-user-name');
    console.log(`[PASS] Admin console shows user: "${adminUserName.trim()}"`);

    // --- STEP 3: GUARD SCREEN BYPASS ON ADMIN.HTML ---
    console.log('[TEST 5] Testing Guard Screen Bypass when logged out...');
    await context.clearCookies();
    await page.evaluate(async (url) => {
      localStorage.clear();
      const apiBase = url.includes('/aiyobills') ? '/aiyobills/api' : '/api';
      await fetch(`${apiBase}/auth/logout`, { method: 'POST', credentials: 'include' });
    }, baseUrl);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(800);

    const guardNowVisible = await guardCard.isVisible();
    console.log(`[PASS] Guard screen now visible on anonymous access: ${guardNowVisible}`);

    const adminDevBox = await page.locator('#admin-dev-bypass-box');
    const isDevBoxVisible = await adminDevBox.isVisible();
    console.log(`[PASS] Admin guard screen shows Localhost Dev Bypass box: ${isDevBoxVisible}`);
    if (!isDevBoxVisible) throw new Error('Dev bypass box not shown on admin guard screen');

    console.log('[TEST 6] Clicking Admin Guard Bypass button (cthrnwong11@gmail.com)...');
    await page.click('#admin-btn-dev-bypass');
    await page.waitForTimeout(1500);

    const isGuardDismissed = !(await guardCard.isVisible());
    const isContentNowVisible = await adminContent.isVisible();
    console.log(`[PASS] Guard dismissed: ${isGuardDismissed}, Content unlocked: ${isContentNowVisible}`);
    if (!isGuardDismissed || !isContentNowVisible) throw new Error('Admin guard bypass did not unlock dashboard');
  }

  console.log('\n=== ALL PLAYWRIGHT TESTS PASSED ON BOTH HOSTS! ===');
  } catch (err) {
    console.error('Playwright test failed:', err);
    process.exitCode = 1;
  } finally {
    await browser.close();
  }
})();
