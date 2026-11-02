import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/svelte';
import { tick } from 'svelte';
import RTLSupport from './RTLSupport.svelte';

describe('RTLSupport Component', () => {
  describe('rendering', () => {
    it('renders LTR by default', () => {
      render(RTLSupport);
      expect(screen.getByText('LTR')).toBeTruthy();
    });

    it('renders RTL when direction is rtl', () => {
      render(RTLSupport, { props: { direction: 'rtl' } });
      expect(screen.getByText('RTL')).toBeTruthy();
    });

    it('has correct aria-label', () => {
      render(RTLSupport);
      const el = document.querySelector('.rtl-support');
      expect(el?.getAttribute('aria-label')).toBe('Toggle RTL/LTR');
    });

    it('has correct title', () => {
      render(RTLSupport);
      const el = document.querySelector('.rtl-support');
      expect(el?.getAttribute('title')).toBe('Toggle RTL/LTR');
    });

    it('has role button', () => {
      render(RTLSupport);
      const el = document.querySelector('.rtl-support');
      expect(el?.getAttribute('role')).toBe('button');
    });
  });

  describe('class generation', () => {
    it('includes base rtl-support class', () => {
      render(RTLSupport);
      const el = document.querySelector('.rtl-support');
      expect(el?.classList.contains('rtl-support')).toBe(true);
    });

    it('includes direction class (ltr)', () => {
      render(RTLSupport, { props: { direction: 'ltr' } });
      const el = document.querySelector('.rtl-support');
      expect(el?.classList.contains('ltr')).toBe(true);
    });

    it('includes direction class (rtl)', () => {
      render(RTLSupport, { props: { direction: 'rtl' } });
      const el = document.querySelector('.rtl-support');
      expect(el?.classList.contains('rtl')).toBe(true);
    });

    it('includes custom className', () => {
      render(RTLSupport, { props: { className: 'my-class another' } });
      const el = document.querySelector('.rtl-support');
      expect(el?.classList.contains('my-class')).toBe(true);
      expect(el?.classList.contains('another')).toBe(true);
    });
  });

  describe('attributes', () => {
    it('sets dir attribute to current direction', () => {
      render(RTLSupport, { props: { direction: 'rtl' } });
      const el = document.querySelector('.rtl-support');
      expect(el?.getAttribute('dir')).toBe('rtl');
    });
  });

  describe('interaction', () => {
    it('toggles direction on click', async () => {
      render(RTLSupport, { props: { direction: 'ltr' } });
      const el = document.querySelector('.rtl-support') as HTMLElement;
      await fireEvent.click(el);
      await tick();
      expect(el.getAttribute('dir')).toBe('rtl');
      expect(screen.getByText('RTL')).toBeTruthy();
    });

    it('dispatches toggle event on click', async () => {
      const onToggle = vi.fn();
      render(RTLSupport, { props: { direction: 'ltr' } });
      const el = document.querySelector('.rtl-support') as HTMLElement;
      el?.addEventListener('toggle', onToggle);
      await fireEvent.click(el);
      // Check immediately
      expect(onToggle).toHaveBeenCalledTimes(1);
      expect(onToggle).toHaveBeenCalledWith(expect.objectContaining({ detail: { direction: 'rtl' } }));
    });

    it('calls onChange callback when provided', async () => {
      const onChange = vi.fn();
      render(RTLSupport, { props: { direction: 'ltr', onChange } });
      const el = document.querySelector('.rtl-support') as HTMLElement;
      await fireEvent.click(el);
      await tick();
      expect(onChange).toHaveBeenCalledWith('rtl');
    });
  });
});
