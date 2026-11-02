import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import { tick } from 'svelte';
import LazyImage from './LazyImage.svelte';

describe('LazyImage Component', () => {
  // Setup global IntersectionObserver mock to avoid ReferenceError in component
  const ioMock: any = vi.fn(() => ({
    observe: vi.fn(),
    unobserve: vi.fn(),
    disconnect: vi.fn(),
  }));

  beforeEach(() => {
    (window as any).IntersectionObserver = ioMock;
    (globalThis as any).IntersectionObserver = ioMock;
  });

  afterEach(() => {
    vi.clearAllMocks();
    delete (window as any).IntersectionObserver;
    delete (globalThis as any).IntersectionObserver;
  });

  it('renders img with src and alt attributes', async () => {
    render(LazyImage, { props: { src: 'test.jpg', alt: 'Test image' } });
    await tick();
    const img = document.querySelector('img');
    expect(img).toHaveAttribute('src', 'test.jpg');
    expect(img).toHaveAttribute('alt', 'Test image');
  });

  it('renders with empty src and alt defaults', async () => {
    render(LazyImage);
    await tick();
    const img = document.querySelector('img');
    expect(img).toHaveAttribute('src', '');
    expect(img).toHaveAttribute('alt', '');
  });

  it('applies className to wrapper div', async () => {
    render(LazyImage, { props: { src: 'a.jpg', alt: 'a', className: 'my-lazy' } });
    await tick();
    const wrapper = document.querySelector('.lazy-image');
    expect(wrapper).toHaveClass('my-lazy');
  });

  it('renders placeholder when placeholder prop is set', async () => {
    render(LazyImage, { props: { src: 'a.jpg', alt: 'a', placeholder: 'Loading...' } });
    await tick();
    expect(screen.queryByText('Loading...')).not.toBeNull();
  });

  it('sets data-src attribute to src value', async () => {
    render(LazyImage, { props: { src: 'photo.png', alt: 'photo' } });
    await tick();
    const img = document.querySelector('img');
    expect(img).toHaveAttribute('data-src', 'photo.png');
  });

  it('img has loading="lazy" attribute', async () => {
    render(LazyImage, { props: { src: 'a.jpg', alt: 'a' } });
    await tick();
    const img = document.querySelector('img');
    expect(img).toHaveAttribute('loading', 'lazy');
  });

  it('uses on:load handler when image emits load event', async () => {
    const cb = vi.fn();
    render(LazyImage, { props: { src: 'a.jpg', alt: 'a', loadCallback: cb } });
    await tick();
    const img = document.querySelector('img');
    await fireEvent.load(img);

    expect(cb).toHaveBeenCalled();
  });

  it('renders wrapper div with lazy-image class', async () => {
    render(LazyImage, { props: { src: 'a.jpg', alt: 'a' } });
    await tick();
    expect(document.querySelector('div.lazy-image')).not.toBeNull();
  });

  it('img has lazy-image-img class', async () => {
    render(LazyImage, { props: { src: 'a.jpg', alt: 'a' } });
    await tick();
    const img = document.querySelector('img');
    expect(img).toHaveClass('lazy-image-img');
  });

  it('hides placeholder on load event via on:load', async () => {
    render(LazyImage, { props: { src: 'a.jpg', alt: 'a', placeholder: 'Wait...' } });
    await tick();
    expect(screen.queryByText('Wait...')).not.toBeNull();

    const img = document.querySelector('img');
    await fireEvent.load(img);
    await tick();

    expect(screen.queryByText('Wait...')).toBeNull();
  });
});
