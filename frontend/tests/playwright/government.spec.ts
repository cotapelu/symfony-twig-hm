import { test, expect } from '@playwright/test';

test.describe('Government Module', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
    // Assume logged in
    await page.fill('[data-testid="email"]', 'test@example.com');
    await page.fill('[data-testid="password"]', 'password123');
    await page.click('[data-testid="login-button"]');
    await page.waitForURL('/dashboard');
  });

  test('should access government services portal', async ({ page }) => {
    await page.click('[data-testid="menu-government"]');
    await expect(page).toHaveURL(/\/government/);
  });

  test('should view citizen profile by ID', async ({ page }) => {
    await page.click('[data-testid="menu-government"]');
    await page.fill('[data-testid="citizen-search"]', '123456789');
    await page.click('[data-testid="search-button"]');
    await expect(page.locator('[data-testid="citizen-name"]')).toContainText('Nguyễn Văn A');
  });

  test('should submit administrative procedure', async ({ page }) => {
    await page.click('[data-testid="menu-government"]');
    await page.click('[data-testid="new-application"]');
    await page.selectOption('[data-testid="procedure-type"]', 'building_permit');
    await page.fill('[data-testid="applicant-name"]', 'Nguyễn Văn B');
    await page.click('[data-testid="submit-application"]');
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should track application status', async ({ page }) => {
    await page.click('[data-testid="menu-government"]');
    await page.click('[data-testid="my-applications"]');
    await expect(page.locator('[data-testid="application-status"]').first()).toContainText('Đang xử lý');
  });
});
