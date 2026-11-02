/**
 * E2E Tests for Wallet and Payment Flows
 */
import { test, expect } from '@playwright/test';

test.describe('Wallet Flow', () => {
  test('should check wallet balance after login', async ({ request }) => {
    // Login
    const loginResponse = await request.post('/api/auth/login', {
      data: { email: 'user@example.com', password: 'password' }
    });
    const { token } = await loginResponse.json();
    
    // Get wallet balance
    const balanceResponse = await request.get('/api/wallet/balance', {
      headers: { Authorization: `Bearer ${token}` }
    });
    expect(balanceResponse.ok()).toBeTruthy();
    
    const balance = await balanceResponse.json();
    expect(balance).toHaveProperty('balance');
    expect(typeof balance.balance).toBe('number');
  });

  test('should create deposit transaction', async ({ request }) => {
    const loginResponse = await request.post('/api/auth/login', {
      data: { email: 'user@example.com', password: 'password' }
    });
    const { token } = await loginResponse.json();
    
    const depositResponse = await request.post('/api/wallet/deposit', {
      headers: { Authorization: `Bearer ${token}` },
      data: { amount: 100.00, method: 'card' }
    });
    expect(depositResponse.ok()).toBeTruthy();
  });
});

test.describe('Delivery Flow', () => {
  test('should create delivery order', async ({ request }) => {
    const loginResponse = await request.post('/api/auth/login', {
      data: { email: 'user@example.com', password: 'password' }
    });
    const { token } = await loginResponse.json();
    
    const orderResponse = await request.post('/api/delivery/orders', {
      headers: { Authorization: `Bearer ${token}` },
      data: {
        pickupAddress: '123 Main St',
        deliveryAddress: '456 Oak Ave',
        packageDesc: 'Test package'
      }
    });
    expect(orderResponse.ok()).toBeTruthy();
    
    const order = await orderResponse.json();
    expect(order).toHaveProperty('id');
    expect(order.status).toBe('pending');
  });
});

test.describe('Notification Flow', () => {
  test('should get user notifications', async ({ request }) => {
    const loginResponse = await request.post('/api/auth/login', {
      data: { email: 'user@example.com', password: 'password' }
    });
    const { token } = await loginResponse.json();
    
    const notifResponse = await request.get('/api/notifications', {
      headers: { Authorization: `Bearer ${token}` }
    });
    expect(notifResponse.ok()).toBeTruthy();
  });
});