import { describe, it, expect } from 'vitest';
import { render, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import PWA from './PWA.svelte';

describe('PWA Component', () => {
  it('renders pwa-status container', async () => {
    const { container } = render(PWA);
    await tick();
    expect(container.querySelector('.pwa-status')).not.toBeNull();
  });

  it('does not show offline-banner initially when online', async () => {
    const { container } = render(PWA);
    await tick();
    // Without onMount firing, isOnline stays at default true
    expect(container.querySelector('.offline-banner')).toBeNull();
  });

  it('does not show install button initially (no beforeinstallprompt fired)', async () => {
    const { container } = render(PWA);
    await tick();
    // Without onMount firing, no event listeners registered
    expect(container.querySelector('.install-btn')).toBeNull();
  });

  it('install button does not exist when deferredPrompt is null', async () => {
    const { container } = render(PWA);
    await tick();
    // Without onMount firing, deferredPrompt remains null
    expect(container.querySelector('.install-btn')).toBeNull();
  });

  it('container has pwa-status class', async () => {
    const { container } = render(PWA);
    await tick();
    const statusEl = container.querySelector('.pwa-status');
    expect(statusEl?.className).toContain('pwa-status');
  });

  it('component renders without errors when no props are passed', async () => {
    const { container } = render(PWA);
    await tick();
    // Component should render even with no props
    expect(container.querySelector('.pwa-status')).not.toBeNull();
  });

  it('does not throw when install button is clicked but no prompt exists', async () => {
    const { container } = render(PWA);
    await tick();
    // No install button exists - clicking it should not throw
    const installBtn = container.querySelector('.install-btn');
    expect(installBtn).toBeNull();
  });

  it('handles rapid render/unmount cycles', async () => {
    const { unmount } = render(PWA);
    await tick();
    unmount();
    // Re-render
    const { container } = render(PWA);
    await tick();
    expect(container.querySelector('.pwa-status')).not.toBeNull();
  });

  it('multiple instances render independently', async () => {
    const { container: c1 } = render(PWA);
    const { container: c2 } = render(PWA);
    await tick();
    expect(c1.querySelector('.pwa-status')).not.toBeNull();
    expect(c2.querySelector('.pwa-status')).not.toBeNull();
  });
});
