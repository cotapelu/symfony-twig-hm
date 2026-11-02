import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render } from '@testing-library/svelte';
import StatusBar from './StatusBar.svelte';

describe('StatusBar', () => {
  beforeEach(() => {
    // Clean up any meta theme-color added by previous tests
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.remove();
  });

  it('renders with default props', () => {
    const { container } = render(StatusBar);
    const statusBar = container.querySelector('.status-bar');
    expect(statusBar).toBeInTheDocument();
  });

  it('renders nothing when visible is false', () => {
    render(StatusBar, { props: { visible: false } });
    const statusBar = document.querySelector('.status-bar');
    expect(statusBar).not.toBeInTheDocument();
  });

  it('applies custom backgroundColor (converted to rgb)', () => {
    const { container } = render(StatusBar, { props: { backgroundColor: '#ff0000' } });
    const statusBar = container.querySelector('.status-bar');
    expect(statusBar).toHaveAttribute('style', expect.stringContaining('rgb(255, 0, 0)'));
  });

  it('applies custom textColor (converted to rgb)', () => {
    const { container } = render(StatusBar, { props: { textColor: '#00ff00' } });
    const statusBar = container.querySelector('.status-bar');
    expect(statusBar).toHaveAttribute('style', expect.stringContaining('rgb(0, 255, 0)'));
  });

  it('applies custom height', () => {
    const { container } = render(StatusBar, { props: { height: 48 } });
    const statusBar = container.querySelector('.status-bar');
    expect(statusBar).toHaveAttribute('style', expect.stringContaining('height: 48px'));
  });

  it('applies translucent class when translucent is true', () => {
    const { container } = render(StatusBar, { props: { translucent: true } });
    const statusBar = container.querySelector('.status-bar');
    expect(statusBar).toHaveClass('status-bar-translucent');
  });

  it('applies custom className', () => {
    const { container } = render(StatusBar, { props: { className: 'custom-status-bar' } });
    const statusBar = container.querySelector('.status-bar');
    expect(statusBar).toHaveClass('custom-status-bar');
  });

  it('sets meta theme-color on mount', () => {
    render(StatusBar, { props: { backgroundColor: '#123456' } });
    const meta = document.querySelector('meta[name="theme-color"]');
    expect(meta).toBeInTheDocument();
    expect(meta).toHaveAttribute('content', '#123456');
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(StatusBar, { props: { backgroundColor: '#111111' } });
    const { container: container2 } = render(StatusBar, { props: { backgroundColor: '#222222', height: 32 } });
    const bar1 = container1.querySelector('.status-bar');
    const bar2 = container2.querySelector('.status-bar');
    expect(bar1).toHaveAttribute('style', expect.stringContaining('rgb(17, 17, 17)'));
    expect(bar2).toHaveAttribute('style', expect.stringContaining('rgb(34, 34, 34)'));
    expect(bar2).toHaveAttribute('style', expect.stringContaining('height: 32px'));
  });

  it('handles translucent with backgroundColor', () => {
    const { container } = render(StatusBar, { props: { translucent: true, backgroundColor: '#3b82f6' } });
    const statusBar = container.querySelector('.status-bar');
    expect(statusBar).toHaveClass('status-bar-translucent');
    expect(statusBar).toHaveAttribute('style', expect.stringContaining('rgb(59, 130, 246)'));
  });

  it('renders with default backgroundColor when not provided', () => {
    const { container } = render(StatusBar);
    const statusBar = container.querySelector('.status-bar');
    expect(statusBar).toHaveAttribute('style', expect.stringContaining('background-color'));
  });

  it('renders with default height when not provided', () => {
    const { container } = render(StatusBar);
    const statusBar = container.querySelector('.status-bar');
    expect(statusBar).toHaveAttribute('style', expect.stringContaining('height: 24px'));
  });

  it('renders with default textColor when not provided', () => {
    const { container } = render(StatusBar);
    const statusBar = container.querySelector('.status-bar');
    expect(statusBar).toHaveAttribute('style', expect.stringContaining('color: white'));
  });

  it('handles component mount/unmount without error', () => {
    const { unmount } = render(StatusBar);
    expect(() => unmount()).not.toThrow();
  });

  it('re-renders without error after unmount', () => {
    const { container: c1, unmount: u1 } = render(StatusBar);
    u1();
    const { container: c2 } = render(StatusBar);
    expect(c2).toBeInTheDocument();
  });
});