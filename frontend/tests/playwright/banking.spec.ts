import { test, expect } from '@playwright/test';

test.describe('Banking Module E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/banking');
  });

  test('should display banking dashboard', async ({ page }) => {
    await expect(page.locator('h1')).toContainText('Banking');
  });

  test('should display account balance', async ({ page }) => {
    const balanceElement = page.locator('[data-testid="account-balance"]');
    await expect(balanceElement).toBeVisible();
  });

  test('should display transaction history', async ({ page }) => {
    const transactionsElement = page.locator('[data-testid="transactions"]');
    await expect(transactionsElement).toBeVisible();
  });

  test('should allow money transfer', async ({ page }) => {
    await page.click('[data-testid="transfer-btn"]');
    await page.fill('[data-testid="recipient-account"]', '1234567890');
    await page.fill('[data-testid="amount"]', '100000');
    await page.click('[data-testid="confirm-transfer"]');
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should display bank accounts list', async ({ page }) => {
    const accountsList = page.locator('[data-testid="accounts-list"]');
    await expect(accountsList).toBeVisible();
  });

  test('should allow adding new beneficiary', async ({ page }) => {
    await page.click('[data-testid="add-beneficiary-btn"]');
    await page.fill('[data-testid="beneficiary-name"]', 'John Doe');
    await page.fill('[data-testid="beneficiary-account"]', '9876543210');
    await page.click('[data-testid="save-beneficiary"]');
    await expect(page.locator('[data-testid="beneficiary-added"]')).toBeVisible();
  });

  test('should display transaction categories', async ({ page }) => {
    const categoriesElement = page.locator('[data-testid="categories"]');
    await expect(categoriesElement).toBeVisible();
  });

  test('should filter transactions by date', async ({ page }) => {
    await page.click('[data-testid="filter-date"]');
    await page.click('[data-testid="last-30-days"]');
    await expect(page.locator('[data-testid="filtered-transactions"]')).toBeVisible();
  });

  test('should display banking services menu', async ({ page }) => {
    const servicesMenu = page.locator('[data-testid="services-menu"]');
    await expect(servicesMenu).toBeVisible();
  });

  test('should allow bill payment', async ({ page }) => {
    await page.click('[data-testid="pay-bills"]');
    await page.selectOption('[data-testid="bill-type"]', 'electricity');
    await page.fill('[data-testid="consumer-number"]', '123456789');
    await page.fill('[data-testid="bill-amount"]', '150000');
    await page.click('[data-testid="pay-bill-btn"]');
    await expect(page.locator('[data-testid="bill-paid"]')).toBeVisible();
  });
});
