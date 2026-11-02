/**
 * E2E Integration Tests for Protected API Endpoints
 * Tests critical user journeys: login → protected API → logout
 */
import { test, expect } from '@playwright/test';

test.describe('Authentication Flow', () => {
  test('should login and access protected profile', async ({ page }) => {
    // Navigate to login page
    await page.goto('/auth/login');
    
    // Fill login form
    await page.fill('[data-testid="email-input"]', 'test@example.com');
    await page.fill('[data-testid="password-input"]', 'password123');
    
    // Submit
    await page.click('[data-testid="login-button"]');
    
    // Wait for navigation
    await page.waitForURL('/dashboard');
    
    // Access protected profile
    const response = await page.request.get('/api/auth/profile');
    expect(response.ok()).toBeTruthy();
    
    const profile = await response.json();
    expect(profile.email).toBe('test@example.com');
  });

  test('should reject unauthorized API access', async ({ page }) => {
    // Without login, try to access protected endpoint
    const response = await page.request.get('/api/wallet/me');
    expect(response.status()).toBe(401);
  });

  test('should complete ecommerce order flow', async ({ page, request }) => {
    // Login first
    await request.post('/api/auth/login', {
      data: { email: 'user@example.com', password: 'password' }
    });
    
    // Get products
    const products = await request.get('/api/ecommerce/products');
    expect(products.ok()).toBeTruthy();
    
    const productList = await products.json();
    expect(productList.length).toBeGreaterThan(0);
    
    // Create order
    const order = await request.post('/api/ecommerce/checkout', {
      data: { productId: productList[0].id, quantity: 1 }
    });
    expect(order.ok()).toBeTruthy();
  });

  test('should access ecommerce endpoints with auth', async ({ page, request }) => {
    // Login
    const loginResponse = await request.post('/api/auth/login', {
      data: { email: 'user@example.com', password: 'password' }
    });
    const { token } = await loginResponse.json();
    
    // Authenticated request
    const response = await request.get('/api/ecommerce/orders', {
      headers: { Authorization: `Bearer ${token}` }
    });
    expect(response.ok()).toBeTruthy();
  });
});