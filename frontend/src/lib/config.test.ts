import { describe, it, expect, vi, beforeEach } from 'vitest';
import * as config from './config';

describe('Config module', () => {
  beforeEach(() => {
    // Reset env to defaults for each test
    delete process.env.VITE_API_BASE;
    delete process.env.VITE_GO_API_BASE;
    delete process.env.VITE_PYTHON_API_BASE;
    delete process.env.VITE_RUST_API_BASE;
    delete process.env.VITE_ZIG_API_BASE;
    delete process.env.VITE_ELIXIR_API_BASE;
    delete process.env.VITE_MERCURE_HUB_URL;
    delete process.env.MODE;
    // Re-import module to get fresh values
    vi.resetModules();
  });

  it('should export API_BASE with default when no env set', async () => {
    const mod = await import('./config');
    expect(mod.API_BASE).toBe('http://localhost:80');
  });

  it('should use VITE_GO_API_BASE if set', async () => {
    process.env.VITE_GO_API_BASE = 'http://go.example.com';
    const mod = await import('./config');
    expect(mod.GO_API_BASE).toBe('http://go.example.com');
  });

  it('should default PYTHON_API_BASE to localhost:8000', async () => {
    const mod = await import('./config');
    expect(mod.PYTHON_API_BASE).toBe('http://localhost:8000');
  });

  it('should export APP_NAME and APP_VERSION', async () => {
    const mod = await import('./config');
    expect(mod.APP_NAME).toBe('CommUnity');
    expect(mod.APP_VERSION).toBe('1.0.0');
  });

  it('should have FEATURES with all expected keys true by default', async () => {
    const mod = await import('./config');
    expect(mod.FEATURES).toMatchObject({
      AI_ASSISTANT: true,
      WEBRTC_CALLS: true,
      FILE_SHARING: true,
      PUSH_NOTIFICATIONS: true,
      DARK_MODE: true,
      MULTI_LANGUAGE: true,
      PWA: true,
      PAYMENTS: true,
      SERVICES: true,
      LOCATION: true,
      BROADCAST: true,
      VIDEO_CONFERENCE: true,
      INTEGRATION: true,
      ENCRYPTION: true,
      MARKETPLACE: true,
      VOICE_MAIL: true,
      CONTENT_MODERATION: true,
      EVENTS: true,
      ECOSYSTEM_WALLET: true,
    });
  });

  it('should have ENDPOINTS with correct structure', async () => {
    const mod = await import('./config');
    expect(mod.ENDPOINTS).toHaveProperty('AUTH.LOGIN');
    expect(mod.ENDPOINTS).toHaveProperty('MESSAGES.LIST');
    expect(mod.ENDPOINTS).toHaveProperty('AI.SMART_REPLIES');
  });

  it('should detect production mode when MODE=production', async () => {
    process.env.MODE = 'production';
    const mod = await import('./config');
    expect(mod.isProduction).toBe(true);
    expect(mod.isDevelopment).toBe(false);
  });

  it('should detect development mode when MODE=development', async () => {
    process.env.MODE = 'development';
    const mod = await import('./config');
    expect(mod.isDevelopment).toBe(true);
    expect(mod.isProduction).toBe(false);
  });
});
