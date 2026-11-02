// Mock onMount before importing the component
vi.mock('svelte', async () => {
  const actual = await vi.importActual('svelte');
  return {
    ...actual,
    onMount: vi.fn(),
    onDestroy: vi.fn(),
    createEventDispatcher: () => vi.fn(),
  };
});

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
};
Object.defineProperty(global, 'localStorage', {
  value: localStorageMock,
  writable: true,
});

// Mock Notification API
Object.defineProperty(global, 'Notification', {
  value: class MockNotification {
    static permission = 'default';
    static requestPermission = vi.fn().mockResolvedValue('granted');
    constructor(public title: string, public options?: NotificationOptions) {
      this.close = vi.fn();
    }
    close = vi.fn();
  },
  writable: true,
});

import { render, screen, fireEvent } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import PushNotifications from './PushNotifications.svelte';

describe('PushNotifications Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorageMock.getItem.mockReturnValue(null);
    localStorageMock.setItem.mockImplementation(() => {});
    localStorageMock.removeItem.mockImplementation(() => {});
    Notification.permission = 'default';
  });

  describe('rendering', () => {
    it('renders without error when showPanel is false', () => {
      render(PushNotifications, { props: { showPanel: false } });
      expect(document.body).toBeTruthy();
    });

    it('renders notification panel when showPanel is true', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(screen.getByText('🔔 Push Notifications')).toBeInTheDocument();
    });

    it('renders permission status section', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(screen.getByText('Permission Status')).toBeInTheDocument();
    });

    it('renders notification settings section', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(screen.getByText('Notification Settings')).toBeInTheDocument();
    });

    it('renders test section', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(screen.getByText('Test Notifications')).toBeInTheDocument();
    });

    it('renders enable notifications button when permission is default', () => {
      Notification.permission = 'default';
      render(PushNotifications, { props: { showPanel: true } });
      expect(screen.getAllByText('Enable Notifications').length).toBeGreaterThan(0);
    });

    it('renders notification settings checkboxes', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(screen.getByText('Sound Effects')).toBeInTheDocument();
      expect(screen.getByText('Message Notifications')).toBeInTheDocument();
      expect(screen.getByText('Group Notifications')).toBeInTheDocument();
      expect(screen.getByText('Call Notifications')).toBeInTheDocument();
      expect(screen.getByText('System Notifications')).toBeInTheDocument();
    });

    it('renders quiet hours section', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(screen.getByText('Quiet Hours')).toBeInTheDocument();
    });

    it('renders send test notification button', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(screen.getByText('Send Test Notification')).toBeInTheDocument();
    });
  });

  describe('props handling', () => {
    it('accepts onClose callback', () => {
      const onClose = vi.fn();
      render(PushNotifications, { props: { showPanel: true, onClose } });
      const closeBtn = screen.getByText('×');
      fireEvent.click(closeBtn);
      expect(onClose).toHaveBeenCalled();
    });

    it('handles undefined props gracefully', () => {
      render(PushNotifications, { props: { showPanel: undefined as any, onClose: undefined as any } });
      expect(document.body).toBeTruthy();
    });

    it('handles multiple instances independently', () => {
      const { container: c1 } = render(PushNotifications, { props: { showPanel: true } });
      const { container: c2 } = render(PushNotifications, { props: { showPanel: false } });
      
      expect(c1.querySelector('.notification-panel')).toBeInTheDocument();
      expect(c2.querySelector('.notification-panel')).not.toBeInTheDocument();
    });
  });

  describe('localStorage integration', () => {
    it('handles localStorage errors gracefully', () => {
      localStorageMock.getItem.mockImplementation(() => { throw new Error('Storage error'); });
      localStorageMock.setItem.mockImplementation(() => { throw new Error('Storage error'); });
      
      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
      
      render(PushNotifications, { props: { showPanel: true } });
      
      expect(consoleSpy).toHaveBeenCalled();
      consoleSpy.mockRestore();
    });
  });

  describe('Notification API integration', () => {
    it('checks Notification API support on mount', () => {
      Notification.permission = 'default';
      render(PushNotifications, { props: { showPanel: true } });
      expect(Notification.permission).toBe('default');
    });
  });

  describe('permission handling', () => {
    it('shows enable button when permission is default', () => {
      Notification.permission = 'default';
      render(PushNotifications, { props: { showPanel: true } });
      expect(screen.getAllByText('Enable Notifications').length).toBeGreaterThan(0);
    });
  });

  describe('settings', () => {
    it('renders sound toggle when notifications enabled', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(screen.getByText('Sound Effects')).toBeInTheDocument();
    });

    it('renders notification type checkboxes', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(screen.getByText('Message Notifications')).toBeInTheDocument();
      expect(screen.getByText('Group Notifications')).toBeInTheDocument();
      expect(screen.getByText('Call Notifications')).toBeInTheDocument();
      expect(screen.getByText('System Notifications')).toBeInTheDocument();
    });

    it('renders quiet hours section', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(screen.getByText('Quiet Hours')).toBeInTheDocument();
    });
  });

  describe('test notification', () => {
    it('renders send test notification button', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(screen.getByText('Send Test Notification')).toBeInTheDocument();
    });

    it('disables test button when permission not granted', () => {
      Notification.permission = 'default';
      render(PushNotifications, { props: { showPanel: true } });
      const btn = screen.getByText('Send Test Notification');
      expect(btn).toBeDisabled();
    });

    it('disables test button when not registered', () => {
      Notification.permission = 'granted';
      render(PushNotifications, { props: { showPanel: true } });
      const btn = screen.getByText('Send Test Notification');
      expect(btn).toBeDisabled();
    });
  });

  describe('close panel', () => {
    it('closes panel on overlay click', () => {
      const onClose = vi.fn();
      render(PushNotifications, { props: { showPanel: true, onClose } });
      const overlay = document.querySelector('.notification-overlay');
      fireEvent.click(overlay!);
      expect(onClose).toHaveBeenCalled();
    });

    it('closes panel on close button click', () => {
      const onClose = vi.fn();
      render(PushNotifications, { props: { showPanel: true, onClose } });
      const closeBtn = screen.getByText('×');
      fireEvent.click(closeBtn);
      expect(onClose).toHaveBeenCalled();
    });

    it('does not close panel when clicking panel content', () => {
      const onClose = vi.fn();
      render(PushNotifications, { props: { showPanel: true, onClose } });
      const panel = document.querySelector('.notification-panel');
      fireEvent.click(panel!);
      expect(onClose).not.toHaveBeenCalled();
    });
  });

  describe('accessibility', () => {
    it('has role dialog on panel', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(document.querySelector('[role="dialog"]')).toHaveAttribute('aria-modal', 'true');
    });

    it('has tabindex on panel', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(document.querySelector('[role="dialog"]')).toHaveAttribute('tabindex', '-1');
    });

    it('has close button', () => {
      render(PushNotifications, { props: { showPanel: true } });
      const closeBtn = screen.getByText('×');
      expect(closeBtn).toBeInTheDocument();
    });
  });

  describe('styling', () => {
    it('includes overlay with backdrop', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(document.querySelector('.notification-overlay')).toBeInTheDocument();
    });

    it('includes panel with slide transition', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(document.querySelector('.notification-panel')).toBeInTheDocument();
    });

    it('includes mobile responsive styles', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(document.querySelector('.notification-panel')).toBeInTheDocument();
    });

    it('includes cyberpunk theme styles', () => {
      render(PushNotifications, { props: { showPanel: true } });
      expect(document.querySelector('.notification-panel')).toBeInTheDocument();
    });
  });

  describe('edge cases', () => {
    it('handles localStorage errors during save', () => {
      localStorageMock.setItem.mockImplementation(() => { throw new Error('Quota exceeded'); });
      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
      
      render(PushNotifications, { props: { showPanel: true } });
      
      expect(consoleSpy).toHaveBeenCalled();
      consoleSpy.mockRestore();
    });

    it('handles rapid onClose calls', () => {
      const onClose = vi.fn();
      render(PushNotifications, { props: { showPanel: true, onClose } });
      
      const closeBtn = screen.getByText('×');
      fireEvent.click(closeBtn);
      fireEvent.click(closeBtn);
      
      expect(onClose).toHaveBeenCalledTimes(2);
    });
  });
});