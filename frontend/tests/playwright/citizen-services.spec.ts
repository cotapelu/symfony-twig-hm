import { test, expect } from '@playwright/test';

test.describe('Citizen Services Flow', () => {
  test('should display citizen services dashboard', async ({ page }) => {
    await page.goto('/citizen-services');
    await expect(page.locator('[data-testid="services-list"]')).toBeVisible();
  });

  test('should search for administrative procedure', async ({ page }) => {
    await page.goto('/citizen-services');
    await page.fill('[data-testid="search-procedure"]', 'birth certificate');
    await page.click('[data-testid="search-button"]');
    await expect(page.locator('[data-testid="procedure-results"]')).toBeVisible();
  });

  test('should view procedure details', async ({ page }) => {
    await page.goto('/citizen-services/procedure/123');
    await expect(page.locator('[data-testid="procedure-name"]')).toBeVisible();
    await expect(page.locator('[data-testid="requirements-list"]')).toBeVisible();
  });

  test('should start new procedure submission', async ({ page }) => {
    await page.goto('/citizen-services/procedure/123');
    await page.click('[data-testid="start-procedure"]');
    await expect(page.locator('[data-testid="procedure-form"]')).toBeVisible();
  });

  test('should submit procedure with documents', async ({ page }) => {
    await page.goto('/citizen-services/submit/123');
    await page.fill('[data-testid="full-name"]', 'Nguyen Van A');
    await page.fill('[data-testid="identity-number"]', '012345678901');
    await page.fill('[data-testid="address"]', '123 Main Street, District 1, HCMC');
    await page.click('[data-testid="upload-documents"]');
    await page.click('[data-testid="submit-procedure"]');
    await expect(page.locator('[data-testid="submission-confirmation"]')).toBeVisible();
  });

  test('should track procedure status', async ({ page }) => {
    await page.goto('/citizen-services/track');
    await page.fill('[data-testid="tracking-code"]', 'TTHC-2026-12345');
    await page.click('[data-testid="track-button"]');
    await expect(page.locator('[data-testid="status-result"]')).toBeVisible();
  });

  test('should display procedure history', async ({ page }) => {
    await page.goto('/citizen-services/history');
    await expect(page.locator('[data-testid="history-list"]')).toBeVisible();
  });

  test('should download document', async ({ page }) => {
    await page.goto('/citizen-services/document/123');
    await page.click('[data-testid="download-button"]');
  });
});
