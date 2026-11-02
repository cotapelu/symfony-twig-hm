import { test, expect } from '@playwright/test';

test.describe('Payment Flow', () => {
  test('should view wallet balance', async ({ page }) => {
    await page.goto('/wallet');
    await expect(page.locator('.balance')).toBeVisible();
  });

  test('should topup wallet', async ({ page }) => {
    await page.goto('/wallet/topup');
    await page.fill('input[name="amount"]', '100000');
    await page.click('button[type="submit"]');
    await expect(page.locator('.success-message')).toBeVisible();
  });

  test('should transfer money', async ({ page }) => {
    await page.goto('/wallet/transfer');
    await page.fill('input[name="to_user"]', 'user456');
    await page.fill('input[name="amount"]', '50000');
    await page.click('button[type="submit"]');
    await expect(page.locator('.success-message')).toBeVisible();
  });
});
