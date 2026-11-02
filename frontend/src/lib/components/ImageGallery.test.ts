import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import ImageGallery from './ImageGallery.svelte';

describe('ImageGallery', () => {
  const sampleImages = [
    {
      src: 'image1.jpg',
      thumbnail: 'thumb1.jpg',
      alt: 'Image 1',
      title: 'First Image',
      description: 'Description 1',
      filename: 'image1.jpg',
    },
    {
      src: 'image2.jpg',
      thumbnail: 'thumb2.jpg',
      alt: 'Image 2',
      title: 'Second Image',
      description: 'Description 2',
      filename: 'image2.jpg',
    },
    {
      src: 'image3.jpg',
      alt: 'Image 3',
      title: 'Third Image',
    },
  ];

  it('renders with default props', () => {
    render(ImageGallery);
    const gallery = document.querySelector('.image-gallery');
    expect(gallery).toBeInTheDocument();
  });

  it('renders empty gallery without error', () => {
    render(ImageGallery, { props: { images: [] } });
    const gallery = document.querySelector('.image-gallery');
    expect(gallery).toBeInTheDocument();
  });

  it('renders gallery items for each image', () => {
    render(ImageGallery, { props: { images: sampleImages } });
    const items = document.querySelectorAll('.gallery-item');
    expect(items.length).toBe(sampleImages.length);
  });

  it('uses thumbnail when provided, falls back to src', () => {
    render(ImageGallery, { props: { images: sampleImages } });
    const images = document.querySelectorAll('.gallery-image');
    expect(images.length).toBe(3);
    // First image has thumbnail
    expect(images[0]).toHaveAttribute('src', 'thumb1.jpg');
    // Second image has thumbnail
    expect(images[1]).toHaveAttribute('src', 'thumb2.jpg');
    // Third image falls back to src
    expect(images[2]).toHaveAttribute('src', 'image3.jpg');
  });

  it('applies custom columns prop', () => {
    render(ImageGallery, { props: { images: sampleImages, columns: 4 } });
    const gallery = document.querySelector('.image-gallery');
    expect(gallery).toHaveAttribute('style', expect.stringContaining('--columns: 3')); // min(columns, images.length)
  });

  it('applies custom gap prop', () => {
    render(ImageGallery, { props: { images: sampleImages, gap: '2rem' } });
    const gallery = document.querySelector('.image-gallery');
    expect(gallery).toHaveAttribute('style', expect.stringContaining('--gap: 2rem'));
  });

  it('shows title in gallery overlay', () => {
    render(ImageGallery, { props: { images: sampleImages } });
    expect(screen.getByText('First Image')).toBeInTheDocument();
    expect(screen.getByText('Second Image')).toBeInTheDocument();
    expect(screen.getByText('Third Image')).toBeInTheDocument();
  });

  it('shows description in gallery overlay when provided', () => {
    render(ImageGallery, { props: { images: sampleImages } });
    expect(screen.getByText('Description 1')).toBeInTheDocument();
    expect(screen.getByText('Description 2')).toBeInTheDocument();
  });

  it('renders zoom button on each gallery item', () => {
    render(ImageGallery, { props: { images: sampleImages } });
    const zoomButtons = document.querySelectorAll('.zoom-btn');
    expect(zoomButtons.length).toBe(3);
  });

  it('renders multiple instances independently', () => {
    const { container: container1 } = render(ImageGallery, { props: { images: sampleImages, columns: 2 } });
    const { container: container2 } = render(ImageGallery, { props: { images: sampleImages, columns: 4 } });
    const gallery1 = container1.querySelector('.image-gallery');
    const gallery2 = container2.querySelector('.image-gallery');
    expect(gallery1).toHaveAttribute('style', expect.stringContaining('--columns: 2'));
    expect(gallery2).toHaveAttribute('style', expect.stringContaining('--columns: 3')); // min(4, 3)
  });

  it('handles single image correctly', () => {
    render(ImageGallery, { props: { images: [sampleImages[0]] } });
    const items = document.querySelectorAll('.gallery-item');
    expect(items.length).toBe(1);
    const gallery = document.querySelector('.image-gallery');
    expect(gallery).toHaveAttribute('style', expect.stringContaining('--columns: 1'));
  });
});