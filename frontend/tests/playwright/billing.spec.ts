import { test, expect } from '@playwright/test';

test.describe('Billing Module E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/billing');
  });

  test('should display billing dashboard', async ({ page }) => {
    await expect(page.locator('h1')).toContainText('Billing');
  });

  test('should display current billing cycle', async ({ page }) => {
    const cycleElement = page.locator('[data-testid="billing-cycle"]');
    await expect(cycleElement).toBeVisible();
  });

  test('should display usage statistics', async ({ page }) => {
    const usageElement = page.locator('[data-testid="usage-stats"]');
    await expect(usageElement).toBeVisible();
  });

  test('should display payment methods', async ({ page }) => {
    const paymentMethods = page.locator('[data-testid="payment-methods"]');
    await expect(paymentMethods).toBeVisible();
  });

  test('should allow adding payment method', async ({ page }) => {
    await page.click('[data-testid="add-payment-method"]');
    await page.fill('[data-testid="card-number"]', '4111111111111111');
    await page.fill('[data-testid="expiry"]', '12/28');
    await page.fill('[data-testid="cvv"]', '123');
    await page.click('[data-testid="save-card"]');
    await expect(page.locator('[data-testid="card-added"]')).toBeVisible();
  });

  test('should display invoice history', async ({ page }) => {
    const invoicesElement = page.locator('[data-testid="invoices"]');
    await expect(invoicesElement).toBeVisible();
  });

  test('should allow downloading invoice', async ({ page }) => {
    await page.click('[data-testid="download-invoice"]');
    await expect(page.locator('[data-testid="invoice-downloaded"]')).toBeVisible();
  });

  test('should display billing address', async ({ page }) => {
    const addressElement = page.locator('[data-testid="billing-address"]');
    await expect(addressElement).toBeVisible();
  });

  test('should allow updating billing address', async ({ page }) => {
    await page.click('[data-testid="edit-address"]');
    await page.fill('[data-testid="address-line"]', '123 New Street');
    await page.fill('[data-testid="city"]', 'Ho Chi Minh City');
    await page.fill('[data-testid="postal-code"]', '700000');
    await page.click('[data-testid="save-address"]');
    await expect(page.locator('[data-testid="address-updated"]')).toBeVisible();
  });

  test('should display billing notifications', async ({ page }) => {
    const notificationsElement = page.locator('[data-testid="notifications"]');
    await expect(notificationsElement).toBeVisible();
  });
});
