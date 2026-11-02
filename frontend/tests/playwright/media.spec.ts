import { test, expect } from '@playwright/test';

test.describe('Media Module', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/media');
  });

  test('should display media page', async ({ page }) => {
    await expect(page.locator('h1')).toContainText('Media');
  });

  test('should display file list', async ({ page }) => {
    await expect(page.locator('[data-testid="file-item"]').first()).toBeVisible();
  });

  test('should navigate to folder', async ({ page }) => {
    await page.locator('[data-testid="folder-item"]').first().click();
    await expect(page).toHaveURL(/.*folder/);
  });

  test('should upload file', async ({ page }) => {
    await page.click('[data-testid="upload-button"]');
    await expect(page.locator('[data-testid="upload-modal"]')).toBeVisible();
  });

  test('should display thumbnail on hover', async ({ page }) => {
    await page.locator('[data-testid="file-item"]').first().hover();
    await expect(page.locator('[data-testid="file-preview"]')).toBeVisible();
  });

  test('should search files', async ({ page }) => {
    await page.fill('[data-testid="search-input"]', 'image');
    await page.press('[data-testid="search-input"]', 'Enter');
    await expect(page.locator('[data-testid="file-item"]').first()).toBeVisible();
  });
});

test.describe('Media Upload', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/media/upload');
  });

  test('should show upload form', async ({ page }) => {
    await expect(page.locator('[data-testid="upload-form"]')).toBeVisible();
    await expect(page.locator('[data-testid="file-input"]')).toBeVisible();
    await expect(page.locator('[data-testid="submit-upload"]')).toBeDisabled();
  });

  test('should validate file type', async ({ page }) => {
    await page.setInputFiles('[data-testid="file-input"]', 'test.pdf');
    await expect(page.locator('[data-testid="file-type-error"]')).toBeVisible();
  });

  test('should enable submit after valid file selection', async ({ page }) => {
    await page.setInputFiles('[data-testid="file-input"]', 'test.jpg');
    await expect(page.locator('[data-testid="submit-upload"]')).toBeEnabled();
  });
});

test.describe('Media Folder Management', () => {
  test('should create new folder', async ({ page }) => {
    await page.goto('/media');
    await page.click('[data-testid="new-folder-button"]');
    await page.fill('[data-testid="folder-name-input"]', 'Vacation Photos');
    await page.click('[data-testid="create-folder-submit"]');
    await expect(page.locator('[data-testid="folder-item"]').last()).toContainText('Vacation Photos');
  });

  test('should rename folder', async ({ page }) => {
    await page.locator('[data-testid="folder-item"]').first().hover();
    await page.click('[data-testid="folder-actions-menu"]');
    await page.click('[data-testid="rename-folder"]');
    await page.fill('[data-testid="folder-name-input"]', 'Updated Name');
    await page.click('[data-testid="confirm-rename"]');
    await expect(page.locator('[data-testid="folder-item"]').first()).toContainText('Updated Name');
  });

  test('should delete folder', async ({ page }) => {
    await page.locator('[data-testid="folder-item"]').first().hover();
    await page.click('[data-testid="folder-actions-menu"]');
    await page.click('[data-testid="delete-folder"]');
    await expect(page.locator('[data-testid="confirm-delete-dialog"]')).toBeVisible();
    await page.click('[data-testid="confirm-delete"]');
  });
});