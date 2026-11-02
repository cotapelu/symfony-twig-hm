import { describe, it, expect } from 'vitest';
import { render } from '@testing-library/svelte';
import TouchFeedback from './TouchFeedback.svelte';

describe('TouchFeedback Component', () => {
  describe('class generation', () => {
    it('applies base touch-feedback class', () => {
      const { container } = render(TouchFeedback);
      const el = container.firstChild as HTMLElement;
      expect(el.classList.contains('touch-feedback')).toBe(true);
    });

    it('applies custom className', () => {
      render(TouchFeedback, { props: { className: 'my-custom-class another' } });
      const el = document.querySelector('.touch-feedback') as HTMLElement;
      expect(el.classList.contains('my-custom-class')).toBe(true);
      expect(el.classList.contains('another')).toBe(true);
    });
  });

  describe('attributes', () => {
    it('sets role attribute? Not directly; wrapper is div', () => {
      render(TouchFeedback);
      const el = document.querySelector('.touch-feedback') as HTMLElement;
      // No special role set on root
      expect(el.getAttribute('role')).toBeNull();
    });
  });

  // Event and interaction tests omitted due to complexity with touch events and slot rendering.
});
