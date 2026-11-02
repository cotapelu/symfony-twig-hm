import { test, expect } from '@playwright/test';

test.describe('Payment Flow', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('[data-testid="email"]', 'user@example.com');
    await page.fill('[data-testid="password"]', 'userpass');
    await page.click('[data-testid="login-button"]');
    await page.waitForURL('/dashboard');
    await page.click('[data-testid="menu-digital-wallet"]');
  });

  test('should view wallet balance', async ({ page }) => {
    await expect(page.locator('[data-testid="wallet-balance"]')).toHaveText(/[0-9,]+ VND/);
  });

  test('should top up wallet', async ({ page }) => {
    await page.click('[data-testid="topup-button"]');
    await page.fill('[data-testid="amount"]', '500000');
    await page.selectOption('[data-testid="payment-method"]', 'bank_transfer');
    await page.click('[data-testid="confirm-topup"]');
    await expect(page.locator('[data-testid="success-toast"]')).toBeVisible();
    await expect(page.locator('[data-testid="wallet-balance"]')).toContainText('500,000');
  });

  test('should make payment to merchant', async ({ page }) => {
    await page.goto('/wallet/pay?merchant=merchant123&amount=100000');
    await page.click('[data-testid="confirm-payment"]');
    await expect(page.locator('[data-testid="payment-status"]')).toContainText('Thành công');
  });

  test('should transfer to another user', async ({ page }) => {
    await page.click('[data-testid="transfer-button"]');
    await page.fill('[data-testid="recipient-phone"]', '0901234567');
    await page.fill('[data-testid="transfer-amount"]', '200000');
    await page.click('[data-testid="confirm-transfer"]');
    await expect(page.locator('[data-testid="transfer-success"]')).toBeVisible();
  });

  test('should view transaction history', async ({ page }) => {
    await page.click('[data-testid="history-tab"]');
    const rows = page.locator('[data-testid="transaction-row"]');
    await expect(rows.first()).toBeVisible();
  });

  test('should fail top up with invalid amount', async ({ page }) => {
    await page.click('[data-testid="topup-button"]');
    await page.fill('[data-testid="amount"]', '5000');
    await page.click('[data-testid="confirm-topup"]');
    await expect(page.locator('[data-testid="error-toast"]')).toBeVisible();
  });
});
