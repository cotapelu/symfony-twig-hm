import { test, expect } from '@playwright/test';

test.describe('Government Module', () => {
  test('should view citizen list', async ({ page }) => {
    await page.goto('/government');
    await expect(page.locator('h1')).toContainText('Quản lý công dân');
    await expect(page.locator('.citizen-table')).toBeVisible();
  });

  test('should create new citizen', async ({ page }) => {
    await page.goto('/government/citizens/new');
    await page.fill('input[name="name"]', 'Nguyen Van A');
    await page.fill('input[name="citizenId"]', '012345678901');
    await page.click('button[type="submit"]');
    await expect(page.locator('.success-message')).toBeVisible();
  });

  test('should view citizen details', async ({ page }) => {
    await page.goto('/government/citizens/1');
    await expect(page.locator('.citizen-details')).toBeVisible();
    await expect(page.locator('.citizen-id')).toContainText('012345678901');
  });
});
