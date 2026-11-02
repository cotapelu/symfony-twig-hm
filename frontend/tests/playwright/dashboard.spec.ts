import { test, expect } from '@playwright/test';

test.describe('Smart City Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.fill('[data-testid="email"]', 'admin@city.gov.vn');
    await page.fill('[data-testid="password"]', 'adminpass');
    await page.click('[data-testid="login-button"]');
    await page.waitForURL('/dashboard');
  });

  test('should display city overview widgets', async ({ page }) => {
    await expect(page.locator('[data-testid="population-widget"]')).toBeVisible();
    await expect(page.locator('[data-testid="traffic-widget"]')).toBeVisible();
    await expect(page.locator('[data-testid="services-widget"]')).toBeVisible();
  });

  test('should update real-time data', async ({ page }) => {
    const initialValue = await page.textContent('[data-testid="active-orders"]');
    await page.click('[data-testid="refresh-button"]');
    await expect(page.locator('[data-testid="last-updated"]')).toContainText('vừa xong');
  });

  test('should navigate to detailed reports', async ({ page }) => {
    await page.click('[data-testid="reports-widget"]');
    await page.click('[data-testid="view-details"]');
    await expect(page).toHaveURL(/\/reports/);
  });

  test('should filter dashboard data by date range', async ({ page }) => {
    await page.click('[data-testid="date-filter"]');
    await page.selectOption('[data-testid="date-range"]', 'today');
    await expect(page.locator('[data-testid="date-label"]')).toContainText('Hôm nay');
  });

  test('should toggle dark mode', async ({ page }) => {
    const body = page.locator('body');
    await expect(body).toHaveClass(/light/);
    await page.click('[data-testid="theme-toggle"]');
    await expect(page.locator('body')).toHaveClass(/dark/);
  });
});
