import { render, within, fireEvent, waitFor } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import Toast from './Toast.svelte';
import ToastContainer from './ToastContainer.svelte';

// Button and Typography are pure presentational atoms with no external deps, and
// Button forwards $$restProps — the real ones are used so aria-label is exercised.
//
// Svelte 5 legacy `createEventDispatcher` events are compiled component events,
// not DOM CustomEvents, so dismiss is asserted through the production harness
// (ToastContainer's on:dismiss -> removeToast) rather than a DOM listener.

const { mockToasts, mockRemoveToast } = vi.hoisted(() => ({
  mockToasts: { value: [] as Array<Record<string, unknown>> },
  mockRemoveToast: vi.fn(),
}));

vi.mock('$lib/stores/global', () => ({
  toasts: {
    subscribe: (fn: (value: unknown) => void) => {
      fn(mockToasts.value);
      return () => {};
    },
  },
  removeToast: mockRemoveToast,
}));

function toastIn(container: HTMLElement) {
  return container.querySelector('.toast-notification') as HTMLElement;
}

/** Render a standalone toast, waiting for onMount to flip `mounted`. */
async function renderToast(props: Record<string, unknown>) {
  const result = render(Toast, { props });
  await waitFor(() => expect(toastIn(result.container)).toBeInTheDocument());
  return result;
}

describe('Toast Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockToasts.value = [];
  });

  describe('rendering', () => {
    it('renders without error when mounted', async () => {
      const { container } = await renderToast({
        id: 'toast-1',
        type: 'info',
        message: 'Test message',
        duration: 5000,
      });
      expect(toastIn(container)).toBeInTheDocument();
    });

    it('renders with info type by default', async () => {
      const { container } = await renderToast({ message: 'Info message' });
      expect(toastIn(container)).toHaveClass('info');
    });

    it('applies the type value as a class', async () => {
      for (const type of ['success', 'error', 'warning', 'info']) {
        const { container, unmount } = await renderToast({ type, message: 'Test' });
        expect(toastIn(container)).toHaveClass(type);
        // A stray literal `type` class means class:type shorthand crept back in
        expect(toastIn(container)).not.toHaveClass('type');
        unmount();
      }
    });

    it('renders icon based on type', async () => {
      const cases = [
        { type: 'success', icon: '✅' },
        { type: 'error', icon: '❌' },
        { type: 'warning', icon: '⚠️' },
        { type: 'info', icon: 'ℹ️' },
      ] as const;

      for (const { type, icon } of cases) {
        const { container, unmount } = await renderToast({ type, message: 'm' });
        expect(within(container).getByText(icon)).toBeInTheDocument();
        unmount();
      }
    });
  });

  describe('props handling', () => {
    it('renders title', async () => {
      const { container } = await renderToast({ title: 'Test Title', message: 'Test message' });
      expect(within(container).getByText('Test Title')).toBeInTheDocument();
    });

    it('renders message', async () => {
      const { container } = await renderToast({ message: 'Hello world' });
      expect(within(container).getByText('Hello world')).toBeInTheDocument();
    });

    it('omits the title element when title is empty', async () => {
      const { container } = await renderToast({ title: '', message: 'Test' });
      expect(container.querySelector('.toast-title')).not.toBeInTheDocument();
      expect(within(container).getByText('Test')).toBeInTheDocument();
    });

    it('renders title with no message', async () => {
      const { container } = await renderToast({ title: 'Title', message: '' });
      expect(within(container).getByText('Title')).toBeInTheDocument();
    });

    it('shows close button by default', async () => {
      const { container } = await renderToast({ message: 'Test' });
      expect(within(container).getByRole('button', { name: 'Close notification' })).toBeInTheDocument();
    });

    it('hides close button when showCloseButton is false', async () => {
      const { container } = await renderToast({ message: 'Test', showCloseButton: false });
      expect(
        within(container).queryByRole('button', { name: 'Close notification' })
      ).not.toBeInTheDocument();
    });

    it('renders progress bar with the given duration', async () => {
      const { container } = await renderToast({ message: 'Test', duration: 10000 });
      const progress = container.querySelector('.toast-progress');
      expect(progress).toBeInTheDocument();
      expect(progress).toHaveStyle({ 'animation-duration': '10000ms' });
    });

    it('hides progress bar when persistent is true', async () => {
      const { container } = await renderToast({ message: 'Test', persistent: true, duration: 5000 });
      expect(container.querySelector('.toast-progress')).not.toBeInTheDocument();
    });

    it('hides progress bar when duration is 0', async () => {
      const { container } = await renderToast({ message: 'Test', duration: 0 });
      expect(container.querySelector('.toast-progress')).not.toBeInTheDocument();
    });
  });

  describe('accessibility', () => {
    it('has role alert', async () => {
      const { container } = await renderToast({ message: 'Test' });
      expect(within(container).getByRole('alert')).toBeInTheDocument();
    });

    it('has aria-live assertive', async () => {
      const { container } = await renderToast({ message: 'Test' });
      expect(within(container).getByRole('alert')).toHaveAttribute('aria-live', 'assertive');
    });

    it('close button has aria-label', async () => {
      const { container } = await renderToast({ message: 'Test' });
      expect(within(container).getByRole('button', { name: 'Close notification' })).toBeInTheDocument();
    });
  });

  describe('styling', () => {
    it.each([['success'], ['error'], ['warning'], ['info']])(
      'applies correct background for %s type',
      async (type) => {
        const { container } = await renderToast({ type, message: 'm' });
        expect(toastIn(container)).toHaveClass(type);
      }
    );

    it('applies correct icon for each type', async () => {
      const cases = [
        ['success', '✅'],
        ['error', '❌'],
        ['warning', '⚠️'],
        ['info', 'ℹ️'],
      ] as const;

      for (const [type, icon] of cases) {
        const { container, unmount } = await renderToast({ type, message: 'm' });
        expect(within(container).getByText(icon)).toBeInTheDocument();
        unmount();
      }
    });

    it('applies cyberpunk theme styles when data-theme is cyberpunk', async () => {
      document.documentElement.setAttribute('data-theme', 'cyberpunk');
      try {
        const { container } = await renderToast({ message: 'Test' });
        expect(toastIn(container)).toBeInTheDocument();
      } finally {
        document.documentElement.removeAttribute('data-theme');
      }
    });
  });

  describe('edge cases', () => {
    it('handles undefined title gracefully', async () => {
      const { container } = await renderToast({ message: 'Test', title: undefined });
      expect(within(container).getByText('Test')).toBeInTheDocument();
    });

    it('handles undefined message gracefully', async () => {
      const { container } = await renderToast({ title: 'Title', message: undefined });
      expect(within(container).getByText('Title')).toBeInTheDocument();
    });
  });

  describe('dismiss and timer behaviour (via ToastContainer)', () => {
    it('removes the toast from the store on close click', async () => {
      mockToasts.value = [{ id: 'toast-9', message: 'Bye', type: 'info', title: '' }];

      const { container } = render(ToastContainer);
      await waitFor(() => expect(toastIn(container)).toBeInTheDocument());

      await fireEvent.click(within(container).getByRole('button', { name: 'Close notification' }));

      expect(mockRemoveToast).toHaveBeenCalledWith('toast-9');
    });

    it('auto-dismisses after the default 5000ms duration', async () => {
      vi.useFakeTimers();
      try {
        // ToastContainer does not forward duration/persistent (the store's Toast
        // type has neither), so production always uses the 5000ms default.
        mockToasts.value = [{ id: 'toast-10', message: 'Bye', type: 'info', title: '' }];

        const { container } = render(ToastContainer);
        await waitFor(() => expect(toastIn(container)).toBeInTheDocument());

        vi.advanceTimersByTime(4999);
        expect(mockRemoveToast).not.toHaveBeenCalled();

        vi.advanceTimersByTime(1);
        expect(mockRemoveToast).toHaveBeenCalledWith('toast-10');
      } finally {
        vi.useRealTimers();
      }
    });

    it('renders each toast independently', async () => {
      mockToasts.value = [
        { id: 'a', message: 'Toast A', type: 'info', title: '' },
        { id: 'b', message: 'Toast B', type: 'error', title: '' },
      ];

      const { container } = render(ToastContainer);
      await waitFor(() => expect(container.querySelectorAll('.toast-notification')).toHaveLength(2));

      expect(within(container).getByText('Toast A')).toBeInTheDocument();
      expect(within(container).getByText('Toast B')).toBeInTheDocument();
    });
  });
});
