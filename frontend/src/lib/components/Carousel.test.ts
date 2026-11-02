import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/svelte';
import Carousel from './Carousel.svelte';

describe('Carousel Component', () => {
  const items = [
    { type: 'content', content: '<p>Slide 1</p>' },
    { type: 'content', content: '<p>Slide 2</p>' },
    { type: 'content', content: '<p>Slide 3</p>' }
  ];

  it('renders first item when items provided', () => {
    render(Carousel, { props: { items } });
    expect(screen.getByText('Slide 1')).toBeInTheDocument();
  });

  it('shows dots when showDots=true', () => {
    render(Carousel, { props: { items, showDots: true } });
    expect(screen.getAllByRole('button').length).toBeGreaterThanOrEqual(1);
  });

  it('hides dots when showDots=false', () => {
    render(Carousel, { props: { items, showDots: false, showArrows: false } });
    expect(screen.queryByRole('tablist')).toBeNull();
  });

  it('navigates to next item on arrow click', async () => {
    render(Carousel, { props: { items, showArrows: true } });
    // initial slide 1
    expect(screen.getByText('Slide 1')).toBeInTheDocument();
    const nextBtn = screen.getByLabelText('Next slide');
    await fireEvent.click(nextBtn);
    // after click, slide 2 should be visible (transition might need tick)
    // Since transition is immediate, next index updates
    expect(screen.getByText('Slide 2')).toBeInTheDocument();
  });

  it('navigates to previous item on arrow click', async () => {
    render(Carousel, { props: { items, showArrows: true } });
    // start at 0, go prev should go to last
    const prevBtn = screen.getByLabelText('Previous slide');
    await fireEvent.click(prevBtn);
    expect(screen.getByText('Slide 3')).toBeInTheDocument();
  });
});
