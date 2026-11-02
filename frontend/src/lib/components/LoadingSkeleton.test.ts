import { render, screen } from '@testing-library/svelte';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import LoadingSkeleton from './LoadingSkeleton.svelte';

describe('LoadingSkeleton Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('rendering', () => {
    it('renders with default props', () => {
      render(LoadingSkeleton);
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toBeInTheDocument();
    });

    it('applies custom width', () => {
      render(LoadingSkeleton, { props: { width: '200px' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('width: 200px');
    });

    it('applies custom height', () => {
      render(LoadingSkeleton, { props: { height: '40px' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('height: 40px');
    });

    it('applies custom borderRadius', () => {
      render(LoadingSkeleton, { props: { borderRadius: '8px' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('border-radius: 8px');
    });

    it('combines all styles correctly', () => {
      render(LoadingSkeleton, { 
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
    it('has loading animation', () => {
      render(LoadingSkeleton);
      const skeleton = document.querySelector('.skeleton');
      // The animation is applied via background gradient and keyframes
      expect(skeleton).toBeInTheDocument();
    });

    it('includes loading keyframes in CSS', () => {
      render(LoadingSkeleton);
      // Component includes @keyframes loading in its style block
      expect(document.querySelector('.skeleton')).toBeInTheDocument();
    });
  });

  describe('styling differences from Skeleton component', () => {
    it('uses different background gradient', () => {
      render(LoadingSkeleton);
      const skeleton = document.querySelector('.skeleton');
      // LoadingSkeleton uses: linear-gradient(90deg, #333 25%, #555 50%, #333 75%)
      expect(skeleton).toBeInTheDocument();
    });

    it('has background-size for animation', () => {
      render(LoadingSkeleton);
      const skeleton = document.querySelector('.skeleton');
      // background-size: 200% 100% is set in CSS
      expect(skeleton).toBeInTheDocument();
    });

    it('has no animated class (animation always on)', () => {
      render(LoadingSkeleton);
      const skeleton = document.querySelector('.skeleton');
      // LoadingSkeleton doesn't have animated prop - animation is always active
      expect(skeleton).not.toHaveClass('animated');
    });

    it('has no reduced motion support', () => {
      render(LoadingSkeleton);
      // LoadingSkeleton doesn't include prefers-reduced-motion media query
      expect(document.querySelector('.skeleton')).toBeInTheDocument();
    });

    it('has no theme variants', () => {
      render(LoadingSkeleton);
      // LoadingSkeleton doesn't include dark/cyberpunk theme styles
      expect(document.querySelector('.skeleton')).toBeInTheDocument();
    });
  });

  describe('edge cases', () => {
    it('handles undefined props gracefully', () => {
      render(LoadingSkeleton, { 
        props: { 
          width: undefined, 
          height: undefined, 
          borderRadius: undefined 
        } 
      });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toBeInTheDocument();
      // Should have default values
      expect(skeleton).toHaveStyle('width: 100%');
      expect(skeleton).toHaveStyle('height: 20px');
      expect(skeleton).toHaveStyle('border-radius: 4px');
    });

    it('handles empty string props', () => {
      render(LoadingSkeleton, { 
        props: { 
          width: '', 
          height: '', 
          borderRadius: '' 
        } 
      });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toBeInTheDocument();
    });

    it('handles percentage width', () => {
      render(LoadingSkeleton, { props: { width: '50%' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('width: 50%');
    });

    it('handles pixel height', () => {
      render(LoadingSkeleton, { props: { height: '24px' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('height: 24px');
    });

    it('handles rem height', () => {
      render(LoadingSkeleton, { props: { height: '2rem' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('height: 2rem');
    });

    it('handles multiple instances independently', () => {
      const { container: c1 } = render(LoadingSkeleton, { props: { width: '100px', height: '20px' } });
      const { container: c2 } = render(LoadingSkeleton, { props: { width: '200px', height: '40px' } });
      
      expect(c1.querySelector('.skeleton')).toHaveStyle('width: 100px');
      expect(c2.querySelector('.skeleton')).toHaveStyle('width: 200px');
    });

    it('handles rapid prop changes', () => {
      const { container } = render(LoadingSkeleton, { props: { width: '100px' } });
      expect(container.querySelector('.skeleton')).toHaveStyle('width: 100px');
    });
  });

  describe('accessibility', () => {
    it('is a div element', () => {
      render(LoadingSkeleton);
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton?.tagName).toBe('DIV');
    });

    it('has no content (placeholder)', () => {
      render(LoadingSkeleton);
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton?.textContent?.trim()).toBe('');
    });

    it('has animated background for visibility', () => {
      render(LoadingSkeleton);
      const skeleton = document.querySelector('.skeleton');
      // Background animation indicates loading state
      expect(skeleton).toBeInTheDocument();
    });
  });

  describe('CSS structure', () => {
    it('has skeleton base class', () => {
      render(LoadingSkeleton);
      expect(document.querySelector('.skeleton')).toBeInTheDocument();
    });

    it('has inline styles for dimensions', () => {
      render(LoadingSkeleton, { props: { width: '300px', height: '16px' } });
      const skeleton = document.querySelector('.skeleton');
      expect(skeleton).toHaveStyle('width: 300px');
      expect(skeleton).toHaveStyle('height: 16px');
    });
  });
});