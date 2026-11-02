import { test, expect } from '@playwright/test';

test.describe('Booking Flow', () => {
  test('should display available hotels', async ({ page }) => {
    await page.goto('/booking');
    await expect(page.locator('[data-testid="hotel-list"]')).toBeVisible();
  });

  test('should search hotels by city', async ({ page }) => {
    await page.goto('/booking');
    await page.fill('[data-testid="city-input"]', 'Ho Chi Minh');
    await page.click('[data-testid="search-button"]');
    await expect(page.locator('[data-testid="search-results"]')).toBeVisible();
  });

  test('should filter hotels by star rating', async ({ page }) => {
    await page.goto('/booking');
    await page.selectOption('[data-testid="star-filter"]', '5');
    await expect(page.locator('[data-testid="hotel-card"]')).toBeVisible();
  });

  test('should select hotel and view details', async ({ page }) => {
    await page.goto('/booking/hotel/123');
    await expect(page.locator('[data-testid="hotel-name"]')).toBeVisible();
    await expect(page.locator('[data-testid="room-types"]')).toBeVisible();
  });

  test('should select room and proceed to checkout', async ({ page }) => {
    await page.goto('/booking/hotel/123');
    await page.click('[data-testid="select-room"]');
    await expect(page.locator('[data-testid="checkout-form"]')).toBeVisible();
  });

  test('should complete booking', async ({ page }) => {
    await page.goto('/booking/checkout');
    await page.fill('[data-testid="guest-name"]', 'John Doe');
    await page.fill('[data-testid="guest-email"]', 'john@example.com');
    await page.fill('[data-testid="guest-phone"]', '+1234567890');
    await page.click('[data-testid="confirm-booking"]');
    await expect(page.locator('[data-testid="booking-confirmation"]')).toBeVisible();
  });

  test('should display booking history', async ({ page }) => {
    await page.goto('/bookings');
    await expect(page.locator('[data-testid="booking-list"]')).toBeVisible();
  });

  test('should cancel booking', async ({ page }) => {
    await page.goto('/bookings/booking-123');
    await page.click('[data-testid="cancel-booking"]');
    await expect(page.locator('[data-testid="cancellation-confirm"]')).toBeVisible();
  });
});
