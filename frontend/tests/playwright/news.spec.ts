import { test, expect } from '@playwright/test';

test.describe('News Module', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/news');
  });

  test('should display news page', async ({ page }) => {
    await expect(page.locator('h1')).toContainText('News');
  });

  test('should display article list', async ({ page }) => {
    await expect(page.locator('[data-testid="article-card"]')).toBeVisible();
  });

  test('should open article on click', async ({ page }) => {
    await page.locator('[data-testid="article-card"]').first().click();
    await expect(page).toHaveURL(/.*article/);
  });

  test('should search articles', async ({ page }) => {
    await page.fill('[data-testid="search-input"]', 'test');
    await page.click('[data-testid="search-button"]');
    await expect(page.locator('[data-testid="article-card"]').first()).toBeVisible();
  });

  test('should filter by category', async ({ page }) => {
    await page.click('[data-testid="category-filter"]');
    await page.click('[data-testid="category-technology"]');
    await expect(page.locator('[data-testid="article-card"]')).toHaveCount(3);
  });
});

test.describe('News Comments', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/news/article/1');
  });

  test('should display comments section', async ({ page }) => {
    await expect(page.locator('[data-testid="comments-section"]')).toBeVisible();
  });

  test('should add a comment', async ({ page }) => {
    await page.fill('[data-testid="comment-input"]', 'Great article!');
    await page.click('[data-testid="submit-comment"]');
    await expect(page.locator('[data-testid="comment"]').last()).toContainText('Great article!');
  });

  test('should not submit empty comment', async ({ page }) => {
    await page.click('[data-testid="submit-comment"]');
    await expect(page.locator('[data-testid="error-message"]')).toBeVisible();
  });
});