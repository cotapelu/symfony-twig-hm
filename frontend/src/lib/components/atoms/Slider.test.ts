import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import Slider from './Slider.svelte';

describe('Slider', () => {
  const renderSlider = (props = {}) => render(Slider, { props });

  it('renders slider with base class', () => {
    const { container } = renderSlider({ value: 50 });
    expect(container.querySelector('.slider')).toBeTruthy();
  });

  it('applies horizontal class by default', () => {
    const { container } = renderSlider({});
    const slider = container.querySelector('.slider');
    expect(slider).toHaveClass('slider-horizontal');
    expect(slider).not.toHaveClass('slider-vertical');
  });

  it('applies vertical class when vertical prop true', () => {
    const { container } = renderSlider({ vertical: true });
    const slider = container.querySelector('.slider');
    expect(slider).toHaveClass('slider-vertical');
    expect(slider).not.toHaveClass('slider-horizontal');
  });

  it('applies disabled class and sets tabindex to -1 when disabled', () => {
    const { container } = renderSlider({ disabled: true });
    const slider = container.querySelector('.slider');
    expect(slider).toHaveClass('slider-disabled');
    expect(slider).toHaveAttribute('tabindex', '-1');
  });

  it('enabled slider has tabindex 0', () => {
    const { container } = renderSlider({});
    const slider = container.querySelector('.slider');
    expect(slider).toHaveAttribute('tabindex', '0');
  });

  it('renders rail and track elements', () => {
    const { container } = renderSlider({});
    expect(container.querySelector('.slider-rail')).toBeTruthy();
    expect(container.querySelector('.slider-track')).toBeTruthy();
  });

  it('renders one handle for single value mode', () => {
    const { container } = renderSlider({ value: 50 });
    const handles = container.querySelectorAll('.slider-handle');
    expect(handles.length).toBe(1);
  });

  it('renders two handles for range mode', () => {
    const { container } = renderSlider({ range: true, value: [20, 80] });
    const handles = container.querySelectorAll('.slider-handle');
    expect(handles.length).toBe(2);
  });

  it('positions handle correctly for single value', () => {
    const { container } = renderSlider({ value: 30, min: 0, max: 100 });
    const handle = container.querySelector('.slider-handle') as HTMLElement;
    expect(handle.style.left).toBe('30%');
  });

  it('positions two handles correctly for range', () => {
    const { container } = renderSlider({ range: true, value: [25, 75], min: 0, max: 100 });
    const handles = container.querySelectorAll('.slider-handle');
    expect(handles[0].style.left).toBe('25%');
    expect(handles[1].style.left).toBe('75%');
  });

  it('handles vertical orientation for range', () => {
    const { container } = renderSlider({ range: true, value: [20, 80], vertical: true, min: 0, max: 100 });
    const handles = container.querySelectorAll('.slider-handle');
    expect(handles[0].style.bottom).toBe('20%');
    expect(handles[1].style.bottom).toBe('80%');
  });

  it('applies reverse class when reverse prop true', () => {
    const { container } = renderSlider({ reverse: true });
    const slider = container.querySelector('.slider');
    expect(slider).toHaveClass('slider-reverse');
  });

  it('applies custom className', () => {
    const { container } = renderSlider({ className: 'my-slider' });
    const slider = container.querySelector('.slider');
    expect(slider).toHaveClass('my-slider');
  });

  it('has proper ARIA attributes on slider element', () => {
    const { container } = renderSlider({ value: 50, min: 0, max: 100 });
    const slider = container.querySelector('.slider') as HTMLElement;
    expect(slider).toHaveAttribute('role', 'slider');
    expect(slider).toHaveAttribute('aria-valuemin', '0');
    expect(slider).toHaveAttribute('aria-valuemax', '100');
    expect(slider).toHaveAttribute('aria-valuenow', '50');
  });
});
