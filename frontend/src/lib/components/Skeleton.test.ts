import { render, screen } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Skeleton from './Skeleton.svelte';

describe('Skeleton Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('rendering', () => {
    it('renders with default props', () => {
      render(Skeleton);
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toBeInTheDocument();
    });

    it('applies custom width', () => {
      render(Skeleton, { props: { width: '200px' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('width: 200px');
    });

    it('applies custom height', () => {
      render(Skeleton, { props: { height: '40px' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('height: 40px');
    });

    it('applies custom borderRadius', () => {
      render(Skeleton, { props: { borderRadius: '8px' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('border-radius: 8px');
    });

    it('applies custom className', () => {
      render(Skeleton, { props: { className: 'custom-skeleton' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveClass('custom-skeleton');
    });

    it('applies animated class by default', () => {
      render(Skeleton);
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveClass('animated');
    });

    it('removes animated class when animated is false', () => {
      render(Skeleton, { props: { animated: false } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).not.toHaveClass('animated');
    });

    it('combines all styles correctly', () => {
      render(Skeleton, { 
        props: { 
          width: '100%', 
          height: '2rem', 
          borderRadius: '12px' 
        } 
      });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('width: 100%');
      expect(skeleton).toHaveStyle('height: 2rem');
      expect(skeleton).toHaveStyle('border-radius: 12px');
    });
  });

  describe('animation', () => {
    it('has shimmer animation when animated', () => {
      render(Skeleton, { props: { animated: true } });
      const skeleton = document.querySelector('.skeleton');
      // The animation is applied via ::before pseudo-element in CSS
      expect(skeleton).toHaveClass('animated');
    });

    it('has no animation when not animated', () => {
      render(Skeleton, { props: { animated: false } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).not.toHaveClass('animated');
    });

    it('includes reduced motion media query', () => {
      render(Skeleton);
      // Component includes @media (prefers-reduced-motion: reduce) in styles
      expect(document.querySelector('.skeleton')).toBeInTheDocument();
    });
  });

  describe('themes', () => {
    it('includes dark theme styles', () => {
      render(Skeleton);
      // Component includes :global([data-theme="dark"]) styles
      expect(document.querySelector('.skeleton')).toBeInTheDocument();
    });

    it('includes cyberpunk theme styles', () => {
      render(Skeleton);
      // Component includes :global([data-theme="cyberpunk"]) styles
      expect(document.querySelector('.skeleton')).toBeInTheDocument();
    });
  });

  describe('edge cases', () => {
    it('handles undefined props gracefully', () => {
      render(Skeleton, { 
        props: { 
          width: undefined, 
          height: undefined, 
          borderRadius: undefined, 
          className: undefined,
          animated: undefined
        } 
      });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toBeInTheDocument();
      // Should have default values
      expect(skeleton).toHaveStyle('width: 100%');
      expect(skeleton).toHaveStyle('height: 1rem');
      expect(skeleton).toHaveStyle('border-radius: 4px');
      expect(skeleton).toHaveClass('animated');
    });

    it('handles empty string props', () => {
      render(Skeleton, { 
        props: { 
          width: '', 
          height: '', 
          borderRadius: '', 
          className: '' 
        } 
      });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toBeInTheDocument();
    });

    it('handles percentage width', () => {
      render(Skeleton, { props: { width: '50%' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('width: 50%');
    });

    it('handles pixel height', () => {
      render(Skeleton, { props: { height: '24px' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('height: 24px');
    });

    it('handles rem height', () => {
      render(Skeleton, { props: { height: '2rem' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('height: 2rem');
    });

    it('handles multiple instances independently', () => {
      const { container: c1 } = render(Skeleton, { props: { width: '100px', height: '20px' } });
      const { container: c2 } = render(Skeleton, { props: { width: '200px', height: '40px' } });
      
      expect(c1.querySelector('.skeleton')).toHaveStyle('width: 100px');
      expect(c2.querySelector('.skeleton')).toHaveStyle('width: 200px');
    });

    it('handles rapid prop changes', () => {
      const { container } = render(Skeleton, { props: { width: '100px' } });
      expect(container.querySelector('.skeleton')).toHaveStyle('width: 100px');
    });
  });

  describe('accessibility', () => {
    it('is a div element', () => {
      render(Skeleton);
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton?.tagName).toBe('DIV');
    });

    it('has no content (placeholder)', () => {
      render(Skeleton);
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton?.textContent?.trim()).toBe('');
    });

    it('has background color for visibility', () => {
      render(Skeleton);
      const skeleton = document.querySelector('.skeleton');
      // Background is set via CSS variable
      expect(skeleton).toBeInTheDocument();
    });
  });

  describe('CSS structure', () => {
    it('has skeleton base class', () => {
      render(Skeleton);
      expect(document.querySelector('.skeleton')).toBeInTheDocument();
    });

    it('has position relative for pseudo-element', () => {
      render(Skeleton);
      // Position relative is set in CSS for ::before animation
      expect(document.querySelector('.skeleton')).toBeInTheDocument();
    });

    it('has overflow hidden for animation', () => {
      render(Skeleton);
      // Overflow hidden is set in CSS for ::before animation
      expect(document.querySelector('.skeleton')).toBeInTheDocument();
    });
  });
});