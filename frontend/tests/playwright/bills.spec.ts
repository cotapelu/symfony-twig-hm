import { test, expect } from '@playwright/test';

test.describe('Bills Module E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/bills');
  });

  test('should display bills dashboard', async ({ page }) => {
    await expect(page.locator('h1')).toContainText('Bills');
  });

  test('should display pending bills', async ({ page }) => {
    const pendingBills = page.locator('[data-testid="pending-bills"]');
    await expect(pendingBills).toBeVisible();
  });

  test('should display bill categories', async ({ page }) => {
    const categoriesElement = page.locator('[data-testid="bill-categories"]');
    await expect(categoriesElement).toBeVisible();
  });

  test('should filter bills by category', async ({ page }) => {
    await page.selectOption('[data-testid="category-filter"]', 'electricity');
    await expect(page.locator('[data-testid="filtered-bills"]')).toBeVisible();
  });

  test('should allow paying electricity bill', async ({ page }) => {
    await page.click('[data-testid="pay-electricity"]');
    await page.fill('[data-testid="consumer-number"]', 'EB123456');
    await page.fill('[data-testid="amount"]', '250000');
    await page.click('[data-testid="pay-now"]');
    await expect(page.locator('[data-testid="payment-success"]')).toBeVisible();
  });

  test('should allow paying water bill', async ({ page }) => {
    await page.click('[data-testid="pay-water"]');
    await page.fill('[data-testid="customer-id"]', 'WA987654');
    await page.fill('[data-testid="amount"]', '180000');
    await page.click('[data-testid="pay-now"]');
    await expect(page.locator('[data-testid="payment-success"]')).toBeVisible();
  });

  test('should allow paying internet bill', async ({ page }) => {
    await page.click('[data-testid="pay-internet"]');
    await page.fill('[data-testid="account-id"]', 'INT456789');
    await page.fill('[data-testid="amount"]', '350000');
    await page.click('[data-testid="pay-now"]');
    await expect(page.locator('[data-testid="payment-success"]')).toBeVisible();
  });

  test('should display payment history', async ({ page }) => {
    const historyElement = page.locator('[data-testid="payment-history"]');
    await expect(historyElement).toBeVisible();
  });

  test('should allow setting up auto-pay', async ({ page }) => {
    await page.click('[data-testid="autopay-setup"]');
    await page.check('[data-testid="enable-autopay"]');
    await page.selectOption('[data-testid="payment-method"]', 'card');
    await page.click('[data-testid="save-autopay"]');
    await expect(page.locator('[data-testid="autopay-enabled"]')).toBeVisible();
  });

  test('should display bill reminders', async ({ page }) => {
    const remindersElement = page.locator('[data-testid="reminders"]');
    await expect(remindersElement).toBeVisible();
  });

  test('should allow creating bill reminder', async ({ page }) => {
    await page.click('[data-testid="add-reminder"]');
    await page.selectOption('[data-testid="reminder-type"]', 'electricity');
    await page.fill('[data-testid="reminder-day"]', '5');
    await page.click('[data-testid="save-reminder"]');
    await expect(page.locator('[data-testid="reminder-created"]')).toBeVisible();
  });

  test('should display bill statistics', async ({ page }) => {
    const statsElement = page.locator('[data-testid="bill-stats"]');
    await expect(statsElement).toBeVisible();
  });
});
