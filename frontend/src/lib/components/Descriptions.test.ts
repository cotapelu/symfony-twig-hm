import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import Descriptions from './Descriptions.svelte';

describe('Descriptions', () => {
  it('renders nothing when open is false', () => {
    render(Descriptions, { props: { open: false } });
    expect(screen.queryByText('Test Title')).not.toBeInTheDocument();
    expect(document.querySelector('.modal-overlay')).not.toBeInTheDocument();
  });

  it('renders modal when open is true', () => {
    render(Descriptions, { props: { open: true, title: 'Test Title', content: 'Test content' } });
    const overlay = document.querySelector('.modal-overlay');
    expect(overlay).toBeInTheDocument();
    expect(screen.getByText('Test Title')).toBeInTheDocument();
    expect(screen.getByText('Test content')).toBeInTheDocument();
  });

  it('applies type variants', () => {
    const types = ['info', 'success', 'warning', 'error'] as const;
    types.forEach(type => {
      const { unmount } = render(Descriptions, { props: { open: true, type, title: 'Test', content: 'Content' } });
      const modal = document.querySelector('.modal');
      expect(modal).toHaveClass(type);
      unmount();
    });
  });

  it('applies custom className', () => {
    render(Descriptions, { props: { open: true, className: 'custom-modal', title: 'Test', content: 'Content' } });
    const modal = document.querySelector('.modal');
    expect(modal).toHaveClass('custom-modal');
  });

  it('shows action button when showAction and actionText are set', () => {
    const onAction = vi.fn();
    render(Descriptions, {
      props: {
        open: true,
        title: 'Test',
        content: 'Content',
        showAction: true,
        actionText: 'Confirm',
        onAction,
      },
    });
    const actionBtn = screen.getByText('Confirm');
    expect(actionBtn).toBeInTheDocument();
    fireEvent.click(actionBtn);
    expect(onAction).toHaveBeenCalledTimes(1);
  });

  it('hides action button when showAction is false', () => {
    render(Descriptions, { props: { open: true, title: 'Test', content: 'Content', showAction: false } });
    expect(screen.queryByText('Confirm')).not.toBeInTheDocument();
  });

  it('hides action button when actionText is empty', () => {
    render(Descriptions, { props: { open: true, title: 'Test', content: 'Content', showAction: true, actionText: '' } });
    expect(screen.queryByText('Confirm')).not.toBeInTheDocument();
  });

  it('calls onClose when close button is clicked', () => {
    const onClose = vi.fn();
    render(Descriptions, { props: { open: true, title: 'Test', content: 'Content', onClose } });
    const closeBtn = document.querySelector('.modal-close');
    fireEvent.click(closeBtn!);
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('closes modal on overlay click', () => {
    const onClose = vi.fn();
    render(Descriptions, { props: { open: true, title: 'Test', content: 'Content', onClose } });
    const overlay = document.querySelector('.modal-overlay');
    fireEvent.click(overlay!);
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('does not close modal on modal content click (stopPropagation)', () => {
    const onClose = vi.fn();
    render(Descriptions, { props: { open: true, title: 'Test', content: 'Content', onClose } });
    const modal = document.querySelector('.modal');
    fireEvent.click(modal!);
    expect(onClose).not.toHaveBeenCalled();
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(Descriptions, { props: { open: true, title: 'Modal 1', content: 'Content 1' } });
    const { container: container2 } = render(Descriptions, { props: { open: true, title: 'Modal 2', content: 'Content 2', className: 'second-modal' } });
    const modal1 = container1.querySelector('.modal');
    const modal2 = container2.querySelector('.modal');
    expect(modal1).toHaveTextContent('Modal 1');
    expect(modal2).toHaveTextContent('Modal 2');
    expect(modal2).toHaveClass('second-modal');
  });

  it('renders with all four type variants', () => {
    const { unmount: u1 } = render(Descriptions, { props: { open: true, type: 'success', title: 'Success', content: 'Content' } });
    const { unmount: u2 } = render(Descriptions, { props: { open: true, type: 'warning', title: 'Warning', content: 'Content' } });
    const { unmount: u3 } = render(Descriptions, { props: { open: true, type: 'error', title: 'Error', content: 'Content' } });
    const { unmount: u4 } = render(Descriptions, { props: { open: true, type: 'info', title: 'Info', content: 'Content' } });
    u1(); u2(); u3(); u4();
  });

  it('renders title and content correctly', () => {
    render(Descriptions, { props: { open: true, title: 'My Title', content: 'My Content' } });
    expect(screen.getByText('My Title')).toBeInTheDocument();
    expect(screen.getByText('My Content')).toBeInTheDocument();
  });

  it('renders without content when content is empty string', () => {
    render(Descriptions, { props: { open: true, title: 'Test', content: '' } });
    expect(screen.getByText('Test')).toBeInTheDocument();
  });
});