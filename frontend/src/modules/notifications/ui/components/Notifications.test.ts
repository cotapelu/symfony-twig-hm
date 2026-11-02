import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { render, screen, act, cleanup } from '@testing-library/svelte';
import Notifications from './Notifications.svelte';
import { notificationsStore } from '../../state/notificationsStore';

/**
 * Store-driven dropdown. Driven via the real notificationsStore
 * (reset()/addNotification()); the service is side-effect-free at construction,
 * so no network happens. Covers empty state, list rendering, per-type icons,
 * unread badge/row-styling, getTimeAgo branches, maxItems truncation, and
 * showViewAll toggle.
 */
function makeNotif(overrides: Record<string, unknown> = {}) {
  return {
    id: 'n1',
    type: 'message',
    title: 'Hello',
    message: 'World',
    read: false,
    createdAt: new Date().toISOString(),
    ...overrides,
  } as any;
}

async function seed(items: any[]) {
  await act(() => {
    notificationsStore.reset();
    items.forEach((i) => notificationsStore.addNotification(i));
  });
}

beforeEach(() => notificationsStore.reset());
afterEach(() => {
  notificationsStore.reset();
  cleanup();
});

describe('Notifications', () => {
  describe('empty state', () => {
    it('shows the empty message when there are no notifications', () => {
      render(Notifications, { props: {} });
      expect(screen.getByText('No new notifications')).toBeInTheDocument();
    });

    it('hides the unread badge and Mark-all-read for 0 unread', () => {
      render(Notifications, { props: {} });
      expect(screen.queryByRole('button', { name: 'Mark all read' })).not.toBeInTheDocument();
    });
  });

  describe('list rendering', () => {
    it('renders notification title, message, and type icon', async () => {
      render(Notifications, { props: {} });
      await seed([makeNotif({ id: 'a', type: 'message', title: 'New message', message: 'You have 1 reply' })]);
      expect(screen.getByText('New message')).toBeInTheDocument();
      expect(screen.getByText('You have 1 reply')).toBeInTheDocument();
      expect(screen.getByText('💬')).toBeInTheDocument();
    });

    it('maps each notification type to its icon (incl. default)', async () => {
      const map: Record<string, string> = {
        message: '💬', call: '📞', payment: '💳', system: '⚙️',
        social: '👥', security: '🔒', other: '🔔',
      };
      render(Notifications, { props: {} });
      for (const [type, icon] of Object.entries(map)) {
        await seed([makeNotif({ id: 'x-' + type, type, title: 'T ' + type })]);
        expect(screen.getByText(icon)).toBeInTheDocument();
        await seed([]);
      }
    });
  });

  describe('unread badge + row styling', () => {
    it('shows the unread count badge and Mark-all-read when unread > 0', async () => {
      render(Notifications, { props: {} });
      await seed([makeNotif({ id: 'a' }), makeNotif({ id: 'b' })]);
      expect(screen.getByText('2')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: 'Mark all read' })).toBeInTheDocument();
    });

    it('applies the unread row class + unread dot for unread, and not for read', async () => {
      render(Notifications, { props: {} });
      await seed([makeNotif({ id: 'u', read: false, title: 'Unread one' })]);
      expect(screen.getByText('Unread one').closest('.p-3')).toHaveClass('bg-blue-50');
      expect(document.querySelector('.bg-blue-500')).toBeInTheDocument();

      await seed([makeNotif({ id: 'r', read: true, title: 'Read one' })]);
      expect(screen.getByText('Read one').closest('.p-3')).not.toHaveClass('bg-blue-50');
      expect(document.querySelector('.bg-blue-500')).not.toBeInTheDocument();
    });
  });

  describe('getTimeAgo', () => {
    it("shows 'Just now' for < 1 minute", async () => {
      render(Notifications, { props: {} });
      await seed([makeNotif({ createdAt: new Date(Date.now()).toISOString() })]);
      expect(screen.getByText('Just now')).toBeInTheDocument();
    });

    it('shows minutes for < 1 hour', async () => {
      render(Notifications, { props: {} });
      await seed([makeNotif({ createdAt: new Date(Date.now() - 5 * 60000).toISOString() })]);
      expect(screen.getByText('5m')).toBeInTheDocument();
    });

    it('shows hours for < 1 day', async () => {
      render(Notifications, { props: {} });
      await seed([makeNotif({ createdAt: new Date(Date.now() - 2 * 3600000).toISOString() })]);
      expect(screen.getByText('2h')).toBeInTheDocument();
    });

    it('shows days for >= 1 day', async () => {
      render(Notifications, { props: {} });
      await seed([makeNotif({ createdAt: new Date(Date.now() - 3 * 86400000).toISOString() })]);
      expect(screen.getByText('3d')).toBeInTheDocument();
    });
  });

  describe('maxItems + showViewAll', () => {
    it('truncates the rendered list to maxItems but counts all unread', async () => {
      render(Notifications, { props: { maxItems: 2 } });
      await seed([
        makeNotif({ id: 'a', title: 'First' }),
        makeNotif({ id: 'b', title: 'Second' }),
        makeNotif({ id: 'c', title: 'Third' }),
      ]);
      // addNotification prepends (newest-first); slice(0,2) keeps the 2 newest.
      expect(screen.getByText('Third')).toBeInTheDocument();
      expect(screen.getByText('Second')).toBeInTheDocument();
      expect(screen.queryByText('First')).not.toBeInTheDocument();
      expect(screen.getByText('3')).toBeInTheDocument(); // badge counts all 3 unread
    });

    it('renders the View-all link by default and hides it when showViewAll is false', () => {
      const { unmount } = render(Notifications, { props: {} });
      expect(screen.getByRole('link', { name: 'View all notifications' })).toBeInTheDocument();
      unmount();
      cleanup();
      render(Notifications, { props: { showViewAll: false } });
      expect(screen.queryByRole('link', { name: 'View all notifications' })).not.toBeInTheDocument();
    });
  });
});
