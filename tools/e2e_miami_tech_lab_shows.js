const { chromium } = require('playwright');

(async () => {
  const base = process.env.MTL_TEST_BASE || 'http://localhost/miami-tech-lab';
  const browser = await chromium.launch({ headless: true, channel: process.env.MTL_TEST_BROWSER || 'chromium' });
  const failures = [];
  for (const viewport of [{ name: 'desktop', width: 1440, height: 900 }, { name: 'mobile', width: 390, height: 844 }]) {
    const page = await browser.newPage({ viewport });
    page.on('pageerror', error => failures.push(`${viewport.name} page error: ${error.message}`));
    page.on('console', message => { if (message.type() === 'error') failures.push(`${viewport.name} console: ${message.text()}`); });
    const response = await page.goto(`${base}/shows`, { waitUntil: 'networkidle' });
    if (!response || response.status() !== 200) failures.push(`${viewport.name}: /shows did not return 200`);
    if (!await page.getByRole('heading', { name: 'Ideas worth operating.' }).isVisible()) failures.push(`${viewport.name}: shows heading missing`);
    if (!await page.getByText('New shows are being prepared. Check back soon.').isVisible()) failures.push(`${viewport.name}: empty CMS state missing`);
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth);
    if (overflow) failures.push(`${viewport.name}: horizontal overflow`);
    if (viewport.name === 'mobile') {
      await page.getByRole('button', { name: 'Open navigation' }).click();
      if (!await page.locator('.nav.open').isVisible()) failures.push('mobile: navigation did not open');
    }
    await page.close();
  }

  const admin = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  await admin.request.post(`${base}/login`, { form: { email: 'info@vnvevents.com', password: '12345' } });
  const adminResponse = await admin.goto(`${base}/panel/miami-tech-lab/shows`, { waitUntil: 'networkidle' });
  if (!adminResponse || adminResponse.status() !== 200) failures.push(`admin: shows workflow returned ${adminResponse ? adminResponse.status() : 'no response'} at ${admin.url()}`);
  if (!await admin.getByRole('heading', { name: 'Miami Tech Lab Shows' }).isVisible()) failures.push('admin: workflow heading missing');
  for (const label of ['Shows', 'Episodes & transcripts', 'Guests', 'Recording schedule']) {
    if (!await admin.getByRole('tab', { name: label, exact: true }).count()) failures.push(`admin: ${label} tab missing`);
  }
  await browser.close();
  console.log(JSON.stringify({ ok: failures.length === 0, failures }, null, 2));
  if (failures.length) process.exitCode = 1;
})().catch(error => { console.error(error); process.exitCode = 1; });
