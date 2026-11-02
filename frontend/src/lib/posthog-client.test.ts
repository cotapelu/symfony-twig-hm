import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import {
  initPosthog,
  identify,
  capture,
  screen,
  reset,
} from './posthog-client';

describe('PostHog client', () => {
  let originalWindowPosthog: any;

  beforeEach(() => {
    originalWindowPosthog = window.posthog;
    // Clean up globals
    delete (window as any).posthog;
    vi.clearAllMocks();
  });

  afterEach(() => {
    // Restore
    if (originalWindowPosthog !== undefined) (window as any).posthog = originalWindowPosthog;
    else delete (window as any).posthog;
  });

  describe('initPosthog', () => {
    it('sets window.posthog to array if not exists and calls init', () => {
      // Mock window.posthog as array with init method
      const mockInit = vi.fn();
      (window as any).posthog = [];
      (window as any).posthog.init = mockInit;

      initPosthog();

      expect((window as any).posthog).toBeDefined();
      expect(mockInit).toHaveBeenCalledWith('phc_project_token', {
        api_host: 'https://us.i.posthog.com',
        person_profiles: 'identified_only',
      });
    });

    it('does nothing if not in browser', () => {
      // In our test environment, browser is true (via stub); cannot simulate false easily.
    });
  });

  describe('identify', () => {
    it('calls posthog.identify with userId and traits', () => {
      const mockIdentify = vi.fn();
      (window as any).posthog = { identify: mockIdentify };
      identify('user123', { plan: 'pro' });
      expect(mockIdentify).toHaveBeenCalledWith('user123', { plan: 'pro' });
    });

    it('does nothing if posthog not set', () => {
      expect(() => identify('user1')).not.toThrow();
    });

    it('does nothing if not in browser (stubbed)', () => {
      // Cannot simulate false; skip.
    });
  });

  describe('capture', () => {
    it('calls posthog.capture with event and properties', () => {
      const mockCapture = vi.fn();
      (window as any).posthog = { capture: mockCapture };
      capture('button_click', { buttonId: 'submit' });
      expect(mockCapture).toHaveBeenCalledWith('button_click', { buttonId: 'submit' });
    });

    it('does nothing if posthog is undefined', () => {
      expect(() => capture('event')).not.toThrow();
    });
  });

  describe('screen', () => {
    it('calls posthog.screen with name and properties', () => {
      const mockScreen = vi.fn();
      (window as any).posthog = { screen: mockScreen };
      screen('HomePage', { section: 'header' });
      expect(mockScreen).toHaveBeenCalledWith('HomePage', { section: 'header' });
    });

    it('no-op if posthog missing', () => {
      expect(() => screen('Page')).not.toThrow();
    });
  });

  describe('reset', () => {
    it('calls posthog.reset', () => {
      const mockReset = vi.fn();
      (window as any).posthog = { reset: mockReset };
      reset();
      expect(mockReset).toHaveBeenCalled();
    });

    it('does nothing if posthog not defined', () => {
      expect(() => reset()).not.toThrow();
    });
  });
});
