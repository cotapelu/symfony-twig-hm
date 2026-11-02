import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import Drawer from './Drawer.svelte';

describe('Drawer Minimal', () => {
  it('renders when open', () => {
    const { container } = render(Drawer, { props: { open: true } });
    console.log('Container:', container.innerHTML);
    expect(container.querySelector('.drawer')).toBeTruthy();
  });
});
