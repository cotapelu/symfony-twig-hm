import { test, expect } from '@playwright/test';

test.describe('Hotel Booking Flow E2E', () => {
  test('should search hotels', async ({ page }) => {
    await page.goto('/hotels');
    
    await page.fill('[data-testid="destination"]', 'Da Nang');
    await page.fill('[data-testid="checkin"]', '2026-04-01');
    await page.fill('[data-testid="checkout"]', '2026-04-05');
    await page.fill('[data-testid="guests"]', '2');
    
    await page.click('[data-testid="search-button"]');
    await expect(page.locator('[data-testid="hotel-results"]')).toBeVisible();
  });

  test('should view hotel details', async ({ page }) => {
    await page.goto('/hotels');
    await page.click('[data-testid="hotel-card"]:first-child');
    await expect(page.locator('[data-testid="hotel-details"]')).toBeVisible();
  });

  test('should select room and proceed to checkout', async ({ page }) => {
    await page.goto('/hotels/1');
    await page.click('[data-testid="room-type"]:first-child');
    await page.click('[data-testid="book-now"]');
    await expect(page.locator('[data-testid="checkout-page"]')).toBeVisible();
  });
});

test.describe('Flight Booking Flow E2E', () => {
  test('should search flights', async ({ page }) => {
    await page.goto('/flights');
    
    await page.fill('[data-testid="from"]', 'SGN');
    await page.fill('[data-testid="to"]', 'HAN');
    await page.fill('[data-testid="departure"]', '2026-04-15');
    await page.selectOption('[data-testid="passengers"]', '1');
    
    await page.click('[data-testid="search-flights"]');
    await expect(page.locator('[data-testid="flight-results"]')).toBeVisible();
  });

  test('should select flight and enter passenger details', async ({ page }) => {
    await page.goto('/flights/search');
    await page.click('[data-testid="flight-option"]:first-child');
    await page.click('[data-testid="continue"]');
    
    await expect(page.locator('[data-testid="passenger-form"]')).toBeVisible();
    await page.fill('[data-testid="passenger-name"]', 'Nguyen Van A');
    await page.fill('[data-testid="passenger-passport"]', 'A12345678');
  });
});

test.describe('Payment Flow E2E', () => {
  test('should display payment methods', async ({ page }) => {
    await page.goto('/payment/methods');
    await expect(page.locator('[data-testid="payment-methods-list"]')).toBeVisible();
  });

  test('should add new payment method', async ({ page }) => {
    await page.goto('/payment/methods');
    await page.click('[data-testid="add-method"]');
    
    await page.selectOption('[data-testid="card-type"]', 'visa');
    await page.fill('[data-testid="card-number"]', '4111111111111111');
    await page.fill('[data-testid="card-expiry"]', '12/28');
    await page.fill('[data-testid="card-cvv"]', '123');
    
    await page.click('[data-testid="save-card"]');
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
  });

  test('should process wallet topup', async ({ page }) => {
    await page.goto('/wallet/topup');
    
    await page.fill('[data-testid="amount"]', '500000');
    await page.selectOption('[data-testid="method"]', 'bank_transfer');
    await page.click('[data-testid="proceed"]');
    
    await expect(page.locator('[data-testid="confirmation"]')).toBeVisible();
  });

  test('should view transaction history', async ({ page }) => {
    await page.goto('/wallet/history');
    await expect(page.locator('[data-testid="transactions-list"]')).toBeVisible();
  });
});
