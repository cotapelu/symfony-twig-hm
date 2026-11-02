import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import { tick } from 'svelte';
import GestureRecognizer from './GestureRecognizer.svelte';

describe('GestureRecognizer Component', () => {
  it('renders wrapper with gesture-recognizer class', async () => {
    const { container } = render(GestureRecognizer);
    await tick();
    expect(container.querySelector('.gesture-recognizer')).not.toBeNull();
  });

  it('applies custom className to wrapper', async () => {
    const { container } = render(GestureRecognizer, { props: { className: 'my-gesture' } });
    await tick();
    expect(container.querySelector('.my-gesture')).not.toBeNull();
  });

  it('renders wrapper with no children content by default (slot placeholder)', async () => {
    const { container } = render(GestureRecognizer);
    await tick();
    // Without a default slot, the wrapper renders only the Svelte slot placeholder
    const wrapper = container.querySelector('.gesture-recognizer');
    expect(wrapper).not.toBeNull();
    expect(wrapper?.children.length).toBe(0);
  });

  it('renders with disabled=true without error', async () => {
    const { container } = render(GestureRecognizer, { props: { disabled: true } });
    await tick();
    expect(container.querySelector('.gesture-recognizer')).not.toBeNull();
  });

  it('renders with enablePan=false without error', async () => {
    const { container } = render(GestureRecognizer, { props: { enablePan: false } });
    await tick();
    expect(container.querySelector('.gesture-recognizer')).not.toBeNull();
  });

  it('renders with enablePinch=false without error', async () => {
    const { container } = render(GestureRecognizer, { props: { enablePinch: false } });
    await tick();
    expect(container.querySelector('.gesture-recognizer')).not.toBeNull();
  });

  it('renders with enableRotate=false without error', async () => {
    const { container } = render(GestureRecognizer, { props: { enableRotate: false } });
    await tick();
    expect(container.querySelector('.gesture-recognizer')).not.toBeNull();
  });

  it('renders with minPointers=2 and maxPointers=3 without error', async () => {
    const { container } = render(GestureRecognizer, {
      props: { minPointers: 2, maxPointers: 3 },
    });
    await tick();
    expect(container.querySelector('.gesture-recognizer')).not.toBeNull();
  });

  it('renders with multiple gesture toggles combined', async () => {
    const { container } = render(GestureRecognizer, {
      props: {
        disabled: false,
        enablePan: false,
        enablePinch: false,
        enableRotate: false,
        minPointers: 2,
        maxPointers: 5,
      },
    });
    await tick();
    expect(container.querySelector('.gesture-recognizer')).not.toBeNull();
  });
});
