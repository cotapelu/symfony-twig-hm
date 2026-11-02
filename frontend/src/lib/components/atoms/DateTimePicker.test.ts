import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import DateTimePicker from './DateTimePicker.svelte';

describe('DateTimePicker', () => {
  const now = new Date();
  const formatOptions = { hour: '2-digit', minute: '2-digit' as const };

  const renderDateTimePicker = (props = {}) => render(DateTimePicker, { props });

  it('renders with default props', () => {
    const { container } = renderDateTimePicker();
    expect(container.querySelector('.date-time')).toBeTruthy();
    // Should display some date text and time text
    expect(container.querySelector('.date-text')).toBeTruthy();
    expect(container.querySelector('.time-text')).toBeTruthy();
  });

  it('displays relative time format by default', () => {
    const { container } = renderDateTimePicker({ date: now });
    const dateText = container.querySelector('.date-text')?.textContent;
    expect(dateText).toBe('Just now');
  });

  it('displays short format', () => {
    const testDate = new Date(2024, 0, 15, 14, 30);
    const { container } = renderDateTimePicker({ date: testDate, format: 'short' });
    const dateText = container.querySelector('.date-text')?.textContent;
    // Expect something like "Jan 15, 2:30 PM" depending on locale
    expect(dateText).toMatch(/Jan\s+15/);
  });

  it('displays long format', () => {
    const testDate = new Date(2024, 0, 15, 14, 30);
    const { container } = renderDateTimePicker({ date: testDate, format: 'long' });
    const dateText = container.querySelector('.date-text')?.textContent;
    // Should include month day (e.g., "January 15" or "Jan 15")
    expect(dateText).toMatch(/15/); // contain day
    expect(dateText).toMatch(/Jan|January/); // contain month abbreviation or full
  });

  it('shows time in 24h or 12h format depending on locale', () => {
    const testDate = new Date(2024, 0, 15, 14, 30);
    const { container } = renderDateTimePicker({ date: testDate, format: 'short' });
    const timeText = container.querySelector('.time-text')?.textContent;
    expect(timeText).toMatch(/\d{1,2}:\d{2}/);
  });

  it('hides date when showDate is false', () => {
    const { container } = renderDateTimePicker({ showDate: false });
    expect(container.querySelector('.date-text')).toBeNull();
    expect(container.querySelector('.time-text')).toBeTruthy();
  });

  it('hides time when showTime is false', () => {
    const { container } = renderDateTimePicker({ showTime: false });
    expect(container.querySelector('.date-text')).toBeTruthy();
    expect(container.querySelector('.time-text')).toBeNull();
  });

  it('hides both when showDate and showTime false', () => {
    const { container } = renderDateTimePicker({ showDate: false, showTime: false });
    expect(container.querySelector('.date-text')).toBeNull();
    expect(container.querySelector('.time-text')).toBeNull();
  });

  it('applies custom className to wrapper', () => {
    const { container } = renderDateTimePicker({ className: 'my-dt' });
    const wrapper = container.querySelector('.date-time');
    expect(wrapper).toHaveClass('my-dt');
  });

  it('renders Typography components with correct variants', () => {
    const { container } = renderDateTimePicker();
    const dateTypography = container.querySelector('.date-text');
    const timeTypography = container.querySelector('.time-text');
    // They should have classes from Typography component
    expect(dateTypography).toHaveClass('typography');
    expect(timeTypography).toHaveClass('typography');
    expect(dateTypography).toHaveClass('typography-body');
    expect(timeTypography).toHaveClass('typography-caption');
  });

  it('handles date as string (ISO)', () => {
    const isoDate = '2024-01-15T14:30:00';
    const { container } = renderDateTimePicker({ date: isoDate, format: 'short' });
    const dateText = container.querySelector('.date-text')?.textContent;
    expect(dateText).toMatch(/Jan\s+15/);
  });

  it('handles invalid date gracefully', () => {
    const invalidDate = 'not-a-date' as any;
    // It should not throw; probably yields "Invalid Date" or empty
    const { container } = renderDateTimePicker({ date: invalidDate });
    const dateText = container.querySelector('.date-text')?.textContent;
    expect(dateText).toBeTruthy(); // some text
  });
});
