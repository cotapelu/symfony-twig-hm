import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import Notification from './Notification.svelte';

describe('Notification Component', () => {
  const defaultProps = {
    id: 'n1',
    title: 'Notification Title',
    message: 'This is a notification message',
    type: 'info' as const,
    dismissible: true
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders title and message', () => {
    render(Notification, { props: defaultProps });
    expect(screen.getByText('Notification Title')).toBeInTheDocument();
    expect(screen.getByText('This is a notification message')).toBeInTheDocument();
  });

  it('shows dismiss button when dismissible', () => {
    render(Notification, { props: defaultProps });
    expect(screen.getByLabelText('Close notification')).toBeInTheDocument();
  });

  it('hides dismiss button when dismissible=false', () => {
    render(Notification, { props: { ...defaultProps, dismissible: false } });
    expect(screen.queryByLabelText('Dismiss')).not.toBeInTheDocument();
  });

  it('dispatches dismiss event when dismiss button clicked', async () => {
    const handleDismiss = vi.fn();
    render(Notification, { props: { ...defaultProps, onDismiss: handleDismiss } });
    const dismissBtn = screen.getByLabelText('Close notification');
    await fireEvent.click(dismissBtn);
    expect(handleDismiss).toHaveBeenCalledWith('n1');
  });

  it('displays action button when actionText provided', () => {
    render(Notification, { props: { ...defaultProps, actionText: 'Undo' } });
    expect(screen.getByRole('button', { name: 'Undo' })).toBeInTheDocument();
  });

  it('dispatches action event when action button clicked', async () => {
    const handleAction = vi.fn();
    render(Notification, { props: { ...defaultProps, actionText: 'Undo', onAction: handleAction } });
    const actionBtn = screen.getByRole('button', { name: 'Undo' });
    await fireEvent.click(actionBtn);
    expect(handleAction).toHaveBeenCalled();
  });

  it('applies correct type class for success', () => {
    render(Notification, { props: { ...defaultProps, type: 'success' } });
    const container = screen.getByText('Notification Title').closest('.notification') || screen.getByText('This is a notification message').closest('.notification');
    expect(container).toHaveClass('notification-success');
  });
});
