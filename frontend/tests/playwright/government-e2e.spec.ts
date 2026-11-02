import { test, expect } from '@playwright/test';

test.describe('Government Services E2E', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/government');
  });

  test('should display government dashboard', async ({ page }) => {
    await expect(page.locator('h1')).toContainText(/Government|Citizen|Administrative/);
  });

  test('should navigate to citizen management tab', async ({ page }) => {
    await page.click('[data-testid="tab-citizens"]');
    await expect(page.locator('[data-testid="citizens-table"]')).toBeVisible();
  });

  test('should search citizen by CCCD', async ({ page }) => {
    await page.click('[data-testid="tab-citizens"]');
    await page.fill('[data-testid="search-input"]', '123456789012');
    await page.click('[data-testid="search-button"]');
    await expect(page.locator('[data-testid="search-results"]')).toBeVisible();
  });

  test('should navigate to TTHC procedures tab', async ({ page }) => {
    await page.click('[data-testid="tab-ththc"]');
    await expect(page.locator('[data-testid="procedures-list"]')).toBeVisible();
  });

  test('should view procedure details', async ({ page }) => {
    await page.click('[data-testid="tab-ththc"]');
    await page.click('[data-testid="procedure-item"]:first-child');
    await expect(page.locator('[data-testid="procedure-details"]')).toBeVisible();
  });

  test('should navigate to documents tab', async ({ page }) => {
    await page.click('[data-testid="tab-documents"]');
    await expect(page.locator('[data-testid="documents-list"]')).toBeVisible();
  });

  test('should display statistics', async ({ page }) => {
    await page.click('[data-testid="tab-statistics"]');
    await expect(page.locator('[data-testid="stats-cards"]')).toBeVisible();
  });

  test('should filter citizens by ward', async ({ page }) => {
    await page.click('[data-testid="tab-citizens"]');
    await page.selectOption('[data-testid="ward-filter"]', 'ward-001');
    await expect(page.locator('[data-testid="citizens-table"]')).toBeVisible();
  });
});

test.describe('Administrative Procedures Flow', () => {
  test('should submit new procedure application', async ({ page }) => {
    await page.goto('/government/ththc');
    await page.click('[data-testid="new-application"]');
    
    await page.selectOption('[data-testid="procedure-type"]', 'cccd');
    await page.fill('[data-testid="applicant-name"]', 'Nguyen Van A');
    await page.fill('[data-testid="applicant-cccd"]', '123456789012');
    await page.fill('[data-testid="applicant-address"]', '123 Main St, Ward 1');
    
    await page.click('[data-testid="submit-button"]');
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should track application status', async ({ page }) => {
    await page.goto('/government/track');
    await page.fill('[data-testid="application-id"]', 'APP-2026-001');
    await page.click('[data-testid="track-button"]');
    await expect(page.locator('[data-testid="application-status"]')).toBeVisible();
  });
});
