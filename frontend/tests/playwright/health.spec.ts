import { test, expect } from '@playwright/test';

test.describe('Health Check', () => {
  test('should return healthy status', async ({ page }) => {
    const response = await page.request.get('/health');
    expect(response.status()).toBe(200);
    const body = await response.json();
    expect(body.status).toMatch(/healthy|ready|unhealthy|degraded/);
  });

  test('should check readiness', async ({ page }) => {
    const response = await page.request.get('/health/ready');
    expect(response.status()).toBe(200);
    const body = await response.json();
    expect(['ready', 'not_ready']).toContain(body.status);
  });

  test('should return alive', async ({ page }) => {
    const response = await page.request.get('/health/live');
    expect(response.status()).toBe(200);
    const body = await response.json();
    expect(body.status).toBe('alive');
  });

  test('should include checks in response', async ({ page }) => {
    const response = await page.request.get('/health');
    const body = await response.json();
    expect(body).toHaveProperty('checks');
    expect(body.checks).toHaveProperty('database');
    expect(body.checks).toHaveProperty('cache');
  });
});
