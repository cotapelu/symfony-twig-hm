import { describe, it, expect } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import Rate from './Rate.svelte';

describe('Rate', () => {
  const renderRate = (props = {}) => render(Rate, { props });

  it('renders default count of 5 stars', () => {
    const { container } = renderRate();
    const stars = container.querySelectorAll('.rate-star');
    expect(stars.length).toBe(5);
  });

  it('renders custom count', () => {
    const { container } = renderRate({ count: 7 });
    const stars = container.querySelectorAll('.rate-star');
    expect(stars.length).toBe(7);
  });

  it('shows correct active stars based on value', async () => {
    const { container } = renderRate({ value: 3 });
    await tick();
    const stars = container.querySelectorAll('.rate-star');
    expect(stars.length).toBe(5);
    expect(stars[0]).toHaveClass('active');
    expect(stars[1]).toHaveClass('active');
    expect(stars[2]).toHaveClass('active');
    expect(stars[3]).not.toHaveClass('active');
    expect(stars[4]).not.toHaveClass('active');
  });

  it('updates active stars on click', async () => {
    const { container } = renderRate({ value: 0 });
    const stars = container.querySelectorAll('.rate-star');
    // Click on the third star's full element (simulate click on star container? The click handler is on the rate-star-full child)
    const fullStar = stars[2].querySelector('.rate-star-full');
    await fireEvent.click(fullStar!);
    await tick();
    // After click, value should be 3 (index 2 + 1), so first three stars active
    expect(stars[0]).toHaveClass('active');
    expect(stars[1]).toHaveClass('active');
    expect(stars[2]).toHaveClass('active');
    expect(stars[3]).not.toHaveClass('active');
    expect(stars[4]).not.toHaveClass('active');
  });

  it('applies size class', () => {
    const { container } = renderRate({ size: 'large' });
    const root = container.querySelector('div[role="radiogroup"]');
    expect(root).toHaveClass('rate-large');
  });

  it('applies custom className', () => {
    const { container } = renderRate({ className: 'my-rate' });
    const root = container.querySelector('div[role="radiogroup"]');
    expect(root).toHaveClass('my-rate');
  });

  it('shows custom character', () => {
    const { container } = renderRate({ character: '★' });
    const fullStar = container.querySelector('.rate-star-full');
    expect(fullStar).toHaveTextContent('★');
  });

  it('disables stars when disabled true', () => {
    const { container } = renderRate({ disabled: true });
    const root = container.querySelector('div[role="radiogroup"]');
    expect(root).toHaveClass('rate-disabled');
  });

  it('makes stars readonly when readonly true', () => {
    const { container } = renderRate({ readonly: true });
    const root = container.querySelector('div[role="radiogroup"]');
    expect(root).toHaveClass('rate-readonly');
  });

  it('renders half stars when allowHalf true', () => {
    const { container } = renderRate({ value: 2.5, allowHalf: true });
    // Each star becomes two halves: left and right
    const leftHalves = container.querySelectorAll('.rate-star-half.rate-star-left');
    const rightHalves = container.querySelectorAll('.rate-star-half.rate-star-right');
    expect(leftHalves.length).toBe(5);
    expect(rightHalves.length).toBe(5);
  });

  it('hovers affect rating preview on full stars', async () => {
    const { container } = renderRate({ value: 0 });
    const stars = container.querySelectorAll('.rate-star');
    // Hover over second star (index 1) by entering its full child
    const fullStar = stars[1].querySelector('.rate-star-full');
    await fireEvent.mouseEnter(fullStar!);
    await tick();
    // Now star 0 and 1 should be active due to hoverValue=2
    expect(stars[0]).toHaveClass('active');
    expect(stars[1]).toHaveClass('active');
    expect(stars[2]).not.toHaveClass('active');
    // Hover leaves (mouseLeave on the same full child)
    await fireEvent.mouseLeave(fullStar!);
    await tick();
    // After mouseLeave, hoverValue resets to 0, so no stars active
    expect(stars[0]).not.toHaveClass('active');
    expect(stars[1]).not.toHaveClass('active');
  });

  it('clicking a star updates value', async () => {
    const { container } = renderRate({ value: 0 });
    const stars = container.querySelectorAll('.rate-star');
    // Click first star
    const fullStar0 = stars[0].querySelector('.rate-star-full');
    await fireEvent.click(fullStar0!);
    await tick();
    // first star should be active (value=1)
    expect(stars[0]).toHaveClass('active');
    expect(stars[1]).not.toHaveClass('active');
  });
});
