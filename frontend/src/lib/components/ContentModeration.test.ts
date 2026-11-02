import { describe, it, expect, vi } from 'vitest';
import { render, fireEvent, screen } from '@testing-library/svelte';
import { tick } from 'svelte';
import ContentModeration from './ContentModeration.svelte';

const flaggedHigh = {
  is_flagged: true,
  severity: 'high',
  flags: ['spam', 'hate-speech'],
  recommendation: 'Please review and remove this content.',
  confidence: 0.95,
  categories: ['advertising', 'harassment'],
};

const flaggedMedium = {
  is_flagged: true,
  severity: 'medium',
  flags: ['profanity'],
  confidence: 0.65,
};

const flaggedLow = {
  is_flagged: true,
  severity: 'low',
  flags: ['style'],
  confidence: 0.3,
};

describe('ContentModeration Component', () => {
  it('renders nothing when moderation is null', async () => {
    const { container } = render(ContentModeration, { props: { moderation: null } });
    await tick();
    expect(container.querySelector('.content-moderation')).toBeNull();
  });

  it('renders nothing when is_flagged is false', async () => {
    const cleanContent = { is_flagged: false, severity: 'low', flags: [], confidence: 0 };
    const { container } = render(ContentModeration, { props: { moderation: cleanContent } });
    await tick();
    expect(container.querySelector('.content-moderation')).toBeNull();
  });

  it('renders wrapper with content-moderation class', async () => {
    const { container } = render(ContentModeration, { props: { moderation: flaggedHigh } });
    await tick();
    expect(container.querySelector('.content-moderation')).not.toBeNull();
  });

  it('wrapper has role=alert and aria-live=assertive', async () => {
    const { container } = render(ContentModeration, { props: { moderation: flaggedHigh } });
    await tick();
    const wrapper = container.querySelector('.content-moderation');
    expect(wrapper?.getAttribute('role')).toBe('alert');
    expect(wrapper?.getAttribute('aria-live')).toBe('assertive');
  });

  it('applies high class when severity=high', async () => {
    const { container } = render(ContentModeration, { props: { moderation: flaggedHigh } });
    await tick();
    expect(container.querySelector('.content-moderation')?.classList.contains('high')).toBe(true);
  });

  it('applies medium class when severity=medium', async () => {
    const { container } = render(ContentModeration, { props: { moderation: flaggedMedium } });
    await tick();
    expect(container.querySelector('.content-moderation')?.classList.contains('medium')).toBe(true);
  });

  it('applies low class when severity=low', async () => {
    const { container } = render(ContentModeration, { props: { moderation: flaggedLow } });
    await tick();
    expect(container.querySelector('.content-moderation')?.classList.contains('low')).toBe(true);
  });

  it('does not apply severity class when severity is unknown', async () => {
    const unknownSeverity = { is_flagged: true, severity: 'unknown', flags: [], confidence: 0.5 };
    const { container } = render(ContentModeration, { props: { moderation: unknownSeverity } });
    await tick();
    const wrapper = container.querySelector('.content-moderation');
    expect(wrapper?.classList.contains('high')).toBe(false);
    expect(wrapper?.classList.contains('medium')).toBe(false);
    expect(wrapper?.classList.contains('low')).toBe(false);
  });

  it('renders Content Moderation Alert title', async () => {
    render(ContentModeration, { props: { moderation: flaggedHigh } });
    await tick();
    expect(screen.getByText('Content Moderation Alert')).not.toBeNull();
  });

  it('renders high severity badge with red background', async () => {
    const { container } = render(ContentModeration, { props: { moderation: flaggedHigh } });
    await tick();
    const badge = container.querySelector('.severity-badge');
    expect(badge?.textContent).toContain('HIGH');
    expect(badge?.getAttribute('style')).toContain('rgb(220, 53, 69)');
  });

  it('renders medium severity badge with yellow background', async () => {
    const { container } = render(ContentModeration, { props: { moderation: flaggedMedium } });
    await tick();
    const badge = container.querySelector('.severity-badge');
    expect(badge?.textContent).toContain('MEDIUM');
    expect(badge?.getAttribute('style')).toContain('rgb(255, 193, 7)');
  });

  it('renders low severity badge with green background', async () => {
    const { container } = render(ContentModeration, { props: { moderation: flaggedLow } });
    await tick();
    const badge = container.querySelector('.severity-badge');
    expect(badge?.textContent).toContain('LOW');
    expect(badge?.getAttribute('style')).toContain('rgb(40, 167, 69)');
  });

  it('renders high severity icon 🚫', async () => {
    const { container } = render(ContentModeration, { props: { moderation: flaggedHigh } });
    await tick();
    expect(container.textContent).toContain('🚫');
  });

  it('renders medium severity icon ⚠️', async () => {
    const { container } = render(ContentModeration, { props: { moderation: flaggedMedium } });
    await tick();
    expect(container.textContent).toContain('⚠️');
  });

  it('renders low severity icon ℹ️', async () => {
    const { container } = render(ContentModeration, { props: { moderation: flaggedLow } });
    await tick();
    expect(container.textContent).toContain('ℹ️');
  });

  it('renders Detected issues caption', async () => {
    render(ContentModeration, { props: { moderation: flaggedHigh } });
    await tick();
    expect(screen.getByText('Detected issues:')).not.toBeNull();
  });

  it('renders each flag as a badge', async () => {
    render(ContentModeration, { props: { moderation: flaggedHigh } });
    await tick();
    expect(screen.getByText('spam')).not.toBeNull();
    expect(screen.getByText('hate-speech')).not.toBeNull();
  });

  it('does not render flags list when flags is empty', async () => {
    const noFlags = { is_flagged: true, severity: 'low', flags: [], confidence: 0.3 };
    const { container } = render(ContentModeration, { props: { moderation: noFlags } });
    await tick();
    expect(container.querySelector('.flags-list')).toBeNull();
  });

  it('renders recommendation text when provided', async () => {
    render(ContentModeration, { props: { moderation: flaggedHigh } });
    await tick();
    expect(screen.getByText(/Please review and remove this content/)).not.toBeNull();
  });

  it('does not render recommendation when missing', async () => {
    const { container } = render(ContentModeration, { props: { moderation: flaggedLow } });
    await tick();
    expect(container.querySelector('.recommendation')).toBeNull();
  });

  it('renders confidence as percentage', async () => {
    render(ContentModeration, { props: { moderation: flaggedHigh } });
    await tick();
    expect(screen.getByText(/Confidence: 95%/)).not.toBeNull();
  });

  it('renders categories when provided', async () => {
    render(ContentModeration, { props: { moderation: flaggedHigh } });
    await tick();
    expect(screen.getByText(/Categories: advertising, harassment/)).not.toBeNull();
  });

  it('does not render categories when missing', async () => {
    render(ContentModeration, { props: { moderation: flaggedMedium } });
    await tick();
    expect(screen.queryByText(/Categories:/)).toBeNull();
  });

  it('renders dismiss button with aria-label', async () => {
    const { container } = render(ContentModeration, { props: { moderation: flaggedHigh } });
    await tick();
    const btn = container.querySelector('.dismiss-btn');
    expect(btn).not.toBeNull();
    expect(btn?.getAttribute('aria-label')).toBe('Dismiss moderation alert');
  });

  it('clicking dismiss button calls onDismiss', async () => {
    const onDismiss = vi.fn();
    const { container } = render(ContentModeration, {
      props: { moderation: flaggedHigh, onDismiss },
    });
    await tick();
    const btn = container.querySelector('.dismiss-btn');
    if (btn) {
      await fireEvent.click(btn);
    }
    expect(onDismiss).toHaveBeenCalled();
  });

  it('clicking dismiss button does not throw when onDismiss not provided', async () => {
    const { container } = render(ContentModeration, {
      props: { moderation: flaggedHigh },
    });
    await tick();
    const btn = container.querySelector('.dismiss-btn');
    let error: any = null;
    try {
      if (btn) await fireEvent.click(btn);
    } catch (e) {
      error = e;
    }
    expect(error).toBeNull();
  });
});
