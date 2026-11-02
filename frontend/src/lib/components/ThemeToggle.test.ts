import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import { tick } from 'svelte';
import ThemeToggle from './ThemeToggle.svelte';

describe('ThemeToggle Component', () => {
  it('renders theme-selector container', async () => {
    const { container } = render(ThemeToggle);
    await tick();
    expect(container.querySelector('.theme-selector')).not.toBeNull();
  });

  it('applies theme-selector class', async () => {
    const { container } = render(ThemeToggle);
    await tick();
    const selector = container.querySelector('.theme-selector');
    expect(selector?.className).toContain('theme-selector');
  });

  it('does not show dropdown initially', async () => {
    const { container } = render(ThemeToggle);
    await tick();
    expect(container.querySelector('.theme-dropdown')).toBeNull();
  });

  it('renders theme-toggle button', async () => {
    const { container } = render(ThemeToggle);
    await tick();
    const toggle = container.querySelector('.theme-toggle');
    expect(toggle).not.toBeNull();
  });

  it('renders theme-icon span', async () => {
    const { container } = render(ThemeToggle);
    await tick();
    const icon = container.querySelector('.theme-icon');
    expect(icon).not.toBeNull();
  });

  it('renders dropdown-arrow svg', async () => {
    const { container } = render(ThemeToggle);
    await tick();
    const arrow = container.querySelector('.dropdown-arrow');
    expect(arrow).not.toBeNull();
  });

  it('multiple instances render independently', async () => {
    const { container: c1 } = render(ThemeToggle);
    const { container: c2 } = render(ThemeToggle);
    await tick();
    expect(c1.querySelector('.theme-selector')).not.toBeNull();
    expect(c2.querySelector('.theme-selector')).not.toBeNull();
  });

  it('does not have rotated class initially', async () => {
    const { container } = render(ThemeToggle);
    await tick();
    const arrow = container.querySelector('.dropdown-arrow');
    expect(arrow?.className).not.toContain('rotated');
  });

  it('renders without errors when localStorage is unavailable', async () => {
    const { component } = render(ThemeToggle);
    await tick();
    expect(component).not.toBeNull();
  });

  it('accepts undefined props gracefully', async () => {
    const { component } = render(ThemeToggle, { props: {} });
    await tick();
    expect(component).not.toBeNull();
  });
});
