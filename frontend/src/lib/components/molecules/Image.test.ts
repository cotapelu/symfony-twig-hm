import { describe, it, expect } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import Image from './Image.svelte';

describe('Image', () => {
  const defaultSrc = 'https://example.com/image.jpg';
  const altText = 'Test image';

  const renderImage = (props = {}) => render(Image, { props });

  it('renders an img element with src and alt', () => {
    renderImage({ src: defaultSrc, alt: altText });
    const img = screen.getByRole('img', { name: altText });
    expect(img).toBeInTheDocument();
    expect(img).toHaveAttribute('src', defaultSrc);
  });

  it('applies base image class', () => {
    const { container } = renderImage({ src: defaultSrc, alt: altText });
    const wrapper = container.querySelector('.image');
    expect(wrapper).toBeTruthy();
  });

  it('uses lazy loading by default', () => {
    renderImage({ src: defaultSrc, alt: altText });
    const img = screen.getByRole('img');
    expect(img).toHaveAttribute('loading', 'lazy');
  });

  it('uses eager loading when lazy=false', () => {
    renderImage({ src: defaultSrc, alt: altText, lazy: false });
    const img = screen.getByRole('img');
    expect(img).toHaveAttribute('loading', 'eager');
  });

  it('applies custom width and height as numbers (px)', () => {
    const { container } = renderImage({ src: defaultSrc, alt: altText, width: 300, height: 200 });
    const img = container.querySelector('img') as HTMLElement;
    expect(img.style.width).toBe('300px');
    expect(img.style.height).toBe('200px');
  });

  it('applies custom width and height as strings', () => {
    const { container } = renderImage({ src: defaultSrc, alt: altText, width: '50%', height: 'auto' });
    const img = container.querySelector('img') as HTMLElement;
    expect(img.style.width).toBe('50%');
    expect(img.style.height).toBe('auto');
  });

  it('applies objectFit style', () => {
    const { container } = renderImage({ src: defaultSrc, alt: altText, objectFit: 'contain' });
    const img = container.querySelector('img') as HTMLElement;
    expect(img.style.objectFit).toBe('contain');
  });

  it('shows loading class initially', () => {
    const { container } = renderImage({ src: defaultSrc, alt: altText });
    const wrapper = container.querySelector('.image');
    expect(wrapper).toHaveClass('image-loading');
  });

  it('removes loading class on load event', async () => {
    const { container } = renderImage({ src: defaultSrc, alt: altText });
    const img = screen.getByRole('img');
    // Initially loading
    expect(container.querySelector('.image')).toHaveClass('image-loading');
    // Simulate load
    await fireEvent.load(img);
    await tick();
    expect(container.querySelector('.image')).not.toHaveClass('image-loading');
    expect(container.querySelector('.image')).not.toHaveClass('image-error');
  });

  it('adds error class on error event', async () => {
    const { container } = renderImage({ src: defaultSrc, alt: altText });
    const img = screen.getByRole('img');
    await fireEvent.error(img);
    await tick();
    expect(container.querySelector('.image')).toHaveClass('image-error');
  });

  it('uses fallbackSrc on error when provided', async () => {
    const fallback = 'https://example.com/fallback.jpg';
    const { container } = renderImage({ src: defaultSrc, alt: altText, fallbackSrc: fallback });
    const img = screen.getByRole('img');
    // Initially src is default
    expect(img).toHaveAttribute('src', defaultSrc);
    // Simulate error
    await fireEvent.error(img);
    await tick();
    // src should be changed to fallback
    expect(img).toHaveAttribute('src', fallback);
    expect(container.querySelector('.image')).toHaveClass('image-error');
  });

  it('does not change src on error when no fallback', async () => {
    renderImage({ src: defaultSrc, alt: altText });
    const img = screen.getByRole('img');
    await fireEvent.error(img);
    await tick();
    expect(img).toHaveAttribute('src', defaultSrc);
  });

  it('does not render preview button when preview false', () => {
    renderImage({ src: defaultSrc, alt: altText, preview: false });
    expect(screen.queryByRole('button', { name: /view full size image/i })).not.toBeInTheDocument();
  });
});
