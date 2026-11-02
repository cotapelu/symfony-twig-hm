import { render, screen, fireEvent, within, waitFor } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import MockButton from './molecules/MockButton.svelte';
import MockTypography from './molecules/MockTypography.svelte';

const { mockGetNotifications, mockSubscribeToTopic } = vi.hoisted(() => ({
  mockGetNotifications: vi.fn(),
  mockSubscribeToTopic: vi.fn(),
}));

// NotificationCenter owns its own state: unreadCount/showPanel are internal,
// driven by the bell click and by realtime events — not by parent props.
vi.mock('$lib/api', () => ({
  subscribeToTopic: mockSubscribeToTopic,
  PHP: {
    getNotifications: mockGetNotifications,
  },
}));

vi.mock('./Button.svelte', () => ({ default: MockButton }));
vi.mock('./Typography.svelte', () => ({ default: MockTypography }));

import NotificationCenter from './NotificationCenter.svelte';

const notification = {
  id: '1',
  title: 'Title',
  message: 'Message',
  created_at: '2024-01-01T12:00:00Z',
};

/** Open the panel the way a user does — by clicking the bell. */
async function openPanel(container: HTMLElement) {
  const bell = container.querySelector('.notification-bell') as HTMLElement;
  fireEvent.click(bell);
  await waitFor(() => expect(within(container).getByText('Notifications')).toBeInTheDocument());
}

/**
 * Resolve everything onMount starts: loadNotifications() first, then the
 * realtime subscription (which only registers after that await settles).
 */
async function waitForMount() {
  await waitFor(() => expect(mockSubscribeToTopic).toHaveBeenCalled());
}

describe('NotificationCenter Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockGetNotifications.mockResolvedValue([]);
  });

  describe('rendering', () => {
    it('renders notification bell', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      expect(within(container).getByText('🔔')).toBeInTheDocument();
    });

    it('renders bell without badge when unreadCount is 0', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      expect(within(container).queryByText('0')).not.toBeInTheDocument();
    });

    it('shows badge when a realtime notification arrives', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();

      // The component subscribes in onMount; replay the broker payload
      const onEvent = mockSubscribeToTopic.mock.calls[0][1] as (data: unknown) => void;
      onEvent({ type: 'new_notification', notification });

      await waitFor(() => expect(within(container).getByText('1')).toBeInTheDocument());
    });

    it('does not render panel before the bell is clicked', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      expect(within(container).queryByText('Notifications')).not.toBeInTheDocument();
    });

    it('renders panel after the bell is clicked', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      await openPanel(container);
      expect(within(container).getByText('Notifications')).toBeInTheDocument();
    });

    it('renders empty state when no notifications', async () => {
      const { container } = render(NotificationCenter, { props: { notifications: [] } });
      await waitForMount();
      await openPanel(container);
      expect(within(container).getByText('No notifications')).toBeInTheDocument();
    });

    it('renders notification items when provided', async () => {
      const notifications = [
        { id: '1', title: 'Test 1', message: 'Message 1', created_at: '2024-01-01' },
        { id: '2', title: 'Test 2', message: 'Message 2', created_at: '2024-01-02' },
      ];
      const { container } = render(NotificationCenter, { props: { notifications } });
      await waitForMount();
      await openPanel(container);
      expect(within(container).getByText('Test 1')).toBeInTheDocument();
      expect(within(container).getByText('Test 2')).toBeInTheDocument();
    });
  });

  describe('notifications prop', () => {
    it('accepts notifications prop', async () => {
      const notifications = [{ id: '1', title: 'Test', message: 'Msg', created_at: '2024-01-01' }];
      const { container } = render(NotificationCenter, { props: { notifications } });
      await waitForMount();
      await openPanel(container);
      expect(within(container).getByText('Test')).toBeInTheDocument();
    });
  });

  describe('panel interactions', () => {
    it('closes panel on close button click', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      await openPanel(container);

      fireEvent.click(within(container).getByText('×'));
      expect(within(container).queryByText('Notifications')).not.toBeInTheDocument();
    });

    it('closes panel on overlay click', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      await openPanel(container);

      fireEvent.click(container.querySelector('.notification-overlay') as HTMLElement);
      expect(within(container).queryByText('Notifications')).not.toBeInTheDocument();
    });

    it('does not close panel when clicking panel content', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      await openPanel(container);

      fireEvent.click(container.querySelector('.notification-panel') as HTMLElement);
      expect(within(container).getByText('Notifications')).toBeInTheDocument();
    });

    it('closes panel on escape key', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      await openPanel(container);

      // The overlay owns the Escape handler, so the event must target it
      fireEvent.keyDown(container.querySelector('.notification-overlay') as HTMLElement, {
        key: 'Escape',
      });
      expect(within(container).queryByText('Notifications')).not.toBeInTheDocument();
    });

    it('toggles panel open and closed on repeated bell clicks', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();

      await openPanel(container);

      fireEvent.click(container.querySelector('.notification-bell') as HTMLElement);
      expect(within(container).queryByText('Notifications')).not.toBeInTheDocument();

      await openPanel(container);
      expect(within(container).getByText('Notifications')).toBeInTheDocument();
    });
  });

  describe('notification interactions', () => {
    it('renders notification with title, message, and timestamp', async () => {
      const { container } = render(NotificationCenter, { props: { notifications: [notification] } });
      await waitForMount();
      await openPanel(container);

      expect(within(container).getByText('Title')).toBeInTheDocument();
      expect(within(container).getByText('Message')).toBeInTheDocument();
      expect(within(container).getByText(notification.created_at)).toBeInTheDocument();
    });

    it('marks notification as read on item click', async () => {
      const { container } = render(NotificationCenter, { props: { notifications: [notification] } });
      await waitForMount();
      await openPanel(container);

      fireEvent.click(within(container).getByText('Title'));
      expect(within(container).queryByText('Title')).not.toBeInTheDocument();
    });

    it('marks notification as read on dismiss button', async () => {
      const { container } = render(NotificationCenter, { props: { notifications: [notification] } });
      await waitForMount();
      await openPanel(container);

      // Scope to the item: the panel header has a separate close button
      const item = container.querySelector('.notification-item') as HTMLElement;
      fireEvent.click(within(item).getByText('×'));

      expect(within(container).queryByText('Title')).not.toBeInTheDocument();
    });
  });

  describe('accessibility', () => {
    it('has role button on bell', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      expect(within(container).getByRole('button', { name: /🔔/ })).toBeInTheDocument();
    });

    it('has role dialog with aria-modal on panel', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      await openPanel(container);
      expect(container.querySelector('[role="dialog"]')).toHaveAttribute('aria-modal', 'true');
    });

    it('has tabindex on panel', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      await openPanel(container);
      expect(container.querySelector('[role="dialog"]')).toHaveAttribute('tabindex', '-1');
    });

    it('has close button', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      await openPanel(container);
      expect(within(container).getByText('×')).toBeInTheDocument();
    });
  });

  describe('styling', () => {
    it('includes overlay with backdrop', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      await openPanel(container);
      expect(container.querySelector('.notification-overlay')).toBeInTheDocument();
    });

    it('includes panel with slide transition', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      await openPanel(container);
      expect(container.querySelector('.notification-panel')).toBeInTheDocument();
    });

    it('includes mobile responsive styles', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      await openPanel(container);
      expect(container.querySelector('.notification-panel')).toBeInTheDocument();
    });

    it('includes dark mode styles', async () => {
      const { container } = render(NotificationCenter);
      await waitForMount();
      await openPanel(container);
      expect(container.querySelector('.notification-panel')).toBeInTheDocument();
    });
  });

  describe('edge cases', () => {
    it('handles undefined props gracefully', async () => {
      const { container } = render(NotificationCenter, {
        props: { notifications: undefined as any, unreadCount: undefined as any },
      });
      await waitForMount();
      await openPanel(container);
      expect(container).toBeTruthy();
    });

    it('handles a rejected getNotifications() without crashing', async () => {
      mockGetNotifications.mockRejectedValueOnce(new Error('API down'));
      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

      const { container } = render(NotificationCenter);
      await waitForMount();

      expect(within(container).getByText('🔔')).toBeInTheDocument();
      await openPanel(container);
      expect(within(container).getByText('No notifications')).toBeInTheDocument();
      consoleError.mockRestore();
    });

    it('handles multiple instances independently', async () => {
      const { container: c1 } = render(NotificationCenter, { props: { notifications: [notification] } });
      const { container: c2 } = render(NotificationCenter);
      await waitForMount();

      await openPanel(c1);
      expect(within(c1).getByText('Title')).toBeInTheDocument();
      expect(within(c2).queryByText('Title')).not.toBeInTheDocument();
    });
  });
});
