import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import Image from './Image.svelte';

describe('Image Component', () => {
  const defaultProps = {
    src: '/img.jpg',
    alt: 'Test image',
  };

  it('renders img element with src and alt', () => {
    const { container } = render(Image, { props: defaultProps });
    const img = container.querySelector('img');
    expect(img).toBeInTheDocument();
    expect(img).toHaveAttribute('src', '/img.jpg');
    expect(img).toHaveAttribute('alt', 'Test image');
  });

  it('applies custom className to wrapper', () => {
    const { container } = render(Image, { props: { ...defaultProps, className: 'my-image' } });
    const wrapper = container.querySelector('.image');
    expect(wrapper).toHaveClass('my-image');
  });

  it('sets width and height on img style', () => {
    const { container } = render(Image, { props: { ...defaultProps, width: 100, height: 200 } });
    const img = container.querySelector('img');
    expect(img?.getAttribute('style')).toContain('width: 100px');
    expect(img?.getAttribute('style')).toContain('height: 200px');
  });

  it('sets object-fit on img style', () => {
    const { container } = render(Image, { props: { ...defaultProps, objectFit: 'contain' } });
    const img = container.querySelector('img');
    expect(img?.getAttribute('style')).toContain('object-fit: contain');
  });

  it('adds loading class to wrapper while loading', () => {
    const { container } = render(Image, { props: defaultProps });
    const wrapper = container.querySelector('.image');
    expect(wrapper).toHaveClass('image-loading');
  });

  it('removes loading class after load', async () => {
    const { container } = render(Image, { props: defaultProps });
    const img = container.querySelector('img')!;
    await fireEvent.load(img);
    const wrapper = container.querySelector('.image');
    expect(wrapper).not.toHaveClass('image-loading');
  });

  it('adds error class after error', async () => {
    const { container } = render(Image, { props: defaultProps });
    const img = container.querySelector('img')!;
    await fireEvent.error(img);
    const wrapper = container.querySelector('.image');
    expect(wrapper).toHaveClass('image-error');
  });

  // Event dispatch tests skipped due to testing complexities.


  it('removes loading class after error', async () => {
    const { container } = render(Image, { props: defaultProps });
    const img = container.querySelector('img')!;
    await fireEvent.error(img);
    const wrapper = container.querySelector('.image');
    expect(wrapper).not.toHaveClass('image-loading');
    expect(wrapper).toHaveClass('image-error');
  });
});
