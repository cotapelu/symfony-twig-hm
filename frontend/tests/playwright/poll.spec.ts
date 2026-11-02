import { test, expect } from '@playwright/test';

test.describe('Poll Module', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/poll');
  });

  test('should display poll page', async ({ page }) => {
    await expect(page.locator('h1')).toContainText('Polls');
  });

  test('should display active polls', async ({ page }) => {
    await expect(page.locator('[data-testid="poll-card"]').first()).toBeVisible();
  });

  test('should filter by status', async ({ page }) => {
    await page.click('[data-testid="status-filter"]');
    await page.click('[data-testid="filter-active"]');
    await expect(page.locator('[data-testid="poll-card"]').first()).toBeVisible();
  });
});

test.describe('Poll Voting', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/poll/1');
  });

  test('should display poll question and options', async ({ page }) => {
    await expect(page.locator('[data-testid="poll-question"]')).toBeVisible();
    await expect(page.locator('[data-testid="poll-option"]').first()).toBeVisible();
  });

  test('should select option', async ({ page }) => {
    await page.locator('[data-testid="poll-option"]').first().click();
    await expect(page.locator('[data-testid="poll-option"]').first()).toHaveClass(/selected/);
  });

  test('should vote successfully', async ({ page }) => {
    await page.locator('[data-testid="poll-option"]').first().click();
    await page.click('[data-testid="submit-vote"]');
    await expect(page.locator('[data-testid="vote-success"]')).toBeVisible();
    await expect(page.locator('[data-testid="results-chart"]')).toBeVisible();
  });

  test('should show results after voting', async ({ page }) => {
    await page.locator('[data-testid="poll-option"]').first().click();
    await page.click('[data-testid="submit-vote"]');
    await expect(page.locator('[data-testid="poll-results"]')).toBeVisible();
  });
});

test.describe('Poll Creation', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/poll/create');
  });

  test('should display create poll form', async ({ page }) => {
    await expect(page.locator('[data-testid="create-poll-form"]')).toBeVisible();
    await expect(page.locator('[data-testid="question-input"]')).toBeVisible();
    await expect(page.locator('[data-testid="options-list"]')).toBeVisible();
  });

  test('should add poll option', async ({ page }) => {
    await page.fill('[data-testid="question-input"]', 'What is your favorite color?');
    await page.click('[data-testid="add-option"]');
    await expect(page.locator('[data-testid="poll-option-input"]')).toHaveCount(3);
  });

  test('should submit poll', async ({ page }) => {
    await page.fill('[data-testid="question-input"]', 'Test poll?');
    await page.fill('[data-testid="poll-option-input"]', 'Option A');
    await page.fill('[data-testid="poll-option-input"]', 'Option B');
    await page.click('[data-testid="create-poll-submit"]');
    await expect(page).toHaveURL(/.*poll/);
  });

  test('should require question and at least two options', async ({ page }) => {
    await page.click('[data-testid="create-poll-submit"]');
    await expect(page.locator('[data-testid="validation-error"]')).toBeVisible();
  });
});

test.describe('Poll Results', () => {
  test('should display poll results', async ({ page }) => {
    await page.goto('/poll/1/results');
    await expect(page.locator('[data-testid="poll-results"]')).toBeVisible();
    await expect(page.locator('[data-testid="result-bar"]').first()).toBeVisible();
  });

  test('should show vote counts', async ({ page }) => {
    await page.goto('/poll/1/results');
    await expect(page.locator('[data-testid="vote-count"]').first()).toBeVisible();
  });

  test('should show percentages', async ({ page }) => {
    await page.goto('/poll/1/results');
    await expect(page.locator('[data-testid="vote-percentage"]').first()).toBeVisible();
  });
});