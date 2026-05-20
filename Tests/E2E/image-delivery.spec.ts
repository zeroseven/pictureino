import { test, expect } from '@playwright/test';

test.describe('Pictureino Image Delivery', () => {

  test('renders pictureino-wrap custom element with data-config', async ({ page }) => {
    await page.goto('/');
    const wrap = page.locator('pictureino-wrap').first();
    await expect(wrap).toBeVisible();
    // data-config is removed by JS after initialization, so just check element exists
  });

  test('loads pictureino JavaScript', async ({ page }) => {
    await page.goto('/');
    const script = page.locator('script#pictureino-js');
    await expect(script).toHaveCount(1);
  });

  test('fires image request to middleware endpoint on load', async ({ page }) => {
    const imageRequest = page.waitForRequest(request =>
      request.url().includes('/-/pictureino/img/')
    );

    await page.goto('/');
    const request = await imageRequest;

    expect(request.url()).toMatch(/\/-\/pictureino\/img\/\d+[12]x.+\/\d+x\d+\/?$/);
  });

  test('middleware returns valid JSON response', async ({ page }) => {
    const responsePromise = page.waitForResponse(response =>
      response.url().includes('/-/pictureino/img/')
    );

    await page.goto('/');
    const response = await responsePromise;

    expect(response.status()).toBe(200);
    const json = await response.json();
    expect(json).toHaveProperty('processed');
    expect(json.processed).toHaveProperty('width');
    expect(json.processed).toHaveProperty('height');
    expect(json.processed).toHaveProperty('img1x');
  });

  test('applies middleware dimensions to rendered image and loads real image pixels', async ({ page }) => {
    const responsePromise = page.waitForResponse(response =>
      response.url().includes('/-/pictureino/img/')
    );

    await page.goto('/');
    const response = await responsePromise;
    const json = await response.json();

    const wrap = page.locator('pictureino-wrap').first();
    await expect(wrap).not.toHaveAttribute('data-loading', '', { timeout: 10000 });

    const img = wrap.locator('img');
    await expect(img).toBeVisible();

    await expect(img).toHaveAttribute('width', String(json.processed.width));
    await expect(img).toHaveAttribute('height', String(json.processed.height));

    const rendered = await img.evaluate((element) => ({
      complete: element.complete,
      naturalWidth: element.naturalWidth,
      naturalHeight: element.naturalHeight,
      currentSrc: element.currentSrc,
    }));

    expect(rendered.complete).toBeTruthy();
    expect(rendered.currentSrc).not.toContain('data:image/jpeg;base64');
    // Validate the real loaded image is at least as large as declared output dimensions.
    expect(rendered.naturalWidth).toBeGreaterThanOrEqual(json.processed.width);
    expect(rendered.naturalHeight).toBeGreaterThanOrEqual(json.processed.height);
  });

  test('updates image src after middleware response', async ({ page }) => {
    await page.goto('/');

    // Wait for pictureino to process (loading attribute gets removed)
    const wrap = page.locator('pictureino-wrap').first();
    await expect(wrap).not.toHaveAttribute('data-loading', '', { timeout: 10000 });

    const img = wrap.locator('img');
    const src = await img.getAttribute('src');
    // After processing, src should no longer be a data: URI placeholder
    expect(src).not.toContain('data:image/jpeg;base64');
  });

  test('sends new request on viewport resize', async ({ page }) => {
    await page.goto('/');

    // Wait for initial load to complete
    const wrap = page.locator('pictureino-wrap').first();
    await expect(wrap).not.toHaveAttribute('data-loading', '', { timeout: 10000 });

    // Collect requests during resize
    const requests: string[] = [];
    page.on('request', request => {
      if (request.url().includes('/-/pictureino/img/')) {
        requests.push(request.url());
      }
    });

    // Resize viewport significantly
    await page.setViewportSize({ width: 600, height: 400 });

    // Wait for debounced resize (150ms + processing)
    await page.waitForTimeout(3000);

    expect(requests.length).toBeGreaterThan(0);
    // New request should have viewport 600 in URL
    expect(requests[0]).toContain('/600');
  });

  test('delivers different image sizes for different viewports', async ({ page }) => {
    await page.setViewportSize({ width: 480, height: 800 });
    const smallRequestPromise = page.waitForRequest(request =>
      request.url().includes('/-/pictureino/img/')
    );
    await page.goto('/');
    const smallRequest = await smallRequestPromise;

    await page.setViewportSize({ width: 1200, height: 800 });
    const largeRequestPromise = page.waitForRequest(request =>
      request.url().includes('/-/pictureino/img/')
    );
    await page.reload();
    const largeRequest = await largeRequestPromise;

    expect(smallRequest.url()).toContain('/480');
    expect(largeRequest.url()).toContain('/1200');

    const smallMatch = smallRequest.url().match(/\/(\d+)x(\d+)\/?$/);
    const largeMatch = largeRequest.url().match(/\/(\d+)x(\d+)\/?$/);

    expect(smallMatch).not.toBeNull();
    expect(largeMatch).not.toBeNull();

    const smallRequestedWidth = Number(smallMatch?.[1] ?? 0);
    const smallRequestedHeight = Number(smallMatch?.[2] ?? 0);
    const largeRequestedWidth = Number(largeMatch?.[1] ?? 0);
    const largeRequestedHeight = Number(largeMatch?.[2] ?? 0);

    expect(smallRequestedWidth).toBeGreaterThan(0);
    expect(smallRequestedHeight).toBeGreaterThan(0);
    expect(largeRequestedWidth).toBeGreaterThan(0);
    expect(largeRequestedHeight).toBeGreaterThan(0);

    // Larger viewport should not result in a smaller delivered image.
    expect(largeRequestedWidth).toBeGreaterThanOrEqual(smallRequestedWidth);
    expect(largeRequestedHeight).toBeGreaterThanOrEqual(smallRequestedHeight);
  });
});
