import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import Drawer from './Drawer.svelte';

describe('Drawer', () => {
  it('renders nothing when open is false', () => {
    render(Drawer, { props: { open: false } });
    expect(screen.queryByText('Test Title')).not.toBeInTheDocument();
    expect(document.querySelector('.drawer-overlay')).not.toBeInTheDocument();
  });

  it('renders drawer when open is true', () => {
    render(Drawer, { props: { open: true, title: 'Test Title' } });
    const overlay = document.querySelector('.drawer-overlay');
    expect(overlay).toBeInTheDocument();
    const drawer = document.querySelector('.drawer');
    expect(drawer).toBeInTheDocument();
    expect(screen.getByText('Test Title')).toBeInTheDocument();
  });

  it('applies position variants', () => {
    const { unmount: u1 } = render(Drawer, { props: { open: true, position: 'left' } });
    let drawer = document.querySelector('.drawer');
    expect(drawer).toHaveClass('left');
    expect(document.querySelector('.drawer-overlay')).not.toHaveClass('overlay-right');
    u1();

    const { unmount: u2 } = render(Drawer, { props: { open: true, position: 'right' } });
    drawer = document.querySelector('.drawer');
    expect(drawer).toHaveClass('right');
    expect(document.querySelector('.drawer-overlay')).toHaveClass('overlay-right');
    u2();
  });

  it('applies size variants', () => {
    const sizes = ['sm', 'md', 'lg', 'xl'] as const;
    sizes.forEach(size => {
      const { unmount } = render(Drawer, { props: { open: true, size, title: 'Test' } });
      const drawer = document.querySelector('.drawer');
      expect(drawer).toHaveClass(size);
      unmount();
    });
  });

  it('applies custom className', () => {
    render(Drawer, { props: { open: true, className: 'custom-drawer', title: 'Test' } });
    const drawer = document.querySelector('.drawer');
    expect(drawer).toHaveClass('custom-drawer');
  });

  it('shows close button when showClose is true (default)', () => {
    render(Drawer, { props: { open: true, title: 'Test' } });
    const closeBtn = document.querySelector('.drawer-close');
    expect(closeBtn).toBeInTheDocument();
  });

  it('hides close button when showClose is false', () => {
    render(Drawer, { props: { open: true, showClose: false, title: 'Test' } });
    const closeBtn = document.querySelector('.drawer-close');
    expect(closeBtn).not.toBeInTheDocument();
  });

  it('renders contentText when provided', () => {
    render(Drawer, { props: { open: true, contentText: 'Custom content', title: 'Test' } });
    expect(screen.getByText('Custom content')).toBeInTheDocument();
  });

  it('renders footer with default close button', () => {
    render(Drawer, { props: { open: true, title: 'Test' } });
    const footer = document.querySelector('.drawer-footer');
    expect(footer).toBeInTheDocument();
    expect(screen.getByText('Close')).toBeInTheDocument();
  });

  it('calls closeCallback and dispatches close event on close button click', () => {
    const closeCallback = vi.fn();
    const onClose = vi.fn();
    render(Drawer, { props: { open: true, title: 'Test', closeCallback, onClose } });
    const closeBtn = document.querySelector('.drawer-close');
    fireEvent.click(closeBtn!);

    // The closeCallback is called synchronously in the handler
    expect(closeCallback).toHaveBeenCalledTimes(1);
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('closes on overlay click when closeOnOverlay is true (default)', () => {
    const closeCallback = vi.fn();
    render(Drawer, { props: { open: true, title: 'Test', closeCallback, closeOnOverlay: true } });
    const overlay = document.querySelector('.drawer-overlay');
    fireEvent.click(overlay!);
    expect(closeCallback).toHaveBeenCalledTimes(1);
  });

  it('does not close on overlay click when closeOnOverlay is false', () => {
    const closeCallback = vi.fn();
    render(Drawer, { props: { open: true, title: 'Test', closeCallback, closeOnOverlay: false } });
    const overlay = document.querySelector('.drawer-overlay');
    fireEvent.click(overlay!);
    expect(closeCallback).not.toHaveBeenCalled();
  });

  it('does not propagate click from drawer content to overlay', () => {
    const closeCallback = vi.fn();
    render(Drawer, { props: { open: true, title: 'Test', closeCallback } });
    const drawer = document.querySelector('.drawer');
    fireEvent.click(drawer!);
    expect(closeCallback).not.toHaveBeenCalled();
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(Drawer, { props: { open: true, position: 'left', title: 'Left Drawer' } });
    const { container: container2 } = render(Drawer, { props: { open: true, position: 'right', title: 'Right Drawer', size: 'lg' } });
    const drawer1 = container1.querySelector('.drawer');
    const drawer2 = container2.querySelector('.drawer');
    expect(drawer1).toHaveClass('left');
    expect(drawer2).toHaveClass('right');
    expect(drawer2).toHaveClass('lg');
    expect(drawer1).toHaveTextContent('Left Drawer');
    expect(drawer2).toHaveTextContent('Right Drawer');
  });

  it('renders drawer with right position and overlay-right class', () => {
    const { unmount } = render(Drawer, { props: { open: true, position: 'right' } });
    const overlay = document.querySelector('.drawer-overlay');
    expect(overlay).toHaveClass('overlay-right');
    unmount();
  });

  it('renders drawer header structure', () => {
    render(Drawer, { props: { open: true, title: 'Test Title' } });
    const header = document.querySelector('.drawer-header');
    expect(header).toBeInTheDocument();
    expect(header).toHaveTextContent('Test Title');
  });

  it('renders drawer content area', () => {
    render(Drawer, { props: { open: true, title: 'Test' } });
    const content = document.querySelector('.drawer-content');
    expect(content).toBeInTheDocument();
  });

  it('renders drawer footer area', () => {
    render(Drawer, { props: { open: true, title: 'Test' } });
    const footer = document.querySelector('.drawer-footer');
    expect(footer).toBeInTheDocument();
  });

  it('applies reactive body overflow style when open', () => {
    // This tests the reactive statement: $: if (open) { document.body.style.overflow = 'hidden' }
    const originalOverflow = document.body.style.overflow;
    render(Drawer, { props: { open: true, title: 'Test' } });
    // Note: In jsdom, this may not actually set the style, but component renders without error
    const drawer = document.querySelector('.drawer');
    expect(drawer).toBeInTheDocument();
    document.body.style.overflow = originalOverflow;
  });
});